CREATE DATABASE IF NOT EXISTS db_konseling_kampus;
USE db_konseling_kampus;

CREATE TABLE IF NOT EXISTS pengaduan_konseling (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_tiket VARCHAR(25) NOT NULL,
    nama_mahasiswa TEXT NOT NULL,
    nim TEXT NOT NULL,
    kontak TEXT NOT NULL,
    kategori TEXT NOT NULL,
    isi_keluhan TEXT NOT NULL,
    catatan_konselor TEXT NOT NULL,
    status_penanganan TEXT NOT NULL,
    tanggal_lapor DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;