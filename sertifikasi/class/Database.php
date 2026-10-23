<?php
/**
 * Koneksi PDO. Semua class model (Auth, Course, dst) extends class ini,
 * jadi cukup `new Auth()` dan koneksi otomatis terbuat.
 */
class Database
{
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
    }

}
