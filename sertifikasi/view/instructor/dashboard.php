<?php
$stats = $data['stats'] ?? ['total_kelas' => 0, 'kelas_aktif' => 0, 'kelas_draft' => 0, 'kelas_ditutup' => 0, 'total_siswa' => 0, 'rata_progress' => 0];
$classes = $data['classes'] ?? [];
$selectedClass = $data['selectedClass'] ?? null;
$students = $data['students'] ?? [];
$modules = $data['modules'] ?? [];
$quizzes = $data['quizzes'] ?? [];
$editModule = $data['editModule'] ?? null;
$editMaterial = $data['editMaterial'] ?? null;
$editQuiz = $data['editQuiz'] ?? null;
$search = $data['search'] ?? '';
$success = $data['success'] ?? '';
$error = $data['error'] ?? '';
$createMode = $data['createMode'] ?? false;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Instruktur | Sertifikasi Academy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../asset/css/theme-soft.css" rel="stylesheet">
    <link href="../../asset/css/admin.css" rel="stylesheet">
    <link href="../../asset/css/instructor.css" rel="stylesheet">

</head>
<body class="soft-dashboard admin-body instructor-body">
<nav class="navbar navbar-expand-lg admin-navbar sticky-top">
    <div class="container-fluid instructor-container">
        <a class="navbar-brand admin-brand" href="DashboardController.php">
            <span class="admin-brand-mark"><i class="bi bi-mortarboard"></i></span>
            <span>Sertifikasi <strong>Academy</strong><small>INSTRUKTUR</small></span>
        </a>
        <div class="ms-auto d-flex align-items-center gap-2 gap-md-3">
            <a class="btn btn-admin-quiet btn-sm" href="../../controller/user/ProfileController.php?tab=profil"><i class="bi bi-person-gear me-1"></i><span class="d-none d-sm-inline">Profil &amp; password</span></a>
            <a class="btn btn-admin-logout btn-sm" href="../../controller/AuthController.php?aksi=logout"><i class="bi bi-box-arrow-right me-1"></i><span class="d-none d-sm-inline">Keluar</span></a>
        </div>
    </div>
</nav>

<main class="container-fluid instructor-container py-4 py-lg-5 instructor-shell">
    <header class="admin-page-heading">
        <div>
            <div class="admin-eyebrow"><i class="bi bi-journal-check me-1"></i> Dashboard instruktur</div>
            <h1>Kelola kelas & siswa</h1>
            <p>Buat kelas, kelola materi, dan pantau progress peserta dari satu tempat.</p>
        </div>
    </header>

    <section class="row g-3 mb-4" aria-label="Ringkasan kelas">
        <div class="col-6 col-md-4 col-xl-3">
            <div class="admin-stat-card">
                <div>
                    <div class="admin-stat-label">Total kelas</div>
                    <div class="admin-stat-value"><?= (int) ($stats['total_kelas'] ?? 0) ?></div>
                </div>
                <span class="admin-stat-icon"><i class="bi bi-journal-bookmark"></i></span>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="admin-stat-card">
                <div>
                    <div class="admin-stat-label">Total siswa</div>
                    <div class="admin-stat-value"><?= (int) ($stats['total_siswa'] ?? 0) ?></div>
                </div>
                <span class="admin-stat-icon"><i class="bi bi-people"></i></span>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="admin-stat-card">
                <div>
                    <div class="admin-stat-label">Progress rata-rata</div>
                    <div class="admin-stat-value"><?= (int) ($stats['rata_progress'] ?? 0) ?>%</div>
                </div>
                <span class="admin-stat-icon"><i class="bi bi-graph-up-arrow"></i></span>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="admin-stat-card">
                <div>
                    <div class="admin-stat-label">Kelas aktif</div>
                    <div class="admin-stat-value"><?= (int) ($stats['kelas_aktif'] ?? 0) ?></div>
                </div>
                <span class="admin-stat-icon"><i class="bi bi-play-circle"></i></span>
            </div>
        </div>
    </section>

    <?php if ($success !== ''): ?>
        <div class="alert admin-alert admin-alert-success"><i class="bi bi-check-circle-fill"></i><span><?= e($success) ?></span></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert admin-alert admin-alert-error"><i class="bi bi-exclamation-circle-fill"></i><span><?= e($error) ?></span></div>
    <?php endif; ?>

    <?php if ($createMode): ?>
        <section class="instructor-section">
            <div class="instructor-actions">
                <div>
                    <h2 class="h4 mb-0">Buat kelas baru</h2>
                </div>
                <a href="DashboardController.php" class="btn btn-admin-quiet">Kembali</a>
            </div>

            <div class="instructor-grid mt-3">
                <form method="POST" action="DashboardController.php" enctype="multipart/form-data" class="instructor-form-card instructor-card instructor-compact">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="aksi" value="save_class">
                    <input type="hidden" name="class_id" value="<?= e((string) ($selectedClass['id'] ?? '')) ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul kelas</label>
                        <input type="text" name="judul" class="form-control" value="<?= e($selectedClass['judul'] ?? '') ?>" placeholder="Contoh: UI/UX Fundamentals" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi kelas</label>
                        <textarea name="deskripsi" class="form-control" rows="4" placeholder="Jelaskan tujuan kelas, materi, dan manfaat untuk siswa." required><?= e($selectedClass['deskripsi'] ?? '') ?></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft" <?= (($selectedClass['status'] ?? 'draft') === 'draft') ? 'selected' : '' ?>>Draft</option>
                                <option value="aktif" <?= (($selectedClass['status'] ?? 'draft') === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                                <option value="ditutup" <?= (($selectedClass['status'] ?? 'draft') === 'ditutup') ? 'selected' : '' ?>>Ditutup</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Enroll key</label>
                            <input type="text" name="enroll_key" class="form-control" value="<?= e($selectedClass['enroll_key'] ?? '') ?>" placeholder="CLASS-ABC123">
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Icon kelas</label>
                            <input type="file" name="class_icon" accept="image/*" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Badge</label>
                            <input type="text" name="badge_label" class="form-control" value="<?= e($selectedClass['badge_label'] ?? 'New') ?>" placeholder="New / Beginner">
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-admin-filter"><?= $selectedClass ? 'Simpan perubahan' : 'Buat kelas' ?></button>
                        <a href="DashboardController.php" class="btn btn-admin-quiet">Batal</a>
                    </div>
                </form>

                <aside class="instructor-detail-box instructor-card instructor-compact">
                    <div class="instructor-ribbon"><i class="bi bi-eye"></i> Preview overview</div>
                    <div class="class-preview-card">
                        <div class="class-preview-thumb"><i class="bi bi-journal-bookmark"></i></div>
                        <div class="class-preview-meta">
                            <span class="class-badge">New</span>
                            <div class="class-preview-title">Judul kelas akan muncul di sini</div>
                            <div class="class-preview-desc">Deskripsi kelas otomatis tampil di dashboard siswa dan overview kelas.</div>
                        </div>
                    </div>
                    <div class="summary-list">
                        <div class="summary-item"><span>Enroll key</span><strong>CLASS-ABC123</strong></div>
                        <div class="summary-item"><span>Status</span><strong>Draft</strong></div>
                        <div class="summary-item"><span>Modul</span><strong>Belum dibuat</strong></div>
                    </div>
                </aside>
            </div>
        </section>
    <?php else: ?>
        <section class="instructor-section">
            <div class="instructor-actions">
                <div>
                    <h2 class="h4 mb-0">Kelola kelas</h2>
                </div>
                <div class="d-flex gap-2">
                    <a href="DashboardController.php?mode=create" class="btn btn-admin-filter">Buat kelas</a>
                </div>
            </div>

            <div class="simple-cta mt-3">
                <div>
                    <div class="admin-eyebrow">Mulai</div>
                    <h3 class="h5 mb-1">Buat kelas baru dari sini</h3>
                    <p class="mb-0 text-muted">Setelah kelas dibuat, kamu bisa lanjut menambahkan modul, materi, dan kuis.</p>
                </div>
                <a href="DashboardController.php?mode=create" class="btn btn-admin-filter">+ Buat kelas</a>
            </div>
        </section>
    <?php endif; ?>

        <section class="instructor-section mt-4">
            <div class="instructor-actions">
                <div>
                    <h2 class="h4 mb-0">Daftar kelas</h2>
                </div>
                <span class="text-muted small"><?= count($classes) ?> kelas</span>
            </div>

            <?php if (empty($classes)): ?>
                <p class="text-muted mb-0">Belum ada kelas yang cocok dengan pencarian.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Status</th>
                                <th>Enroll key</th>
                                <th>Siswa</th>
                                <th>Progress</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classes as $class): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e($class['judul']) ?></div>
                                        <small class="text-muted"><?= e($class['slug']) ?></small>
                                    </td>
                                    <td><span class="badge-status status-<?= e($class['status']) ?>"><?= e(ucfirst($class['status'])) ?></span></td>
                                    <td><code><?= e($class['enroll_key']) ?></code></td>
                                    <td><?= (int) ($class['total_siswa'] ?? 0) ?></td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar js-progress-bar" data-progress="<?= (int) ($class['avg_progress'] ?? 0) ?>"></div>
                                        </div>
                                        <small><?= (int) ($class['avg_progress'] ?? 0) ?>%</small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="DashboardController.php?class_id=<?= (int) $class['id'] ?>" class="btn btn-sm btn-outline-soft">Lihat</a>
                                            <form method="POST" action="DashboardController.php">
                                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                                                <input type="hidden" name="aksi" value="delete_class">
                                                <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger js-confirm" data-confirm="Hapus kelas ini?">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>
<script src="../../asset/js/instructor.js" defer></script>
</body>
</html>
