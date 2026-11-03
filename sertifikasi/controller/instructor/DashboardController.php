<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../class/Auth.php';
require_once __DIR__ . '/../../class/InstructorClass.php';
require_once __DIR__ . '/../../class/InstructorTools.php';

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
$tools = new InstructorTools();

if (isset($_GET['ajax']) && in_array($_GET['ajax'], ['get_class', 'class_detail'], true)) {
    $classId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : false;
    if (!$classId) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Kelas tidak valid.']);
        exit();
    }

    try {
        $class = $model->getClassById($actorId, (int) $classId);
        if ($class === null) {
            http_response_code(404);
            $payload = ['success' => false, 'message' => 'Kelas tidak ditemukan.'];
        } else {
            $classPayload = [
                'id' => (int) $class['id'],
                'judul' => (string) $class['judul'],
                'kategori' => (string) ($class['kategori'] ?? 'Umum'),
                'deskripsi' => (string) $class['deskripsi'],
                'status' => (string) $class['status'],
                'icon_url' => (string) ($class['icon_url'] ?? ''),
            ];
            $payload = [
                'success' => true,
                'class' => $classPayload,
            ];
            if ($_GET['ajax'] === 'class_detail') {
                $quizzes = $model->getClassQuizzes((int) $classId);
                foreach ($quizzes as &$quiz) {
                    $quiz['questions'] = $model->getQuizQuestions((int) $quiz['id']);
                }
                unset($quiz);
                $payload['students'] = $model->getClassStudents($actorId, (int) $classId);
                $payload['modules'] = $model->getClassModules((int) $classId);
                $payload['quizzes'] = $quizzes;
                $payload['assignments'] = $tools->getClassAssignments($actorId, (int) $classId);
                foreach ($payload['assignments'] as &$assignment) {
                    $assignment['submissions'] = $tools->getAssignmentSubmissions($actorId, (int) $assignment['id']);
                }
                unset($assignment);
            }
        }
    } catch (Throwable $e) {
        error_log('Instructor class lookup failed: ' . $e->getMessage());
        http_response_code(500);
        $payload = ['success' => false, 'message' => 'Gagal memuat data kelas.'];
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ajaxRequest = isset($_POST['ajax']) && $_POST['ajax'] === '1';
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        if ($ajaxRequest) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Sesi form tidak valid. Muat ulang halaman dan coba lagi.']);
            exit();
        }
        $_SESSION['instructor_error'] = 'Sesi form tidak valid. Muat ulang halaman dan coba lagi.';
        header('Location: DashboardController.php');
        exit();
    }

    $action = $_POST['aksi'] ?? '';
    $ajaxError = null;
    $ajaxStatus = 200;
    $savedClassId = null;
    $createdRecordId = null;
    try {
        $classId = isset($_POST['class_id']) ? filter_var($_POST['class_id'], FILTER_VALIDATE_INT) : false;
        if (isset($_POST['class_id']) && $_POST['class_id'] !== '' && !$classId) {
            throw new DomainException('Kelas tidak valid.');
        }
        if ($action === 'save_class') {
            $iconUrl = trim((string) ($_POST['icon_url'] ?? ''));
            if (isset($_FILES['class_icon']) && (int) $_FILES['class_icon']['error'] !== UPLOAD_ERR_NO_FILE
                && (int) $_FILES['class_icon']['error'] !== UPLOAD_ERR_OK) {
                throw new InvalidArgumentException('Upload cover kelas gagal. Silakan pilih file kembali.');
            }
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
                $iconUrl = '../../uploads/class-icons/' . $fileName;
            }

            $data = [
                'judul' => (string) ($_POST['judul'] ?? ''),
                'kategori' => (string) ($_POST['kategori'] ?? 'Umum'),
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
                $savedClassId = (int) $classId;
                $_SESSION['instructor_success'] = 'Kelas berhasil diperbarui.';
            } else {
                $savedClassId = $model->createClass($actorId, $data);
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
                $createdRecordId = $model->createModule($actorId, (int) $classId, $moduleData);
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
            $materialContent = (string) ($_POST['material_isi'] ?? '');
            $materialType = (string) ($_POST['material_tipe'] ?? 'teks');
            if (isset($_FILES['material_file']) && (int) $_FILES['material_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ((int) $_FILES['material_file']['error'] !== UPLOAD_ERR_OK) {
                    throw new InvalidArgumentException('Upload materi gagal. Pilih file kembali.');
                }
                $tmpFile = $_FILES['material_file']['tmp_name'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = $finfo !== false ? finfo_file($finfo, $tmpFile) : null;
                if ($finfo !== false) { finfo_close($finfo); }
                $allowedMime = [
                    'application/pdf' => ['pdf', 'dokumen'],
                    'video/mp4' => ['mp4', 'video'],
                    'video/webm' => ['webm', 'video'],
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx', 'dokumen'],
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation' => ['pptx', 'dokumen'],
                ];
                $size = (int) ($_FILES['material_file']['size'] ?? 0);
                if ($mime === null || !isset($allowedMime[$mime]) || $size < 1 || $size > 25 * 1024 * 1024) {
                    throw new InvalidArgumentException('Materi harus berupa PDF, DOCX, PPTX, MP4, atau WebM maksimal 25 MB.');
                }
                $uploadDir = __DIR__ . '/../../uploads/class-materials';
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                    throw new RuntimeException('Folder upload materi tidak bisa dibuat.');
                }
                $ext = $allowedMime[$mime][0];
                $fileName = 'material-' . $actorId . '-' . bin2hex(random_bytes(12)) . '.' . $ext;
                if (!move_uploaded_file($tmpFile, $uploadDir . DIRECTORY_SEPARATOR . $fileName)) {
                    throw new RuntimeException('Gagal menyimpan file materi.');
                }
                $materialContent = '../../uploads/class-materials/' . $fileName;
                $materialType = $allowedMime[$mime][1];
            }
            $materialData = [
                'judul' => (string) ($_POST['material_judul'] ?? ''),
                'isi' => $materialContent,
                'tipe' => $materialType,
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
        } elseif ($action === 'save_quiz_with_question') {
            if (!$classId) {
                throw new DomainException('Kelas tidak valid.');
            }
            $quizData = [
                'judul' => (string) ($_POST['quiz_judul'] ?? ''),
                'nilai_minimum' => 70,
                'waktu_durasi' => 0,
            ];
            $questionData = [
                'teks' => (string) ($_POST['question_text'] ?? ''),
                'option_0' => (string) ($_POST['option_0'] ?? ''),
                'option_1' => (string) ($_POST['option_1'] ?? ''),
                'option_2' => (string) ($_POST['option_2'] ?? ''),
                'option_3' => (string) ($_POST['option_3'] ?? ''),
                'jawaban_benar' => isset($_POST['jawaban_benar']) ? (int) $_POST['jawaban_benar'] : 0,
            ];
            $createdRecordId = $model->createQuizWithQuestion($actorId, (int) $classId, $quizData, $questionData);
            $_SESSION['instructor_success'] = 'Kuis dan pertanyaan berhasil dibuat.';
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
                $createdRecordId = $model->createQuiz($actorId, (int) $classId, $quizData);
                $_SESSION['instructor_success'] = 'Kuis berhasil dibuat.';
            }
        } elseif ($action === 'save_assignment') {
            if (!$classId) {
                throw new DomainException('Kelas tidak valid.');
            }
            $createdRecordId = $tools->createClassAssignment($actorId, (int) $classId, [
                'title' => (string) ($_POST['assignment_title'] ?? ''),
                'description' => (string) ($_POST['assignment_description'] ?? ''),
                'due_at' => (string) ($_POST['assignment_due_at'] ?? ''),
            ]);
            $_SESSION['instructor_success'] = 'Tugas berhasil dibuat.';
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
        if ($ajaxRequest) {
            $ajaxError = ($e instanceof InvalidArgumentException || $e instanceof DomainException)
                ? $e->getMessage()
                : 'Terjadi kesalahan saat memproses permintaan.';
            $ajaxStatus = ($e instanceof InvalidArgumentException || $e instanceof DomainException) ? 422 : 500;
            if ($ajaxError === 'Terjadi kesalahan saat menyimpan kelas.') {
                error_log('Instructor dashboard AJAX failed: ' . $e->getMessage());
            }
        } else {
            $_SESSION['instructor_error'] = $e->getMessage();
        }
    }

    if ($ajaxRequest) {
        if ($ajaxError !== null) {
            http_response_code($ajaxStatus);
            $payload = ['success' => false, 'message' => $ajaxError];
        } else {
            $payload = ['success' => true, 'message' => $_SESSION['instructor_success'] ?? 'Perubahan berhasil disimpan.'];
            if ($createdRecordId !== null) {
                $payload['record_id'] = $createdRecordId;
            }
            if ($action === 'save_class' && $savedClassId !== null) {
                $savedClass = $model->getClassById($actorId, $savedClassId);
                $payload['class'] = $savedClass !== null ? [
                    'id' => (int) $savedClass['id'],
                    'judul' => (string) $savedClass['judul'],
                    'kategori' => (string) ($savedClass['kategori'] ?? 'Umum'),
                    'deskripsi' => (string) $savedClass['deskripsi'],
                    'status' => (string) $savedClass['status'],
                    'icon_url' => (string) ($savedClass['icon_url'] ?? ''),
                ] : null;
            }
        }
        unset($_SESSION['instructor_success']);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit();
    }

    $redirectTarget = 'DashboardController.php';
    if ($classId) {
        $redirectTarget .= '?class_id=' . (int) $classId;
    }
    header('Location: ' . $redirectTarget);
    exit();
}

$page = isset($_GET['page']) && is_string($_GET['page']) ? $_GET['page'] : '';
if ($page === 'bank_materi') {
    $page = 'materi';
}
if (!in_array($page, ['', 'kelas', 'materi'], true)) {
    $page = '';
}
$search = $page === '' && isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$createMode = $page === '' && isset($_GET['mode']) && $_GET['mode'] === 'create';
$tab = $page === 'materi'
    ? 'modul'
    : ($page === 'kelas' ? 'stream' : (isset($_GET['tab']) && is_string($_GET['tab']) ? $_GET['tab'] : 'stream'));
$classes = [];
$stats = ['total_kelas' => 0, 'kelas_aktif' => 0, 'kelas_draft' => 0, 'kelas_ditutup' => 0, 'total_siswa' => 0, 'rata_progress' => 0];
$monthlyRegistrations = [];
$unansweredCount = null;
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
    $monthlyRegistrations = $model->getMonthlyRegistrations($actorId);
    try {
        $unansweredCount = $tools->getUnansweredCount($actorId);
    } catch (Throwable $e) {
        error_log('Instructor unanswered question count is unavailable: ' . $e->getMessage());
    }
    $selectedId = $page === '' && isset($_GET['class_id']) ? filter_var($_GET['class_id'], FILTER_VALIDATE_INT) : false;
    if ($selectedId) {
        $selectedClass = $model->getClassById($actorId, (int) $selectedId);
    }
    if (!$selectedId && ($tab === 'modul' || $page === 'materi') && !empty($classes)) {
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
    $monthlyRegistrations = [];
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
    'monthlyRegistrations' => $monthlyRegistrations,
    'unansweredCount' => $unansweredCount,
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
