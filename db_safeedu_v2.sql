-- phpMyAdmin SQL Dump
-- Database: `db_safeedu_v2`

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

-- 1. Tabel users
CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teknisi','siswa') NOT NULL DEFAULT 'siswa',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel kategori
CREATE TABLE `kategori` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `jenis` enum('fasilitas','etik') NOT NULL,
  `nama` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabel fasilitas
CREATE TABLE `fasilitas` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama` varchar(150) NOT NULL,
  `lokasi` varchar(150) NOT NULL,
  `kondisi` enum('Bisa Digunakan','Dalam Perbaikan','Tidak Bisa Digunakan') NOT NULL DEFAULT 'Bisa Digunakan',
  `deskripsi` text NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabel pengaduan
CREATE TABLE `pengaduan` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `kategori_id` bigint UNSIGNED NOT NULL,
  `fasilitas_id` bigint UNSIGNED NULL,
  `judul` varchar(150) NOT NULL,
  `deskripsi` text NOT NULL,
  `lokasi_kejadian` varchar(150) NULL,
  `status` enum('menunggu','diproses','selesai','ditolak') NOT NULL DEFAULT 'menunggu',
  `is_anonim` boolean NOT NULL DEFAULT false,
  `foto_bukti` varchar(255) NULL,
  `rating_kepuasan` tinyint NULL, -- Bintang 1 sampai 5
  `ulasan_pelapor` text NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`kategori_id`) REFERENCES `kategori`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`fasilitas_id`) REFERENCES `fasilitas`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabel penugasan
CREATE TABLE `penugasan` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `pengaduan_id` bigint UNSIGNED NOT NULL,
  `teknisi_id` bigint UNSIGNED NOT NULL,
  `admin_id` bigint UNSIGNED NOT NULL,
  `catatan_admin` text NULL,
  `status` enum('ditugaskan','diterima','selesai') NOT NULL DEFAULT 'ditugaskan',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`pengaduan_id`) REFERENCES `pengaduan`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`teknisi_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabel log_pengerjaan
CREATE TABLE `log_pengerjaan` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `penugasan_id` bigint UNSIGNED NOT NULL,
  `catatan_teknisi` text NOT NULL,
  `foto_hasil` varchar(255) NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMES"TAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMES"TAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`penugasan_id`) REFERENCES `penugasan`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabel notifikasi
CREATE TABLE `notifikasi` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `judul` varchar(100) NOT NULL,
  `pesan` text NOT NULL,
  `is_read` boolean NOT NULL DEFAULT false,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- INSERT DUMMY DATA (SIAP PAKAI)
-- --------------------------------------------------------

-- Password default untuk semua dummy adalah: password123 (hashed menggunakan bcrypt Laravel)
-- Password default untuk semua dummy adalah: password

INSERT INTO `users` (`nama`, `email`, `password`, `role`) VALUES
('Sistem Admin', 'admin@safeedu.com', '$2y$12$zX8jR0dFLl/Gff4sRXhqC.cdmj01vDBys9p7ZvXF1LNrFk8DBckAK', 'admin'),
('Bambang Teknisi', 'teknisi@safeedu.com', '$2y$12$zX8jR0dFLl/Gff4sRXhqC.cdmj01vDBys9p7ZvXF1LNrFk8DBckAK', 'teknisi'),
('Budi Siswa', 'siswa@safeedu.com', '$2y$12$zX8jR0dFLl/Gff4sRXhqC.cdmj01vDBys9p7ZvXF1LNrFk8DBckAK', 'siswa');

INSERT INTO `kategori` (`jenis`, `nama`) VALUES
('fasilitas', 'Pendingin Ruangan (AC)'),
('fasilitas', 'Kelistrikan & Lampu'),
('fasilitas', 'Sanitasi & Perpipaan'),
('etik', 'Bullying / Perundungan'),
('etik', 'Vandalisme');

INSERT INTO `fasilitas` (`nama`, `lokasi`, `kondisi`, `deskripsi`) VALUES
('AC Lab Komputer 2', 'Gedung B, Lantai 3', 'Bisa Digunakan', 'Service rutin terakhir bulan lalu'),
('Proyektor Ruang 104', 'Gedung C, Lantai 1', 'Bisa Digunakan', 'Resolusi 1080p'),
('Wastafel Toilet Pria', 'Gedung A, Lantai 1', 'Dalam Perbaikan', 'Kran patah menunggu suku cadang');

INSERT INTO kategori (jenis, nama, created_at, updated_at)
SELECT 'fasilitas', 'Kerusakan Fasilitas', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM kategori WHERE jenis = 'fasilitas'
);

INSERT INTO kategori (jenis, nama, created_at, updated_at)
SELECT 'etik', 'Pelanggaran Kode Etik', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM kategori WHERE jenis = 'etik'
);

COMMIT;
