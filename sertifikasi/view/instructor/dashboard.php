<?php
$stats = $data['stats'] ?? ['total_kelas' => 0, 'kelas_aktif' => 0, 'kelas_draft' => 0, 'kelas_ditutup' => 0, 'total_siswa' => 0, 'rata_progress' => 0];
$classes = $data['classes'] ?? [];
$selectedClass = $data['selectedClass'] ?? null;
$success = $data['success'] ?? '';
$error = $data['error'] ?? '';
$createMode = $data['createMode'] ?? false;
$search = $data['search'] ?? '';
$tab = $data['tab'] ?? 'ringkasan';
$students = $data['students'] ?? [];
$modules = $data['modules'] ?? [];
$quizzes = $data['quizzes'] ?? [];
$announcements = $data['announcements'] ?? [];
$selectedClassStatus = strtolower((string) ($selectedClass['status'] ?? 'draft'));
$selectedStatusLabel = $selectedClassStatus === 'aktif' ? 'Aktif' : ($selectedClassStatus === 'ditutup' ? 'Ditutup' : 'Draft');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Instruktur | Sertifikasi Academy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../asset/css/theme-soft.css?v=20261103-green2" rel="stylesheet">
    <link href="../../asset/css/instructor.css" rel="stylesheet">
</head>
<body class="instructor-dashboard-page">
    <div class="instructor-layout">
        <aside class="instructor-sidebar">
            <div class="brand-box">
                <div class="brand-mark"><i class="bi bi-mortarboard"></i></div>
                <div class="brand-copy">
                    <span>Sertifikasi</span>
                    <strong>Academy</strong>
                    <small>INSTRUKTUR</small>
                </div>
            </div>

            <div class="sidebar-menu">
                <div class="sidebar-label">Menu utama</div>
                <a href="DashboardController.php" class="sidebar-item <?= empty($selectedClass) && !$createMode ? 'active' : '' ?>">
                    <span class="sidebar-icon"><i class="bi bi-journal-bookmark"></i></span>
                    <span>Kelas Saya</span>
                    <span class="sidebar-badge"><?= count($classes) ?></span>
                </a>
                <a href="DashboardController.php?mode=create" class="sidebar-item <?= $createMode ? 'active' : '' ?>">
                    <span class="sidebar-icon"><i class="bi bi-plus-circle"></i></span>
                    <span>Buat Kelas</span>
                </a>
            </div>

            <?php if (!empty($classes)): ?>
                <div class="sidebar-section">
                    <div class="sidebar-label">Kelas ini</div>
                    <?php foreach ($classes as $item): ?>
                        <?php
                        $itemStatus = strtolower((string) ($item['status'] ?? 'draft'));
                        $itemActive = !empty($selectedClass) && ((int) ($item['id'] ?? 0) === (int) ($selectedClass['id'] ?? 0));
                        ?>
                        <a href="DashboardController.php?class_id=<?= (int) ($item['id'] ?? 0) ?>" class="course-item <?= $itemActive ? 'active' : '' ?>">
                            <span class="course-icon"><i class="bi bi-journal-text"></i></span>
                            <span class="course-text"><?= e($item['judul'] ?? '-') ?></span>
                            <?php if ($itemStatus === 'aktif'): ?>
                                <span class="course-status green"></span>
                            <?php else: ?>
                                <span class="course-status muted"></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="sidebar-footer">
                <div class="mini-profile">
                    <div class="mini-avatar"><?= e(strtoupper(substr($_SESSION['username'] ?? 'I', 0, 1))) ?></div>
                    <div class="mini-meta">
                        <strong><?= e($_SESSION['username'] ?? 'Instruktur') ?></strong>
                        <small>Instruktur</small>
                    </div>
                </div>
                <a href="../../controller/AuthController.php?aksi=logout" class="logout-link">Keluar</a>
            </div>
        </aside>

        <main class="instructor-main">
            <header class="instructor-topbar">
                <div class="instructor-breadcrumbs">
                    <span>Instruktur</span>
                    <span class="slash">/</span>
                    <span>Kelas Saya</span>
                    <?php if (!empty($selectedClass)): ?>
                        <span class="slash">/</span>
                        <strong><?= e($selectedClass['judul'] ?? 'Kelas') ?></strong>
                    <?php endif; ?>
                </div>
                <div class="topbar-user">
                    <span class="topbar-avatar"><?= e(strtoupper(substr($_SESSION['username'] ?? 'I', 0, 1))) ?></span>
                    <span>Instruktur</span>
                    <a href="../../controller/AuthController.php?aksi=logout" class="topbar-logout">Keluar</a>
                </div>
            </header>

            <section class="instructor-panel">
                <?php if ($success !== ''): ?>
                    <div class="alert-inline success"><?= e($success) ?></div>
                <?php endif; ?>
                <?php if ($error !== ''): ?>
                    <div class="alert-inline error"><?= e($error) ?></div>
                <?php endif; ?>

                <?php if ($createMode): ?>
                    <div class="panel-header create-header">
                        <div class="eyebrow">PANEL INSTRUKTUR</div>
                        <div class="panel-title-row">
                            <h1>Buat kelas baru</h1>
                        </div>
                        <p>Isi informasi dasar kelas. Modul, materi, dan kuis bisa ditambahkan setelah kelas dibuat.</p>
                    </div>

                    <section class="create-class-panel">
                        <form method="POST" action="DashboardController.php" enctype="multipart/form-data" class="class-create-form">
                            <input type="hidden" name="aksi" value="save_class">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">

                            <div class="form-grid">
                                <label class="form-field wide">
                                    <span>Judul kelas</span>
                                    <input type="text" name="judul" placeholder="Contoh: Kelas UI/UX Dasar" required>
                                </label>

                                <label class="form-field wide">
                                    <span>Deskripsi kelas</span>
                                    <textarea name="deskripsi" rows="4" placeholder="Jelaskan tujuan, topik, dan target belajar kelas ini." required></textarea>
                                </label>

                                <div class="two-column-field">
                                    <label class="form-field">
                                        <span>Status</span>
                                        <select name="status">
                                            <option value="draft">Draft</option>
                                            <option value="aktif">Aktif</option>
                                            <option value="ditutup">Ditutup</option>
                                        </select>
                                    </label>

                                    <label class="form-field">
                                        <span>Kode enroll</span>
                                        <input type="text" name="enroll_key" placeholder="Kosongkan agar kode otomatis">
                                    </label>
                                </div>

                                <div class="two-column-field">
                                    <label class="form-field">
                                        <span>Ikon kelas</span>
                                        <input type="file" name="class_icon" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                    </label>

                                    <label class="form-field">
                                        <span>Label badge</span>
                                        <input type="text" name="badge_label" placeholder="Misal: Belajar baru">
                                    </label>
                                </div>

                                <label class="form-field checkbox-field wide">
                                    <input type="checkbox" name="badge_aktif" value="1">
                                    <span>Tampilkan badge di kartu kelas</span>
                                </label>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="primary-button">Buat kelas</button>
                                <a href="DashboardController.php" class="secondary-button">Batal</a>
                            </div>
                        </form>
                    </section>
                <?php elseif ($selectedClass): ?>
                    <div class="page-wrap">
                        <div class="page-header-block">
                            <div class="eyebrow">PANEL INSTRUKTUR</div>
                            <h1><?= e($selectedClass['judul'] ?? 'Kelas') ?></h1>
                            <p><?= e($selectedClass['deskripsi'] ?? 'Kelas ini belum memiliki deskripsi.') ?></p>
                            <div class="page-top-actions">
                                <span class="status-chip <?= e($selectedClassStatus) ?>"><?= e($selectedStatusLabel) ?></span>
                                <a class="back-link" href="DashboardController.php">← Semua kelas</a>
                            </div>
                        </div>

                        <nav class="class-tabs" aria-label="Tab kelas">
                            <?php foreach (['ringkasan', 'modul', 'kuis', 'siswa', 'pengumuman', 'pengaturan'] as $navTab): ?>
                                <a href="DashboardController.php?class_id=<?= (int) ($selectedClass['id'] ?? 0) ?>&tab=<?= e($navTab) ?>" class="tab-item <?= $tab === $navTab ? 'active' : '' ?>"><?= e(match ($navTab) {
                                    'ringkasan' => 'Ringkasan',
                                    'modul' => 'Modul & Materi',
                                    'kuis' => 'Kuis',
                                    'siswa' => 'Siswa',
                                    'pengumuman' => 'Pengumuman',
                                    'pengaturan' => 'Pengaturan',
                                }) ?></a>
                            <?php endforeach; ?>
                        </nav>

                        <div class="class-detail-body">
                            <?php if ($tab === 'ringkasan'): ?>
                                <div class="hero-cta-card">
                                    <div class="hero-visual">
                                        <div class="book-mark"><i class="bi bi-journal-text"></i></div>
                                    </div>
                                    <div class="hero-copy">
                                        <h2><?= e($selectedClass['judul'] ?? 'Kelas') ?></h2>
                                        <p><?= e($selectedClass['deskripsi'] ?? 'Belum ada deskripsi kelas.') ?></p>
                                    </div>
                                    <div class="enroll-box">
                                        <span class="label">Kode enroll</span>
                                        <div class="code-row">
                                            <strong><?= e($selectedClass['enroll_key'] ?? '-') ?></strong>
                                            <button type="button" class="tiny-button">Salin</button>
                                        </div>
                                        <small>Bagikan kode ini ke siswa.</small>
                                    </div>
                                </div>

                                <div class="quick-stats">
                                    <article class="mini-stat">
                                        <div class="mini-icon"><i class="bi bi-people"></i></div>
                                        <div>
                                            <strong><?= count($students) ?></strong>
                                            <span>Siswa</span>
                                        </div>
                                    </article>
                                    <article class="mini-stat">
                                        <div class="mini-icon"><i class="bi bi-journal-bookmark"></i></div>
                                        <div>
                                            <strong><?= count($modules) ?></strong>
                                            <span>Modul</span>
                                        </div>
                                    </article>
                                    <article class="mini-stat">
                                        <div class="mini-icon"><i class="bi bi-question-circle"></i></div>
                                        <div>
                                            <strong><?= count($quizzes) ?></strong>
                                            <span>Kuis</span>
                                        </div>
                                    </article>
                                    <article class="mini-stat">
                                        <div class="mini-icon"><i class="bi bi-megaphone"></i></div>
                                        <div>
                                            <strong><?= count($announcements) ?></strong>
                                            <span>Pengumuman</span>
                                        </div>
                                    </article>
                                </div>

                                <div class="summary-grid">
                                    <div class="summary-card">
                                        <h3>Langkah berikutnya</h3>
                                        <ul class="check-list">
                                            <li>Tambahkan modul</li>
                                            <li>Isi modul dengan materi</li>
                                            <li>Buat kuis dan soal</li>
                                            <li>Aktifkan status kelas</li>
                                            <li>Bagikan kode enroll ke siswa</li>
                                        </ul>
                                    </div>
                                    <div class="summary-card">
                                        <h3>Pengumuman terbaru</h3>
                                        <?php if (empty($announcements)): ?>
                                            <p class="empty-copy">Belum ada pengumuman. Buat pengumuman pertama di tab Pengumuman.</p>
                                        <?php else: ?>
                                            <ul class="announcement-list">
                                                <?php foreach (array_slice($announcements, 0, 3) as $announcement): ?>
                                                    <li>
                                                        <strong><?= e($announcement['judul'] ?? 'Pengumuman') ?></strong>
                                                        <small><?= e($announcement['isi'] ?? '') ?></small>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php elseif ($tab === 'modul'): ?>
                                <div class="section-head-row">
                                    <div>
                                        <h3>Modul & materi</h3>
                                        <small>Susun materi per modul. Siswa mengikuti urutan yang tertata.</small>
                                    </div>
                                    <button type="button" class="primary-button compact">+ Tambah modul</button>
                                </div>
                                <?php if (empty($modules)): ?>
                                    <div class="empty-box">Belum ada modul. Tambahkan modul pertama untuk memulai kelas.</div>
                                <?php else: ?>
                                    <?php foreach ($modules as $module): ?>
                                        <div class="module-block">
                                            <div class="module-header">
                                                <div class="module-title-wrap">
                                                    <span class="module-number"><?= e((string) ($module['urutan'] ?? 1)) ?></span>
                                                    <div>
                                                        <strong><?= e($module['judul'] ?? 'Modul baru') ?></strong>
                                                        <small><?= e($module['deskripsi'] ?? '') ?></small>
                                                    </div>
                                                </div>
                                                <div class="module-actions">
                                                    <button type="button" class="icon-button" aria-label="Tambah materi"><i class="bi bi-plus"></i></button>
                                                    <button type="button" class="icon-button" aria-label="Edit modul"><i class="bi bi-pencil"></i></button>
                                                    <button type="button" class="icon-button danger" aria-label="Hapus modul"><i class="bi bi-trash"></i></button>
                                                </div>
                                            </div>
                                            <?php if (empty($module['materi'] ?? [])): ?>
                                                <div class="module-empty">Belum ada materi di modul ini.</div>
                                            <?php else: ?>
                                                <?php foreach ($module['materi'] as $material): ?>
                                                    <div class="material-item">
                                                        <div class="material-icon"><i class="bi bi-file-text"></i></div>
                                                        <div class="material-copy">
                                                            <strong><?= e($material['judul'] ?? 'Materi') ?></strong>
                                                            <small><?= e($material['tipe'] ?? 'teks') ?></small>
                                                        </div>
                                                        <div class="material-actions">
                                                            <button type="button" class="icon-button" aria-label="Edit materi"><i class="bi bi-pencil"></i></button>
                                                            <button type="button" class="icon-button danger" aria-label="Hapus materi"><i class="bi bi-trash"></i></button>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php elseif ($tab === 'kuis'): ?>
                                <div class="section-head-row">
                                    <div>
                                        <h3>Kuis kelas</h3>
                                        <small>Kelola soal pilihan ganda dan nilai minimum.</small>
                                    </div>
                                    <button type="button" class="primary-button compact">+ Buat kuis</button>
                                </div>
                                <?php if (empty($quizzes)): ?>
                                    <div class="empty-box">Belum ada kuis. Tambahkan kuis baru untuk menguji siswa.</div>
                                <?php else: ?>
                                    <?php foreach ($quizzes as $quiz): ?>
                                        <div class="quiz-block">
                                            <div class="quiz-header">
                                                <div>
                                                    <strong><?= e($quiz['judul'] ?? 'Kuis baru') ?></strong>
                                                    <small>Nilai minimum: <?= (int) ($quiz['nilai_minimum'] ?? 70) ?> · <?= (int) ($quiz['waktu_durasi'] ?? 0) ?> menit</small>
                                                </div>
                                                <div class="module-actions">
                                                    <button type="button" class="icon-button" aria-label="Edit kuis"><i class="bi bi-pencil"></i></button>
                                                    <button type="button" class="icon-button danger" aria-label="Hapus kuis"><i class="bi bi-trash"></i></button>
                                                </div>
                                            </div>
                                            <?php if (!empty($quiz['soal'])): ?>
                                                <?php foreach ($quiz['soal'] as $question): ?>
                                                    <div class="question-item">
                                                        <div class="question-no"><?= e((string) ($question['nomor'] ?? '1')) ?></div>
                                                        <div class="question-copy">
                                                            <strong><?= e($question['teks'] ?? 'Pertanyaan') ?></strong>
                                                            <small>Jawaban benar: <?= (int) ($question['jawaban_benar'] ?? 0) ?></small>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php elseif ($tab === 'siswa'): ?>
                                <div class="section-head-row">
                                    <div>
                                        <h3>Peserta kelas</h3>
                                        <small>Periksa progres dan status keaktifan siswa.</small>
                                    </div>
                                </div>
                                <?php if (empty($students)): ?>
                                    <div class="empty-box">Belum ada siswa yang bergabung ke kelas ini.</div>
                                <?php else: ?>
                                    <div class="student-table-wrap">
                                        <table class="student-table">
                                            <thead>
                                                <tr>
                                                    <th>Siswa</th>
                                                    <th>Progress</th>
                                                    <th>Terakhir aktif</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($students as $student): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="student-cell">
                                                                <span class="student-avatar"><?= e(strtoupper(substr((string) ($student['nama'] ?? 'S'), 0, 1))) ?></span>
                                                                <div>
                                                                    <strong><?= e($student['nama'] ?? 'Siswa') ?></strong>
                                                                    <small><?= e($student['email'] ?? '') ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="mini-progress">
                                                                <span class="js-progress-bar" data-progress="<?= min(max((int) ($student['progress'] ?? 0), 0), 100) ?>"></span>
                                                            </div>
                                                            <small><?= (int) ($student['progress'] ?? 0) ?>%</small>
                                                        </td>
                                                        <td><?= e($student['terakhir_aktif'] ?? '-') ?></td>
                                                        <td><button type="button" class="danger-link">Keluarkan</button></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            <?php elseif ($tab === 'pengumuman'): ?>
                                <div class="section-head-row">
                                    <div>
                                        <h3>Pengumuman kelas</h3>
                                        <small>Bagikan informasi penting untuk kelas.</small>
                                    </div>
                                    <button type="button" class="primary-button compact">+ Buat pengumuman</button>
                                </div>
                                <?php if (empty($announcements)): ?>
                                    <div class="empty-box">Belum ada pengumuman. Buat pengumuman pertama untuk siswa.</div>
                                <?php else: ?>
                                    <?php foreach ($announcements as $announcement): ?>
                                        <div class="announcement-card">
                                            <div class="announcement-header">
                                                <strong><?= e($announcement['judul'] ?? 'Pengumuman') ?></strong>
                                                <span><?= e($announcement['dibuat_pada'] ?? '') ?></span>
                                            </div>
                                            <p><?= e($announcement['isi'] ?? '') ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="section-head-row">
                                    <div>
                                        <h3>Pengaturan kelas</h3>
                                        <small>Kelola status, ikon, kode enroll, dan tampilan kelas.</small>
                                    </div>
                                </div>
                                <div class="setting-form-card">
                                    <form method="POST" action="DashboardController.php" enctype="multipart/form-data">
                                        <input type="hidden" name="aksi" value="save_class">
                                        <input type="hidden" name="class_id" value="<?= (int) ($selectedClass['id'] ?? 0) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">

                                        <div class="setting-grid">
                                            <label class="form-field">
                                                <span>Judul kelas</span>
                                                <input type="text" name="judul" value="<?= e($selectedClass['judul'] ?? '') ?>">
                                            </label>
                                            <label class="form-field">
                                                <span>Status</span>
                                                <select name="status">
                                                    <option value="draft" <?= ($selectedClassStatus === 'draft') ? 'selected' : '' ?>>Draft</option>
                                                    <option value="aktif" <?= ($selectedClassStatus === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                                                    <option value="ditutup" <?= ($selectedClassStatus === 'ditutup') ? 'selected' : '' ?>>Ditutup</option>
                                                </select>
                                            </label>
                                            <label class="form-field wide">
                                                <span>Deskripsi</span>
                                                <textarea name="deskripsi" rows="4"><?= e($selectedClass['deskripsi'] ?? '') ?></textarea>
                                            </label>
                                            <label class="form-field">
                                                <span>Kode enroll</span>
                                                <input type="text" name="enroll_key" value="<?= e($selectedClass['enroll_key'] ?? '') ?>">
                                            </label>
                                            <label class="form-field">
                                                <span>Ikon kelas</span>
                                                <input type="file" name="class_icon" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                            </label>
                                        </div>
                                        <div class="form-actions">
                                            <button type="submit" class="primary-button">Simpan perubahan</button>
                                            <button type="button" class="secondary-button">Batal</button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="panel-header">
                        <div class="eyebrow">PANEL INSTRUKTUR</div>
                        <div class="panel-title-row">
                            <h1>Kelas saya</h1>
                            <span class="panel-date">Selasa, 3 November 2026</span>
                        </div>
                        <p>Buat kelas, kelola materi, dan pantau progress peserta dari satu tempat.</p>
                    </div>

                    <section class="stats-grid" aria-label="Ringkasan kelas">
                        <article class="stat-card">
                            <div class="stat-label">Total kelas</div>
                            <div class="stat-body">
                                <div class="stat-icon"><i class="bi bi-journal-bookmark"></i></div>
                                <div class="stat-value"><?= (int) ($stats['total_kelas'] ?? 0) ?></div>
                            </div>
                            <div class="stat-foot">Semua kelas yang kamu buat</div>
                        </article>

                        <article class="stat-card">
                            <div class="stat-label">Total siswa</div>
                            <div class="stat-body">
                                <div class="stat-icon"><i class="bi bi-people"></i></div>
                                <div class="stat-value"><?= (int) ($stats['total_siswa'] ?? 0) ?></div>
                            </div>
                            <div class="stat-foot">Peserta di seluruh kelas</div>
                        </article>

                        <article class="stat-card">
                            <div class="stat-label">Progress rata-rata</div>
                            <div class="stat-body">
                                <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
                                <div class="stat-value"><?= (int) ($stats['rata_progress'] ?? 0) ?>%</div>
                            </div>
                            <div class="stat-foot">Rata-rata semua peserta</div>
                        </article>

                        <article class="stat-card">
                            <div class="stat-label">Kelas aktif</div>
                            <div class="stat-body">
                                <div class="stat-icon"><i class="bi bi-play-circle"></i></div>
                                <div class="stat-value"><?= (int) ($stats['kelas_aktif'] ?? 0) ?></div>
                            </div>
                            <div class="stat-foot"><?= (int) ($stats['kelas_aktif'] ?? 0) ?> aktif / draft / ditutup</div>
                        </article>
                    </section>

                    <section class="classes-panel">
                        <div class="classes-toolbar">
                            <h2>Daftar kelas</h2>
                            <a href="DashboardController.php?mode=create" class="primary-button">+ Buat kelas</a>
                        </div>

                        <div class="filter-row">
                            <div class="search-field">
                                <i class="bi bi-search"></i>
                                <input type="search" value="<?= e($search) ?>" placeholder="Cari judul, kelas, atau kode enroll...">
                            </div>
                            <select class="filter-select" aria-label="Filter kelas">
                                <option>Semua status</option>
                                <option>Aktif</option>
                                <option>Draft</option>
                                <option>Ditutup</option>
                            </select>
                            <button class="filter-button" type="button">Filter</button>
                        </div>

                        <?php if (empty($classes)): ?>
                            <div class="empty-state">Belum ada kelas yang dibuat. Klik tombol buat kelas untuk mulai.</div>
                        <?php else: ?>
                            <div class="classes-table-wrap">
                                <table class="classes-table">
                                    <thead>
                                        <tr>
                                            <th>Kelas</th>
                                            <th>Status</th>
                                            <th>Kode enroll</th>
                                            <th>Siswa</th>
                                            <th>Progress</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($classes as $class): ?>
                                            <?php
                                            $classStatus = strtolower((string) ($class['status'] ?? 'draft'));
                                            $progress = (int) ($class['avg_progress'] ?? 0);
                                            $label = match ($classStatus) {
                                                'aktif' => 'Aktif',
                                                'ditutup' => 'Ditutup',
                                                default => 'Draft',
                                            };
                                            $statusClass = 'status-' . $classStatus;
                                            ?>
                                            <tr>
                                                <td class="class-cell">
                                                    <div class="class-thumb">
                                                        <?php if (!empty($class['icon_url'])): ?>
                                                            <img src="<?= e($class['icon_url']) ?>" alt="Icon kelas <?= e($class['judul'] ?? '') ?>">
                                                        <?php else: ?>
                                                            <i class="bi bi-journal-text"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="class-name-wrap">
                                                        <strong><?= e($class['judul'] ?? '-') ?></strong>
                                                        <small><?= e($class['deskripsi'] ?? 'Belum ada deskripsi kelas.') ?></small>
                                                    </div>
                                                </td>
                                                <td><span class="status-badge <?= e($statusClass) ?>"><?= e($label) ?></span></td>
                                                <td><code><?= e($class['enroll_key'] ?? '-') ?></code></td>
                                                <td><?= (int) ($class['total_siswa'] ?? 0) ?></td>
                                                <td>
                                                    <div class="progress-mini" aria-label="Progress kelas">
                                                        <span class="js-progress-bar" data-progress="<?= min(max($progress, 0), 100) ?>"></span>
                                                    </div>
                                                    <small><?= $progress ?>%</small>
                                                </td>
                                                <td class="actions-cell">
                                                    <a href="DashboardController.php?class_id=<?= (int) ($class['id'] ?? 0) ?>" class="action-btn">Kelola</a>
                                                    <button type="button" class="action-btn danger" data-confirm="Hapus kelas ini?">Hapus</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script src="../../asset/js/instructor.js"></script>
</body>
</html>
