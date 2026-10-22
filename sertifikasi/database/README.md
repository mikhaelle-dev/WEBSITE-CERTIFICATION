# Skema database

Semua tabel tetap berada di database `db_sertifikasi`. File SQL dipisahkan berdasarkan tanggung jawab dan diberi nomor sesuai urutan impor:

1. `00_database.sql` — membuat database.
2. `01_users.sql` — tabel pengguna.
3. `02_profiles.sql` — tabel profil pengguna.
4. `03_courses.sql` — tabel kursus, materi, dan progres materi setiap pengguna.
5. `04_quizzes.sql` — tabel kuis, soal, opsi, dan hasil.
6. `05_seed_demo_users.sql` — akun demo.
7. `06_seed_cybersecurity_course.sql` — kursus dan materi contoh.
8. `07_seed_cybersecurity_quiz.sql` — lima soal beserta opsi jawaban.
9. `08_enrollments.sql` — pendaftaran, progres status, dan tanggal penyelesaian kursus.
10. `09_achievements.sql` — badge milestone, pencatatan badge, dan sertifikat kelulusan.
11. `10_submateri.sql` — submateri kursus dan progres per submateri.

Jalankan file satu per satu di HeidiSQL sesuai urutan di atas. File skema dan seed dapat dijalankan kembali tanpa menggandakan data contoh.

`03_courses.sql` dapat dijalankan kembali untuk menambahkan tabel progres materi pada database yang sudah ada.
Untuk database yang sudah menjalankan file sebelumnya, jalankan `08_enrollments.sql` agar pendaftaran dan riwayat penyelesaian kursus tersimpan.
Untuk database yang sudah memiliki kursus dan pendaftaran, jalankan `09_achievements.sql` untuk menambahkan sistem badge dan sertifikat.
Untuk database yang sudah memiliki kursus dan badge, jalankan `10_submateri.sql` agar progres dihitung setelah seluruh submateri dalam modul dibuka.
