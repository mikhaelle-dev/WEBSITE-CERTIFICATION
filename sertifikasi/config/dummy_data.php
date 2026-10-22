<?php
/**
 * DATA DUMMY untuk demo tampilan.
 * Nanti tiap bagian diganti query ke database:
 *   kategori    -> tabel kategori
 *   kursus      -> class Course
 *   kursus_saya -> class Enrollment (progress < 100)
 *   pencapaian  -> tabel sertifikat (jenis: Sertifikat / Lencana / Pencapaian)
 *   riwayat     -> class Enrollment (semua kursus yang pernah diikuti)
 *   profil      -> tabel user + profil
 */
return [
    'kategori' => ['Networking', 'Cybersecurity', 'Programming', 'Cloud', 'Database'],

    'kursus' => [
        ['id' => 1, 'kategori' => 'Networking',    'judul' => 'Dasar Jaringan Komputer',       'level' => 'Beginner',     'tipe' => 'Self-paced', 'durasi' => '12 jam', 'sertifikat' => true, 'peserta' => 3812],
        ['id' => 2, 'kategori' => 'Networking',    'judul' => 'Routing & Switching CCNA',      'level' => 'Intermediate', 'tipe' => 'Self-paced', 'durasi' => '30 jam', 'sertifikat' => true, 'peserta' => 1204],
        ['id' => 3, 'kategori' => 'Cybersecurity', 'judul' => 'Pengenalan Keamanan Siber',     'level' => 'Beginner',     'tipe' => 'Self-paced', 'durasi' => '8 jam',  'sertifikat' => true, 'peserta' => 10468, 'slug' => 'pengenalan-keamanan-siber'],
        ['id' => 4, 'kategori' => 'Programming',   'judul' => 'Pemrograman Web dengan PHP',    'level' => 'Beginner',     'tipe' => 'Self-paced', 'durasi' => '20 jam', 'sertifikat' => true, 'peserta' => 2290],
        ['id' => 5, 'kategori' => 'Cloud',         'judul' => 'Dasar Cloud Computing',         'level' => 'Beginner',     'tipe' => 'Self-paced', 'durasi' => '10 jam', 'sertifikat' => true, 'peserta' => 1090],
        ['id' => 6, 'kategori' => 'Database',      'judul' => 'Administrasi Basis Data MySQL', 'level' => 'Intermediate', 'tipe' => 'Self-paced', 'durasi' => '16 jam', 'sertifikat' => true, 'peserta' => 870],
    ],

    'kursus_saya' => [],

    // Akun baru belum punya apa-apa -> kosong. Nanti dari tabel sertifikat.
    'pencapaian' => [],

    // Contoh data terisi, buat ngetes tampilan. Ganti nama key jadi 'pencapaian' kalau mau lihat.
    'pencapaian_contoh' => [
        ['jenis' => 'Sertifikat',  'judul' => 'Pengenalan Keamanan Siber',    'tanggal' => '2026-08-13'],
        ['jenis' => 'Lencana',     'judul' => 'Pengenalan Keamanan Siber',    'tanggal' => '2026-08-13'],
        ['jenis' => 'Pencapaian',  'judul' => 'Security Alerts',              'tanggal' => '2026-08-06'],
        ['jenis' => 'Sertifikat',  'judul' => 'Dasar Jaringan Komputer',      'tanggal' => '2026-05-04'],
        ['jenis' => 'Lencana',     'judul' => 'Dasar Jaringan Komputer',      'tanggal' => '2026-05-04'],
        ['jenis' => 'Pencapaian',  'judul' => 'Network Protocols Basics',     'tanggal' => '2026-05-04'],
    ],

    // Akun baru belum pernah belajar -> kosong. Nanti dari tabel enrollment.
    'riwayat' => [],

    // Contoh data terisi, buat ngetes tampilan. Ganti nama key jadi 'riwayat' kalau mau lihat.
    'riwayat_contoh' => [
        ['judul' => 'Pengenalan Keamanan Siber',   'tipe' => 'Self-paced', 'mulai' => '2026-04-25', 'selesai' => '2026-08-13', 'status' => 'Selesai'],
        ['judul' => 'Dasar Jaringan Komputer',     'tipe' => 'Self-paced', 'mulai' => '2026-04-18', 'selesai' => '2026-05-04', 'status' => 'Selesai'],
        ['judul' => 'Pemrograman Web dengan PHP',  'tipe' => 'Self-paced', 'mulai' => '2026-09-01', 'selesai' => null,         'status' => 'Berjalan'],
    ],

    'profil' => [
        'nama_depan'    => 'Mikhael',
        'nama_belakang' => '',
        'email'         => 'mikhael@example.com',
        'bahasa'        => 'Indonesia',
        'negara'        => 'Indonesia',
        'provinsi'      => 'Jawa Tengah',
        'tujuan_karier' => '',
    ],
];
