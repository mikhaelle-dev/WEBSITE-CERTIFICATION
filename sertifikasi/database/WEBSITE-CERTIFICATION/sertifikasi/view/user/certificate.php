<?php
$certificate = $data['certificate'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat <?= e($certificate['judul_kursus']) ?> | Sertifikasi Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../asset/css/certificate.css" rel="stylesheet">
    <link href="../../asset/css/brutal.css" rel="stylesheet">
</head>
<body class="brutal-app certificate-page">
<nav class="certificate-toolbar" aria-label="Aksi sertifikat">
    <a href="ProfileController.php?tab=sertifikat"><i class="bi bi-arrow-left"></i> Kembali ke pencapaian</a>
    <button type="button" data-print-page><i class="bi bi-printer"></i> Cetak / Simpan PDF</button>
</nav>

<main class="certificate-sheet">
    <div class="certificate-inner">
        <header class="certificate-brand">
            <div class="brand-mark" aria-hidden="true"><span>SA</span></div>
            <div>
                <div class="brand-name">SERTIFIKASI <strong>ACADEMY</strong></div>
                <div class="brand-caption">PUSAT PEMBELAJARAN PROFESIONAL</div>
            </div>
            <div class="certificate-seal" aria-label="Sertifikat kelulusan">
                <i class="bi bi-patch-check-fill"></i>
                <span>COURSE<br>COMPLETION</span>
            </div>
        </header>

        <div class="certificate-rule"></div>

        <section class="certificate-copy">
            <p class="eyebrow">SERTIFIKAT KELULUSAN</p>
            <h1>Sertifikat</h1>
            <p class="intro">Dengan ini menyatakan bahwa</p>
            <h2><?= e($certificate['nama_peserta']) ?></h2>
            <p class="completion-copy">telah menyelesaikan pembelajaran dan memenuhi persyaratan kelulusan untuk kursus</p>
            <h3><?= e($certificate['judul_kursus']) ?></h3>
            <p class="category"><?= e($certificate['kategori']) ?> <span>·</span> <?= e($certificate['durasi']) ?></p>
        </section>

        <section class="certificate-details" aria-label="Detail sertifikat">
            <div><span>Tanggal diterbitkan</span><strong><?= e(tgl_indo($certificate['diterbitkan_pada'])) ?></strong></div>
            <div><span>Nomor sertifikat</span><strong><?= e($certificate['nomor_sertifikat']) ?></strong></div>
        </section>

        <footer class="certificate-footer">
            <div class="director-signature">
                <div class="signature-name">Mikhael Rakarenzo</div>
                <div class="signature-line"></div>
                <strong>Mikhael Rakarenzo</strong>
                <span>Direktur · Sertifikasi Academy</span>
            </div>
            <div class="certificate-seal-large" aria-hidden="true">
                <i class="bi bi-shield-check"></i>
                <span>SA<br>VERIFIED<br>LEARNING</span>
            </div>
        </footer>

        <div class="certificate-bottom">
            <span>SERTIFIKASI ACADEMY <b>·</b> BELAJAR, BERTUMBUH, TERSERTIFIKASI</span>
            <span><?= e($certificate['nomor_sertifikat']) ?></span>
        </div>
    </div>
</main>
<script src="../../asset/js/page-actions.js"></script>
</body>
</html>
