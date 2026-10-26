<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../class/Auth.php';
require_once __DIR__ . '/../../class/Feedback.php';

if (!isset($_SESSION['logged_in'], $_SESSION['id_user'], $_SESSION['role'])
    || $_SESSION['logged_in'] !== true
    || !in_array($_SESSION['role'], ['siswa', 'instruktur', 'admin'], true)) {
    header('Location: ../../view/login.php');
    exit();
}

$auth = new Auth();
if ($auth->isRestrictedAccount((int) $_SESSION['id_user']) || !empty($_SESSION['account_restricted'])) {
    $_SESSION['account_restricted'] = true;
    header('Location: ../AccountDisabledController.php');
    exit();
}

$tab = $_GET['tab'] ?? 'support';
if (!is_string($tab) || !in_array($tab, ['support', 'feedback'], true)) {
    $tab = 'support';
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$notice = $_SESSION['support_notice'] ?? null;
unset($_SESSION['support_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($tab !== 'feedback') {
        http_response_code(405);
        exit('Metode permintaan tidak diizinkan.');
    }

    $token = $_POST['csrf_token'] ?? '';
    $rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
    $type = $_POST['type'] ?? '';
    $message = $_POST['message'] ?? '';
    $allowedTypes = ['Saran dan kritik', 'Laporan bug', 'Permintaan fitur'];
    $messageLength = is_string($message) ? preg_match_all('/./us', trim($message)) : false;

    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $notice = ['type' => 'danger', 'message' => 'Sesi form tidak valid. Muat ulang halaman lalu coba lagi.'];
    } elseif (!is_int($rating) || $rating < 1 || $rating > 5
        || !is_string($type) || !in_array($type, $allowedTypes, true)
        || !is_string($message) || trim($message) === ''
        || $messageLength === false || $messageLength > 3000) {
        $notice = ['type' => 'danger', 'message' => 'Periksa kembali rating dan masukan. Pesan wajib diisi dan maksimal 3000 karakter.'];
    } else {
        try {
            (new Feedback())->create((int) $_SESSION['id_user'], $rating, $type, trim($message));
            $_SESSION['support_notice'] = ['type' => 'success', 'message' => 'Terima kasih! Masukanmu berhasil dikirim.'];
            header('Location: SupportController.php?tab=feedback');
            exit();
        } catch (PDOException $e) {
            error_log('Save user feedback error: ' . $e->getMessage());
            $message = ($e->errorInfo[1] ?? null) === 1146
                ? 'Fitur masukan belum aktif. Minta admin menjalankan sertifikasi/database/13_feedback.sql terlebih dahulu.'
                : 'Masukan belum berhasil disimpan. Silakan coba lagi nanti.';
            $notice = ['type' => 'danger', 'message' => $message];
        }
    }
}

$data = [
    'username' => $_SESSION['username'] ?? 'Pengguna',
    'role' => $_SESSION['role'],
    'foto_profil' => $_SESSION['foto_profil'] ?? null,
];
$adminEmail = $auth->getActiveAdminEmail() ?? admin_contact_email();

include __DIR__ . '/../../view/user/support.php';
