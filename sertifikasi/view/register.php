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
    <title>Daftar | Sertifikasi Academy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../asset/css/auth.css?v=20261103-green2" rel="stylesheet">
    <link href="../asset/css/theme-soft.css?v=20261103-green2" rel="stylesheet">
</head>
<body class="soft-app auth-page auth-register-page">
    <div class="login-bg-circle login-bg-circle-one" aria-hidden="true"></div>
    <div class="login-bg-circle login-bg-circle-two" aria-hidden="true"></div>
    <div class="login-bg-circle login-bg-circle-three" aria-hidden="true"></div>

    <main class="register-layout">
        <header class="register-navbar">
            <div class="register-login-top">
                Sudah punya akun?
                <a href="login.php">Masuk <span aria-hidden="true">↗</span></a>
            </div>
        </header>

        <div class="register-main">
            <section class="login-hero" aria-labelledby="register-hero-title">
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

                <h1 id="register-hero-title">Bangun skill<br>masa depanmu<br><span>di sini.</span></h1>

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

            <section class="register-panel" aria-labelledby="register-title">
                <div class="register-card">
                    <p class="register-welcome">Halo, calon peserta baru 👋</p>
                    <h2 id="register-title">Buat akunmu<span>.</span></h2>
                    <p class="register-description">Daftar gratis dan mulai perjalanan belajarmu menuju sertifikasi impian.</p>

                    <?php if ($error !== ''): ?><div class="alert alert-danger py-2" role="alert"><?= e($error) ?></div><?php endif; ?>

                    <form method="POST" action="../controller/AuthController.php?aksi=register">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                        <div class="register-form-group">
                            <label for="nama">Nama Lengkap</label>
                            <div class="register-input-box">
                                <i class="bi bi-person register-input-icon" aria-hidden="true"></i>
                                <input class="register-input-control" id="nama" name="nama" maxlength="100" placeholder="Masukkan nama lengkap kamu" autocomplete="name" required value="<?= e($oldNama) ?>">
                            </div>
                        </div>

                        <div class="register-form-group">
                            <label for="email">Email</label>
                            <div class="register-input-box">
                                <i class="bi bi-envelope register-input-icon" aria-hidden="true"></i>
                                <input class="register-input-control" type="email" id="email" name="email" placeholder="Masukkan email kamu" autocomplete="email" required value="<?= e($oldEmail) ?>">
                            </div>
                        </div>

                        <div class="register-form-group">
                            <label for="password">Password</label>
                            <div class="register-input-box">
                                <i class="bi bi-lock register-input-icon" aria-hidden="true"></i>
                                <input class="register-input-control" type="password" id="password" name="password" minlength="8" placeholder="Buat password (min. 8 karakter)" autocomplete="new-password" required>
                                <button type="button" class="register-password-toggle" aria-label="Tampilkan password" aria-controls="password" aria-pressed="false" data-toggle-password>
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <div class="register-form-group">
                            <label for="konfirmasi">Ulangi Password</label>
                            <div class="register-input-box">
                                <i class="bi bi-shield-lock register-input-icon" aria-hidden="true"></i>
                                <input class="register-input-control" type="password" id="konfirmasi" name="konfirmasi" minlength="8" placeholder="Ulangi password kamu" autocomplete="new-password" required>
                                <button type="button" class="register-password-toggle" aria-label="Tampilkan password" aria-controls="konfirmasi" aria-pressed="false" data-toggle-password>
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Akun baru selalu dibuat sebagai siswa. -->
                        <button class="register-submit" type="submit">Daftar <span aria-hidden="true">⟶</span></button>
                    </form>
                </div>
            </section>
        </div>
    </main>
    <script src="../asset/js/page-actions.js"></script>
</body>
</html>
