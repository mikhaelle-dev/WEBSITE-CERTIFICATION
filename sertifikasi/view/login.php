<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';

// Sudah login? langsung ke dashboard sesuai role.
if (!empty($_SESSION['logged_in'])) {
    if (!empty($_SESSION['account_restricted'])) {
        header('Location: ../controller/AccountDisabledController.php');
    } elseif ($_SESSION['role'] === 'siswa') {
        header('Location: ../controller/user/DashboardController.php');
    } elseif ($_SESSION['role'] === 'admin') {
        header('Location: ../controller/admin/DashboardController.php');
    } else {
        header('Location: ../controller/instructor/DashboardController.php');
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../asset/css/auth.css?v=6" rel="stylesheet">
    <link href="../asset/css/theme-soft.css" rel="stylesheet">
</head>
<body class="soft-app auth-page auth-login-page">
    <div class="login-bg-circle login-bg-circle-one" aria-hidden="true"></div>
    <div class="login-bg-circle login-bg-circle-two" aria-hidden="true"></div>
    <div class="login-bg-circle login-bg-circle-three" aria-hidden="true"></div>

    <main class="login-layout">
        <header class="login-navbar">
            <div class="login-register-top">
                Belum punya akun?
                <a href="register.php">Daftar sekarang <span aria-hidden="true">↗</span></a>
            </div>
        </header>

        <div class="login-main">
            <section class="login-hero" aria-labelledby="login-hero-title">
                <div class="login-brand">
                    <svg class="login-brand-logo" width="58" height="58" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <rect x="5" y="5" width="43" height="48" rx="5" stroke="currentColor" stroke-width="3.5"/>
                        <path d="M14 17H39M14 26H39M14 35H30" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/>
                        <circle cx="44" cy="43" r="10" fill="#F4D982" stroke="currentColor" stroke-width="3"/>
                        <path d="M38 51L35 62L44 57L51 62L50 51" fill="#77B991" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/>
                    </svg>
                    <div class="login-brand-name">
                        <span>Sertifikasi</span>
                        <strong>Academy</strong>
                    </div>
                </div>

                <div class="login-tagline">✦ YOUR NEXT SKILL STARTS HERE</div>

                <h1 id="login-hero-title">Bangun skill<br>masa depanmu<br><span>di sini.</span></h1>

                <p class="login-hero-description">
                    Belajar mandiri, kerjakan asesmen, dan dapatkan sertifikat otomatis kalau kamu lolos.
                    Mulai perjalananmu dari sini!
                </p>

                <ol class="login-learning-steps" aria-label="Tahapan belajar">
                    <li>
                        <span class="login-step-number">01</span>
                        <strong>Materi</strong>
                        <span>Belajar mandiri</span>
                    </li>
                    <li>
                        <span class="login-step-number">02</span>
                        <strong>Asesmen</strong>
                        <span>Uji pemahaman</span>
                    </li>
                    <li>
                        <span class="login-step-number">03</span>
                        <strong>Sertifikat</strong>
                        <span>Setelah lulus</span>
                    </li>
                </ol>
            </section>

            <section class="login-panel" aria-labelledby="login-title">
                <div class="login-card">
                    <p class="login-welcome">Selamat datang kembali 👋</p>
                    <h2 id="login-title">Masuk ke akunmu<span>.</span></h2>
                    <p class="login-description">Lanjutkan perjalanan belajarmu dan raih sertifikasi impian.</p>

                    <?php if ($success !== ''): ?><div class="alert alert-success py-2" role="status"><?= e($success) ?></div><?php endif; ?>
                    <?php if ($error !== ''): ?><div class="alert alert-danger py-2" role="alert"><?= e($error) ?></div><?php endif; ?>

                    <form method="POST" action="../controller/AuthController.php?aksi=login" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                        <div class="login-form-group">
                            <label for="email">Email</label>
                            <div class="login-input-box">
                                <i class="bi bi-envelope login-input-icon" aria-hidden="true"></i>
                                <input class="login-input-control" type="email" id="email" name="email" placeholder="Masukkan email kamu" autocomplete="email" required autofocus value="<?= e($oldEmail) ?>">
                            </div>
                        </div>

                        <div class="login-form-group">
                            <label for="password">Password</label>
                            <div class="login-input-box">
                                <i class="bi bi-lock login-input-icon" aria-hidden="true"></i>
                                <input class="login-input-control" type="password" id="password" name="password" placeholder="Masukkan password kamu" autocomplete="current-password" required>
                                <button type="button" class="login-password-toggle" aria-label="Tampilkan password" aria-controls="password" aria-pressed="false" data-toggle-password>
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <div class="login-form-options">
                            <a href="forgot-password.php">Lupa password?</a>
                        </div>

                        <button class="login-submit" type="submit">Login <span aria-hidden="true">→</span></button>
                    </form>

                    <p class="login-register-bottom">
                        Belum punya akun? <a href="register.php">Daftar sekarang</a>
                    </p>
                </div>
            </section>
        </div>
    </main>
    <script src="../asset/js/page-actions.js"></script>
</body>
</html>
