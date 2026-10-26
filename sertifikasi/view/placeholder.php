<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../class/Auth.php';

if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit();
}
$role = $_SESSION['role'];
$mustChangePassword = (new Auth())->mustChangePassword((int) $_SESSION['id_user']);
if ($mustChangePassword) {
    header('Location: ../controller/user/ProfileController.php?tab=profil&must_change=1');
    exit();
}
$isInstructor = $role === 'instruktur';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isInstructor ? 'Dashboard Instruktur' : 'Dashboard ' . e(ucfirst($role)) ?> | Sertifikasi Academy</title>
    <?php if ($isInstructor): ?>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
        <link href="../asset/css/theme-soft.css" rel="stylesheet">
    <?php else: ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="../asset/css/theme-soft.css" rel="stylesheet">
    <?php endif; ?>
</head>
<?php if ($isInstructor): ?>
<body class="soft-app soft-dashboard">
    <header class="soft-topbar">
        <a class="soft-brand" href="placeholder.php" aria-label="Sertifikasi Academy, dashboard instruktur">
            <span class="soft-brand-mark" aria-hidden="true"><i class="bi bi-mortarboard-fill"></i></span>
            <span>Sertifikasi <strong>Academy</strong></span>
        </a>

        <nav class="soft-nav" aria-label="Navigasi instruktur">
            <a class="soft-nav-link is-active" href="placeholder.php" aria-current="page">
                <i class="bi bi-grid-1x2-fill" aria-hidden="true"></i>Dashboard
            </a>
            <a class="soft-nav-link" href="#analytics"><i class="bi bi-graph-up" aria-hidden="true"></i>Analitik</a>
            <a class="soft-nav-link" href="#courses"><i class="bi bi-journal-richtext" aria-hidden="true"></i>Kursus</a>
            <a class="soft-nav-link" href="#students"><i class="bi bi-people" aria-hidden="true"></i>Siswa</a>
        </nav>

        <div class="soft-user-tools">
            <label class="soft-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <span class="visually-hidden">Cari kursus</span>
                <input type="search" placeholder="Cari kursus..." disabled>
            </label>
            <button class="soft-icon-button" type="button" aria-label="Notifikasi belum tersedia" title="Notifikasi belum tersedia" disabled>
                <i class="bi bi-bell" aria-hidden="true"></i>
            </button>
            <a class="soft-user" href="../controller/user/ProfileController.php?tab=profil" aria-label="Buka profil <?= e($_SESSION['username']) ?>">
                <span class="soft-avatar" aria-hidden="true"><?= e(strtoupper(mb_substr((string) $_SESSION['username'], 0, 1))) ?></span>
                <span class="soft-user-name"><?= e($_SESSION['username']) ?><small>Instruktur</small></span>
            </a>
            <a class="soft-logout" href="../controller/AuthController.php?aksi=logout" aria-label="Keluar">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>Keluar</span>
            </a>
        </div>
    </header>

    <main class="soft-main">
        <section class="soft-welcome">
            <div>
                <h1>Selamat pagi, <?= e($_SESSION['username']) ?></h1>
                <p>Ringkasan aktivitas kursus dan perkembangan belajar siswa.</p>
            </div>
            <span class="soft-date"><i class="bi bi-calendar3" aria-hidden="true"></i><?= e(date('d M Y')) ?></span>
        </section>

        <section class="soft-stats-grid" aria-label="Ringkasan performa">
            <article class="soft-card soft-stat-card">
                <div class="soft-stat-icon icon-violet"><i class="bi bi-journals" aria-hidden="true"></i></div>
                <p>Total kursus</p>
                <strong aria-label="Belum tersedia">—</strong>
                <span>Data kursus instruktur belum terhubung</span>
            </article>
            <article class="soft-card soft-stat-card">
                <div class="soft-stat-icon icon-yellow"><i class="bi bi-people" aria-hidden="true"></i></div>
                <p>Total siswa</p>
                <strong aria-label="Belum tersedia">—</strong>
                <span>Data pendaftaran belum terhubung</span>
            </article>
            <article class="soft-card soft-stat-card">
                <div class="soft-stat-icon icon-peach"><i class="bi bi-award" aria-hidden="true"></i></div>
                <p>Rata-rata nilai kuis</p>
                <strong aria-label="Belum tersedia">—</strong>
                <span>Data nilai belum tersedia</span>
            </article>
            <article class="soft-card soft-stat-card">
                <div class="soft-stat-icon icon-mint"><i class="bi bi-patch-check" aria-hidden="true"></i></div>
                <p>Sertifikat terbit</p>
                <strong aria-label="Belum tersedia">—</strong>
                <span>Data sertifikat belum terhubung</span>
            </article>
        </section>

        <section class="soft-content-grid" id="analytics" aria-label="Analitik pembelajaran">
            <article class="soft-card soft-chart-card">
                <div class="soft-card-heading">
                    <div>
                        <h2>Pendaftar dan kelulusan</h2>
                        <p>Tren bulanan · ilustrasi data sementara</p>
                    </div>
                    <span class="soft-period"><i class="bi bi-bar-chart-line" aria-hidden="true"></i>12 bulan</span>
                </div>
                <div class="soft-chart-legend" aria-label="Legenda grafik">
                    <span><i class="legend-dot legend-purple"></i>Pendaftar</span>
                    <span><i class="legend-dot legend-yellow"></i>Lulus</span>
                </div>
                <div class="soft-chart-wrap">
                    <canvas id="instructorEnrollmentChart" role="img" aria-label="Grafik ilustrasi jumlah pendaftar dan siswa lulus per bulan"></canvas>
                </div>
                <p class="soft-chart-note"><i class="bi bi-info-circle" aria-hidden="true"></i>Grafik ini memakai data contoh dan belum berasal dari database.</p>
            </article>

            <article class="soft-card soft-completion-card">
                <div class="soft-card-heading">
                    <div>
                        <h2>Penyelesaian kursus</h2>
                        <p>Completion rate keseluruhan</p>
                    </div>
                    <span class="soft-heading-icon"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                </div>
                <div class="soft-gauge" role="img" aria-label="Completion rate belum tersedia">
                    <svg viewBox="0 0 240 140" aria-hidden="true">
                        <path class="soft-gauge-track" d="M 28 120 A 92 92 0 0 1 212 120"></path>
                    </svg>
                    <div class="soft-gauge-value">—<small>Belum ada data</small></div>
                </div>
                <div class="soft-gauge-foot">
                    <span><i class="legend-dot legend-purple"></i>Progress siswa</span>
                    <strong>Data belum tersedia</strong>
                </div>
            </article>

            <article class="soft-card soft-progress-card">
                <div class="soft-card-heading">
                    <div>
                        <h2>Penyelesaian materi</h2>
                        <p>Persentase siswa yang menuntaskan materi</p>
                    </div>
                    <i class="bi bi-book-half soft-muted-icon" aria-hidden="true"></i>
                </div>
                <div class="soft-progress-summary">
                    <strong>—</strong>
                    <span>Belum ada data progres</span>
                </div>
                <div class="soft-progress-track" role="progressbar" aria-label="Progres penyelesaian materi belum tersedia" aria-valuemin="0" aria-valuemax="100">
                    <span></span>
                </div>
                <p class="soft-empty-hint">Data akan tampil setelah progres materi terhubung ke dashboard instruktur.</p>
            </article>

            <article class="soft-card soft-students-card" id="students">
                <div class="soft-card-heading">
                    <div>
                        <h2>Siswa terbaru</h2>
                        <p>Aktivitas terbaru di kursusmu</p>
                    </div>
                    <a class="soft-text-link" href="#courses">Lihat kursus</a>
                </div>
                <div class="soft-empty-state">
                    <span class="soft-empty-icon"><i class="bi bi-person-lines-fill" aria-hidden="true"></i></span>
                    <strong>Belum ada data siswa</strong>
                    <span>Daftar siswa dan status belajarnya akan muncul di sini.</span>
                </div>
            </article>

            <article class="soft-card soft-courses-card" id="courses">
                <div class="soft-card-heading">
                    <div>
                        <h2>Kursus saya</h2>
                        <p>Kelola materi dan publikasi kursus</p>
                    </div>
                    <div class="soft-course-actions" aria-label="Aksi kursus belum tersedia">
                        <button class="soft-button soft-button-outline" type="button" disabled><i class="bi bi-pencil" aria-hidden="true"></i>Edit</button>
                        <button class="soft-button soft-button-outline" type="button" disabled><i class="bi bi-trash3" aria-hidden="true"></i>Hapus</button>
                        <button class="soft-button soft-button-primary" type="button" disabled><i class="bi bi-send" aria-hidden="true"></i>Publish</button>
                    </div>
                </div>
                <div class="soft-table-wrap">
                    <table class="soft-table">
                        <thead>
                            <tr><th scope="col">Nama kursus</th><th scope="col">Siswa</th><th scope="col">Status</th><th scope="col">Aksi</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4">
                                    <div class="soft-empty-state soft-table-empty">
                                        <span class="soft-empty-icon"><i class="bi bi-journal-x" aria-hidden="true"></i></span>
                                        <strong>Belum ada kursus yang terhubung</strong>
                                        <span>Kursus milik instruktur akan tampil di sini setelah relasi data tersedia.</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>

        <footer class="soft-footer">
            <span>Sertifikasi Academy</span>
            <span>Dashboard instruktur</span>
        </footer>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
    <script src="../asset/js/instructor-dashboard.js"></script>
</body>
<?php else: ?>
<body class="soft-app soft-placeholder">
    <div>
        <h1 class="h3">Dashboard <?= e($role) ?> belum dibuat</h1>
        <p class="text-muted">Login sebagai <strong><?= e($_SESSION['username']) ?></strong> berhasil. Role terbaca dari database: <code><?= e($role) ?></code>.</p>
        <a class="btn btn-primary me-2" href="../controller/user/ProfileController.php?tab=profil">Profil &amp; ganti password</a>
        <a class="btn btn-outline-dark" href="../controller/AuthController.php?aksi=logout">Logout</a>
    </div>
</body>
<?php endif; ?>
</html>
