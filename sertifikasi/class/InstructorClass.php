<?php
require_once __DIR__ . '/Database.php';

class InstructorClass extends Database
{
    private const CLASS_STATUSES = ['draft', 'aktif', 'ditutup'];
    private const CLASS_CATEGORIES = ['Umum', 'Teknologi', 'Bisnis', 'Desain', 'Data', 'Lainnya'];
    private const CLASS_LEVELS = ['Beginner', 'Intermediate', 'Advanced'];
    private const MATERIAL_TYPES = ['teks', 'video', 'dokumen', 'latihan'];

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
            'kategori' => "VARCHAR(80) NOT NULL DEFAULT 'Umum' AFTER deskripsi",
            'durasi_estimasi' => 'SMALLINT UNSIGNED NULL DEFAULT NULL AFTER kategori',
            'tingkat' => "VARCHAR(30) NOT NULL DEFAULT 'Beginner' AFTER durasi_estimasi",
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

        $classKeyColumn = $this->db->query("SHOW COLUMNS FROM kelas LIKE 'enroll_key'")->fetch();
        if ($classKeyColumn && (string) $classKeyColumn['Null'] !== 'YES') {
            $this->db->exec('ALTER TABLE kelas MODIFY COLUMN enroll_key VARCHAR(50) NULL DEFAULT NULL');
        }

        $moduleColumns = $this->db->query("SHOW COLUMNS FROM modul_kelas")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('enroll_key', $moduleColumns, true)) {
            $this->db->exec('ALTER TABLE modul_kelas ADD COLUMN enroll_key VARCHAR(50) NULL DEFAULT NULL AFTER deskripsi');
        }

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS kelas_modul_siswa (
                modul_id INT NOT NULL,
                user_id INT NOT NULL,
                dibuka_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (modul_id, user_id),
                CONSTRAINT fk_kelas_modul_siswa_modul FOREIGN KEY (modul_id) REFERENCES modul_kelas(id) ON DELETE CASCADE,
                CONSTRAINT fk_kelas_modul_siswa_user FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
            )"
        );
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS kelas_lencana_siswa (
                id INT AUTO_INCREMENT PRIMARY KEY,
                class_id INT NOT NULL,
                student_id INT NOT NULL,
                badge_label VARCHAR(60) NOT NULL,
                badge_color VARCHAR(30) NOT NULL DEFAULT 'purple',
                earned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_kelas_lencana_siswa (class_id, student_id),
                CONSTRAINT fk_kelas_lencana_siswa_class FOREIGN KEY (class_id) REFERENCES kelas(id) ON DELETE CASCADE,
                CONSTRAINT fk_kelas_lencana_siswa_user FOREIGN KEY (student_id) REFERENCES user(id) ON DELETE CASCADE
            )"
        );

        $materialTypeColumn = $this->db->query("SHOW COLUMNS FROM materi_kelas LIKE 'tipe'")->fetch();
        if ($materialTypeColumn) {
            foreach (self::MATERIAL_TYPES as $materialType) {
                if (strpos((string) $materialTypeColumn['Type'], "'" . $materialType . "'") === false) {
                    $this->db->exec(
                        "ALTER TABLE materi_kelas MODIFY COLUMN tipe ENUM('teks', 'video', 'dokumen', 'latihan') NOT NULL DEFAULT 'teks'"
                    );
                    break;
                }
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

    public function getMonthlyRegistrations(int $instructorId): array
    {
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $firstMonth = (new DateTimeImmutable('first day of this month'))->modify('-5 months');
        $rows = $this->run(
            "SELECT DATE_FORMAT(ks.daftar_pada, '%Y-%m') AS bulan, COUNT(*) AS total
             FROM kelas_siswa ks
             JOIN kelas k ON k.id = ks.kelas_id
             WHERE k.instruktur_id = ? AND ks.daftar_pada >= ?
             GROUP BY DATE_FORMAT(ks.daftar_pada, '%Y-%m')",
            [$instructorId, $firstMonth->format('Y-m-d 00:00:00')]
        )->fetchAll();

        $totalsByMonth = [];
        foreach ($rows as $row) {
            $totalsByMonth[(string) $row['bulan']] = (int) $row['total'];
        }

        $months = [];
        for ($offset = 0; $offset < 6; $offset++) {
            $month = $firstMonth->modify('+' . $offset . ' months');
            $key = $month->format('Y-m');
            $months[] = [
                'label' => $monthNames[(int) $month->format('n') - 1],
                'total' => $totalsByMonth[$key] ?? 0,
            ];
        }

        return $months;
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
        $category = trim((string) ($data['kategori'] ?? ($current['kategori'] ?? 'Umum')));
        $levelValue = $data['tingkat'] ?? ($current['tingkat'] ?? 'Beginner');
        $level = is_string($levelValue) ? trim($levelValue) : '';
        $durationValue = $data['durasi_estimasi'] ?? ($current['durasi_estimasi'] ?? null);
        $durationValue = is_string($durationValue) ? trim($durationValue) : $durationValue;
        $duration = $durationValue === null || $durationValue === ''
            ? null
            : ((is_string($durationValue) || is_int($durationValue))
                ? filter_var($durationValue, FILTER_VALIDATE_INT)
                : false);
        $badgeLabel = trim((string) ($data['badge_label'] ?? ($current['badge_label'] ?? '')));
        $badgeColor = trim((string) ($data['badge_color'] ?? ($current['badge_color'] ?? '')));
        $badgeActive = !empty($data['badge_aktif']) ? 1 : 0;
        if ($badgeActive && $badgeLabel === '') {
            throw new InvalidArgumentException('Nama badge wajib diisi jika badge otomatis diaktifkan.');
        }

        if (!in_array($category, self::CLASS_CATEGORIES, true)) {
            throw new InvalidArgumentException('Kategori kelas tidak valid.');
        }
        if (!in_array($level, self::CLASS_LEVELS, true)) {
            throw new InvalidArgumentException('Tingkat kelas tidak valid.');
        }
        if ($duration === false || ($duration !== null && ($duration < 1 || $duration > 1000))) {
            throw new InvalidArgumentException('Durasi estimasi harus antara 1 sampai 1000 jam, atau kosongkan jika belum ditentukan.');
        }
        if (!in_array($badgeColor, ['purple', 'green', 'blue', 'amber'], true)) {
            $badgeColor = 'purple';
        }

        return [
            'judul'       => $this->requireText($data, 'judul', 'Judul dan deskripsi kelas wajib diisi.'),
            'deskripsi'   => $this->requireText($data, 'deskripsi', 'Judul dan deskripsi kelas wajib diisi.'),
            'kategori'    => $category,
            'durasi_estimasi' => $duration,
            'tingkat'      => $level,
            'status'      => $status,
            'icon_url'    => $iconUrl !== '' ? $iconUrl : null,
            'badge_label' => $badgeLabel !== '' ? $badgeLabel : null,
            // Checkbox yang tidak dicentang tidak dikirim, jadi dianggap 0.
            'badge_aktif' => $badgeActive,
            'badge_color' => $badgeColor,
        ];
    }

    public function createClass(int $instructorId, array $data): int
    {
        $f = $this->classFields($data);

        $key = strtoupper(trim((string) ($data['enroll_key'] ?? '')));
        if ($f['status'] === 'aktif') {
            $key = '';
        }
        if (strlen($key) > 50) {
            throw new InvalidArgumentException('Kode enroll kelas maksimal 50 karakter.');
        }
        if ($key !== '' && $this->enrollKeyTaken($key)) {
            throw new InvalidArgumentException('Kode enroll sudah dipakai kelas lain.');
        }
        $key = $key !== '' ? $key : null;

        $this->run(
            'INSERT INTO kelas (instruktur_id, slug, judul, deskripsi, kategori, durasi_estimasi, tingkat, status, enroll_key, icon_url, badge_label, badge_aktif, badge_color)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $instructorId, $this->uniqueSlug($f['judul']), $f['judul'], $f['deskripsi'], $f['kategori'],
                $f['durasi_estimasi'], $f['tingkat'], $f['status'],
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
        if ($f['status'] === 'aktif') {
            $key = '';
        }
        if (strlen($key) > 50) {
            throw new InvalidArgumentException('Kode enroll kelas maksimal 50 karakter.');
        }
        if ($key !== '' && $this->enrollKeyTaken($key, $classId)) {
            throw new InvalidArgumentException('Kode enroll sudah dipakai kelas lain.');
        }
        $key = $key !== '' ? $key : null;
        $previousIcon = (string) ($class['icon_url'] ?? '');

        $this->run(
            'UPDATE kelas
             SET slug = ?, judul = ?, deskripsi = ?, kategori = ?, durasi_estimasi = ?, tingkat = ?, status = ?, enroll_key = ?, icon_url = ?, badge_label = ?, badge_aktif = ?, badge_color = ?
             WHERE id = ? AND instruktur_id = ?',
            [
                $this->uniqueSlug($f['judul'], $classId), $f['judul'], $f['deskripsi'], $f['kategori'],
                $f['durasi_estimasi'], $f['tingkat'], $f['status'], $key,
                $f['icon_url'], $f['badge_label'], $f['badge_aktif'], $f['badge_color'],
                $classId, $instructorId,
            ]
        );
        $newIcon = (string) ($f['icon_url'] ?? '');
        if ($previousIcon !== '' && $previousIcon !== $newIcon) {
            $this->deleteClassUploadIfUnreferenced($previousIcon, 'class-icons');
        }
    }

    public function deleteClass(int $instructorId, int $classId): void
    {
        $classIcon = $this->run(
            'SELECT icon_url FROM kelas WHERE id = ? AND instruktur_id = ? LIMIT 1',
            [$classId, $instructorId]
        )->fetchColumn();
        if ($classIcon === false) {
            throw new InvalidArgumentException('Kelas tidak ditemukan.');
        }

        $materialFiles = $this->run(
            'SELECT mt.isi
             FROM materi_kelas mt
             JOIN modul_kelas m ON m.id = mt.modul_id
             WHERE m.kelas_id = ?',
            [$classId]
        )->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $this->run('DELETE FROM kelas WHERE id = ? AND instruktur_id = ?', [$classId, $instructorId]);
        if ($stmt->rowCount() === 0) {
            throw new InvalidArgumentException('Kelas tidak ditemukan.');
        }

        if (is_string($classIcon) && $classIcon !== '') {
            $this->deleteClassUploadIfUnreferenced($classIcon, 'class-icons');
        }
        foreach (array_unique($materialFiles) as $materialFile) {
            if (is_string($materialFile) && $materialFile !== '') {
                $this->deleteClassUploadIfUnreferenced($materialFile, 'class-materials');
            }
        }
    }

    public function deleteClassUploadIfUnreferenced(string $relativePath, string $uploadType): void
    {
        $patterns = [
            'class-icons' => [
                'regex' => '~\A\.\./\.\./uploads/class-icons/(class-\d+-\d+-[a-f0-9]{16}\.(?:jpg|png|webp))\z~D',
                'referenceQuery' => 'SELECT 1 FROM kelas WHERE icon_url = ? LIMIT 1',
            ],
            'class-materials' => [
                'regex' => '~\A\.\./\.\./uploads/class-materials/(material-\d+-[a-f0-9]{24}\.(?:pdf|mp4|webm|jpg|png|webp|docx|pptx))\z~D',
                'referenceQuery' => 'SELECT 1 FROM materi_kelas WHERE isi = ? LIMIT 1',
            ],
        ];
        if (!isset($patterns[$uploadType]) || !preg_match($patterns[$uploadType]['regex'], $relativePath, $matches)) {
            error_log('Class upload cleanup skipped an invalid ' . $uploadType . ' path.');
            return;
        }

        if ($this->run($patterns[$uploadType]['referenceQuery'], [$relativePath])->fetchColumn()) {
            return;
        }

        $filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR
            . $uploadType . DIRECTORY_SEPARATOR . $matches[1];
        if ((is_file($filePath) || is_link($filePath)) && !unlink($filePath)) {
            error_log('Class upload cleanup failed for ' . $filePath);
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

    public function getStudentClassModules(int $userId, int $classId): array
    {
        $modules = $this->run(
            'SELECT m.id, m.kelas_id, m.urutan, m.judul, m.deskripsi,
                    (COALESCE(m.enroll_key, "") <> "") AS is_private,
                    (COALESCE(m.enroll_key, "") = "" OR EXISTS (
                        SELECT 1 FROM kelas_modul_siswa module_access
                        WHERE module_access.modul_id = m.id AND module_access.user_id = ?
                    )) AS is_unlocked,
                    (SELECT COUNT(*) FROM materi_kelas mt WHERE mt.modul_id = m.id) AS total_materi
             FROM modul_kelas m
             JOIN kelas_siswa ks ON ks.kelas_id = m.kelas_id AND ks.user_id = ?
             WHERE m.kelas_id = ?
             ORDER BY m.urutan ASC, m.id ASC',
            [$userId, $userId, $classId]
        )->fetchAll();

        $unlockedModuleIds = [];
        foreach ($modules as &$module) {
            $module['is_private'] = (bool) $module['is_private'];
            $module['is_unlocked'] = (bool) $module['is_unlocked'];
            $module['materi'] = [];
            if ($module['is_unlocked']) {
                $unlockedModuleIds[] = (int) $module['id'];
            }
        }
        unset($module);

        if ($unlockedModuleIds === []) {
            return $modules;
        }

        $placeholders = implode(',', array_fill(0, count($unlockedModuleIds), '?'));
        $materials = $this->run(
            'SELECT * FROM materi_kelas WHERE modul_id IN (' . $placeholders . ') ORDER BY urutan ASC, id ASC',
            $unlockedModuleIds
        )->fetchAll();
        $byModule = [];
        foreach ($materials as $material) {
            $byModule[(int) $material['modul_id']][] = $material;
        }
        foreach ($modules as &$module) {
            if ($module['is_unlocked']) {
                $module['materi'] = $byModule[(int) $module['id']] ?? [];
            }
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
        $key = strtoupper(trim((string) ($data['enroll_key'] ?? '')));
        if (strlen($key) > 50) {
            throw new InvalidArgumentException('Kode akses modul maksimal 50 karakter.');
        }
        if ($key !== '' && $this->moduleEnrollKeyTaken($classId, $key)) {
            throw new InvalidArgumentException('Kode akses modul sudah dipakai modul lain di kelas ini.');
        }
        $key = $key !== '' ? $key : null;
        $urutan = (int) ($data['urutan'] ?? 0);
        if ($urutan <= 0) {
            $urutan = $this->nextOrder('modul_kelas', 'kelas_id', $classId);
        }

        $this->run(
            'INSERT INTO modul_kelas (kelas_id, urutan, judul, deskripsi, enroll_key) VALUES (?, ?, ?, ?, ?)',
            [$classId, $urutan, $judul, trim((string) ($data['deskripsi'] ?? '')), $key]
        );

        return (int) $this->db->lastInsertId();
    }

    public function updateModule(int $instructorId, int $classId, int $moduleId, array $data): void
    {
        $module = $this->getModuleById($instructorId, $classId, $moduleId);
        if ($module === null) {
            throw new InvalidArgumentException('Modul tidak ditemukan.');
        }

        $key = strtoupper(trim((string) ($data['enroll_key'] ?? '')));
        if (strlen($key) > 50) {
            throw new InvalidArgumentException('Kode akses modul maksimal 50 karakter.');
        }
        if ($key !== '' && $this->moduleEnrollKeyTaken($classId, $key, $moduleId)) {
            throw new InvalidArgumentException('Kode akses modul sudah dipakai modul lain di kelas ini.');
        }
        $key = $key !== '' ? $key : null;
        $previousKey = strtoupper(trim((string) ($module['enroll_key'] ?? '')));
        $order = (int) ($data['urutan'] ?? 0);
        if ($order <= 0) {
            $order = (int) $module['urutan'];
        }

        $this->run(
            'UPDATE modul_kelas SET judul = ?, deskripsi = ?, enroll_key = ?, urutan = ? WHERE id = ? AND kelas_id = ?',
            [
                $this->requireText($data, 'judul', 'Judul modul wajib diisi.'),
                trim((string) ($data['deskripsi'] ?? '')),
                $key,
                $order,
                $moduleId,
                $classId,
            ]
        );
        if ($previousKey !== (string) ($key ?? '')) {
            $this->run('DELETE FROM kelas_modul_siswa WHERE modul_id = ?', [$moduleId]);
        }
    }

    public function deleteModule(int $instructorId, int $classId, int $moduleId): void
    {
        if ($this->getModuleById($instructorId, $classId, $moduleId) === null) {
            throw new InvalidArgumentException('Modul tidak ditemukan.');
        }

        $materialFiles = array_column($this->getModuleMaterials($moduleId), 'isi');
        $this->run('DELETE FROM modul_kelas WHERE id = ? AND kelas_id = ?', [$moduleId, $classId]);
        foreach (array_unique($materialFiles) as $materialFile) {
            if (is_string($materialFile) && $materialFile !== '') {
                $this->deleteClassUploadIfUnreferenced($materialFile, 'class-materials');
            }
        }
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
        if ($material['isi'] !== $f['isi']) {
            $this->deleteClassUploadIfUnreferenced((string) $material['isi'], 'class-materials');
        }
    }

    public function deleteMaterial(int $instructorId, int $classId, int $materialId): void
    {
        $material = $this->getMaterialById($instructorId, $classId, $materialId);
        if ($material === null) {
            throw new InvalidArgumentException('Materi tidak ditemukan.');
        }

        $this->run('DELETE FROM materi_kelas WHERE id = ? AND modul_id = ?', [$materialId, (int) $material['modul_id']]);
        $this->deleteClassUploadIfUnreferenced((string) $material['isi'], 'class-materials');
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

    public function createQuizWithQuestion(int $instructorId, int $classId, array $quizData, array $questionData): int
    {
        if ($this->getClassById($instructorId, $classId) === null) {
            throw new InvalidArgumentException('Kelas tidak ditemukan.');
        }
        $quiz = $this->quizFields($quizData);
        $questionText = $this->requireText($questionData, 'teks', 'Teks soal wajib diisi.');
        $options = $this->normalizeQuestionOptions($questionData);
        $answerIndex = (int) ($questionData['jawaban_benar'] ?? -1);

        $this->db->beginTransaction();
        try {
            $this->run(
                'INSERT INTO kuis_kelas (kelas_id, judul, nilai_minimum, waktu_durasi) VALUES (?, ?, ?, ?)',
                [$classId, $quiz['judul'], $quiz['nilai_minimum'], $quiz['waktu_durasi']]
            );
            $quizId = (int) $this->db->lastInsertId();
            $this->run(
                'INSERT INTO soal_kuis_kelas (kuis_id, urutan, teks) VALUES (?, ?, ?)',
                [$quizId, 1, $questionText]
            );
            $questionId = (int) $this->db->lastInsertId();
            foreach ($options as $index => $option) {
                $this->run(
                    'INSERT INTO opsi_kuis_kelas (soal_id, urutan, teks, benar) VALUES (?, ?, ?, ?)',
                    [$questionId, $index + 1, $option, $index === $answerIndex ? 1 : 0]
                );
            }
            $this->db->commit();
            return $quizId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function unlockModuleForStudent(int $userId, int $classId, int $moduleId, string $enrollKey): void
    {
        $module = $this->run(
            'SELECT m.enroll_key
             FROM modul_kelas m
             JOIN kelas k ON k.id = m.kelas_id
             JOIN kelas_siswa ks ON ks.kelas_id = k.id AND ks.user_id = ?
             WHERE m.id = ? AND m.kelas_id = ? AND k.status <> "draft"
             LIMIT 1',
            [$userId, $moduleId, $classId]
        )->fetch();
        if ($module === false) {
            throw new InvalidArgumentException('Modul tidak ditemukan atau kamu belum bergabung ke kelas ini.');
        }
        if (trim((string) ($module['enroll_key'] ?? '')) === '') {
            throw new InvalidArgumentException('Modul ini sudah bersifat publik dan tidak memerlukan kode.');
        }
        $submittedKey = strtoupper(trim($enrollKey));
        if (strlen($submittedKey) > 50 || !hash_equals((string) $module['enroll_key'], $submittedKey)) {
            throw new InvalidArgumentException('Kode akses modul tidak sesuai.');
        }

        $this->run(
            'INSERT INTO kelas_modul_siswa (modul_id, user_id) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)',
            [$moduleId, $userId]
        );
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

    public function enrollStudentInPublicClass(int $userId, int $classId): ?array
    {
        $class = $this->run(
            "SELECT id, instruktur_id, slug, judul, status
             FROM kelas
             WHERE id = ? AND status = 'aktif' AND COALESCE(enroll_key, '') = ''
             LIMIT 1",
            [$classId]
        )->fetch();
        if (!$class) {
            return null;
        }

        $this->run(
            "INSERT INTO kelas_siswa (kelas_id, user_id, status, progress)
             VALUES (?, ?, 'aktif', 0)
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)",
            [(int) $class['id'], $userId]
        );

        return $class;
    }

    public function getStudentDashboardClasses(int $userId): array
    {
        return $this->run(
            'SELECT k.id, k.slug, k.judul, k.deskripsi, k.status, k.icon_url,
                    ks.progress, ks.status AS enrollment_status, ks.daftar_pada,
                    u.nama AS instruktur_nama
             FROM kelas_siswa ks
             JOIN kelas k ON k.id = ks.kelas_id
             JOIN user u ON u.id = k.instruktur_id
             WHERE ks.user_id = ? AND k.status <> "draft"
               AND ks.selesai_pada IS NULL AND COALESCE(ks.progress, 0) < 100
             ORDER BY ks.daftar_pada DESC',
            [$userId]
        )->fetchAll();
    }

    public function getStudentClassLearningHistory(int $userId): array
    {
        return $this->run(
            "SELECT k.id AS class_id, k.slug, k.judul, k.icon_url AS cover_image,
                    'Kelas' AS tipe, ks.daftar_pada AS mulai_pada,
                    CASE WHEN ks.selesai_pada IS NOT NULL OR COALESCE(ks.progress, 0) >= 100
                         THEN COALESCE(ks.selesai_pada, ks.daftar_pada)
                         ELSE NULL END AS selesai_pada,
                    CASE WHEN ks.selesai_pada IS NOT NULL OR COALESCE(ks.progress, 0) >= 100
                         THEN 'Selesai' ELSE 'Berjalan' END AS status,
                    (SELECT q.judul
                     FROM hasil_kuis_kelas hk
                     JOIN kuis_kelas q ON q.id = hk.kuis_id
                     WHERE hk.user_id = ks.user_id AND q.kelas_id = k.id
                     ORDER BY hk.dikerjakan_pada DESC, hk.id DESC
                     LIMIT 1) AS judul_kuis,
                    (SELECT CASE WHEN hk.lulus = 1 THEN 'Lulus' ELSE 'Belum lulus' END
                     FROM hasil_kuis_kelas hk
                     JOIN kuis_kelas q ON q.id = hk.kuis_id
                     WHERE hk.user_id = ks.user_id AND q.kelas_id = k.id
                     ORDER BY hk.dikerjakan_pada DESC, hk.id DESC
                     LIMIT 1) AS status_kuis,
                    (SELECT hk.nilai
                     FROM hasil_kuis_kelas hk
                     JOIN kuis_kelas q ON q.id = hk.kuis_id
                     WHERE hk.user_id = ks.user_id AND q.kelas_id = k.id
                     ORDER BY hk.dikerjakan_pada DESC, hk.id DESC
                     LIMIT 1) AS nilai_kuis
             FROM kelas_siswa ks
             JOIN kelas k ON k.id = ks.kelas_id
             WHERE ks.user_id = ? AND k.status <> 'draft'
             ORDER BY COALESCE(ks.selesai_pada, ks.daftar_pada) DESC",
            [$userId]
        )->fetchAll();
    }

    public function getAvailableClassesForStudent(int $userId): array
    {
        return $this->run(
            "SELECT k.id, k.slug, k.judul, k.deskripsi, k.kategori, k.durasi_estimasi, k.tingkat, k.icon_url,
                    (COALESCE(k.enroll_key, '') = '') AS is_public,
                    u.nama AS instruktur_nama,
                    (SELECT COUNT(*) FROM kelas_siswa peserta
                     WHERE peserta.kelas_id = k.id AND peserta.status = 'aktif') AS total_siswa
             FROM kelas k
             JOIN user u ON u.id = k.instruktur_id
             LEFT JOIN kelas_siswa terdaftar
                ON terdaftar.kelas_id = k.id AND terdaftar.user_id = ?
             WHERE k.status = 'aktif' AND COALESCE(k.enroll_key, '') = '' AND terdaftar.id IS NULL
             ORDER BY k.diperbarui_pada DESC, k.dibuat_pada DESC",
            [$userId]
        )->fetchAll();
    }

    public function getAvailableClassOverview(int $classId): ?array
    {
        $class = $this->run(
            "SELECT k.id, k.judul, k.deskripsi, k.kategori, k.durasi_estimasi, k.tingkat,
                    k.icon_url, k.badge_label, k.badge_aktif, k.badge_color,
                    (COALESCE(k.enroll_key, '') = '') AS is_public,
                    u.nama AS instruktur_nama
             FROM kelas k
             JOIN user u ON u.id = k.instruktur_id
             WHERE k.id = ? AND k.status = 'aktif' AND COALESCE(k.enroll_key, '') = ''
             LIMIT 1",
            [$classId]
        )->fetch();
        if ($class === false) {
            return null;
        }

        $class['modules'] = $this->run(
            'SELECT m.id, m.judul, m.deskripsi,
                    (COALESCE(m.enroll_key, "") <> "") AS is_private,
                    COUNT(mt.id) AS total_materi
             FROM modul_kelas m
             LEFT JOIN materi_kelas mt ON mt.modul_id = m.id
             WHERE m.kelas_id = ?
             GROUP BY m.id, m.judul, m.deskripsi, m.enroll_key, m.urutan
             ORDER BY m.urutan ASC, m.id ASC',
            [$classId]
        )->fetchAll();
        $class['quizzes'] = $this->run(
            'SELECT q.id, q.judul, q.nilai_minimum,
                    (SELECT COUNT(*) FROM soal_kuis_kelas sq WHERE sq.kuis_id = q.id) AS total_soal
             FROM kuis_kelas q
             WHERE q.kelas_id = ?
             ORDER BY q.dibuat_pada ASC, q.id ASC',
            [$classId]
        )->fetchAll();

        return $class;
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
             JOIN kelas_siswa ks ON ks.kelas_id = mk.kelas_id AND ks.user_id = ?
             LEFT JOIN kelas_modul_siswa access
                ON access.modul_id = mk.id AND access.user_id = ?
             WHERE mt.id = ? AND mk.kelas_id = ?
               AND (COALESCE(mk.enroll_key, "") = "" OR access.modul_id IS NOT NULL)',
            [$userId, $userId, $materialId, $classId]
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
             ORDER BY hk.dikerjakan_pada DESC, hk.id DESC',
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
             WHERE pm.user_id = ? AND mk.kelas_id = ? AND pm.selesai_dibuka = 1',
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

        $latestAttempt = $this->run(
            'SELECT lulus
             FROM hasil_kuis_kelas
             WHERE user_id = ? AND kuis_id = ?
             ORDER BY dikerjakan_pada DESC, id DESC
             LIMIT 1',
            [$userId, $quizId]
        )->fetch();
        if ($latestAttempt !== false && (int) $latestAttempt['lulus'] === 1) {
            throw new InvalidArgumentException('Kuis ini sudah lulus dan tidak perlu dikerjakan ulang.');
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
             WHERE pm.user_id = ? AND mk.kelas_id = ? AND pm.selesai_dibuka = 1',
            [$userId, $classId]
        )->fetchColumn();

        $totalKuis = (int) $this->run(
            'SELECT COUNT(*) FROM kuis_kelas WHERE kelas_id = ?',
            [$classId]
        )->fetchColumn();

        $lulusKuis = (int) $this->run(
            'SELECT COUNT(DISTINCT q.id)
             FROM hasil_kuis_kelas hk
             JOIN kuis_kelas q ON q.id = hk.kuis_id
             WHERE hk.user_id = ? AND q.kelas_id = ? AND hk.lulus = 1',
            [$userId, $classId]
        )->fetchColumn();

        $denominator = max(1, $totalMateri + $totalKuis);
        $progress = (int) round((($openedMateri + $lulusKuis) / $denominator) * 100);
        $progress = max(0, min(100, $progress));

        $this->run(
            'UPDATE kelas_siswa
             SET progress = ?,
                 selesai_pada = CASE
                     WHEN ? = 100 THEN COALESCE(selesai_pada, CURRENT_TIMESTAMP)
                     ELSE NULL
                 END
             WHERE user_id = ? AND kelas_id = ?',
            [$progress, $progress, $userId, $classId]
        );

        if ($progress === 100) {
            $classBadge = $this->run(
                'SELECT badge_label, badge_color
                 FROM kelas
                 WHERE id = ? AND badge_aktif = 1 AND badge_label IS NOT NULL AND badge_label <> ""
                 LIMIT 1',
                [$classId]
            )->fetch();
            if ($classBadge !== false) {
                $this->run(
                    'INSERT INTO kelas_lencana_siswa (class_id, student_id, badge_label, badge_color)
                     VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE student_id = VALUES(student_id)',
                    [$classId, $userId, $classBadge['badge_label'], $classBadge['badge_color'] ?: 'purple']
                );
            }
        }
    }

    public function getStudentClassBadges(int $userId): array
    {
        return $this->run(
            "SELECT 'Lencana' AS jenis, award.badge_label AS judul, k.judul AS kursus_judul,
                    award.earned_at AS tanggal, award.id AS item_id, 'bi-award' AS ikon,
                    NULL AS sertifikat_id, NULL AS nomor_sertifikat,
                    CONCAT('Menyelesaikan kelas ', k.judul, '.') AS deskripsi,
                    NULL AS certificate_url, award.badge_color
             FROM kelas_lencana_siswa award
             JOIN kelas k ON k.id = award.class_id
             WHERE award.student_id = ?
             ORDER BY award.earned_at DESC, award.id DESC",
            [$userId]
        )->fetchAll();
    }

    public function getStudentClassBadge(int $userId, int $classId): ?array
    {
        $badge = $this->run(
            'SELECT badge_label, badge_color, earned_at
             FROM kelas_lencana_siswa
             WHERE student_id = ? AND class_id = ?
             LIMIT 1',
            [$userId, $classId]
        )->fetch();

        return $badge ?: null;
    }

    public function awardClassBadgesForCompletedStudents(int $classId): void
    {
        $badge = $this->run(
            'SELECT badge_label, badge_color
             FROM kelas
             WHERE id = ? AND badge_aktif = 1 AND badge_label IS NOT NULL AND badge_label <> ""
             LIMIT 1',
            [$classId]
        )->fetch();
        if ($badge === false) {
            return;
        }

        $students = $this->run(
            'SELECT user_id FROM kelas_siswa
             WHERE kelas_id = ? AND progress >= 100',
            [$classId]
        )->fetchAll(PDO::FETCH_COLUMN);
        foreach ($students as $studentId) {
            $this->run(
                'INSERT INTO kelas_lencana_siswa (class_id, student_id, badge_label, badge_color)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE student_id = VALUES(student_id)',
                [$classId, (int) $studentId, $badge['badge_label'], $badge['badge_color'] ?: 'purple']
            );
        }
    }

    public function getStudentClassCertificates(int $userId): array
    {
        return $this->run(
            "SELECT 'Sertifikat' AS jenis, k.judul, k.judul AS kursus_judul,
                    uc.issued_at AS tanggal, uc.id AS item_id, 'bi-award' AS ikon,
                    uc.id AS sertifikat_id, uc.certificate_no AS nomor_sertifikat,
                    NULL AS deskripsi,
                    CONCAT('ClassCertificateController.php?id=', uc.id) AS certificate_url
             FROM user_certificates uc
             JOIN kelas k ON k.id = uc.class_id
             WHERE uc.student_id = ? AND uc.status = 'issued'
             ORDER BY uc.issued_at DESC, uc.id DESC",
            [$userId]
        )->fetchAll();
    }

    public function getStudentClassCertificate(int $userId, int $classId): ?array
    {
        $certificate = $this->run(
            "SELECT id, certificate_no, issued_at,
                    CONCAT('ClassCertificateController.php?id=', id) AS certificate_url
             FROM user_certificates
             WHERE student_id = ? AND class_id = ? AND status = 'issued'
             ORDER BY issued_at DESC, id DESC
             LIMIT 1",
            [$userId, $classId]
        )->fetch();

        return $certificate ?: null;
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

    private function moduleEnrollKeyTaken(int $classId, string $key, int $excludeModuleId = 0): bool
    {
        return (bool) $this->run(
            'SELECT 1
             FROM modul_kelas
             WHERE kelas_id = ? AND UPPER(enroll_key) = ? AND id <> ?
             LIMIT 1',
            [$classId, $key, $excludeModuleId]
        )->fetchColumn();
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