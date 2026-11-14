# Sistem Penjadwalan Pertanian

Aplikasi web PHP untuk menjadwalkan seluruh kegiatan budidaya, dari penanaman sampai panen: penyiraman, pemupukan, pengairan, penyiangan, dan pengendalian hama.

Cara kerjanya: setiap tanaman punya **umur panen** dan **pola kegiatan** (misalnya "pemupukan hari ke-7", "penyiraman tiap 2 hari"). Saat Anda membuat **musim tanam** di sebuah lahan, sistem menyusun jadwal lengkapnya secara otomatis dari tanggal tanam.

## Fitur

- **Beranda**: kegiatan terlambat, hari ini, dan 7 hari ke depan, serta perjalanan setiap musim tanam.
- **Lahan**: daftar lahan dengan lokasi dan luas.
- **Tanaman**: umur panen dan pola kegiatan per tanaman. Data contoh tersedia untuk padi sawah, jagung, dan cabai merah.
- **Musim tanam**: pilih lahan, tanaman, dan tanggal tanam, lalu jadwal dibuat otomatis. Musim bisa ditandai selesai, dibatalkan, atau dihapus.
- **Jadwal**: saring kegiatan berdasarkan rentang waktu, status, lahan, dan jenis; tandai selesai, pindahkan tanggal, hapus, atau tambah kegiatan manual.
- **Kalender**: tampilan bulanan dengan warna per jenis kegiatan.
- **Akun**: tambah pengguna dan ganti password. Semua pengguna punya akses yang sama.

## Teknologi

PHP 8.1+ tanpa framework, MySQL/MariaDB lewat PDO, HTML dan CSS biasa.
Keamanan dasar: password di-hash (`password_hash`), semua query memakai prepared statement, setiap formulir dilindungi token CSRF, dan semua keluaran di-escape.

## Menjalankan di XAMPP

1. Salin folder `pertanian` ke `C:\xampp\htdocs\` (Windows) atau `/opt/lampp/htdocs/` (Linux).
2. Buka XAMPP Control Panel, jalankan **Apache** dan **MySQL**.
3. Buka `http://localhost/phpmyadmin`, pilih tab **Import**, pilih file `database.sql`, lalu klik **Import**. Database `pertanian` beserta data contoh tanaman akan dibuat.
4. Jika MySQL Anda memakai password, ubah `DB_USER` dan `DB_PASS` di `config.php`.
5. Buka `http://localhost/pertanian`. Pada kunjungan pertama, Anda diminta membuat akun pertama.

## Alur pemakaian

1. Tambahkan lahan di menu **Lahan**.
2. Periksa pola kegiatan di menu **Tanaman** dan sesuaikan harinya.
3. Buat musim tanam di menu **Musim Tanam**.
4. Pantau dan centang kegiatan dari **Beranda**, **Jadwal**, atau **Kalender**.

> Angka hari pada data contoh tanaman hanya untuk memulai. Sesuaikan dengan anjuran penyuluh pertanian dan kondisi lahan Anda.

## Struktur folder

```
pertanian/
├── config.php            pengaturan database
├── database.sql          skema dan data contoh
├── index.php             beranda
├── jadwal.php            daftar dan saringan kegiatan
├── kalender.php          kalender bulanan
├── musim.php             daftar dan pembuatan musim tanam
├── musim_detail.php      jadwal satu musim tanam
├── lahan.php             kelola lahan
├── tanaman.php           kelola tanaman
├── tanaman_detail.php    pola kegiatan per tanaman
├── tugas_aksi.php        aksi kegiatan (selesai, pindah, hapus, tambah)
├── akun.php              pengguna dan password
├── login.php / logout.php
├── assets/style.css
└── includes/
    ├── bootstrap.php     dimuat di awal setiap halaman
    ├── db.php            koneksi PDO
    ├── functions.php     fungsi bantu (keamanan, tanggal)
    ├── jadwal.php        logika pembuatan jadwal otomatis
    └── layout.php        kerangka halaman dan komponen tampilan
```
