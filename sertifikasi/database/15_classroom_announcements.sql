USE db_sertifikasi;

CREATE TABLE IF NOT EXISTS pengumuman_kelas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    kelas_id    INT NOT NULL,
    penulis_id  INT NOT NULL,
    isi         TEXT NOT NULL,
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pengumuman_kelas_kelas (kelas_id, dibuat_pada),
    CONSTRAINT fk_pengumuman_kelas_kelas
        FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE,
    CONSTRAINT fk_pengumuman_kelas_user
        FOREIGN KEY (penulis_id) REFERENCES user(id) ON DELETE CASCADE
);
