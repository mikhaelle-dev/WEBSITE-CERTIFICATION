USE db_sertifikasi;

CREATE TABLE IF NOT EXISTS user_feedback (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    rating      TINYINT UNSIGNED NOT NULL,
    category    ENUM('Saran dan kritik', 'Laporan bug', 'Permintaan fitur') NOT NULL,
    message     TEXT NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_user_feedback_rating CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT fk_user_feedback_user
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    KEY idx_user_feedback_created_at (created_at)
);
