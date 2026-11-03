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
</head>
<body class="soft-app">
<?php $aktif = 'mylearning'; include __DIR__ . '/../../component/navbar.php'; ?>

<main class="container py-4 py-lg-5">
    <div class="d-flex align-items-center justify-content-between gap-3 mb-4 flex-wrap">
        <div>
            <a href="DashboardController.php" class="btn btn-sm btn-outline-secondary mb-2"><i class="bi bi-arrow-left"></i> Kembali ke dashboard</a>
            <h1 class="mb-1"><?= e($class['judul'] ?? 'Kelas') ?></h1>
            <p class="text-muted mb-0"><?= e($class['deskripsi'] ?? '') ?></p>
        </div>
        <div class="text-end">
            <div class="text-muted small">Kode enroll</div>
            <div class="fw-bold fs-5"><?= e($class['enroll_key'] ?? '-') ?></div>
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
                <li class="nav-item"><a class="nav-link <?= $tab === 'stream' ? 'active' : '' ?>" href="ClassroomController.php?class_id=<?= (int) ($class['id'] ?? 0) ?>&tab=stream">Stream</a></li>
                <li class="nav-item"><a class="nav-link <?= $tab === 'materi' ? 'active' : '' ?>" href="ClassroomController.php?class_id=<?= (int) ($class['id'] ?? 0) ?>&tab=materi">Materi</a></li>
                <li class="nav-item"><a class="nav-link <?= $tab === 'kuis' ? 'active' : '' ?>" href="ClassroomController.php?class_id=<?= (int) ($class['id'] ?? 0) ?>&tab=kuis">Kuis</a></li>
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
    <?php elseif ($tab === 'materi'): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?php if (empty($modules)): ?>
                    <div class="text-muted border rounded p-4 bg-light">Belum ada materi di kelas ini.</div>
                <?php else: ?>
                    <?php foreach ($modules as $module): ?>
                        <div class="border rounded p-3 mb-4 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
                                <div>
                                    <div class="text-uppercase text-muted small fw-semibold">Modul</div>
                                    <h3 class="h5 mb-0"><?= e($module['judul'] ?? '-') ?></h3>
                                </div>
                                <span class="badge bg-primary-subtle text-primary"><?= count($module['materi'] ?? []) ?> materi</span>
                            </div>

                            <?php $materials = $module['materi'] ?? []; ?>
                            <?php if (empty($materials)): ?>
                                <div class="text-muted">Belum ada materi di modul ini.</div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($materials as $material): ?>
                                        <?php $materialId = (int) ($material['id'] ?? 0); $opened = isset($openedMap[$materialId]); ?>
                                        <div class="list-group-item px-0 py-3">
                                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                                <div>
                                                    <div class="fw-semibold"><?= e($material['judul'] ?? '-') ?></div>
                                                    <div class="small text-muted"><?= e(ucfirst((string) ($material['tipe'] ?? 'teks'))) ?></div>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <?php if ($opened): ?>
                                                        <span class="badge text-bg-success">Dibuka</span>
                                                    <?php else: ?>
                                                        <span class="badge text-bg-light text-muted">Belum dibuka</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <?php if (in_array(($material['tipe'] ?? ''), ['video', 'dokumen'], true) && preg_match('~\A\.\./\.\./uploads/class-materials/[A-Za-z0-9._-]+\z~', (string) ($material['isi'] ?? ''))): ?>
                                                    <?php if (($material['tipe'] ?? '') === 'video'): ?>
                                                        <video class="w-100 rounded mb-3" controls preload="metadata">
                                                            <source src="<?= e($material['isi']) ?>">
                                                        </video>
                                                    <?php else: ?>
                                                        <a class="btn btn-outline-success btn-sm mb-3" href="<?= e($material['isi']) ?>" target="_blank" rel="noopener">Buka / unduh materi</a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <p class="mb-3 text-secondary"><?= nl2br(e($material['isi'] ?? '')) ?></p>
                                                <?php endif; ?>
                                                <form method="POST" action="ClassroomController.php?class_id=<?= (int) ($class['id'] ?? 0) ?>&tab=materi">
                                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                                                    <input type="hidden" name="aksi" value="mark_material">
                                                    <input type="hidden" name="material_id" value="<?= $materialId ?>">
                                                    <button type="submit" class="btn btn-sm btn-primary" <?= ($opened || (($class['status'] ?? 'aktif') === 'ditutup')) ? 'disabled' : '' ?>><?= $opened ? 'Sudah dibuka' : (($class['status'] ?? 'aktif') === 'ditutup' ? 'Read-only' : 'Tandai sudah dibuka') ?></button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Tugas kelas</h2>
                <?php if (empty($assignments)): ?>
                    <div class="text-muted border rounded p-3 bg-light mb-4">Belum ada tugas unggah file.</div>
                <?php else: ?>
                    <?php foreach ($assignments as $assignment): ?>
                        <article class="border rounded p-3 mb-3 bg-light">
                            <h3 class="h6 mb-1"><?= e($assignment['title']) ?></h3>
                            <p class="text-secondary mb-2"><?= nl2br(e($assignment['description'])) ?></p>
                            <?php if (!empty($assignment['due_at'])): ?><p class="small text-muted">Tenggat: <?= e(date('d M Y H:i', strtotime($assignment['due_at']))) ?></p><?php endif; ?>
                            <?php if (!empty($assignment['submission_path'])): ?>
                                <p class="small mb-2">File terakhir: <a href="<?= e($assignment['submission_path']) ?>" target="_blank" rel="noopener"><?= e($assignment['submission_name']) ?></a></p>
                            <?php endif; ?>
                            <form method="POST" action="ClassroomController.php?class_id=<?= (int) ($class['id'] ?? 0) ?>&tab=kuis" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-end">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                                <input type="hidden" name="aksi" value="submit_assignment">
                                <input type="hidden" name="tab" value="kuis">
                                <input type="hidden" name="assignment_id" value="<?= (int) $assignment['id'] ?>">
                                <label class="form-label mb-0"><span class="visually-hidden">File tugas</span><input type="file" class="form-control form-control-sm" name="assignment_file" accept=".pdf,.docx,.pptx,.zip,.txt,application/pdf,application/zip,text/plain" required></label>
                                <button type="submit" class="btn btn-success btn-sm">Kumpulkan file</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (empty($quizzes)): ?>
                    <div class="text-muted border rounded p-4 bg-light">Belum ada kuis di kelas ini.</div>
                <?php else: ?>
                    <?php foreach ($quizzes as $quiz): ?>
                        <?php $quizResult = $resultsByQuiz[(int) ($quiz['id'] ?? 0)] ?? null; ?>
                        <div class="border rounded p-3 mb-4 bg-light">
                            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
                                <div>
                                    <div class="text-uppercase text-muted small fw-semibold">Kuis</div>
                                    <h3 class="h5 mb-0"><?= e($quiz['judul'] ?? '-') ?></h3>
                                </div>
                                <?php if ($quizResult): ?>
                                    <span class="badge text-bg-success">Nilai: <?= (int) $quizResult['nilai'] ?>%</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Belum dikerjakan</span>
                                <?php endif; ?>
                            </div>

                            <?php if ($quizResult): ?>
                                <div class="alert alert-light border mb-3">
                                    <strong>Hasil terakhir:</strong> <?= (int) $quizResult['nilai'] ?>% • <?= (int) $quizResult['jumlah_benar'] ?>/<?= (int) $quizResult['jumlah_soal'] ?> benar.
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="ClassroomController.php?class_id=<?= (int) ($class['id'] ?? 0) ?>&tab=kuis">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                                <input type="hidden" name="aksi" value="submit_quiz">
                                <input type="hidden" name="quiz_id" value="<?= (int) ($quiz['id'] ?? 0) ?>">

                                <?php foreach ($quiz['soal'] ?? [] as $index => $question): ?>
                                    <div class="border rounded p-3 mb-3">
                                        <div class="fw-semibold mb-2"><?= (int) $index + 1 ?>. <?= e($question['teks'] ?? '-') ?></div>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($question['opsi'] ?? [] as $option): ?>
                                                <label class="list-group-item list-group-item-action">
                                                    <input type="radio" name="jawaban[<?= (int) ($question['id'] ?? 0) ?>]" value="<?= (int) ($option['id'] ?? 0) ?>" required>
                                                    <span class="ms-2"><?= e($option['teks'] ?? '') ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <button type="submit" class="btn btn-primary" <?= (($class['status'] ?? 'aktif') === 'ditutup') ? 'disabled' : '' ?>><?= (($class['status'] ?? 'aktif') === 'ditutup') ? 'Kelas read-only' : 'Kirim jawaban' ?></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
