# Sertifikasi Academy

## Setup dan running

1. Buat database dan tabel mengikuti urutan migrasi di [sertifikasi/database/README.md](sertifikasi/database/README.md).
2. Salin template koneksi database dari [sertifikasi/config/database.local.example.php](sertifikasi/config/database.local.example.php) ke [sertifikasi/config/database.local.php](sertifikasi/config/database.local.php) lalu isi konfigurasi lokal.
3. Buka [sertifikasi/set_up.php](sertifikasi/set_up.php) satu kali untuk membuat admin pertama. Setelah admin ada, setup tidak bisa dipakai lagi.
4. Login dari [sertifikasi/view/login.php](sertifikasi/view/login.php). Akun siswa dibuat melalui form pendaftaran publik.

## Catatan operasional

- Pemulihan password dan tautan kontak akun nonaktif memakai `ADMIN_CONTACT_EMAIL`.
- Login membatasi percobaan menjadi 3 kegagalan berturut-turut per IP dan email, lalu memblokir selama 10 detik.
- Folder `sertifikasi/temp` menyimpan status sementara pembatas percobaan login. Folder ini dibuat otomatis, perlu dapat ditulisi aplikasi, dan isinya dikecualikan dari Git.