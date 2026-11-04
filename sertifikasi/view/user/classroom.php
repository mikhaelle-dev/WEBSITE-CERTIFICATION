<?php
$class = $data['class'] ?? null;
$tab = $data['tab'] ?? 'stream';
$announcements = $data['announcements'] ?? [];
$modules = $data['modules'] ?? [];
$quizzes = $data['quizzes'] ?? [];
$assignments = $data['assignments'] ?? [];
$forumThreads = $data['forumThreads'] ?? [];
$openedMaterialIds = $data['openedMaterialIds'] ?? [];
$resultsByQuiz = $data['resultsByQuiz'] ?? [];
$success = $data['success'] ?? '';
$error = $data['error'] ?? '';
$openedMap = array_fill_keys($openedMaterialIds, true);
$selectedModule = null;
$requestedModuleValue = $_GET['module_id'] ?? null;
$requestedModuleId = is_string($requestedModuleValue) || is_int($requestedModuleValue)
    ? filter_var($requestedModuleValue, FILTER_VALIDATE_INT)
    : false;
$completedMaterialCount = 0;
$totalClassMaterials = 0;
foreach ($modules as $module) {
    $moduleMaterials = $module['materi'] ?? [];
    $totalClassMaterials += (int) ($module['total_materi'] ?? count($moduleMaterials));
    foreach ($moduleMaterials as $material) {
        if (isset($openedMap[(int) $material['id']])) {
            $completedMaterialCount++;
        }
    }
    if ($requestedModuleId && (int) $module['id'] === (int) $requestedModuleId) {
        $selectedModule = $module;
    }
}
$quizCount = count($quizzes);
$completedQuizCount = count(array_filter(
    $quizzes,
    static fn (array $quiz): bool => (int) (($resultsByQuiz[(int) $quiz['id']]['lulus'] ?? 0)) === 1
));
$totalProgressItems = $totalClassMaterials + $quizCount;
$completedProgressItems = $completedMaterialCount + $completedQuizCount;
$classProgress = $totalProgressItems > 0
    ? (int) floor(($completedProgressItems / $totalProgressItems) * 100)
    : 0;
$selectedModule ??= $modules[0] ?? null;
$selectedMaterial = null;
$requestedMaterialValue = $_GET['material_id'] ?? null;
$requestedMaterialId = is_string($requestedMaterialValue) || is_int($requestedMaterialValue)
    ? filter_var($requestedMaterialValue, FILTER_VALIDATE_INT)
    : false;
$requestedQuizValue = $_GET['quiz_id'] ?? null;
$requestedQuizId = is_string($requestedQuizValue) || is_int($requestedQuizValue)
    ? filter_var($requestedQuizValue, FILTER_VALIDATE_INT)
    : false;
$selectedQuiz = null;
foreach ($quizzes as $quiz) {
    if ($requestedQuizId && (int) $quiz['id'] === (int) $requestedQuizId) {
        $selectedQuiz = $quiz;
        break;
    }
}
$selectedQuiz ??= $quizzes[0] ?? null;
if ($selectedModule !== null && !empty($selectedModule['is_unlocked'])) {
    foreach ($selectedModule['materi'] ?? [] as $material) {
        if ($requestedMaterialId && (int) $material['id'] === (int) $requestedMaterialId) {
            $selectedMaterial = $material;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($class['judul'] ?? 'Kelas') ?> | Sertifikasi Academy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../asset/css/theme-soft.css?v=20261103-green2" rel="stylesheet">
    <link href="../../asset/css/dashboard.css?v=20261103-green2" rel="stylesheet">
    <link href="../../asset/css/course.css?v=20261103-course-flow" rel="stylesheet">
</head>
<body class="soft-app">
<?php $aktif = 'mylearning'; include __DIR__ . '/../../component/navbar.php'; ?>

<main class="container py-4 py-lg-5">
    <div class="d-flex align-items-center justify-content-between gap-3 mb-4 flex-wrap">
        <div>
            <a href="ClassroomController.php?class_id=<?= (int) ($class['id'] ?? 0) ?>&amp;tab=overview" class="btn btn-sm btn-outline-secondary mb-2"><i class="bi bi-arrow-left"></i> Overview kelas</a>
            <h1 class="mb-1"><?= e($class['judul'] ?? 'Kelas') ?></h1>
            <p class="text-muted mb-0"><?= e($class['deskripsi'] ?? '') ?></p>
        </div>
        <div class="text-end">
            <div class="text-muted small">Progres kelas</div>
            <div class="fw-bold fs-5"><?= $classProgress ?>%</div>
            <div class="progress mt-2" role="progressbar" aria-label="Progres kelas" aria-valuenow="<?= $classProgress ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar bg-success" style="width: <?= $classProgress ?>%"></div>
            </div>
        </div>
    </div>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success" role="alert"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3 p-md-4">
            <ul class="nav nav-pills flex-wrap">
                <li class="nav-item"><a class="nav-link" href="ClassroomController.php?class_id=<?= (int) ($class['id'] ?? 0) ?>&tab=overview">Overview</a></li>
                <li class="nav-item"><a class="nav-link <?= $tab === 'stream' ? 'active' : '' ?>" href="ClassroomController.php?class_id=<?= (int) ($class['id'] ?? 0) ?>&tab=stream">Diskusi</a></li>
                <li class="nav-item"><a class="nav-link <?= $tab === 'materi' ? 'active' : '' ?>" href="ClassroomController.php?class_id=<?= (int) ($class['id'] ?? 0) ?>&tab=materi">Modul &amp; Materi</a></li>
                <li class="nav-item"><a class="nav-link <?= $tab === 'kuis' ? 'active' : '' ?>" href="ClassroomController.php?class_id=<?= (int) ($class['id'] ?? 0) ?>&tab=kuis">Kuis &amp; Tugas</a></li>
            </ul>
        </div>
    </div>

    <?php if ($tab === 'stream'): ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h3 class="h5 mb-3">Tanyakan kepada instruktur</h3>
                        <?php if (($class['status'] ?? '') === 'aktif'): ?>
                            <form method="POST" action="ClassroomController.php?class_id=<?= (int) ($class['id'] ?? 0) ?>&tab=stream">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                                <input type="hidden" name="aksi" value="ask_question">
                                <input type="hidden" name="tab" value="stream">
                                <label class="form-label w-100">Judul pertanyaan<input class="form-control mt-1" name="question_title" maxlength="180" required></label>
                                <label class="form-label w-100">Pertanyaan<textarea class="form-control mt-1" name="question_text" rows="3" required></textarea></label>
                                <button type="submit" class="btn btn-success">Kirim pertanyaan</button>
                            </form>
                        <?php else: ?>
                            <p class="text-secondary mb-0">Forum pertanyaan tidak menerima pertanyaan baru untuk kelas ini.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($forumThreads)): ?>
                    <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
                        <h3 class="h5 mb-3">Pertanyaan saya</h3>
                        <?php foreach ($forumThreads as $thread): ?>
                            <article class="border rounded p-3 mb-3 bg-light">
                                <div class="d-flex justify-content-between gap-2"><strong><?= e($thread['title']) ?></strong><span class="badge <?= $thread['status'] === 'answered' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $thread['status'] === 'answered' ? 'Sudah dijawab' : 'Menunggu jawaban' ?></span></div>
                                <p class="mb-2 mt-2"><?= nl2br(e($thread['question'])) ?></p>
                                <?php foreach ($thread['replies'] as $reply): ?>
                                    <div class="border-start border-success border-3 ps-3 mt-3"><strong><?= e($reply['author_name']) ?> · Instruktur</strong><p class="mb-0 mt-1"><?= nl2br(e($reply['message'])) ?></p>
                                        <?php if (!empty($reply['code_snippet'])): ?><pre class="mt-2 mb-0"><code><?= e($reply['code_snippet']) ?></code></pre><?php endif; ?>
                                        <?php if (!empty($reply['attachment_path'])): ?><a href="<?= e($reply['attachment_path']) ?>" target="_blank" rel="noopener"><?= e($reply['attachment_name']) ?></a><?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </article>
                        <?php endforeach; ?>
                    </div></div>
                <?php endif; ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="h5 mb-3">Pengumuman kelas</h3>
                        <?php if (empty($announcements)): ?>
                            <div class="text-muted border rounded p-4 bg-light">Belum ada pengumuman baru di kelas ini.</div>
                        <?php else: ?>
                            <?php foreach ($announcements as $announcement): ?>
                                <article class="border rounded p-3 mb-3 bg-light">
                                    <div class="d-flex justify-content-between gap-3 mb-2">
                                        <div><strong><?= e($announcement['penulis_nama'] ?? 'Instruktur') ?></strong></div>
                                        <small class="text-muted"><?= e(date('d M Y, H:i', strtotime($announcement['dibuat_pada']))) ?></small>
                                    </div>
                                    <p class="mb-0 whitespace-pre-wrap"><?= nl2br(e($announcement['isi'] ?? '')) ?></p>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h3 class="h5 mb-3">Ringkasan</h3>
                        <dl class="row mb-0">
                            <dt class="col-6">Status</dt>
                            <dd class="col-6 text-end"><?= e(ucfirst((string) ($class['status'] ?? 'draft'))) ?></dd>
                            <dt class="col-6">Progress saya</dt>
                            <dd class="col-6 text-end"><?= (int) ($class['progress'] ?? 0) ?>%</dd>
                            <dt class="col-6">Jumlah modul</dt>
                            <dd class="col-6 text-end"><?= count($modules) ?></dd>
                            <dt class="col-6">Jumlah kuis</dt>
                            <dd class="col-6 text-end"><?= count($quizzes) ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif (in_array($tab, ['materi', 'kuis'], true)): ?>
            <div class="row g-4">
                <aside class="col-lg-4">
                    <?php include __DIR__ . '/classroom-outline.php'; ?>
                </aside>

                <section class="col-lg-8">
                    <?php if ($tab === 'kuis'): ?>
                        <?php if ($selectedQuiz === null): ?>
                            <div class="course-content text-secondary">Belum ada kuis di kelas ini.</div>
                        <?php else: ?>
                            <article class="course-content">
                                <?php
                                $quiz = $selectedQuiz;
                                $quizResult = $resultsByQuiz[(int) $quiz['id']] ?? null;
                                $quizPassed = $quizResult !== null && (int) $quizResult['lulus'] === 1;
                                ?>
                                <div class="lesson-counter">Kuis kelas</div>
                                <h2><?= e($quiz['judul']) ?></h2>
                                <p>Jawab semua pertanyaan. Nilai minimum untuk lulus adalah <?= (int) $quiz['nilai_minimum'] ?>.</p>
                                <?php if ($quizResult): ?>
                                    <?php $quizScore = (int) $quizResult['nilai']; ?>
                                    <section class="quiz-result <?= $quizPassed ? 'passed' : 'failed' ?>" aria-live="polite">
                                        <div class="quiz-gauge" role="img" aria-label="Nilai <?= $quizScore ?> dari 100">
                                            <svg viewBox="0 0 200 112" aria-hidden="true">
                                                <path class="quiz-gauge-track" d="M 20 100 A 80 80 0 0 1 180 100" pathLength="100"></path>
                                                <path class="quiz-gauge-value" d="M 20 100 A 80 80 0 0 1 180 100" pathLength="100" stroke-dasharray="100" stroke-dashoffset="<?= 100 - $quizScore ?>"></path>
                                            </svg>
                                            <strong><?= $quizScore ?><span>/100</span></strong>
                                        </div>
                                        <h3><?= $quizPassed ? 'Selamat, kamu lulus!' : 'Kuis belum lulus' ?></h3>
                                        <p>Kamu menjawab benar <?= (int) $quizResult['jumlah_benar'] ?> dari <?= (int) $quizResult['jumlah_soal'] ?> soal.</p>
                                        <?php if ($quizPassed): ?>
                                            <p class="quiz-result-hint">Hasil kuis ini sudah tersimpan dalam progres kelas.</p>
                                        <?php else: ?>
                                            <p class="quiz-result-hint">Pelajari kembali materi lalu coba lagi. Nilai minimum kelulusan <?= (int) $quiz['nilai_minimum'] ?>.</p>
                                        <?php endif; ?>
                                    </section>
                                <?php endif; ?>

                                <?php if (!$quizResult || !$quizPassed): ?>
                                    <?php $quizQuestions = $quiz['soal'] ?? []; ?>
                                    <?php if ($quizQuestions === []): ?>
                                        <p class="text-secondary mb-0">Kuis ini belum memiliki soal.</p>
                                    <?php else: ?>
                                        <form method="POST" action="ClassroomController.php?class_id=<?= (int) $class['id'] ?>&amp;tab=kuis&amp;quiz_id=<?= (int) $quiz['id'] ?>" class="quiz-form" id="quizForm">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                                            <input type="hidden" name="aksi" value="submit_quiz">
                                            <input type="hidden" name="tab" value="kuis">
                                            <input type="hidden" name="quiz_id" value="<?= (int) $quiz['id'] ?>">
                                            <input type="hidden" name="return_quiz_id" value="<?= (int) $quiz['id'] ?>">
                                            <div class="quiz-question-nav" aria-label="Navigasi soal">
                                                <?php foreach ($quizQuestions as $index => $question): ?>
                                                    <button type="button" class="quiz-question-jump <?= $index === 0 ? 'is-current' : '' ?>" data-question-index="<?= (int) $index ?>" aria-label="Soal <?= (int) $index + 1 ?>" aria-current="<?= $index === 0 ? 'step' : 'false' ?>">
                                                        <?= (int) $index + 1 ?>
                                                    </button>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php foreach ($quizQuestions as $index => $question): ?>
                                                <fieldset class="quiz-question <?= $index === 0 ? 'is-active' : '' ?>" data-question-index="<?= (int) $index ?>">
                                                    <legend><span>Soal <?= (int) $index + 1 ?> dari <?= count($quizQuestions) ?></span><?= e($question['teks'] ?? '-') ?></legend>
                                                    <?php foreach ($question['opsi'] ?? [] as $option): ?>
                                                        <label class="quiz-option">
                                                            <input type="radio" name="jawaban[<?= (int) ($question['id'] ?? 0) ?>]" value="<?= (int) ($option['id'] ?? 0) ?>" <?= $index === 0 ? 'required' : '' ?>>
                                                            <span><?= e($option['teks'] ?? '') ?></span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </fieldset>
                                            <?php endforeach; ?>
                                            <p class="quiz-navigation-message" id="quizNavigationMessage" aria-live="polite"></p>
                                            <div class="quiz-navigation">
                                                <button class="btn btn-outline-secondary" type="button" id="previousQuestion" hidden><i class="bi bi-arrow-left"></i> Sebelumnya</button>
                                                <button class="btn btn-success" type="button" id="nextQuestion" hidden>Selanjutnya <i class="bi bi-arrow-right"></i></button>
                                                <button class="btn btn-success" type="submit" id="submitQuiz" <?= ($class['status'] ?? 'aktif') === 'ditutup' ? 'disabled' : '' ?>>
                                                    <?= ($class['status'] ?? 'aktif') === 'ditutup' ? 'Kelas read-only' : ($quizResult ? 'Coba lagi' : 'Kirim jawaban') ?>
                                                </button>
                                            </div>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="small text-secondary mb-0">Kuis ini sudah kamu luluskan.</p>
                                <?php endif; ?>
                            </article>
                        <?php endif; ?>
                        <?php if ($assignments !== []): ?>
                            <section class="course-content mt-4">
                                <h2 class="h5">Tugas kelas</h2>
                                <?php foreach ($assignments as $assignment): ?>
                                    <article class="border-bottom py-3">
                                        <h3 class="h6 mb-1"><?= e($assignment['title']) ?></h3>
                                        <p class="text-secondary mb-2"><?= nl2br(e($assignment['description'])) ?></p>
                                        <?php if (!empty($assignment['due_at'])): ?>
                                            <p class="small text-muted">Tenggat: <?= e(date('d M Y H:i', strtotime($assignment['due_at']))) ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($assignment['submission_path'])): ?>
                                            <p class="small mb-2">File terakhir: <a href="<?= e($assignment['submission_path']) ?>" target="_blank" rel="noopener"><?= e($assignment['submission_name']) ?></a></p>
                                        <?php endif; ?>
                                        <form method="POST" action="ClassroomController.php?class_id=<?= (int) $class['id'] ?>&amp;tab=kuis&amp;quiz_id=<?= (int) ($selectedQuiz['id'] ?? 0) ?>" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-end">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                                            <input type="hidden" name="aksi" value="submit_assignment">
                                            <input type="hidden" name="tab" value="kuis">
                                            <input type="hidden" name="assignment_id" value="<?= (int) $assignment['id'] ?>">
                                            <input type="hidden" name="return_quiz_id" value="<?= (int) ($selectedQuiz['id'] ?? 0) ?>">
                                            <label class="form-label mb-0"><span class="visually-hidden">File tugas</span><input type="file" class="form-control form-control-sm" name="assignment_file" accept=".pdf,.docx,.pptx,.zip,.txt,application/pdf,application/zip,text/plain" required></label>
                                            <button type="submit" class="btn btn-success btn-sm">Kumpulkan file</button>
                                        </form>
                                    </article>
                                <?php endforeach; ?>
                            </section>
                        <?php endif; ?>
                    <?php else: ?>
                    <article class="course-content">
                        <?php if ($selectedModule === null): ?>
                            <p class="text-secondary mb-0">Pilih modul untuk mulai belajar.</p>
                        <?php elseif (empty($selectedModule['is_unlocked'])): ?>
                            <div class="lesson-counter">Modul privat</div>
                            <h2><?= e($selectedModule['judul']) ?></h2>
                            <?php if (!empty($selectedModule['deskripsi'])): ?>
                                <p class="text-secondary"><?= e($selectedModule['deskripsi']) ?></p>
                            <?php endif; ?>
                            <p class="text-secondary">Masukkan kode akses dari instruktur untuk membuka materi di modul ini.</p>
                            <form method="POST" action="ClassroomController.php?class_id=<?= (int) $class['id'] ?>&amp;tab=materi" class="row g-2">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                                <input type="hidden" name="aksi" value="unlock_module">
                                <input type="hidden" name="tab" value="materi">
                                <input type="hidden" name="module_id" value="<?= (int) $selectedModule['id'] ?>">
                                <div class="col-sm">
                                    <label class="visually-hidden" for="module-access-key">Kode akses modul</label>
                                    <input class="form-control" id="module-access-key" type="text" name="module_enroll_key" maxlength="50" autocomplete="off" placeholder="Kode akses modul" required>
                                </div>
                                <div class="col-sm-auto">
                                    <button type="submit" class="btn btn-success" <?= ($class['status'] ?? '') === 'ditutup' ? 'disabled' : '' ?>>Buka modul</button>
                                </div>
                            </form>
                        <?php elseif ($selectedMaterial === null): ?>
                            <div class="lesson-counter">Overview modul <?= (int) $selectedModule['urutan'] ?> dari <?= count($modules) ?></div>
                            <h2><?= e($selectedModule['judul']) ?></h2>
                            <?php if (!empty($selectedModule['deskripsi'])): ?>
                                <p class="lesson-body"><?= nl2br(e($selectedModule['deskripsi'])) ?></p>
                            <?php endif; ?>
                            <?php $firstMaterial = $selectedModule['materi'][0] ?? null; ?>
                            <?php if ($firstMaterial !== null): ?>
                                <div class="lesson-navigation">
                                    <a class="btn btn-success px-4" href="ClassroomController.php?class_id=<?= (int) $class['id'] ?>&amp;tab=materi&amp;module_id=<?= (int) $selectedModule['id'] ?>&amp;material_id=<?= (int) $firstMaterial['id'] ?>">
                                        Mulai modul <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                    </a>
                                </div>
                            <?php else: ?>
                                <p class="text-secondary mb-0">Belum ada materi di modul ini.</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php $selectedMaterialId = (int) $selectedMaterial['id']; ?>
                            <div class="lesson-counter">Modul <?= (int) $selectedModule['urutan'] ?> · <?= e(ucfirst((string) ($selectedMaterial['tipe'] ?? 'teks'))) ?></div>
                            <h2><?= e($selectedMaterial['judul']) ?></h2>
                            <div class="lesson-body">
                                <?php if (in_array(($selectedMaterial['tipe'] ?? ''), ['video', 'dokumen'], true) && preg_match('~\A\.\./\.\./uploads/class-materials/[A-Za-z0-9._-]+\z~', (string) ($selectedMaterial['isi'] ?? ''))): ?>
                                    <?php if (($selectedMaterial['tipe'] ?? '') === 'video'): ?>
                                        <video class="w-100 rounded mb-3" controls preload="metadata">
                                            <source src="<?= e($selectedMaterial['isi']) ?>">
                                        </video>
                                    <?php else: ?>
                                        <a class="btn btn-outline-success mb-3" href="<?= e($selectedMaterial['isi']) ?>" target="_blank" rel="noopener">Buka / unduh materi</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?= nl2br(e($selectedMaterial['isi'] ?? '')) ?>
                                <?php endif; ?>
                            </div>
                            <?php $isOpened = isset($openedMap[$selectedMaterialId]); ?>
                            <div class="lesson-navigation">
                                <form method="POST" action="ClassroomController.php?class_id=<?= (int) $class['id'] ?>&amp;tab=materi">
                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="aksi" value="mark_material">
                                    <input type="hidden" name="tab" value="materi">
                                    <input type="hidden" name="module_id" value="<?= (int) $selectedModule['id'] ?>">
                                    <input type="hidden" name="material_id" value="<?= $selectedMaterialId ?>">
                                    <input type="hidden" name="return_material_id" value="<?= $selectedMaterialId ?>">
                                    <button type="submit" class="btn <?= $isOpened ? 'btn-outline-success' : 'btn-success' ?>" <?= ($isOpened || ($class['status'] ?? '') === 'ditutup') ? 'disabled' : '' ?>>
                                        <?= $isOpened ? 'Materi sudah dibuka' : (($class['status'] ?? '') === 'ditutup' ? 'Kelas read-only' : 'Tandai sudah dibuka') ?>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </article>
                    <?php endif; ?>
                </section>
            </div>
    <?php endif; ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../asset/js/page-actions.js"></script>
<?php if ($tab === 'kuis' && $selectedQuiz !== null): ?>
<script src="../../asset/js/course-quiz.js?v=1"></script>
<?php endif; ?>
</body>
</html>
