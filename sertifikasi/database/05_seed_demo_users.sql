USE db_sertifikasi;

-- Akun demo lokal (password sama untuk keduanya: siswa123)
INSERT IGNORE INTO user (email, nama, password, role) VALUES
('instruktur@example.test', 'Instruktur Demo',       '$2y$10$4SE3FUp67TKKgST6UmdkA.VB1e2Uwfvws0eoUH5aQMVj5zM74SBNq', 'instruktur'),
('siswa@example.test',      'Siswa Demo',            '$2y$10$4SE3FUp67TKKgST6UmdkA.VB1e2Uwfvws0eoUH5aQMVj5zM74SBNq', 'siswa');