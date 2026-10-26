<?php
require_once __DIR__ . '/../config/helpers.php';

$adminEmail = admin_contact_email();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa password | Sertifikasi Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../asset/css/auth.css" rel="stylesheet">
    <link href="../asset/css/brutal.css" rel="stylesheet">
</head>
<body class="auth-page brutal-app">
    <main class="auth-wrap auth-brutal forgot-password-page">
        <a class="auth-brand forgot-password-brand" href="login.php" aria-label="Sertifikasi Academy, kembali ke login">
            <span>Sertifikasi</span><strong>Academy</strong>
        </a>
        <section class="auth-panel w-100">
            <div class="auth-box brutal-card">
                <h1 class="h2">Lupa password?</h1>
                <p class="text-muted">Hubungi admin untuk meminta reset password akun. Demi keamanan, password lama tidak dapat ditampilkan.</p>
                <?php if ($adminEmail !== ''): ?>
                    <a class="btn brutal-btn brutal-btn--primary w-100" href="mailto:<?= e($adminEmail) ?>?subject=Permintaan%20reset%20password">
                        <i class="bi bi-envelope me-1"></i>Hubungi admin
                    </a>
                <?php else: ?>
                    <div class="alert alert-info" role="status">Email admin belum dikonfigurasi. Hubungi admin melalui kanal komunikasi yang biasa digunakan.</div>
                <?php endif; ?>
                <a class="d-inline-flex align-items-center gap-1 mt-3" href="login.php"><i class="bi bi-arrow-left"></i>Kembali ke login</a>
            </div>
        </section>
    </main>
</body>
</html>
