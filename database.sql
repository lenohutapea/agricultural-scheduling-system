-- Sistem Penjadwalan Pertanian
-- Impor file ini lewat phpMyAdmin (tab Import) atau:  mysql -u root < database.sql

CREATE DATABASE IF NOT EXISTS pertanian CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pertanian;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  username VARCHAR(30) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE lahan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  lokasi VARCHAR(150) NULL,
  luas_m2 DECIMAL(12,2) NULL,
  catatan TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE tanaman (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL UNIQUE,
  umur_panen_hari INT NOT NULL,
  catatan TEXT NULL
) ENGINE=InnoDB;

-- Pola kegiatan per tanaman. Hari dihitung dari tanggal tanam (HST = hari setelah tanam).
-- ulangi_tiap_hari = 0 berarti kegiatan dilakukan sekali pada hari_mulai.
CREATE TABLE template_kegiatan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tanaman_id INT NOT NULL,
  jenis VARCHAR(30) NOT NULL,
  judul VARCHAR(150) NOT NULL,
  hari_mulai INT NOT NULL DEFAULT 0,
  ulangi_tiap_hari INT NOT NULL DEFAULT 0,
  hari_selesai INT NULL,
  keterangan VARCHAR(255) NULL,
  FOREIGN KEY (tanaman_id) REFERENCES tanaman(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE musim_tanam (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lahan_id INT NOT NULL,
  tanaman_id INT NOT NULL,
  tanggal_tanam DATE NOT NULL,
  perkiraan_panen DATE NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'Berjalan',
  catatan TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (lahan_id) REFERENCES lahan(id) ON DELETE CASCADE,
  FOREIGN KEY (tanaman_id) REFERENCES tanaman(id)
) ENGINE=InnoDB;

CREATE TABLE jadwal (
  id INT AUTO_INCREMENT PRIMARY KEY,
  musim_id INT NULL,
  lahan_id INT NOT NULL,
  jenis VARCHAR(30) NOT NULL,
  judul VARCHAR(150) NOT NULL,
  tanggal DATE NOT NULL,
  hari_ke INT NULL,
  keterangan VARCHAR(255) NULL,
  selesai TINYINT(1) NOT NULL DEFAULT 0,
  selesai_pada DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (musim_id) REFERENCES musim_tanam(id) ON DELETE CASCADE,
  FOREIGN KEY (lahan_id) REFERENCES lahan(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_jadwal_tanggal ON jadwal (tanggal, selesai);

-- ---------------------------------------------------------------
-- DATA CONTOH. Angka hari di bawah hanya contoh untuk memulai;
-- sesuaikan dengan anjuran penyuluh pertanian dan kondisi lahan Anda.
-- ---------------------------------------------------------------
INSERT INTO tanaman (id, nama, umur_panen_hari, catatan) VALUES
(1, 'Padi sawah', 115, 'Contoh pola untuk padi sawah irigasi.'),
(2, 'Jagung', 100, 'Contoh pola untuk jagung pipil.'),
(3, 'Cabai merah', 120, 'Contoh pola untuk cabai merah dengan mulsa.');

INSERT INTO template_kegiatan (tanaman_id, jenis, judul, hari_mulai, ulangi_tiap_hari, hari_selesai, keterangan) VALUES
(1, 'Pengairan', 'Periksa dan atur tinggi air', 1, 7, 100, 'Keringkan lahan menjelang panen'),
(1, 'Pemupukan', 'Pemupukan pertama', 7, 0, NULL, 'Pupuk dasar'),
(1, 'Pemupukan', 'Pemupukan kedua', 25, 0, NULL, 'Pupuk susulan'),
(1, 'Pemupukan', 'Pemupukan ketiga', 45, 0, NULL, 'Pupuk susulan'),
(1, 'Penyiangan', 'Penyiangan gulma', 21, 21, 42, NULL),
(1, 'Pengendalian Hama', 'Pengamatan hama dan penyakit', 14, 14, 98, NULL),

(2, 'Penyiraman', 'Penyiraman', 1, 4, 90, 'Lewati jika sudah hujan'),
(2, 'Pemupukan', 'Pemupukan pertama', 7, 0, NULL, NULL),
(2, 'Pemupukan', 'Pemupukan kedua', 30, 0, NULL, NULL),
(2, 'Pemupukan', 'Pemupukan ketiga', 45, 0, NULL, NULL),
(2, 'Penyiangan', 'Penyiangan dan pembumbunan', 15, 15, 30, NULL),
(2, 'Pengendalian Hama', 'Pengamatan hama dan penyakit', 14, 14, 84, NULL),

(3, 'Penyiraman', 'Penyiraman', 1, 2, 110, 'Lewati jika sudah hujan'),
(3, 'Pemupukan', 'Pemupukan susulan', 14, 14, 98, NULL),
(3, 'Penyiangan', 'Penyiangan gulma', 21, 21, 63, NULL),
(3, 'Pengendalian Hama', 'Pengamatan hama dan penyakit', 7, 7, 112, NULL);
