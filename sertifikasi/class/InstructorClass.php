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
             WHERE ks.user_id = ?
             ORDER BY ks.daftar_pada DESC',
            [$userId]
        )->fetchAll();
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