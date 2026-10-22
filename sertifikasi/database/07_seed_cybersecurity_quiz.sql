USE db_sertifikasi;

SET @kursus_keamanan_siber = (
    SELECT id FROM kursus WHERE slug = 'pengenalan-keamanan-siber'
);

INSERT INTO kuis_kursus (kursus_id, judul, nilai_minimum)
VALUES (@kursus_keamanan_siber, 'Kuis Dasar Keamanan Siber', 70)
ON DUPLICATE KEY UPDATE
    judul = VALUES(judul),
    nilai_minimum = VALUES(nilai_minimum);

SET @kuis_keamanan_siber = (
    SELECT id FROM kuis_kursus WHERE kursus_id = @kursus_keamanan_siber
);

DELETE FROM soal_kuis
WHERE kuis_id = @kuis_keamanan_siber AND urutan > 5;

INSERT INTO soal_kuis (kuis_id, urutan, teks)
VALUES
(@kuis_keamanan_siber, 1, 'Apa tujuan utama kerahasiaan (confidentiality) dalam keamanan informasi?'),
(@kuis_keamanan_siber, 2, 'Apa cara yang paling baik untuk mengelola kata sandi berbagai akun?'),
(@kuis_keamanan_siber, 3, 'Sebuah pesan tak terduga meminta kode OTP dengan alasan akun akan ditutup. Apa tindakan yang tepat?'),
(@kuis_keamanan_siber, 4, 'Mengapa pembaruan sistem dan aplikasi penting?'),
(@kuis_keamanan_siber, 5, 'Apa manfaat utama membuat cadangan data secara berkala?')
ON DUPLICATE KEY UPDATE teks = VALUES(teks);

DELETE opsi
FROM opsi_kuis opsi
JOIN soal_kuis soal ON soal.id = opsi.soal_id
WHERE soal.kuis_id = @kuis_keamanan_siber AND opsi.urutan > 4;

INSERT INTO opsi_kuis (soal_id, urutan, teks, benar)
SELECT soal.id, seed.urutan_opsi, seed.teks, seed.benar
FROM soal_kuis soal
JOIN (
    SELECT 1 AS urutan_soal, 1 AS urutan_opsi, 'Memastikan informasi hanya dapat diakses pihak yang berhak.' AS teks, 1 AS benar
    UNION ALL SELECT 1, 2, 'Membuat semua informasi tersedia untuk siapa saja.', 0
    UNION ALL SELECT 1, 3, 'Menghapus data lama secara otomatis.', 0
    UNION ALL SELECT 1, 4, 'Mempercepat koneksi internet.', 0

    UNION ALL SELECT 2, 1, 'Menggunakan kata sandi panjang dan unik, dibantu pengelola kata sandi tepercaya.', 1
    UNION ALL SELECT 2, 2, 'Menuliskan semua kata sandi di tempat yang dapat dilihat umum.', 0
    UNION ALL SELECT 2, 3, 'Membagikan kata sandi kepada rekan kerja.', 0
    UNION ALL SELECT 2, 4, 'Menggunakan satu kata sandi yang sama agar mudah diingat.', 0

    UNION ALL SELECT 3, 1, 'Tidak membagikan kode, lalu memeriksa akun melalui kanal resmi.', 1
    UNION ALL SELECT 3, 2, 'Meneruskan kode itu kepada teman untuk diperiksa.', 0
    UNION ALL SELECT 3, 3, 'Membuka semua tautan di dalam pesan.', 0
    UNION ALL SELECT 3, 4, 'Segera membalas pesan dan mengirim kode OTP.', 0

    UNION ALL SELECT 4, 1, 'Agar kerentanan yang diketahui dapat diperbaiki dan perangkat tetap didukung.', 1
    UNION ALL SELECT 4, 2, 'Agar pencadangan data tidak lagi diperlukan.', 0
    UNION ALL SELECT 4, 3, 'Agar semua kata sandi menjadi sama.', 0
    UNION ALL SELECT 4, 4, 'Agar aplikasi dapat meminta izin tanpa batas.', 0

    UNION ALL SELECT 5, 1, 'Memulihkan data setelah kehilangan atau kerusakan.', 1
    UNION ALL SELECT 5, 2, 'Membuat perangkat kebal terhadap semua risiko.', 0
    UNION ALL SELECT 5, 3, 'Menggantikan kebutuhan kata sandi dan MFA.', 0
    UNION ALL SELECT 5, 4, 'Membagikan file secara otomatis kepada publik.', 0
) AS seed ON seed.urutan_soal = soal.urutan
WHERE soal.kuis_id = @kuis_keamanan_siber
ON DUPLICATE KEY UPDATE
    teks = VALUES(teks),
    benar = VALUES(benar);
