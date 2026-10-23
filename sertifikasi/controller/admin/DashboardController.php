<?php
require_once __DIR__ . '/../../config/session.php';

require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../class/AdminUser.php';

if (empty($_SESSION['logged_in']) || empty($_SESSION['id_user'])) {
    header('Location: ../../view/login.php');
    exit();
}

$adminUsers = new AdminUser();
$actorId = (int) $_SESSION['id_user'];
if (!$adminUsers->isActiveAdmin($actorId)) {
    session_destroy();
    header('Location: ../../view/login.php');
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $_SESSION['admin_error'] = 'Sesi form tidak valid. Muat ulang halaman dan coba lagi.';
        header('Location: DashboardController.php');
        exit();
    }

    $postString = static function (string $key): string {
        return isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : '';
    };
    $action = $postString('aksi');
    $userIdValue = $_POST['user_id'] ?? null;
    $userId = is_string($userIdValue) || is_int($userIdValue)
        ? filter_var($userIdValue, FILTER_VALIDATE_INT)
        : false;

    try {
        switch ($action) {
            case 'tambah':
                $nama = trim($postString('nama'));
                $email = strtolower(trim($postString('email')));
                $password = $postString('password');
                $role = $postString('role');
                if ($nama === '' || mb_strlen($nama) > 100) {
                    throw new DomainException('Nama wajib diisi dan maksimal 100 karakter.');
                }
                if (mb_strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new DomainException('Email tidak valid (maksimal 100 karakter).');
                }
                if (strlen($password) < 8 || strlen($password) > 200) {
                    throw new DomainException('Password awal harus 8 sampai 200 karakter.');
                }
                $adminUsers->createUser($actorId, $nama, $email, $password, $role);
                $_SESSION['admin_success'] = 'Akun berhasil ditambahkan.';
                break;

            case 'role':
                if (!$userId) {
                    throw new DomainException('Akun tidak valid.');
                }
                $_SESSION['admin_success'] = $adminUsers->changeRole($actorId, $userId, $postString('role'));
                break;

            case 'status':
                if (!$userId) {
                    throw new DomainException('Akun tidak valid.');
                }
                $statusValue = $_POST['status'] ?? null;
                $status = is_string($statusValue) || is_int($statusValue)
                    ? filter_var($statusValue, FILTER_VALIDATE_INT)
                    : false;
                if ($status === false) {
                    throw new DomainException('Status akun tidak valid.');
                }
                $_SESSION['admin_success'] = $adminUsers->setStatus($actorId, $userId, $status);
                break;

            case 'hapus':
                if (!$userId) {
                    throw new DomainException('Akun tidak valid.');
                }
                $_SESSION['admin_success'] = $adminUsers->deleteUser($actorId, $userId);
                break;

            case 'reset_password':
                if (!$userId) {
                    throw new DomainException('Akun tidak valid.');
                }
                $mode = $postString('mode');
                if ($mode === '') {
                    $mode = 'manual';
                }
                if ($mode === 'generate') {
                    $password = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
                    $_SESSION['generated_password_once'] = $password;
                } else {
                    $password = $postString('password_baru');
                    if (strlen($password) < 8 || strlen($password) > 200) {
                        throw new DomainException('Password baru harus 8 sampai 200 karakter.');
                    }
                    unset($_SESSION['generated_password_user_id'], $_SESSION['generated_password_once']);
                }
                $adminUsers->resetPassword($actorId, $userId, $password);
                $_SESSION['admin_success'] = $mode === 'generate'
                    ? 'Password acak berhasil dibuat. Salin sekarang; password ini hanya ditampilkan satu kali.'
                    : 'Password akun berhasil direset.';
                break;

            default:
                throw new DomainException('Aksi tidak dikenal.');
        }
    } catch (DomainException $e) {
        unset($_SESSION['generated_password_user_id'], $_SESSION['generated_password_once']);
        $_SESSION['admin_error'] = $e->getMessage();
    } catch (PDOException $e) {
        unset($_SESSION['generated_password_user_id'], $_SESSION['generated_password_once']);
        error_log('Admin account action error: ' . $e->getMessage());
        $_SESSION['admin_error'] = $e->getCode() === '23000'
            ? 'Email sudah terdaftar atau akun masih memiliki data terkait.'
            : 'Perubahan akun gagal disimpan ke database. Silakan coba lagi.';
    } catch (Throwable $e) {
        unset($_SESSION['generated_password_user_id'], $_SESSION['generated_password_once']);
        error_log('Admin account action error: ' . $e->getMessage());
        $_SESSION['admin_error'] = 'Terjadi kesalahan saat memproses akun. Silakan coba lagi.';
    }

    $query = http_build_query(array_filter([
        'q' => $_POST['q'] ?? '',
        'role' => $_POST['filter_role'] ?? '',
        'status' => $_POST['filter_status'] ?? '',
    ], static function ($value) {
        return is_string($value) && $value !== '';
    }));
    header('Location: DashboardController.php' . ($query === '' ? '' : '?' . $query));
    exit();
}

$search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$roleFilter = isset($_GET['role']) && is_string($_GET['role']) ? $_GET['role'] : '';
$statusFilter = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : '';

try {
    $stats = $adminUsers->getStats();
    $users = $adminUsers->getUsers($search, $roleFilter, $statusFilter);
} catch (PDOException $e) {
    error_log('Admin dashboard load error: ' . $e->getMessage());
    http_response_code(500);
    exit('Dashboard admin tidak dapat dimuat dari database. Silakan coba lagi.');
}

$success = $_SESSION['admin_success'] ?? '';
$error = $_SESSION['admin_error'] ?? '';
$generatedPassword = $_SESSION['generated_password_once'] ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error'], $_SESSION['generated_password_once']);
if ($generatedPassword !== '') {
    header('Cache-Control: no-store, private');
    header('Pragma: no-cache');
}

$data = [
    'username' => $_SESSION['username'] ?? '',
    'stats' => $stats,
    'users' => $users,
    'search' => $search,
    'roleFilter' => $roleFilter,
    'statusFilter' => $statusFilter,
    'csrf' => $_SESSION['csrf_token'],
    'success' => $success,
    'error' => $error,
    'generatedPassword' => $generatedPassword,
    'actorId' => $actorId,
];

define('ADMIN_DASHBOARD_AUTHORIZED', true);
include __DIR__ . '/../../view/admin/dashboard.php';
