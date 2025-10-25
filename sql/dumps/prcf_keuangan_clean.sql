-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 16, 2025 at 08:28 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30
--
-- CLEAN VERSION - NO DUMMY DATA

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `prcf_keuangan`
--

-- --------------------------------------------------------

--
-- Table structure for table `buku_bank`
--

CREATE TABLE `buku_bank` (
  `id_bank` int(11) NOT NULL,
  `kode_projek` varchar(50) DEFAULT NULL,
  `nama_rek` varchar(255) DEFAULT NULL,
  `no_rek` varchar(50) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `reff` varchar(100) DEFAULT NULL,
  `activity` varchar(255) DEFAULT NULL,
  `cost_desc` text DEFAULT NULL,
  `recipient` varchar(255) DEFAULT NULL,
  `p_code` varchar(50) DEFAULT NULL,
  `exp_code` varchar(50) DEFAULT NULL,
  `nominal_code` varchar(50) DEFAULT NULL,
  `exrate` decimal(10,4) DEFAULT 1.0000,
  `cost_curr` varchar(10) DEFAULT 'IDR',
  `debit_idr` decimal(15,2) DEFAULT 0.00,
  `debit_usd` decimal(15,2) DEFAULT 0.00,
  `credit_idr` decimal(15,2) DEFAULT 0.00,
  `credit_usd` decimal(15,2) DEFAULT 0.00,
  `balance_idr` decimal(15,2) DEFAULT 0.00,
  `balance_usd` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buku_piutang_detail`
--

CREATE TABLE `buku_piutang_detail` (
  `id_detail_piutang` int(11) NOT NULL,
  `id_piutang` int(11) NOT NULL,
  `tgl_trx` date DEFAULT NULL,
  `reff` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `recipient` varchar(255) DEFAULT NULL,
  `p_code` varchar(50) DEFAULT NULL,
  `exp_code` varchar(50) DEFAULT NULL,
  `nominal_code` varchar(50) DEFAULT NULL,
  `exrate` decimal(10,4) DEFAULT 1.0000,
  `debit_idr` decimal(15,2) DEFAULT 0.00,
  `debit_usd` decimal(15,2) DEFAULT 0.00,
  `credit_idr` decimal(15,2) DEFAULT 0.00,
  `credit_usd` decimal(15,2) DEFAULT 0.00,
  `balance_idr` decimal(15,2) DEFAULT 0.00,
  `balance_usd` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buku_piutang_header`
--

CREATE TABLE `buku_piutang_header` (
  `id_piutang` int(11) NOT NULL,
  `kode_proyek` varchar(50) DEFAULT NULL,
  `account_name` varchar(255) DEFAULT NULL,
  `periode_mulai` date DEFAULT NULL,
  `periode_selesai` date DEFAULT NULL,
  `beginning_balance_idr` decimal(15,2) DEFAULT 0.00,
  `ending_balance_idr` decimal(15,2) DEFAULT 0.00,
  `beginning_balance_usd` decimal(15,2) DEFAULT 0.00,
  `ending_balance_usd` decimal(15,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `catatan_fm` text DEFAULT NULL,
  `status` enum('draft','submitted','approved','rejected') DEFAULT 'draft',
  `tgl_pembuatan` date DEFAULT NULL,
  `tgl_persetujuan` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buku_piutang_unliquidated`
--

CREATE TABLE `buku_piutang_unliquidated` (
  `id_unliquidate` int(11) NOT NULL,
  `id_piutang` int(11) NOT NULL,
  `tgl` date DEFAULT NULL,
  `voucher_no` varchar(100) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `nilai_idr` decimal(15,2) DEFAULT 0.00,
  `nilai_usd` decimal(15,2) DEFAULT 0.00,
  `status` enum('pending','liquidated','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `laporan_donor`
--

CREATE TABLE `laporan_donor` (
  `id_donor` int(11) NOT NULL,
  `periode` varchar(50) DEFAULT NULL,
  `kode_proyek` varchar(50) DEFAULT NULL,
  `realisasi_kegiatan` text DEFAULT NULL,
  `realisasi_keuangan` text DEFAULT NULL,
  `total_anggaran` decimal(15,2) DEFAULT NULL,
  `total_realisasi` decimal(15,2) DEFAULT NULL,
  `file_laporan` varchar(255) DEFAULT NULL,
  `tanggal_kirim` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `status` enum('draft','submitted','approved','sent') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `laporan_keuangan_detail`
--

CREATE TABLE `laporan_keuangan_detail` (
  `id_detail_keu` int(11) NOT NULL,
  `id_laporan_keu` int(11) NOT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `item_desc` text DEFAULT NULL,
  `recipient` varchar(255) DEFAULT NULL,
  `place_code` varchar(50) DEFAULT NULL,
  `exp_code` varchar(50) DEFAULT NULL,
  `unit_total` int(11) DEFAULT NULL,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `requested` decimal(15,2) DEFAULT NULL,
  `actual` decimal(15,2) DEFAULT NULL,
  `balance` decimal(15,2) DEFAULT NULL,
  `explanation` text DEFAULT NULL,
  `file_nota` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `laporan_keuangan_header`
--

CREATE TABLE `laporan_keuangan_header` (
  `id_laporan_keu` int(11) NOT NULL,
  `kode_projek` varchar(50) DEFAULT NULL,
  `nama_projek` varchar(255) DEFAULT NULL,
  `nama_kegiatan` varchar(255) DEFAULT NULL,
  `pelaksana` varchar(255) DEFAULT NULL,
  `tanggal_pelaksanaan` date DEFAULT NULL,
  `tanggal_laporan` date DEFAULT NULL,
  `mata_uang` varchar(10) DEFAULT 'IDR',
  `exrate` decimal(10,4) DEFAULT 1.0000,
  `created_by` int(11) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `status_lap` enum('draft','submitted','verified','approved','rejected') DEFAULT 'draft',
  `catatan_finance` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proposal`
--

CREATE TABLE `proposal` (
  `id_proposal` int(11) NOT NULL,
  `judul_proposal` varchar(255) NOT NULL,
  `pj` varchar(255) NOT NULL COMMENT 'Penanggung Jawab',
  `date` date DEFAULT NULL,
  `pemohon` varchar(255) DEFAULT NULL,
  `status` enum('draft','submitted','approved','rejected') DEFAULT 'draft',
  `kode_proyek` varchar(50) DEFAULT NULL,
  `tor` text DEFAULT NULL COMMENT 'Terms of Reference',
  `file_budget` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proyek`
--

CREATE TABLE `proyek` (
  `kode_proyek` varchar(50) NOT NULL,
  `nama_proyek` varchar(255) NOT NULL,
  `status_proyek` enum('planning','ongoing','completed','cancelled') DEFAULT 'planning',
  `donor` varchar(255) DEFAULT NULL,
  `nilai_anggaran` decimal(15,2) DEFAULT NULL,
  `periode_mulai` date DEFAULT NULL,
  `periode_selesai` date DEFAULT NULL,
  `rekening_khusus` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `role` enum('Finance Manager','Project Manager','Staff Accountant','Direktur') NOT NULL,
  `email` varchar(255) NOT NULL,
  `no_HP` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buku_bank`
--
ALTER TABLE `buku_bank`
  ADD PRIMARY KEY (`id_bank`),
  ADD KEY `kode_projek` (`kode_projek`);

--
-- Indexes for table `buku_piutang_detail`
--
ALTER TABLE `buku_piutang_detail`
  ADD PRIMARY KEY (`id_detail_piutang`),
  ADD KEY `id_piutang` (`id_piutang`);

--
-- Indexes for table `buku_piutang_header`
--
ALTER TABLE `buku_piutang_header`
  ADD PRIMARY KEY (`id_piutang`),
  ADD KEY `kode_proyek` (`kode_proyek`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `buku_piutang_unliquidated`
--
ALTER TABLE `buku_piutang_unliquidated`
  ADD PRIMARY KEY (`id_unliquidate`),
  ADD KEY `id_piutang` (`id_piutang`);

--
-- Indexes for table `laporan_donor`
--
ALTER TABLE `laporan_donor`
  ADD PRIMARY KEY (`id_donor`),
  ADD KEY `kode_proyek` (`kode_proyek`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `laporan_keuangan_detail`
--
ALTER TABLE `laporan_keuangan_detail`
  ADD PRIMARY KEY (`id_detail_keu`),
  ADD KEY `id_laporan_keu` (`id_laporan_keu`);

--
-- Indexes for table `laporan_keuangan_header`
--
ALTER TABLE `laporan_keuangan_header`
  ADD PRIMARY KEY (`id_laporan_keu`),
  ADD KEY `kode_projek` (`kode_projek`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `proposal`
--
ALTER TABLE `proposal`
  ADD PRIMARY KEY (`id_proposal`),
  ADD KEY `kode_proyek` (`kode_proyek`);

--
-- Indexes for table `proyek`
--
ALTER TABLE `proyek`
  ADD PRIMARY KEY (`kode_proyek`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buku_bank`
--
ALTER TABLE `buku_bank`
  MODIFY `id_bank` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buku_piutang_detail`
--
ALTER TABLE `buku_piutang_detail`
  MODIFY `id_detail_piutang` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buku_piutang_header`
--
ALTER TABLE `buku_piutang_header`
  MODIFY `id_piutang` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buku_piutang_unliquidated`
--
ALTER TABLE `buku_piutang_unliquidated`
  MODIFY `id_unliquidate` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `laporan_donor`
--
ALTER TABLE `laporan_donor`
  MODIFY `id_donor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `laporan_keuangan_detail`
--
ALTER TABLE `laporan_keuangan_detail`
  MODIFY `id_detail_keu` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `laporan_keuangan_header`
--
ALTER TABLE `laporan_keuangan_header`
  MODIFY `id_laporan_keu` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `proposal`
--
ALTER TABLE `proposal`
  MODIFY `id_proposal` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `buku_bank`
--
ALTER TABLE `buku_bank`
  ADD CONSTRAINT `buku_bank_ibfk_1` FOREIGN KEY (`kode_projek`) REFERENCES `proyek` (`kode_proyek`) ON DELETE SET NULL;

--
-- Constraints for table `buku_piutang_detail`
--
ALTER TABLE `buku_piutang_detail`
  ADD CONSTRAINT `buku_piutang_detail_ibfk_1` FOREIGN KEY (`id_piutang`) REFERENCES `buku_piutang_header` (`id_piutang`) ON DELETE CASCADE;

--
-- Constraints for table `buku_piutang_header`
--
ALTER TABLE `buku_piutang_header`
  ADD CONSTRAINT `buku_piutang_header_ibfk_1` FOREIGN KEY (`kode_proyek`) REFERENCES `proyek` (`kode_proyek`) ON DELETE SET NULL,
  ADD CONSTRAINT `buku_piutang_header_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `user` (`id_user`) ON DELETE SET NULL,
  ADD CONSTRAINT `buku_piutang_header_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `user` (`id_user`) ON DELETE SET NULL;

--
-- Constraints for table `buku_piutang_unliquidated`
--
ALTER TABLE `buku_piutang_unliquidated`
  ADD CONSTRAINT `buku_piutang_unliquidated_ibfk_1` FOREIGN KEY (`id_piutang`) REFERENCES `buku_piutang_header` (`id_piutang`) ON DELETE CASCADE;

--
-- Constraints for table `laporan_donor`
--
ALTER TABLE `laporan_donor`
  ADD CONSTRAINT `laporan_donor_ibfk_1` FOREIGN KEY (`kode_proyek`) REFERENCES `proyek` (`kode_proyek`) ON DELETE SET NULL,
  ADD CONSTRAINT `laporan_donor_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `user` (`id_user`) ON DELETE SET NULL,
  ADD CONSTRAINT `laporan_donor_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `user` (`id_user`) ON DELETE SET NULL;

--
-- Constraints for table `laporan_keuangan_detail`
--
ALTER TABLE `laporan_keuangan_detail`
  ADD CONSTRAINT `laporan_keuangan_detail_ibfk_1` FOREIGN KEY (`id_laporan_keu`) REFERENCES `laporan_keuangan_header` (`id_laporan_keu`) ON DELETE CASCADE;

--
-- Constraints for table `laporan_keuangan_header`
--
ALTER TABLE `laporan_keuangan_header`
  ADD CONSTRAINT `laporan_keuangan_header_ibfk_1` FOREIGN KEY (`kode_projek`) REFERENCES `proyek` (`kode_proyek`) ON DELETE SET NULL,
  ADD CONSTRAINT `laporan_keuangan_header_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `user` (`id_user`) ON DELETE SET NULL,
  ADD CONSTRAINT `laporan_keuangan_header_ibfk_3` FOREIGN KEY (`verified_by`) REFERENCES `user` (`id_user`) ON DELETE SET NULL,
  ADD CONSTRAINT `laporan_keuangan_header_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `user` (`id_user`) ON DELETE SET NULL;

--
-- Constraints for table `proposal`
--
ALTER TABLE `proposal`
  ADD CONSTRAINT `proposal_ibfk_1` FOREIGN KEY (`kode_proyek`) REFERENCES `proyek` (`kode_proyek`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

