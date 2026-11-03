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

$certificateId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$certificate = $certificateId ? (new InstructorTools())->getIssuedCertificate($instructorId, (int) $certificateId) : null;
if ($certificate === null) {
    http_response_code(404);
    exit('Sertifikat tidak ditemukan.');
}
$template = (new InstructorTools())->getCertificateTemplate($instructorId);
include __DIR__ . '/../../view/instructor/certificate-print.php';
