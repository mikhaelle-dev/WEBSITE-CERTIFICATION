<?php
// View: HANYA tampilan. Data datang dari DashboardController lewat $data.
$punyaKursus = !empty($data['kursusSaya']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | Sertifikasi Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../asset/css/dashboard.css" rel="stylesheet">
    <link href="../../asset/css/brutal.css" rel="stylesheet">
</head>
<body class="brutal-app">

<?php $aktif = 'mylearning'; include __DIR__ . '/../../component/navbar.php'; ?>

<main class="container-fluid px-4 px-lg-5 py-4">
    <div class="row g-4">

        <!-- KOLOM UTAMA -->
        <div class="col-lg-9">
            <h1 class="page-title"><i class="bi bi-house-door"></i> Home</h1>

            <?php if (!$punyaKursus): ?>
                <section class="promo">
                    <div>
                        <span class="promo-tag">MULAI DARI SINI</span>
                        <h2>Bangun skill, dapatkan sertifikat.</h2>
                        <p>Belajar mandiri, kerjakan asesmen, dan raih sertifikat otomatis kalau kamu lolos.</p>
                        <a href="#rekomendasi" class="btn btn-promo">Jelajahi Kursus</a>
                    </div>
                    <i class="bi bi-mortarboard-fill promo-icon"></i>
                </section>

            <?php endif; ?>

            <?php if ($punyaKursus && $data['q'] === ''): ?>
                <section aria-labelledby="progress-heading">
                    <div class="d-flex justify-content-between align-items-center gap-2">
                        <h3 class="section-title mb-0" id="progress-heading">Progres belajar</h3>
                        <a href="ProfileController.php?tab=riwayat&amp;status_belajar=Berjalan" class="small">Riwayat progres</a>
                    </div>
                    <?php foreach ($data['kursusSaya'] as $k): ?>
                        <article class="course-card mb-4">
                            <div class="course-body">
                                <div class="course-meta"><?= e($k['tipe']) ?> &middot; <?= e($k['durasi']) ?></div>
                                <h4 class="course-title"><?= e($k['judul']) ?></h4>
                                <div class="progress" role="progressbar" aria-valuenow="<?= (int) $k['progress'] ?>" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar" data-progress="<?= (int) $k['progress'] ?>"></div>
                                </div>
                                <small class="text-muted"><?= (int) $k['progress'] ?>% selesai</small>
                                <a href="CourseController.php?slug=<?= urlencode($k['slug']) ?><?= $k['resume_sublesson_id'] !== null ? '&amp;submateri=' . (int) $k['resume_sublesson_id'] : '' ?>" class="btn btn-sm btn-enroll mt-2">Lanjutkan belajar</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center gap-2">
                <h3 class="section-title" id="rekomendasi">
                    <?php if ($data['q'] !== ''): ?>
                        Hasil pencarian: <?= e($data['q']) ?>
                    <?php else: ?>
                        <?= $data['kategori'] !== '' ? 'Kursus ' . e($data['kategori']) : 'Lihat semua kursus' ?>
                    <?php endif; ?>
                </h3>
                <?php if ($data['kategori'] !== ''): ?>
                    <a href="DashboardController.php#rekomendasi" class="small">Tampilkan semua</a>
                <?php endif; ?>
            </div>
            <?php $daftarKartu = $data['rekomendasi']; ?>

            <?php if (empty($daftarKartu)): ?>
                <p class="text-muted">
                    <?= $data['q'] !== '' ? 'Tidak ada kursus yang cocok dengan pencarian.' : 'Belum ada kursus di bidang ini.' ?>
                </p>
            <?php endif; ?>

            <div class="row g-4">
                <?php foreach ($daftarKartu as $k): ?>
                    <div class="col-sm-6 col-xl-4">
                        <article class="course-card">
                            <div class="course-thumb">
                                <span class="level-badge"><?= e(strtoupper($k['level'])) ?></span>
                                <i class="bi bi-play-circle-fill play"></i>
                            </div>
                            <div class="course-body">
                                <div class="course-meta"><?= e($k['tipe']) ?> &middot; <?= e($k['durasi']) ?></div>
                                <h4 class="course-title"><?= e($k['judul']) ?></h4>

                                <?php if (!empty($k['sertifikat'])): ?>
                                    <small class="text-muted"><i class="bi bi-award"></i> Ada sertifikat</small>
                                <?php endif; ?>
                                <?php if (!empty($k['slug'])): ?>
                                    <a href="CourseOverviewController.php?slug=<?= urlencode($k['slug']) ?>" class="btn btn-sm btn-enroll mt-2">Mulai Belajar</a>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-secondary mt-2" disabled>Segera hadir</button>
                                <?php endif; ?>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- PANEL KANAN: pencapaian -->
        <aside class="col-lg-3">
            <div class="side-panel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="m-0 fw-bold">Latest Achievements</h5>
                    <a href="ProfileController.php?tab=sertifikat" class="small">Lihat semua</a>
                </div>

                <?php if (empty($data['pencapaian'])): ?>
                    <div class="empty-ach">
                        <i class="bi bi-trophy"></i>
                        <p class="m-0">Belum ada pencapaian. Selesaikan kursus pertamamu untuk dapat sertifikat.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($data['pencapaian'] as $p): ?>
                        <a class="ach-item" href="ProfileController.php?tab=sertifikat" data-history-back>
                            <i class="bi <?= e($p['ikon']) ?>"></i>
                            <div>
                                <small class="text-muted text-uppercase"><?= e($p['jenis']) ?></small>
                                <div class="fw-bold"><?= e($p['judul']) ?></div>
                                <small class="text-muted"><?= e($p['kursus_judul']) ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../asset/js/page-actions.js"></script>
</body>
</html>
