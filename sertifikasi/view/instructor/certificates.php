<?php
$template = $data['template'];
$certificates = $data['certificates'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e($data['csrfToken']) ?>">
    <title>Kelola Sertifikat | Sertifikasi Academy</title>
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
            <a href="DashboardController.php?page=materi" class="sidebar-item"><span class="sidebar-icon"><i class="bi bi-collection"></i></span><span>Bank Materi</span></a>
            <a href="CertificateController.php" class="sidebar-item active"><span class="sidebar-icon"><i class="bi bi-patch-check"></i></span><span>Kelola Sertifikat</span></a>
            <a href="ForumController.php" class="sidebar-item"><span class="sidebar-icon"><i class="bi bi-chat-dots"></i></span><span>Forum Q&amp;A</span></a>
            <a href="ReportsController.php" class="sidebar-item"><span class="sidebar-icon"><i class="bi bi-bar-chart"></i></span><span>Laporan</span></a>
        </div>
        <div class="sidebar-footer"><div class="mini-profile"><div class="mini-avatar"><?= e(strtoupper(substr($data['username'], 0, 1))) ?></div><div class="mini-meta"><strong><?= e($data['username']) ?></strong><small>Instruktur</small></div></div><a href="../../controller/AuthController.php?aksi=logout" class="logout-link">Keluar</a></div>
    </aside>
    <main class="instructor-main">
        <header class="instructor-topbar"><div class="instructor-breadcrumbs"><a href="DashboardController.php">Instruktur</a><span class="slash">/</span><strong>Kelola Sertifikat</strong></div><div class="topbar-user"><span class="topbar-avatar"><?= e(strtoupper(substr($data['username'], 0, 1))) ?></span><span>Instruktur</span></div></header>
        <section class="instructor-panel">
            <div id="certificate-feedback" class="alert d-none" role="alert"></div>
            <div class="panel-header"><div class="eyebrow">PANEL INSTRUKTUR</div><div class="panel-title-row"><h1>Kelola sertifikat</h1><button type="button" class="primary-button" id="issue-all-certificates">Terbitkan Massal</button></div><p>Verifikasi peserta yang menyelesaikan kelas dan atur tampilan sertifikat.</p></div>
            <section class="classes-panel">
                <div class="classes-toolbar"><h2>Verifikasi sertifikat</h2><span class="analytics-period"><?= count($certificates) ?> peserta memenuhi progres 100%</span></div>
                <div class="classes-table-wrap"><table class="classes-table certificate-table">
                    <thead><tr><th>Nama peserta</th><th>Kelas</th><th>Tanggal lulus</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach ($certificates as $certificate): ?>
                        <?php $issued = $certificate['certificate_status'] === 'issued'; ?>
                        <tr>
                            <td><?= e($certificate['student_name']) ?></td>
                            <td><?= e($certificate['class_title']) ?></td>
                            <td><?= e($certificate['passed_at'] ? tgl_indo($certificate['passed_at']) : '-') ?></td>
                            <td><span class="status-badge <?= $issued ? 'status-aktif' : 'status-draft' ?>"><?= $issued ? 'Diterbitkan' : 'Pending' ?></span></td>
                            <td class="actions-cell">
                                <?php if ($issued): ?>
                                    <a class="action-btn" href="CertificatePrintController.php?id=<?= (int) $certificate['certificate_id'] ?>" target="_blank" rel="noopener">Unduh PDF</a>
                                <?php else: ?>
                                    <button type="button" class="action-btn js-issue-certificate" data-class-id="<?= (int) $certificate['class_id'] ?>" data-student-id="<?= (int) $certificate['student_id'] ?>">Terbitkan Sertifikat</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$certificates): ?><tr><td colspan="5" class="text-secondary">Belum ada peserta dengan progres 100% atau status selesai.</td></tr><?php endif; ?>
                    </tbody>
                </table></div>
            </section>

            <div class="certificate-tools-grid">
                <section class="manage-form-card">
                    <h2>Template sertifikat</h2>
                    <form id="certificate-template-form" enctype="multipart/form-data">
                        <input type="hidden" name="aksi" value="save_template">
                        <input type="hidden" name="csrf_token" value="<?= e($data['csrfToken']) ?>">
                        <label class="form-field mb-3"><span>Gambar latar (PNG/JPG)</span><input class="form-control" type="file" name="certificate_background" accept="image/png,image/jpeg,.png,.jpg,.jpeg"></label>
                        <?php if (!empty($template['background_path'])): ?><img class="template-preview" src="<?= e($template['background_path']) ?>" alt="Latar sertifikat saat ini"><?php endif; ?>
                        <label class="form-field mb-3"><span>Tanda tangan digital (PNG transparan)</span><input class="form-control" type="file" name="certificate_signature" accept="image/png,.png"></label>
                        <?php if (!empty($template['signature_path'])): ?><img class="signature-preview" src="<?= e($template['signature_path']) ?>" alt="Tanda tangan saat ini"><?php endif; ?>
                        <label class="form-field mb-3"><span>Nama penandatangan</span><input class="form-control" name="signer_name" maxlength="150" value="<?= e($template['signer_name']) ?>" required></label>
                        <label class="form-field mb-3"><span>Jabatan</span><input class="form-control" name="signer_title" maxlength="150" value="<?= e($template['signer_title']) ?>" required></label>
                        <label class="form-field mb-3"><span>Format nomor</span><input class="form-control" name="number_format" maxlength="180" value="<?= e($template['number_format']) ?>" required><small class="text-secondary">Wajib menyertakan {YEAR}, {CLASS_ID}, dan {RANDOM}.</small></label>
                        <button class="primary-button" type="submit">Simpan template</button>
                    </form>
                </section>
                <section class="manage-form-card">
                    <h2>Cek validitas</h2>
                    <p class="text-secondary">Cari nomor sertifikat yang diterbitkan oleh instruktur ini.</p>
                    <form id="certificate-verify-form" class="d-flex gap-2">
                        <input class="form-control" name="number" placeholder="CERT/2026/12/ABCD1234" maxlength="180" required>
                        <button class="primary-button" type="submit">Verifikasi</button>
                    </form>
                    <div id="certificate-verification-result" class="mt-3" aria-live="polite"></div>
                </section>
            </div>
        </section>
    </main>
</div>
<script src="../../asset/js/instructor-tools.js"></script>
</body>
</html>
