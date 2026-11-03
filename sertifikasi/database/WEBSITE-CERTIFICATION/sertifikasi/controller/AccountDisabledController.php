<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../class/Auth.php';

if (
    empty($_SESSION['logged_in'])
    || empty($_SESSION['id_user'])
    || empty($_SESSION['account_restricted'])
) {
    header('Location: ../view/login.php');
    exit();
}

$auth = new Auth();
$accountAccess = $auth->getAccountAccess((int) $_SESSION['id_user']);
if ($accountAccess === null) {
    header('Location: AuthController.php?aksi=logout');
    exit();
}

if ($accountAccess['status'] === 1 && $accountAccess['masuk_sampah_pada'] === null) {
    unset($_SESSION['account_restricted']);
    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: admin/DashboardController.php');
    } else {
        header('Location: user/DashboardController.php');
    }
    exit();
}

$data = [
    'username' => $_SESSION['username'] ?? '',
    'inTrash' => $accountAccess['masuk_sampah_pada'] !== null,
    'remainingSeconds' => $accountAccess['waktu_sampah_tersisa'],
];

include __DIR__ . '/../view/account_disabled.php';
