USE db_sertifikasi;

CREATE TABLE IF NOT EXISTS kuis_kursus (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    kursus_id     INT NOT NULL UNIQUE,
    judul         VARCHAR(150) NOT NULL,
    nilai_minimum TINYINT UNSIGNED NOT NULL DEFAULT 70,
    CONSTRAINT fk_kuis_kursus_kursus
        FOREIGN KEY (kursus_id) REFERENCES kursus(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS soal_kuis (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    kuis_id INT NOT NULL,
    urutan  SMALLINT UNSIGNED NOT NULL,
    teks    TEXT NOT NULL,
    UNIQUE KEY uq_soal_kuis_urutan (kuis_id, urutan),
    CONSTRAINT fk_soal_kuis_kuis
        FOREIGN KEY (kuis_id) REFERENCES kuis_kursus(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS opsi_kuis (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    soal_id INT NOT NULL,
    urutan  TINYINT UNSIGNED NOT NULL,
    teks    VARCHAR(500) NOT NULL,
    benar   TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_opsi_kuis_urutan (soal_id, urutan),
    CONSTRAINT fk_opsi_kuis_soal
        FOREIGN KEY (soal_id) REFERENCES soal_kuis(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS hasil_kuis (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    kuis_id         INT NOT NULL,
    nilai           TINYINT UNSIGNED NOT NULL,
    jumlah_benar    SMALLINT UNSIGNED NOT NULL,
    jumlah_soal     SMALLINT UNSIGNED NOT NULL,
    lulus           TINYINT(1) NOT NULL,
    dikerjakan_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_hasil_kuis_user_kuis (user_id, kuis_id, dikerjakan_pada),
    CONSTRAINT fk_hasil_kuis_user
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    CONSTRAINT fk_hasil_kuis_kuis
        FOREIGN KEY (kuis_id) REFERENCES kuis_kursus(id) ON DELETE CASCADE
);
