<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../class/Auth.php';
require_once __DIR__ . '/../../class/InstructorTools.php';

if (
    !isset($_SESSION['logged_in'], $_SESSION['id_user'], $_SESSION['role'])
    || $_SESSION['logged_in'] !== true
    || $_SESSION['role'] !== 'siswa'
) {
    header('Location: ../../view/login.php');
    exit();
}

$studentId = (int) $_SESSION['id_user'];
$auth = new Auth();
if ($auth->isRestrictedAccount($studentId) || !empty($_SESSION['account_restricted'])) {
    $_SESSION['account_restricted'] = true;
    header('Location: ../AccountDisabledController.php');
    exit();
}
if ($auth->mustChangePassword($studentId)) {
    header('Location: ProfileController.php?tab=profil&must_change=1');
    exit();
}

$certificateId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$tools = new InstructorTools();
$certificate = $certificateId && $certificateId > 0
    ? $tools->getStudentIssuedCertificate($studentId, (int) $certificateId)
    : null;
if ($certificate === null) {
    http_response_code(404);
    exit('Sertifikat tidak ditemukan.');
}

$data = [
    'certificate' => [
        'nama_peserta' => $certificate['student_name'],
        'judul_kursus' => $certificate['class_title'],
        'kategori' => $certificate['category'],
        'diterbitkan_pada' => $certificate['issued_at'],
        'nomor_sertifikat' => $certificate['certificate_no'],
    ],
];
include __DIR__ . '/../../view/user/certificate.php';
