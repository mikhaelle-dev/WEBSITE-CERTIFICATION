<?php
declare(strict_types=1);

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/class/Auth.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';
$setupComplete = false;

try {
    $auth = new Auth();
    $setupComplete = $auth->hasAdminAccount();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$setupComplete) {
        $token = $_POST['csrf_token'] ?? '';
        $nama = trim(is_string($_POST['nama'] ?? null) ? $_POST['nama'] : '');
        $email = strtolower(trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : ''));
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $confirmation = is_string($_POST['konfirmasi'] ?? null) ? $_POST['konfirmasi'] : '';

        if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
            $error = 'Sesi form tidak valid. Muat ulang halaman dan coba lagi.';
        } elseif ($nama === '' || mb_strlen($nama) > 100) {
            $error = 'Nama wajib diisi dan maksimal 100 karakter.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
            $error = 'Format email tidak valid.';
        } elseif (strlen($password) < 8 || strlen($password) > 200) {
            $error = 'Password harus 8 sampai 200 karakter.';
        } elseif ($password !== $confirmation) {
            $error = 'Konfirmasi password tidak sama.';
        } else {
            $auth->createInitialAdmin($nama, $email, $password);
            $_SESSION['flash_success'] = 'Admin pertama berhasil dibuat. Silakan login.';
            header('Location: view/login.php');
            exit();
        }

        $setupComplete = $auth->hasAdminAccount();
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $error = 'Setup admin sudah selesai. Silakan login.';
    }
} catch (DomainException $exception) {
    $setupComplete = true;
    $error = $exception->getMessage();
} catch (PDOException $exception) {
    error_log('Initial admin setup database error: ' . $exception->getMessage());
    if ($exception->getCode() === '23000') {
        $error = 'Email tersebut sudah digunakan oleh akun lain.';
    } else {
        http_response_code(503);
        $error = 'Database belum siap. Pastikan database db_sertifikasi dan tabel user sudah dibuat.';
    }
} catch (Throwable $exception) {
    error_log('Initial admin setup error: ' . $exception->getMessage());
    http_response_code(500);
    $error = 'Setup admin gagal. Periksa konfigurasi database dan coba lagi.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup admin | Sertifikasi Academy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="asset/css/auth.css" rel="stylesheet">
    <link href="asset/css/theme-soft.css" rel="stylesheet">
</head>
<body class="soft-app auth-page">
    <main class="auth-wrap auth-setup">
        <section class="auth-panel w-100">
            <div class="auth-box">
                <h1 class="h2">Setup admin pertama</h1>
                <?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
                <?php if ($setupComplete): ?>
                    <p>Admin sudah tersedia. Halaman setup tidak dapat digunakan lagi.</p>
                    <a class="btn btn-primary w-100" href="view/login.php">Ke halaman login</a>
                <?php else: ?>
                    <p class="text-muted">Buat akun admin pertama. Halaman ini otomatis terkunci setelah admin berhasil dibuat.</p>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                        <label class="form-label" for="nama">Nama</label>
                        <input class="form-control mb-3" id="nama" name="nama" maxlength="100" required>
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control mb-3" type="email" id="email" name="email" maxlength="100" autocomplete="email" required>
                        <label class="form-label" for="password">Password</label>
                        <input class="form-control mb-3" type="password" id="password" name="password" minlength="8" maxlength="200" autocomplete="new-password" required>
                        <label class="form-label" for="konfirmasi">Konfirmasi password</label>
                        <input class="form-control mb-3" type="password" id="konfirmasi" name="konfirmasi" minlength="8" maxlength="200" autocomplete="new-password" required>
                        <button class="btn btn-primary w-100" type="submit">Buat admin pertama</button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
