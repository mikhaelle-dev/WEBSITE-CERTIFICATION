<?php
/**
 * Dashboard Siswa
 * Controller: cek sesi -> siapkan data -> include view
 */
require_once __DIR__ . '/../../config/session.php';

require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../class/Auth.php';
require_once __DIR__ . '/../../class/Course.php';
require_once __DIR__ . '/../../class/InstructorClass.php';

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

if (!isset($_SESSION['logged_in'], $_SESSION['id_user'], $_SESSION['role'])
    || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'siswa') {
    header('Location: ../../view/login.php');
    exit();
}

$auth = new Auth();
if ($auth->isRestrictedAccount((int) $_SESSION['id_user'])) {
    $_SESSION['account_restricted'] = true;
    header('Location: ../AccountDisabledController.php');
    exit();
}

if (!empty($_SESSION['account_restricted'])) {
    header('Location: ../AccountDisabledController.php');
    exit();
}
if ($auth->mustChangePassword((int) $_SESSION['id_user'])) {
    header('Location: ProfileController.php?tab=profil&must_change=1');
    exit();
}

if (isset($_GET['logout'])) {
    header('Location: ../AuthController.php?aksi=logout');
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $_SESSION['student_error'] = 'Sesi form tidak valid. Muat ulang halaman dan coba lagi.';
        header('Location: DashboardController.php');
        exit();
    }

    $action = $_POST['aksi'] ?? '';
    if ($action === 'join_class') {
        $key = trim((string) ($_POST['enroll_key'] ?? ''));
        $classModel = new InstructorClass();
        $result = $classModel->enrollStudentByKey((int) $_SESSION['id_user'], $key);
        if ($result === null) {
            $_SESSION['student_error'] = 'Enroll key tidak valid atau kelas tidak bisa diakses.';
        } else {
            $_SESSION['student_success'] = 'Kamu berhasil bergabung ke kelas ' . $result['judul'] . '.';
        }
    }

    header('Location: DashboardController.php');
    exit();
}

// ---------------------------------------------------------------
// DATA DUMMY -> nanti diganti query class Course / Enrollment
// ---------------------------------------------------------------
$dummy = require __DIR__ . '/../../config/dummy_data.php';
$courseModel = new Course();
$instructorClassModel = new InstructorClass();
$userId = (int) $_SESSION['id_user'];
$activeCourse = $courseModel->getActiveEnrollment($userId);
$completedCourseSlugs = $courseModel->getCompletedCourseSlugs($userId);
$activeCourseSlug = $activeCourse['slug'] ?? null;
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
$rekomendasi = array_values(array_filter($rekomendasi, function ($course) use ($completedCourseSlugs, $activeCourseSlug, $kategori, $q) {
    if (
        isset($course['slug'])
        && (in_array($course['slug'], $completedCourseSlugs, true) || $course['slug'] === $activeCourseSlug)
    ) {
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
    'kelasSaya'   => $instructorClassModel->getStudentDashboardClasses($userId),
    'rekomendasi' => $rekomendasi,
    'kategori'    => $kategori,
    'q'           => $q,
    'pencapaian'  => array_slice($courseModel->getAchievements($userId), 0, 3),
    'student_success' => $_SESSION['student_success'] ?? '',
    'student_error' => $_SESSION['student_error'] ?? '',
];
unset($_SESSION['student_success'], $_SESSION['student_error']);

include __DIR__ . '/../../view/user/dashboard.php';
