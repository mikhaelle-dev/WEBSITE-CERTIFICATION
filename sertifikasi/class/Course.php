<?php
require_once __DIR__ . '/Database.php';

class Course extends Database
{
    public function getBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM kursus WHERE slug = ?');
        $stmt->execute([$slug]);
        $course = $stmt->fetch();

        return $course ?: null;
    }

    public function getLessons(int $courseId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, urutan, judul, isi FROM materi_kursus WHERE kursus_id = ? ORDER BY urutan'
        );
        $stmt->execute([$courseId]);
        $lessons = $stmt->fetchAll();

        $stmt = $this->db->prepare(
            'SELECT id, materi_id, urutan, judul, isi
             FROM submateri_kursus
             WHERE materi_id IN (SELECT id FROM materi_kursus WHERE kursus_id = ?)
             ORDER BY materi_id, urutan'
        );
        $stmt->execute([$courseId]);
        $sublessonsByLesson = [];
        foreach ($stmt->fetchAll() as $sublesson) {
            $sublessonsByLesson[(int) $sublesson['materi_id']][] = $sublesson;
        }

        foreach ($lessons as &$lesson) {
            $lesson['submateri'] = $sublessonsByLesson[(int) $lesson['id']] ?? [];
        }
        unset($lesson);

        return $lessons;
    }

    public function getEnrollment(int $userId, int $courseId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, mulai_pada, selesai_pada
             FROM pendaftaran_kursus
             WHERE user_id = ? AND kursus_id = ?'
        );
        $stmt->execute([$userId, $courseId]);
        $enrollment = $stmt->fetch();

        return $enrollment ?: null;
    }

    public function getActiveEnrollment(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT kursus.id AS course_id, kursus.slug, kursus.judul, kursus.kategori,
                    kursus.level, kursus.durasi, kursus.deskripsi, pendaftaran.mulai_pada
             FROM pendaftaran_kursus pendaftaran
             JOIN kursus ON kursus.id = pendaftaran.kursus_id
             WHERE pendaftaran.user_id = ? AND pendaftaran.selesai_pada IS NULL
             ORDER BY pendaftaran.mulai_pada
             LIMIT 1'
        );
        $stmt->execute([$userId]);
        $enrollment = $stmt->fetch();

        return $enrollment ?: null;
    }

    public function getCompletedCourseSlugs(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT kursus.slug
             FROM pendaftaran_kursus pendaftaran
             JOIN kursus ON kursus.id = pendaftaran.kursus_id
             WHERE pendaftaran.user_id = ? AND pendaftaran.selesai_pada IS NOT NULL'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getCompletedCourses(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT kursus.judul, kursus.slug, kursus.kategori, kursus.durasi,
                    \'Self-paced\' AS tipe, \'Selesai\' AS status,
                    pendaftaran.mulai_pada, pendaftaran.selesai_pada
             FROM pendaftaran_kursus pendaftaran
             JOIN kursus ON kursus.id = pendaftaran.kursus_id
             WHERE pendaftaran.user_id = ? AND pendaftaran.selesai_pada IS NOT NULL
             ORDER BY pendaftaran.selesai_pada DESC'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function getAchievements(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT 'Sertifikat' AS jenis, kursus.judul, kursus.judul AS kursus_judul,
                    sertifikat.diterbitkan_pada AS tanggal, sertifikat.id AS item_id,
                    'bi-award' AS ikon, sertifikat.id AS sertifikat_id,
                    sertifikat.nomor_sertifikat, NULL AS deskripsi
             FROM sertifikat
             JOIN kursus ON kursus.id = sertifikat.kursus_id
             WHERE sertifikat.user_id = ?
             UNION ALL
             SELECT 'Lencana' AS jenis, badge.judul, kursus.judul AS kursus_judul,
                    lencana.diperoleh_pada AS tanggal, lencana.id AS item_id,
                    badge.ikon, NULL AS sertifikat_id, NULL AS nomor_sertifikat,
                    badge.deskripsi
             FROM lencana_pengguna lencana
             JOIN lencana_kursus badge ON badge.id = lencana.lencana_id
             JOIN kursus ON kursus.id = badge.kursus_id
             WHERE lencana.user_id = ?
             ORDER BY tanggal DESC, item_id DESC"
        );
        $stmt->execute([$userId, $userId]);

        return $stmt->fetchAll();
    }

    public function getCertificateForUser(int $certificateId, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT sertifikat.id, sertifikat.nomor_sertifikat, sertifikat.nama_peserta,
                    sertifikat.diterbitkan_pada, kursus.judul AS judul_kursus,
                    kursus.kategori, kursus.durasi
             FROM sertifikat
             JOIN kursus ON kursus.id = sertifikat.kursus_id
             WHERE sertifikat.id = ? AND sertifikat.user_id = ?'
        );
        $stmt->execute([$certificateId, $userId]);
        $certificate = $stmt->fetch();

        return $certificate ?: null;
    }

    public function getCourseBadges(int $courseId, int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT badge.id, badge.kode, badge.judul, badge.deskripsi, badge.ikon,
                    materi.urutan AS materi_urutan,
                    award.diperoleh_pada
             FROM lencana_kursus badge
             JOIN materi_kursus materi ON materi.id = badge.materi_id
             LEFT JOIN lencana_pengguna award
                ON award.lencana_id = badge.id AND award.user_id = ?
             WHERE badge.kursus_id = ?
             ORDER BY materi.urutan, badge.id'
        );
        $stmt->execute([$userId, $courseId]);

        return $stmt->fetchAll();
    }

    public function getCourseCertificate(int $courseId, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, nomor_sertifikat, nama_peserta, diterbitkan_pada
             FROM sertifikat
             WHERE kursus_id = ? AND user_id = ?'
        );
        $stmt->execute([$courseId, $userId]);
        $certificate = $stmt->fetch();

        return $certificate ?: null;
    }

    public function getResumeSublessonId(int $userId, int $courseId): ?int
    {
        $stmt = $this->db->prepare(
            'SELECT submateri.id
             FROM progres_submateri progres
             JOIN submateri_kursus submateri ON submateri.id = progres.submateri_id
             JOIN materi_kursus materi ON materi.id = submateri.materi_id
             WHERE progres.user_id = ? AND materi.kursus_id = ?
             ORDER BY progres.pertama_dibuka DESC, materi.urutan DESC, submateri.urutan DESC
             LIMIT 1'
        );
        $stmt->execute([$userId, $courseId]);
        $sublessonId = $stmt->fetchColumn();

        return $sublessonId === false ? null : (int) $sublessonId;
    }

    public function enroll(int $userId, int $courseId): bool
    {
        $this->db->beginTransaction();

        try {
            $lockUser = $this->db->prepare('SELECT id FROM user WHERE id = ? FOR UPDATE');
            $lockUser->execute([$userId]);

            $existing = $this->getEnrollment($userId, $courseId);
            if ($existing !== null) {
                $this->db->commit();
                return true;
            }

            $activeEnrollment = $this->db->prepare(
                'SELECT id FROM pendaftaran_kursus
                 WHERE user_id = ? AND selesai_pada IS NULL
                 LIMIT 1'
            );
            $activeEnrollment->execute([$userId]);
            if ($activeEnrollment->fetch()) {
                $this->db->commit();
                return false;
            }

            $insert = $this->db->prepare(
                'INSERT INTO pendaftaran_kursus (user_id, kursus_id) VALUES (?, ?)'
            );
            $insert->execute([$userId, $courseId]);
            $this->db->commit();

            return true;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    public function markSublessonOpened(int $userId, int $sublessonId): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO progres_submateri (user_id, submateri_id)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE pertama_dibuka = pertama_dibuka'
        );
        $stmt->execute([$userId, $sublessonId]);

        $stmt = $this->db->prepare(
            'INSERT INTO progres_materi (user_id, materi_id)
             SELECT ?, materi.id
             FROM materi_kursus materi
             JOIN submateri_kursus submateri ON submateri.materi_id = materi.id
             LEFT JOIN progres_submateri progres
                ON progres.submateri_id = submateri.id AND progres.user_id = ?
             WHERE materi.id = (SELECT materi_id FROM submateri_kursus WHERE id = ?)
             GROUP BY materi.id
             HAVING COUNT(submateri.id) = COUNT(progres.submateri_id)
             ON DUPLICATE KEY UPDATE pertama_dibuka = pertama_dibuka'
        );
        $stmt->execute([$userId, $userId, $sublessonId]);

        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO lencana_pengguna (user_id, lencana_id)
             SELECT ?, badge.id
             FROM lencana_kursus badge
             JOIN progres_materi progres
                ON progres.materi_id = badge.materi_id AND progres.user_id = ?
             JOIN submateri_kursus submateri ON submateri.materi_id = badge.materi_id
             WHERE submateri.id = ?'
        );
        $stmt->execute([$userId, $userId, $sublessonId]);
    }

    public function getOpenedSublessonIds(int $userId, int $courseId): array
    {
        $stmt = $this->db->prepare(
            'SELECT progres.submateri_id
             FROM progres_submateri progres
             JOIN submateri_kursus submateri ON submateri.id = progres.submateri_id
             JOIN materi_kursus materi ON materi.id = submateri.materi_id
             WHERE progres.user_id = ? AND materi.kursus_id = ?'
        );
        $stmt->execute([$userId, $courseId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function getProgress(int $userId, int $courseId): array
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) AS total_materi,
                    COUNT(progres.materi_id) AS materi_selesai
             FROM materi_kursus materi
             LEFT JOIN progres_materi progres
                ON progres.materi_id = materi.id AND progres.user_id = ?
             WHERE materi.kursus_id = ?'
        );
        $stmt->execute([$userId, $courseId]);
        $progress = $stmt->fetch();

        $stmt = $this->db->prepare(
            'SELECT kuis.id,
                    EXISTS(
                        SELECT 1 FROM hasil_kuis hasil
                        WHERE hasil.user_id = ? AND hasil.kuis_id = kuis.id AND hasil.lulus = 1
                    ) AS kuis_lulus
             FROM kuis_kursus kuis
             WHERE kuis.kursus_id = ?'
        );
        $stmt->execute([$userId, $courseId]);
        $quiz = $stmt->fetch();

        $totalMateri = (int) $progress['total_materi'];
        $materiSelesai = (int) $progress['materi_selesai'];
        $totalBagian = $totalMateri + ($quiz ? 1 : 0);
        $bagianSelesai = $materiSelesai + ($quiz && (int) $quiz['kuis_lulus'] === 1 ? 1 : 0);

        return [
            'total_materi' => $totalMateri,
            'materi_selesai' => $materiSelesai,
            'kuis_lulus' => $quiz ? (bool) $quiz['kuis_lulus'] : true,
            'persentase' => $totalBagian > 0 ? (int) floor(($bagianSelesai / $totalBagian) * 100) : 0,
            'selesai' => $totalBagian > 0 && $bagianSelesai === $totalBagian,
        ];
    }

    public function updateEnrollmentCompletion(int $userId, int $courseId): void
    {
        $progress = $this->getProgress($userId, $courseId);
        if (!$progress['selesai']) {
            return;
        }

        $stmt = $this->db->prepare(
            'UPDATE pendaftaran_kursus
             SET selesai_pada = CURRENT_TIMESTAMP
             WHERE user_id = ? AND kursus_id = ? AND selesai_pada IS NULL'
        );
        $stmt->execute([$userId, $courseId]);

        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO sertifikat (user_id, kursus_id, nomor_sertifikat, nama_peserta)
             SELECT user.id, kursus.id, ?, user.nama
             FROM user
             JOIN kursus ON kursus.id = ?
             WHERE user.id = ?'
        );
        $stmt->execute([
            'SA-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(6))),
            $courseId,
            $userId,
        ]);
    }

    public function getQuiz(int $courseId): ?array
    {
        $stmt = $this->db->prepare('SELECT id, judul, nilai_minimum FROM kuis_kursus WHERE kursus_id = ?');
        $stmt->execute([$courseId]);
        $quiz = $stmt->fetch();

        if (!$quiz) {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT id, urutan, teks FROM soal_kuis WHERE kuis_id = ? ORDER BY urutan'
        );
        $stmt->execute([$quiz['id']]);
        $questions = $stmt->fetchAll();

        $stmt = $this->db->prepare(
            'SELECT id, soal_id, urutan, teks FROM opsi_kuis WHERE soal_id = ? ORDER BY urutan'
        );
        foreach ($questions as &$question) {
            $stmt->execute([$question['id']]);
            $question['opsi'] = $stmt->fetchAll();
        }
        unset($question);

        $quiz['soal'] = $questions;
        return $quiz;
    }

    public function getCorrectOptionIds(int $quizId): array
    {
        $stmt = $this->db->prepare(
            'SELECT soal.id AS soal_id, opsi.id AS opsi_id
             FROM soal_kuis soal
             JOIN opsi_kuis opsi ON opsi.soal_id = soal.id
             WHERE soal.kuis_id = ? AND opsi.benar = 1'
        );
        $stmt->execute([$quizId]);

        $correctOptions = [];
        foreach ($stmt->fetchAll() as $row) {
            $correctOptions[(int) $row['soal_id']] = (int) $row['opsi_id'];
        }

        return $correctOptions;
    }

    public function saveAttempt(int $userId, int $quizId, int $score, int $correct, int $total, bool $passed): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO hasil_kuis (user_id, kuis_id, nilai, jumlah_benar, jumlah_soal, lulus)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $quizId, $score, $correct, $total, (int) $passed]);
    }

    public function getLatestAttempt(int $userId, int $quizId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT nilai, jumlah_benar, jumlah_soal, lulus, dikerjakan_pada
             FROM hasil_kuis
             WHERE user_id = ? AND kuis_id = ?
             ORDER BY dikerjakan_pada DESC, id DESC
             LIMIT 1'
        );
        $stmt->execute([$userId, $quizId]);
        $attempt = $stmt->fetch();

        return $attempt ?: null;
    }
}
