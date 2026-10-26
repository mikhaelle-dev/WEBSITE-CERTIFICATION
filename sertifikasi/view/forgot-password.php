<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../class/PasswordResetRequest.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $email = strtolower(trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : ''));

    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Sesi form tidak valid. Muat ulang halaman dan coba lagi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
        $error = 'Masukkan alamat email yang valid.';
    } else {
        try {
            (new PasswordResetRequest())->submit($email);
            $success = 'Jika email cocok dengan akun, permintaan reset sudah diteruskan ke admin. Temui admin secara langsung untuk verifikasi.';
            $email = '';
        } catch (PDOException $exception) {
            error_log('Password reset request database error: ' . $exception->getMessage());
            http_response_code(503);
            $error = 'Permintaan belum dapat disimpan. Pastikan database sudah menjalankan migrasi reset password.';
        } catch (Throwable $exception) {
            error_log('Password reset request error: ' . $exception->getMessage());
            http_response_code(500);
            $error = 'Permintaan reset gagal disimpan. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa password | Sertifikasi Academy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../asset/css/auth.css" rel="stylesheet">
    <link href="../asset/css/theme-soft.css" rel="stylesheet">
</head>
<body class="soft-app auth-page">
    <main class="auth-wrap forgot-password-page">
        <a class="auth-brand forgot-password-brand" href="login.php" aria-label="Sertifikasi Academy, kembali ke login">
            <span>Sertifikasi</span><strong>Academy</strong>
        </a>
        <section class="auth-panel w-100">
            <div class="auth-box">
                <h1 class="h2">Lupa password?</h1>
                <p class="text-muted">Masukkan email akun. Setelah mengirim permintaan, temui admin secara langsung untuk verifikasi dan reset password.</p>
                <?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
                <?php if ($success !== ''): ?><div class="alert alert-success" role="status"><?= e($success) ?></div><?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <label class="form-label" for="email">Email akun</label>
                    <input class="form-control mb-3" type="email" id="email" name="email" maxlength="100" autocomplete="email" value="<?= e($email) ?>" required>
                    <button class="btn btn-primary w-100" type="submit">Kirim permintaan reset</button>
                </form>
                <a class="d-inline-flex align-items-center gap-1 mt-3" href="login.php"><i class="bi bi-arrow-left"></i>Kembali ke login</a>
            </div>
        </section>
    </main>
</body>
</html>
