<?php
require_once __DIR__ . '/../../config/session.php';

require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../class/Auth.php';
require_once __DIR__ . '/../../class/Course.php';

if (
    !isset($_SESSION['logged_in'], $_SESSION['id_user'], $_SESSION['role'])
    || $_SESSION['logged_in'] !== true
    || $_SESSION['role'] !== 'siswa'
) {
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

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$slug = $_GET['slug'] ?? '';
if (!is_string($slug) || $slug === '') {
    http_response_code(404);
    exit('Kursus tidak ditemukan.');
}

$courseModel = new Course();
$course = $courseModel->getBySlug($slug);
if ($course === null) {
    http_response_code(404);
    exit('Kursus tidak ditemukan.');
}

$lessons = $courseModel->getLessons((int) $course['id']);
$quiz = $courseModel->getQuiz((int) $course['id']);
if (!$lessons || $quiz === null) {
    http_response_code(503);
    exit('Overview kursus belum tersedia karena materi atau kuis belum lengkap.');
}

$userId = (int) $_SESSION['id_user'];
$enrollment = $courseModel->getEnrollment($userId, (int) $course['id']);
$activeCourse = $courseModel->getActiveEnrollment($userId);
$error = '';
$badges = $courseModel->getCourseBadges((int) $course['id'], $userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'mulai_kursus') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Sesi form tidak valid. Muat ulang halaman dan coba lagi.';
    } elseif (!$courseModel->enroll($userId, (int) $course['id'])) {
        $activeCourse = $courseModel->getActiveEnrollment($userId);
        $error = 'Selesaikan kursus yang sedang berjalan sebelum memilih kursus lain.';
    } else {
        header('Location: CourseController.php?slug=' . urlencode($slug) . '&submateri=' . (int) $lessons[0]['submateri'][0]['id']);
        exit();
    }
}

$enrollment = $courseModel->getEnrollment($userId, (int) $course['id']);
$activeCourse = $courseModel->getActiveEnrollment($userId);
$sedangAktif = $activeCourse !== null && (int) $activeCourse['course_id'] === (int) $course['id'];
$sudahSelesai = $enrollment !== null && $enrollment['selesai_pada'] !== null;
$submateriLanjutan = $courseModel->getResumeSublessonId($userId, (int) $course['id']);
$certificate = $courseModel->getCourseCertificate((int) $course['id'], $userId);

$data = [
    'username' => $_SESSION['username'],
    'foto_profil' => $_SESSION['foto_profil'] ?? null,
    'course' => $course,
    'lessons' => $lessons,
    'quiz' => $quiz,
    'badges' => $badges,
    'certificate' => $certificate,
    'enrolled' => $enrollment !== null,
    'active' => $sedangAktif,
    'completed' => $sudahSelesai,
    'activeCourse' => $activeCourse,
    'resumeSublessonId' => $submateriLanjutan ?? (int) $lessons[0]['submateri'][0]['id'],
    'error' => $error,
    'csrf' => $_SESSION['csrf_token'],
];

include __DIR__ . '/../../view/user/course-overview.php';
