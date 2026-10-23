USE db_sertifikasi;

-- AKUN DEMO (hapus/ganti sebelum dipakai sungguhan)
-- admin@demo.test      / admin123
-- instruktur@demo.test / instruktur123
-- siswa@demo.test      / siswa123
-- zeta1@gmail.com      / zeta123
-- Akun awal (password sama untuk ketiganya: siswa123)
INSERT IGNORE INTO user (email, nama, password, role) VALUES
('admin',                'Admin',                   '$2y$10$4SE3FUp67TKKgST6UmdkA.VB1e2Uwfvws0eoUH5aQMVj5zM74SBNq', 'admin'),
('instruktur',           'Instruktur',              '$2y$10$4SE3FUp67TKKgST6UmdkA.VB1e2Uwfvws0eoUH5aQMVj5zM74SBNq', 'instruktur'),
('siswa',                'Mikhael rakarenzo',       '$2y$10$4SE3FUp67TKKgST6UmdkA.VB1e2Uwfvws0eoUH5aQMVj5zM74SBNq', 'siswa'),
('admin@demo.test',      'Admin Demo',      '$2y$10$zuXJ5539EEfvTkPKOh040uxfLnAIrQiZHK95the87pkFsVOvr5Twq', 'admin'),
('instruktur@demo.test', 'Instruktur Demo', '$2y$10$uKv//r.USAYajWM7FgJ9/eRt98BvZbYENadLvTXLw2TpWC8SWDoSS', 'instruktur'),
('siswa@demo.test',      'Siswa Demo',      '$2y$10$Stjxw/YUtOtfndly9103Q.ITPGpq762gSCAWZJ8vcM75xmw3CcK66', 'siswa'),
('zeta1@gmail.com',      'zeta ruby chu simanjuntak', '$2y$10$DGXOf05oBMUNORKQ6HpxK.CWCrzLIbIbTiiaH2ewHS2MR.ZXwfkIC', 'siswa');
