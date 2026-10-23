<?php
// View: HANYA tampilan. Data dari ProfileController lewat $data.
$tab = $data['tab'];
$p   = $data['profil'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" rel="stylesheet">
    <link href="../../asset/css/dashboard.css" rel="stylesheet">
    <link href="../../asset/css/profile.css" rel="stylesheet">
</head>
<body>

<?php $aktif = 'profil'; include __DIR__ . '/../../component/navbar.php'; ?>

<!-- BANNER PROFIL -->
<header class="pf-banner">
    <div class="container-fluid px-4 px-lg-5 d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <form method="POST" action="ProfileController.php?tab=profil" id="profilePhotoForm" class="profile-photo-form" enctype="multipart/form-data">
                <input type="hidden" name="aksi" value="simpan_foto">
                <input type="hidden" name="csrf_token" value="<?= e($data['csrf']) ?>">
                <?php if ($data['foto_profil']): ?>
                    <img class="avatar avatar-xl" src="../../<?= e($data['foto_profil']) ?>" alt="Foto profil">
                <?php else: ?>
                    <span class="avatar avatar-xl"><?= e(strtoupper(substr($data['username'], 0, 1))) ?></span>
                <?php endif; ?>
                <label class="profile-photo-edit" for="foto_profil" id="editProfilePhoto" role="button" tabindex="0" aria-label="Ubah foto profil" title="Ubah foto profil">
                    <i class="bi bi-pencil-fill"></i>
                </label>
                <input class="visually-hidden" type="file" id="foto_profil" name="foto_profil" accept="image/jpeg,image/png,image/webp">
            </form>
            <div>
                <small class="d-block opacity-75">Selamat datang,</small>
                <h1 class="pf-name"><?= e(strtoupper($data['namaLengkap'])) ?></h1>
                <small class="opacity-75"><?= e(ucfirst($data['role'])) ?></small>
            </div>
        </div>
        <?php if ($data['role'] === 'siswa'): ?>
        <div class="pf-stats">
            <div><i class="bi bi-award"></i> <span class="num"><?= (int) $data['totalSertifikat'] ?></span><small>Sertifikat</small></div>
            <div class="sep"></div>
            <div><i class="bi bi-book"></i> <span class="num"><?= (int) $data['totalSelesai'] ?></span><small>Kursus selesai</small></div>
        </div>
        <?php endif; ?>
    </div>
</header>

<main class="container pf-main">

    <!-- TAB -->
    <ul class="pf-tabs">
        <li><a class="<?= $tab === 'profil' ? 'on' : '' ?>" href="?tab=profil"><i class="bi bi-person"></i> Profil</a></li>
        <?php if ($data['role'] === 'siswa'): ?>
            <li><a class="<?= $tab === 'sertifikat' ? 'on' : '' ?>" href="?tab=sertifikat"><i class="bi bi-award"></i> Sertifikat &amp; Lencana</a></li>
            <li><a class="<?= $tab === 'riwayat' ? 'on' : '' ?>" href="?tab=riwayat"><i class="bi bi-clock-history"></i> Riwayat Belajar</a></li>
        <?php endif; ?>
    </ul>

    <?php if ($tab === 'profil'): ?>
    <!-- ============ TAB PROFIL ============ -->
        <?php if ($data['pesan'] !== ''): ?>
            <div class="alert alert-success py-2"><?= e($data['pesan']) ?></div>
        <?php endif; ?>
        <?php if ($data['errors']): ?>
            <div class="alert alert-danger py-2 mb-3">
                <ul class="m-0 ps-3"><?php foreach ($data['errors'] as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <div class="pf-card">
            <h2 class="pf-h">Informasi Dasar</h2>
            <form method="POST" action="ProfileController.php?tab=profil" id="profileInfoForm">
                <input type="hidden" name="aksi" value="simpan_profil">
                <input type="hidden" name="csrf_token" value="<?= e($data['csrf']) ?>">
            <div class="row g-3">
                <div class="col-md-6 col-lg-3">
                    <label class="form-label">Nama depan *</label>
                    <input class="form-control" name="nama_depan" maxlength="50" required value="<?= e($p['nama_depan']) ?>">
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label">Nama belakang</label>
                    <input class="form-control" name="nama_belakang" maxlength="50" value="<?= e($p['nama_belakang']) ?>">
                </div>
                <div class="col-md-4 col-lg-2">
                    <label class="form-label">Bahasa</label>
                    <select class="form-select" name="bahasa">
                        <?php foreach ($data['opsiBahasa'] as $b): ?>
                            <option <?= $p['bahasa'] === $b ? 'selected' : '' ?>><?= e($b) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 col-lg-2">
                    <label class="form-label">Negara *</label>
                    <input class="form-control" name="negara" maxlength="100" required value="<?= e($p['negara']) ?>">
                </div>
                <div class="col-md-4 col-lg-2">
                    <label class="form-label">Provinsi</label>
                    <input class="form-control" name="provinsi" maxlength="100" value="<?= e($p['provinsi']) ?>">
                </div>
            </div>

            <h2 class="pf-h mt-4">Kontak</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input class="form-control" value="<?= e($p['email']) ?>" disabled>
                    <small class="text-muted">Email dipakai untuk login dan tidak bisa diubah di sini.</small>
                </div>
            </div>

            <h2 class="pf-h mt-4">Karier</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Tujuan karier</label>
                    <select class="form-select" name="tujuan_karier">
                        <option value="">Pilih...</option>
                        <?php foreach ($data['opsiKarier'] as $k): ?>
                            <option <?= $p['tujuan_karier'] === $k ? 'selected' : '' ?>><?= e($k) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Opsional. Dipakai untuk rekomendasi kursus.</small>
                </div>
            </div>

            <button class="btn btn-enroll mt-4 px-4" type="submit">Simpan</button>
            </form>
        </div>

        <div class="pf-card mt-4">
            <h2 class="pf-h">Ganti password</h2>
            <p class="text-muted">Masukkan password saat ini untuk memastikan perubahan berasal dari pemilik akun.</p>
            <form method="POST" action="ProfileController.php?tab=profil" autocomplete="off">
                <input type="hidden" name="aksi" value="ubah_password">
                <input type="hidden" name="csrf_token" value="<?= e($data['csrf']) ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="password_lama">Password saat ini</label>
                        <input class="form-control" type="password" id="password_lama" name="password_lama" autocomplete="current-password" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="password_baru">Password baru</label>
                        <input class="form-control" type="password" id="password_baru" name="password_baru" minlength="8" maxlength="200" autocomplete="new-password" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="konfirmasi_password">Ulangi password baru</label>
                        <input class="form-control" type="password" id="konfirmasi_password" name="konfirmasi_password" minlength="8" maxlength="200" autocomplete="new-password" required>
                    </div>
                </div>
                <button class="btn btn-enroll mt-4 px-4" type="submit">Simpan password baru</button>
            </form>
        </div>

        <div class="modal fade" id="profilePhotoCropModal" tabindex="-1" aria-labelledby="profilePhotoCropTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="profilePhotoCropTitle">Atur foto profil</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger d-none" id="profilePhotoCropError" role="alert"></div>
                        <div class="profile-crop-stage">
                            <img id="profilePhotoCropImage" alt="Potong foto profil">
                        </div>
                        <p class="text-muted small mt-3 mb-0">Geser foto untuk mengatur posisi. Foto akan dipotong menjadi persegi.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-enroll" id="applyProfilePhotoCrop">
                            <i class="bi bi-check2"></i> Gunakan foto
                        </button>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($tab === 'sertifikat'): ?>
    <!-- ============ TAB SERTIFIKAT & LENCANA ============ -->
        <?php $kosongSertifikat = !$data['pencapaian'] && $data['jenis'] === 'Semua' && $data['q'] === ''; ?>
        <?php if (!$kosongSertifikat): ?>
        <form method="GET" class="pf-filter">
            <input type="hidden" name="tab" value="sertifikat">
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="search" name="q" class="form-control" placeholder="Cari judul kursus" value="<?= e($data['q']) ?>">
            </div>
            <select class="form-select w-auto" name="jenis" onchange="this.form.submit()">
                <?php foreach ($data['jenisValid'] as $j): ?>
                    <option <?= $data['jenis'] === $j ? 'selected' : '' ?>><?= e($j) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>

        <?php if ($kosongSertifikat): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-patch-check fs-1 text-success"></i>
                <h3 class="h5 mt-3 text-dark">Belum ada sertifikat</h3>
                <p>Selesaikan kursus dan lulus asesmennya untuk dapat sertifikat dan lencana pertamamu.</p>
                <a class="btn btn-enroll px-4" href="DashboardController.php#rekomendasi">Mulai belajar</a>
            </div>
        <?php elseif (!$data['pencapaian']): ?>
            <p class="text-muted mt-4">Tidak ada yang cocok dengan pencarianmu.</p>
        <?php endif; ?>

        <div class="row g-3 mt-1">
            <?php foreach ($data['pencapaian'] as $a): ?>
            <div class="col-sm-6 col-lg-3">
                <?php if ($a['sertifikat_id'] !== null): ?>
                    <a class="ach-card ach-card-link ach-certificate-card" href="CertificateController.php?id=<?= (int) $a['sertifikat_id'] ?>">
                <?php else: ?>
                    <article class="ach-card ach-badge-card">
                <?php endif; ?>
                    <span class="ach-tag"><?= e($a['jenis']) ?></span>
                    <span class="ach-medallion"><i class="bi <?= e($a['ikon']) ?> ach-icon"></i></span>
                    <div class="ach-title"><?= e($a['judul']) ?></div>
                    <div class="ach-course-name"><?= e($a['kursus_judul']) ?></div>
                    <?php if ($a['sertifikat_id'] !== null): ?>
                        <span class="ach-action">Lihat sertifikat <i class="bi bi-arrow-up-right"></i></span>
                    <?php else: ?>
                        <span class="ach-description"><?= e($a['deskripsi']) ?></span>
                    <?php endif; ?>
                    <div class="ach-date"><?= $a['jenis'] === 'Sertifikat' ? 'Terbit' : 'Diraih' ?>: <?= e(tgl_indo($a['tanggal'])) ?></div>
                <?php if ($a['sertifikat_id'] !== null): ?>
                    </a>
                <?php else: ?>
                    </article>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>

    <?php else: ?>
    <!-- ============ TAB RIWAYAT BELAJAR ============ -->
        <?php $kosongRiwayat = !$data['riwayat'] && $data['statusBelajar'] === 'Semua' && $data['q'] === ''; ?>
        <?php if (!$kosongRiwayat): ?>
        <form method="GET" class="pf-filter">
            <input type="hidden" name="tab" value="riwayat">
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="search" name="q" class="form-control" placeholder="Cari kursus" value="<?= e($data['q']) ?>">
            </div>
            <select class="form-select w-auto" name="status_belajar" onchange="this.form.submit()">
                <?php foreach ($data['statusValid'] as $s): ?>
                    <option <?= $data['statusBelajar'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>

        <?php if ($kosongRiwayat): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-clock-history fs-1 text-success"></i>
                <h3 class="h5 mt-3 text-dark">Belum ada riwayat belajar</h3>
                <p>Kursus yang kamu ikuti akan muncul di sini.</p>
                <a class="btn btn-enroll px-4" href="DashboardController.php#rekomendasi">Jelajahi kursus</a>
            </div>
        <?php elseif (!$data['riwayat']): ?>
            <p class="text-muted mt-4">Tidak ada kursus yang cocok dengan pencarianmu.</p>
        <?php endif; ?>

        <?php foreach ($data['riwayat'] as $r): ?>
            <div class="hist-row">
                <div class="hist-main">
                    <div class="hist-thumb"><i class="bi bi-play-circle-fill"></i></div>
                    <div>
                        <small class="text-muted"><i class="bi bi-book"></i> Kursus &middot; <?= e($r['tipe']) ?></small>
                        <div class="fw-bold text-success"><?= e($r['judul']) ?></div>
                        <a href="CourseOverviewController.php?slug=<?= urlencode($r['slug'] ?? '') ?>" class="small">Lihat kursus</a>
                    </div>
                </div>
                <div class="hist-col"><small class="text-muted">Mulai</small><div class="fw-semibold"><?= e(tgl_indo($r['mulai_pada'] ?? $r['mulai'] ?? null)) ?></div></div>
                <div class="hist-col"><small class="text-muted">Selesai</small><div class="fw-semibold"><?= e(tgl_indo($r['selesai_pada'] ?? $r['selesai'] ?? null)) ?></div></div>
                <div class="hist-col">
                    <small class="text-muted">Status</small>
                    <div><span class="badge <?= $r['status'] === 'Selesai' ? 'text-bg-success' : 'text-bg-warning' ?>"><?= e($r['status']) ?></span></div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script src="../../asset/js/profile-photo.js?v=3"></script>
</body>
</html>
