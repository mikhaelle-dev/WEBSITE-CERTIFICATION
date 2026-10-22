<?php
session_start();
require_once __DIR__ . '/../config/helpers.php';

// Sudah login? langsung ke dashboard sesuai role.
if (!empty($_SESSION['logged_in'])) {
    header('Location: ' . ($_SESSION['role'] === 'siswa' ? '../controller/user/DashboardController.php' : 'placeholder.php'));
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Pesan flash: tampil sekali lalu dihapus
$error    = $_SESSION['flash_error']   ?? '';
$success  = $_SESSION['flash_success'] ?? '';
$oldEmail = $_SESSION['old_email']     ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success'], $_SESSION['old_email']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sertifikasi Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../asset/css/auth.css" rel="stylesheet">
</head>
<body>
<div class="auth-wrap">

    <section class="auth-hero">
        <div class="auth-brand"><span>Sertifikasi</span><strong>Academy</strong></div>
        <h1>Bangun skill<br>kamu di sini.</h1>
        <p>Belajar mandiri, kerjakan asesmen, dan dapatkan sertifikat otomatis kalau kamu lolos.</p>
        <i class="bi bi-mortarboard-fill auth-hero-icon"></i>
    </section>

    <section class="auth-panel">
        <div class="auth-box">
            <h2>Welcome!</h2>
            <p class="text-muted">Silakan login ke akun kamu.</p>

            <?php if ($success !== ''): ?><div class="alert alert-success py-2"><?= e($success) ?></div><?php endif; ?>
            <?php if ($error !== ''): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>

            <form method="POST" action="../controller/AuthController.php?aksi=login" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                <label class="form-label" for="email">Email *</label>
                <input class="form-control mb-3" type="email" id="email" name="email" required autofocus value="<?= e($oldEmail) ?>">

                <label class="form-label" for="password">Password *</label>
                <div class="pw-wrap mb-2">
                    <input class="form-control" type="password" id="password" name="password" required>
                    <button type="button" class="pw-toggle" aria-label="Tampilkan password" onclick="
                        const i=document.getElementById('password');
                        i.type = i.type==='password' ? 'text' : 'password';
                        this.firstElementChild.className = i.type==='password' ? 'bi bi-eye' : 'bi bi-eye-slash';">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>

                <button class="btn btn-login w-100 mt-3" type="submit">Login</button>
            </form>

            <div class="auth-alt">
                Belum punya akun? <a href="register.php">Sign up</a>
            </div>
        </div>
    </section>

</div>
</body>
</html>
