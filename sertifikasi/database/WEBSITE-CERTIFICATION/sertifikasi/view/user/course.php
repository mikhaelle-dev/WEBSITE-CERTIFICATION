<?php
// View materi dan kuis. Data dimuat dari database oleh CourseController.
$course = $data['course'];
$quiz = $data['quiz'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($course['judul']) ?> | Sertifikasi Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../asset/css/dashboard.css" rel="stylesheet">
    <link href="../../asset/css/course.css?v=2" rel="stylesheet">
    <link href="../../asset/css/brutal.css" rel="stylesheet">
</head>
<body class="brutal-app">
<?php $aktif = 'mylearning'; include __DIR__ . '/../../component/navbar.php'; ?>

<main class="container py-4 course-page">
    <a class="course-back" href="DashboardController.php"><i class="bi bi-arrow-left"></i> Kembali ke My Learning</a>
    <header class="course-heading">
        <span class="badge text-bg-success"><?= e($course['kategori']) ?> · <?= e($course['level']) ?></span>
        <h1><?= e($course['judul']) ?></h1>
        <p><?= e($course['deskripsi']) ?></p>
        <small class="text-muted"><i class="bi bi-clock"></i> Estimasi <?= e($course['durasi']) ?></small>
    </header>

    <?php if ($data['errors']): ?>
        <div class="alert alert-danger">
            <ul class="mb-0"><?php foreach ($data['errors'] as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <aside class="col-lg-4">
            <nav class="course-outline" aria-label="Daftar materi kursus">
                <h2 class="h5">Materi kursus</h2>
                <?php foreach ($data['lessons'] as $item): ?>
                    <?php
                    $submateriSelesai = 0;
                    foreach ($item['submateri'] as $sublesson) {
                        if (in_array((int) $sublesson['id'], $data['submateriDibuka'], true)) {
                            $submateriSelesai++;
                        }
                    }
                    $jumlahSubmateri = count($item['submateri']);
                    $persentaseModul = $jumlahSubmateri > 0 ? (int) floor(($submateriSelesai / $jumlahSubmateri) * 100) : 0;
                    $sedangAktif = !$data['isQuiz'] && (int) $item['id'] === (int) $data['lesson']['id'];
                    ?>
                    <details class="module-outline" <?= $sedangAktif ? 'open' : '' ?>>
                        <summary class="module-outline-summary">
                            <span class="module-outline-title">
                                <span class="outline-number <?= $persentaseModul === 100 ? 'is-complete' : '' ?>">
                                    <?php if ($persentaseModul === 100): ?><i class="bi bi-check-lg" aria-label="Modul selesai"></i><?php else: ?><?= (int) $item['urutan'] ?><?php endif; ?>
                                </span>
                                <span><?= e($item['judul']) ?></span>
                            </span>
                            <span class="module-progress-value"><?= $persentaseModul ?>%</span>
                            <span class="module-progress-track" role="progressbar" aria-label="Progres modul <?= e($item['judul']) ?>" aria-valuenow="<?= $persentaseModul ?>" aria-valuemin="0" aria-valuemax="100">
                                <span data-progress="<?= $persentaseModul ?>"></span>
                            </span>
                        </summary>
                        <div class="sublesson-list">
                            <?php foreach ($item['submateri'] as $sublesson): ?>
                                <?php
                                $sublessonOpened = in_array((int) $sublesson['id'], $data['submateriDibuka'], true);
                                $sublessonActive = !$data['isQuiz'] && (int) $sublesson['id'] === (int) $data['sublesson']['id'];
                                ?>
                                <a class="sublesson-link <?= $sublessonActive ? 'active' : '' ?> <?= $sublessonOpened ? 'is-complete' : '' ?>"
                                   <?= $sublessonActive ? 'aria-current="page"' : '' ?>
                                   href="?slug=<?= urlencode($course['slug']) ?>&submateri=<?= (int) $sublesson['id'] ?>">
                                    <span class="sublesson-status">
                                        <?php if ($sublessonOpened): ?><i class="bi bi-check-lg" aria-label="Sudah dipelajari"></i><?php else: ?><?= (int) $item['urutan'] ?>.<?= (int) $sublesson['urutan'] ?><?php endif; ?>
                                    </span>
                                    <span><?= e($sublesson['judul']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </details>
                <?php endforeach; ?>
                <?php
                $kuisSudahDikerjakan = $data['attemptTerakhir'] !== null;
                $kuisLulus = $kuisSudahDikerjakan && (int) $data['attemptTerakhir']['lulus'] === 1;
                ?>
                <a class="outline-link <?= $data['isQuiz'] ? 'active' : '' ?> <?= $kuisLulus ? 'is-complete' : '' ?> <?= $kuisSudahDikerjakan && !$kuisLulus ? 'needs-retry' : '' ?>"
                   <?= $data['isQuiz'] ? 'aria-current="page"' : '' ?>
                   href="?slug=<?= urlencode($course['slug']) ?>&bagian=kuis">
                    <span class="outline-number">
                        <?php if ($kuisSudahDikerjakan): ?>
                            <i class="bi <?= (int) $data['attemptTerakhir']['lulus'] === 1 ? 'bi-check-lg' : 'bi-exclamation-lg' ?>"
                               aria-label="<?= (int) $data['attemptTerakhir']['lulus'] === 1 ? 'Kuis lulus' : 'Kuis perlu diulang' ?>"></i>
                        <?php else: ?>
                            <i class="bi bi-patch-question"></i>
                        <?php endif; ?>
                    </span>
                    <span class="outline-title"><?= e($quiz['judul']) ?></span>
                    <?php if ($kuisSudahDikerjakan): ?>
                        <span class="quiz-score-badge <?= (int) $data['attemptTerakhir']['lulus'] === 1 ? 'passed' : 'needs-retry' ?>">
                            <?= (int) $data['attemptTerakhir']['nilai'] ?>
                        </span>
                    <?php endif; ?>
                </a>
            </nav>
        </aside>

        <section class="col-lg-8">
            <?php if ($data['isQuiz']): ?>
                <article class="course-content">
                    <h2><?= e($quiz['judul']) ?></h2>
                    <p>Jawab semua pertanyaan. Nilai minimum untuk lulus adalah <?= (int) $quiz['nilai_minimum'] ?>.</p>

                    <?php if ($data['hasilKuis']): ?>
                        <?php
                        $nilaiKuis = (int) $data['hasilKuis']['nilai'];
                        $offsetGauge = 100 - $nilaiKuis;
                        ?>
                        <section class="quiz-result <?= $data['hasilKuis']['lulus'] ? 'passed' : 'failed' ?>" aria-live="polite">
                            <div class="quiz-gauge" role="img" aria-label="Nilai <?= $nilaiKuis ?> dari 100">
                                <svg viewBox="0 0 200 112" aria-hidden="true">
                                    <path class="quiz-gauge-track" d="M 20 100 A 80 80 0 0 1 180 100" pathLength="100"></path>
                                    <path class="quiz-gauge-value" d="M 20 100 A 80 80 0 0 1 180 100" pathLength="100" stroke-dasharray="100" stroke-dashoffset="<?= $offsetGauge ?>"></path>
                                </svg>
                                <strong><?= $nilaiKuis ?><span>/100</span></strong>
                            </div>
                            <h3><?= $data['hasilKuis']['lulus'] ? 'Selamat, kamu lulus!' : 'Kuis belum lulus' ?></h3>
                            <p>Kamu menjawab benar <?= (int) $data['hasilKuis']['jumlah_benar'] ?> dari <?= (int) $data['hasilKuis']['jumlah_soal'] ?> soal.</p>
                            <?php if (!$data['hasilKuis']['lulus']): ?>
                                <p class="quiz-result-hint">Pelajari kembali materi, lalu coba lagi. Nilai minimum kelulusan adalah <?= (int) $quiz['nilai_minimum'] ?>.</p>
                            <?php endif; ?>
                            <a class="btn btn-enroll px-4 mt-2" href="?slug=<?= urlencode($course['slug']) ?>&bagian=kuis&ulang=1">
                                <?= $data['hasilKuis']['lulus'] ? 'Ulangi kuis' : 'Coba lagi' ?>
                            </a>
                        </section>
                    <?php elseif ($data['attemptTerakhir']): ?>
                        <p class="small text-muted">Percobaan terakhir: nilai <?= (int) $data['attemptTerakhir']['nilai'] ?>.</p>
                    <?php endif; ?>

                    <?php if (!$data['hasilKuis']): ?>
                        <form method="POST" action="CourseController.php?slug=<?= urlencode($course['slug']) ?>&bagian=kuis" class="quiz-form" id="quizForm">
                            <input type="hidden" name="aksi" value="kirim_kuis">
                            <input type="hidden" name="csrf_token" value="<?= e($data['csrf']) ?>">
                            <div class="quiz-question-nav" aria-label="Navigasi soal">
                                <?php foreach ($quiz['soal'] as $index => $question): ?>
                                    <button type="button" class="quiz-question-jump <?= $index === 0 ? 'is-current' : '' ?>" data-question-index="<?= $index ?>" aria-label="Soal <?= $index + 1 ?>" aria-current="<?= $index === 0 ? 'step' : 'false' ?>">
                                        <?= $index + 1 ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <?php foreach ($quiz['soal'] as $index => $question): ?>
                                <fieldset class="quiz-question <?= $index === 0 ? 'is-active' : '' ?>" data-question-index="<?= $index ?>">
                                    <legend><span>Soal <?= $index + 1 ?> dari <?= count($quiz['soal']) ?></span><?= e($question['teks']) ?></legend>
                                    <?php foreach ($question['opsi'] as $option): ?>
                                        <label class="quiz-option">
                                            <input type="radio" name="jawaban[<?= (int) $question['id'] ?>]" value="<?= (int) $option['id'] ?>" <?= $index === 0 ? 'required' : '' ?>>
                                            <span><?= e($option['teks']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </fieldset>
                            <?php endforeach; ?>
                            <p class="quiz-navigation-message" id="quizNavigationMessage" aria-live="polite"></p>
                            <div class="quiz-navigation">
                                <button class="btn btn-outline-secondary" type="button" id="previousQuestion" hidden><i class="bi bi-arrow-left"></i> Sebelumnya</button>
                                <button class="btn btn-enroll" type="button" id="nextQuestion" hidden>Selanjutnya <i class="bi bi-arrow-right"></i></button>
                                <button class="btn btn-enroll" type="submit" id="submitQuiz">Kirim jawaban <i class="bi bi-check2"></i></button>
                            </div>
                        </form>
                    <?php endif; ?>
                </article>
            <?php else: ?>
                <article class="course-content">
                    <div class="lesson-counter">
                        Modul <?= (int) $data['lesson']['urutan'] ?> dari <?= count($data['lessons']) ?>
                        · Submateri <?= (int) $data['sublesson']['urutan'] ?> dari <?= count($data['lesson']['submateri']) ?>
                    </div>
                    <h2><?= e($data['sublesson']['judul']) ?></h2>
                    <div class="lesson-body"><?= $data['sublesson']['isi'] ?></div>
                    <div class="lesson-navigation">
                        <?php $nextSublesson = $data['sublessons'][$data['sublessonIndex'] + 1] ?? null; ?>
                        <?php if ($nextSublesson): ?>
                            <a class="btn btn-enroll px-4" href="?slug=<?= urlencode($course['slug']) ?>&submateri=<?= (int) $nextSublesson['id'] ?>">
                                Materi berikutnya <i class="bi bi-arrow-right"></i>
                            </a>
                        <?php else: ?>
                            <a class="btn btn-enroll px-4" href="?slug=<?= urlencode($course['slug']) ?>&bagian=kuis">
                                Mulai kuis <i class="bi bi-arrow-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endif; ?>
        </section>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../asset/js/page-actions.js"></script>
<?php if ($data['isQuiz'] && !$data['hasilKuis']): ?>
<script src="../../asset/js/course-quiz.js?v=1"></script>
<?php endif; ?>
</body>
</html>
