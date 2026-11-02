USE db_sertifikasi;

CREATE TABLE IF NOT EXISTS kelas (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    instruktur_id   INT NOT NULL,
    slug            VARCHAR(120) NOT NULL UNIQUE,
    judul           VARCHAR(150) NOT NULL,
    deskripsi       TEXT NOT NULL,
    status          ENUM('draft', 'aktif', 'ditutup') NOT NULL DEFAULT 'draft',
    enroll_key      VARCHAR(50) NOT NULL UNIQUE,
    icon_url        VARCHAR(255) NULL DEFAULT NULL,
    badge_label     VARCHAR(60) NULL DEFAULT NULL,
    badge_aktif     TINYINT(1) NOT NULL DEFAULT 0,
    badge_color     VARCHAR(30) NULL DEFAULT NULL,
    max_siswa       INT UNSIGNED NULL DEFAULT NULL,
    dibuat_pada     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    diperbarui_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_kelas_instruktur (instruktur_id, status),
    KEY idx_kelas_status (status),
    CONSTRAINT fk_kelas_instruktur
        FOREIGN KEY (instruktur_id) REFERENCES user(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS kelas_siswa (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    kelas_id      INT NOT NULL,
    user_id       INT NOT NULL,
    status        ENUM('pending', 'aktif', 'selesai') NOT NULL DEFAULT 'aktif',
    progress      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    terakhir_dilihat TIMESTAMP NULL DEFAULT NULL,
    daftar_pada   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    selesai_pada  TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_kelas_siswa (kelas_id, user_id),
    KEY idx_kelas_siswa_user (user_id, status),
    CONSTRAINT fk_kelas_siswa_kelas
        FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE,
    CONSTRAINT fk_kelas_siswa_user
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS modul_kelas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    kelas_id    INT NOT NULL,
    urutan      SMALLINT UNSIGNED NOT NULL,
    judul       VARCHAR(150) NOT NULL,
    deskripsi   TEXT NULL,
    UNIQUE KEY uq_modul_kelas_urutan (kelas_id, urutan),
    CONSTRAINT fk_modul_kelas_kelas
        FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS materi_kelas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    modul_id    INT NOT NULL,
    urutan      SMALLINT UNSIGNED NOT NULL,
    judul       VARCHAR(150) NOT NULL,
    isi         LONGTEXT NOT NULL,
    tipe        ENUM('teks', 'video', 'latihan') NOT NULL DEFAULT 'teks',
    UNIQUE KEY uq_materi_kelas_urutan (modul_id, urutan),
    CONSTRAINT fk_materi_kelas_modul
        FOREIGN KEY (modul_id) REFERENCES modul_kelas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS progres_materi_kelas (
    user_id           INT NOT NULL,
    materi_id         INT NOT NULL,
    pertama_dibuka    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    selesai_dibuka    TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (user_id, materi_id),
    CONSTRAINT fk_progres_materi_kelas_user
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    CONSTRAINT fk_progres_materi_kelas_materi
        FOREIGN KEY (materi_id) REFERENCES materi_kelas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS kuis_kelas (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    kelas_id        INT NOT NULL,
    judul           VARCHAR(150) NOT NULL,
    nilai_minimum   TINYINT UNSIGNED NOT NULL DEFAULT 70,
    waktu_durasi    INT UNSIGNED NULL DEFAULT NULL,
    dibuat_pada     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_kuis_kelas_kelas (kelas_id),
    CONSTRAINT fk_kuis_kelas_kelas
        FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS soal_kuis_kelas (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    kuis_id   INT NOT NULL,
    urutan    SMALLINT UNSIGNED NOT NULL,
    teks      TEXT NOT NULL,
    UNIQUE KEY uq_soal_kuis_kelas_urutan (kuis_id, urutan),
    CONSTRAINT fk_soal_kuis_kelas_kuis
        FOREIGN KEY (kuis_id) REFERENCES kuis_kelas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS opsi_kuis_kelas (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    soal_id   INT NOT NULL,
    urutan    TINYINT UNSIGNED NOT NULL,
    teks      VARCHAR(500) NOT NULL,
    benar     TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_opsi_kuis_kelas_urutan (soal_id, urutan),
    CONSTRAINT fk_opsi_kuis_kelas_soal
        FOREIGN KEY (soal_id) REFERENCES soal_kuis_kelas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS hasil_kuis_kelas (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    kuis_id         INT NOT NULL,
    nilai           TINYINT UNSIGNED NOT NULL,
    jumlah_benar    SMALLINT UNSIGNED NOT NULL,
    jumlah_soal     SMALLINT UNSIGNED NOT NULL,
    lulus           TINYINT(1) NOT NULL,
    dikerjakan_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_hasil_kuis_kelas_user_kuis (user_id, kuis_id, dikerjakan_pada),
    CONSTRAINT fk_hasil_kuis_kelas_user
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    CONSTRAINT fk_hasil_kuis_kelas_kuis
        FOREIGN KEY (kuis_id) REFERENCES kuis_kelas(id) ON DELETE CASCADE
);
