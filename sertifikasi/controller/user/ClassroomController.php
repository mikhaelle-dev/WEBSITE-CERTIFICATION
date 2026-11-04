<?php
// Desain halaman kelas ini mengikuti pola Google Classroom agar siswa fokus pada stream, materi, dan kuis tanpa navigasi yang membingungkan. Tujuan utamanya adalah presentasi kuliah yang bersih, cepat dipahami, dan tetap fungsional.
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../class/Auth.php';
require_once __DIR__ . '/../../class/InstructorClass.php';
require_once __DIR__ . '/../../class/InstructorTools.php';

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
$tools = new InstructorTools();
$classId = isset($_GET['class_id']) ? filter_var($_GET['class_id'], FILTER_VALIDATE_INT) : false;
if (!$classId) {
    http_response_code(404);
    exit('Kelas tidak ditemukan.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $_SESSION['classroom_error'] = 'Sesi form tidak valid. Muat ulang halaman dan coba lagi.';
        header('Location: ClassroomController.php?class_id=' . (int) $classId . '&tab=' . urlencode($_POST['tab'] ?? 'overview'));
        exit();
    }

    $action = $_POST['aksi'] ?? '';
    $tab = isset($_POST['tab']) && is_string($_POST['tab']) ? $_POST['tab'] : 'overview';

    try {
        $classInfo = $classModel->getStudentClassById($userId, (int) $classId);
        if (($classInfo['status'] ?? null) === 'ditutup' && $action !== 'unlock_module') {
            throw new RuntimeException('Kelas ini sedang dalam mode read-only dan tidak menerima perubahan baru.');
        }

        if ($action === 'mark_material') {
            $materialId = isset($_POST['material_id']) ? filter_var($_POST['material_id'], FILTER_VALIDATE_INT) : false;
            if (!$materialId) {
                throw new InvalidArgumentException('Materi tidak valid.');
            }
            $classModel->markMaterialOpened($userId, (int) $classId, (int) $materialId);
            $_SESSION['classroom_success'] = 'Materi dibuka dan tercatat dalam progres kelas.';
        } elseif ($action === 'unlock_module') {
            $moduleId = filter_var($_POST['module_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$moduleId) {
                throw new InvalidArgumentException('Modul tidak valid.');
            }
            $classModel->unlockModuleForStudent(
                $userId,
                (int) $classId,
                (int) $moduleId,
                (string) ($_POST['module_enroll_key'] ?? '')
            );
            $_SESSION['classroom_success'] = 'Modul berhasil dibuka.';
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
        } elseif ($action === 'submit_assignment') {
            $assignmentId = filter_var($_POST['assignment_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$assignmentId || !isset($_FILES['assignment_file']) || (int) $_FILES['assignment_file']['error'] !== UPLOAD_ERR_OK) {
                throw new InvalidArgumentException('Pilih file tugas yang valid.');
            }
            $tmpFile = $_FILES['assignment_file']['tmp_name'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo !== false ? finfo_file($finfo, $tmpFile) : null;
            if ($finfo !== false) { finfo_close($finfo); }
            $allowed = [
                'application/pdf' => 'pdf',
                'text/plain' => 'txt',
                'application/zip' => 'zip',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            ];
            $size = (int) ($_FILES['assignment_file']['size'] ?? 0);
            if ($mime === null || !isset($allowed[$mime]) || $size < 1 || $size > 10 * 1024 * 1024) {
                throw new InvalidArgumentException('Tugas harus berupa PDF, DOCX, PPTX, ZIP, atau TXT maksimal 10 MB.');
            }
            $uploadDir = __DIR__ . '/../../uploads/class-assignments';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                throw new RuntimeException('Folder pengumpulan tugas tidak bisa dibuat.');
            }
            $fileName = 'submission-' . $userId . '-' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
            if (!move_uploaded_file($tmpFile, $uploadDir . DIRECTORY_SEPARATOR . $fileName)) {
                throw new RuntimeException('Gagal menyimpan file tugas.');
            }
            $tools->submitClassAssignment(
                $userId,
                (int) $classId,
                (int) $assignmentId,
                '../../uploads/class-assignments/' . $fileName,
                basename((string) ($_FILES['assignment_file']['name'] ?? $fileName))
            );
            $_SESSION['classroom_success'] = 'File tugas berhasil dikumpulkan.';
        } elseif ($action === 'ask_question') {
            $tools->createForumThread(
                $userId,
                (int) $classId,
                (string) ($_POST['question_title'] ?? ''),
                (string) ($_POST['question_text'] ?? '')
            );
            $_SESSION['classroom_success'] = 'Pertanyaan berhasil dikirim ke instruktur.';
        }
        if (in_array($action, ['mark_material', 'submit_quiz'], true)) {
            try {
                if ($tools->issueAutomaticClassCertificate((int) $classId, $userId)) {
                    $_SESSION['classroom_success'] .= ' Sertifikat kelulusan juga sudah diterbitkan.';
                }
            } catch (Throwable $certificateError) {
                error_log('Automatic class certificate issuance failed: ' . $certificateError->getMessage());
                $_SESSION['classroom_error'] = 'Aktivitas tersimpan, tetapi sertifikat belum bisa diterbitkan otomatis. Hubungi instruktur.';
            }
        }
    } catch (Throwable $e) {
        $_SESSION['classroom_error'] = $e->getMessage();
    }

    $redirect = 'ClassroomController.php?class_id=' . (int) $classId . '&tab=' . urlencode($tab);
    if (in_array($action, ['mark_material', 'unlock_module'], true)) {
        $returnModuleValue = $_POST['module_id'] ?? null;
        $returnModuleId = is_string($returnModuleValue) || is_int($returnModuleValue)
            ? filter_var($returnModuleValue, FILTER_VALIDATE_INT)
            : false;
        $returnMaterialValue = $_POST['return_material_id'] ?? null;
        $returnMaterialId = is_string($returnMaterialValue) || is_int($returnMaterialValue)
            ? filter_var($returnMaterialValue, FILTER_VALIDATE_INT)
            : false;
        if ($returnModuleId) {
            $redirect .= '&module_id=' . (int) $returnModuleId;
        }
        if ($returnMaterialId) {
            $redirect .= '&material_id=' . (int) $returnMaterialId;
        }
    }
    if (in_array($action, ['submit_quiz', 'submit_assignment'], true)) {
        $returnQuizValue = $_POST['return_quiz_id'] ?? null;
        $returnQuizId = is_string($returnQuizValue) || is_int($returnQuizValue)
            ? filter_var($returnQuizValue, FILTER_VALIDATE_INT)
            : false;
        if ($returnQuizId) {
            $redirect .= '&quiz_id=' . (int) $returnQuizId;
        }
    }
    header('Location: ' . $redirect);
    exit();
}

$class = $classModel->getStudentClassById($userId, (int) $classId);
if ($class === null) {
    $requestedTab = isset($_GET['tab']) && is_string($_GET['tab']) ? $_GET['tab'] : 'overview';
    $previewClass = $requestedTab === 'overview'
        ? $classModel->getAvailableClassOverview((int) $classId)
        : null;
    if ($previewClass !== null) {
        $data = [
            'username' => $_SESSION['username'] ?? 'Siswa',
            'foto_profil' => $_SESSION['foto_profil'] ?? null,
            'role' => $_SESSION['role'],
            'class' => $previewClass,
        ];
        include __DIR__ . '/../../view/user/classroom-overview.php';
        exit();
    }

    http_response_code(404);
    exit('Kamu belum terdaftar di kelas ini atau kelas tidak tersedia untuk siswa.');
}

$tab = isset($_GET['tab']) && is_string($_GET['tab']) ? $_GET['tab'] : 'overview';
$allowedTabs = ['overview', 'stream', 'materi', 'kuis'];
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'overview';
}

if (in_array($tab, ['overview', 'materi'], true)) {
    $classModel->updateClassProgressForUser($userId, (int) $classId);
    $class = $classModel->getStudentClassById($userId, (int) $classId);
}

$modules = $classModel->getStudentClassModules($userId, (int) $classId);
$quizzes = $classModel->getClassQuizzes((int) $classId);
$openedMaterialIds = $classModel->getOpenedMaterialIds($userId, (int) $classId);
$results = $classModel->getStudentClassQuizResults($userId, (int) $classId);
$resultsByQuiz = [];
foreach ($results as $result) {
    $quizId = (int) $result['kuis_id'];
    if (!isset($resultsByQuiz[$quizId])) {
        $resultsByQuiz[$quizId] = $result;
    }
}

if ($tab === 'overview') {
    $data = [
        'username' => $_SESSION['username'],
        'foto_profil' => $_SESSION['foto_profil'] ?? null,
        'role' => $_SESSION['role'],
        'class' => $class,
        'enrolled' => true,
        'modules' => $modules,
        'quizzes' => $quizzes,
        'openedMaterialIds' => $openedMaterialIds,
        'resultsByQuiz' => $resultsByQuiz,
        'certificate' => $classModel->getStudentClassCertificate($userId, (int) $classId),
        'classBadge' => $classModel->getStudentClassBadge($userId, (int) $classId),
        'success' => $_SESSION['classroom_success'] ?? '',
        'error' => $_SESSION['classroom_error'] ?? '',
    ];
    unset($_SESSION['classroom_success'], $_SESSION['classroom_error']);

    include __DIR__ . '/../../view/user/classroom-overview.php';
    exit();
}

$announcements = $classModel->getClassAnnouncements((int) $classId);
$quizzes = $classModel->getClassQuizDetailed((int) $classId);
$assignments = $tools->getStudentClassAssignments($userId, (int) $classId);
$forumThreads = $tools->getStudentForumThreads($userId, (int) $classId);

$data = [
    'username' => $_SESSION['username'],
    'foto_profil' => $_SESSION['foto_profil'] ?? null,
    'class' => $class,
    'tab' => $tab,
    'announcements' => $announcements,
    'modules' => $modules,
    'quizzes' => $quizzes,
    'assignments' => $assignments,
    'forumThreads' => $forumThreads,
    'openedMaterialIds' => $openedMaterialIds,
    'resultsByQuiz' => $resultsByQuiz,
    'success' => $_SESSION['classroom_success'] ?? '',
    'error' => $_SESSION['classroom_error'] ?? '',
];
unset($_SESSION['classroom_success'], $_SESSION['classroom_error']);

include __DIR__ . '/../../view/user/classroom.php';
