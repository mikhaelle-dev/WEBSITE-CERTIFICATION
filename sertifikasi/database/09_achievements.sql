USE db_sertifikasi;

CREATE TABLE IF NOT EXISTS lencana_kursus (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    kursus_id   INT NOT NULL,
    materi_id   INT NOT NULL UNIQUE,
    kode        VARCHAR(80) NOT NULL,
    judul       VARCHAR(120) NOT NULL,
    deskripsi   VARCHAR(255) NOT NULL,
    ikon        VARCHAR(60) NOT NULL,
    UNIQUE KEY uq_lencana_kursus_kode (kursus_id, kode),
    CONSTRAINT fk_lencana_kursus_kursus
        FOREIGN KEY (kursus_id) REFERENCES kursus(id) ON DELETE CASCADE,
    CONSTRAINT fk_lencana_kursus_materi
        FOREIGN KEY (materi_id) REFERENCES materi_kursus(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS lencana_pengguna (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    lencana_id    INT NOT NULL,
    diperoleh_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_lencana_pengguna (user_id, lencana_id),
    CONSTRAINT fk_lencana_pengguna_user
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    CONSTRAINT fk_lencana_pengguna_lencana
        FOREIGN KEY (lencana_id) REFERENCES lencana_kursus(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS sertifikat (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT NOT NULL,
    kursus_id         INT NOT NULL,
    nomor_sertifikat  VARCHAR(40) NOT NULL UNIQUE,
    nama_peserta      VARCHAR(150) NOT NULL,
    diterbitkan_pada  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_sertifikat_user_kursus (user_id, kursus_id),
    CONSTRAINT fk_sertifikat_user
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    CONSTRAINT fk_sertifikat_kursus
        FOREIGN KEY (kursus_id) REFERENCES kursus(id) ON DELETE CASCADE
);

INSERT INTO lencana_kursus (kursus_id, materi_id, kode, judul, deskripsi, ikon)
SELECT kursus.id, materi.id, 'dasar-keamanan', 'Fondasi Keamanan',
       'Menyelesaikan materi Dasar Keamanan Siber.', 'bi-shield-lock'
FROM kursus
JOIN materi_kursus materi ON materi.kursus_id = kursus.id AND materi.urutan = 1
WHERE kursus.slug = 'pengenalan-keamanan-siber'
ON DUPLICATE KEY UPDATE
    judul = VALUES(judul), deskripsi = VALUES(deskripsi), ikon = VALUES(ikon);

INSERT INTO lencana_kursus (kursus_id, materi_id, kode, judul, deskripsi, ikon)
SELECT kursus.id, materi.id, 'penjaga-akun', 'Penjaga Akun',
       'Menyelesaikan materi perlindungan akun dan pengenalan phishing.', 'bi-key'
FROM kursus
JOIN materi_kursus materi ON materi.kursus_id = kursus.id AND materi.urutan = 2
WHERE kursus.slug = 'pengenalan-keamanan-siber'
ON DUPLICATE KEY UPDATE
    judul = VALUES(judul), deskripsi = VALUES(deskripsi), ikon = VALUES(ikon);

INSERT INTO lencana_kursus (kursus_id, materi_id, kode, judul, deskripsi, ikon)
SELECT kursus.id, materi.id, 'pelindung-data', 'Pelindung Data',
       'Menyelesaikan materi perlindungan perangkat dan data.', 'bi-database-lock'
FROM kursus
JOIN materi_kursus materi ON materi.kursus_id = kursus.id AND materi.urutan = 3
WHERE kursus.slug = 'pengenalan-keamanan-siber'
ON DUPLICATE KEY UPDATE
    judul = VALUES(judul), deskripsi = VALUES(deskripsi), ikon = VALUES(ikon);
