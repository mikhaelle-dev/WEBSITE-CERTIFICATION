<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../class/Auth.php';
require_once __DIR__ . '/../../class/InstructorTools.php';

if (empty($_SESSION['logged_in']) || empty($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'instruktur') {
    header('Location: ../../view/login.php');
    exit();
}

$instructorId = (int) $_SESSION['id_user'];
$auth = new Auth();
if ($auth->isRestrictedAccount($instructorId) || !empty($_SESSION['account_restricted'])) {
    $_SESSION['account_restricted'] = true;
    header('Location: ../AccountDisabledController.php');
    exit();
}
if ($auth->mustChangePassword($instructorId)) {
    header('Location: ../user/ProfileController.php?tab=profil&must_change=1');
    exit();
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$tools = new InstructorTools();
$json = static function (array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit();
};

if (isset($_GET['ajax'])) {
    try {
        if ($_GET['ajax'] === 'threads') {
            $classId = filter_var($_GET['class_id'] ?? 0, FILTER_VALIDATE_INT);
            $status = (string) ($_GET['status'] ?? 'all');
            if ($classId === false || $classId < 0 || !in_array($status, ['all', 'unanswered', 'answered'], true)) {
                $json(['success' => false, 'message' => 'Filter forum tidak valid.'], 422);
            }
            $json([
                'success' => true,
                'threads' => $tools->getForumThreads($instructorId, (int) $classId, $status),
                'unanswered' => $tools->getUnansweredCount($instructorId),
            ]);
        }
        if ($_GET['ajax'] === 'thread') {
            $threadId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$threadId || $threadId < 1) {
                $json(['success' => false, 'message' => 'Pertanyaan tidak valid.'], 422);
            }
            $thread = $tools->getForumThread($instructorId, (int) $threadId);
            if ($thread === null) {
                $json(['success' => false, 'message' => 'Pertanyaan tidak ditemukan.'], 404);
            }
            $json(['success' => true, 'thread' => $thread]);
        }
        $json(['success' => false, 'message' => 'Permintaan forum tidak valid.'], 400);
    } catch (Throwable $e) {
        error_log('Instructor forum lookup failed: ' . $e->getMessage());
        $json(['success' => false, 'message' => 'Forum gagal dimuat. Jalankan database/16_instructor_tools.sql jika tabel belum tersedia.'], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $json(['success' => false, 'message' => 'Sesi form tidak valid. Muat ulang halaman.'], 403);
    }
    $attachmentPath = '';
    $attachmentName = '';
    try {
        if (isset($_FILES['reply_file']) && (int) $_FILES['reply_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ((int) $_FILES['reply_file']['error'] !== UPLOAD_ERR_OK) {
                throw new InvalidArgumentException('Lampiran gagal diunggah.');
            }
            $tmpFile = $_FILES['reply_file']['tmp_name'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo !== false ? finfo_file($finfo, $tmpFile) : null;
            if ($finfo !== false) { finfo_close($finfo); }
            $allowed = [
                'application/pdf' => 'pdf',
                'text/plain' => 'txt',
                'image/png' => 'png',
                'image/jpeg' => 'jpg',
            ];
            $size = (int) ($_FILES['reply_file']['size'] ?? 0);
            if ($mime === null || !isset($allowed[$mime]) || $size < 1 || $size > 4 * 1024 * 1024) {
                throw new InvalidArgumentException('Lampiran harus berupa PDF, TXT, PNG, atau JPG maksimal 4 MB.');
            }
            $directory = __DIR__ . '/../../uploads/forum';
            if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new RuntimeException('Folder lampiran forum tidak bisa dibuat.');
            }
            $fileName = 'reply-' . $instructorId . '-' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
            if (!move_uploaded_file($tmpFile, $directory . DIRECTORY_SEPARATOR . $fileName)) {
                throw new RuntimeException('Gagal menyimpan lampiran forum.');
            }
            $attachmentPath = '../../uploads/forum/' . $fileName;
            $attachmentName = basename((string) ($_FILES['reply_file']['name'] ?? 'lampiran'));
        }

        if (($_POST['aksi'] ?? '') !== 'reply') {
            throw new InvalidArgumentException('Aksi forum tidak valid.');
        }
        $threadId = filter_var($_POST['thread_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$threadId || $threadId < 1) {
            throw new InvalidArgumentException('Pertanyaan tidak valid.');
        }
        $tools->createForumReply(
            $instructorId,
            (int) $threadId,
            (string) ($_POST['message'] ?? ''),
            (string) ($_POST['code_snippet'] ?? ''),
            $attachmentPath,
            $attachmentName
        );
        $json(['success' => true, 'message' => 'Jawaban berhasil dikirim.']);
    } catch (InvalidArgumentException $e) {
        $json(['success' => false, 'message' => $e->getMessage()], 422);
    } catch (Throwable $e) {
        error_log('Instructor forum reply failed: ' . $e->getMessage());
        $json(['success' => false, 'message' => 'Jawaban gagal dikirim.'], 500);
    }
}

try {
    $data = [
        'username' => $_SESSION['username'] ?? 'Instruktur',
        'classes' => $tools->getInstructorClasses($instructorId),
        'unanswered' => $tools->getUnansweredCount($instructorId),
        'csrfToken' => $_SESSION['csrf_token'],
    ];
} catch (Throwable $e) {
    error_log('Instructor forum dashboard failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Forum belum siap. Jalankan database/16_instructor_tools.sql.');
}

include __DIR__ . '/../../view/instructor/forum.php';
