<?php
$class = $data['class'];
$enrolled = !empty($data['enrolled']);
$modules = $data['modules'] ?? ($class['modules'] ?? []);
$quizzes = $data['quizzes'] ?? ($class['quizzes'] ?? []);
$openedMap = array_fill_keys(array_map('intval', $data['openedMaterialIds'] ?? []), true);
$resultsByQuiz = $data['resultsByQuiz'] ?? [];
$totalMaterials = array_sum(array_map(static fn ($module) => (int) ($module['total_materi'] ?? count($module['materi'] ?? [])), $modules));
$totalQuizzes = count($quizzes);
$totalModules = count($modules);
$progress = (int) ($class['progress'] ?? 0);
$certificate = $data['certificate'] ?? null;
$classBadge = $data['classBadge'] ?? null;
$badgeLabel = trim((string) ($class['badge_label'] ?? ''));
$badgeEnabled = !empty($class['badge_aktif']) && $badgeLabel !== '';
$duration = (int) ($class['durasi_estimasi'] ?? 0);
$durationLabel = $duration > 0 ? $duration . ' jam belajar' : 'Belum ditentukan';
$levelLabels = ['Beginner' => 'Pemula', 'Intermediate' => 'Menengah', 'Advanced' => 'Lanjutan'];
$levelLabel = $levelLabels[$class['tingkat'] ?? ''] ?? 'Pemula';
$continueUrl = 'ClassroomController.php?class_id=' . (int) $class['id'] . '&tab=materi';
$continueLabel = 'Mulai belajar';
$continueIcon = 'bi-play-circle';

if ($enrolled) {
    $firstAvailableMaterial = null;
    $nextMaterial = null;
    $lockedModuleId = null;
    foreach ($modules as $module) {
        if (empty($module['is_unlocked'])) {
            $lockedModuleId ??= (int) $module['id'];
            continue;
        }
        foreach ($module['materi'] ?? [] as $material) {
            $candidate = 'ClassroomController.php?class_id=' . (int) $class['id']
                . '&tab=materi&module_id=' . (int) $module['id']
                . '&material_id=' . (int) $material['id'];
            $firstAvailableMaterial ??= $candidate;
            if (!isset($openedMap[(int) $material['id']])) {
                $nextMaterial = $candidate;
                break 2;
            }
        }
    }

    $continueUrl = $nextMaterial ?? $firstAvailableMaterial ?? $continueUrl;
    $continueLabel = $progress > 0 ? 'Lanjutkan belajar' : $continueLabel;
    if ($nextMaterial === null && $lockedModuleId !== null) {
        $continueUrl = 'ClassroomController.php?class_id=' . (int) $class['id'] . '&tab=materi&module_id=' . $lockedModuleId;
        $continueLabel = 'Buka modul privat';
        $continueIcon = 'bi-lock';
    }
    foreach ($quizzes as $quiz) {
        $result = $resultsByQuiz[(int) $quiz['id']] ?? null;
        if ($result === null || (int) ($result['lulus'] ?? 0) !== 1) {
            if ($nextMaterial === null && $lockedModuleId === null) {
                $continueUrl = 'ClassroomController.php?class_id=' . (int) $class['id'] . '&tab=kuis';
                $continueLabel = $result ? 'Ulangi kuis' : 'Mulai kuis';
                $continueIcon = 'bi-patch-question';
            }
            break;
        }
    }

    if ($progress >= 100) {
        $continueUrl = 'ProfileController.php?tab=riwayat';
        $continueLabel = 'Lihat riwayat belajar';
        $continueIcon = 'bi-clock-history';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi kelas <?= e($class['judul']) ?> | Sertifikasi Academy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../asset/css/dashboard.css?v=20261103-course-flow" rel="stylesheet">
    <link href="../../asset/css/course.css?v=20261104-class-flow" rel="stylesheet">
    <link href="../../asset/css/theme-soft.css?v=20261103-course-flow" rel="stylesheet">
</head>
<body class="soft-app class-overview-page">
<?php $aktif = 'mylearning'; include __DIR__ . '/../../component/navbar.php'; ?>

<main class="container py-4 py-lg-5 course-page">
    <a class="course-back" href="DashboardController.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke Dasbor</a>

    <?php if (!empty($data['success'])): ?>
        <div class="alert alert-success mt-3" role="status"><?= e($data['success']) ?></div>
    <?php endif; ?>
    <?php if (!empty($data['error'])): ?>
        <div class="alert alert-danger mt-3" role="alert"><?= e($data['error']) ?></div>
    <?php endif; ?>

    <section class="overview-hero">
        <div class="overview-hero-content">
            <span class="badge text-bg-success"><?= e($class['kategori'] ?? 'Umum') ?> · <?= e($levelLabel) ?></span>
            <h1><?= e($class['judul']) ?></h1>
            <p><?= e($class['deskripsi'] ?? '') ?></p>
            <div class="overview-meta">
                <span><i class="bi bi-clock" aria-hidden="true"></i> Estimasi belajar: <?= e($durationLabel) ?></span>
                <span><i class="bi bi-journal-text" aria-hidden="true"></i> <?= $totalModules ?> modul</span>
                <span><i class="bi bi-patch-question" aria-hidden="true"></i> <?= $totalQuizzes ?> kuis</span>
                <span><i class="bi bi-person-workspace" aria-hidden="true"></i> <?= e($class['instruktur_nama'] ?? 'Instruktur') ?></span>
            </div>
        </div>
        <?php if (!empty($class['icon_url'])): ?>
            <img class="class-overview-hero-image" src="<?= e($class['icon_url']) ?>" alt="" decoding="async">
        <?php else: ?>
            <i class="bi bi-journal-bookmark overview-hero-icon" aria-hidden="true"></i>
        <?php endif; ?>
    </section>

    <div class="row g-4 mt-1 align-items-start">
        <section class="col-lg-8" aria-labelledby="overview-content-heading">
            <article class="course-content overview-section">
                <h2 id="overview-content-heading">Gambaran kelas</h2>
                <p><?= e($class['deskripsi'] ?? '') ?></p>
                <h3 class="h5 mt-4">Modul yang akan dipelajari</h3>
                <?php if ($modules !== []): ?>
                    <ul class="overview-outcomes">
                        <?php foreach ($modules as $module): ?>
                            <li><?= e($module['judul']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-secondary mb-0">Instruktur belum menambahkan modul ke kelas ini.</p>
                <?php endif; ?>
            </article>

            <article class="course-content overview-section mt-4">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
                    <h2 class="mb-0">Kurikulum</h2>
                    <span class="small text-secondary"><?= $totalMaterials ?> materi · <?= $totalQuizzes ?> kuis</span>
                </div>
                <?php if ($modules === [] && $quizzes === []): ?>
                    <p class="text-secondary mb-0">Kurikulum akan muncul setelah instruktur menambahkan modul atau kuis.</p>
                <?php else: ?>
                    <div class="overview-curriculum">
                        <?php foreach ($modules as $index => $module): ?>
                            <?php
                            $isUnlocked = $enrolled && !empty($module['is_unlocked']);
                            $materials = $module['materi'] ?? [];
                            $moduleMaterialCount = (int) ($module['total_materi'] ?? count($materials));
                            ?>
                            <div class="overview-curriculum-item">
                                <span class="overview-curriculum-number"><?= (int) ($module['urutan'] ?? ($index + 1)) ?></span>
                                <span><?= e($module['judul']) ?></span>
                                <small class="ms-auto text-muted"><?= $moduleMaterialCount ?> materi</small>
                            </div>
                            <?php if (!empty($module['deskripsi'])): ?>
                                <div class="overview-curriculum-subitem"><i class="bi bi-info-circle" aria-hidden="true"></i><?= e($module['deskripsi']) ?></div>
                            <?php endif; ?>
                            <?php if ($enrolled && $isUnlocked && $materials !== []): ?>
                                <?php foreach ($materials as $material): ?>
                                    <?php $isOpened = isset($openedMap[(int) $material['id']]); ?>
                                    <a class="overview-curriculum-subitem text-decoration-none <?= $isOpened ? 'is-complete' : '' ?>"
                                       href="ClassroomController.php?class_id=<?= (int) $class['id'] ?>&amp;tab=materi&amp;module_id=<?= (int) $module['id'] ?>&amp;material_id=<?= (int) $material['id'] ?>">
                                        <i class="bi <?= $isOpened ? 'bi-check-circle-fill' : 'bi-play-circle' ?>" aria-hidden="true"></i><?= e($material['judul']) ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php elseif ($moduleMaterialCount > 0): ?>
                                <div class="overview-curriculum-subitem">
                                    <i class="bi <?= !empty($module['is_private']) ? 'bi-lock' : 'bi-journal-text' ?>" aria-hidden="true"></i>
                                    <?= $enrolled && !empty($module['is_private'])
                                        ? 'Buka dengan kode akses untuk melihat materi'
                                        : ($enrolled ? 'Materi belum tersedia' : 'Materi tersedia setelah bergabung') ?>
                                </div>
                            <?php else: ?>
                                <div class="overview-curriculum-subitem"><i class="bi bi-dash-circle" aria-hidden="true"></i>Belum ada materi di modul ini</div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php foreach ($quizzes as $index => $quiz): ?>
                            <?php $result = $resultsByQuiz[(int) $quiz['id']] ?? null; ?>
                            <div class="overview-curriculum-item">
                                <span class="overview-curriculum-number"><i class="bi bi-patch-question" aria-hidden="true"></i></span>
                                <span><?= e($quiz['judul']) ?></span>
                                <?php if ($result): ?>
                                    <small class="ms-auto <?= (int) $result['lulus'] === 1 ? 'text-success' : 'text-warning' ?>">
                                        <?= (int) $result['nilai'] ?>%
                                    </small>
                                <?php else: ?>
                                    <i class="bi bi-list-check ms-auto text-muted" aria-hidden="true"></i>
                                <?php endif; ?>
                            </div>
                            <div class="overview-curriculum-subitem">
                                <i class="bi bi-question-circle" aria-hidden="true"></i>
                                <?= (int) ($quiz['total_soal'] ?? 0) ?> soal · Nilai minimum <?= (int) ($quiz['nilai_minimum'] ?? 70) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>

            <article class="course-content overview-section mt-4" id="overview-badges">
                <div class="overview-section-heading">
                    <span class="overview-section-icon"><i class="bi bi-award" aria-hidden="true"></i></span>
                    <div>
                        <h2>Lencana yang bisa kamu dapatkan</h2>
                        <p class="text-muted mb-0">Lencana ditentukan instruktur dan diberikan otomatis setelah kelas diselesaikan.</p>
                    </div>
                </div>
                <?php if ($badgeEnabled): ?>
                    <?php $badgeEarned = $classBadge !== null; ?>
                    <article class="overview-badge class-badge-<?= e($class['badge_color'] ?? 'purple') ?> <?= $badgeEarned ? 'is-earned' : '' ?>">
                        <span class="overview-badge-medallion">
                            <i class="bi bi-award" aria-hidden="true"></i>
                        </span>
                        <div class="overview-badge-content">
                            <span class="overview-badge-status"><?= $badgeEarned ? 'Lencana didapat' : 'Pencapaian penyelesaian kelas' ?></span>
                            <h3><?= e($badgeLabel) ?></h3>
                            <p>Lencana ini diberikan otomatis setelah semua materi dibuka dan seluruh kuis lulus.</p>
                            <?php if ($badgeEarned): ?>
                                <small><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Diraih <?= e(date('d M Y', strtotime((string) $classBadge['earned_at']))) ?></small>
                            <?php else: ?>
                                <small><i class="bi bi-lock" aria-hidden="true"></i> Selesaikan kelas untuk mendapatkannya</small>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php else: ?>
                    <div class="overview-empty-award">
                        <i class="bi bi-award" aria-hidden="true"></i>
                        <p>Instruktur belum menambahkan badge untuk kelas ini.</p>
                    </div>
                <?php endif; ?>
            </article>

            <article class="overview-certificate mt-4" id="overview-certificate">
                <div class="overview-certificate-art" aria-hidden="true">
                    <div class="overview-certificate-art-inner">
                        <span class="overview-certificate-mark">SA</span>
                        <i class="bi bi-patch-check-fill"></i>
                        <small>SERTIFIKAT</small>
                        <strong><?= e($class['judul']) ?></strong>
                        <span class="overview-certificate-lines"></span>
                    </div>
                </div>
                <div class="overview-certificate-info">
                    <span class="overview-certificate-kicker"><i class="bi bi-patch-check" aria-hidden="true"></i> Sertifikat kelulusan</span>
                    <h2>Selesaikan kelas, raih sertifikat</h2>
                    <p>Sertifikat dengan template default diterbitkan otomatis setelah semua materi dibuka dan seluruh kuis lulus.</p>
                    <ul>
                        <li><i class="bi bi-check2-circle" aria-hidden="true"></i> Nama peserta dan judul kelas tercantum pada sertifikat.</li>
                        <li><i class="bi bi-check2-circle" aria-hidden="true"></i> Sertifikat dapat dilihat dan dicetak setelah kelas selesai.</li>
                    </ul>
                    <?php if ($certificate !== null): ?>
                        <a class="btn btn-enroll px-4" href="<?= e($certificate['certificate_url']) ?>">
                            Lihat / cetak sertifikat <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                        </a>
                    <?php else: ?>
                        <span class="overview-certificate-pending"><i class="bi bi-lock" aria-hidden="true"></i> Belum terbit — selesaikan semua materi dan lulus kuis</span>
                    <?php endif; ?>
                </div>
            </article>
        </section>

        <aside class="col-lg-4" aria-labelledby="overview-action-heading">
            <div class="overview-enroll-card">
                <?php if ($enrolled): ?>
                    <h2 class="h5 mb-3" id="overview-action-heading"><?= $progress >= 100 ? 'Kelas selesai' : 'Progres belajarmu' ?></h2>
                    <div class="d-flex justify-content-between small mb-2">
                        <span>Progres kelas</span>
                        <strong><?= $progress ?>%</strong>
                    </div>
                    <div class="progress mb-3" role="progressbar" aria-label="Progres kelas" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-success" style="width: <?= $progress ?>%"></div>
                    </div>
                    <a class="btn btn-success w-100 mb-2" href="<?= e($continueUrl) ?>">
                        <i class="bi <?= e($continueIcon) ?>" aria-hidden="true"></i> <?= e($continueLabel) ?>
                    </a>
                    <?php if (($class['status'] ?? '') === 'ditutup'): ?>
                        <p class="small text-secondary mt-3 mb-0">Kelas ditutup. Materi dan hasil belajarmu tetap dapat dilihat dalam mode read-only.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <h2 class="h5 mb-2" id="overview-action-heading"><?= !empty($class['is_public']) ? 'Ikuti kelas publik' : 'Gabung kelas privat' ?></h2>
                    <?php if (!empty($class['is_public'])): ?>
                        <p class="text-secondary">Kelas ini terbuka untuk semua siswa. Kamu bisa langsung bergabung tanpa kode kelas. Modul privat tetap meminta kode modul.</p>
                    <?php else: ?>
                        <p class="text-secondary">Masukkan kode kelas dari instruktur. Modul privat juga dapat memiliki kode akses tersendiri.</p>
                    <?php endif; ?>
                    <form method="POST" action="DashboardController.php">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                        <input type="hidden" name="aksi" value="join_class">
                        <input type="hidden" name="return_class_id" value="<?= (int) $class['id'] ?>">
                        <?php if (empty($class['is_public'])): ?>
                            <label class="form-label" for="overview-enroll-key">Kode enroll kelas</label>
                            <input class="form-control mb-3" id="overview-enroll-key" type="text" name="enroll_key" autocomplete="off" placeholder="Masukkan kode dari instruktur" required>
                        <?php endif; ?>
                        <button class="btn btn-success w-100" type="submit">
                            <?= !empty($class['is_public']) ? 'Gabung kelas' : 'Gabung kelas dan mulai belajar' ?>
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </button>
                    </form>
                <?php endif; ?>
                <hr>
                <ul class="overview-facts">
                    <li><i class="bi bi-clock" aria-hidden="true"></i><span>Durasi estimasi</span><strong><?= e($durationLabel) ?></strong></li>
                    <li><i class="bi bi-bar-chart" aria-hidden="true"></i><span>Tingkat</span><strong><?= e($levelLabel) ?></strong></li>
                    <li><i class="bi bi-journal-check" aria-hidden="true"></i><span>Materi dan kuis</span><strong><?= $totalMaterials ?> materi · <?= $totalQuizzes ?> kuis</strong></li>
                    <li><i class="bi bi-award" aria-hidden="true"></i><span>Lencana</span><strong><?= $badgeEnabled ? '1 pencapaian' : 'Belum ditambahkan' ?></strong></li>
                    <li><i class="bi bi-patch-check" aria-hidden="true"></i><span>Sertifikat</span><strong><?= $certificate !== null ? 'Sudah terbit' : 'Setelah lulus' ?></strong></li>
                </ul>
            </div>
        </aside>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
