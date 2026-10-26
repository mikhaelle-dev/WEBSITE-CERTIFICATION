<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';

// Sudah login? langsung ke dashboard sesuai role.
if (!empty($_SESSION['logged_in'])) {
    if ($_SESSION['role'] === 'siswa') {
        header('Location: ../controller/user/DashboardController.php');
    } elseif ($_SESSION['role'] === 'admin') {
        header('Location: ../controller/admin/DashboardController.php');
    } else {
        header('Location: placeholder.php');
    }
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
    <link href="../asset/css/brutal.css" rel="stylesheet">
</head>
<body class="auth-page brutal-app">
<div class="auth-wrap auth-brutal">

    <section class="auth-hero" aria-labelledby="hero-title">
        <div class="auth-brand"><span>Sertifikasi</span><strong>Academy</strong></div>
        <h1 id="hero-title">Bangun skill<br>kamu di sini.</h1>
        <p>Belajar mandiri, kerjakan asesmen, dan dapatkan sertifikat otomatis kalau kamu lolos.</p>
        <div class="auth-process" aria-labelledby="process-title">
            <h2 id="process-title">Dari materi sampai sertifikat</h2>
            <ol class="auth-steps">
                <li>
                    <span class="auth-step-number">01</span>
                    <strong>Materi</strong>
                    <span>Belajar mandiri</span>
                </li>
                <li>
                    <span class="auth-step-number">02</span>
                    <strong>Asesmen</strong>
                    <span>Uji pemahaman</span>
                </li>
                <li>
                    <span class="auth-step-number">03</span>
                    <strong>Sertifikat</strong>
                    <span>Setelah lulus</span>
                </li>
            </ol>
        </div>
    </section>

    <section class="auth-panel" aria-labelledby="login-title">
        <div class="auth-box brutal-card">
            <h2 id="login-title">Masuk ke akunmu</h2>
            <p class="text-muted">Silakan login ke akun kamu.</p>

            <?php if ($success !== ''): ?><div class="alert alert-success brutal-alert brutal-alert--success py-2" role="status"><?= e($success) ?></div><?php endif; ?>
            <?php if ($error !== ''): ?><div class="alert alert-danger brutal-alert brutal-alert--danger py-2" role="alert"><?= e($error) ?></div><?php endif; ?>

            <form method="POST" action="../controller/AuthController.php?aksi=login" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                <label class="form-label" for="email">Email</label>
                <input class="form-control brutal-input mb-3" type="email" id="email" name="email" autocomplete="email" required autofocus value="<?= e($oldEmail) ?>">

                <label class="form-label" for="password">Password</label>
                <div class="pw-wrap mb-2">
                    <input class="form-control brutal-input" type="password" id="password" name="password" autocomplete="current-password" required>
                    <button type="button" class="pw-toggle" aria-label="Tampilkan password" aria-controls="password" aria-pressed="false" onclick="
                        const i=document.getElementById('password');
                        i.type = i.type==='password' ? 'text' : 'password';
                        const visible = i.type==='text';
                        this.setAttribute('aria-pressed', String(visible));
                        this.setAttribute('aria-label', visible ? 'Sembunyikan password' : 'Tampilkan password');
                        this.firstElementChild.className = visible ? 'bi bi-eye-slash' : 'bi bi-eye';">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>

                <button class="btn brutal-btn brutal-btn--primary btn-login w-100 mt-3" type="submit">Login</button>
            </form>

            <div class="auth-alt">
                Belum punya akun? <a href="register.php">Daftar sekarang</a>
            </div>
        </div>
    </section>

</div>
</body>
</html>
