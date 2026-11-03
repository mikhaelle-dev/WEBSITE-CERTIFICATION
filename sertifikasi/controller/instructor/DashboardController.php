<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../class/Auth.php';
require_once __DIR__ . '/../../class/InstructorClass.php';

if (empty($_SESSION['logged_in']) || empty($_SESSION['id_user'])) {
    header('Location: ../../view/login.php');
    exit();
}

$actorId = (int) $_SESSION['id_user'];
$role = $_SESSION['role'] ?? '';
if ($role !== 'instruktur') {
    if ($role === 'siswa') {
        header('Location: ../user/DashboardController.php');
    } elseif ($role === 'admin') {
        header('Location: ../admin/DashboardController.php');
    } else {
        header('Location: ../../view/login.php');
    }
    exit();
}

$auth = new Auth();
if ($auth->isRestrictedAccount($actorId)) {
    $_SESSION['account_restricted'] = true;
    header('Location: ../AccountDisabledController.php');
    exit();
}
if (!empty($_SESSION['account_restricted'])) {
    header('Location: ../AccountDisabledController.php');
    exit();
}
if ($auth->mustChangePassword($actorId)) {
    header('Location: ../user/ProfileController.php?tab=profil&must_change=1');
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$model = new InstructorClass();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $_SESSION['instructor_error'] = 'Sesi form tidak valid. Muat ulang halaman dan coba lagi.';
        header('Location: DashboardController.php');
        exit();
    }

    $action = $_POST['aksi'] ?? '';
    try {
        $classId = isset($_POST['class_id']) ? filter_var($_POST['class_id'], FILTER_VALIDATE_INT) : false;
        if ($action === 'save_class') {
            $iconUrl = trim((string) ($_POST['icon_url'] ?? ''));
            if (!empty($_FILES['class_icon']['tmp_name']) && is_uploaded_file($_FILES['class_icon']['tmp_name'])) {
                $uploadDir = __DIR__ . '/../../uploads/class-icons';
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                        throw new RuntimeException('Folder upload ikon kelas tidak bisa dibuat.');
                    }
                }

                $tmpFile = $_FILES['class_icon']['tmp_name'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = $finfo !== false ? finfo_file($finfo, $tmpFile) : null;
                if ($finfo !== false) { finfo_close($finfo); }
                $size = (int) ($_FILES['class_icon']['size'] ?? 0);
                $sizeOk = $size > 0 && $size <= 2 * 1024 * 1024;
                $imageInfo = @getimagesize($tmpFile);
                $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if ($mime === null || !isset($allowedMime[$mime]) || !$sizeOk || $imageInfo === false) {
                    throw new InvalidArgumentException('Ikon kelas harus berupa file JPG, PNG, atau WebP maksimal 2 MB.');
                }

                $ext = $allowedMime[$mime];
                $fileName = 'class-' . $actorId . '-' . time() . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
                $target = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
                if (!move_uploaded_file($tmpFile, $target)) {
                    throw new RuntimeException('Gagal menyimpan ikon kelas.');
                }
                $iconUrl = '/uploads/class-icons/' . $fileName;
            }

            $data = [
                'judul' => (string) ($_POST['judul'] ?? ''),
                'deskripsi' => (string) ($_POST['deskripsi'] ?? ''),
                'status' => (string) ($_POST['status'] ?? 'draft'),
                'enroll_key' => (string) ($_POST['enroll_key'] ?? ''),
                'icon_url' => $iconUrl,
                'badge_label' => trim((string) ($_POST['badge_label'] ?? '')),
                'badge_aktif' => !empty($_POST['badge_aktif']) ? 1 : 0,
                'badge_color' => trim((string) ($_POST['badge_color'] ?? 'purple')),
            ];
            if ($classId) {
                $model->updateClass($actorId, (int) $classId, $data);
                $_SESSION['instructor_success'] = 'Kelas berhasil diperbarui.';
            } else {
                $model->createClass($actorId, $data);
                $_SESSION['instructor_success'] = 'Kelas berhasil dibuat.';
            }
        } elseif ($action === 'delete_class') {
            if (!$classId) {
                throw new DomainException('Kelas tidak valid.');
            }
            $model->deleteClass($actorId, (int) $classId);
            $_SESSION['instructor_success'] = 'Kelas berhasil dihapus.';
        } elseif ($action === 'save_module') {
            if (!$classId) {
                throw new DomainException('Kelas tidak valid.');
            }
            $moduleId = isset($_POST['module_id']) ? filter_var($_POST['module_id'], FILTER_VALIDATE_INT) : false;
            $moduleData = [
                'judul' => (string) ($_POST['module_judul'] ?? ''),
                'deskripsi' => (string) ($_POST['module_deskripsi'] ?? ''),
                'urutan' => isset($_POST['module_urutan']) ? (int) $_POST['module_urutan'] : 0,
            ];
            if ($moduleId) {
                $model->updateModule($actorId, (int) $classId, (int) $moduleId, $moduleData);
                $_SESSION['instructor_success'] = 'Modul berhasil diperbarui.';
            } else {
                $model->createModule($actorId, (int) $classId, $moduleData);
                $_SESSION['instructor_success'] = 'Modul berhasil dibuat.';
            }
        } elseif ($action === 'delete_module') {
            if (!$classId) {
                throw new DomainException('Kelas tidak valid.');
            }
            $moduleId = isset($_POST['module_id']) ? filter_var($_POST['module_id'], FILTER_VALIDATE_INT) : false;
            if (!$moduleId) {
                throw new DomainException('Modul tidak valid.');
            }
            $model->deleteModule($actorId, (int) $classId, (int) $moduleId);
            $_SESSION['instructor_success'] = 'Modul berhasil dihapus.';
        } elseif ($action === 'save_material') {
            if (!$classId) {
                throw new DomainException('Kelas tidak valid.');
            }
            $moduleId = isset($_POST['module_id']) ? filter_var($_POST['module_id'], FILTER_VALIDATE_INT) : false;
            if (!$moduleId) {
                throw new DomainException('Modul untuk materi wajib dipilih.');
            }
            $materialId = isset($_POST['material_id']) ? filter_var($_POST['material_id'], FILTER_VALIDATE_INT) : false;
            $materialData = [
                'judul' => (string) ($_POST['material_judul'] ?? ''),
                'isi' => (string) ($_POST['material_isi'] ?? ''),
                'tipe' => (string) ($_POST['material_tipe'] ?? 'teks'),
                'urutan' => isset($_POST['material_urutan']) ? (int) $_POST['material_urutan'] : 0,
            ];
            if ($materialId) {
                $model->updateMaterial($actorId, (int) $classId, (int) $materialId, $materialData);
                $_SESSION['instructor_success'] = 'Materi berhasil diperbarui.';
            } else {
                $model->createMaterial($actorId, (int) $classId, (int) $moduleId, $materialData);
                $_SESSION['instructor_success'] = 'Materi berhasil dibuat.';
            }
        } elseif ($action === 'delete_material') {
            if (!$classId) {
                throw new DomainException('Kelas tidak valid.');
            }
            $materialId = isset($_POST['material_id']) ? filter_var($_POST['material_id'], FILTER_VALIDATE_INT) : false;
            if (!$materialId) {
                throw new DomainException('Materi tidak valid.');
            }
            $model->deleteMaterial($actorId, (int) $classId, (int) $materialId);
            $_SESSION['instructor_success'] = 'Materi berhasil dihapus.';
        } elseif ($action === 'save_quiz') {
            if (!$classId) {
                throw new DomainException('Kelas tidak valid.');
            }
            $quizId = isset($_POST['quiz_id']) ? filter_var($_POST['quiz_id'], FILTER_VALIDATE_INT) : false;
            $quizData = [
                'judul' => (string) ($_POST['quiz_judul'] ?? ''),
                'nilai_minimum' => isset($_POST['quiz_nilai_minimum']) ? (int) $_POST['quiz_nilai_minimum'] : 70,
                'waktu_durasi' => isset($_POST['quiz_waktu_durasi']) ? (int) $_POST['quiz_waktu_durasi'] : 0,
            ];
            if ($quizId) {
                $model->updateQuiz($actorId, (int) $classId, (int) $quizId, $quizData);
                $_SESSION['instructor_success'] = 'Kuis berhasil diperbarui.';
            } else {
                $model->createQuiz($actorId, (int) $classId, $quizData);
                $_SESSION['instructor_success'] = 'Kuis berhasil dibuat.';
            }
        } elseif ($action === 'delete_quiz') {
            if (!$classId) {
                throw new DomainException('Kelas tidak valid.');
            }
            $quizId = isset($_POST['quiz_id']) ? filter_var($_POST['quiz_id'], FILTER_VALIDATE_INT) : false;
            if (!$quizId) {
                throw new DomainException('Kuis tidak valid.');
            }
            $model->deleteQuiz($actorId, (int) $classId, (int) $quizId);
            $_SESSION['instructor_success'] = 'Kuis berhasil dihapus.';
        } elseif ($action === 'save_announcement') {
            if (!$classId) {
                throw new DomainException('Kelas tidak valid.');
            }
            $message = trim((string) ($_POST['announcement_text'] ?? ''));
            $model->createClassAnnouncement($actorId, (int) $classId, $message);
            $_SESSION['instructor_success'] = 'Pengumuman kelas berhasil diposting.';
        } elseif ($action === 'delete_announcement') {
            if (!$classId) {
                throw new DomainException('Kelas tidak valid.');
            }
            $announcementId = isset($_POST['announcement_id']) ? filter_var($_POST['announcement_id'], FILTER_VALIDATE_INT) : false;
            if (!$announcementId) {
                throw new DomainException('Pengumuman tidak valid.');
            }
            $model->deleteClassAnnouncement($actorId, (int) $classId, (int) $announcementId);
            $_SESSION['instructor_success'] = 'Pengumuman berhasil dihapus.';
        } elseif ($action === 'remove_student') {
            if (!$classId) {
                throw new DomainException('Kelas tidak valid.');
            }
            $studentId = isset($_POST['student_id']) ? filter_var($_POST['student_id'], FILTER_VALIDATE_INT) : false;
            if (!$studentId) {
                throw new DomainException('Siswa tidak valid.');
            }
            $model->removeStudentFromClass($actorId, (int) $classId, (int) $studentId);
            $_SESSION['instructor_success'] = 'Siswa berhasil dikeluarkan dari kelas.';
        } elseif ($action === 'save_question') {
            if (!$classId) {
                throw new DomainException('Kelas tidak valid.');
            }
            $quizId = isset($_POST['quiz_id']) ? filter_var($_POST['quiz_id'], FILTER_VALIDATE_INT) : false;
            if (!$quizId) {
                throw new DomainException('Kuis tidak valid.');
            }
            $questionId = isset($_POST['question_id']) ? filter_var($_POST['question_id'], FILTER_VALIDATE_INT) : false;
            $questionData = [
                'teks' => (string) ($_POST['question_text'] ?? ''),
                'option_0' => (string) ($_POST['option_0'] ?? ''),
                'option_1' => (string) ($_POST['option_1'] ?? ''),
                'option_2' => (string) ($_POST['option_2'] ?? ''),
                'option_3' => (string) ($_POST['option_3'] ?? ''),
                'option_4' => (string) ($_POST['option_4'] ?? ''),
                'option_5' => (string) ($_POST['option_5'] ?? ''),
                'jawaban_benar' => isset($_POST['jawaban_benar']) ? (int) $_POST['jawaban_benar'] : 0,
            ];

            if ($questionId) {
                $model->updateQuizQuestion($actorId, (int) $classId, (int) $quizId, (int) $questionId, $questionData);
                $_SESSION['instructor_success'] = 'Soal kuis berhasil diperbarui.';
            } else {
                $model->createQuizQuestion($actorId, (int) $classId, (int) $quizId, $questionData);
                $_SESSION['instructor_success'] = 'Soal kuis berhasil ditambahkan.';
            }
        } elseif ($action === 'delete_question') {
            if (!$classId) {
                throw new DomainException('Kelas tidak valid.');
            }
            $questionId = isset($_POST['question_id']) ? filter_var($_POST['question_id'], FILTER_VALIDATE_INT) : false;
            if (!$questionId) {
                throw new DomainException('Soal tidak valid.');
            }
            $model->deleteQuizQuestion($actorId, (int) $classId, (int) $questionId);
            $_SESSION['instructor_success'] = 'Soal kuis berhasil dihapus.';
        } else {
            throw new DomainException('Aksi tidak valid.');
        }
    } catch (Throwable $e) {
        $_SESSION['instructor_error'] = $e->getMessage();
    }

    $redirectTarget = 'DashboardController.php';
    if ($classId) {
        $redirectTarget .= '?class_id=' . (int) $classId;
    }
    header('Location: ' . $redirectTarget);
    exit();
}

$search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$createMode = (isset($_GET['mode']) && $_GET['mode'] === 'create');
$tab = isset($_GET['tab']) && is_string($_GET['tab']) ? $_GET['tab'] : 'stream';
$classes = [];
$stats = ['total_kelas' => 0, 'kelas_aktif' => 0, 'kelas_draft' => 0, 'kelas_ditutup' => 0, 'total_siswa' => 0, 'rata_progress' => 0];
$selectedClass = null;
$students = [];
$modules = [];
$quizzes = [];
$announcements = [];
$grades = [];
$editModule = null;
$editMaterial = null;
$editQuiz = null;

try {
    $classes = $model->getClasses($actorId, $search);
    $stats = $model->getStats($actorId);
    $selectedId = isset($_GET['class_id']) ? filter_var($_GET['class_id'], FILTER_VALIDATE_INT) : false;
    if ($selectedId) {
        $selectedClass = $model->getClassById($actorId, (int) $selectedId);
    }
    if (!$createMode && $selectedClass === null && !empty($classes)) {
        $selectedClass = $model->getClassById($actorId, (int) $classes[0]['id']);
    }
    $students = $selectedClass !== null ? $model->getClassStudents($actorId, (int) $selectedClass['id']) : [];
    $modules = $selectedClass !== null ? $model->getClassModules((int) $selectedClass['id']) : [];
    $quizzes = $selectedClass !== null ? $model->getClassQuizzes((int) $selectedClass['id']) : [];
    foreach ($quizzes as &$quiz) {
        $quiz['soal'] = $model->getQuizQuestions((int) $quiz['id']);
    }
    unset($quiz);
    $announcements = $selectedClass !== null ? $model->getClassAnnouncements((int) $selectedClass['id']) : [];
    $grades = $selectedClass !== null ? $model->getClassGrades((int) $selectedClass['id']) : [];

    $editModuleId = isset($_GET['module_id']) ? filter_var($_GET['module_id'], FILTER_VALIDATE_INT) : false;
    $editModule = $editModuleId && $selectedClass !== null ? $model->getModuleById($actorId, (int) $selectedClass['id'], (int) $editModuleId) : null;
    $editMaterialId = isset($_GET['material_id']) ? filter_var($_GET['material_id'], FILTER_VALIDATE_INT) : false;
    $editMaterial = $editMaterialId && $selectedClass !== null ? $model->getMaterialById($actorId, (int) $selectedClass['id'], (int) $editMaterialId) : null;
    $editQuizId = isset($_GET['quiz_id']) ? filter_var($_GET['quiz_id'], FILTER_VALIDATE_INT) : false;
    $editQuiz = $editQuizId && $selectedClass !== null ? $model->getQuizById($actorId, (int) $selectedClass['id'], (int) $editQuizId) : null;
} catch (Throwable $e) {
    $_SESSION['instructor_error'] = $e->getMessage();
    $classes = [];
    $stats = ['total_kelas' => 0, 'kelas_aktif' => 0, 'kelas_draft' => 0, 'kelas_ditutup' => 0, 'total_siswa' => 0, 'rata_progress' => 0];
    $selectedClass = null;
    $students = [];
    $modules = [];
    $quizzes = [];
    $announcements = [];
    $grades = [];
    $editModule = null;
    $editMaterial = null;
    $editQuiz = null;
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$data = [
    'username' => $_SESSION['username'],
    'foto_profil' => $_SESSION['foto_profil'] ?? null,
    'role' => $role,
    'stats' => $stats,
    'classes' => $classes,
    'selectedClass' => $selectedClass,
    'students' => $students,
    'modules' => $modules,
    'quizzes' => $quizzes,
    'announcements' => $announcements,
    'grades' => $grades,
    'tab' => $tab,
    'editModule' => $editModule,
    'editMaterial' => $editMaterial,
    'editQuiz' => $editQuiz,
    'search' => $search,
    'success' => $_SESSION['instructor_success'] ?? '',
    'error' => $_SESSION['instructor_error'] ?? '',
    'createMode' => $createMode,
];
unset($_SESSION['instructor_success'], $_SESSION['instructor_error']);

include __DIR__ . '/../../view/instructor/dashboard.php';
