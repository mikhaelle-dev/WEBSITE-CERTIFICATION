USE db_sertifikasi;

CREATE TABLE IF NOT EXISTS kursus (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    slug        VARCHAR(120) NOT NULL UNIQUE,
    judul       VARCHAR(150) NOT NULL,
    kategori    VARCHAR(80) NOT NULL,
    deskripsi   TEXT NOT NULL,
    level       VARCHAR(30) NOT NULL DEFAULT 'Beginner',
    durasi      VARCHAR(30) NOT NULL DEFAULT '8 jam',
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS materi_kursus (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    kursus_id INT NOT NULL,
    urutan    SMALLINT UNSIGNED NOT NULL,
    judul     VARCHAR(150) NOT NULL,
    isi       LONGTEXT NOT NULL,
    UNIQUE KEY uq_materi_kursus_urutan (kursus_id, urutan),
    CONSTRAINT fk_materi_kursus_kursus
        FOREIGN KEY (kursus_id) REFERENCES kursus(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS progres_materi (
    user_id        INT NOT NULL,
    materi_id      INT NOT NULL,
    pertama_dibuka TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, materi_id),
    CONSTRAINT fk_progres_materi_user
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    CONSTRAINT fk_progres_materi_materi
        FOREIGN KEY (materi_id) REFERENCES materi_kursus(id) ON DELETE CASCADE
);
