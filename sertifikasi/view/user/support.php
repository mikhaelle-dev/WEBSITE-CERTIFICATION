<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tab === 'feedback' ? 'Feedback' : 'Support' ?> | Sertifikasi Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../asset/css/dashboard.css" rel="stylesheet">
    <link href="../../asset/css/brutal.css" rel="stylesheet">
    <link href="../../asset/css/support.css" rel="stylesheet">
</head>
<body class="brutal-app">
<?php $aktif = ''; include __DIR__ . '/../../component/navbar.php'; ?>

<main class="container support-page py-4 py-lg-5">
    <header class="support-heading mb-4">
        <span class="support-eyebrow">SERTIFIKASI ACADEMY</span>
        <h1 class="page-title mb-2"><?= $tab === 'feedback' ? 'Feedback & Masukan' : 'Support & Bantuan' ?></h1>
        <p class="text-muted mb-0">
            <?= $tab === 'feedback'
                ? 'Pendapatmu membantu kami membuat pengalaman belajar yang lebih baik.'
                : 'Temukan jawaban, panduan, atau cara menghubungi tim bantuan.' ?>
        </p>
    </header>

    <nav class="support-tabs mb-4" aria-label="Bantuan dan masukan">
        <a class="<?= $tab === 'support' ? 'active' : '' ?>" href="SupportController.php?tab=support" <?= $tab === 'support' ? 'aria-current="page"' : '' ?>>
            <i class="bi bi-life-preserver"></i> Support
        </a>
        <a class="<?= $tab === 'feedback' ? 'active' : '' ?>" href="SupportController.php?tab=feedback" <?= $tab === 'feedback' ? 'aria-current="page"' : '' ?>>
            <i class="bi bi-chat-square-heart"></i> Feedback
        </a>
    </nav>

    <?php if ($tab === 'support'): ?>
        <section class="support-grid">
            <article class="support-card">
                <div class="support-icon"><i class="bi bi-question-circle"></i></div>
                <h2>FAQ</h2>
                <p class="text-muted">Jawaban untuk pertanyaan yang sering diajukan.</p>
                <div class="accordion accordion-flush" id="supportFaq">
                    <div class="accordion-item">
                        <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqPassword" aria-expanded="false" aria-controls="faqPassword">Bagaimana jika saya lupa password?</button></h3>
                        <div id="faqPassword" class="accordion-collapse collapse" data-bs-parent="#supportFaq"><div class="accordion-body">Hubungi admin untuk meminta reset password akun melalui halaman <a href="../../view/forgot-password.php">Lupa password</a>.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCourse" aria-expanded="false" aria-controls="faqCourse">Bagaimana cara mulai belajar?</button></h3>
                        <div id="faqCourse" class="accordion-collapse collapse" data-bs-parent="#supportFaq"><div class="accordion-body">Pilih kursus dari halaman Home, buka detailnya, lalu mulai belajar. Progres kursus dapat dilanjutkan dari dashboard.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCertificate" aria-expanded="false" aria-controls="faqCertificate">Di mana saya bisa melihat sertifikat?</button></h3>
                        <div id="faqCertificate" class="accordion-collapse collapse" data-bs-parent="#supportFaq"><div class="accordion-body">Sertifikat tersedia pada menu Profil &gt; Sertifikat &amp; Lencana setelah kamu menyelesaikan kursus dan memenuhi syarat kelulusan.</div></div>
                    </div>
                </div>
            </article>

            <article class="support-card">
                <div class="support-icon"><i class="bi bi-journal-text"></i></div>
                <h2>Help Center</h2>
                <p class="text-muted">Panduan singkat menggunakan aplikasi.</p>
                <ol class="help-steps">
                    <li><strong>Pilih kursus</strong><span>Jelajahi kursus yang tersedia dari halaman Home.</span></li>
                    <li><strong>Ikuti materi</strong><span>Buka kursus dan selesaikan materi serta asesmen.</span></li>
                    <li><strong>Pantau progres</strong><span>Lihat progres belajar dan pencapaian di dashboard atau profil.</span></li>
                </ol>
                <a class="support-inline-link" href="<?= e($dashboardPath) ?>"><i class="bi bi-arrow-right"></i> Kembali ke dashboard</a>
            </article>

            <article class="support-card support-contact-card">
                <div class="support-icon"><i class="bi bi-headset"></i></div>
                <h2>Hubungi tim bantuan</h2>
                <p class="text-muted">Laporkan kendala atau tanyakan hal yang belum terjawab. Live chat belum tersedia; kamu dapat menghubungi admin melalui email.</p>
                <?php if ($adminEmail !== ''): ?>
                    <a class="btn btn-enroll" href="mailto:<?= e($adminEmail) ?>?subject=Permintaan%20bantuan%20Sertifikasi%20Academy">
                        <i class="bi bi-envelope me-1"></i>Contact Us / Laporkan masalah
                    </a>
                <?php else: ?>
                    <div class="alert alert-info mb-0" role="status">Kontak admin belum dikonfigurasi. Silakan hubungi admin melalui kanal komunikasi yang biasa digunakan.</div>
                <?php endif; ?>
            </article>
        </section>
    <?php else: ?>
        <section class="support-card feedback-card">
            <div class="support-icon"><i class="bi bi-chat-square-heart"></i></div>
            <h2>Kami ingin mendengar pendapatmu</h2>
            <p class="text-muted">Beri penilaian, sampaikan saran, laporkan bug, atau usulkan fitur. Masukanmu akan disimpan agar dapat ditinjau oleh pengelola aplikasi.</p>

            <?php if (isset($notice) && is_array($notice)): ?>
                <div class="alert alert-<?= e($notice['type']) ?>" role="<?= $notice['type'] === 'danger' ? 'alert' : 'status' ?>"><?= e($notice['message']) ?></div>
            <?php endif; ?>
            <form id="feedbackForm" method="POST" action="SupportController.php?tab=feedback">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <fieldset class="mb-4">
                    <legend class="form-label">Seberapa puas kamu menggunakan aplikasi?</legend>
                    <div class="rating-options">
                        <?php for ($rating = 1; $rating <= 5; $rating++): ?>
                            <label>
                                <input type="radio" name="rating" value="<?= $rating ?>" required>
                                <span><?= $rating ?> <i class="bi bi-star-fill"></i></span>
                            </label>
                        <?php endfor; ?>
                    </div>
                </fieldset>
                <div class="mb-3">
                    <label class="form-label" for="feedbackType">Jenis masukan</label>
                    <select class="form-select" id="feedbackType" name="type" required>
                        <option value="Saran dan kritik">Saran dan kritik</option>
                        <option value="Laporan bug">Report a Bug</option>
                        <option value="Permintaan fitur">Feature Request</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="feedbackMessage">Ceritakan masukanmu</label>
                    <textarea class="form-control" id="feedbackMessage" name="message" rows="5" maxlength="3000" required placeholder="Tuliskan detail masukan atau kendala yang kamu alami..."></textarea>
                    <div class="form-text">Maksimal 3000 karakter. Jangan sertakan password atau informasi rahasia.</div>
                </div>
                <button class="btn btn-enroll" type="submit"><i class="bi bi-send me-1"></i>Kirim masukan</button>
            </form>
        </section>
    <?php endif; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../asset/js/page-actions.js"></script>
</body>
</html>
