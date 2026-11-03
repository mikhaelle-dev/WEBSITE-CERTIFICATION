<?php
// Desain halaman kelas ini mengikuti pola Google Classroom agar siswa fokus pada stream, materi, dan kuis tanpa navigasi yang membingungkan. Tujuan utamanya adalah presentasi kuliah yang bersih, cepat dipahami, dan tetap fungsional.
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../class/Auth.php';
require_once __DIR__ . '/../../class/InstructorClass.php';

if (!isset($_SESSION['logged_in'], $_SESSION['id_user'], $_SESSION['role'])
    || $_SESSION['logged_in'] !== true
    || $_SESSION['role'] !== 'siswa') {
    header('Location: ../../view/login.php');
    exit();
}

$auth = new Auth();
$userId = (int) $_SESSION['id_user'];
if ($auth->isRestrictedAccount($userId)) {
    $_SESSION['account_restricted'] = true;
    header('Location: ../AccountDisabledController.php');
    exit();
}
if (!empty($_SESSION['account_restricted'])) {
    header('Location: ../AccountDisabledController.php');
    exit();
}
if ($auth->mustChangePassword($userId)) {
    header('Location: ProfileController.php?tab=profil&must_change=1');
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$classModel = new InstructorClass();
$classId = isset($_GET['class_id']) ? filter_var($_GET['class_id'], FILTER_VALIDATE_INT) : false;
if (!$classId) {
    http_response_code(404);
    exit('Kelas tidak ditemukan.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $_SESSION['classroom_error'] = 'Sesi form tidak valid. Muat ulang halaman dan coba lagi.';
        header('Location: ClassroomController.php?class_id=' . (int) $classId . '&tab=' . urlencode($_POST['tab'] ?? 'stream'));
        exit();
    }

    $action = $_POST['aksi'] ?? '';
    $tab = isset($_POST['tab']) && is_string($_POST['tab']) ? $_POST['tab'] : 'stream';

    try {
        $classInfo = $classModel->getStudentClassById($userId, (int) $classId);
        if (($classInfo['status'] ?? null) === 'ditutup') {
            throw new RuntimeException('Kelas ini sedang dalam mode read-only dan tidak menerima perubahan baru.');
        }

        if ($action === 'mark_material') {
            $materialId = isset($_POST['material_id']) ? filter_var($_POST['material_id'], FILTER_VALIDATE_INT) : false;
            if (!$materialId) {
                throw new InvalidArgumentException('Materi tidak valid.');
            }
            $classModel->markMaterialOpened($userId, (int) $classId, (int) $materialId);
            $_SESSION['classroom_success'] = 'Materi dibuka dan tercatat dalam progres kelas.';
        } elseif ($action === 'submit_quiz') {
            $quizId = isset($_POST['quiz_id']) ? filter_var($_POST['quiz_id'], FILTER_VALIDATE_INT) : false;
            if (!$quizId) {
                throw new InvalidArgumentException('Kuis tidak valid.');
            }
            $answers = $_POST['jawaban'] ?? [];
            if (!is_array($answers)) {
                $answers = [];
            }
            $result = $classModel->saveClassQuizAttempt($userId, (int) $classId, (int) $quizId, $answers);
            $_SESSION['classroom_success'] = 'Kamu menyelesaikan kuis dengan nilai ' . (int) $result['nilai'] . '%.';
        }
    } catch (Throwable $e) {
        $_SESSION['classroom_error'] = $e->getMessage();
    }

    header('Location: ClassroomController.php?class_id=' . (int) $classId . '&tab=' . urlencode($tab));
    exit();
}

$class = $classModel->getStudentClassById($userId, (int) $classId);
if ($class === null) {
    http_response_code(404);
    exit('Kamu belum terdaftar di kelas ini atau kelas tidak tersedia untuk siswa.');
}

$tab = isset($_GET['tab']) && is_string($_GET['tab']) ? $_GET['tab'] : 'stream';
$allowedTabs = ['stream', 'materi', 'kuis'];
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'stream';
}

$announcements = $classModel->getClassAnnouncements((int) $classId);
$modules = $classModel->getClassModules((int) $classId);
$quizzes = $classModel->getClassQuizDetailed((int) $classId);
$openedMaterialIds = $classModel->getOpenedMaterialIds($userId, (int) $classId);
$results = $classModel->getStudentClassQuizResults($userId, (int) $classId);
$resultsByQuiz = [];
foreach ($results as $result) {
    $resultsByQuiz[(int) $result['kuis_id']] = $result;
}

$data = [
    'username' => $_SESSION['username'],
    'foto_profil' => $_SESSION['foto_profil'] ?? null,
    'class' => $class,
    'tab' => $tab,
    'announcements' => $announcements,
    'modules' => $modules,
    'quizzes' => $quizzes,
    'openedMaterialIds' => $openedMaterialIds,
    'resultsByQuiz' => $resultsByQuiz,
    'success' => $_SESSION['classroom_success'] ?? '',
    'error' => $_SESSION['classroom_error'] ?? '',
];
unset($_SESSION['classroom_success'], $_SESSION['classroom_error']);

include __DIR__ . '/../../view/user/classroom.php';
