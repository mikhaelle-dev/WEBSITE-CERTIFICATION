<?php
$course = $data['course'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($course['judul']) ?> | Overview Kursus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../asset/css/dashboard.css" rel="stylesheet">
    <link href="../../asset/css/course.css?v=2" rel="stylesheet">
    <link href="../../asset/css/brutal.css" rel="stylesheet">
</head>
<body class="brutal-app">
<?php $aktif = 'mylearning'; include __DIR__ . '/../../component/navbar.php'; ?>

<main class="container course-page py-4">
    <a class="course-back" href="DashboardController.php"><i class="bi bi-arrow-left"></i> Kembali ke Home</a>

    <section class="overview-hero">
        <div class="overview-hero-content">
            <span class="badge text-bg-success"><?= e($course['kategori']) ?> · <?= e($course['level']) ?></span>
            <h1><?= e($course['judul']) ?></h1>
            <p><?= e($course['deskripsi']) ?></p>
            <div class="overview-meta">
                <span><i class="bi bi-clock"></i> <?= e($course['durasi']) ?></span>
                <span><i class="bi bi-journal-text"></i> <?= count($data['lessons']) ?> materi</span>
                <span><i class="bi bi-patch-question"></i> 1 kuis</span>
            </div>
        </div>
        <i class="bi bi-shield-lock overview-hero-icon" aria-hidden="true"></i>
    </section>

    <?php if ($data['error'] !== ''): ?>
        <div class="alert alert-danger mt-4" role="alert"><?= e($data['error']) ?></div>
    <?php endif; ?>

    <div class="row g-4 mt-1">
        <section class="col-lg-8">
            <article class="course-content overview-section">
                <h2>Overview</h2>
                <p><?= e($course['deskripsi']) ?> Dalam kursus ini, kamu akan mempelajari kebiasaan praktis untuk melindungi akun, perangkat, dan data pribadi.</p>
                <h3 class="h5 mt-4">Yang akan kamu pelajari</h3>
                <ul class="overview-outcomes">
                    <li>Mengenali prinsip dasar keamanan siber dan perlindungan informasi.</li>
                    <li>Melindungi akun serta mengenali tanda-tanda phishing.</li>
                    <li>Menerapkan kebiasaan aman pada perangkat dan data.</li>
                </ul>
            </article>

            <article class="course-content overview-section mt-4">
                <h2>Kurikulum</h2>
                <div class="overview-curriculum">
                    <?php foreach ($data['lessons'] as $lesson): ?>
                        <div class="overview-curriculum-item">
                            <span class="overview-curriculum-number"><?= (int) $lesson['urutan'] ?></span>
                            <span><?= e($lesson['judul']) ?></span>
                            <i class="bi bi-journal-text ms-auto text-muted"></i>
                        </div>
                    <?php foreach ($lesson['submateri'] as $sublesson): ?>
                        <div class="overview-curriculum-subitem">
                            <i class="bi bi-dot"></i><?= e($sublesson['judul']) ?>
                        </div>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                    <div class="overview-curriculum-item">
                        <span class="overview-curriculum-number"><i class="bi bi-patch-question"></i></span>
                        <span><?= e($data['quiz']['judul']) ?></span>
                        <i class="bi bi-list-check ms-auto text-muted"></i>
                    </div>
                </div>
            </article>

            <article class="course-content overview-section mt-4" id="overview-badges">
                <div class="overview-section-heading">
                    <span class="overview-section-icon"><i class="bi bi-award"></i></span>
                    <div>
                        <h2>Badge yang bisa kamu dapatkan</h2>
                        <p class="text-muted mb-0">Setiap badge diberikan otomatis setelah seluruh submateri dalam modul milestone dibuka.</p>
                    </div>
                </div>
                <?php if ($data['badges']): ?>
                    <div class="overview-badge-grid">
                        <?php foreach ($data['badges'] as $badge): ?>
                            <article class="overview-badge <?= $badge['diperoleh_pada'] !== null ? 'is-earned' : '' ?>">
                                <span class="overview-badge-medallion">
                                    <i class="bi <?= e($badge['ikon']) ?>"></i>
                                </span>
                                <div class="overview-badge-content">
                                    <span class="overview-badge-status">
                                        <?= $badge['diperoleh_pada'] !== null ? 'Badge didapat' : 'Milestone materi ' . (int) $badge['materi_urutan'] ?>
                                    </span>
                                    <h3><?= e($badge['judul']) ?></h3>
                                    <p><?= e($badge['deskripsi']) ?></p>
                                    <?php if ($badge['diperoleh_pada'] !== null): ?>
                                        <small><i class="bi bi-check-circle-fill"></i> Diraih <?= e(tgl_indo($badge['diperoleh_pada'])) ?></small>
                                    <?php else: ?>
                                        <small><i class="bi bi-lock"></i> Otomatis diberikan setelah materi dibuka</small>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="overview-empty-award">
                        <i class="bi bi-award"></i>
                        <p>Badge untuk kursus ini belum tersedia.</p>
                    </div>
                <?php endif; ?>
            </article>

            <article class="overview-certificate mt-4" id="overview-certificate">
                <div class="overview-certificate-art" aria-hidden="true">
                    <div class="overview-certificate-art-inner">
                        <span class="overview-certificate-mark">SA</span>
                        <i class="bi bi-patch-check-fill"></i>
                        <small>SERTIFIKAT</small>
                        <strong><?= e($course['judul']) ?></strong>
                        <span class="overview-certificate-lines"></span>
                    </div>
                </div>
                <div class="overview-certificate-info">
                    <span class="overview-certificate-kicker"><i class="bi bi-patch-check"></i> Sertifikat kelulusan</span>
                    <h2>Selesaikan kursus, raih sertifikat</h2>
                    <p>Sertifikat personal akan diterbitkan otomatis setelah kamu membuka semua materi dan lulus kuis dengan nilai minimum <?= (int) $data['quiz']['nilai_minimum'] ?>.</p>
                    <ul>
                        <li><i class="bi bi-check2-circle"></i> Nama peserta diambil dari profil saat sertifikat diterbitkan.</li>
                        <li><i class="bi bi-check2-circle"></i> Memuat judul kursus, tanggal terbit, dan nomor sertifikat unik.</li>
                        <li><i class="bi bi-check2-circle"></i> Ditandatangani Mikhael Rakarenzo, Direktur Sertifikasi Academy.</li>
                    </ul>
                    <?php if ($data['certificate'] !== null): ?>
                        <a class="btn btn-enroll px-4" href="CertificateController.php?id=<?= (int) $data['certificate']['id'] ?>">
                            Lihat / cetak sertifikat <i class="bi bi-arrow-up-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="overview-certificate-pending"><i class="bi bi-lock"></i> Belum terbit — selesaikan semua materi dan lulus kuis</span>
                    <?php endif; ?>
                </div>
            </article>
        </section>

        <aside class="col-lg-4">
            <div class="overview-enroll-card">
                <?php if ($data['completed']): ?>
                    <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> Kursus ini sudah selesai.</div>
                    <a class="btn btn-enroll w-100" href="CourseController.php?slug=<?= urlencode($course['slug']) ?>&submateri=<?= (int) $data['resumeSublessonId'] ?>">Lihat materi</a>
                <?php elseif ($data['active']): ?>
                    <p class="text-muted">Kursus ini sudah ada di Home. Lanjutkan dari progres terakhirmu.</p>
                    <a class="btn btn-enroll w-100" href="CourseController.php?slug=<?= urlencode($course['slug']) ?>&submateri=<?= (int) $data['resumeSublessonId'] ?>">Resume Course</a>
                <?php elseif ($data['activeCourse'] !== null): ?>
                    <p class="fw-semibold">Selesaikan kursus aktifmu terlebih dahulu.</p>
                    <p class="text-muted mb-3"><?= e($data['activeCourse']['judul']) ?> masih berjalan.</p>
                    <a class="btn btn-enroll w-100" href="DashboardController.php">Kembali ke Home</a>
                <?php else: ?>
                    <p class="text-muted">Mulai belajar mandiri dan lanjutkan progresmu kapan saja.</p>
                    <form method="POST">
                        <input type="hidden" name="aksi" value="mulai_kursus">
                        <input type="hidden" name="csrf_token" value="<?= e($data['csrf']) ?>">
                        <button class="btn btn-enroll w-100" type="submit">Mulai belajar</button>
                    </form>
                <?php endif; ?>

                <hr>
                <ul class="overview-facts">
                    <li><i class="bi bi-clock"></i><span>Durasi estimasi</span><strong><?= e($course['durasi']) ?></strong></li>
                    <li><i class="bi bi-bar-chart"></i><span>Tingkat</span><strong><?= e($course['level']) ?></strong></li>
                    <li><i class="bi bi-journal-check"></i><span>Materi dan kuis</span><strong><?= count($data['lessons']) ?> materi · 1 kuis</strong></li>
                    <li><i class="bi bi-award"></i><span>Badge</span><strong><?= count($data['badges']) ?> milestone</strong></li>
                    <li><i class="bi bi-patch-check"></i><span>Sertifikat</span><strong><?= $data['certificate'] !== null ? 'Sudah terbit' : 'Setelah lulus' ?></strong></li>
                </ul>
            </div>
        </aside>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
