# Sertifikasi Academy

## Menyiapkan admin pertama

1. Buat database dan tabel dengan mengikuti urutan migrasi di [sertifikasi/database/README.md](sertifikasi/database/README.md).
2. Atur koneksi database lokal sesuai [sertifikasi/config/database.local.example.php](sertifikasi/config/database.local.example.php).
3. Buka `sertifikasi/set_up.php` satu kali untuk membuat admin pertama. Setelah admin ada, halaman ini menolak pembuatan akun selanjutnya.
4. Login dari `sertifikasi/view/login.php`. Akun siswa baru dibuat melalui form pendaftaran publik; admin tidak lagi membuat akun lewat dashboard.

Pemulihan password dan tautan kontak akun nonaktif memakai `ADMIN_CONTACT_EMAIL`. Setel environment variable tersebut di server agar tombol email mengarah ke admin; jika tidak disetel, halaman menampilkan petunjuk menghubungi admin lewat kanal lain.

Login membatasi percobaan menjadi tiga kegagalan berturut-turut per IP dan email, lalu mengunci selama 10 detik. Opsi “Ingat saya” mempertahankan sesi pada perangkat selama 30 hari.