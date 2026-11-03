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

if (isset($_GET['ajax']) && $_GET['ajax'] === 'verify') {
    $number = trim((string) ($_GET['number'] ?? ''));
    if ($number === '' || strlen($number) > 180) {
        $json(['success' => false, 'message' => 'Masukkan kode sertifikat yang valid.'], 422);
    }
    try {
        $certificate = $tools->verifyCertificate($number, $instructorId);
        $json(['success' => true, 'certificate' => $certificate]);
    } catch (Throwable $e) {
        error_log('Instructor certificate verification failed: ' . $e->getMessage());
        $json(['success' => false, 'message' => 'Validasi sertifikat gagal diproses.'], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $json(['success' => false, 'message' => 'Sesi form tidak valid. Muat ulang halaman.'], 403);
    }
    try {
        $action = (string) ($_POST['aksi'] ?? '');
        if ($action === 'save_template') {
            $current = $tools->getCertificateTemplate($instructorId);
            $uploads = [
                'certificate_background' => ['field' => 'background_path', 'prefix' => 'background', 'allowed' => ['image/png' => 'png', 'image/jpeg' => 'jpg']],
                'certificate_signature' => ['field' => 'signature_path', 'prefix' => 'signature', 'allowed' => ['image/png' => 'png']],
            ];
            $paths = [
                'background_path' => (string) ($current['background_path'] ?? ''),
                'signature_path' => (string) ($current['signature_path'] ?? ''),
            ];
            foreach ($uploads as $input => $config) {
                if (!isset($_FILES[$input]) || (int) $_FILES[$input]['error'] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                if ((int) $_FILES[$input]['error'] !== UPLOAD_ERR_OK) {
                    throw new InvalidArgumentException('Upload gambar sertifikat gagal.');
                }
                $tmpFile = $_FILES[$input]['tmp_name'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = $finfo !== false ? finfo_file($finfo, $tmpFile) : null;
                if ($finfo !== false) { finfo_close($finfo); }
                $size = (int) ($_FILES[$input]['size'] ?? 0);
                if ($mime === null || !isset($config['allowed'][$mime]) || $size < 1 || $size > 5 * 1024 * 1024 || getimagesize($tmpFile) === false) {
                    throw new InvalidArgumentException('Gambar template/tanda tangan tidak sesuai format atau melebihi 5 MB.');
                }
                $directory = __DIR__ . '/../../uploads/certificates';
                if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                    throw new RuntimeException('Folder upload sertifikat tidak bisa dibuat.');
                }
                $fileName = $config['prefix'] . '-' . $instructorId . '-' . bin2hex(random_bytes(12)) . '.' . $config['allowed'][$mime];
                if (!move_uploaded_file($tmpFile, $directory . DIRECTORY_SEPARATOR . $fileName)) {
                    throw new RuntimeException('Gagal menyimpan gambar sertifikat.');
                }
                $paths[$config['field']] = '../../uploads/certificates/' . $fileName;
            }

            $tools->saveCertificateTemplate($instructorId, [
                'background_path' => $paths['background_path'],
                'signature_path' => $paths['signature_path'],
                'signer_name' => $_POST['signer_name'] ?? '',
                'signer_title' => $_POST['signer_title'] ?? '',
                'number_format' => $_POST['number_format'] ?? '',
            ]);
            $json(['success' => true, 'message' => 'Pengaturan sertifikat berhasil disimpan.']);
        }

        if ($action === 'issue') {
            $classId = filter_var($_POST['class_id'] ?? null, FILTER_VALIDATE_INT);
            $studentId = filter_var($_POST['student_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$classId || !$studentId) {
                throw new InvalidArgumentException('Peserta atau kelas tidak valid.');
            }
            $tools->issueCertificate($instructorId, (int) $classId, (int) $studentId);
            $json(['success' => true, 'message' => 'Sertifikat berhasil diterbitkan.']);
        }

        if ($action === 'issue_all') {
            $count = $tools->issueAllEligibleCertificates($instructorId);
            $json(['success' => true, 'message' => $count . ' sertifikat berhasil diterbitkan.']);
        }

        throw new InvalidArgumentException('Aksi sertifikat tidak valid.');
    } catch (InvalidArgumentException $e) {
        $json(['success' => false, 'message' => $e->getMessage()], 422);
    } catch (Throwable $e) {
        error_log('Instructor certificate operation failed: ' . $e->getMessage());
        $json(['success' => false, 'message' => 'Operasi sertifikat gagal diproses.'], 500);
    }
}

try {
    $data = [
        'username' => $_SESSION['username'] ?? 'Instruktur',
        'template' => $tools->getCertificateTemplate($instructorId),
        'certificates' => $tools->getEligibleCertificates($instructorId),
        'csrfToken' => $_SESSION['csrf_token'],
    ];
} catch (Throwable $e) {
    error_log('Instructor certificate dashboard failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Fitur sertifikat belum siap. Jalankan database/16_instructor_tools.sql.');
}

include __DIR__ . '/../../view/instructor/certificates.php';
