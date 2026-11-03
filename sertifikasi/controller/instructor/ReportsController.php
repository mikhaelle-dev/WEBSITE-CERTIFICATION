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

$tools = new InstructorTools();
$error = '';
try {
    $classes = $tools->getInstructorClasses($instructorId);
} catch (Throwable $exception) {
    error_log('Instructor report class list failed: ' . $exception->getMessage());
    http_response_code(500);
    $classes = [];
    $error = 'Daftar kelas gagal dimuat.';
}
$today = date('Y-m-d');
$defaultStart = date('Y-m-d', strtotime('-29 days'));
$startDate = isset($_GET['start_date']) && is_string($_GET['start_date']) ? $_GET['start_date'] : $defaultStart;
$endDate = isset($_GET['end_date']) && is_string($_GET['end_date']) ? $_GET['end_date'] : $today;
$classId = filter_var($_GET['class_id'] ?? null, FILTER_VALIDATE_INT);
$report = null;

$validDate = static function (string $date): bool {
    $parsed = DateTime::createFromFormat('!Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
};

if (isset($_GET['class_id']) && !$classId && $error === '') {
    http_response_code(422);
    $error = 'Pilih kelas yang valid.';
}
if ((!$validDate($startDate) || !$validDate($endDate) || $startDate > $endDate) && $error === '') {
    http_response_code(422);
    $error = 'Rentang tanggal tidak valid.';
    $startDate = $defaultStart;
    $endDate = $today;
}

if ($classId && $error === '') {
    try {
        $report = $tools->getClassReport($instructorId, (int) $classId, $startDate, $endDate);
    } catch (InvalidArgumentException $exception) {
        http_response_code(404);
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        error_log('Instructor class report failed: ' . $exception->getMessage());
        http_response_code(500);
        $error = 'Laporan gagal dimuat. Pastikan migrasi sertifikat telah diterapkan.';
    }
}

if (($_GET['format'] ?? '') === 'pdf') {
    if ($report === null || $error !== '') {
        http_response_code(422);
        exit('Pilih kelas dan rentang tanggal yang valid sebelum mengekspor laporan.');
    }

    $pdfText = static function ($value): string {
        $value = (string) $value;
        $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);
        if ($converted === false) {
            $converted = preg_replace('/[^\x20-\x7E]/', '?', $value);
        }
        return str_replace(["\\", '(', ')', "\r", "\n"], ["\\\\", '\\(', '\\)', ' ', ' '], (string) $converted);
    };
    $fit = static function ($value, int $width) use ($pdfText): string {
        $text = $pdfText($value);
        return strlen($text) > $width ? substr($text, 0, max(0, $width - 3)) . '...' : $text;
    };
    $rows = [];
    foreach ($report['students'] as $student) {
        $rows[] = [
            $fit($student['nama'], 25),
            $fit($student['email'], 34),
            (int) $student['progress'] . '%',
            $student['final_score'] === null ? '-' : (int) $student['final_score'],
            $student['passed'] ? 'Lulus' : 'Belum lulus',
            $student['certificate_issued'] ? 'Terbit' : 'Belum',
        ];
    }
    $pageRows = array_chunk($rows, 30);
    if (!$pageRows) {
        $pageRows = [[]];
    }

    $objects = [];
    $nextObject = 1;
    $catalogId = $nextObject++;
    $pagesId = $nextObject++;
    $fontId = $nextObject++;
    $boldFontId = $nextObject++;
    $pageIds = [];
    foreach ($pageRows as $pageIndex => $pageData) {
        $pageId = $nextObject++;
        $streamId = $nextObject++;
        $pageIds[] = $pageId;
        $content = "BT /F2 15 Tf 34 570 Td (" . $pdfText('Laporan & Analitik - ' . $report['class']['judul']) . ") Tj ET\n";
        $content .= "BT /F1 9 Tf 34 552 Td (" . $pdfText('Periode: ' . $startDate . ' s.d. ' . $endDate) . ") Tj ET\n";
        if ($pageIndex === 0) {
            $metrics = $report['metrics'];
            $avgScore = $metrics['average_score'] === null ? '-' : $metrics['average_score'] . '%';
            $content .= "BT /F1 9 Tf 34 534 Td (" . $pdfText(
                'Rata-rata nilai: ' . $avgScore . '   Kelulusan: ' . $metrics['pass_percentage'] .
                '%   Sertifikat terbit: ' . $metrics['issued_certificates']
            ) . ") Tj ET\n";
        }
        $headerY = $pageIndex === 0 ? 506 : 530;
        $content .= "BT /F2 8 Tf 34 " . $headerY . " Td (Nama) Tj 142 0 Td (Email) Tj 212 0 Td (Progres) Tj 48 0 Td (Nilai) Tj 52 0 Td (Kelulusan) Tj 77 0 Td (Sertifikat) Tj ET\n";
        $rowY = $headerY - 16;
        foreach ($pageData as $row) {
            $content .= "BT /F1 8 Tf 34 " . $rowY . " Td (" . $row[0] . ") Tj 142 0 Td (" . $row[1] . ") Tj 212 0 Td (" . $row[2] . ") Tj 48 0 Td (" . $row[3] . ") Tj 52 0 Td (" . $row[4] . ") Tj 77 0 Td (" . $row[5] . ") Tj ET\n";
            $rowY -= 14;
        }
        $objects[$streamId] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "endstream";
        $objects[$pageId] = '<< /Type /Page /Parent ' . $pagesId . ' 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 ' . $fontId . ' 0 R /F2 ' . $boldFontId . ' 0 R >> >> /Contents ' . $streamId . ' 0 R >>';
    }
    $objects[$catalogId] = '<< /Type /Catalog /Pages ' . $pagesId . ' 0 R >>';
    $objects[$pagesId] = '<< /Type /Pages /Kids [' . implode(' ', array_map(static function (int $id): string {
        return $id . ' 0 R';
    }, $pageIds)) . '] /Count ' . count($pageIds) . ' >>';
    $objects[$fontId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
    $objects[$boldFontId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';
    ksort($objects);

    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = [0];
    foreach ($objects as $id => $body) {
        $offsets[$id] = strlen($pdf);
        $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
    }
    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . ($nextObject) . "\n0000000000 65535 f \n";
    for ($id = 1; $id < $nextObject; $id++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
    }
    $pdf .= "trailer\n<< /Size " . $nextObject . ' /Root ' . $catalogId . " 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="laporan-kelas-' . (int) $classId . '.pdf"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit();
}

$data = [
    'username' => $_SESSION['username'] ?? 'Instruktur',
    'classes' => $classes,
    'classId' => $classId ? (int) $classId : 0,
    'startDate' => $startDate,
    'endDate' => $endDate,
    'report' => $report,
    'error' => $error,
];

include __DIR__ . '/../../view/instructor/reports.php';
