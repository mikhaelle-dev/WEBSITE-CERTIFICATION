<?php
declare(strict_types=1);
/**
 * Handles login, register, logout.
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php'; // includes CSP header
require_once __DIR__ . '/../class/Auth.php';
require_once __DIR__ . '/../class/RateLimiter.php';

$aksi = $_GET['aksi'] ?? '';

// ---------- logout ----------
if ($aksi === 'logout') {
    // Clear session data and cookie
    $_SESSION = [];
    session_unset();
    if (ini_get('session.use_cookies')) {
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $cookie['path'],
            'domain' => $cookie['domain'],
            'secure' => $cookie['secure'],
            'httponly' => $cookie['httponly'],
            'samesite' => $cookie['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
    header('Location: ../view/login.php');
    exit();
}

// Only accept POST for login/register
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../view/login.php');
    exit();
}

// ---------- CSRF check ----------
$token = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
    $_SESSION['flash_error'] = 'Sesi form tidak valid, silakan coba lagi.';
    header('Location: ../view/login.php');
    exit();
}

// ---------- login ----------
if ($aksi === 'login') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $_SESSION['flash_error'] = 'Email dan password wajib diisi.';
        $_SESSION['old_email']   = $email;
        header('Location: ../view/login.php');
        exit();
    }

    $rateLimitKey = $ip . "\0" . $email;
    $remainingLockout = RateLimiter::remainingLockout($rateLimitKey);
    if ($remainingLockout > 0) {
        $_SESSION['flash_error'] = "Terlalu banyak percobaan login. Coba lagi dalam {$remainingLockout} detik.";
        header('Location: ../view/login.php');
        exit();
    }

    try {
        $hasil = (new Auth())->login($email, $password);
    } catch (PDOException $e) {
        error_log('Login error: ' . $e->getMessage());
        $_SESSION['flash_error'] = 'Database belum siap. Pastikan MySQL aktif, db_sertifikasi tersedia, dan semua migrasi sudah diimpor.';
        header('Location: ../view/login.php');
        exit();
    }

    if (!$hasil['ok']) {
        RateLimiter::recordFailure($rateLimitKey);
        $_SESSION['flash_error'] = $hasil['error'];
        $_SESSION['old_email']   = $email;
        header('Location: ../view/login.php');
        exit();
    }

    RateLimiter::clear($rateLimitKey);
    session_regenerate_id(true);
    $u = $hasil['user'];
    $_SESSION['logged_in']          = true;
    $_SESSION['id_user']            = (int) $u['id'];
    $_SESSION['username']           = $u['nama'];
    $_SESSION['email']               = $u['email'];
    $_SESSION['role']               = $u['role'];
    $_SESSION['foto_profil']         = $u['foto_profil'] ?? null;
    $_SESSION['account_restricted'] = $hasil['restricted'];

    // Redirect based on role / restriction
    if ($hasil['restricted']) {
        header('Location: AccountDisabledController.php');
    } elseif ($u['role'] === 'siswa') {
        header('Location: user/DashboardController.php');
    } elseif ($u['role'] === 'admin') {
        header('Location: admin/DashboardController.php');
    } else {
        header('Location: ../view/placeholder.php');
    }
    exit();
}

// ---------- register ----------
if ($aksi === 'register') {
    $nama       = trim((string) ($_POST['nama'] ?? ''));
    $email      = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password   = (string) ($_POST['password'] ?? '');
    $konfirmasi = (string) ($_POST['konfirmasi'] ?? '');

    $errors = [];
    if ($nama === '' || mb_strlen($nama) > 100) $errors[] = 'Nama wajib diisi (maksimal 100 karakter).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (strlen($password) < 8) $errors[] = 'Password minimal 8 karakter.';
    if ($password !== $konfirmasi) $errors[] = 'Konfirmasi password tidak sama.';

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

// Fallback
header('Location: ../view/login.php');
exit();
