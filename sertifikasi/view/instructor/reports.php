<?php
$report = $data['report'];
$metrics = $report['metrics'] ?? null;
$students = $report['students'] ?? [];
$chartStudents = array_slice($students, 0, 20);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan &amp; Analitik | Sertifikasi Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../asset/css/theme-soft.css" rel="stylesheet">
    <link href="../../asset/css/instructor.css" rel="stylesheet">
    <link href="../../asset/css/instructor-tools.css" rel="stylesheet">
</head>
<body class="instructor-dashboard-page">
<div class="instructor-layout">
    <aside class="instructor-sidebar">
        <div class="brand-box"><div class="brand-mark"><i class="bi bi-mortarboard"></i></div><div class="brand-copy"><span>Sertifikasi</span><strong>Academy</strong><small>INSTRUKTUR</small></div></div>
        <div class="sidebar-menu">
            <div class="sidebar-label">Menu utama</div>
            <a href="DashboardController.php?page=kelas" class="sidebar-item"><span class="sidebar-icon"><i class="bi bi-journal-bookmark"></i></span><span>Kelas Saya</span></a>
            <a href="DashboardController.php?page=bank_materi" class="sidebar-item"><span class="sidebar-icon"><i class="bi bi-collection"></i></span><span>Bank Materi</span></a>
            <a href="ForumController.php?page=forum" class="sidebar-item"><span class="sidebar-icon"><i class="bi bi-chat-dots"></i></span><span>Forum Q&amp;A</span></a>
            <a href="ReportsController.php?page=laporan" class="sidebar-item active"><span class="sidebar-icon"><i class="bi bi-bar-chart"></i></span><span>Laporan</span></a>
        </div>
        <div class="sidebar-footer"><div class="mini-profile"><div class="mini-avatar"><?= e(strtoupper(substr($data['username'], 0, 1))) ?></div><div class="mini-meta"><strong><?= e($data['username']) ?></strong><small>Instruktur</small></div></div><a href="../../controller/AuthController.php?aksi=logout" class="logout-link">Keluar</a></div>
    </aside>
    <main class="instructor-main">
        <header class="instructor-topbar"><div class="instructor-breadcrumbs"><a href="DashboardController.php">Instruktur</a><span class="slash">/</span><strong>Laporan &amp; Analitik</strong></div><div class="topbar-user"><span class="topbar-avatar"><?= e(strtoupper(substr($data['username'], 0, 1))) ?></span><span>Instruktur</span></div></header>
        <section class="instructor-panel">
            <div class="panel-header">
                <div class="eyebrow">PANEL INSTRUKTUR</div>
                <div class="panel-title-row"><h1>Laporan &amp; Analitik</h1></div>
                <p>Ringkasan progres materi, hasil kuis, kelulusan, dan sertifikat peserta.</p>
            </div>

            <?php if ($data['error'] !== ''): ?><div class="alert alert-danger" role="alert"><?= e($data['error']) ?></div><?php endif; ?>

            <form class="manage-form-card mb-4" method="get" action="ReportsController.php" data-preserve-dashboard-scroll>
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-field"><span>Pilih kelas</span>
                            <select class="form-select" name="class_id" required>
                                <option value="">Pilih kelas...</option>
                                <?php foreach ($data['classes'] as $class): ?>
                                    <option value="<?= (int) $class['id'] ?>" <?= $data['classId'] === (int) $class['id'] ? 'selected' : '' ?>><?= e($class['judul']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <div class="col-md-3"><label class="form-field"><span>Dari tanggal</span><input class="form-control" type="date" name="start_date" value="<?= e($data['startDate']) ?>" required></label></div>
                    <div class="col-md-3"><label class="form-field"><span>Sampai tanggal</span><input class="form-control" type="date" name="end_date" value="<?= e($data['endDate']) ?>" required></label></div>
                    <div class="col-md-2 d-grid"><button class="primary-button" type="submit"><i class="bi bi-funnel me-1"></i>Tampilkan</button></div>
                </div>
            </form>

            <?php if ($report !== null): ?>
                <div class="classes-toolbar mb-3">
                    <div><h2 class="mb-1"><?= e($report['class']['judul']) ?></h2><span class="analytics-period"><?= e($data['startDate']) ?> — <?= e($data['endDate']) ?> · <?= (int) $metrics['student_count'] ?> peserta terdaftar dalam periode</span></div>
                    <a class="primary-button text-decoration-none" href="ReportsController.php?class_id=<?= (int) $data['classId'] ?>&amp;start_date=<?= e($data['startDate']) ?>&amp;end_date=<?= e($data['endDate']) ?>&amp;format=pdf"><i class="bi bi-file-earmark-pdf me-1"></i>Ekspor PDF</a>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4"><div class="manage-form-card h-100"><div class="text-secondary">Rata-rata Nilai Kelas</div><div class="display-6 fw-bold"><?= $metrics['average_score'] === null ? '—' : (int) $metrics['average_score'] . '%' ?></div><small class="text-secondary">Rata-rata nilai kuis terakhir per peserta</small></div></div>
                    <div class="col-md-4"><div class="manage-form-card h-100"><div class="text-secondary">Persentase Kelulusan</div><div class="display-6 fw-bold"><?= (int) $metrics['pass_percentage'] ?>%</div><small class="text-secondary"><?= (int) $metrics['passed_count'] ?> dari <?= (int) $metrics['student_count'] ?> peserta</small></div></div>
                    <div class="col-md-4"><div class="manage-form-card h-100"><div class="text-secondary">Total Sertifikat Terbit</div><div class="display-6 fw-bold"><?= (int) $metrics['issued_certificates'] ?></div><small class="text-secondary">Diterbitkan pada periode ini</small></div></div>
                </div>

                <section class="manage-form-card mb-4">
                    <div class="classes-toolbar"><div><h2>Tingkat penyelesaian materi</h2><p class="text-secondary mb-0">Progres peserta berdasarkan data progres kelas.</p></div><span class="analytics-period">Rata-rata <?= (int) $metrics['average_progress'] ?>%</span></div>
                    <?php if ($chartStudents): ?>
                        <div style="height: <?= max(260, count($chartStudents) * 28) ?>px; max-height: 560px; position: relative;">
                            <canvas id="completion-chart"
                                data-labels="<?= e(json_encode(array_column($chartStudents, 'nama'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>"
                                data-values="<?= e(json_encode(array_map('intval', array_column($chartStudents, 'progress')))) ?>"></canvas>
                        </div>
                        <?php if (count($students) > count($chartStudents)): ?><small class="text-secondary">Grafik menampilkan 20 peserta pertama; tabel memuat seluruh peserta.</small><?php endif; ?>
                    <?php else: ?>
                        <div class="text-secondary py-4 text-center">Belum ada peserta terdaftar pada periode ini.</div>
                    <?php endif; ?>
                </section>

                <section class="classes-panel">
                    <div class="classes-toolbar"><h2>Rekapitulasi Nilai Peserta</h2><span class="analytics-period"><?= count($students) ?> peserta</span></div>
                    <div class="classes-table-wrap"><table class="classes-table">
                        <thead><tr><th>Nama Siswa</th><th>Email</th><th>Progress</th><th>Nilai Akhir</th><th>Status Kelulusan</th><th>Sertifikat</th></tr></thead>
                        <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?= e($student['nama']) ?></td>
                                <td><?= e($student['email']) ?></td>
                                <td><?= (int) $student['progress'] ?>%</td>
                                <td><?= $student['final_score'] === null ? '—' : (int) $student['final_score'] . '%' ?></td>
                                <td><span class="status-badge <?= $student['passed'] ? 'status-aktif' : 'status-draft' ?>"><?= $student['passed'] ? 'Lulus' : 'Belum Lulus' ?></span></td>
                                <td><?= $student['certificate_issued'] ? 'Diterbitkan' : 'Belum terbit' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$students): ?><tr><td colspan="6" class="text-center text-secondary py-4">Tidak ada peserta terdaftar pada periode yang dipilih.</td></tr><?php endif; ?>
                        </tbody>
                    </table></div>
                </section>
                <p class="text-secondary small mt-3 mb-0">Nilai akhir adalah rata-rata nilai percobaan kuis terakhir dalam periode. Status lulus memerlukan progres 100% dan seluruh kuis pada kelas dinyatakan lulus dalam periode tersebut.</p>
            <?php elseif (!$data['classes']): ?>
                <div class="alert alert-info">Belum ada kelas yang dapat ditampilkan.</div>
            <?php else: ?>
                <div class="manage-form-card text-center text-secondary py-5">Pilih kelas dan periode tanggal untuk melihat laporan.</div>
            <?php endif; ?>
        </section>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="../../asset/js/instructor-tools.js"></script>
<script src="../../asset/js/filter-scroll.js"></script>
</body>
</html>
