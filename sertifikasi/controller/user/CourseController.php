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
    exit('Kursus tidak ditemukan. Pastikan skema kursus sudah diimpor ke database.');
}

$userId = (int) $_SESSION['id_user'];
$enrollment = $courseModel->getEnrollment($userId, (int) $course['id']);
if ($enrollment === null) {
    header('Location: CourseOverviewController.php?slug=' . urlencode($slug));
    exit();
}

$lessons = $courseModel->getLessons((int) $course['id']);
$quiz = $courseModel->getQuiz((int) $course['id']);
$sublessons = [];
foreach ($lessons as $module) {
    foreach ($module['submateri'] as $sublesson) {
        $sublesson['module_id'] = (int) $module['id'];
        $sublesson['module_urutan'] = (int) $module['urutan'];
        $sublesson['module_judul'] = $module['judul'];
        $sublessons[] = $sublesson;
    }
}
if (!$lessons || !$sublessons || $quiz === null || !$quiz['soal']) {
    http_response_code(503);
    exit('Submateri atau kuis kursus belum tersedia di database. Pastikan file database/10_submateri.sql sudah dijalankan.');
}

$courseCompleted = $enrollment['selesai_pada'] !== null;
$pendingQuizResult = $_SESSION['hasil_kuis_terakhir'] ?? null;
$showFinalQuizResult = $courseCompleted
    && ($_GET['hasil'] ?? '') === 'terkirim'
    && is_array($pendingQuizResult)
    && ($pendingQuizResult['kuis_id'] ?? null) === (int) $quiz['id']
    && ($pendingQuizResult['lulus'] ?? false) === true;

if ($courseCompleted && !$showFinalQuizResult) {
    header('Location: CourseOverviewController.php?slug=' . urlencode($slug));
    exit();
}

$errors = [];
$hasilKuis = null;
$sedangMengerjakanKuis = ($_GET['bagian'] ?? '') === 'kuis' || $showFinalQuizResult;
$attemptTerakhir = $courseModel->getLatestAttempt($userId, (int) $quiz['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'kirim_kuis') {
    $sedangMengerjakanKuis = true;
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $errors[] = 'Sesi form tidak valid. Muat ulang halaman dan coba lagi.';
    } elseif ($attemptTerakhir !== null && (int) $attemptTerakhir['lulus'] === 1) {
        $errors[] = 'Kuis ini sudah lulus. Lanjutkan materi yang belum dipelajari.';
    } else {
        $jawaban = $_POST['jawaban'] ?? [];
        if (!is_array($jawaban)) {
            $jawaban = [];
        }

        $benar = 0;
        $opsiBenar = $courseModel->getCorrectOptionIds((int) $quiz['id']);
        foreach ($quiz['soal'] as $soal) {
            $soalId = (int) $soal['id'];
            $pilihan = $jawaban[$soalId] ?? null;
            $opsiSah = false;

            foreach ($soal['opsi'] as $opsi) {
                if (is_scalar($pilihan) && (string) $opsi['id'] === (string) $pilihan) {
                    $opsiSah = true;
                    if (($opsiBenar[$soalId] ?? null) === (int) $opsi['id']) {
                        $benar++;
                    }
                    break;
                }
            }

            if (!$opsiSah) {
                $errors[] = 'Jawab semua pertanyaan dengan pilihan yang tersedia.';
                break;
            }
        }

        if (!$errors) {
            $jumlahSoal = count($quiz['soal']);
            $nilai = (int) round(($benar / $jumlahSoal) * 100);
            $lulus = $nilai >= (int) $quiz['nilai_minimum'];
            $courseModel->saveAttempt(
                $userId,
                (int) $quiz['id'],
                $nilai,
                $benar,
                $jumlahSoal,
                $lulus
            );
            if ($lulus) {
                $courseModel->updateEnrollmentCompletion($userId, (int) $course['id']);
            }
            $_SESSION['hasil_kuis_terakhir'] = [
                'kuis_id' => (int) $quiz['id'],
                'nilai' => $nilai,
                'jumlah_benar' => $benar,
                'jumlah_soal' => $jumlahSoal,
                'lulus' => $lulus,
            ];
            header('Location: CourseController.php?slug=' . urlencode($slug) . '&bagian=kuis&hasil=terkirim');
            exit();
        }
    }
}

if (($_GET['hasil'] ?? '') === 'terkirim') {
    $hasil = $_SESSION['hasil_kuis_terakhir'] ?? null;
    if (is_array($hasil) && $hasil['kuis_id'] === (int) $quiz['id']) {
        $hasilKuis = $hasil;
        unset($_SESSION['hasil_kuis_terakhir']);
    }
}

if ($hasilKuis === null && $sedangMengerjakanKuis && $attemptTerakhir !== null && (int) $attemptTerakhir['lulus'] === 1) {
    $hasilKuis = [
        'nilai' => (int) $attemptTerakhir['nilai'],
        'jumlah_benar' => (int) $attemptTerakhir['jumlah_benar'],
        'jumlah_soal' => (int) $attemptTerakhir['jumlah_soal'],
        'lulus' => true,
    ];
}

$sublessonId = filter_input(INPUT_GET, 'submateri', FILTER_VALIDATE_INT);
$legacyLessonId = filter_input(INPUT_GET, 'materi', FILTER_VALIDATE_INT);
$requestedModuleId = filter_input(INPUT_GET, 'modul', FILTER_VALIDATE_INT);
$lesson = null;
$sublesson = null;
$isModuleOverview = false;
$hasSublessonSelector = isset($_GET['submateri']) || isset($_GET['materi']);

if (!$sedangMengerjakanKuis && !$hasSublessonSelector) {
    $moduleId = $requestedModuleId !== null && $requestedModuleId !== false
        ? $requestedModuleId
        : (int) $lessons[0]['id'];
    foreach ($lessons as $candidate) {
        if ((int) $candidate['id'] === $moduleId) {
            $lesson = $candidate;
            break;
        }
    }

    $lesson = $lesson ?? $lessons[0];
    $isModuleOverview = true;
} else {
    $sublesson = $sublessons[0];
    if ($sublessonId !== null && $sublessonId !== false) {
        foreach ($sublessons as $candidate) {
            if ((int) $candidate['id'] === $sublessonId) {
                $sublesson = $candidate;
                break;
            }
        }
    } elseif ($legacyLessonId !== null && $legacyLessonId !== false) {
        foreach ($sublessons as $candidate) {
            if ((int) $candidate['module_id'] === $legacyLessonId) {
                $sublesson = $candidate;
                break;
            }
        }
    }

    foreach ($lessons as $candidate) {
        if ((int) $candidate['id'] === (int) $sublesson['module_id']) {
            $lesson = $candidate;
            break;
        }
    }
}

if (!$sedangMengerjakanKuis && !$isModuleOverview && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $courseModel->markSublessonOpened($userId, (int) $sublesson['id']);
    $courseModel->updateEnrollmentCompletion($userId, (int) $course['id']);
}
$submateriDibuka = $courseModel->getOpenedSublessonIds($userId, (int) $course['id']);
$sublessonIndex = 0;
$sublessonIds = array_column($sublessons, 'id');
$sublessonsRemaining = array_values(array_diff($sublessonIds, $submateriDibuka));
if ($sublesson !== null) {
    foreach ($sublessons as $index => $candidate) {
        if ((int) $candidate['id'] === (int) $sublesson['id']) {
            $sublessonIndex = $index;
            break;
        }
    }
}
$data = [
    'username' => $_SESSION['username'],
    'foto_profil' => $_SESSION['foto_profil'] ?? null,
    'course' => $course,
    'lessons' => $lessons,
    'lesson' => $lesson,
    'sublessons' => $sublessons,
    'sublesson' => $sublesson,
    'sublessonIndex' => $sublessonIndex,
    'isModuleOverview' => $isModuleOverview,
    'submateriDibuka' => $submateriDibuka,
    'quiz' => $quiz,
    'isQuiz' => $sedangMengerjakanKuis,
    'errors' => $errors,
    'hasilKuis' => $hasilKuis,
    'attemptTerakhir' => $attemptTerakhir,
    'courseCompleted' => $courseCompleted,
    'nextSublessonId' => $sublessonsRemaining[0] ?? null,
    'csrf' => $_SESSION['csrf_token'],
];

include __DIR__ . '/../../view/user/course.php';
