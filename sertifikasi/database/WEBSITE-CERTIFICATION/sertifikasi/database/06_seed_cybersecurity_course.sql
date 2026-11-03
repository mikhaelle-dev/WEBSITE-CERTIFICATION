USE db_sertifikasi;

INSERT INTO kursus (slug, judul, kategori, deskripsi, level, durasi)
VALUES (
    'pengenalan-keamanan-siber',
    'Pengenalan Keamanan Siber',
    'Cybersecurity',
    'Pelajari dasar menjaga akun, perangkat, dan informasi pribadi dari risiko digital sehari-hari.',
    'Beginner',
    '8 jam'
)
ON DUPLICATE KEY UPDATE
    judul = VALUES(judul),
    kategori = VALUES(kategori),
    deskripsi = VALUES(deskripsi),
    level = VALUES(level),
    durasi = VALUES(durasi);

INSERT INTO materi_kursus (kursus_id, urutan, judul, isi)
SELECT id, 1, 'Dasar Keamanan Siber',
'<p>Keamanan siber adalah upaya melindungi perangkat, akun, jaringan, dan informasi dari akses atau perubahan yang tidak diinginkan.</p><h3>Tiga tujuan utama</h3><ul><li><strong>Kerahasiaan:</strong> informasi hanya dapat dilihat oleh pihak yang berhak.</li><li><strong>Integritas:</strong> informasi tetap akurat dan tidak diubah tanpa izin.</li><li><strong>Ketersediaan:</strong> sistem dan informasi dapat digunakan saat dibutuhkan.</li></ul><h3>Contoh sehari-hari</h3><p>Mengunci layar saat meninggalkan komputer membantu menjaga kerahasiaan. Menyimpan salinan cadangan membantu pemulihan data. Memasang pembaruan resmi membantu menjaga perangkat tetap aman.</p><div class="alert alert-info"><strong>Ingat:</strong> keamanan adalah kebiasaan berkelanjutan, bukan hanya memasang satu aplikasi.</div>'
FROM kursus WHERE slug = 'pengenalan-keamanan-siber'
ON DUPLICATE KEY UPDATE judul = VALUES(judul), isi = VALUES(isi);

INSERT INTO materi_kursus (kursus_id, urutan, judul, isi)
SELECT id, 2, 'Lindungi Akun dan Kenali Phishing',
'<p>Akun digital sering menjadi pintu masuk ke informasi pribadi. Gunakan kata sandi yang panjang, unik untuk setiap layanan, dan jangan membagikan kode verifikasi kepada siapa pun.</p><h3>Perkuat akun</h3><ul><li>Gunakan pengelola kata sandi tepercaya untuk membuat dan menyimpan kata sandi unik.</li><li>Aktifkan autentikasi multifaktor (MFA), terutama pada email dan akun penting.</li><li>Simpan kode pemulihan di tempat yang aman dan terpisah dari perangkat utama.</li></ul><h3>Waspadai phishing</h3><p>Phishing adalah upaya menipu seseorang agar memberikan informasi atau melakukan tindakan tertentu. Periksa alamat pengirim, alamat situs, konteks pesan, dan permintaan yang tidak biasa. Jangan membuka lampiran atau tautan yang tidak diharapkan. Hubungi organisasi melalui kanal resmi jika ragu.</p><div class="alert alert-warning"><strong>Berhenti sejenak:</strong> pesan mendesak yang meminta kata sandi atau kode OTP adalah alasan untuk memeriksa kebenarannya, bukan untuk segera membalas.</div>'
FROM kursus WHERE slug = 'pengenalan-keamanan-siber'
ON DUPLICATE KEY UPDATE judul = VALUES(judul), isi = VALUES(isi);

INSERT INTO materi_kursus (kursus_id, urutan, judul, isi)
SELECT id, 3, 'Amankan Perangkat dan Data',
'<p>Perangkat yang diperbarui dan kebiasaan pencadangan yang baik mengurangi dampak masalah keamanan.</p><h3>Kebiasaan perlindungan</h3><ul><li>Pasang pembaruan sistem operasi dan aplikasi dari sumber resmi.</li><li>Gunakan kunci layar dan enkripsi perangkat bila tersedia.</li><li>Unduh aplikasi hanya dari toko atau situs resmi, dan tinjau izin yang diminta.</li><li>Cadangkan data penting secara berkala dan pastikan salinan cadangan dapat dipulihkan.</li><li>Hindari memasukkan informasi sensitif melalui Wi-Fi publik yang tidak tepercaya; gunakan koneksi aman yang disediakan organisasi bila tersedia.</li></ul><h3>Jika terjadi insiden</h3><p>Segera beri tahu tim dukungan atau admin yang berwenang. Ganti kata sandi dari perangkat yang aman, cabut sesi yang tidak dikenal, dan ikuti prosedur pemulihan resmi. Jangan menghapus bukti atau mencoba menyelidiki sistem yang bukan milikmu.</p><p><strong>Ringkasan:</strong> gunakan kata sandi unik dan MFA, verifikasi pesan mencurigakan, perbarui perangkat, serta siapkan cadangan.</p>'
FROM kursus WHERE slug = 'pengenalan-keamanan-siber'
ON DUPLICATE KEY UPDATE judul = VALUES(judul), isi = VALUES(isi);
