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
    <style>
        :root {
            --bg: #f4f7fb;
            --panel: #ffffff;
            --panel-strong: #f8f9ff;
            --stroke: #e8edf7;
            --stroke-strong: #dfe7ff;
            --text: #1d2433;
            --muted: #667085;
            --brand: #5b6cff;
            --brand-2: #8b5cf6;
            --brand-soft: #edf1ff;
            --success: #1fbf75;
            --warning: #f6b23d;
            --danger: #ef5350;
            --shadow: 0 18px 40px rgba(31, 41, 55, 0.08);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: "Plus Jakarta Sans", sans-serif;
        }
        a { text-decoration: none; }

        .soft-shell {
            max-width: 1400px;
            margin: 24px auto;
            padding: 0 18px 40px;
        }

        .soft-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            background: rgba(255,255,255,0.86);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(216,225,245,0.95);
            border-radius: 24px;
            padding: 18px 22px;
            box-shadow: var(--shadow);
        }
        .brand-lockup {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            font-size: 1.1rem;
            letter-spacing: -0.03em;
        }
        .brand-mark {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--brand), var(--brand-2));
            color: #fff;
            box-shadow: 0 10px 18px rgba(91,108,255,0.32);
            font-size: 1rem;
        }
        .brand-lockup small {
            display: block;
            font-size: 0.68rem;
            letter-spacing: 0.12em;
            color: var(--muted);
            text-transform: uppercase;
            font-weight: 700;
        }
        .nav-pills {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--panel-strong);
            border: 1px solid var(--stroke);
            border-radius: 999px;
            padding: 6px;
        }
        .nav-pills a {
            color: var(--muted);
            font-size: 0.92rem;
            font-weight: 600;
            padding: 8px 14px;
            border-radius: 999px;
            transition: all 0.2s ease;
        }
        .nav-pills a.active {
            background: #fff;
            color: var(--text);
            box-shadow: 0 6px 16px rgba(31,41,55,0.08);
        }
        .top-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .top-actions .btn {
            border-radius: 12px;
            padding: 10px 16px;
            font-weight: 600;
        }
        .btn-outline-soft {
            background: #fff;
            border: 1px solid var(--stroke-strong);
            color: var(--text);
            box-shadow: 0 8px 18px rgba(17, 24, 39, 0.04);
        }
        .btn-primary-soft {
            background: linear-gradient(135deg, var(--brand), var(--brand-2));
            border: none;
            color: #fff;
            border-radius: 12px;
            padding: 10px 18px;
            font-weight: 700;
            box-shadow: 0 14px 24px rgba(91,108,255,0.28);
        }
        .btn-outline-danger {
            border: 1px solid rgba(239,83,80,0.2);
            background: rgba(239,83,80,0.04);
            color: var(--danger);
            border-radius: 12px;
            padding: 8px 14px;
            font-weight: 600;
        }
        .user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #f3f5ff, #f6f2ff);
            border: 1px solid var(--stroke-strong);
            border-radius: 999px;
            padding: 8px 12px 8px 8px;
        }
        .user-pill .avatar {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--brand), #8f7bff);
            color: #fff;
            font-weight: 800;
            font-size: 0.9rem;
        }
        .user-pill strong {
            display: block;
            font-size: 0.92rem;
        }
        .user-pill small {
            color: var(--muted);
            font-size: 0.72rem;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(180px, 1fr));
            gap: 18px;
            margin-top: 22px;
        }
        .summary-card {
            position: relative;
            overflow: hidden;
            background: var(--panel);
            border: 1px solid var(--stroke);
            border-radius: 22px;
            padding: 20px 20px 18px;
            box-shadow: 0 12px 28px rgba(31,41,55,0.04);
        }
        .summary-card::before {
            content: "";
            position: absolute;
            inset: 0 auto auto 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--brand), var(--brand-2));
        }
        .summary-card .label {
            margin: 0;
            color: var(--muted);
            font-size: 0.78rem;
            font-weight: 600;
        }
        .summary-card .value {
            margin: 14px 0 8px;
            font-size: clamp(1.8rem, 1.6rem + 0.8vw, 2.6rem);
            font-weight: 800;
            letter-spacing: -0.05em;
        }
        .summary-card .hint {
            color: var(--muted);
            font-size: 0.8rem;
            line-height: 1.5;
        }

        .section-panel {
            background: var(--panel);
            border: 1px solid var(--stroke);
            border-radius: 28px;
            padding: 22px;
            margin-top: 24px;
            box-shadow: 0 12px 28px rgba(31,41,55,0.04);
        }
        .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }
        .header-row h2,
        .header-row h3,
        .header-row h4 {
            margin: 0;
            letter-spacing: -0.03em;
        }
        .search-box {
            width: min(100%, 320px);
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--panel-strong);
            border: 1px solid var(--stroke);
            border-radius: 14px;
            padding: 8px 12px;
        }
        .search-box input {
            width: 100%;
            border: none;
            background: transparent;
            outline: none;
            font-size: 0.94rem;
            color: var(--text);
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1.45fr 1fr;
            gap: 18px;
        }
        .stack-card {
            background: linear-gradient(180deg, #fafbff, #f7f8ff);
            border: 1px solid var(--stroke-strong);
            border-radius: 22px;
            padding: 20px;
        }
        .form-label {
            font-size: 0.82rem;
            color: var(--muted);
            font-weight: 700;
            margin-bottom: 8px;
        }
        .form-control,
        .form-select,
        textarea {
            border-radius: 14px !important;
            border: 1px solid var(--stroke-strong) !important;
            background: #fff;
            padding: 11px 12px;
            font-size: 0.96rem;
            color: var(--text);
            box-shadow: none;
        }
        .form-control:focus,
        .form-select:focus,
        textarea:focus {
            border-color: rgba(91,108,255,0.5) !important;
            box-shadow: 0 0 0 4px rgba(91,108,255,0.1) !important;
        }
        .table {
            margin: 0;
        }
        .table th {
            color: var(--muted);
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 700;
            border-bottom-color: var(--stroke) !important;
        }
        .table td {
            vertical-align: middle;
            border-bottom-color: var(--stroke) !important;
            padding-top: 14px;
            padding-bottom: 14px;
        }
        .badge-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 10px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
        }
        .status-draft { background: #fff4d9; color: #a56800; }
        .status-aktif { background: #e8fff3; color: #0d8d5f; }
        .status-ditutup { background: #fef0f0; color: #b13d3d; }
        .student-list { list-style:none; padding:0; margin:0; display:grid; gap:10px; }
        .student-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fafbff;
            border: 1px solid var(--stroke);
            border-radius: 16px;
            padding: 12px 14px;
        }
        .student-item strong { display:block; }
        .student-item small { display:block; color: var(--muted); }
        .progress {
            height: 10px;
            border-radius: 999px;
            background: #edf2ff;
            overflow: hidden;
        }
        .progress-bar {
            background: linear-gradient(90deg, var(--brand), #93c5fd);
            border-radius: 999px;
        }
        .stack-card .form-control,
        .stack-card .form-select,
        .stack-card textarea { background: #fff; }
        .muted-label { color: var(--muted); font-size: 0.75rem; }
        .kpi-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 6px 10px;
            background: var(--brand-soft);
            color: var(--brand);
            font-weight: 700;
            font-size: 0.72rem;
        }
        @media (max-width: 980px) {
            .summary-grid { grid-template-columns: repeat(2, minmax(180px, 1fr)); }
            .form-grid { grid-template-columns: 1fr; }
            .soft-topbar { flex-wrap: wrap; }
            .nav-pills { order: 3; width: 100%; justify-content: center; }
        }
        @media (max-width: 600px) {
            .soft-shell { padding: 0 12px 32px; }
            .summary-grid { grid-template-columns: 1fr; }
            .header-row { flex-direction: column; align-items: flex-start; }
            .search-box { width: 100%; }
            .top-actions { width: 100%; justify-content: space-between; }
        }
        .soft-dashboard {
            background: var(--soft-page);
            color: var(--soft-ink);
            font-family: "Plus Jakarta Sans", sans-serif;
        }
        .soft-shell {
            max-width: 1400px;
            margin: 0 auto;
        }
        .soft-stat-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(180px, 1fr));
            gap: 18px;
            margin-top: 22px;
        }
        .soft-stat-card {
            min-height: 180px;
            padding: 20px 22px;
            border-radius: 24px;
            background: var(--soft-surface);
            border: 1px solid var(--soft-line);
            box-shadow: var(--soft-shadow-card);
        }
        .soft-stat-card .soft-stat-icon {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            border-radius: 16px;
            margin-bottom: 20px;
        }
        .soft-stat-card p {
            margin: 0;
            color: var(--soft-muted);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }
        .soft-stat-card strong {
            display: block;
            margin-top: 10px;
            font-size: clamp(2rem, 1.9rem + 0.8vw, 2.6rem);
            line-height: 1.1;
            letter-spacing: -0.05em;
            font-weight: 700;
        }
        .soft-stat-card > span {
            display: block;
            margin-top: 8px;
            color: var(--soft-muted);
            font-size: 11px;
        }
        .soft-panel {
            margin-top: 24px;
            padding: 22px;
            border-radius: 26px;
            background: var(--soft-surface);
            border: 1px solid var(--soft-line);
            box-shadow: var(--soft-shadow-card);
        }
        .soft-card-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 18px;
        }
        @media (max-width: 980px) {
            .soft-stat-grid { grid-template-columns: repeat(2, minmax(180px, 1fr)); }
        }
        @media (max-width: 600px) {
            .soft-stat-grid { grid-template-columns: 1fr; }
            .soft-card-head { flex-direction: column; align-items: flex-start; }
        }
    .instructor-body {
        background: var(--admin-bg);
    }
    .instructor-container {
        width: min(100% - 2rem, 1480px);
        margin-inline: auto;
    }
    .instructor-shell {
        padding-top: 1.4rem;
    }
    .instructor-section {
        background: rgba(255,255,255,0.94);
        border: 1px solid var(--admin-line);
        border-radius: 20px;
        box-shadow: none;
        padding: 20px;
    }
    .instructor-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }
    .instructor-search {
        width: min(100%, 380px);
    }
    .instructor-form-card {
        background: #fff;
        border: 1px solid var(--admin-line);
        border-radius: 18px;
        padding: 18px;
    }
    .instructor-detail-box {
        background: #fff;
        border: 1px solid var(--admin-line);
        border-radius: 18px;
        padding: 18px;
    }
    .class-wizard {
        display: grid;
        gap: 1rem;
    }
    .wizard-header {
        display: flex;
        justify-content: flex-start;
        align-items: center;
        padding-bottom: .75rem;
        border-bottom: 1px solid var(--admin-line);
    }
    .wizard-steps {
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
    }
    .wizard-step {
        border: 1px solid var(--admin-line);
        background: #fff;
        color: var(--admin-muted);
        border-radius: 999px;
        padding: .6rem .9rem;
        font-size: .8rem;
        font-weight: 700;
        transition: all .2s ease;
    }
    .wizard-step.is-active {
        background: linear-gradient(135deg, #f0ebff, #ede9ff);
        color: var(--soft-purple-dark);
        border-color: rgba(131, 110, 214, 0.22);
    }
    .wizard-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.7fr) minmax(260px, .9fr);
        gap: 1rem;
        align-items: start;
    }
    .class-wizard-form {
        min-height: 520px;
    }
    .step-panel {
        display: none;
    }
    .step-panel.is-active {
        display: block;
    }
    .form-group-block {
        border: 1px solid var(--admin-line);
        border-radius: 16px;
        background: #faf9ff;
        padding: .9rem;
    }
    .upload-box {
        display: grid;
        gap: .5rem;
    }
    .upload-box small {
        color: var(--admin-muted);
    }
    .badge-switch-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }
    .switch-toggle {
        position: relative;
        display: inline-flex;
        width: 48px;
        height: 28px;
    }
    .switch-toggle input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .slider {
        position: absolute;
        inset: 0;
        border-radius: 999px;
        background: #dfe3ef;
        transition: .2s ease;
    }
    .slider:before {
        content: "";
        position: absolute;
        width: 20px;
        height: 20px;
        left: 4px;
        top: 4px;
        border-radius: 50%;
        background: #fff;
        transition: .2s ease;
        box-shadow: 0 6px 12px rgba(17, 24, 39, 0.12);
    }
    .switch-toggle input:checked + .slider {
        background: linear-gradient(135deg, var(--soft-purple-light), var(--soft-purple));
    }
    .switch-toggle input:checked + .slider:before {
        transform: translateX(20px);
    }
    .module-builder-box {
        border: 1px solid var(--admin-line);
        border-radius: 18px;
        padding: 1rem;
        background: linear-gradient(180deg, #fff, #faf8ff);
    }
    .quiz-draft-hint {
        border: 1px dashed rgba(131, 110, 214, 0.4);
        border-radius: 14px;
        background: rgba(139, 92, 246, 0.05);
        color: var(--soft-purple-dark);
        font-size: .82rem;
        padding: .75rem .9rem;
    }
    .wizard-actions {
        display: flex;
        justify-content: flex-end;
        gap: .75rem;
        margin-top: 1.25rem;
        padding-top: 1rem;
        border-top: 1px solid var(--admin-line);
    }
    .hidden {
        display: none !important;
    }
    .wizard-summary {
        position: sticky;
        top: 1rem;
    }
    .class-preview-card {
        display: grid;
        gap: .9rem;
        margin-top: 1rem;
    }
    .class-preview-thumb {
        display: grid;
        place-items: center;
        width: 100%;
        min-height: 130px;
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.18), rgba(91, 108, 255, 0.2));
        color: var(--soft-purple-dark);
        font-size: 2.3rem;
    }
    .class-preview-meta {
        display: grid;
        gap: .5rem;
    }
    .class-badge {
        display: inline-flex;
        width: fit-content;
        padding: .35rem .75rem;
        border-radius: 999px;
        background: #f3e8ff;
        color: var(--soft-purple-dark);
        font-size: .72rem;
        font-weight: 700;
    }
    .class-preview-title {
        font-size: 1.2rem;
        font-weight: 800;
        letter-spacing: -.04em;
    }
    .class-preview-desc {
        color: var(--admin-muted);
        line-height: 1.6;
        font-size: .88rem;
    }
    .summary-list {
        display: grid;
        gap: .6rem;
        margin-top: 1rem;
    }
    .summary-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .75rem;
        padding: .7rem .8rem;
        border: 1px solid var(--admin-line);
        border-radius: 12px;
        background: #fbfaff;
    }
    .summary-item span {
        color: var(--admin-muted);
        font-size: .76rem;
    }
    .summary-item strong {
        color: var(--admin-ink);
        font-size: .8rem;
    }
    .simple-cta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.2rem 1.25rem;
        border: 1px solid var(--admin-line);
        border-radius: 18px;
        background: linear-gradient(135deg, #ffffff, #f7f4ff);
    }
    @media (max-width: 767px) {
        .instructor-actions {
            flex-direction: column;
            align-items: stretch;
        }
        .instructor-search {
            width: 100%;
        }
        .simple-cta {
            flex-direction: column;
            align-items: flex-start;
        }
    }
    </style>
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
                                        <div class="progress" style="width:120px;">
                                            <div class="progress-bar" style="width: <?= (int) ($class['avg_progress'] ?? 0) ?>%"></div>
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
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus kelas ini?')">Hapus</button>
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
</body>
</html>
