# Skema database

Semua tabel tetap berada di database `db_sertifikasi`. File SQL dipisahkan berdasarkan tanggung jawab dan diberi nomor sesuai urutan impor.

1. `00_database.sql` — membuat database.
2. `01_users.sql` — tabel pengguna dan role.
3. `02_profiles.sql` — tabel profil pengguna.
4. `03_courses.sql` — tabel kursus, materi, dan progres materi per pengguna.
5. `04_quizzes.sql` — tabel kuis, soal, opsi, dan hasil kuis kursus umum.
6. `05_seed_demo_users.sql` — akun demo.
7. `06_seed_cybersecurity_course.sql` — kursus dan materi contoh.
8. `07_seed_cybersecurity_quiz.sql` — soal dan opsi jawaban contoh.
9. `08_enrollments.sql` — pendaftaran kursus, progres, dan tanggal penyelesaian.
10. `09_achievements.sql` — badge milestone, pencatatan badge, dan sertifikat kelulusan.
11. `10_submateri.sql` — submateri kursus dan progres per submateri.
12. `11_account_trash.sql` — status sampah akun untuk pemulihan dan penghapusan terjadwal setelah 5 menit.
13. `12_account_trash_scheduler.sql` — MySQL Event untuk menghapus akun kedaluwarsa tanpa menunggu dashboard admin dibuka.
14. `13_feedback.sql` — rating, saran, laporan bug, dan permintaan fitur dari pengguna.
15. `13_password_reset_requests.sql` — permintaan reset password dan tanda pengguna wajib mengganti password setelah login.
16. `14_classroom.sql` — skema kelas instruktur, modul, materi, kuis, enroll key, dan peserta kelas.

Jalankan file satu per satu di HeidiSQL sesuai urutan di atas. File skema dan seed dapat dijalankan kembali tanpa menggandakan data contoh.

Catatan migrasi:
- `03_courses.sql` dapat dijalankan kembali untuk menambahkan tabel progres materi pada database yang sudah ada.
- Untuk database yang sudah menjalankan file sebelumnya, jalankan `08_enrollments.sql` agar pendaftaran dan riwayat penyelesaian kursus tersimpan.
- Untuk database yang sudah memiliki kursus dan pendaftaran, jalankan `09_achievements.sql` untuk menambahkan sistem badge dan sertifikat.
- Untuk database yang sudah memiliki kursus dan badge, jalankan `10_submateri.sql` agar progres dihitung setelah seluruh submateri dalam modul dibuka.
- Jalankan `11_account_trash.sql` setelah `10_submateri.sql` untuk mengaktifkan fitur tempat sampah akun. Jalankan satu kali pada database yang sudah ada.
- Jalankan `12_account_trash_scheduler.sql` setelah `11_account_trash.sql` agar MySQL menghapus akun sampah kedaluwarsa tanpa bergantung pada dashboard admin.
- `14_classroom.sql` menyiapkan struktur kelas instruktur, modul, materi, kuis, dan enroll key agar fitur dashboard kelas dapat dikembangkan bertahap.

Admin dapat memindahkan akun ke sampah tanpa mengetik konfirmasi; akun dinonaktifkan dan dapat dipulihkan selama lima menit. Waktu dihitung berdasarkan timestamp database sehingga tetap berjalan saat dashboard ditutup. Jika pemilik mencoba login dalam masa tersebut dengan password yang benar, halaman login menampilkan sisa waktu dan meminta mereka segera menghubungi admin.

Dashboard admin dan fitur ganti/reset password menggunakan tabel `user` yang dibuat oleh `01_users.sql`. Buat admin pertama melalui `sertifikasi/set_up.php`; setelah admin pertama tersimpan, setup tidak dapat dipakai kembali. Admin tidak membuat akun melalui dashboard. Akun siswa tetap dibuat melalui form pendaftaran.

Konfigurasi koneksi dapat disesuaikan tanpa menyimpan kredensial di Git: salin `../config/database.local.example.php` menjadi `../config/database.local.php`, lalu isi host, port, nama database, username, dan password. File lokal tersebut diabaikan oleh Git. Agar perubahan akun terlihat dari laptop berbeda, aplikasi yang diakses semua orang harus tersambung ke database hosting yang sama; sekadar `git pull` pada aplikasi yang masih memakai MySQL lokal tidak menyinkronkan data.
