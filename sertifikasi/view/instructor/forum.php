<?php $classes = $data['classes']; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e($data['csrfToken']) ?>">
    <title>Forum Q&amp;A | Sertifikasi Academy</title>
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
            <a href="CertificateController.php" class="sidebar-item"><span class="sidebar-icon"><i class="bi bi-patch-check"></i></span><span>Kelola Sertifikat</span></a>
            <a href="ForumController.php" class="sidebar-item active"><span class="sidebar-icon"><i class="bi bi-chat-dots"></i></span><span>Forum Q&amp;A</span><span class="sidebar-badge" id="unanswered-sidebar-count"><?= (int) $data['unanswered'] ?></span></a>
            <a href="ReportsController.php" class="sidebar-item"><span class="sidebar-icon"><i class="bi bi-bar-chart"></i></span><span>Laporan</span></a>
        </div>
        <div class="sidebar-footer"><div class="mini-profile"><div class="mini-avatar"><?= e(strtoupper(substr($data['username'], 0, 1))) ?></div><div class="mini-meta"><strong><?= e($data['username']) ?></strong><small>Instruktur</small></div></div><a href="../../controller/AuthController.php?aksi=logout" class="logout-link">Keluar</a></div>
    </aside>
    <main class="instructor-main">
        <header class="instructor-topbar"><div class="instructor-breadcrumbs"><a href="DashboardController.php">Instruktur</a><span class="slash">/</span><strong>Forum Q&amp;A</strong></div><div class="topbar-user"><span class="topbar-avatar"><?= e(strtoupper(substr($data['username'], 0, 1))) ?></span><span>Instruktur</span></div></header>
        <section class="instructor-panel forum-panel">
            <div class="panel-header"><div class="eyebrow">RUANG DISKUSI</div><div class="panel-title-row"><h1>Forum Q&amp;A</h1><span class="unanswered-counter"><strong id="unanswered-count"><?= (int) $data['unanswered'] ?></strong> pertanyaan belum dijawab</span></div><p>Pilih pertanyaan di sebelah kiri untuk membaca diskusi dan membalas siswa.</p></div>
            <div class="forum-split">
                <aside class="forum-inbox">
                    <div class="forum-filters">
                        <label class="form-field"><span>Kelas</span><select id="forum-class-filter" class="form-select"><option value="0">Semua kelas</option><?php foreach ($classes as $class): ?><option value="<?= (int) $class['id'] ?>"><?= e($class['judul']) ?></option><?php endforeach; ?></select></label>
                        <label class="form-field"><span>Status</span><select id="forum-status-filter" class="form-select"><option value="all">Semua</option><option value="unanswered">Belum dijawab</option><option value="answered">Sudah dijawab</option></select></label>
                    </div>
                    <div id="forum-thread-list" class="forum-thread-list" aria-live="polite"><p class="text-secondary p-3">Memuat pertanyaan...</p></div>
                </aside>
                <section class="forum-discussion" aria-label="Detail diskusi">
                    <div id="forum-empty-detail" class="forum-empty-detail"><i class="bi bi-chat-square-text"></i><h2>Pilih pertanyaan</h2><p>Detail percakapan akan tampil di sini.</p></div>
                    <div id="forum-detail-content" class="d-none">
                        <header class="discussion-header"><div class="student-avatar" id="forum-student-avatar">S</div><div><h2 id="forum-question-title"></h2><p><span id="forum-student-name"></span> · <span id="forum-class-name"></span> · <time id="forum-question-time"></time></p></div></header>
                        <div id="forum-messages" class="forum-messages"></div>
                        <form id="forum-reply-form" class="forum-reply-form" enctype="multipart/form-data">
                            <input type="hidden" name="aksi" value="reply">
                            <input type="hidden" name="thread_id" id="forum-thread-id" value="">
                            <input type="hidden" name="csrf_token" value="<?= e($data['csrfToken']) ?>">
                            <label class="form-field"><span>Balasan instruktur</span><textarea name="message" rows="3" placeholder="Tulis jawaban yang membantu... (atau lampirkan kode/file)"></textarea></label>
                            <details class="forum-code-attach"><summary>Lampirkan kode atau file</summary><label class="form-field mt-2"><span>Potongan kode</span><textarea name="code_snippet" rows="4" placeholder="Tempel potongan kode (opsional)"></textarea></label><label class="form-field mt-2"><span>File (PDF, TXT, PNG, JPG; maks. 4 MB)</span><input type="file" name="reply_file" accept=".pdf,.txt,.png,.jpg,.jpeg,application/pdf,text/plain,image/png,image/jpeg"></label></details>
                            <div id="forum-reply-feedback" class="alert d-none" role="alert"></div>
                            <button type="submit" class="primary-button">Kirim Jawaban</button>
                        </form>
                    </div>
                </section>
            </div>
        </section>
    </main>
</div>
<script src="../../asset/js/instructor-tools.js"></script>
</body>
</html>
