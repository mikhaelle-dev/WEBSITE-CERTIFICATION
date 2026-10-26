<?php
if (!defined('ADMIN_DASHBOARD_AUTHORIZED')) {
    http_response_code(404);
    exit();
}

$statCards = [
    ['Semua akun', $data['stats']['total'], 'bi-people'],
    ['Siswa', $data['stats']['siswa'], 'bi-mortarboard'],
    ['Instruktur', $data['stats']['instruktur'], 'bi-easel'],
    ['Admin', $data['stats']['admin'], 'bi-shield-lock'],
    ['Aktif', $data['stats']['aktif'], 'bi-person-check'],
    ['Nonaktif', $data['stats']['nonaktif'], 'bi-person-slash'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | Sertifikasi Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../asset/css/admin.css" rel="stylesheet">
    <link href="../../asset/css/brutal.css" rel="stylesheet">
</head>
<body class="admin-body brutal-app">
<nav class="navbar navbar-expand-lg admin-navbar sticky-top">
    <div class="container-fluid admin-container">
        <a class="navbar-brand admin-brand" href="DashboardController.php">
            <span>Sertifikasi <strong>Academy</strong><small>ADMINISTRATION</small></span>
        </a>
        <div class="ms-auto d-flex align-items-center gap-2 gap-md-3">
            <a class="btn btn-admin-quiet btn-sm" href="../user/ProfileController.php?tab=profil"><i class="bi bi-person-gear me-1"></i><span class="d-none d-sm-inline">Profil &amp; password</span><span class="d-sm-none">Profil</span></a>
            <span class="admin-user-name d-none d-md-inline"><?= e($data['username']) ?></span>
            <a class="btn btn-admin-logout btn-sm" href="../AuthController.php?aksi=logout"><i class="bi bi-box-arrow-right me-1"></i>Keluar</a>
        </div>
    </div>
</nav>

<main class="container-fluid admin-container py-4 py-lg-5">
    <header class="admin-page-heading">
        <div>
            <div class="admin-eyebrow"><i class="bi bi-shield-check me-1"></i> Panel administrasi</div>
            <h1>Manajemen akun</h1>
            <p>Kelola akses, status, dan kata sandi pengguna dari satu tempat.</p>
        </div>
        <a class="btn btn-admin-primary" href="#addAccount" onclick="document.getElementById('addAccount').open = true"><i class="bi bi-plus-lg me-2"></i>Tambah akun</a>
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
                <div class="small mt-1">Salin dan berikan secara aman kepada pemilik akun.</div>
            <div class="input-group mt-2">
                    <input class="form-control font-monospace" id="generatedPassword" value="<?= e($data['generatedPassword']) ?>" readonly>
                    <button class="btn btn-admin-quiet" type="button" onclick="navigator.clipboard.writeText(document.getElementById('generatedPassword').value)">Salin</button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <section class="row g-3 mb-4" aria-label="Ringkasan akun">
        <?php foreach ($statCards as [$label, $count, $icon]): ?>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="admin-stat-card">
                    <div>
                        <div class="admin-stat-label"><?= e($label) ?></div>
                        <div class="admin-stat-value"><?= (int) $count ?></div>
                    </div>
                    <span class="admin-stat-icon"><i class="bi <?= e($icon) ?>"></i></span>
                </div>
            </div>
        <?php endforeach; ?>
    </section>

    <details class="admin-panel admin-add-panel mb-4" id="addAccount">
        <summary class="admin-panel-summary">
            <span class="admin-panel-icon"><i class="bi bi-person-plus"></i></span>
            <span class="flex-grow-1"><strong>Tambah akun baru</strong><small>Buat akun siswa, instruktur, atau admin</small></span>
            <i class="bi bi-chevron-down admin-chevron"></i>
        </summary>
        <div class="admin-panel-body">
            <form method="POST" onsubmit="return confirm('Tambahkan akun baru dengan role dan password awal ini?')">
                <input type="hidden" name="csrf_token" value="<?= e($data['csrf']) ?>">
                <input type="hidden" name="aksi" value="tambah">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label" for="newName">Nama</label>
                        <input class="form-control" id="newName" name="nama" maxlength="100" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="newEmail">Email</label>
                        <input class="form-control" type="email" id="newEmail" name="email" maxlength="100" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="newRole">Role</label>
                        <select class="form-select" id="newRole" name="role" required>
                            <option value="siswa">Siswa</option>
                            <option value="instruktur">Instruktur</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="newPassword">Kata sandi awal</label>
                        <input class="form-control" type="password" id="newPassword" name="password" minlength="8" maxlength="200" autocomplete="new-password" required>
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-admin-primary w-100" type="submit"><i class="bi bi-plus-lg me-1"></i><span class="d-md-none d-xl-inline">Buat</span></button>
                    </div>
                </div>
                <p class="admin-form-hint"><i class="bi bi-info-circle me-1"></i>Kata sandi disimpan dalam bentuk hash dan tidak bisa dilihat setelah akun dibuat.</p>
            </form>
        </div>
    </details>

    <section class="admin-panel admin-users-panel">
        <div class="admin-users-heading">
            <div>
                <h2>Daftar akun</h2>
                <p><?= count($data['users']) ?> akun ditampilkan</p>
            </div>
            <i class="bi bi-people admin-users-heading-icon"></i>
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
        <div class="table-responsive">
            <table class="table admin-users-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Akun</th><th>Role</th><th>Status</th><th>Tanggal daftar</th><th class="text-end">Kelola</th>
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
                            <div class="admin-account-cell">
                                <?php if (!empty($user['foto_profil'])): ?>
                                    <img class="admin-account-avatar" src="../../<?= e($user['foto_profil']) ?>" alt="Foto profil <?= e($user['nama']) ?>" loading="lazy">
                                <?php else: ?>
                                    <span class="admin-account-avatar"><?= e(strtoupper(substr($user['nama'], 0, 1))) ?></span>
                                <?php endif; ?>
                                <span><strong><?= e($user['nama']) ?><?= $self ? ' <small class="admin-self-label">(kamu)</small>' : '' ?></strong><small><?= e($user['email']) ?></small></span>
                            </div>
                        </td>
                        <td><span class="admin-role-badge admin-role-<?= e($user['role']) ?>"><i class="bi <?= $user['role'] === 'admin' ? 'bi-shield-lock' : ($user['role'] === 'instruktur' ? 'bi-easel' : 'bi-mortarboard') ?>"></i><?= e(ucfirst($user['role'])) ?></span></td>
                        <td><span class="admin-status-badge <?= $user['status'] === 1 ? 'is-active' : 'is-inactive' ?>"><span></span><?= $user['status'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td class="admin-date-cell"><?= e($user['dibuat_pada']) ?></td>
                        <td class="text-end">
                            <details class="admin-row-actions">
                                <summary><i class="bi bi-sliders2 me-1"></i>Kelola</summary>
                                <div class="admin-actions-panel">
                                    <h3>Akses akun</h3>
                                    <form method="POST" class="admin-action-form" onsubmit="return confirm('Yakin mengubah role akun ini?')">
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
                                    <form method="POST" class="admin-action-form" onsubmit="return confirm('<?= $user['status'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?> akun ini?')">
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
                                        <form method="POST" class="admin-action-form admin-password-form" onsubmit="return confirm('Simpan password baru untuk akun ini? Password lama tidak dapat dipulihkan.')">
                                            <input type="hidden" name="csrf_token" value="<?= e($data['csrf']) ?>">
                                            <input type="hidden" name="aksi" value="reset_password">
                                            <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                            <?php foreach ($returnFilters as $key => $value): ?><input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>"><?php endforeach; ?>
                                            <label for="password-<?= (int) $user['id'] ?>">Kata sandi baru</label>
                                            <input class="form-control form-control-sm" id="password-<?= (int) $user['id'] ?>" type="password" name="password_baru" minlength="8" maxlength="200" placeholder="Minimal 8 karakter" autocomplete="new-password" required>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-admin-filter flex-grow-1" name="mode" value="manual" type="submit">Ubah kata sandi</button>
                                                <button class="btn btn-sm btn-admin-quiet flex-grow-1" name="mode" value="generate" type="submit" formnovalidate>Buat acak</button>
                                            </div>
                                            <small>Kata sandi tidak ditampilkan setelah disimpan.</small>
                                        </form>
                                    <?php endif; ?>

                                    <h3 class="mt-3">Penghapusan</h3>
                                    <?php if ($user['related_tables']): ?>
                                        <button class="btn btn-sm btn-outline-danger" type="button" disabled title="Akun memiliki data terkait; nonaktifkan akun saja.">Hapus permanen</button>
                                        <p class="admin-action-note">Akun memiliki data: <?= e(implode(', ', $user['related_tables'])) ?>.</p>
                                    <?php else: ?>
                                        <form method="POST" onsubmit="return confirm('Hapus permanen akun ini? Tindakan ini tidak bisa dibatalkan.')">
                                            <input type="hidden" name="csrf_token" value="<?= e($data['csrf']) ?>">
                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                            <?php foreach ($returnFilters as $key => $value): ?><input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>"><?php endforeach; ?>
                                            <button class="btn btn-sm btn-outline-danger" type="submit" <?= $self ? 'disabled' : '' ?>>Hapus permanen</button>
                                            <?php if ($self): ?><small class="admin-action-note d-block mt-1">Akun sendiri tidak dapat dihapus.</small><?php endif; ?>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
