<?php
/**
 * Koneksi PDO. Semua class model (Auth, Course, dst) extends class ini,
 * jadi cukup `new Auth()` dan koneksi otomatis terbuat.
 */
class Database
{
    private $host     = 'localhost';
    private $dbname   = 'db_sertifikasi';
    private $username = 'root';
    private $password = '';
    protected $db;

    public function __construct()
    {
        $this->db = new PDO(
            "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
            $this->username,
            $this->password,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
}
