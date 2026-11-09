<nav class="course-outline" aria-label="Daftar modul, materi, dan kuis kelas">
    <h2 class="h5">Modul, materi &amp; kuis</h2>
    <?php if ($modules === []): ?>
        <p class="small text-secondary">Belum ada modul di kelas ini.</p>
    <?php endif; ?>
    <?php foreach ($modules as $module): ?>
        <?php
        $moduleId = (int) $module['id'];
        $isSelectedModule = $selectedModule !== null && $moduleId === (int) $selectedModule['id'];
        $materials = $module['materi'] ?? [];
        $moduleMaterialTotal = (int) ($module['total_materi'] ?? count($materials));
        $openedInModule = 0;
        foreach ($materials as $item) {
            if (isset($openedMap[(int) $item['id']])) {
                $openedInModule++;
            }
        }
        $moduleProgress = $moduleMaterialTotal > 0
            ? (int) floor($openedInModule / $moduleMaterialTotal * 100)
            : 0;
        ?>
        <details class="module-outline" <?= $tab === 'materi' && $isSelectedModule ? 'open' : '' ?>>
            <summary class="module-outline-summary">
                <span class="module-outline-title">
                    <span class="outline-number <?= $moduleProgress === 100 && $moduleMaterialTotal > 0 ? 'is-complete' : '' ?>">
                        <?= $moduleProgress === 100 && $moduleMaterialTotal > 0
                            ? '<i class="bi bi-check-lg" aria-label="Modul selesai"></i>'
                            : (int) $module['urutan'] ?>
                    </span>
                    <span><?= e($module['judul']) ?></span>
                </span>
                <span class="module-progress-value">
                    <?= !empty($module['is_private']) ? 'Privat' : 'Publik' ?> · <?= $moduleProgress ?>%
                </span>
                <span class="module-progress-track" role="progressbar"
                      aria-label="Progres modul <?= e($module['judul']) ?>"
                      aria-valuenow="<?= $moduleProgress ?>" aria-valuemin="0" aria-valuemax="100">
                    <span style="width: <?= $moduleProgress ?>%"></span>
                </span>
            </summary>
            <div class="sublesson-list">
                <?php if (!empty($module['is_unlocked'])): ?>
                    <a class="module-overview-link <?= $tab === 'materi' && $isSelectedModule && $selectedMaterial === null ? 'active' : '' ?>"
                       href="ClassroomController.php?class_id=<?= (int) $class['id'] ?>&amp;tab=materi&amp;module_id=<?= $moduleId ?>">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        <span>Ringkasan modul</span>
                    </a>
                    <?php foreach ($materials as $material): ?>
                        <?php
                        $materialId = (int) $material['id'];
                        $isOpened = isset($openedMap[$materialId]);
                        $isActiveMaterial = $tab === 'materi' && $isSelectedModule
                            && $selectedMaterial !== null
                            && $materialId === (int) $selectedMaterial['id'];
                        ?>
                        <a class="sublesson-link <?= $isActiveMaterial ? 'active' : '' ?> <?= $isOpened ? 'is-complete' : '' ?>"
                           <?= $isActiveMaterial ? 'aria-current="page"' : '' ?>
                           href="ClassroomController.php?class_id=<?= (int) $class['id'] ?>&amp;tab=materi&amp;module_id=<?= $moduleId ?>&amp;material_id=<?= $materialId ?>">
                            <span class="sublesson-status">
                                <?php if ($isOpened): ?>
                                    <i class="bi bi-check-lg" aria-label="Sudah diselesaikan"></i>
                                <?php else: ?>
                                    <?= (int) $module['urutan'] ?>.<?= (int) $material['urutan'] ?>
                                <?php endif; ?>
                            </span>
                            <span><?= e($material['judul']) ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <a class="module-overview-link <?= $tab === 'materi' && $isSelectedModule ? 'active' : '' ?>"
                       href="ClassroomController.php?class_id=<?= (int) $class['id'] ?>&amp;tab=materi&amp;module_id=<?= $moduleId ?>">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                        <span>Buka modul dengan kode akses</span>
                    </a>
                <?php endif; ?>
            </div>
        </details>
    <?php endforeach; ?>

    <?php foreach ($quizzes as $quiz): ?>
        <?php
        $quizId = (int) $quiz['id'];
        $quizResult = $resultsByQuiz[$quizId] ?? null;
        $quizPassed = $quizResult !== null && (int) $quizResult['lulus'] === 1;
        $quizSelected = $tab === 'kuis' && $selectedQuiz !== null
            && $quizId === (int) $selectedQuiz['id'];
        ?>
        <a class="outline-link class-quiz-outline-link <?= $quizSelected ? 'active' : '' ?> <?= $quizPassed ? 'is-complete' : '' ?> <?= $quizResult && !$quizPassed ? 'needs-retry' : '' ?>"
           <?= $quizSelected ? 'aria-current="page"' : '' ?>
           href="ClassroomController.php?class_id=<?= (int) $class['id'] ?>&amp;tab=kuis&amp;quiz_id=<?= $quizId ?>">
            <span class="outline-number">
                <i class="bi <?= $quizPassed ? 'bi-check-lg' : 'bi-patch-question' ?>" aria-hidden="true"></i>
            </span>
            <span class="outline-title"><?= e($quiz['judul']) ?></span>
            <?php if ($quizResult): ?>
                <span class="quiz-score-badge <?= $quizPassed ? 'passed' : 'needs-retry' ?>"><?= (int) $quizResult['nilai'] ?></span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</nav>
