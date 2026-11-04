<?php
$kelasSaya = $data['kelasSaya'] ?? [];
$kelasTersedia = $data['kelasTersedia'] ?? [];
$kursusSaya = $data['kursusSaya'] ?? [];
$rekomendasi = $data['rekomendasi'] ?? [];
$pencapaian = $data['pencapaian'] ?? [];
$studentSuccess = $data['student_success'] ?? '';
$studentError = $data['student_error'] ?? '';
$q = $data['q'] ?? '';
$kategori = $data['kategori'] ?? '';
$activeCourse = $kursusSaya[0] ?? null;
$learningCount = count($kelasSaya) + ($activeCourse !== null ? 1 : 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa | Sertifikasi Academy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../asset/css/dashboard.css?v=<?= e(substr(hash_file('sha256', __DIR__ . '/../../asset/css/dashboard.css'), 0, 12)) ?>" rel="stylesheet">
    <link href="../../asset/css/theme-soft.css?v=20261103-course-flow" rel="stylesheet">
</head>
<body class="soft-app student-dashboard">
<?php $aktif = 'mylearning'; include __DIR__ . '/../../component/navbar.php'; ?>

<main class="student-dashboard-shell">
    <header class="student-welcome">
        <div>
            <h1>Selamat datang, <?= e($data['username'] ?? 'Siswa') ?></h1>
            <p>Temukan kelas, lanjutkan kursus, dan lihat perkembangan belajarmu.</p>
        </div>
        <a class="student-browse-link" href="#rekomendasi">
            Jelajahi kursus <i class="bi bi-arrow-down-right" aria-hidden="true"></i>
        </a>
    </header>

    <?php if ($studentSuccess !== ''): ?>
        <div class="alert alert-success student-alert" role="status"><?= e($studentSuccess) ?></div>
    <?php endif; ?>
    <?php if ($studentError !== ''): ?>
        <div class="alert alert-danger student-alert" role="alert"><?= e($studentError) ?></div>
    <?php endif; ?>

    <div class="student-dashboard-grid">
        <div class="student-primary-column">
            <section class="student-section" id="rekomendasi" aria-labelledby="recommendations-heading">
                <div class="student-section-heading">
                    <div>
                        <h2 id="recommendations-heading">
                            <?php if ($q !== ''): ?>
                                Hasil pencarian
                            <?php elseif ($kategori !== ''): ?>
                                Kursus <?= e($kategori) ?>
                            <?php else: ?>
                                Kursus untuk dipelajari
                            <?php endif; ?>
                        </h2>
                        <p>Pilih kursus mandiri atau kelas aktif dari instruktur.</p>
                    </div>
                    <?php if ($q !== '' || $kategori !== ''): ?>
                        <a class="student-reset-link" href="DashboardController.php#rekomendasi">Hapus filter</a>
                    <?php endif; ?>
                </div>

                <?php if ($rekomendasi !== []): ?>
                    <div class="student-course-grid">
                        <?php foreach ($rekomendasi as $kursus): ?>
                            <a class="student-course" href="CourseOverviewController.php?slug=<?= urlencode($kursus['slug'] ?? '') ?>">
                                <img class="student-course-image" src="../../asset/img/courses/<?= e($kursus['cover_image'] ?? 'digital-learning.jpg') ?>" alt="" loading="lazy" decoding="async">
                                <div class="student-course-content">
                                    <div class="student-course-top">
                                        <span class="student-course-level"><?= e(strtoupper($kursus['level'] ?? '')) ?></span>
                                    </div>
                                    <p class="student-course-category"><?= e($kursus['kategori'] ?? '') ?></p>
                                    <h3><?= e($kursus['judul'] ?? '') ?></h3>
                                    <p class="student-course-description"><?= e($kursus['deskripsi'] ?? '') ?></p>
                                    <div class="student-course-footer">
                                        <span><i class="bi bi-clock" aria-hidden="true"></i> <?= e($kursus['durasi'] ?? '') ?></span>
                                        <span class="student-course-open">Lihat kursus</span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="student-empty">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <div>
                            <strong><?= $q !== '' ? 'Kursus tidak ditemukan' : 'Belum ada kursus tersedia' ?></strong>
                            <p><?= $q !== '' ? 'Coba kata kunci lain atau hapus filter untuk melihat semua kursus.' : 'Kursus akan tampil di sini setelah tersedia di katalog.' ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($kelasTersedia !== []): ?>
                    <div class="student-available-list mt-4" id="kelas-tersedia">
                        <?php foreach ($kelasTersedia as $kelas): ?>
                            <a class="student-course student-available-course" href="ClassroomController.php?class_id=<?= (int) ($kelas['id'] ?? 0) ?>&amp;tab=overview" aria-label="Lihat kelas <?= e($kelas['judul'] ?? 'Kelas') ?>">
                                <span class="student-available-image" aria-hidden="true">
                                    <?php if (!empty($kelas['icon_url'])): ?>
                                        <img src="<?= e($kelas['icon_url']) ?>" alt="" loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <i class="bi bi-journal-bookmark"></i>
                                    <?php endif; ?>
                                </span>
                                <div class="student-course-content">
                                    <div class="student-available-meta">
                                        <span class="student-course-category"><?= e($kelas['kategori'] ?? 'Umum') ?></span>
                                        <span class="student-available-badges">
                                            <?php if (empty($kelas['is_public'])): ?>
                                                <span class="student-available-access" aria-label="Kode kelas diperlukan" title="Kode kelas diperlukan">
                                                    <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                                </span>
                                            <?php endif; ?>
                                            <span class="student-course-level"><?= e(strtoupper($kelas['tingkat'] ?? 'Beginner')) ?></span>
                                        </span>
                                    </div>
                                    <h3><?= e($kelas['judul'] ?? 'Kelas') ?></h3>
                                    <p class="student-course-description"><?= e($kelas['deskripsi'] ?? '') ?></p>
                                    <div class="student-course-footer">
                                        <span><i class="bi bi-clock" aria-hidden="true"></i> <?= ($kelas['durasi_estimasi'] ?? null) !== null ? (int) $kelas['durasi_estimasi'] . ' jam' : 'Durasi belum ditentukan' ?></span>
                                        <span class="student-course-open">Lihat kursus <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="student-section" aria-labelledby="student-classes-heading">
                <div class="student-section-heading">
                    <div>
                        <h2 id="student-classes-heading">Kelas dan kursus yang saya ikuti</h2>
                        <p>Kelas dan kursus yang sudah kamu ikuti.</p>
                    </div>
                    <span class="student-count"><?= $learningCount ?></span>
                </div>

                <?php if ($learningCount > 0): ?>
                    <div class="student-class-list">
                        <?php if ($activeCourse !== null): ?>
                            <?php $courseProgress = max(0, min(100, (int) ($activeCourse['progress'] ?? 0))); ?>
                            <article class="student-class-row">
                                <span class="student-class-mark" aria-hidden="true">
                                    <img src="../../asset/img/courses/<?= e($activeCourse['cover_image'] ?? 'digital-learning.jpg') ?>" alt="" loading="lazy" decoding="async">
                                </span>
                                <a class="student-class-content student-enrolled-overview" href="CourseController.php?slug=<?= urlencode($activeCourse['slug']) ?><?= $activeCourse['resume_sublesson_id'] !== null ? '&amp;submateri=' . (int) $activeCourse['resume_sublesson_id'] : '' ?>" aria-label="Lanjutkan kursus <?= e($activeCourse['judul']) ?>">
                                    <div class="student-class-meta">
                                        <span>Kursus mandiri · <?= e($activeCourse['kategori'] ?? 'Umum') ?></span>
                                        <span class="student-status"><?= e(ucfirst($activeCourse['level'] ?? '')) ?></span>
                                    </div>
                                    <h3><?= e($activeCourse['judul']) ?></h3>
                                    <p><?= e($activeCourse['deskripsi'] ?? '') ?></p>
                                    <div class="student-progress-label">
                                        <span>Progres kursus</span><strong><?= $courseProgress ?>%</strong>
                                    </div>
                                    <div class="student-progress-track" role="progressbar" aria-label="Progres kursus <?= e($activeCourse['judul']) ?>" aria-valuenow="<?= $courseProgress ?>" aria-valuemin="0" aria-valuemax="100">
                                        <span data-progress="<?= $courseProgress ?>"></span>
                                    </div>
                                </a>
                                <a class="student-class-open" href="CourseController.php?slug=<?= urlencode($activeCourse['slug']) ?><?= $activeCourse['resume_sublesson_id'] !== null ? '&amp;submateri=' . (int) $activeCourse['resume_sublesson_id'] : '' ?>" aria-label="Lanjutkan kursus <?= e($activeCourse['judul']) ?>">
                                    <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                                </a>
                            </article>
                        <?php endif; ?>
                        <?php foreach ($kelasSaya as $kelas): ?>
                            <?php $progress = max(0, min(100, (int) ($kelas['progress'] ?? 0))); ?>
                            <article class="student-class-row">
                                <span class="student-class-mark" aria-hidden="true">
                                    <?php if (!empty($kelas['icon_url'])): ?>
                                        <img src="<?= e($kelas['icon_url']) ?>" alt="" loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <i class="bi bi-easel"></i>
                                    <?php endif; ?>
                                </span>
                                <a class="student-class-content student-enrolled-overview" href="ClassroomController.php?class_id=<?= (int) ($kelas['id'] ?? 0) ?>&amp;tab=overview" aria-label="Buka overview kelas <?= e($kelas['judul'] ?? 'Kelas') ?>">
                                    <div class="student-class-meta">
                                        <span><?= e($kelas['instruktur_nama'] ?? 'Instruktur') ?></span>
                                        <span class="student-status"><?= e(ucfirst($kelas['status'] ?? 'aktif')) ?></span>
                                    </div>
                                    <h3><?= e($kelas['judul'] ?? 'Kelas') ?></h3>
                                    <p><?= e($kelas['deskripsi'] ?? '') ?></p>
                                    <div class="student-progress-label">
                                        <span>Progres kelas</span><strong><?= $progress ?>%</strong>
                                    </div>
                                    <div class="student-progress-track" role="progressbar" aria-label="Progres kelas <?= e($kelas['judul'] ?? 'Kelas') ?>" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100">
                                        <span data-progress="<?= $progress ?>"></span>
                                    </div>
                                </a>
                                <a class="student-class-open" href="ClassroomController.php?class_id=<?= (int) ($kelas['id'] ?? 0) ?>&amp;tab=overview" aria-label="Buka kelas <?= e($kelas['judul'] ?? 'Kelas') ?>">
                                    <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                                </a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="student-empty">
                        <i class="bi bi-easel" aria-hidden="true"></i>
                        <div>
                            <strong>Belum ada kelas atau kursus yang diikuti</strong>
                            <p>Kelas yang diikuti dan kursus yang kamu pelajari akan muncul di sini.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </section>

        </div>

        <aside class="student-secondary-column">
            <section class="student-side-section" id="gabung-kelas" aria-labelledby="join-class-heading">
                <div class="student-side-heading">
                    <span class="student-side-icon" aria-hidden="true"><i class="bi bi-key"></i></span>
                    <div>
                        <h2 id="join-class-heading">Gabung kelas</h2>
                        <p>Punya kode dari instruktur?</p>
                    </div>
                </div>
                <form method="POST" action="DashboardController.php" class="student-join-form">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="aksi" value="join_class">
                    <label class="visually-hidden" for="student-enroll-key">Kode enroll kelas</label>
                    <input id="student-enroll-key" type="text" name="enroll_key" placeholder="Masukkan kode enroll" autocomplete="off" required>
                    <button type="submit">Gabung kelas <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
                </form>
                <p class="student-form-hint">Kode ini hanya untuk kelas privat. Kelas publik dapat diikuti langsung dari overview kelas.</p>
            </section>

            <section class="student-side-section" aria-labelledby="achievements-heading">
                <div class="student-side-heading student-achievement-heading">
                    <span class="student-side-icon student-side-icon-warm" aria-hidden="true"><i class="bi bi-award"></i></span>
                    <div>
                        <h2 id="achievements-heading">Pencapaian terbaru</h2>
                        <p>Hasil dari proses belajarmu.</p>
                    </div>
                    <a href="ProfileController.php?tab=sertifikat" aria-label="Lihat semua pencapaian"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                </div>

                <?php if ($pencapaian !== []): ?>
                    <div class="student-achievement-list">
                        <?php foreach ($pencapaian as $item): ?>
                            <a class="student-achievement" href="ProfileController.php?tab=sertifikat">
                                <i class="bi <?= e($item['ikon'] ?? 'bi-award') ?>" aria-hidden="true"></i>
                                <span>
                                    <small><?= e($item['jenis'] ?? '') ?></small>
                                    <strong><?= e($item['judul'] ?? '') ?></strong>
                                    <small><?= e($item['kursus_judul'] ?? '') ?></small>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="student-achievement-empty">
                        <p>Selesaikan materi dan asesmen untuk mendapatkan sertifikat atau lencana pertamamu.</p>
                    </div>
                <?php endif; ?>
            </section>
        </aside>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../asset/js/page-actions.js"></script>
</body>
</html>
