<?php
/**
 * Koneksi PDO. Semua class model (Auth, Course, dst) extends class ini,
 * jadi cukup `new Auth()` dan koneksi otomatis terbuat.
 */
class Database
{
    public const ACCOUNT_TRASH_RETENTION_MINUTES = 5;

    protected $db;

    public function __construct()
    {
        $configPath = dirname(__DIR__) . '/config/database.local.php';
        $config = is_file($configPath) ? require $configPath : [];
        if (!is_array($config)) {
            throw new RuntimeException('Konfigurasi database harus mengembalikan array.');
        }

        $host = $config['host'] ?? 'localhost';
        $port = (int) ($config['port'] ?? 3306);
        $dbname = $config['dbname'] ?? 'db_sertifikasi';
        $username = $config['username'] ?? 'root';
        $password = $config['password'] ?? '';
        if (!is_string($host) || $host === '' || $port < 1
            || !is_string($dbname) || $dbname === ''
            || !is_string($username) || !is_string($password)) {
            throw new RuntimeException('Konfigurasi koneksi database tidak valid.');
        }

        $this->db = new PDO(
            "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );

        $this->purgeQueuedAccountPhotos();
    }

    private function purgeQueuedAccountPhotos(): void
    {
        try {
            $stmt = $this->db->query(
                'SELECT path
                 FROM account_file_purge_queue
                 ORDER BY queued_at ASC
                 LIMIT 50'
            );
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) !== 1146) {
                throw $e;
            }

            error_log('Account photo cleanup is unavailable: run database/12_account_trash_scheduler.sql to create its queue table.');
            return;
        }

        $delete = $this->db->prepare('DELETE FROM account_file_purge_queue WHERE path = ?');
        $profileDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'profiles';

        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $relativePath) {
            if (
                !is_string($relativePath)
                || !preg_match('~\Auploads/profiles/([a-f0-9]{32}\.(?:jpg|png|webp))\z~D', $relativePath, $matches)
            ) {
                error_log('Account photo cleanup skipped an invalid queued path.');
                $delete->execute([$relativePath]);
                continue;
            }

            $photoPath = $profileDirectory . DIRECTORY_SEPARATOR . $matches[1];
            if ((is_file($photoPath) || is_link($photoPath)) && !unlink($photoPath)) {
                error_log('Account photo cleanup failed for ' . $photoPath);
                continue;
            }

            $delete->execute([$relativePath]);
        }
    }
}
