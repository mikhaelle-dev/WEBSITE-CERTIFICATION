<?php
session_start();

require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../class/Course.php';

if (
    !isset($_SESSION['logged_in'], $_SESSION['id_user'], $_SESSION['role'])
    || $_SESSION['logged_in'] !== true
    || $_SESSION['role'] !== 'siswa'
) {
    header('Location: ../../view/login.php');
    exit();
}

$certificateId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($certificateId === false || $certificateId === null || $certificateId < 1) {
    http_response_code(404);
    exit('Sertifikat tidak ditemukan.');
}

$certificate = (new Course())->getCertificateForUser($certificateId, (int) $_SESSION['id_user']);
if ($certificate === null) {
    http_response_code(404);
    exit('Sertifikat tidak ditemukan.');
}

$data = ['certificate' => $certificate];
include __DIR__ . '/../../view/user/certificate.php';
