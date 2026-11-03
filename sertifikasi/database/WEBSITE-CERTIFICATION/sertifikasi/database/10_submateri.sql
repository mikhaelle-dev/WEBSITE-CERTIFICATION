USE db_sertifikasi;

CREATE TABLE IF NOT EXISTS submateri_kursus (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    materi_id INT NOT NULL,
    urutan    SMALLINT UNSIGNED NOT NULL,
    judul     VARCHAR(150) NOT NULL,
    isi       LONGTEXT NOT NULL,
    UNIQUE KEY uq_submateri_kursus_urutan (materi_id, urutan),
    CONSTRAINT fk_submateri_kursus_materi
        FOREIGN KEY (materi_id) REFERENCES materi_kursus(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS progres_submateri (
    user_id       INT NOT NULL,
    submateri_id  INT NOT NULL,
    pertama_dibuka TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, submateri_id),
    CONSTRAINT fk_progres_submateri_user
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    CONSTRAINT fk_progres_submateri_submateri
        FOREIGN KEY (submateri_id) REFERENCES submateri_kursus(id) ON DELETE CASCADE
);

INSERT INTO submateri_kursus (materi_id, urutan, judul, isi)
SELECT materi.id, seed.urutan, seed.judul, seed.isi
FROM materi_kursus materi
JOIN kursus ON kursus.id = materi.kursus_id
JOIN (
    SELECT 1 AS urutan_materi, 1 AS urutan, 'Pengantar Keamanan Siber' AS judul,
           '<p>Keamanan siber adalah upaya melindungi perangkat, akun, jaringan, dan informasi dari akses atau perubahan yang tidak diinginkan.</p>' AS isi
    UNION ALL SELECT 1, 2, 'Tiga tujuan utama',
           '<ul><li><strong>Kerahasiaan:</strong> informasi hanya dapat dilihat oleh pihak yang berhak.</li><li><strong>Integritas:</strong> informasi tetap akurat dan tidak diubah tanpa izin.</li><li><strong>Ketersediaan:</strong> sistem dan informasi dapat digunakan saat dibutuhkan.</li></ul>'
    UNION ALL SELECT 1, 3, 'Contoh sehari-hari',
           '<p>Mengunci layar saat meninggalkan komputer membantu menjaga kerahasiaan. Menyimpan salinan cadangan membantu pemulihan data. Memasang pembaruan resmi membantu menjaga perangkat tetap aman.</p><div class="alert alert-info"><strong>Ingat:</strong> keamanan adalah kebiasaan berkelanjutan, bukan hanya memasang satu aplikasi.</div>'

    UNION ALL SELECT 2, 1, 'Pengantar Perlindungan Akun',
           '<p>Akun digital sering menjadi pintu masuk ke informasi pribadi. Gunakan kata sandi yang panjang, unik untuk setiap layanan, dan jangan membagikan kode verifikasi kepada siapa pun.</p>'
    UNION ALL SELECT 2, 2, 'Perkuat akun',
           '<ul><li>Gunakan pengelola kata sandi tepercaya untuk membuat dan menyimpan kata sandi unik.</li><li>Aktifkan autentikasi multifaktor (MFA), terutama pada email dan akun penting.</li><li>Simpan kode pemulihan di tempat yang aman dan terpisah dari perangkat utama.</li></ul>'
    UNION ALL SELECT 2, 3, 'Waspadai phishing',
           '<p>Phishing adalah upaya menipu seseorang agar memberikan informasi atau melakukan tindakan tertentu. Periksa alamat pengirim, alamat situs, konteks pesan, dan permintaan yang tidak biasa. Jangan membuka lampiran atau tautan yang tidak diharapkan. Hubungi organisasi melalui kanal resmi jika ragu.</p><div class="alert alert-warning"><strong>Berhenti sejenak:</strong> pesan mendesak yang meminta kata sandi atau kode OTP adalah alasan untuk memeriksa kebenarannya, bukan untuk segera membalas.</div>'

    UNION ALL SELECT 3, 1, 'Pengantar Perlindungan Perangkat dan Data',
           '<p>Perangkat yang diperbarui dan kebiasaan pencadangan yang baik mengurangi dampak masalah keamanan.</p>'
    UNION ALL SELECT 3, 2, 'Kebiasaan perlindungan',
           '<ul><li>Pasang pembaruan sistem operasi dan aplikasi dari sumber resmi.</li><li>Gunakan kunci layar dan enkripsi perangkat bila tersedia.</li><li>Unduh aplikasi hanya dari toko atau situs resmi, dan tinjau izin yang diminta.</li><li>Cadangkan data penting secara berkala dan pastikan salinan cadangan dapat dipulihkan.</li><li>Hindari memasukkan informasi sensitif melalui Wi-Fi publik yang tidak tepercaya; gunakan koneksi aman yang disediakan organisasi bila tersedia.</li></ul>'
    UNION ALL SELECT 3, 3, 'Jika terjadi insiden',
           '<p>Segera beri tahu tim dukungan atau admin yang berwenang. Ganti kata sandi dari perangkat yang aman, cabut sesi yang tidak dikenal, dan ikuti prosedur pemulihan resmi. Jangan menghapus bukti atau mencoba menyelidiki sistem yang bukan milikmu.</p><p><strong>Ringkasan:</strong> gunakan kata sandi unik dan MFA, verifikasi pesan mencurigakan, perbarui perangkat, serta siapkan cadangan.</p>'
) AS seed ON seed.urutan_materi = materi.urutan
WHERE kursus.slug = 'pengenalan-keamanan-siber'
ON DUPLICATE KEY UPDATE judul = VALUES(judul), isi = VALUES(isi);

INSERT IGNORE INTO progres_submateri (user_id, submateri_id, pertama_dibuka)
SELECT progres.user_id, submateri.id, progres.pertama_dibuka
FROM progres_materi progres
JOIN submateri_kursus submateri ON submateri.materi_id = progres.materi_id;
