USE db_sertifikasi;

CREATE TABLE IF NOT EXISTS certificate_templates (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    instructor_id   INT NOT NULL,
    background_path VARCHAR(255) NULL,
    signature_path  VARCHAR(255) NULL,
    signer_name     VARCHAR(150) NOT NULL,
    signer_title    VARCHAR(150) NOT NULL,
    number_format   VARCHAR(180) NOT NULL DEFAULT 'CERT/{YEAR}/{CLASS_ID}/{RANDOM}',
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_certificate_template_instructor (instructor_id),
    CONSTRAINT fk_certificate_template_instructor
        FOREIGN KEY (instructor_id) REFERENCES user(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_certificates (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    student_id      INT NOT NULL,
    class_id        INT NOT NULL,
    certificate_no  VARCHAR(180) NOT NULL UNIQUE,
    status          ENUM('pending', 'issued') NOT NULL DEFAULT 'pending',
    issued_at       TIMESTAMP NULL DEFAULT NULL,
    issued_by       INT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_certificate_student_class (student_id, class_id),
    KEY idx_user_certificate_class_status (class_id, status),
    CONSTRAINT fk_user_certificate_student
        FOREIGN KEY (student_id) REFERENCES user(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_certificate_class
        FOREIGN KEY (class_id) REFERENCES kelas(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_certificate_issuer
        FOREIGN KEY (issued_by) REFERENCES user(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS forum_threads (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    class_id        INT NOT NULL,
    student_id      INT NOT NULL,
    title           VARCHAR(180) NOT NULL,
    question        TEXT NOT NULL,
    status          ENUM('unanswered', 'answered') NOT NULL DEFAULT 'unanswered',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_forum_threads_class_status (class_id, status, updated_at),
    KEY idx_forum_threads_student (student_id, created_at),
    CONSTRAINT fk_forum_thread_class
        FOREIGN KEY (class_id) REFERENCES kelas(id) ON DELETE CASCADE,
    CONSTRAINT fk_forum_thread_student
        FOREIGN KEY (student_id) REFERENCES user(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS forum_replies (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    thread_id       INT NOT NULL,
    author_id       INT NOT NULL,
    message         TEXT NOT NULL,
    code_snippet    MEDIUMTEXT NULL,
    attachment_path VARCHAR(255) NULL,
    attachment_name VARCHAR(255) NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_forum_replies_thread (thread_id, created_at),
    CONSTRAINT fk_forum_reply_thread
        FOREIGN KEY (thread_id) REFERENCES forum_threads(id) ON DELETE CASCADE,
    CONSTRAINT fk_forum_reply_author
        FOREIGN KEY (author_id) REFERENCES user(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS class_assignments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    class_id        INT NOT NULL,
    instructor_id   INT NOT NULL,
    title           VARCHAR(180) NOT NULL,
    description     TEXT NOT NULL,
    due_at          DATETIME NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_class_assignments_class (class_id, created_at),
    CONSTRAINT fk_class_assignment_class
        FOREIGN KEY (class_id) REFERENCES kelas(id) ON DELETE CASCADE,
    CONSTRAINT fk_class_assignment_instructor
        FOREIGN KEY (instructor_id) REFERENCES user(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS class_assignment_submissions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id   INT NOT NULL,
    student_id      INT NOT NULL,
    file_path       VARCHAR(255) NOT NULL,
    file_name       VARCHAR(255) NOT NULL,
    submitted_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_class_assignment_submission (assignment_id, student_id),
    CONSTRAINT fk_class_submission_assignment
        FOREIGN KEY (assignment_id) REFERENCES class_assignments(id) ON DELETE CASCADE,
    CONSTRAINT fk_class_submission_student
        FOREIGN KEY (student_id) REFERENCES user(id) ON DELETE CASCADE
);
