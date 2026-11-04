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
        $returnClassId = filter_var($_POST['return_class_id'] ?? null, FILTER_VALIDATE_INT);
        $result = $key !== ''
            ? $classModel->enrollStudentByKey((int) $_SESSION['id_user'], $key)
            : ($returnClassId
                ? $classModel->enrollStudentInPublicClass((int) $_SESSION['id_user'], (int) $returnClassId)
                : null);
        if ($result === null) {
            $_SESSION['student_error'] = $key !== ''
                ? 'Kode enroll tidak valid atau kelas tidak bisa diakses.'
                : 'Kelas publik tidak tersedia untuk diikuti.';
        } else {
            if ($returnClassId && $returnClassId === (int) $result['id']) {
                $_SESSION['classroom_success'] = 'Kamu berhasil bergabung ke kelas ' . $result['judul'] . '.';
                header('Location: ClassroomController.php?class_id=' . (int) $result['id'] . '&tab=overview');
                exit();
            }
            $_SESSION['student_success'] = 'Kamu berhasil bergabung ke kelas ' . $result['judul'] . '.';
        }
    }

    header('Location: DashboardController.php');
    exit();
}

$courseModel = new Course();
$instructorClassModel = new InstructorClass();
$userId = (int) $_SESSION['id_user'];
$activeCourse = $courseModel->getActiveEnrollment($userId);
$kursusSaya = [];

if ($activeCourse !== null) {
    $progress = $courseModel->getProgress($userId, (int) $activeCourse['course_id']);
    $kursusSaya[] = [
        'id' => (int) $activeCourse['course_id'],
        'slug' => $activeCourse['slug'],
        'kategori' => $activeCourse['kategori'],
        'cover_image' => $courseModel->getCoverImage((string) $activeCourse['kategori']),
        'judul' => $activeCourse['judul'],
        'level' => $activeCourse['level'],
        'tipe' => 'Self-paced',
        'durasi' => $activeCourse['durasi'],
        'progress' => $progress['persentase'],
        'resume_sublesson_id' => $courseModel->getResumeSublessonId($userId, (int) $activeCourse['course_id']),
    ];
}

$kategori = isset($_GET['kategori']) && is_string($_GET['kategori']) ? $_GET['kategori'] : '';
$q = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$rekomendasi = $courseModel->getRecommendations($userId, $kategori, $q);
$exploreCourses = $kategori === '' && $q === ''
    ? array_slice($rekomendasi, 0, 3)
    : array_slice($courseModel->getRecommendations($userId), 0, 3);
$kategoriKursus = $courseModel->getCategories();
$kelasSaya = $instructorClassModel->getStudentDashboardClasses($userId);
$kelasTersedia = $instructorClassModel->getAvailableClassesForStudent($userId);
$pencapaian = array_merge(
    $courseModel->getAchievements($userId),
    $instructorClassModel->getStudentClassBadges($userId),
    $instructorClassModel->getStudentClassCertificates($userId)
);
usort($pencapaian, static function (array $left, array $right): int {
    return strtotime((string) ($right['tanggal'] ?? '')) <=> strtotime((string) ($left['tanggal'] ?? ''));
});

$data = [
    'username'    => $_SESSION['username'],
    'foto_profil' => $_SESSION['foto_profil'] ?? null,
    'role'        => $_SESSION['role'],
    'kursusSaya'  => $kursusSaya,
    'kelasSaya'   => $kelasSaya,
    'kelasTersedia' => $kelasTersedia,
    'rekomendasi' => $rekomendasi,
    'exploreCourses' => $exploreCourses,
    'kategoriKursus' => $kategoriKursus,
    'kategori'    => $kategori,
    'q'           => $q,
    'pencapaian'  => array_slice($pencapaian, 0, 3),
    'student_success' => $_SESSION['student_success'] ?? '',
    'student_error' => $_SESSION['student_error'] ?? '',
];
unset($_SESSION['student_success'], $_SESSION['student_error']);

include __DIR__ . '/../../view/user/dashboard.php';
