<?php
require_once __DIR__ . '/Database.php';

class InstructorTools extends Database
{
    private function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    private function issueEligibleCertificate(int $instructorId, int $classId, int $studentId): bool
    {
        $eligible = $this->run(
            "SELECT u.nama
             FROM kelas_siswa ks
             JOIN kelas k ON k.id = ks.kelas_id AND k.instruktur_id = ?
             JOIN user u ON u.id = ks.user_id
             WHERE ks.kelas_id = ? AND ks.user_id = ?
               AND (ks.status = 'selesai' OR ks.progress >= 100)
             LIMIT 1",
            [$instructorId, $classId, $studentId]
        )->fetch();
        if (!$eligible) {
            throw new InvalidArgumentException('Peserta belum memenuhi kriteria kelulusan kelas.');
        }

        $existing = $this->run(
            "SELECT id FROM user_certificates WHERE student_id = ? AND class_id = ? AND status = 'issued' LIMIT 1",
            [$studentId, $classId]
        )->fetchColumn();
        if ($existing) {
            return false;
        }

        $number = 'CERT/' . date('Y') . '/' . $classId . '/' . strtoupper(bin2hex(random_bytes(4)));

        $this->run(
            "INSERT INTO user_certificates (student_id, class_id, certificate_no, status, issued_at, issued_by)
             VALUES (?, ?, ?, 'issued', CURRENT_TIMESTAMP, ?)
             ON DUPLICATE KEY UPDATE
                certificate_no = VALUES(certificate_no),
                status = 'issued',
                issued_at = CURRENT_TIMESTAMP,
                issued_by = VALUES(issued_by)",
            [$studentId, $classId, $number, $instructorId]
        );
        return true;
    }

    public function issueAutomaticClassCertificate(int $classId, int $studentId): bool
    {
        $class = $this->run(
            'SELECT k.instruktur_id, ks.progress
             FROM kelas k
             JOIN kelas_siswa ks ON ks.kelas_id = k.id AND ks.user_id = ?
             WHERE k.id = ?
             LIMIT 1',
            [$studentId, $classId]
        )->fetch();
        if (!$class || (int) $class['progress'] < 100) {
            return false;
        }

        return $this->issueEligibleCertificate((int) $class['instruktur_id'], $classId, $studentId);
    }

    public function issueEligibleCertificatesAutomaticallyForClass(int $instructorId, int $classId): int
    {
        $eligible = $this->run(
            "SELECT ks.user_id
             FROM kelas_siswa ks
             JOIN kelas k ON k.id = ks.kelas_id
             LEFT JOIN user_certificates uc
                ON uc.student_id = ks.user_id AND uc.class_id = ks.kelas_id AND uc.status = 'issued'
             WHERE k.id = ? AND k.instruktur_id = ?
               AND uc.id IS NULL AND ks.progress >= 100
             ORDER BY ks.user_id",
            [$classId, $instructorId]
        )->fetchAll(PDO::FETCH_COLUMN);

        $issued = 0;
        foreach ($eligible as $studentId) {
            if ($this->issueEligibleCertificate($instructorId, $classId, (int) $studentId)) {
                $issued++;
            }
        }
        return $issued;
    }

    public function getStudentIssuedCertificate(int $studentId, int $certificateId): ?array
    {
        $row = $this->run(
            "SELECT uc.*, u.nama AS student_name, k.judul AS class_title,
                    k.kategori AS category
             FROM user_certificates uc
             JOIN user u ON u.id = uc.student_id
             JOIN kelas k ON k.id = uc.class_id
             WHERE uc.id = ? AND uc.student_id = ? AND uc.status = 'issued'
             LIMIT 1",
            [$certificateId, $studentId]
        )->fetch();
        return $row ?: null;
    }

    public function getInstructorClasses(int $instructorId): array
    {
        return $this->run(
            'SELECT id, judul FROM kelas WHERE instruktur_id = ? ORDER BY judul ASC',
            [$instructorId]
        )->fetchAll();
    }

    public function getForumThreads(int $instructorId, int $classId = 0, string $status = 'all'): array
    {
        $sql = "SELECT ft.id, ft.class_id, ft.student_id, ft.title, ft.question, ft.status, ft.created_at, ft.updated_at,
                       u.nama AS student_name, k.judul AS class_title,
                       (SELECT COUNT(*) FROM forum_replies fr WHERE fr.thread_id = ft.id) AS reply_count
                FROM forum_threads ft
                JOIN kelas k ON k.id = ft.class_id AND k.instruktur_id = ?
                JOIN user u ON u.id = ft.student_id
                WHERE 1 = 1";
        $params = [$instructorId];
        if ($classId > 0) {
            $sql .= ' AND ft.class_id = ?';
            $params[] = $classId;
        }
        if ($status === 'unanswered' || $status === 'answered') {
            $sql .= ' AND ft.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY (ft.status = \'unanswered\') DESC, ft.updated_at DESC LIMIT 200';
        return $this->run($sql, $params)->fetchAll();
    }

    public function createForumThread(int $studentId, int $classId, string $title, string $question): void
    {
        $title = trim($title);
        $question = trim($question);
        if ($title === '' || $question === '') {
            throw new InvalidArgumentException('Judul dan isi pertanyaan wajib diisi.');
        }
        $enrollment = $this->run(
            "SELECT ks.kelas_id FROM kelas_siswa ks
             JOIN kelas k ON k.id = ks.kelas_id
             WHERE ks.user_id = ? AND ks.kelas_id = ? AND k.status = 'aktif'
             LIMIT 1",
            [$studentId, $classId]
        )->fetchColumn();
        if (!$enrollment) {
            throw new InvalidArgumentException('Kamu tidak terdaftar pada kelas aktif ini.');
        }
        $this->run(
            "INSERT INTO forum_threads (class_id, student_id, title, question, status)
             VALUES (?, ?, ?, ?, 'unanswered')",
            [$classId, $studentId, $title, $question]
        );
    }

    public function getStudentForumThreads(int $studentId, int $classId): array
    {
        $threads = $this->run(
            'SELECT * FROM forum_threads WHERE student_id = ? AND class_id = ? ORDER BY updated_at DESC LIMIT 50',
            [$studentId, $classId]
        )->fetchAll();
        foreach ($threads as &$thread) {
            $thread['replies'] = $this->run(
                'SELECT fr.*, u.nama AS author_name FROM forum_replies fr
                 JOIN user u ON u.id = fr.author_id
                 WHERE fr.thread_id = ? ORDER BY fr.created_at ASC',
                [(int) $thread['id']]
            )->fetchAll();
        }
        unset($thread);
        return $threads;
    }

    public function getUnansweredCount(int $instructorId): int
    {
        return (int) $this->run(
            "SELECT COUNT(*) FROM forum_threads ft
             JOIN kelas k ON k.id = ft.class_id
             WHERE k.instruktur_id = ? AND ft.status = 'unanswered'",
            [$instructorId]
        )->fetchColumn();
    }

    public function getForumThread(int $instructorId, int $threadId): ?array
    {
        $thread = $this->run(
            'SELECT ft.*, u.nama AS student_name, k.judul AS class_title
             FROM forum_threads ft
             JOIN kelas k ON k.id = ft.class_id AND k.instruktur_id = ?
             JOIN user u ON u.id = ft.student_id
             WHERE ft.id = ?
             LIMIT 1',
            [$instructorId, $threadId]
        )->fetch();
        if (!$thread) {
            return null;
        }
        $thread['replies'] = $this->run(
            'SELECT fr.*, u.nama AS author_name, u.role AS author_role
             FROM forum_replies fr
             JOIN user u ON u.id = fr.author_id
             WHERE fr.thread_id = ?
             ORDER BY fr.created_at ASC, fr.id ASC',
            [$threadId]
        )->fetchAll();
        return $thread;
    }

    public function createForumReply(int $instructorId, int $threadId, string $message, string $codeSnippet, string $attachmentPath, string $attachmentName): void
    {
        $thread = $this->run(
            'SELECT ft.id FROM forum_threads ft JOIN kelas k ON k.id = ft.class_id
             WHERE ft.id = ? AND k.instruktur_id = ? LIMIT 1',
            [$threadId, $instructorId]
        )->fetchColumn();
        if (!$thread) {
            throw new InvalidArgumentException('Pertanyaan tidak ditemukan.');
        }

        $message = trim($message);
        $codeSnippet = trim($codeSnippet);
        if ($message === '' && $codeSnippet === '' && $attachmentPath === '') {
            throw new InvalidArgumentException('Isi jawaban atau lampiran wajib diisi.');
        }

        $this->db->beginTransaction();
        try {
            $this->run(
                'INSERT INTO forum_replies (thread_id, author_id, message, code_snippet, attachment_path, attachment_name)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$threadId, $instructorId, $message, $codeSnippet !== '' ? $codeSnippet : null, $attachmentPath !== '' ? $attachmentPath : null, $attachmentName !== '' ? $attachmentName : null]
            );
            $this->run(
                "UPDATE forum_threads SET status = 'answered', updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$threadId]
            );
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function createClassAssignment(int $instructorId, int $classId, array $data): int
    {
        $ownsClass = $this->run(
            'SELECT id FROM kelas WHERE id = ? AND instruktur_id = ? LIMIT 1',
            [$classId, $instructorId]
        )->fetchColumn();
        if (!$ownsClass) {
            throw new InvalidArgumentException('Kelas tidak ditemukan.');
        }
        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        if ($title === '' || $description === '') {
            throw new InvalidArgumentException('Judul dan instruksi tugas wajib diisi.');
        }
        $dueAt = trim((string) ($data['due_at'] ?? ''));
        if ($dueAt !== '') {
            $dueDate = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $dueAt);
            $dateErrors = DateTimeImmutable::getLastErrors();
            if ($dueDate === false || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
                throw new InvalidArgumentException('Format tenggat tugas tidak valid.');
            }
            $dueAt = $dueDate->format('Y-m-d H:i:s');
        } else {
            $dueAt = null;
        }
        $this->run(
            'INSERT INTO class_assignments (class_id, instructor_id, title, description, due_at)
             VALUES (?, ?, ?, ?, ?)',
            [$classId, $instructorId, $title, $description, $dueAt]
        );
        return (int) $this->db->lastInsertId();
    }

    public function getClassAssignments(int $instructorId, int $classId): array
    {
        return $this->run(
            'SELECT a.*, (SELECT COUNT(*) FROM class_assignment_submissions s WHERE s.assignment_id = a.id) AS submission_count
             FROM class_assignments a JOIN kelas k ON k.id = a.class_id
             WHERE a.class_id = ? AND k.instruktur_id = ? ORDER BY a.created_at DESC',
            [$classId, $instructorId]
        )->fetchAll();
    }

    public function getAssignmentSubmissions(int $instructorId, int $assignmentId): array
    {
        return $this->run(
            'SELECT s.*, u.nama AS student_name
             FROM class_assignment_submissions s
             JOIN class_assignments a ON a.id = s.assignment_id
             JOIN kelas k ON k.id = a.class_id AND k.instruktur_id = ?
             JOIN user u ON u.id = s.student_id
             WHERE a.id = ? ORDER BY s.submitted_at DESC',
            [$instructorId, $assignmentId]
        )->fetchAll();
    }

    public function getStudentClassAssignments(int $studentId, int $classId): array
    {
        return $this->run(
            'SELECT a.*, s.file_path AS submission_path, s.file_name AS submission_name, s.submitted_at
             FROM class_assignments a
             JOIN kelas_siswa ks ON ks.kelas_id = a.class_id AND ks.user_id = ?
             LEFT JOIN class_assignment_submissions s ON s.assignment_id = a.id AND s.student_id = ?
             WHERE a.class_id = ? ORDER BY a.created_at DESC',
            [$studentId, $studentId, $classId]
        )->fetchAll();
    }

    public function submitClassAssignment(int $studentId, int $classId, int $assignmentId, string $filePath, string $fileName): void
    {
        if ($filePath === '' || $fileName === '') {
            throw new InvalidArgumentException('File tugas wajib dipilih.');
        }
        $assignment = $this->run(
            'SELECT a.id FROM class_assignments a
             JOIN kelas_siswa ks ON ks.kelas_id = a.class_id AND ks.user_id = ?
             WHERE a.id = ? AND a.class_id = ? LIMIT 1',
            [$studentId, $assignmentId, $classId]
        )->fetchColumn();
        if (!$assignment) {
            throw new InvalidArgumentException('Tugas tidak ditemukan atau Anda tidak terdaftar pada kelas ini.');
        }
        $this->run(
            'INSERT INTO class_assignment_submissions (assignment_id, student_id, file_path, file_name)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), file_name = VALUES(file_name), submitted_at = CURRENT_TIMESTAMP',
            [$assignmentId, $studentId, $filePath, $fileName]
        );
    }

    public function getClassReport(int $instructorId, int $classId, string $startDate, string $endDate): array
    {
        $class = $this->run(
            'SELECT id, judul FROM kelas WHERE id = ? AND instruktur_id = ? LIMIT 1',
            [$classId, $instructorId]
        )->fetch();
        if (!$class) {
            throw new InvalidArgumentException('Kelas tidak ditemukan.');
        }

        $students = $this->run(
            'SELECT ks.user_id AS student_id, u.nama, u.email, ks.progress, ks.status AS enrollment_status,
                    ks.daftar_pada, ks.selesai_pada,
                    uc.id AS certificate_id, uc.certificate_no, uc.status AS certificate_status
             FROM kelas_siswa ks
             JOIN user u ON u.id = ks.user_id
             LEFT JOIN user_certificates uc ON uc.student_id = ks.user_id AND uc.class_id = ks.kelas_id
             WHERE ks.kelas_id = ? AND ks.daftar_pada >= ? AND ks.daftar_pada < DATE_ADD(?, INTERVAL 1 DAY)
             ORDER BY u.nama ASC',
            [$classId, $startDate, $endDate]
        )->fetchAll();

        $quizzes = $this->run(
            'SELECT id, judul, nilai_minimum FROM kuis_kelas WHERE kelas_id = ?',
            [$classId]
        )->fetchAll();
        $quizIds = [];
        $quizMinimum = [];
        foreach ($quizzes as $quiz) {
            $quizIds[(int) $quiz['id']] = true;
            $quizMinimum[(int) $quiz['id']] = (int) $quiz['nilai_minimum'];
        }

        $latestResults = [];
        if ($quizIds && $students) {
            $results = $this->run(
                'SELECT hk.user_id, hk.kuis_id, hk.nilai, hk.lulus, hk.dikerjakan_pada
                 FROM hasil_kuis_kelas hk
                 JOIN kuis_kelas q ON q.id = hk.kuis_id AND q.kelas_id = ?
                 WHERE hk.dikerjakan_pada >= ? AND hk.dikerjakan_pada < DATE_ADD(?, INTERVAL 1 DAY)
                 ORDER BY hk.dikerjakan_pada DESC, hk.id DESC',
                [$classId, $startDate, $endDate]
            )->fetchAll();
            foreach ($results as $result) {
                $studentId = (int) $result['user_id'];
                $quizId = (int) $result['kuis_id'];
                if (!isset($latestResults[$studentId][$quizId])) {
                    $latestResults[$studentId][$quizId] = $result;
                }
            }
        }

        $issuedCount = (int) $this->run(
            "SELECT COUNT(*)
             FROM user_certificates uc
             JOIN kelas k ON k.id = uc.class_id AND k.instruktur_id = ?
             WHERE uc.class_id = ? AND uc.status = 'issued'
               AND uc.issued_at >= ? AND uc.issued_at < DATE_ADD(?, INTERVAL 1 DAY)",
            [$instructorId, $classId, $startDate, $endDate]
        )->fetchColumn();
        $passingCount = 0;
        $scoredStudents = 0;
        $scoreTotal = 0;
        $progressTotal = 0;
        foreach ($students as &$student) {
            $studentId = (int) $student['student_id'];
            $studentResults = $latestResults[$studentId] ?? [];
            $score = null;
            if ($studentResults) {
                $score = (int) round(array_sum(array_map(
                    static function (array $result): int {
                        return (int) $result['nilai'];
                    },
                    array_values($studentResults)
                )) / count($studentResults));
                $scoreTotal += $score;
                $scoredStudents++;
            }

            $allQuizzesPassed = true;
            foreach ($quizIds as $quizId => $_present) {
                if (!isset($studentResults[$quizId]) || empty($studentResults[$quizId]['lulus'])
                    || (int) $studentResults[$quizId]['nilai'] < $quizMinimum[$quizId]) {
                    $allQuizzesPassed = false;
                    break;
                }
            }
            $isPassed = (int) $student['progress'] >= 100 && $allQuizzesPassed;
            $student['final_score'] = $score;
            $student['passed'] = $isPassed;
            $student['latest_quiz_results'] = array_values($studentResults);
            $student['certificate_issued'] = ($student['certificate_status'] ?? '') === 'issued';
            $progressTotal += (int) $student['progress'];
            if ($isPassed) {
                $passingCount++;
            }
        }
        unset($student);

        $completion = [];
        foreach ($students as $student) {
            $completion[] = [
                'name' => (string) $student['nama'],
                'progress' => (int) $student['progress'],
            ];
        }

        return [
            'class' => $class,
            'students' => $students,
            'quizzes' => $quizzes,
            'metrics' => [
                'average_score' => $scoredStudents > 0 ? (int) round($scoreTotal / $scoredStudents) : null,
                'pass_percentage' => count($students) > 0 ? (int) round(($passingCount / count($students)) * 100) : 0,
                'issued_certificates' => $issuedCount,
                'average_progress' => count($students) > 0 ? (int) round($progressTotal / count($students)) : 0,
                'student_count' => count($students),
                'passed_count' => $passingCount,
            ],
            'completion' => $completion,
        ];
    }
}
