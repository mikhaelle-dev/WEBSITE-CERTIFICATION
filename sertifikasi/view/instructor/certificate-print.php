<?php
$template = $template ?? [];
$background = trim((string) ($template['background_path'] ?? ''));
$signature = trim((string) ($template['signature_path'] ?? ''));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sertifikat <?= e($certificate['certificate_no']) ?></title>
    <link href="../../asset/css/certificate.css" rel="stylesheet">
    <link href="../../asset/css/instructor-tools.css" rel="stylesheet">
</head>
<body class="instructor-certificate-print">
<nav class="print-toolbar"><a href="CertificateController.php">Kembali</a><button type="button" data-print-certificate>Unduh PDF / Cetak</button></nav>
<main class="instructor-certificate-sheet">
    <?php if ($background !== ''): ?><img class="certificate-background-image" src="<?= e($background) ?>" alt=""><?php endif; ?>
    <div class="certificate-inner">
        <p class="eyebrow">SERTIFIKASI ACADEMY</p>
        <h1>Sertifikat Kelulusan</h1>
        <p>Dengan ini menyatakan bahwa</p>
        <h2><?= e($certificate['student_name']) ?></h2>
        <p>telah menyelesaikan kelas</p>
        <h3><?= e($certificate['class_title']) ?></h3>
        <p>Diterbitkan pada <?= e(tgl_indo($certificate['issued_at'])) ?></p>
        <div class="certificate-number">Nomor: <?= e($certificate['certificate_no']) ?></div>
        <div class="print-signature">
            <?php if ($signature !== ''): ?><img src="<?= e($signature) ?>" alt="Tanda tangan instruktur"><?php endif; ?>
            <strong><?= e($template['signer_name'] ?? '') ?></strong>
            <span><?= e($template['signer_title'] ?? '') ?></span>
        </div>
        <div class="certificate-verification">Verifikasi: <?= e($certificate['certificate_no']) ?></div>
    </div>
</main>
<script src="../../asset/js/instructor-tools.js"></script>
</body>
</html>
