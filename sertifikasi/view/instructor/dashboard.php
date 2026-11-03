<?php
$stats = $data['stats'] ?? ['total_kelas' => 0, 'kelas_aktif' => 0, 'kelas_draft' => 0, 'kelas_ditutup' => 0, 'total_siswa' => 0, 'rata_progress' => 0];
$classes = $data['classes'] ?? [];
$monthlyRegistrations = $data['monthlyRegistrations'] ?? [];
$unansweredCount = $data['unansweredCount'] ?? null;
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
<html lang="id" class="instructor-dashboard">
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
    <link href="../../asset/css/instructor-tools.css" rel="stylesheet">
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
                <a href="DashboardController.php?page=kelas" class="sidebar-item <?= empty($selectedClass) && !$createMode ? 'active' : '' ?>">
                    <span class="sidebar-icon"><i class="bi bi-journal-bookmark"></i></span>
                    <span>Kelas Saya</span>
                </a>
                <a href="DashboardController.php?page=bank_materi" class="sidebar-item">
                    <span class="sidebar-icon"><i class="bi bi-collection"></i></span>
                    <span>Bank Materi</span>
                </a>
                <a href="CertificateController.php?page=sertifikat" class="sidebar-item">
                    <span class="sidebar-icon"><i class="bi bi-patch-check"></i></span>
                    <span>Kelola Sertifikat</span>
                </a>
                <a href="ForumController.php?page=forum" class="sidebar-item">
                    <span class="sidebar-icon"><i class="bi bi-chat-dots"></i></span>
                    <span>Forum Q&amp;A</span>
                </a>
                <a href="ReportsController.php?page=laporan" class="sidebar-item">
                    <span class="sidebar-icon"><i class="bi bi-bar-chart"></i></span>
                    <span>Laporan</span>
                </a>
            </div>

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
                                <a href="DashboardController.php?page=kelas" class="secondary-button">Batal</a>
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
                                <a class="back-link" href="DashboardController.php?page=kelas">← Semua kelas</a>
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
                                                <tr data-class-row="<?= (int) ($class['id'] ?? 0) ?>">
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
                            <span class="panel-date"><?= e(tgl_indo(date('Y-m-d'))) ?></span>
                        </div>
                        <p>Pantau pertumbuhan peserta dan kelola kelas Anda dari satu tempat.</p>
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
                            <div class="stat-label">Kelas aktif</div>
                            <div class="stat-body">
                                <div class="stat-icon"><i class="bi bi-play-circle"></i></div>
                                <div class="stat-value"><?= (int) ($stats['kelas_aktif'] ?? 0) ?></div>
                            </div>
                            <div class="stat-foot">Kelas dengan status publish</div>
                        </article>

                        <article class="stat-card">
                            <div class="stat-label">Pertanyaan unread</div>
                            <div class="stat-body">
                                <div class="stat-icon"><i class="bi bi-chat-dots"></i></div>
                                <div class="stat-value"><?= $unansweredCount === null ? '—' : (int) $unansweredCount ?></div>
                            </div>
                            <div class="stat-foot"><?= $unansweredCount === null ? 'Jalankan migrasi forum untuk mengaktifkan' : 'Pertanyaan yang menunggu jawaban' ?></div>
                        </article>
                    </section>

                    <section class="analytics-card" aria-labelledby="registration-chart-title">
                        <div class="analytics-card-heading">
                            <div>
                                <h2 id="registration-chart-title">Pendaftaran siswa</h2>
                                <p>Jumlah peserta baru selama enam bulan terakhir.</p>
                            </div>
                            <span class="analytics-period">6 bulan terakhir</span>
                        </div>
                        <div class="registration-chart-wrap">
                            <canvas
                                id="registration-chart"
                                role="img"
                                aria-label="Grafik jumlah siswa yang mendaftar per bulan"
                                data-labels="<?= e((string) json_encode(array_column($monthlyRegistrations, 'label'), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>"
                                data-values="<?= e((string) json_encode(array_column($monthlyRegistrations, 'total'), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>"
                            ></canvas>
                        </div>
                    </section>

                    <section class="classes-panel">
                        <div class="classes-toolbar">
                            <h2>Daftar kelas</h2>
                            <button type="button" class="primary-button" data-bs-toggle="modal" data-bs-target="#classModal">+ Buat Kelas Baru</button>
                        </div>

                        <form method="get" action="DashboardController.php" class="filter-row">
                            <div class="search-field">
                                <i class="bi bi-search"></i>
                                <input type="search" name="q" value="<?= e($search) ?>" placeholder="Cari judul, kelas, atau kode enroll..." aria-label="Cari kelas">
                            </div>
                            <button class="filter-button" type="submit">Cari kelas</button>
                        </form>

                        <?php if (empty($classes)): ?>
                            <div class="empty-state">Belum ada kelas yang dibuat. Klik tombol buat kelas untuk mulai.</div>
                        <?php else: ?>
                            <div class="classes-table-wrap">
                                <table class="classes-table">
                                    <thead>
                                        <tr>
                                            <th>Kelas</th>
                                            <th>Kategori</th>
                                            <th>Jumlah siswa</th>
                                            <th>Status</th>
                                            <th>Tanggal dibuat</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($classes as $class): ?>
                                            <?php
                                            $classStatus = strtolower((string) ($class['status'] ?? 'draft'));
                                            $label = match ($classStatus) {
                                                'aktif' => 'Publish',
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
                                                <td><?= e($class['kategori'] ?? 'Umum') ?></td>
                                                <td><?= (int) ($class['total_siswa'] ?? 0) ?></td>
                                                <td><span class="status-badge <?= e($statusClass) ?>"><?= e($label) ?></span></td>
                                                <td><?= e(tgl_indo((string) ($class['dibuat_pada'] ?? ''))) ?></td>
                                                <td class="actions-cell">
                                                    <button type="button" class="action-btn js-manage-class" data-class-id="<?= (int) ($class['id'] ?? 0) ?>" data-bs-toggle="modal" data-bs-target="#manageClassModal">Kelola</button>
                                                    <button type="button" class="action-btn js-edit-class" data-class-id="<?= (int) ($class['id'] ?? 0) ?>" data-bs-toggle="modal" data-bs-target="#classModal">Edit</button>
                                                    <button type="button" class="action-btn danger js-delete-class" data-class-id="<?= (int) ($class['id'] ?? 0) ?>" data-class-title="<?= e($class['judul'] ?? '') ?>">Hapus</button>
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

    <div class="modal fade" id="classModal" tabindex="-1" aria-labelledby="class-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered class-modal-dialog">
            <div class="modal-content instructor-modal">
                <form id="class-form" action="DashboardController.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="aksi" value="save_class">
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="class_id" value="">
                    <input type="hidden" name="icon_url" value="">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">

                    <div class="modal-header">
                        <div>
                            <span class="eyebrow">MANAJEMEN KELAS</span>
                            <h2 class="modal-title" id="class-modal-title">Buat kelas baru</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div id="class-form-feedback" class="alert alert-danger d-none" role="alert"></div>
                        <label class="form-field mb-3">
                            <span>Judul kelas</span>
                            <input class="form-control" type="text" name="judul" maxlength="150" placeholder="Contoh: Dasar UI/UX" required>
                        </label>
                        <label class="form-field mb-3">
                            <span>Kategori kelas</span>
                            <select class="form-select" name="kategori" required>
                                <option value="Umum">Umum</option>
                                <option value="Teknologi">Teknologi</option>
                                <option value="Bisnis">Bisnis</option>
                                <option value="Desain">Desain</option>
                                <option value="Data">Data</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </label>
                        <label class="form-field mb-3">
                            <span>Deskripsi singkat</span>
                            <textarea class="form-control" name="deskripsi" rows="4" placeholder="Jelaskan topik dan tujuan kelas." required></textarea>
                        </label>
                        <label class="form-field mb-3">
                            <span>Thumbnail / cover kelas</span>
                            <input class="form-control" type="file" name="class_icon" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            <small id="current-class-cover" class="text-secondary">JPG, PNG, atau WebP, maksimal 2 MB. Opsional.</small>
                        </label>
                        <fieldset class="form-field">
                            <legend class="status-legend">Status kelas</legend>
                            <div class="class-status-options">
                                <label><input type="radio" name="status" value="draft" required> Draft</label>
                                <label><input type="radio" name="status" value="aktif"> Publish</label>
                                <label><input type="radio" name="status" value="ditutup"> Ditutup</label>
                            </div>
                        </fieldset>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="secondary-button" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="primary-button" id="class-submit">
                            <span class="js-submit-label">Simpan</span>
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="manageClassModal" tabindex="-1" aria-labelledby="manage-class-title" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content instructor-modal manage-class-modal">
                <div class="modal-header">
                    <div>
                        <span class="eyebrow">PENGELOLAAN KELAS</span>
                        <h2 class="modal-title" id="manage-class-title">Kelola kelas</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div id="manage-class-feedback" class="alert d-none" role="alert"></div>
                    <ul class="nav nav-tabs manage-tabs" role="tablist">
                        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#manage-materials" type="button" role="tab">Materi</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#manage-quizzes" type="button" role="tab">Kuis &amp; Tugas</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#manage-students" type="button" role="tab">Daftar Siswa</button></li>
                    </ul>
                    <div class="tab-content pt-4">
                        <section class="tab-pane fade show active" id="manage-materials" role="tabpanel">
                            <div class="manage-grid">
                                <form class="manage-form-card js-manage-form" data-action="save_module">
                                    <h3>Tambah modul</h3>
                                    <input type="hidden" name="module_id" value="">
                                    <label class="form-field mb-3"><span>Nama modul</span><input class="form-control" name="module_judul" maxlength="150" required></label>
                                    <label class="form-field mb-3"><span>Deskripsi</span><textarea class="form-control" name="module_deskripsi" rows="3"></textarea></label>
                                    <button class="primary-button" type="submit">Simpan modul</button>
                                </form>
                                <form class="manage-form-card js-manage-form" data-action="save_material" enctype="multipart/form-data">
                                    <h3>Tambah video / dokumen</h3>
                                    <label class="form-field mb-3"><span>Modul</span><select class="form-select" name="module_id" data-module-select required><option value="">Pilih modul</option></select></label>
                                    <label class="form-field mb-3"><span>Judul materi</span><input class="form-control" name="material_judul" maxlength="150" required></label>
                                    <label class="form-field mb-3"><span>Jenis</span><select class="form-select" name="material_tipe"><option value="video">Video</option><option value="dokumen">Dokumen</option><option value="teks">Teks / tautan</option></select></label>
                                    <label class="form-field mb-3"><span>Isi atau tautan (untuk teks)</span><textarea class="form-control" name="material_isi" rows="2"></textarea></label>
                                    <label class="form-field mb-3"><span>File (PDF, DOCX, PPTX, MP4, WebM; maks. 25 MB)</span><input class="form-control" type="file" name="material_file" accept=".pdf,.docx,.pptx,.mp4,.webm,application/pdf,video/mp4,video/webm"></label>
                                    <button class="primary-button" type="submit">Simpan materi</button>
                                </form>
                            </div>
                            <div id="manage-module-list" class="manage-list mt-4"><p class="text-secondary">Memuat daftar materi...</p></div>
                        </section>
                        <section class="tab-pane fade" id="manage-quizzes" role="tabpanel">
                            <div class="manage-grid">
                                <form class="manage-form-card" id="manage-quiz-form">
                                    <h3>Buat kuis pilihan ganda</h3>
                                    <label class="form-field mb-3"><span>Judul kuis</span><input class="form-control" name="quiz_judul" maxlength="150" required></label>
                                    <label class="form-field mb-3"><span>Pertanyaan pertama</span><textarea class="form-control" name="question_text" rows="2" required></textarea></label>
                                    <div class="manage-quiz-options">
                                        <?php for ($optionIndex = 0; $optionIndex < 4; $optionIndex++): ?>
                                            <label class="form-field mb-3"><span>Opsi <?= $optionIndex + 1 ?></span><input class="form-control" name="option_<?= $optionIndex ?>" required></label>
                                        <?php endfor; ?>
                                    </div>
                                    <label class="form-field mb-3"><span>Jawaban benar</span><select class="form-select" name="jawaban_benar"><option value="0">Opsi 1</option><option value="1">Opsi 2</option><option value="2">Opsi 3</option><option value="3">Opsi 4</option></select></label>
                                    <button class="primary-button" type="submit">Simpan kuis &amp; soal</button>
                                </form>
                                <form class="manage-form-card js-manage-form" data-action="save_assignment">
                                    <h3>Buat tugas unggah file</h3>
                                    <label class="form-field mb-3"><span>Judul tugas</span><input class="form-control" name="assignment_title" maxlength="180" required></label>
                                    <label class="form-field mb-3"><span>Instruksi pengumpulan</span><textarea class="form-control" name="assignment_description" rows="4" required></textarea></label>
                                    <label class="form-field mb-3"><span>Tenggat (opsional)</span><input class="form-control" type="datetime-local" name="assignment_due_at"></label>
                                    <p class="small text-secondary">Tugas dapat dikumpulkan siswa sebagai file melalui halaman kelas.</p>
                                    <button class="primary-button" type="submit">Simpan tugas</button>
                                </form>
                            </div>
                            <div id="manage-quiz-list" class="manage-list mt-4"><p class="text-secondary">Memuat kuis dan tugas...</p></div>
                        </section>
                        <section class="tab-pane fade" id="manage-students" role="tabpanel">
                            <div id="manage-student-list" class="student-table-wrap"><p class="p-3 text-secondary">Memuat daftar siswa...</p></div>
                        </section>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="secondary-button" data-bs-dismiss="modal">Tutup</button></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="../../asset/js/instructor.js"></script>
</body>
</html>
