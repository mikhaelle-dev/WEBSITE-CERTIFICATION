<?php
if (!isset($data['inTrash']) || !array_key_exists('remainingSeconds', $data)) {
    http_response_code(404);
    exit();
}

$closeUrl = '../controller/AuthController.php?aksi=logout';
$adminEmail = $data['adminEmail'] ?? admin_contact_email();
$remainingSeconds = $data['remainingSeconds'];
$remainingLabel = '';
if ($remainingSeconds !== null && $remainingSeconds > 0) {
    $minutes = intdiv($remainingSeconds, 60);
    $seconds = $remainingSeconds % 60;
    $remainingLabel = $minutes > 0
        ? $minutes . ' menit ' . $seconds . ' detik'
        : $seconds . ' detik';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Akses akun dinonaktifkan | Sertifikasi Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../asset/css/brutal.css" rel="stylesheet">
    <link href="../asset/css/account-disabled.css" rel="stylesheet">
</head>
<body class="account-disabled-page brutal-app">
    <main class="disabled-stage">
        <div class="disabled-dashboard-preview" aria-hidden="true" inert>
            <header class="preview-navbar">
                <span class="preview-brand">Sertifikasi <strong>Academy</strong></span>
                <span class="preview-nav-item"></span>
                <span class="preview-nav-item"></span>
                <span class="preview-avatar"></span>
            </header>
            <section class="preview-content">
                <div class="preview-title">
                    <span class="preview-line preview-line-short"></span>
                    <span class="preview-line preview-line-long"></span>
                </div>
                <div class="preview-stat-grid">
                    <div class="preview-stat"></div>
                    <div class="preview-stat"></div>
                    <div class="preview-stat"></div>
                </div>
                <div class="preview-course">
                    <div class="preview-course-image"></div>
                    <div class="preview-course-copy">
                        <span class="preview-line preview-line-long"></span>
                        <span class="preview-line preview-line-medium"></span>
                        <span class="preview-progress"></span>
                    </div>
                </div>
            </section>
        </div>

        <section class="disabled-dialog" role="dialog" aria-modal="true" aria-labelledby="disabled-title" aria-describedby="disabled-description">
            <div class="disabled-dialog-symbol" aria-hidden="true">
                <i class="bi bi-person-lock"></i>
            </div>
            <h1 id="disabled-title">Akun dinonaktifkan</h1>
            <?php if ($data['username'] !== ''): ?>
                <p class="disabled-account-name"><?= e($data['username']) ?></p>
            <?php endif; ?>
            <p id="disabled-description">
                <?php if ($data['inTrash'] && $remainingSeconds !== null && $remainingSeconds > 0): ?>
                    Akun dan seluruh data terkait akan dihapus permanen dalam
                    <strong id="disabled-countdown" data-remaining-seconds="<?= (int) $remainingSeconds ?>"><?= e($remainingLabel) ?></strong>.
                <?php elseif ($data['inTrash']): ?>
                    Masa pemulihan akun hampir berakhir. Hubungi admin sesegera mungkin.
                <?php else: ?>
                    Akun ini sedang dinonaktifkan sehingga kamu belum dapat mengakses dashboard.
                <?php endif; ?>
                Hubungi admin untuk meminta bantuan<?= $data['inTrash'] ? ' dan pemulihan akun' : '' ?>.
            </p>
            <?php if ($adminEmail !== ''): ?>
                <a class="btn brutal-btn disabled-contact" href="mailto:<?= e($adminEmail) ?>?subject=Permintaan%20bantuan%20akun">
                    <i class="bi bi-envelope me-1"></i>Hubungi admin
                </a>
            <?php else: ?>
                <p class="disabled-contact-hint">Email admin belum dikonfigurasi. Hubungi admin melalui kanal komunikasi yang biasa digunakan.</p>
            <?php endif; ?>
            <a class="btn brutal-btn brutal-btn--primary disabled-close" href="<?= e($closeUrl) ?>" autofocus>
                Tutup dan kembali ke login
            </a>
        </section>
    </main>
    <?php if ($data['inTrash'] && $remainingSeconds !== null && $remainingSeconds > 0): ?>
    <script src="../asset/js/account-disabled-countdown.js"></script>
    <?php endif; ?>
</body>
</html>
