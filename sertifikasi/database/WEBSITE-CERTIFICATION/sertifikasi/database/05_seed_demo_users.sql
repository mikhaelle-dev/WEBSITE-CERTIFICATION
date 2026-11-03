USE db_sertifikasi;

-- Akun awal (password sama untuk ketiganya: siswa123)
INSERT IGNORE INTO user (email, nama, password, role) VALUES
('admin',                'Admin',                   '$2y$10$4SE3FUp67TKKgST6UmdkA.VB1e2Uwfvws0eoUH5aQMVj5zM74SBNq', 'admin'),
('instruktur',           'Instruktur',              '$2y$10$4SE3FUp67TKKgST6UmdkA.VB1e2Uwfvws0eoUH5aQMVj5zM74SBNq', 'instruktur'),
('siswa',                'Mikhael rakarenzo',       '$2y$10$4SE3FUp67TKKgST6UmdkA.VB1e2Uwfvws0eoUH5aQMVj5zM74SBNq', 'siswa');