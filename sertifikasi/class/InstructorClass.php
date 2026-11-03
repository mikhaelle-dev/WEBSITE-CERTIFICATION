<?php
require_once __DIR__ . '/Database.php';

class InstructorClass extends Database
{
    private const CLASS_STATUSES = ['draft', 'aktif', 'ditutup'];
    private const MATERIAL_TYPES = ['teks', 'video', 'latihan'];

    private bool $schemaChecked = false;

    // ------------------------------------------------------------------
    // Helper dasar
    // ------------------------------------------------------------------

    /** Cek tabel hanya sekali per instance. */
    private function ensureClassroomSchema(): void
    {
        if ($this->schemaChecked) {
            return;
        }

        try {
            $this->db->query('SELECT 1 FROM kelas LIMIT 1');
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1146) {
                throw new RuntimeException(
                    'Fitur kelas belum siap. Jalankan migrasi sertifikasi/database/14_classroom.sql di database db_sertifikasi terlebih dahulu.'
                );
            }
            throw $e;
        }

        $columns = $this->db->query("SHOW COLUMNS FROM kelas")->fetchAll(PDO::FETCH_COLUMN);
        $columnDefinitions = [
            'icon_url' => 'VARCHAR(255) NULL DEFAULT NULL AFTER enroll_key',
            'badge_label' => 'VARCHAR(60) NULL DEFAULT NULL AFTER icon_url',
            'badge_aktif' => 'TINYINT(1) NOT NULL DEFAULT 0 AFTER badge_label',
            'badge_color' => 'VARCHAR(30) NULL DEFAULT NULL AFTER badge_aktif',
        ];
        foreach ($columnDefinitions as $column => $definition) {
            if (!in_array($column, $columns, true)) {
                $this->db->exec("ALTER TABLE kelas ADD COLUMN {$column} {$definition}");
            }
        }

        $this->schemaChecked = true;
    }

    /** Prepare + execute dalam satu langkah (sekaligus memastikan skema ada). */
    private function run(string $sql, array $params = []): PDOStatement
    {
        $this->ensureClassroomSchema();
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    private function requireText(array $data, string $key, string $message): string
    {
        $value = trim((string) ($data[$key] ?? ''));
        if ($value === '') {
            throw new InvalidArgumentException($message);
        }

        return $value;
    }

    private function nextOrder(string $table, string $column, int $parentId): int
    {
        // $table dan $column hanya dipanggil dari dalam class ini (konstanta).
        $max = $this->run("SELECT COALESCE(MAX(urutan), 0) FROM {$table} WHERE {$column} = ?", [$parentId])->fetchColumn();

        return (int) $max + 1;
    }

    // ------------------------------------------------------------------
    // Statistik & daftar kelas
    // ------------------------------------------------------------------

    public function getStats(int $instructorId): array
    {
        $classes = $this->run(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(status = 'aktif'), 0) AS aktif,
                    COALESCE(SUM(status = 'draft'), 0) AS draft,
                    COALESCE(SUM(status = 'ditutup'), 0) AS ditutup
             FROM kelas
             WHERE instruktur_id = ?",
            [$instructorId]
        )->fetch() ?: [];

        $students = $this->run(
            'SELECT COUNT(*) AS total, COALESCE(ROUND(AVG(ks.progress)), 0) AS rata
             FROM kelas_siswa ks
             JOIN kelas k ON k.id = ks.kelas_id
             WHERE k.instruktur_id = ?',
            [$instructorId]
        )->fetch() ?: [];

        return [
            'total_kelas'   => (int) ($classes['total'] ?? 0),
            'kelas_aktif'   => (int) ($classes['aktif'] ?? 0),
            'kelas_draft'   => (int) ($classes['draft'] ?? 0),
            'kelas_ditutup' => (int) ($classes['ditutup'] ?? 0),
            'total_siswa'   => (int) ($students['total'] ?? 0),
            'rata_progress' => (int) ($students['rata'] ?? 0),
        ];
    }

    public function getClasses(int $instructorId, string $search = ''): array
    {
        $sql = 'SELECT k.*,
                       (SELECT COUNT(*) FROM kelas_siswa WHERE kelas_id = k.id) AS total_siswa,
                       (SELECT ROUND(COALESCE(AVG(progress), 0)) FROM kelas_siswa WHERE kelas_id = k.id) AS avg_progress
                FROM kelas k
                WHERE k.instruktur_id = ?';
        $params = [$instructorId];

        if ($search !== '') {
            $sql .= ' AND (k.judul LIKE ? OR k.deskripsi LIKE ? OR k.enroll_key LIKE ?)';
            $term = '%' . $search . '%';
            array_push($params, $term, $term, $term);
        }

        return $this->run($sql . ' ORDER BY k.dibuat_pada DESC', $params)->fetchAll();
    }

    public function getClassById(int $instructorId, int $classId): ?array
    {
        $row = $this->run(
            'SELECT * FROM kelas WHERE id = ? AND instruktur_id = ? LIMIT 1',
            [$classId, $instructorId]
        )->fetch();

        return $row ?: null;
    }

    // ------------------------------------------------------------------
    // CRUD kelas
    // ------------------------------------------------------------------

    /** Validasi & normalisasi field kelas (dipakai create dan update). */
    private function classFields(array $data, array $current = []): array
    {
        $fallbackStatus = $current['status'] ?? 'draft';
        $status = $data['status'] ?? $fallbackStatus;
        if (!in_array($status, self::CLASS_STATUSES, true)) {
            $status = $fallbackStatus;
        }

        $iconUrl = trim((string) ($data['icon_url'] ?? ($current['icon_url'] ?? '')));
        $badgeLabel = trim((string) ($data['badge_label'] ?? ($current['badge_label'] ?? '')));
        $badgeColor = trim((string) ($data['badge_color'] ?? ($current['badge_color'] ?? '')));

        return [
            'judul'       => $this->requireText($data, 'judul', 'Judul dan deskripsi kelas wajib diisi.'),
            'deskripsi'   => $this->requireText($data, 'deskripsi', 'Judul dan deskripsi kelas wajib diisi.'),
            'status'      => $status,
            'icon_url'    => $iconUrl !== '' ? $iconUrl : null,
            'badge_label' => $badgeLabel !== '' ? $badgeLabel : null,
            // Checkbox yang tidak dicentang tidak dikirim, jadi dianggap 0.
            'badge_aktif' => !empty($data['badge_aktif']) ? 1 : 0,
            'badge_color' => $badgeColor !== '' ? $badgeColor : 'purple',
        ];
    }

    public function createClass(int $instructorId, array $data): int
    {
        $f = $this->classFields($data);

        $key = strtoupper(trim((string) ($data['enroll_key'] ?? '')));
        if ($key === '') {
            $key = $this->generateEnrollKey();
        } elseif ($this->enrollKeyTaken($key)) {
            throw new InvalidArgumentException('Kode enroll sudah dipakai kelas lain.');
        }

        $this->run(
            'INSERT INTO kelas (instruktur_id, slug, judul, deskripsi, status, enroll_key, icon_url, badge_label, badge_aktif, badge_color)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $instructorId, $this->uniqueSlug($f['judul']), $f['judul'], $f['deskripsi'], $f['status'],
                $key, $f['icon_url'], $f['badge_label'], $f['badge_aktif'], $f['badge_color'],
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function updateClass(int $instructorId, int $classId, array $data): void
    {
        $class = $this->getClassById($instructorId, $classId);
        if ($class === null) {
            throw new InvalidArgumentException('Kelas tidak ditemukan.');
        }

        $f = $this->classFields($data, $class);

        $key = strtoupper(trim((string) ($data['enroll_key'] ?? '')));
        if ($key === '') {
            $key = $class['enroll_key'];
        } elseif ($this->enrollKeyTaken($key, $classId)) {
            throw new InvalidArgumentException('Kode enroll sudah dipakai kelas lain.');
        }

        $this->run(
            'UPDATE kelas
             SET slug = ?, judul = ?, deskripsi = ?, status = ?, enroll_key = ?, icon_url = ?, badge_label = ?, badge_aktif = ?, badge_color = ?
             WHERE id = ? AND instruktur_id = ?',
            [
                $this->uniqueSlug($f['judul'], $classId), $f['judul'], $f['deskripsi'], $f['status'], $key,
                $f['icon_url'], $f['badge_label'], $f['badge_aktif'], $f['badge_color'],
                $classId, $instructorId,
            ]
        );
    }

    public function deleteClass(int $instructorId, int $classId): void
    {
        $stmt = $this->run('DELETE FROM kelas WHERE id = ? AND instruktur_id = ?', [$classId, $instructorId]);
        if ($stmt->rowCount() === 0) {
            throw new InvalidArgumentException('Kelas tidak ditemukan.');
        }
    }

    public function getClassStudents(int $instructorId, int $classId): array
    {
        // Pemeriksaan kepemilikan sudah ada di JOIN; bukan pemilik = hasil kosong.
        return $this->run(
            'SELECT ks.id, ks.user_id, ks.status, ks.progress, ks.daftar_pada,
                    u.nama, u.email, p.foto_profil
             FROM kelas_siswa ks
             JOIN kelas k ON k.id = ks.kelas_id
             JOIN user u ON u.id = ks.user_id
             LEFT JOIN profil_user p ON p.user_id = u.id
             WHERE ks.kelas_id = ? AND k.instruktur_id = ?
             ORDER BY ks.daftar_pada DESC',
            [$classId, $instructorId]
        )->fetchAll();
    }

    // ------------------------------------------------------------------
    // Modul & materi
    // ------------------------------------------------------------------

    /** Modul beserta materinya, memakai 2 query (bukan 1 query per modul). */
    public function getClassModules(int $classId): array
    {
        $modules = $this->run(
            'SELECT * FROM modul_kelas WHERE kelas_id = ? ORDER BY urutan ASC, id ASC',
            [$classId]
        )->fetchAll();

        if (!$modules) {
            return [];
        }

        $rows = $this->run(
            'SELECT mt.*
             FROM materi_kelas mt
             JOIN modul_kelas m ON m.id = mt.modul_id
             WHERE m.kelas_id = ?
             ORDER BY mt.urutan ASC, mt.id ASC',
            [$classId]
        )->fetchAll();

        $byModule = [];
        foreach ($rows as $row) {
            $byModule[(int) $row['modul_id']][] = $row;
        }

        foreach ($modules as &$module) {
            $module['materi'] = $byModule[(int) $module['id']] ?? [];
        }
        unset($module);

        return $modules;
    }

    public function getModuleMaterials(int $moduleId): array
    {
        return $this->run(
            'SELECT * FROM materi_kelas WHERE modul_id = ? ORDER BY urutan ASC, id ASC',
            [$moduleId]
        )->fetchAll();
    }

    public function getModuleById(int $instructorId, int $classId, int $moduleId): ?array
    {
        $row = $this->run(
            'SELECT m.* FROM modul_kelas m
             JOIN kelas k ON k.id = m.kelas_id
             WHERE m.id = ? AND m.kelas_id = ? AND k.instruktur_id = ?
             LIMIT 1',
            [$moduleId, $classId, $instructorId]
        )->fetch();

        return $row ?: null;
    }

    public function createModule(int $instructorId, int $classId, array $data): int
    {
        if ($this->getClassById($instructorId, $classId) === null) {
            throw new InvalidArgumentException('Kelas tidak ditemukan.');
        }

        $judul = $this->requireText($data, 'judul', 'Judul modul wajib diisi.');
        $urutan = (int) ($data['urutan'] ?? 0);
        if ($urutan <= 0) {
            $urutan = $this->nextOrder('modul_kelas', 'kelas_id', $classId);
        }

        $this->run(
            'INSERT INTO modul_kelas (kelas_id, urutan, judul, deskripsi) VALUES (?, ?, ?, ?)',
            [$classId, $urutan, $judul, trim((string) ($data['deskripsi'] ?? ''))]
        );

        return (int) $this->db->lastInsertId();
    }

    public function updateModule(int $instructorId, int $classId, int $moduleId, array $data): void
    {
        $module = $this->getModuleById($instructorId, $classId, $moduleId);
        if ($module === null) {
            throw new InvalidArgumentException('Modul tidak ditemukan.');
        }

        $this->run(
            'UPDATE modul_kelas SET judul = ?, deskripsi = ?, urutan = ? WHERE id = ? AND kelas_id = ?',
            [
                $this->requireText($data, 'judul', 'Judul modul wajib diisi.'),
                trim((string) ($data['deskripsi'] ?? '')),
                max(1, (int) ($data['urutan'] ?? $module['urutan'])),
                $moduleId,
                $classId,
            ]
        );
    }

    public function deleteModule(int $instructorId, int $classId, int $moduleId): void
    {
        if ($this->getModuleById($instructorId, $classId, $moduleId) === null) {
            throw new InvalidArgumentException('Modul tidak ditemukan.');
        }

        $this->run('DELETE FROM modul_kelas WHERE id = ? AND kelas_id = ?', [$moduleId, $classId]);
    }

    public function getMaterialById(int $instructorId, int $classId, int $materialId): ?array
    {
        // Alias "mk" dipakai karena "mod" adalah kata kunci MySQL.
        $row = $this->run(
            'SELECT mt.* FROM materi_kelas mt
             JOIN modul_kelas mk ON mk.id = mt.modul_id
             JOIN kelas k ON k.id = mk.kelas_id
             WHERE mt.id = ? AND mk.kelas_id = ? AND k.instruktur_id = ?
             LIMIT 1',
            [$materialId, $classId, $instructorId]
        )->fetch();

        return $row ?: null;
    }

    private function materialFields(array $data, array $current = []): array
    {
        $fallbackType = $current['tipe'] ?? 'teks';
        $tipe = $data['tipe'] ?? $fallbackType;

        return [
            'judul'  => $this->requireText($data, 'judul', 'Judul dan isi materi wajib diisi.'),
            'isi'    => $this->requireText($data, 'isi', 'Judul dan isi materi wajib diisi.'),
            'tipe'   => in_array($tipe, self::MATERIAL_TYPES, true) ? $tipe : $fallbackType,
            'urutan' => (int) ($data['urutan'] ?? ($current['urutan'] ?? 0)),
        ];
    }

    public function createMaterial(int $instructorId, int $classId, int $moduleId, array $data): int
    {
        if ($this->getModuleById($instructorId, $classId, $moduleId) === null) {
            throw new InvalidArgumentException('Modul tidak ditemukan untuk kelas ini.');
        }

        $f = $this->materialFields($data);
        $urutan = $f['urutan'] > 0 ? $f['urutan'] : $this->nextOrder('materi_kelas', 'modul_id', $moduleId);

        $this->run(
            'INSERT INTO materi_kelas (modul_id, urutan, judul, isi, tipe) VALUES (?, ?, ?, ?, ?)',
            [$moduleId, $urutan, $f['judul'], $f['isi'], $f['tipe']]
        );

        return (int) $this->db->lastInsertId();
    }

    public function updateMaterial(int $instructorId, int $classId, int $materialId, array $data): void
    {
        $material = $this->getMaterialById($instructorId, $classId, $materialId);
        if ($material === null) {
            throw new InvalidArgumentException('Materi tidak ditemukan.');
        }

        $f = $this->materialFields($data, $material);

        $this->run(
            'UPDATE materi_kelas SET judul = ?, isi = ?, tipe = ?, urutan = ? WHERE id = ? AND modul_id = ?',
            [$f['judul'], $f['isi'], $f['tipe'], max(1, $f['urutan']), $materialId, (int) $material['modul_id']]
        );
    }

    public function deleteMaterial(int $instructorId, int $classId, int $materialId): void
    {
        $material = $this->getMaterialById($instructorId, $classId, $materialId);
        if ($material === null) {
            throw new InvalidArgumentException('Materi tidak ditemukan.');
        }

        $this->run('DELETE FROM materi_kelas WHERE id = ? AND modul_id = ?', [$materialId, (int) $material['modul_id']]);
    }

    // ------------------------------------------------------------------
    // Kuis
    // ------------------------------------------------------------------

    public function getClassQuizzes(int $classId): array
    {
        return $this->run(
            'SELECT q.*,
                    (SELECT COUNT(*) FROM soal_kuis_kelas WHERE kuis_id = q.id) AS total_soal
             FROM kuis_kelas q
             WHERE q.kelas_id = ?
             ORDER BY q.dibuat_pada DESC',
            [$classId]
        )->fetchAll();
    }

    public function getQuizById(int $instructorId, int $classId, int $quizId): ?array
    {
        $row = $this->run(
            'SELECT q.* FROM kuis_kelas q
             JOIN kelas k ON k.id = q.kelas_id
             WHERE q.id = ? AND q.kelas_id = ? AND k.instruktur_id = ?
             LIMIT 1',
            [$quizId, $classId, $instructorId]
        )->fetch();

        return $row ?: null;
    }

    private function quizFields(array $data, array $current = []): array
    {
        $minimum = (int) ($data['nilai_minimum'] ?? ($current['nilai_minimum'] ?? 70));
        $durasi = (int) ($data['waktu_durasi'] ?? 0);

        return [
            'judul'         => $this->requireText($data, 'judul', 'Judul kuis wajib diisi.'),
            'nilai_minimum' => max(1, min(100, $minimum)),
            'waktu_durasi'  => $durasi > 0 ? $durasi : null,
        ];
    }

    public function createQuiz(int $instructorId, int $classId, array $data): int
    {
        if ($this->getClassById($instructorId, $classId) === null) {
            throw new InvalidArgumentException('Kelas tidak ditemukan.');
        }

        $f = $this->quizFields($data);
        $this->run(
            'INSERT INTO kuis_kelas (kelas_id, judul, nilai_minimum, waktu_durasi) VALUES (?, ?, ?, ?)',
            [$classId, $f['judul'], $f['nilai_minimum'], $f['waktu_durasi']]
        );

        return (int) $this->db->lastInsertId();
    }

    public function updateQuiz(int $instructorId, int $classId, int $quizId, array $data): void
    {
        $quiz = $this->getQuizById($instructorId, $classId, $quizId);
        if ($quiz === null) {
            throw new InvalidArgumentException('Kuis tidak ditemukan.');
        }

        $f = $this->quizFields($data, $quiz);
        $this->run(
            'UPDATE kuis_kelas SET judul = ?, nilai_minimum = ?, waktu_durasi = ? WHERE id = ? AND kelas_id = ?',
            [$f['judul'], $f['nilai_minimum'], $f['waktu_durasi'], $quizId, $classId]
        );
    }

    public function deleteQuiz(int $instructorId, int $classId, int $quizId): void
    {
        if ($this->getQuizById($instructorId, $classId, $quizId) === null) {
            throw new InvalidArgumentException('Kuis tidak ditemukan.');
        }

        $this->run('DELETE FROM kuis_kelas WHERE id = ? AND kelas_id = ?', [$quizId, $classId]);
    }

    public function createQuizQuestion(int $instructorId, int $classId, int $quizId, array $data): int
    {
        $quiz = $this->getQuizById($instructorId, $classId, $quizId);
        if ($quiz === null) {
            throw new InvalidArgumentException('Kuis tidak ditemukan.');
        }

        $teks = $this->requireText($data, 'teks', 'Teks soal wajib diisi.');
        $options = $this->normalizeQuestionOptions($data);
        $urutan = isset($data['urutan']) ? max(1, (int) $data['urutan']) : $this->nextOrder('soal_kuis_kelas', 'kuis_id', $quizId);

        $this->db->beginTransaction();
        try {
            $this->run(
                'INSERT INTO soal_kuis_kelas (kuis_id, urutan, teks) VALUES (?, ?, ?)',
                [$quizId, $urutan, $teks]
            );
            $questionId = (int) $this->db->lastInsertId();

            foreach ($options as $index => $optionText) {
                $this->run(
                    'INSERT INTO opsi_kuis_kelas (soal_id, urutan, teks, benar) VALUES (?, ?, ?, ?)',
                    [$questionId, $index + 1, $optionText, ((string) ($data['jawaban_benar'] ?? '') === (string) $index) ? 1 : 0]
                );
            }

            $this->db->commit();
            return $questionId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateQuizQuestion(int $instructorId, int $classId, int $quizId, int $questionId, array $data): void
    {
        $question = $this->run(
            'SELECT sq.*
             FROM soal_kuis_kelas sq
             JOIN kuis_kelas q ON q.id = sq.kuis_id
             JOIN kelas k ON k.id = q.kelas_id
             WHERE sq.id = ? AND q.id = ? AND q.kelas_id = ? AND k.instruktur_id = ?
             LIMIT 1',
            [$questionId, $quizId, $classId, $instructorId]
        )->fetch();

        if ($question === false) {
            throw new InvalidArgumentException('Soal kuis tidak ditemukan.');
        }

        $teks = $this->requireText($data, 'teks', 'Teks soal wajib diisi.');
        $options = $this->normalizeQuestionOptions($data);

        $this->db->beginTransaction();
        try {
            $this->run('UPDATE soal_kuis_kelas SET teks = ? WHERE id = ?', [$teks, $questionId]);
            $this->run('DELETE FROM opsi_kuis_kelas WHERE soal_id = ?', [$questionId]);

            foreach ($options as $index => $optionText) {
                $this->run(
                    'INSERT INTO opsi_kuis_kelas (soal_id, urutan, teks, benar) VALUES (?, ?, ?, ?)',
                    [$questionId, $index + 1, $optionText, ((string) ($data['jawaban_benar'] ?? '') === (string) $index) ? 1 : 0]
                );
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteQuizQuestion(int $instructorId, int $classId, int $questionId): void
    {
        $question = $this->run(
            'SELECT sq.id
             FROM soal_kuis_kelas sq
             JOIN kuis_kelas q ON q.id = sq.kuis_id
             JOIN kelas k ON k.id = q.kelas_id
             WHERE sq.id = ? AND q.kelas_id = ? AND k.instruktur_id = ?
             LIMIT 1',
            [$questionId, $classId, $instructorId]
        )->fetch();

        if ($question === false) {
            throw new InvalidArgumentException('Soal kuis tidak ditemukan.');
        }

        $this->run('DELETE FROM soal_kuis_kelas WHERE id = ?', [$questionId]);
    }

    public function getClassGrades(int $classId): array
    {
        $rows = $this->run(
            'SELECT ks.user_id, u.nama, u.email, ks.progress,
                    hk.kuis_id, q.judul AS kuis_judul, hk.nilai, hk.lulus
             FROM kelas_siswa ks
             JOIN user u ON u.id = ks.user_id
             LEFT JOIN hasil_kuis_kelas hk ON hk.user_id = ks.user_id
             LEFT JOIN kuis_kelas q ON q.id = hk.kuis_id
             WHERE ks.kelas_id = ?
             ORDER BY u.nama ASC, hk.dikerjakan_pada DESC',
            [$classId]
        )->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $userId = (int) $row['user_id'];
            if (!isset($grouped[$userId])) {
                $grouped[$userId] = [
                    'user_id' => $userId,
                    'nama' => $row['nama'],
                    'email' => $row['email'],
                    'progress' => (int) ($row['progress'] ?? 0),
                    'quizzes' => [],
                ];
            }

            if (!empty($row['kuis_id'])) {
                $grouped[$userId]['quizzes'][(int) $row['kuis_id']] = [
                    'kuis_id' => (int) $row['kuis_id'],
                    'judul' => $row['kuis_judul'],
                    'nilai' => $row['nilai'] !== null ? (int) $row['nilai'] : null,
                    'lulus' => !empty($row['lulus']) ? 1 : 0,
                ];
            }
        }

        return array_values($grouped);
    }

    public function removeStudentFromClass(int $instructorId, int $classId, int $studentId): void
    {
        $class = $this->getClassById($instructorId, $classId);
        if ($class === null) {
            throw new InvalidArgumentException('Kelas tidak ditemukan.');
        }

        $this->run('DELETE FROM kelas_siswa WHERE kelas_id = ? AND user_id = ?', [$classId, $studentId]);
    }

    private function normalizeQuestionOptions(array $data): array
    {
        $options = [];
        for ($i = 0; $i < 6; $i++) {
            $label = trim((string) ($data['option_' . $i] ?? ''));
            if ($label === '') {
                continue;
            }
            $options[] = $label;
        }

        if (count($options) < 2) {
            throw new InvalidArgumentException('Setiap soal pilihan ganda minimal harus memiliki 2 opsi jawaban.');
        }

        if (!isset($data['jawaban_benar']) || !isset($options[(int) $data['jawaban_benar']])) {
            throw new InvalidArgumentException('Pilih satu jawaban benar untuk soal ini.');
        }

        return $options;
    }

    // ------------------------------------------------------------------
    // Sisi siswa
    // ------------------------------------------------------------------

    public function enrollStudentByKey(int $userId, string $enrollKey): ?array
    {
        $key = strtoupper(trim($enrollKey));
        if ($key === '') {
            return null;
        }

        $class = $this->run(
            'SELECT id, instruktur_id, slug, judul, status FROM kelas WHERE enroll_key = ? LIMIT 1',
            [$key]
        )->fetch();

        // Hanya kelas aktif yang bisa diikuti.
        if (!$class || strtolower((string) $class['status']) !== 'aktif') {
            return null;
        }

        $alreadyEnrolled = $this->run(
            'SELECT 1 FROM kelas_siswa WHERE kelas_id = ? AND user_id = ? LIMIT 1',
            [(int) $class['id'], $userId]
        )->fetchColumn();

        if (!$alreadyEnrolled) {
            $this->run(
                "INSERT INTO kelas_siswa (kelas_id, user_id, status, progress) VALUES (?, ?, 'aktif', 0)",
                [(int) $class['id'], $userId]
            );
        }

        return $class;
    }

    public function getStudentDashboardClasses(int $userId): array
    {
        return $this->run(
            'SELECT k.id, k.slug, k.judul, k.deskripsi, k.status,
                    ks.progress, ks.status AS enrollment_status, ks.daftar_pada,
                    u.nama AS instruktur_nama
             FROM kelas_siswa ks
             JOIN kelas k ON k.id = ks.kelas_id
             JOIN user u ON u.id = k.instruktur_id
             WHERE ks.user_id = ? AND k.status <> "draft"
             ORDER BY ks.daftar_pada DESC',
            [$userId]
        )->fetchAll();
    }

    public function getAvailableClassesForStudent(int $userId): array
    {
        return $this->run(
            "SELECT k.id, k.slug, k.judul, k.deskripsi, k.icon_url,
                    u.nama AS instruktur_nama,
                    (SELECT COUNT(*) FROM kelas_siswa peserta
                     WHERE peserta.kelas_id = k.id AND peserta.status = 'aktif') AS total_siswa
             FROM kelas k
             JOIN user u ON u.id = k.instruktur_id
             LEFT JOIN kelas_siswa terdaftar
                ON terdaftar.kelas_id = k.id AND terdaftar.user_id = ?
             WHERE k.status = 'aktif' AND terdaftar.id IS NULL
             ORDER BY k.diperbarui_pada DESC, k.dibuat_pada DESC",
            [$userId]
        )->fetchAll();
    }

    public function getStudentClassById(int $userId, int $classId): ?array
    {
        $row = $this->run(
            'SELECT k.*, ks.progress, ks.status AS enrollment_status, ks.daftar_pada
             FROM kelas_siswa ks
             JOIN kelas k ON k.id = ks.kelas_id
             WHERE ks.user_id = ? AND k.id = ? AND k.status <> "draft"
             LIMIT 1',
            [$userId, $classId]
        )->fetch();

        return $row ?: null;
    }

    public function getClassAnnouncements(int $classId): array
    {
        try {
            $this->run('SELECT 1 FROM pengumuman_kelas LIMIT 1');
        } catch (PDOException $e) {
            return [];
        }

        return $this->run(
            'SELECT pk.*, u.nama AS penulis_nama
             FROM pengumuman_kelas pk
             JOIN user u ON u.id = pk.penulis_id
             WHERE pk.kelas_id = ?
             ORDER BY pk.dibuat_pada DESC',
            [$classId]
        )->fetchAll();
    }

    public function createClassAnnouncement(int $instructorId, int $classId, string $content): void
    {
        if ($this->getClassById($instructorId, $classId) === null) {
            throw new InvalidArgumentException('Kelas tidak ditemukan.');
        }

        $message = trim($content);
        if ($message === '') {
            throw new InvalidArgumentException('Isi pengumuman tidak boleh kosong.');
        }

        $this->run(
            'INSERT INTO pengumuman_kelas (kelas_id, penulis_id, isi) VALUES (?, ?, ?)',
            [$classId, $instructorId, $message]
        );
    }

    public function deleteClassAnnouncement(int $instructorId, int $classId, int $announcementId): void
    {
        $this->run(
            'DELETE pk FROM pengumuman_kelas pk
             JOIN kelas k ON k.id = pk.kelas_id
             WHERE pk.id = ? AND pk.kelas_id = ? AND k.instruktur_id = ?',
            [$announcementId, $classId, $instructorId]
        );
    }

    public function markMaterialOpened(int $userId, int $classId, int $materialId): void
    {
        $material = $this->run(
            'SELECT mt.id
             FROM materi_kelas mt
             JOIN modul_kelas mk ON mk.id = mt.modul_id
             WHERE mt.id = ? AND mk.kelas_id = ?',
            [$materialId, $classId]
        )->fetch();

        if ($material === false) {
            throw new InvalidArgumentException('Materi tidak ditemukan di kelas ini.');
        }

        $this->run(
            'INSERT INTO progres_materi_kelas (user_id, materi_id, pertama_dibuka, selesai_dibuka)
             VALUES (?, ?, NOW(), 1)
             ON DUPLICATE KEY UPDATE selesai_dibuka = 1, pertama_dibuka = IFNULL(pertama_dibuka, NOW())',
            [$userId, $materialId]
        );

        $this->updateClassProgressForUser($userId, $classId);
    }

    public function getClassQuizDetailed(int $classId): array
    {
        $quizzes = $this->run(
            'SELECT q.*
             FROM kuis_kelas q
             WHERE q.kelas_id = ?
             ORDER BY q.dibuat_pada DESC',
            [$classId]
        )->fetchAll();

        foreach ($quizzes as &$quiz) {
            $quiz['soal'] = $this->getQuizQuestions((int) $quiz['id']);
        }
        unset($quiz);

        return $quizzes;
    }

    public function getStudentClassQuizResults(int $userId, int $classId): array
    {
        return $this->run(
            'SELECT hk.*, q.judul AS kuis_judul
             FROM hasil_kuis_kelas hk
             JOIN kuis_kelas q ON q.id = hk.kuis_id
             WHERE hk.user_id = ? AND q.kelas_id = ?
             ORDER BY hk.dikerjakan_pada DESC',
            [$userId, $classId]
        )->fetchAll();
    }

    public function getOpenedMaterialIds(int $userId, int $classId): array
    {
        $rows = $this->run(
            'SELECT DISTINCT pm.materi_id
             FROM progres_materi_kelas pm
             JOIN materi_kelas mt ON mt.id = pm.materi_id
             JOIN modul_kelas mk ON mk.id = mt.modul_id
             WHERE pm.user_id = ? AND mk.kelas_id = ?',
            [$userId, $classId]
        )->fetchAll();

        return array_map(static fn ($row) => (int) $row['materi_id'], $rows);
    }

    public function getQuizQuestions(int $quizId): array
    {
        $questions = $this->run(
            'SELECT * FROM soal_kuis_kelas WHERE kuis_id = ? ORDER BY urutan ASC, id ASC',
            [$quizId]
        )->fetchAll();

        if (!$questions) {
            return [];
        }

        $questionIds = array_map(static fn ($row) => (int) $row['id'], $questions);
        $options = [];
        if ($questionIds !== []) {
            $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
            $options = $this->run(
                'SELECT * FROM opsi_kuis_kelas WHERE soal_id IN (' . $placeholders . ') ORDER BY soal_id ASC, urutan ASC',
                $questionIds
            )->fetchAll();
        }

        $byQuestion = [];
        foreach ($options as $option) {
            $byQuestion[(int) $option['soal_id']][] = $option;
        }

        foreach ($questions as &$question) {
            $question['opsi'] = $byQuestion[(int) $question['id']] ?? [];
        }
        unset($question);

        return $questions;
    }

    public function saveClassQuizAttempt(int $userId, int $classId, int $quizId, array $answers): array
    {
        $quiz = $this->run(
            'SELECT q.*
             FROM kuis_kelas q
             JOIN kelas_siswa ks ON ks.kelas_id = q.kelas_id
             WHERE q.id = ? AND q.kelas_id = ? AND ks.user_id = ?
             LIMIT 1',
            [$quizId, $classId, $userId]
        )->fetch();

        if ($quiz === false) {
            throw new InvalidArgumentException('Kuis ini tidak tersedia untuk murid yang terdaftar.');
        }

        $questions = $this->getQuizQuestions($quizId);
        if (!$questions) {
            throw new InvalidArgumentException('Kuis belum memiliki soal yang valid.');
        }

        $correct = 0;
        foreach ($questions as $question) {
            $selectedId = isset($answers[(int) $question['id']]) ? (int) $answers[(int) $question['id']] : 0;
            $found = false;
            foreach ($question['opsi'] as $option) {
                if ((int) $option['id'] === $selectedId) {
                    $found = true;
                    if ((int) $option['benar'] === 1) {
                        $correct++;
                    }
                    break;
                }
            }

            if (!$found) {
                throw new InvalidArgumentException('Semua soal harus dijawab dengan pilihan yang tersedia.');
            }
        }

        $nilai = (int) round(($correct / count($questions)) * 100);
        $lulus = $nilai >= (int) $quiz['nilai_minimum'];

        $this->run(
            'INSERT INTO hasil_kuis_kelas (user_id, kuis_id, nilai, jumlah_benar, jumlah_soal, lulus)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $quizId, $nilai, $correct, count($questions), $lulus ? 1 : 0]
        );

        $this->updateClassProgressForUser($userId, $classId);

        return [
            'nilai' => $nilai,
            'jumlah_benar' => $correct,
            'jumlah_soal' => count($questions),
            'lulus' => $lulus,
        ];
    }

    public function updateClassProgressForUser(int $userId, int $classId): void
    {
        $totalMateri = (int) $this->run(
            'SELECT COUNT(*)
             FROM materi_kelas mt
             JOIN modul_kelas mk ON mk.id = mt.modul_id
             WHERE mk.kelas_id = ?',
            [$classId]
        )->fetchColumn();

        $openedMateri = (int) $this->run(
            'SELECT COUNT(*)
             FROM progres_materi_kelas pm
             JOIN materi_kelas mt ON mt.id = pm.materi_id
             JOIN modul_kelas mk ON mk.id = mt.modul_id
             WHERE pm.user_id = ? AND mk.kelas_id = ?',
            [$userId, $classId]
        )->fetchColumn();

        $totalKuis = (int) $this->run(
            'SELECT COUNT(*) FROM kuis_kelas WHERE kelas_id = ?',
            [$classId]
        )->fetchColumn();

        $lulusKuis = (int) $this->run(
            'SELECT COUNT(*)
             FROM hasil_kuis_kelas hk
             JOIN kuis_kelas q ON q.id = hk.kuis_id
             WHERE hk.user_id = ? AND q.kelas_id = ? AND hk.lulus = 1',
            [$userId, $classId]
        )->fetchColumn();

        $denominator = max(1, $totalMateri + $totalKuis);
        $progress = (int) round((($openedMateri + $lulusKuis) / $denominator) * 100);
        $progress = max(0, min(100, $progress));

        $this->run(
            'UPDATE kelas_siswa SET progress = ? WHERE user_id = ? AND kelas_id = ?',
            [$progress, $userId, $classId]
        );
    }

    // ------------------------------------------------------------------
    // Kode enroll & slug
    // ------------------------------------------------------------------

    private function enrollKeyTaken(string $key, int $excludeClassId = 0): bool
    {
        return (bool) $this->run(
            'SELECT 1 FROM kelas WHERE enroll_key = ? AND id <> ? LIMIT 1',
            [$key, $excludeClassId]
        )->fetchColumn();
    }

    private function generateEnrollKey(): string
    {
        do {
            $key = 'CLASS-' . strtoupper(bin2hex(random_bytes(4)));
        } while ($this->enrollKeyTaken($key));

        return $key;
    }

    /** Slug unik; kelas yang sedang diedit (excludeId) tidak dianggap bentrok dengan dirinya sendiri. */
    private function uniqueSlug(string $title, int $excludeId = 0): string
    {
        $base = strtolower(trim($title));
        $base = preg_replace('/[^a-z0-9\s-]+/', '', $base) ?? '';
        $base = trim((string) preg_replace('/[\s-]+/', '-', $base), '-');

        if ($base === '') {
            throw new InvalidArgumentException('Judul kelas tidak valid untuk slug.');
        }

        $slug = $base;
        for ($i = 1; $this->slugTaken($slug, $excludeId); $i++) {
            $slug = $base . '-' . $i;
        }

        return $slug;
    }

    private function slugTaken(string $slug, int $excludeId): bool
    {
        return (bool) $this->run(
            'SELECT 1 FROM kelas WHERE slug = ? AND id <> ? LIMIT 1',
            [$slug, $excludeId]
        )->fetchColumn();
    }
}