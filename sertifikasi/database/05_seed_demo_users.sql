USE db_sertifikasi;

-- AKUN DEMO (hapus/ganti sebelum dipakai sungguhan)
-- admin@demo.test      / admin123
-- instruktur@demo.test / instruktur123
-- siswa@demo.test      / siswa123
INSERT IGNORE INTO user (email, nama, password, role) VALUES
('admin@demo.test',      'Admin Demo',      '$2y$10$zuXJ5539EEfvTkPKOh040uxfLnAIrQiZHK95the87pkFsVOvr5Twq', 'admin'),
('instruktur@demo.test', 'Instruktur Demo', '$2y$10$uKv//r.USAYajWM7FgJ9/eRt98BvZbYENadLvTXLw2TpWC8SWDoSS', 'instruktur'),
('siswa@demo.test',      'Siswa Demo',      '$2y$10$Stjxw/YUtOtfndly9103Q.ITPGpq762gSCAWZJ8vcM75xmw3CcK66', 'siswa');
