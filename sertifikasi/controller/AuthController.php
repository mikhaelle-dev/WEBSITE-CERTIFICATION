<?php
/**
 * Menangani: login, register, logout.
 * View (login.php / register.php) hanya menampilkan form + pesan flash.
 */
session_start();

require_once __DIR__ . '/../class/Auth.php';

$aksi = $_GET['aksi'] ?? '';

// ---------- logout ----------
if ($aksi === 'logout') {
    session_destroy();
    header('Location: ../view/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../view/login.php');
    exit();
}

// ---------- cek CSRF (semua aksi POST) ----------
$token = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
    $_SESSION['flash_error'] = 'Sesi form tidak valid, silakan coba lagi.';
    header('Location: ../view/login.php');
    exit();
}

// ---------- login ----------
if ($aksi === 'login') {
    $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $_SESSION['flash_error'] = 'Email dan password wajib diisi.';
        $_SESSION['old_email']   = $email;
        header('Location: ../view/login.php');
        exit();
    }

    try {
        $hasil = (new Auth())->login($email, $password);
    } catch (PDOException $e) {
        error_log('Login error: ' . $e->getMessage());
        $_SESSION['flash_error'] = 'Tidak bisa terhubung ke database. Pastikan MySQL jalan dan db_sertifikasi sudah di-import.';
        header('Location: ../view/login.php');
        exit();
    }

    if (!$hasil['ok']) {
        $_SESSION['flash_error'] = $hasil['error'];
        $_SESSION['old_email']   = $email;
        header('Location: ../view/login.php');
        exit();
    }

    session_regenerate_id(true);   // cegah session fixation
    $u = $hasil['user'];
    $_SESSION['logged_in'] = true;
    $_SESSION['id_user']   = (int) $u['id'];
    $_SESSION['username']  = $u['nama'];     // nama tampilan di navbar
    $_SESSION['email']     = $u['email'];
    $_SESSION['role']      = $u['role'];
    $_SESSION['foto_profil'] = $u['foto_profil'] ?? null;

    // Arahkan sesuai role (dibaca dari database, bukan pilihan user)
    if ($u['role'] === 'siswa') {
        header('Location: user/DashboardController.php');
    } else {
        header('Location: ../view/placeholder.php');   // dashboard admin/instruktur belum dibuat
    }
    exit();
}

// ---------- register ----------
if ($aksi === 'register') {
    $nama      = trim((string) ($_POST['nama'] ?? ''));
    $email     = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password  = (string) ($_POST['password'] ?? '');
    $konfirmasi = (string) ($_POST['konfirmasi'] ?? '');

    $errors = [];
    if ($nama === '' || mb_strlen($nama) > 100)         $errors[] = 'Nama wajib diisi (maksimal 100 karakter).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $errors[] = 'Format email tidak valid.';
    if (strlen($password) < 8)                          $errors[] = 'Password minimal 8 karakter.';
    if ($password !== $konfirmasi)                      $errors[] = 'Konfirmasi password tidak sama.';

    if ($errors) {
        $_SESSION['flash_error'] = implode(' ', $errors);
        $_SESSION['old_nama']    = $nama;
        $_SESSION['old_email']   = $email;
        header('Location: ../view/register.php');
        exit();
    }

    try {
        $hasil = (new Auth())->register($nama, $email, $password);
    } catch (PDOException $e) {
        error_log('Register error: ' . $e->getMessage());
        $_SESSION['flash_error'] = 'Tidak bisa terhubung ke database.';
        header('Location: ../view/register.php');
        exit();
    }

    if (!$hasil['ok']) {
        $_SESSION['flash_error'] = $hasil['error'];
        $_SESSION['old_nama']    = $nama;
        $_SESSION['old_email']   = $email;
        header('Location: ../view/register.php');
        exit();
    }

    $_SESSION['flash_success'] = 'Akun berhasil dibuat. Silakan login.';
    header('Location: ../view/login.php');
    exit();
}

header('Location: ../view/login.php');
exit();
