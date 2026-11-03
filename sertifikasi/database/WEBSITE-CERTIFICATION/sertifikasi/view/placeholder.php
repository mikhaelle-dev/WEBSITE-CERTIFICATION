<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';

if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit();
}
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard <?= e(ucfirst($role)) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../asset/css/brutal.css" rel="stylesheet">
</head>
<body class="brutal-app placeholder-page d-flex align-items-center justify-content-center min-vh-100">
    <div class="text-center">
        <h1 class="h3">Dashboard <?= e($role) ?> belum dibuat</h1>
        <p class="text-muted">Login sebagai <strong><?= e($_SESSION['username']) ?></strong> berhasil. Role terbaca dari database: <code><?= e($role) ?></code>.</p>
        <a class="btn btn-primary me-2" href="../controller/user/ProfileController.php?tab=profil">Profil &amp; ganti password</a>
        <a class="btn btn-outline-dark" href="../controller/AuthController.php?aksi=logout">Logout</a>
    </div>
</body>
</html>
