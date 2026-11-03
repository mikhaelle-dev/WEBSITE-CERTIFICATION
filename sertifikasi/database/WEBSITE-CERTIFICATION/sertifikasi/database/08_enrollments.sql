USE db_sertifikasi;

CREATE TABLE IF NOT EXISTS pendaftaran_kursus (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    kursus_id   INT NOT NULL,
    mulai_pada  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    selesai_pada TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_pendaftaran_user_kursus (user_id, kursus_id),
    KEY idx_pendaftaran_user_status (user_id, selesai_pada),
    CONSTRAINT fk_pendaftaran_user
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    CONSTRAINT fk_pendaftaran_kursus
        FOREIGN KEY (kursus_id) REFERENCES kursus(id) ON DELETE CASCADE
);
