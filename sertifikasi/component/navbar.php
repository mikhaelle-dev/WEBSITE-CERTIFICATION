<?php
/**
 * Navbar siswa (dipakai dashboard & profil).
 * Butuh: $data['username'] dan (opsional) $aktif = 'mylearning' | 'profil'
 * Pastikan config/helpers.php sudah di-require oleh controller.
 */
$_menu       = require __DIR__ . '/../config/dummy_data.php';   // nanti: query kategori & kursus populer
$menuKursus  = array_slice($_menu['kursus'], 0, 3);
$menuKategori = $_menu['kategori'];
$aktif       = $aktif ?? 'mylearning';
$inisial     = strtoupper(substr($data['username'], 0, 1));
$role        = $data['role'] ?? 'siswa';
$dashboardPath = $role === 'admin'
    ? '../admin/DashboardController.php'
    : ($role === 'instruktur' ? '../../view/placeholder.php' : 'DashboardController.php');
$logoutPath = '../AuthController.php?aksi=logout';
?>
<nav class="navbar navbar-expand-lg sticky-top topnav">
    <div class="container-fluid px-4">
        <a class="navbar-brand brand" href="<?= e($dashboardPath) ?>">
            <span class="brand-small">Sertifikasi</span><br><strong>Academy</strong>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">

            <!-- EXPLORE (mega menu) -->
            <?php if ($role === 'siswa'): ?>
            <div class="dropdown me-3">
                <button class="btn btn-explore dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <i class="bi bi-grid-3x3-gap-fill"></i> Explore
                </button>
                <div class="dropdown-menu mega p-0">
                    <div class="row g-0">
                        <div class="col-md-4 mega-left">
                            <div class="mega-head">Bidang</div>
                            <?php foreach ($menuKategori as $kat): ?>
                                <a class="mega-link" href="DashboardController.php?kategori=<?= urlencode($kat) ?>#rekomendasi">
                                    <?= e($kat) ?> <i class="bi bi-chevron-right"></i>
                                </a>
                            <?php endforeach; ?>
                            <a class="btn btn-enroll w-100 mt-3" href="DashboardController.php#rekomendasi">Lihat semua kursus</a>
                        </div>
                        <div class="col-md-8 mega-right">
                            <div class="mega-head">Ambil kursus gratis</div>
                            <p class="small text-muted">Pelajari topik jaringan, keamanan siber, dan pemrograman, lalu dapatkan sertifikat setelah lolos asesmen.</p>
                            <?php foreach ($menuKursus as $mk): ?>
                                <a class="mega-course" href="DashboardController.php?kategori=<?= urlencode($mk['kategori']) ?>#rekomendasi">
                                    <div class="mega-thumb"><i class="bi bi-play-circle-fill"></i></div>
                                    <div>
                                        <small class="text-muted"><?= e($mk['tipe']) ?> &middot; <?= e($mk['level']) ?></small>
                                        <div class="fw-bold"><?= e($mk['judul']) ?></div>
                                        <small class="text-muted"><i class="bi bi-people"></i> <?= number_format($mk['peserta'], 0, ',', '.') ?> sudah mendaftar</small>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($role === 'siswa'): ?>
            <form class="search-box flex-grow-1 me-3" action="DashboardController.php" method="GET">
                <i class="bi bi-search"></i>
                <input type="search" name="q" placeholder="Cari kursus..." class="form-control" value="<?= e($data['q'] ?? '') ?>">
            </form>
            <?php endif; ?>

            <ul class="navbar-nav align-items-lg-center ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $aktif === 'mylearning' ? 'nav-active' : '' ?>" href="<?= e($dashboardPath) ?>"><i class="bi bi-house-door me-1"></i><?= $role === 'admin' ? 'Dashboard Admin' : ($role === 'instruktur' ? 'Dashboard Instruktur' : 'Home') ?></a>
                </li>
                <li class="nav-item ms-lg-4">
                    <!-- klik avatar -> buka drawer -->
                    <button class="user-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#userDrawer" aria-controls="userDrawer">
                        <?php if (!empty($data['foto_profil'])): ?>
                            <img class="avatar" src="../../<?= e($data['foto_profil']) ?>" alt="Foto profil">
                        <?php else: ?>
                            <span class="avatar"><?= e($inisial) ?></span>
                        <?php endif; ?>
                        <span class="ms-2 lh-1 text-start">
                            <span class="d-block fw-bold small"><?= e(strtoupper($data['username'])) ?></span>
                            <span class="d-block text-muted tiny"><?= e(ucfirst($role)) ?></span>
                        </span>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- DRAWER USER -->
<div class="offcanvas offcanvas-end drawer" tabindex="-1" id="userDrawer" aria-labelledby="userDrawerLabel">
    <div class="drawer-head">
        <?php if (!empty($data['foto_profil'])): ?>
            <img class="avatar avatar-lg" src="../../<?= e($data['foto_profil']) ?>" alt="Foto profil">
        <?php else: ?>
            <span class="avatar avatar-lg"><?= e($inisial) ?></span>
        <?php endif; ?>
        <div class="flex-grow-1 fw-semibold" id="userDrawerLabel"><?= e(strtoupper($data['username'])) ?></div>
    </div>

    <div class="offcanvas-body p-0">
        <div class="drawer-sec">
            <div class="drawer-title"><i class="bi bi-speedometer2"></i> Dashboard</div>
            <a class="drawer-link" href="<?= e($dashboardPath) ?>"><?= $role === 'admin' ? 'Dashboard Admin' : ($role === 'instruktur' ? 'Dashboard Instruktur' : 'Home') ?> <i class="bi bi-chevron-right"></i></a>
        </div>

        <div class="drawer-sec">
            <div class="drawer-title"><i class="bi bi-person"></i> Profil</div>
            <a class="drawer-link" href="ProfileController.php?tab=profil">Update Profil <i class="bi bi-chevron-right"></i></a>
            <?php if ($role === 'siswa'): ?>
                <a class="drawer-link" href="ProfileController.php?tab=sertifikat">Sertifikat &amp; Lencana <i class="bi bi-chevron-right"></i></a>
                <a class="drawer-link" href="ProfileController.php?tab=riwayat">Riwayat Belajar <i class="bi bi-chevron-right"></i></a>
            <?php endif; ?>
        </div>

        <div class="drawer-sec">
            <div class="drawer-title"><i class="bi bi-chat-dots"></i> Bantuan</div>
            <a class="drawer-link" href="#">Support <i class="bi bi-chevron-right"></i></a>
            <a class="drawer-link" href="#">Feedback <i class="bi bi-chevron-right"></i></a>
        </div>

        <div class="p-3">
            <a class="btn btn-outline-dark rounded-pill px-4" href="<?= e($logoutPath) ?>"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </div>
</div>
