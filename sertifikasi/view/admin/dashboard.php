<?php
if (!defined('ADMIN_DASHBOARD_AUTHORIZED')) {
    http_response_code(404);
    exit();
}

$statCards = [
    ['Semua akun', $data['stats']['total'], 'bi-people', 'Total pengguna terdaftar'],
    ['Siswa', $data['stats']['siswa'], 'bi-mortarboard', 'Akun peserta belajar'],
    ['Instruktur', $data['stats']['instruktur'], 'bi-easel', 'Akun pengajar'],
    ['Admin', $data['stats']['admin'], 'bi-shield-check', 'Administrator'],
    ['Aktif', $data['stats']['aktif'], 'bi-person-check', 'Akun dapat digunakan'],
    ['Nonaktif', $data['stats']['nonaktif'], 'bi-person-x', 'Akun dinonaktifkan'],
];
$pageTitles = [
    'akun' => ['Manajemen akun', 'Kelola akses, status, dan kata sandi pengguna dari satu tempat.'],
    'reset' => ['Permintaan reset', 'Tinjau dan proses permintaan pengaturan ulang kata sandi.'],
    'sampah' => ['Sampah akun', 'Pulihkan akun atau hapus akun secara permanen.'],
];
[$pageTitle, $pageDescription] = $pageTitles[$data['activeTab']];
$weekdayNames = [
    1 => 'Senin',
    2 => 'Selasa',
    3 => 'Rabu',
    4 => 'Kamis',
    5 => 'Jumat',
    6 => 'Sabtu',
    7 => 'Minggu',
];
$monthNames = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember',
];
$today = date('j') . ' ' . $monthNames[(int) date('n')] . ' ' . date('Y');
$todayLabel = $weekdayNames[(int) date('N')] . ', ' . $today;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | Sertifikasi Academy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../asset/css/admin.css" rel="stylesheet">
    <link href="../../asset/css/theme-soft.css" rel="stylesheet">
    <link href="../../asset/css/admin-console.css?v=6" rel="stylesheet">
</head>
<body class="soft-app admin-body admin-console">
<div class="admin-console-shell">
    <aside class="admin-console-sidebar" id="adminSidebar">
        <a class="admin-console-brand" href="DashboardController.php" aria-label="Sertifikasi Academy Admin">
            <svg viewBox="0 0 64 64" fill="none" aria-hidden="true">
                <rect x="7" y="5" width="42" height="48" rx="5" stroke="currentColor" stroke-width="3.5"/>
                <path d="M15 17H39M15 26H39M15 35H31" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/>
                <circle cx="45" cy="43" r="10" fill="#F4D982" stroke="currentColor" stroke-width="3"/>
                <path d="M39 51L36 62L45 57L52 62L51 51" fill="#77B991" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/>
            </svg>
            <span class="admin-console-brand-copy"><span>Sertifikasi</span><strong>Academy</strong><small>ADMINISTRATION</small></span>
        </a>

        <div class="admin-console-menu-label">MENU UTAMA</div>
        <nav class="admin-console-nav" aria-label="Navigasi administrasi">
            <a class="<?= $data['activeTab'] === 'akun' ? 'is-active' : '' ?>" href="DashboardController.php" <?= $data['activeTab'] === 'akun' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-people" aria-hidden="true"></i><span>Manajemen Akun</span>
            </a>
            <a class="<?= $data['activeTab'] === 'reset' ? 'is-active' : '' ?>" href="DashboardController.php?tab=reset" <?= $data['activeTab'] === 'reset' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-key" aria-hidden="true"></i><span>Permintaan Reset</span>
                <?php if ($data['pendingResetCount'] > 0): ?><b class="admin-console-nav-count"><?= (int) $data['pendingResetCount'] ?></b><?php endif; ?>
            </a>
            <a class="<?= $data['activeTab'] === 'sampah' ? 'is-active' : '' ?>" href="DashboardController.php?tab=sampah" <?= $data['activeTab'] === 'sampah' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-trash3" aria-hidden="true"></i><span>Sampah</span>
                <?php if ($data['trashUsers']): ?><b class="admin-console-nav-count"><?= count($data['trashUsers']) ?></b><?php endif; ?>
            </a>
        </nav>

        <div class="admin-console-sidebar-bottom">
            <a class="admin-console-help" href="../user/ProfileController.php?tab=profil">
                <i class="bi bi-shield-check" aria-hidden="true"></i>
                <span><strong>Area Administrator</strong><small>Kelola akun dengan aman.</small></span>
            </a>
            <small class="admin-console-version">Sertifikasi Academy · Admin Panel</small>
        </div>
    </aside>
    <button class="admin-console-overlay" id="adminSidebarOverlay" type="button" aria-label="Tutup menu"></button>

    <div class="admin-console-main">
        <header class="admin-console-topbar">
            <div class="admin-console-topbar-left">
                <button class="admin-console-icon-button admin-console-menu-toggle" id="adminMenuToggle" type="button" aria-label="Buka atau tutup navigasi" aria-controls="adminSidebar" aria-expanded="false">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <div class="admin-console-breadcrumb"><span>Administrator</span><i class="bi bi-chevron-right" aria-hidden="true"></i><strong><?= e(ucfirst($pageTitle)) ?></strong></div>
            </div>
            <div class="admin-console-topbar-actions">
                <a class="admin-console-profile" href="../user/ProfileController.php?tab=profil">
                    <span class="admin-console-avatar"><?= e(strtoupper(substr($data['username'] !== '' ? $data['username'] : 'A', 0, 1))) ?></span>
                    <span><strong><?= e($data['username']) ?></strong><small>Administrator</small></span>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </a>
                <a class="admin-console-logout" href="../AuthController.php?aksi=logout"><i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>Keluar</span></a>
            </div>
        </header>

<main class="admin-console-content">
    <header class="admin-page-heading">
        <div>
            <div class="admin-eyebrow"><span class="admin-console-eyebrow-line"></span> Panel administrasi</div>
            <h1><?= e($pageTitle) ?></h1>
            <p><?= e($pageDescription) ?></p>
        </div>
        <div class="admin-console-date"><i class="bi bi-calendar3" aria-hidden="true"></i><span><?= e($todayLabel) ?></span></div>
    </header>

    <?php if ($data['success'] !== ''): ?>
        <div class="alert admin-alert admin-alert-success"><i class="bi bi-check-circle-fill"></i><span><?= e($data['success']) ?></span></div>
    <?php endif; ?>
    <?php if ($data['error'] !== ''): ?>
        <div class="alert admin-alert admin-alert-error"><i class="bi bi-exclamation-circle-fill"></i><span><?= e($data['error']) ?></span></div>
    <?php endif; ?>
    <?php if ($data['generatedPassword'] !== ''): ?>
        <div class="alert admin-alert admin-alert-warning" role="alert">
            <i class="bi bi-key-fill"></i>
            <div class="flex-grow-1">
                <strong>Kata sandi sementara — hanya ditampilkan sekali</strong>
                <div class="small mt-1">Berikan kepada pemilik akun secara langsung setelah memverifikasi identitasnya.</div>
                <div class="input-group mt-2">
                    <input class="form-control font-monospace" id="generatedPassword" value="<?= e($data['generatedPassword']) ?>" readonly>
                    <button class="btn btn-admin-quiet" type="button" data-copy-target="generatedPassword">Salin</button>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($data['activeTab'] === 'akun'): ?>
    <section class="row g-3 mb-4" aria-label="Ringkasan akun">
        <?php foreach ($statCards as [$label, $count, $icon, $description]): ?>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="admin-stat-card">
                    <div class="admin-stat-label"><?= e($label) ?></div>
                    <div class="admin-stat-value"><?= (int) $count ?></div>
                    <span class="admin-stat-icon<?= $label === 'Nonaktif' ? ' is-muted' : '' ?>"><i class="bi <?= e($icon) ?>"></i></span>
                    <small class="admin-stat-description"><?= e($description) ?></small>
                </div>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="admin-panel admin-users-panel">
        <div class="admin-users-heading">
            <div>
                <h2>Daftar akun</h2>
                <p>Kelola seluruh akun yang terdaftar di sistem.</p>
            </div>
            <div class="admin-users-heading-actions">
                <button
                    class="btn btn-sm btn-admin-quiet"
                    id="toggleBulkTrashMode"
                    type="button"
                    aria-expanded="false"
                    aria-controls="bulkTrashToolbar"
                >
                    <i class="bi bi-ui-checks me-1"></i>Pilih beberapa
                </button>
                <i class="bi bi-people admin-users-heading-icon" aria-hidden="true"></i>
            </div>
        </div>
        <div class="admin-filter-bar">
            <form class="row g-2 align-items-center" method="GET">
                <div class="col-12 col-lg-5">
                    <label class="visually-hidden" for="searchUsers">Cari nama atau email</label>
                    <div class="admin-search">
                        <i class="bi bi-search"></i>
                        <input class="form-control" id="searchUsers" name="q" type="search" placeholder="Cari nama atau email..." value="<?= e($data['search']) ?>">
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <label class="visually-hidden" for="filterRole">Filter role</label>
                    <select class="form-select" id="filterRole" name="role">
                        <option value="">Semua role</option>
                        <?php foreach (['siswa' => 'Siswa', 'instruktur' => 'Instruktur', 'admin' => 'Admin'] as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $data['roleFilter'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="visually-hidden" for="filterStatus">Filter status</label>
                    <select class="form-select" id="filterStatus" name="status">
                        <option value="">Semua status</option>
                        <option value="aktif" <?= $data['statusFilter'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="nonaktif" <?= $data['statusFilter'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
                <div class="col-12 col-lg-2 d-flex gap-2">
                    <button class="btn btn-admin-filter flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button>
                    <?php if ($data['search'] !== '' || $data['roleFilter'] !== '' || $data['statusFilter'] !== ''): ?>
                        <a class="btn btn-admin-quiet" href="DashboardController.php" aria-label="Hapus filter"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <form id="bulkTrashForm" class="admin-bulk-toolbar" method="POST" hidden>
            <input type="hidden" name="csrf_token" value="<?= e($data['csrf']) ?>">
            <input type="hidden" name="aksi" value="hapus">
            <input type="hidden" name="tab" value="akun">
            <input type="hidden" name="q" value="<?= e($data['search']) ?>">
            <input type="hidden" name="filter_role" value="<?= e($data['roleFilter']) ?>">
            <input type="hidden" name="filter_status" value="<?= e($data['statusFilter']) ?>">
            <div>
                <strong>Pilih akun untuk dipindahkan ke sampah</strong>
                <span id="bulkTrashSelectionStatus" aria-live="polite">0 dari 5 akun dipilih</span>
                <span id="bulkTrashLimitNotice" class="admin-bulk-limit" aria-live="polite" hidden>Maksimal lima akun dapat dipilih sekaligus.</span>
            </div>
            <div class="admin-bulk-actions">
                <button class="btn btn-sm btn-admin-quiet" id="cancelBulkTrashMode" type="button">Batal</button>
                <button class="btn btn-sm btn-outline-danger" id="bulkTrashButton" type="submit" disabled>
                    <i class="bi bi-trash3 me-1"></i>Ke sampah
                </button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table admin-users-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Akun</th><th scope="col">Role</th><th scope="col">Status</th><th scope="col">Tanggal daftar</th><th scope="col" class="text-end">Kelola</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$data['users']): ?>
                    <tr><td colspan="5">
                        <div class="admin-empty-state">
                            <i class="bi bi-person-exclamation"></i>
                            <strong>Tidak ada akun ditemukan</strong>
                            <span>Coba ubah kata pencarian atau filter yang digunakan.</span>
                        </div>
                    </td></tr>
                <?php endif; ?>
                <?php foreach ($data['users'] as $user): ?>
                    <?php
                    $self = (int) $user['id'] === $data['actorId'];
                    $returnFilters = [
                        'q' => $data['search'],
                        'filter_role' => $data['roleFilter'],
                        'filter_status' => $data['statusFilter'],
                    ];
                    ?>
                    <tr>
                        <td>
                            <label class="admin-account-cell admin-account-hitarea<?= $self ? ' is-unselectable' : '' ?>">
                                <input
                                    class="form-check-input admin-account-select"
                                    type="checkbox"
                                    name="user_ids[]"
                                    value="<?= (int) $user['id'] ?>"
                                    form="bulkTrashForm"
                                    aria-label="Pilih akun <?= e($user['nama']) ?> untuk dipindahkan ke sampah"
                                    <?= $self ? 'disabled title="Akun admin yang sedang digunakan tidak dapat dipindahkan."' : '' ?>
                                >
                                <?php if (!empty($user['foto_profil'])): ?>
                                    <img class="admin-account-avatar" src="../../<?= e($user['foto_profil']) ?>" alt="Foto profil <?= e($user['nama']) ?>" loading="lazy">
                                <?php else: ?>
                                    <span class="admin-account-avatar"><?= e(strtoupper(substr($user['nama'], 0, 1))) ?></span>
                                <?php endif; ?>
                                <span><strong><?= e($user['nama']) ?><?= $self ? ' <small class="admin-self-label">(kamu)</small>' : '' ?></strong><small><?= e($user['email']) ?></small></span>
                            </label>
                        </td>
                        <td><span class="admin-role-badge admin-role-<?= e($user['role']) ?>"><i class="bi <?= $user['role'] === 'admin' ? 'bi-shield-lock' : ($user['role'] === 'instruktur' ? 'bi-easel' : 'bi-mortarboard') ?>"></i><?= e(ucfirst($user['role'])) ?></span></td>
                        <td><span class="admin-status-badge <?= $user['status'] === 1 ? 'is-active' : 'is-inactive' ?>"><span></span><?= $user['status'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td class="admin-date-cell"><?= e($user['dibuat_pada']) ?></td>
                        <td class="text-end">
                            <button
                                class="admin-row-actions"
                                type="button"
                                data-dialog-target="manage-account-<?= (int) $user['id'] ?>"
                                aria-haspopup="dialog"
                            ><i class="bi bi-sliders2 me-1"></i>Kelola</button>
                            <dialog class="admin-manage-dialog" id="manage-account-<?= (int) $user['id'] ?>" aria-labelledby="manage-account-title-<?= (int) $user['id'] ?>">
                                <div class="admin-actions-panel">
                                    <div class="admin-actions-heading">
                                        <div>
                                            <h2 id="manage-account-title-<?= (int) $user['id'] ?>">Kelola akun</h2>
                                            <p><?= e($user['nama']) ?></p>
                                        </div>
                                        <button class="admin-dialog-close" type="button" aria-label="Tutup dialog"><i class="bi bi-x-lg"></i></button>
                                    </div>
                                    <h3>Akses akun</h3>
                                    <form method="POST" class="admin-action-form" data-confirm="Yakin mengubah role akun ini?">
                                    <input type="hidden" name="csrf_token" value="<?= e($data['csrf']) ?>">
                                    <input type="hidden" name="aksi" value="role">
                                    <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                    <?php foreach ($returnFilters as $key => $value): ?><input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>"><?php endforeach; ?>
                                        <label for="role-<?= (int) $user['id'] ?>">Role</label>
                                        <select class="form-select form-select-sm" id="role-<?= (int) $user['id'] ?>" name="role" aria-label="Role baru untuk <?= e($user['nama']) ?>" <?= $self ? 'disabled' : '' ?>>
                                        <?php foreach (['siswa' => 'Siswa', 'instruktur' => 'Instruktur', 'admin' => 'Admin'] as $value => $label): ?>
                                            <option value="<?= e($value) ?>" <?= $user['role'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                        <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-admin-filter btn-sm" type="submit" <?= $self ? 'disabled' : '' ?>>Simpan role</button>
                                    </form>
                                    <form method="POST" class="admin-action-form" data-confirm="<?= e(($user['status'] === 1 ? 'Nonaktifkan' : 'Aktifkan') . ' akun ini?') ?>">
                                        <input type="hidden" name="csrf_token" value="<?= e($data['csrf']) ?>">
                                        <input type="hidden" name="aksi" value="status">
                                        <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                        <input type="hidden" name="status" value="<?= $user['status'] === 1 ? '0' : '1' ?>">
                                        <?php foreach ($returnFilters as $key => $value): ?><input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>"><?php endforeach; ?>
                                        <div>
                                            <label>Status</label>
                                            <div class="admin-current-value"><?= $user['status'] === 1 ? 'Aktif' : 'Nonaktif' ?></div>
                                        </div>
                                        <button class="btn btn-sm <?= $user['status'] === 1 ? 'btn-outline-warning' : 'btn-outline-success' ?>" type="submit" <?= $self && $user['status'] === 1 ? 'disabled' : '' ?>>
                                            <?= $user['status'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?>
                                        </button>
                                    </form>
                                    <?php if ($self && $user['status'] === 1): ?><p class="admin-action-note"><i class="bi bi-lock me-1"></i>Akun sendiri tidak dapat diubah role atau dinonaktifkan.</p><?php endif; ?>

                                    <?php if (!$self): ?>
                                        <h3 class="mt-3">Kata sandi</h3>
                                        <form method="POST" class="admin-action-form admin-password-form" data-confirm="Simpan password baru untuk akun ini? Password lama tidak dapat dipulihkan.">
                                            <input type="hidden" name="csrf_token" value="<?= e($data['csrf']) ?>">
                                            <input type="hidden" name="aksi" value="reset_password">
                                            <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                            <?php foreach ($returnFilters as $key => $value): ?><input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>"><?php endforeach; ?>
                                            <label for="password-<?= (int) $user['id'] ?>">Kata sandi baru</label>
                                            <div class="input-group input-group-sm">
                                                <input class="form-control" id="password-<?= (int) $user['id'] ?>" type="password" name="password_baru" minlength="8" maxlength="200" placeholder="Minimal 8 karakter" autocomplete="new-password" required>
                                                <button class="btn btn-admin-quiet" type="button" data-toggle-password aria-label="Tampilkan password" aria-controls="password-<?= (int) $user['id'] ?>" aria-pressed="false"><i class="bi bi-eye"></i></button>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-admin-filter flex-grow-1" name="mode" value="manual" type="submit">Ubah kata sandi</button>
                                                <button class="btn btn-sm btn-admin-quiet flex-grow-1" name="mode" value="generate" type="submit" formnovalidate>Buat acak</button>
                                            </div>
                                            <small>Password lama tidak dapat dilihat; tombol mata hanya menampilkan password baru sebelum disimpan.</small>
                                        </form>
                                    <?php endif; ?>

                                    <h3 class="mt-3">Penghapusan bertahap</h3>
                                    <p class="admin-action-note">Akun akan dinonaktifkan sekarang dan dihapus permanen dalam 5 menit jika tidak dipulihkan. Waktu dihitung dari database dan terus berjalan meski dashboard ditutup.</p>
                                    <form method="POST" class="admin-action-form" data-confirm="Pindahkan akun ini ke sampah? Akun akan langsung dinonaktifkan dan dihapus permanen dalam 5 menit jika tidak dipulihkan.">
                                        <input type="hidden" name="csrf_token" value="<?= e($data['csrf']) ?>">
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                        <?php foreach ($returnFilters as $key => $value): ?><input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>"><?php endforeach; ?>
                                        <button class="btn btn-sm btn-outline-danger" type="submit" <?= $self ? 'disabled' : '' ?>>
                                            <i class="bi bi-trash3 me-1"></i>Ke sampah
                                        </button>
                                        <?php if ($self): ?><small class="admin-action-note">Akun sendiri tidak dapat dipindahkan ke sampah.</small><?php endif; ?>
                                    </form>
                                </div>
                            </dialog>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php elseif ($data['activeTab'] === 'sampah'): ?>
    <section class="admin-panel admin-trash-panel" aria-labelledby="trash-heading">
        <div class="admin-users-heading">
            <div>
                <h2 id="trash-heading">Sampah akun</h2>
                <p>Akun dinonaktifkan saat dipindahkan. Akun dan semua data terkait dihapus permanen setelah 5 menit selama MySQL tetap berjalan.</p>
            </div>
            <i class="bi bi-trash3 admin-users-heading-icon" aria-hidden="true"></i>
        </div>
        <?php if (!$data['trashUsers']): ?>
            <div class="admin-empty-state">
                <i class="bi bi-trash3" aria-hidden="true"></i>
                <strong>Sampah kosong</strong>
                <span>Akun yang dipindahkan ke sampah akan tampil di sini selama 5 menit.</span>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table admin-users-table admin-trash-table align-middle mb-0">
                    <thead>
                        <tr><th>Akun</th><th>Dipindahkan</th><th>Penghapusan permanen</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($data['trashUsers'] as $user): ?>
                        <tr>
                            <td>
                                <div class="admin-account-cell">
                                    <?php if (!empty($user['foto_profil'])): ?>
                                        <img class="admin-account-avatar" src="../../<?= e($user['foto_profil']) ?>" alt="" loading="lazy">
                                    <?php else: ?>
                                        <span class="admin-account-avatar" aria-hidden="true"><?= e(strtoupper(substr($user['nama'], 0, 1))) ?></span>
                                    <?php endif; ?>
                                    <span><strong><?= e($user['nama']) ?></strong><small><?= e($user['email']) ?></small></span>
                                </div>
                            </td>
                            <td class="admin-date-cell"><?= e($user['masuk_sampah_pada']) ?></td>
                            <td>
                                <span class="admin-trash-countdown" data-expire-at="<?= (int) $user['hapus_pada_epoch'] ?>">Menghitung waktu...</span>
                            </td>
                            <td class="text-end">
                                <div class="admin-trash-actions">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= e($data['csrf']) ?>">
                                        <input type="hidden" name="aksi" value="pulihkan">
                                        <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                        <input type="hidden" name="tab" value="sampah">
                                        <button class="btn btn-sm btn-admin-quiet" type="submit">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                        </button>
                                    </form>
                                    <form method="POST" class="admin-trash-form">
                                        <input type="hidden" name="csrf_token" value="<?= e($data['csrf']) ?>">
                                        <input type="hidden" name="aksi" value="hapus_permanen">
                                        <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                        <input type="hidden" name="tab" value="sampah">
                                        <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Hapus <?= e($user['nama']) ?> permanen">
                                            <i class="bi bi-x-octagon me-1"></i>Hapus sekarang
                                        </button>
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
    <?php else: ?>
    <section class="admin-panel admin-users-panel" aria-labelledby="reset-heading">
        <div class="admin-users-heading">
            <div>
                <h2 id="reset-heading">Permintaan reset password</h2>
                <p>Verifikasi identitas pemilik secara langsung sebelum menetapkan password baru.</p>
            </div>
            <i class="bi bi-key admin-users-heading-icon" aria-hidden="true"></i>
        </div>
        <?php if (!$data['pendingResetRequests']): ?>
            <div class="admin-empty-state">
                <i class="bi bi-inbox" aria-hidden="true"></i>
                <strong>Tidak ada permintaan tertunda</strong>
                <span>Permintaan dari pengguna akan muncul di sini.</span>
            </div>
        <?php else: ?>
            <div class="d-grid gap-3">
                <?php foreach ($data['pendingResetRequests'] as $request): ?>
                    <details class="admin-panel p-3">
                        <summary class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <span>
                                <strong><?= e($request['nama']) ?></strong>
                                <span class="d-block small text-muted"><?= e($request['email']) ?> · <?= e(ucfirst($request['role'])) ?></span>
                            </span>
                            <span class="small text-muted">Diminta <?= e($request['created_at']) ?></span>
                        </summary>
                        <?php if ($request['masuk_sampah_pada'] !== null): ?>
                            <div class="alert alert-warning mt-3 mb-0" role="status">
                                Pulihkan akun dari sampah sebelum mereset password.
                            </div>
                        <?php elseif ((int) $request['status'] !== 1): ?>
                            <div class="alert alert-warning mt-3 mb-0" role="status">
                                Aktifkan akun terlebih dahulu dari daftar akun sebelum memproses reset password.
                            </div>
                        <?php else: ?>
                            <form method="POST" class="admin-action-form mt-3">
                                <input type="hidden" name="csrf_token" value="<?= e($data['csrf']) ?>">
                                <input type="hidden" name="aksi" value="reset_password_request">
                                <input type="hidden" name="request_id" value="<?= (int) $request['request_id'] ?>">
                                <input type="hidden" name="user_id" value="<?= (int) $request['user_id'] ?>">
                                <input type="hidden" name="tab" value="reset">
                                <label for="reset-password-<?= (int) $request['request_id'] ?>">Password baru</label>
                                <input
                                    class="form-control"
                                    id="reset-password-<?= (int) $request['request_id'] ?>"
                                    type="password"
                                    name="password_baru"
                                    minlength="8"
                                    maxlength="200"
                                    autocomplete="new-password"
                                    required
                                >
                                <small>Minimal 8 karakter. Password akan ditampilkan sekali jika dibuat acak.</small>
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-sm btn-admin-filter" name="mode" value="manual" type="submit">
                                        Reset dengan password ini
                                    </button>
                                    <button class="btn btn-sm btn-admin-quiet" name="mode" value="generate" type="submit" formnovalidate>
                                        Buat password acak
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
    <?php if ($data['trashUsers']): ?>
        <div class="visually-hidden" aria-hidden="true">
            <?php foreach ($data['trashUsers'] as $user): ?>
                <span data-expire-at="<?= (int) $user['hapus_pada_epoch'] ?>"></span>
            <?php endforeach; ?>
        </div>
        <form method="POST" id="purgeExpiredTrashForm" class="visually-hidden" aria-hidden="true">
            <input type="hidden" name="csrf_token" value="<?= e($data['csrf']) ?>">
            <input type="hidden" name="aksi" value="bersihkan_sampah">
            <input type="hidden" name="tab" value="<?= e($data['activeTab']) ?>">
        </form>
    <?php endif; ?>
    <footer class="admin-console-footer">© <?= date('Y') ?> Sertifikasi Academy <span>·</span> Admin Panel</footer>
</main>
    </div>
</div>
<script src="../../asset/js/admin-dashboard.js?v=4"></script>
<script src="../../asset/js/page-actions.js"></script>
</body>
</html>
