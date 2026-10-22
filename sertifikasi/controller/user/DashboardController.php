<?php
/**
 * Dashboard Siswa
 * Controller: cek sesi -> siapkan data -> include view
 */
session_start();

require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../class/Course.php';

// ---------------------------------------------------------------
// MODE DEMO: set false kalau login.php sudah jadi.
// Selama true, halaman bisa dibuka tanpa login (pakai user dummy).
// ---------------------------------------------------------------
$DEV_MODE = false;   // login sungguhan sudah jadi (set true hanya untuk demo tanpa database)

if ($DEV_MODE && !isset($_SESSION['logged_in'])) {
    $_SESSION['logged_in'] = true;
    $_SESSION['username']  = 'mikhael';
    $_SESSION['role']      = 'siswa';
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'siswa') {
    header('Location: ../../view/login.php');
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../../view/login.php');
    exit();
}

// ---------------------------------------------------------------
// DATA DUMMY -> nanti diganti query class Course / Enrollment
// ---------------------------------------------------------------
$dummy = require __DIR__ . '/../../config/dummy_data.php';
$courseModel = new Course();
$userId = (int) $_SESSION['id_user'];
$activeCourse = $courseModel->getActiveEnrollment($userId);
$completedCourseSlugs = $courseModel->getCompletedCourseSlugs($userId);
$kursusSaya = [];

if ($activeCourse !== null) {
    $progress = $courseModel->getProgress($userId, (int) $activeCourse['course_id']);
    $kursusSaya[] = [
        'id' => (int) $activeCourse['course_id'],
        'slug' => $activeCourse['slug'],
        'kategori' => $activeCourse['kategori'],
        'judul' => $activeCourse['judul'],
        'level' => $activeCourse['level'],
        'tipe' => 'Self-paced',
        'durasi' => $activeCourse['durasi'],
        'progress' => $progress['persentase'],
        'resume_sublesson_id' => $courseModel->getResumeSublessonId($userId, (int) $activeCourse['course_id']),
    ];
}

// Filter bidang dari menu Explore (whitelist, bukan langsung dipakai)
$kategori    = isset($_GET['kategori']) && is_string($_GET['kategori']) ? $_GET['kategori'] : '';
$q           = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
if ($kategori !== '' && !in_array($kategori, $dummy['kategori'], true)) {
    $kategori = '';
}
$rekomendasi = $dummy['kursus'];
$rekomendasi = array_values(array_filter($rekomendasi, function ($course) use ($completedCourseSlugs, $kategori, $q) {
    if (isset($course['slug']) && in_array($course['slug'], $completedCourseSlugs, true)) {
        return false;
    }
    if ($kategori !== '' && $course['kategori'] !== $kategori) {
        return false;
    }
    if ($q !== '' && stripos($course['judul'] . ' ' . $course['kategori'] . ' ' . $course['level'], $q) === false) {
        return false;
    }
    return true;
}));

$data = [
    'username'    => $_SESSION['username'],
    'foto_profil' => $_SESSION['foto_profil'] ?? null,
    'role'        => $_SESSION['role'],
    'kursusSaya'  => $kursusSaya,
    'rekomendasi' => $rekomendasi,
    'kategori'    => $kategori,
    'q'           => $q,
    'pencapaian'  => array_slice($courseModel->getAchievements($userId), 0, 3),
];

include __DIR__ . '/../../view/user/dashboard.php';
