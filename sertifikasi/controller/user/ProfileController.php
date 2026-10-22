<?php
/**
 * Profil Siswa: 3 tab (profil, sertifikat & lencana, riwayat belajar)
 * Controller: cek sesi -> proses form -> siapkan data -> include view
 */
session_start();

require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../class/Auth.php';
require_once __DIR__ . '/../../class/Course.php';

$DEV_MODE = false;   // login sungguhan sudah jadi (set true hanya untuk demo tanpa database)

if ($DEV_MODE && !isset($_SESSION['logged_in'])) {
    $_SESSION['logged_in'] = true;
    $_SESSION['username']  = 'mikhael';
    $_SESSION['role']      = 'siswa';
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'siswa') {
    header('Location: ../../view/login.php');
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ---------- tab aktif (whitelist) ----------
$tabValid = ['profil', 'sertifikat', 'riwayat'];
$tab = $_GET['tab'] ?? 'profil';
if (!is_string($tab) || !in_array($tab, $tabValid, true)) {
    $tab = 'profil';
}

$pesan   = '';
$errors  = [];

$opsiBahasa = ['Indonesia', 'English'];
$opsiKarier = ['Network Engineer', 'Cloud Engineer', 'DevOps Engineer', 'Security Analyst', 'Web Developer'];

try {
    $akunProfil = (new Auth())->getProfile((int) $_SESSION['id_user']);
    if ($akunProfil === null) {
        http_response_code(404);
        exit('Akun tidak ditemukan.');
    }

    $bagianNama = preg_split('/\s+/', trim($akunProfil['nama']), 2);
    $profil = [
        'nama_depan'    => $bagianNama[0] ?? '',
        'nama_belakang' => $bagianNama[1] ?? '',
        'email'         => $akunProfil['email'],
        'bahasa'        => $akunProfil['bahasa'],
        'negara'        => $akunProfil['negara'],
        'provinsi'      => $akunProfil['provinsi'],
        'tujuan_karier' => $akunProfil['tujuan_karier'],
        'foto_profil'   => $akunProfil['foto_profil'],
    ];
} catch (PDOException $e) {
    error_log('Load profile error: ' . $e->getMessage());
    $errors[] = 'Profil tidak dapat dimuat dari database. Silakan coba lagi.';
    $profil = [
        'nama_depan' => '',
        'nama_belakang' => '',
        'email' => $_SESSION['email'] ?? '',
        'bahasa' => 'Indonesia',
        'negara' => '',
        'provinsi' => '',
        'tujuan_karier' => '',
        'foto_profil' => null,
    ];
}

// ---------- simpan profil atau foto profil ----------
$aksiSimpan = $_POST['aksi'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($aksiSimpan, ['simpan_profil', 'simpan_foto'], true)) {
    $tab = 'profil';
    $hanyaSimpanFoto = $aksiSimpan === 'simpan_foto';

    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $errors[] = 'Sesi form tidak valid, muat ulang halaman.';
    } else {
        $baru = $hanyaSimpanFoto ? $profil : [
            'nama_depan'    => trim((string) ($_POST['nama_depan'] ?? '')),
            'nama_belakang' => trim((string) ($_POST['nama_belakang'] ?? '')),
            'bahasa'        => (string) ($_POST['bahasa'] ?? ''),
            'negara'        => trim((string) ($_POST['negara'] ?? '')),
            'provinsi'      => trim((string) ($_POST['provinsi'] ?? '')),
            'tujuan_karier' => (string) ($_POST['tujuan_karier'] ?? ''),
        ];
        $namaLengkapBaru = trim($baru['nama_depan'] . ' ' . $baru['nama_belakang']);
        $fotoLama = $profil['foto_profil'];
        $fotoBaru = null;

        if (!$hanyaSimpanFoto) {
            if ($baru['nama_depan'] === '')                              $errors[] = 'Nama depan wajib diisi.';
            if (mb_strlen($baru['nama_depan']) > 50)                     $errors[] = 'Nama depan maksimal 50 karakter.';
            if (mb_strlen($baru['nama_belakang']) > 50)                  $errors[] = 'Nama belakang maksimal 50 karakter.';
            if (mb_strlen($namaLengkapBaru) > 100)                       $errors[] = 'Nama lengkap maksimal 100 karakter.';
            if (!in_array($baru['bahasa'], $opsiBahasa, true))           $errors[] = 'Bahasa tidak valid.';
            if ($baru['negara'] === '')                                  $errors[] = 'Negara wajib diisi.';
            if (mb_strlen($baru['negara']) > 100)                        $errors[] = 'Negara maksimal 100 karakter.';
            if (mb_strlen($baru['provinsi']) > 100)                      $errors[] = 'Provinsi maksimal 100 karakter.';
            if ($baru['tujuan_karier'] !== '' && !in_array($baru['tujuan_karier'], $opsiKarier, true)) {
                $errors[] = 'Tujuan karier tidak valid.';
            }
        }

        $unggahan = $_FILES['foto_profil'] ?? null;
        if ($hanyaSimpanFoto && (!is_array($unggahan) || $unggahan['error'] === UPLOAD_ERR_NO_FILE)) {
            $errors[] = 'Pilih foto yang ingin digunakan.';
        }
        if (is_array($unggahan) && $unggahan['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($unggahan['error'] !== UPLOAD_ERR_OK) {
                $errors[] = $unggahan['error'] === UPLOAD_ERR_INI_SIZE || $unggahan['error'] === UPLOAD_ERR_FORM_SIZE
                    ? 'Ukuran foto maksimal 2 MB.'
                    : 'Foto gagal diunggah. Silakan coba lagi.';
            } elseif ($unggahan['size'] < 1 || $unggahan['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Ukuran foto harus lebih dari 0 dan maksimal 2 MB.';
            } elseif (!is_uploaded_file($unggahan['tmp_name'])) {
                $errors[] = 'File foto tidak valid.';
            } else {
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($unggahan['tmp_name']);
                $tipeGambar = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                ];
                $infoGambar = @getimagesize($unggahan['tmp_name']);

                if (!isset($tipeGambar[$mime]) || $infoGambar === false || $infoGambar['mime'] !== $mime) {
                    $errors[] = 'Format foto harus JPEG, PNG, atau WebP.';
                } elseif (!$errors) {
                    $direktoriFoto = __DIR__ . '/../../uploads/profiles';
                    if (!is_dir($direktoriFoto) && !mkdir($direktoriFoto, 0755, true) && !is_dir($direktoriFoto)) {
                        error_log('Profile photo upload error: cannot create upload directory.');
                        $errors[] = 'Folder penyimpanan foto tidak tersedia.';
                    } else {
                        $namaFileFoto = bin2hex(random_bytes(16)) . '.' . $tipeGambar[$mime];
                        $jalurFoto = $direktoriFoto . '/' . $namaFileFoto;
                        if (move_uploaded_file($unggahan['tmp_name'], $jalurFoto)) {
                            $fotoBaru = 'uploads/profiles/' . $namaFileFoto;
                        } else {
                            error_log('Profile photo upload error: move_uploaded_file failed.');
                            $errors[] = 'Foto gagal disimpan. Pastikan folder uploads dapat ditulis.';
                        }
                    }
                }
            }
        }

        if (!$errors) {
            try {
                $berhasil = (new Auth())->updateProfile(
                    (int) $_SESSION['id_user'],
                    $namaLengkapBaru,
                    [
                        'bahasa' => $baru['bahasa'],
                        'negara' => $baru['negara'],
                        'provinsi' => $baru['provinsi'],
                        'tujuan_karier' => $baru['tujuan_karier'],
                        'foto_profil' => $fotoBaru,
                    ]
                );

                if ($berhasil) {
                    $awalanFotoProfil = 'uploads/profiles/';
                    $namaFotoLama = $fotoLama !== null && strpos($fotoLama, $awalanFotoProfil) === 0
                        ? substr($fotoLama, strlen($awalanFotoProfil))
                        : '';
                    if ($fotoBaru !== null && $namaFotoLama !== '' && basename($namaFotoLama) === $namaFotoLama) {
                        $jalurFotoLama = dirname(__DIR__, 2) . '/' . $awalanFotoProfil . $namaFotoLama;
                        if (is_file($jalurFotoLama) && !unlink($jalurFotoLama)) {
                            error_log('Profile photo cleanup error: unable to delete ' . $jalurFotoLama);
                        }
                    }
                    $_SESSION['username'] = $namaLengkapBaru;
                    $_SESSION['foto_profil'] = $fotoBaru ?? $fotoLama;
                    $statusSimpan = $hanyaSimpanFoto ? 'photo_saved' : 'saved';
                    header('Location: ProfileController.php?tab=profil&status=' . $statusSimpan);
                    exit();
                }
                if ($fotoBaru !== null) {
                    unlink(dirname(__DIR__, 2) . '/' . $fotoBaru);
                }
                $errors[] = 'Akun tidak ditemukan. Silakan login kembali.';
            } catch (PDOException $e) {
                if ($fotoBaru !== null && is_file(dirname(__DIR__, 2) . '/' . $fotoBaru)) {
                    unlink(dirname(__DIR__, 2) . '/' . $fotoBaru);
                }
                error_log('Update profile error: ' . $e->getMessage());
                $errors[] = 'Profil gagal disimpan ke database. Silakan coba lagi.';
            }
        }
        $profil = array_merge($profil, $baru);   // biar input user tidak hilang
        $profil['foto_profil'] = $fotoLama;
    }
}

if (($_GET['status'] ?? '') === 'saved') {
    $pesan = 'Profil berhasil disimpan.';
} elseif (($_GET['status'] ?? '') === 'photo_saved') {
    $pesan = 'Foto profil berhasil diperbarui.';
}

// ---------- tab sertifikat: filter jenis + cari ----------
$jenisValid  = ['Semua', 'Sertifikat', 'Lencana'];
$jenis       = isset($_GET['jenis']) && in_array($_GET['jenis'], $jenisValid, true) ? $_GET['jenis'] : 'Semua';
$q           = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';

$courseModel = new Course();
$semuaPencapaian = $courseModel->getAchievements((int) $_SESSION['id_user']);
$pencapaian = array_values(array_filter($semuaPencapaian, function ($p) use ($jenis, $q) {
    if ($jenis !== 'Semua' && $p['jenis'] !== $jenis) return false;
    if (
        $q !== ''
        && stripos($p['judul'] . ' ' . $p['kursus_judul'], $q) === false
    ) return false;
    return true;
}));

// ---------- tab riwayat: filter status + cari ----------
$statusValid = ['Semua', 'Selesai'];
$status      = isset($_GET['status_belajar']) && in_array($_GET['status_belajar'], $statusValid, true) ? $_GET['status_belajar'] : 'Semua';

$riwayatKursus = $courseModel->getCompletedCourses((int) $_SESSION['id_user']);
$riwayat = array_values(array_filter($riwayatKursus, function ($r) use ($status, $q) {
    if ($status !== 'Semua' && $r['status'] !== $status) return false;
    if ($q !== '' && stripos($r['judul'], $q) === false) return false;
    return true;
}));

// ---------- statistik banner (dihitung dari data penuh, bukan hasil filter) ----------
$totalSertifikat = count(array_filter($semuaPencapaian, fn($p) => $p['jenis'] === 'Sertifikat'));
$totalSelesai    = count($riwayatKursus);

$namaLengkap = trim($profil['nama_depan'] . ' ' . $profil['nama_belakang']);

$data = [
    'username'        => $_SESSION['username'],
    'foto_profil'     => $profil['foto_profil'],
    'role'            => $_SESSION['role'],
    'tab'             => $tab,
    'profil'          => $profil,
    'namaLengkap'     => $namaLengkap,
    'pesan'           => $pesan,
    'errors'          => $errors,
    'csrf'            => $_SESSION['csrf_token'],
    'opsiBahasa'      => $opsiBahasa,
    'opsiKarier'      => $opsiKarier,
    'pencapaian'      => $pencapaian,
    'jenis'           => $jenis,
    'jenisValid'      => $jenisValid,
    'riwayat'         => $riwayat,
    'statusBelajar'   => $status,
    'statusValid'     => $statusValid,
    'q'               => $q,
    'totalSertifikat' => $totalSertifikat,
    'totalSelesai'    => $totalSelesai,
];

include __DIR__ . '/../../view/user/profile.php';
