<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error    = $_SESSION['flash_error'] ?? '';
$oldNama  = $_SESSION['old_nama']    ?? '';
$oldEmail = $_SESSION['old_email']   ?? '';
unset($_SESSION['flash_error'], $_SESSION['old_nama'], $_SESSION['old_email']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up | Sertifikasi Academy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../asset/css/auth.css" rel="stylesheet">
    <link href="../asset/css/theme-soft.css" rel="stylesheet">
</head>
<body class="soft-app auth-page">
<div class="auth-wrap">

    <section class="auth-hero">
        <div class="auth-brand"><span>Sertifikasi</span><strong>Academy</strong></div>
        <h1>Mulai belajar,<br>gratis.</h1>
        <p>Buat akun siswa dan langsung akses materi serta asesmen.</p>
        <i class="bi bi-mortarboard-fill auth-hero-icon"></i>
    </section>

    <section class="auth-panel">
        <div class="auth-box">
            <h2>Buat akun</h2>
            <p class="text-muted">Isi data di bawah untuk mendaftar sebagai siswa.</p>

            <?php if ($error !== ''): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>

            <form method="POST" action="../controller/AuthController.php?aksi=register" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                <label class="form-label" for="nama">Nama lengkap *</label>
                <input class="form-control mb-3" id="nama" name="nama" maxlength="100" required autofocus value="<?= e($oldNama) ?>">

                <label class="form-label" for="email">Email *</label>
                <input class="form-control mb-3" type="email" id="email" name="email" required value="<?= e($oldEmail) ?>">

                <label class="form-label" for="password">Password * <small class="text-muted">(min. 8 karakter)</small></label>
                <input class="form-control mb-3" type="password" id="password" name="password" minlength="8" required>

                <label class="form-label" for="konfirmasi">Ulangi password *</label>
                <input class="form-control mb-3" type="password" id="konfirmasi" name="konfirmasi" minlength="8" required>

                <!-- Sengaja TIDAK ada pilihan role: akun baru selalu siswa -->
                <button class="btn btn-login w-100 mt-2" type="submit">Daftar</button>
            </form>

            <div class="auth-alt">
                Sudah punya akun? <a href="login.php">Login</a>
            </div>
        </div>
    </section>

</div>
</body>
</html>
