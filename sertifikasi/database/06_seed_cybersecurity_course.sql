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

INSERT INTO kursus (slug, judul, kategori, deskripsi, level, durasi)
VALUES
    (
        'dasar-jaringan-komputer',
        'Dasar Jaringan Komputer',
        'Networking',
        'Kenali cara perangkat terhubung, bertukar data, dan menjaga jaringan tetap andal.',
        'Beginner',
        '6 jam'
    ),
    (
        'dasar-kecerdasan-buatan',
        'Dasar Kecerdasan Buatan',
        'AI & Data',
        'Pahami konsep dasar AI, cara membaca hasilnya, dan prinsip penggunaan yang bertanggung jawab.',
        'Beginner',
        '6 jam'
    )
ON DUPLICATE KEY UPDATE
    judul = VALUES(judul),
    kategori = VALUES(kategori),
    deskripsi = VALUES(deskripsi),
    level = VALUES(level),
    durasi = VALUES(durasi);

INSERT INTO materi_kursus (kursus_id, urutan, judul, isi)
SELECT id, 1, 'Dasar Jaringan dan Alamat IP',
    '<p>Jaringan komputer menghubungkan perangkat agar dapat bertukar data dan memakai layanan bersama. Setiap perangkat yang berkomunikasi memiliki alamat jaringan yang membantu data sampai ke tujuan.</p><h3>Jenis jaringan</h3><ul><li><strong>LAN:</strong> menghubungkan perangkat di area terbatas, seperti rumah atau kantor.</li><li><strong>WAN:</strong> menghubungkan jaringan pada area geografis yang lebih luas.</li></ul><h3>Alamat IP</h3><p>Alamat IP mengidentifikasi perangkat pada jaringan. Alamat dapat ditetapkan secara manual atau dibagikan oleh layanan jaringan. Pastikan perubahan pengaturan mengikuti panduan administrator jaringan.</p>'
FROM kursus WHERE slug = 'dasar-jaringan-komputer'
ON DUPLICATE KEY UPDATE judul = VALUES(judul), isi = VALUES(isi);

INSERT INTO materi_kursus (kursus_id, urutan, judul, isi)
SELECT id, 2, 'Perangkat dan Keamanan Jaringan',
    '<p>Perangkat jaringan meneruskan data dan menyediakan layanan agar komunikasi berjalan tertib. Konfigurasi yang tepat serta pembaruan rutin membantu menjaga ketersediaan dan keamanan jaringan.</p><h3>Peran perangkat</h3><ul><li><strong>Switch:</strong> menghubungkan perangkat dalam jaringan lokal.</li><li><strong>Router:</strong> meneruskan data antarjaringan.</li><li><strong>Access point:</strong> menyediakan koneksi nirkabel ke jaringan.</li></ul><h3>Kebiasaan aman</h3><p>Gunakan kata sandi Wi-Fi yang kuat, perbarui firmware dari sumber resmi, dan ubah pengaturan jaringan hanya jika berwenang. Hubungi administrator jika koneksi atau konfigurasi tidak dikenal.</p>'
FROM kursus WHERE slug = 'dasar-jaringan-komputer'
ON DUPLICATE KEY UPDATE judul = VALUES(judul), isi = VALUES(isi);

INSERT INTO materi_kursus (kursus_id, urutan, judul, isi)
SELECT id, 1, 'Mengenal Kecerdasan Buatan',
    '<p>Kecerdasan buatan (AI) adalah bidang yang mengembangkan sistem untuk melakukan tugas yang biasanya membutuhkan kemampuan manusia, seperti mengenali pola atau memahami bahasa.</p><h3>Machine learning</h3><p>Machine learning menggunakan contoh data untuk mempelajari pola. Kualitas dan keterwakilan data berpengaruh pada hasil, sehingga hasil model perlu dinilai sesuai konteks penggunaannya.</p><h3>Contoh penggunaan</h3><p>AI dapat membantu mengelompokkan dokumen, mengenali objek, atau menyarankan konten. Sistem tetap memiliki batas dan tidak menggantikan pemeriksaan manusia untuk keputusan penting.</p>'
FROM kursus WHERE slug = 'dasar-kecerdasan-buatan'
ON DUPLICATE KEY UPDATE judul = VALUES(judul), isi = VALUES(isi);

INSERT INTO materi_kursus (kursus_id, urutan, judul, isi)
SELECT id, 2, 'AI Generatif dan Penggunaan Bertanggung Jawab',
    '<p>AI generatif membuat keluaran baru seperti teks, gambar, atau ringkasan berdasarkan pola yang dipelajari dari data. Keluaran yang terdengar meyakinkan belum tentu benar.</p><h3>Periksa sebelum menggunakan</h3><ul><li>Verifikasi fakta dengan sumber tepercaya.</li><li>Tinjau konteks, bias, dan kemungkinan informasi yang tidak akurat.</li><li>Jangan memasukkan data pribadi atau rahasia ke layanan yang belum disetujui.</li></ul><p>Gunakan AI sebagai alat bantu, patuhi kebijakan organisasi, dan tetap bertanggung jawab atas keputusan akhir.</p>'
FROM kursus WHERE slug = 'dasar-kecerdasan-buatan'
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
