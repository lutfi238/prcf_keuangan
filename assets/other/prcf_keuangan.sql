-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2025 at 04:55 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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
-- Table structure for table `buku_bank_detail`
--

CREATE TABLE `buku_bank_detail` (
  `id_detail_bank` varchar(30) NOT NULL,
  `id_bank_header` varchar(30) NOT NULL,
  `tanggal` date NOT NULL,
  `reff` varchar(50) DEFAULT NULL,
  `title_activity` varchar(150) DEFAULT NULL,
  `cost_description` text DEFAULT NULL,
  `recipient` varchar(100) DEFAULT NULL,
  `place_code` varchar(20) DEFAULT NULL,
  `exp_code` varchar(20) DEFAULT NULL,
  `nominal_code` varchar(20) DEFAULT NULL,
  `exrate` decimal(12,2) DEFAULT NULL,
  `cost_curr` varchar(10) DEFAULT NULL,
  `debit_idr` decimal(18,2) DEFAULT 0.00,
  `debit_usd` decimal(18,2) DEFAULT 0.00,
  `credit_idr` decimal(18,2) DEFAULT 0.00,
  `credit_usd` decimal(18,2) DEFAULT 0.00,
  `balance_idr` decimal(18,2) DEFAULT 0.00,
  `balance_usd` decimal(18,2) DEFAULT 0.00,
  `status` enum('ongoing','final') DEFAULT 'ongoing'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buku_bank_detail`
--

INSERT INTO `buku_bank_detail` (`id_detail_bank`, `id_bank_header`, `tanggal`, `reff`, `title_activity`, `cost_description`, `recipient`, `place_code`, `exp_code`, `nominal_code`, `exrate`, `cost_curr`, `debit_idr`, `debit_usd`, `credit_idr`, `credit_usd`, `balance_idr`, `balance_usd`, `status`) VALUES
('BD-20251020-074455-a668', 'BH-20251020-073558-25fb', '2025-01-01', 'BP01-24-07-01', 'Advance Staff', 'Advance - Forest patrol and monitoring, July 2024 (Yadi Purwanto)', 'Yadi Purwanto', '-', '-', 'Adv', 16116.00, '0', 0.00, 0.00, 52240000.00, 3241.50, 2447760000.00, -3241.50, 'ongoing');

-- --------------------------------------------------------

--
-- Table structure for table `buku_bank_header`
--

CREATE TABLE `buku_bank_header` (
  `id_bank_header` varchar(30) NOT NULL,
  `kode_proyek` varchar(20) NOT NULL,
  `account_name` varchar(150) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `exrate` decimal(12,2) DEFAULT 1.00,
  `currency` varchar(10) NOT NULL,
  `periode_bulan` char(2) NOT NULL,
  `periode_tahun` char(4) NOT NULL,
  `saldo_awal_idr` decimal(18,2) DEFAULT 0.00,
  `saldo_awal_usd` decimal(18,2) DEFAULT 0.00,
  `current_period_change_idr` decimal(18,2) DEFAULT 0.00,
  `current_period_change_usd` decimal(18,2) DEFAULT 0.00,
  `saldo_akhir_idr` decimal(18,2) DEFAULT 0.00,
  `saldo_akhir_usd` decimal(18,2) DEFAULT 0.00,
  `prepared_by` varchar(100) DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `status_laporan` enum('draft','submitted','approved') DEFAULT 'draft',
  `tanggal_pembuatan` date DEFAULT curdate(),
  `tanggal_persetujuan` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buku_bank_header`
--

INSERT INTO `buku_bank_header` (`id_bank_header`, `kode_proyek`, `account_name`, `bank_name`, `account_number`, `currency`, `periode_bulan`, `periode_tahun`, `saldo_awal_idr`, `saldo_awal_usd`, `current_period_change_idr`, `current_period_change_usd`, `saldo_akhir_idr`, `saldo_akhir_usd`, `prepared_by`, `approved_by`, `status_laporan`, `tanggal_pembuatan`, `tanggal_persetujuan`) VALUES
('BH-20251020-073558-25fb', 'PRJ-2025-001', 'Aam', 'Bank M', '146 1231 123123', 'IDR', '01', '2025', 2500000000.00, 0.00, -52240000.00, -3241.50, 2447760000.00, -3241.50, 'Ferrosi Pratama', NULL, 'draft', '2025-10-20', NULL);

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
  `periode_bulan` char(2) NOT NULL,
  `periode_tahun` char(4) NOT NULL,
  `exrate` decimal(12,2) DEFAULT 1.00,
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

--
-- Dumping data for table `laporan_keuangan_detail`
--

INSERT INTO `laporan_keuangan_detail` (`id_detail_keu`, `id_laporan_keu`, `invoice_no`, `invoice_date`, `item_desc`, `recipient`, `place_code`, `exp_code`, `unit_total`, `unit_cost`, `requested`, `actual`, `balance`, `explanation`, `file_nota`, `created_at`, `updated_at`) VALUES
(1, 1, '11', '2025-10-16', 'Travel', 'Yoga', '1213', '1234', 1, 100000.00, 0.00, 0.00, 0.00, 'LKK - 1', NULL, '2025-10-16 09:09:57', '2025-10-16 09:09:57');

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

--
-- Dumping data for table `laporan_keuangan_header`
--

INSERT INTO `laporan_keuangan_header` (`id_laporan_keu`, `kode_projek`, `nama_projek`, `nama_kegiatan`, `pelaksana`, `tanggal_pelaksanaan`, `tanggal_laporan`, `mata_uang`, `exrate`, `created_by`, `verified_by`, `approved_by`, `status_lap`, `catatan_finance`, `created_at`, `updated_at`) VALUES
(1, 'PRJ-2025-001', 'Tes - Prototype 1', 'Tes - LKK - 1', 'Chandra', '2025-10-16', '2025-10-16', 'IDR', 1.0000, 1, 6, NULL, 'rejected', '', '2025-10-16 09:09:57', '2025-10-19 12:59:40');

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
  `status` enum('draft','submitted','approved_fm','approved','rejected') DEFAULT 'draft' COMMENT 'draft=PM draft, submitted=waiting FM, approved_fm=FM approved waiting DIR, approved=DIR approved final, rejected=rejected',
  `approved_by_fm` int(11) DEFAULT NULL,
  `approved_by_dir` int(11) DEFAULT NULL,
  `fm_approval_date` datetime DEFAULT NULL,
  `dir_approval_date` datetime DEFAULT NULL,
  `kode_proyek` varchar(50) DEFAULT NULL,
  `tor` text DEFAULT NULL COMMENT 'Terms of Reference',
  `file_budget` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proposal`
--

INSERT INTO `proposal` (`id_proposal`, `judul_proposal`, `pj`, `date`, `pemohon`, `status`, `approved_by_fm`, `approved_by_dir`, `fm_approval_date`, `dir_approval_date`, `kode_proyek`, `tor`, `file_budget`, `created_at`, `updated_at`) VALUES
(5, 'Tes - Alur Kerja PM - 4', 'Yoga', '2025-10-16', 'Chandra', 'approved_fm', 5, NULL, '2025-10-16 15:25:45', NULL, 'PRJ-2025-001', 'uploads/tor/1760603074_Presentasi - Analisis PEC Warung Bubur Soto Ibu Suratmi.pdf', 'uploads/budgets/1760603074_chan of Personal Feedback – Johari Window (Chandra Erland Prayoga) (Responses).xlsx', '2025-10-16 08:24:34', '2025-10-16 08:25:45'),
(6, 'Tes - Alur Kerja PM - 5', 'Yoga', '2025-10-16', 'Chandra', 'approved_fm', 5, NULL, '2025-10-16 15:35:37', NULL, 'PRJ-2025-001', 'uploads/tor/1760603721_KWU2_5E_3202316065_Chandra Erland Prayoga.pdf', 'uploads/budgets/1760603721_chan of Personal Feedback – Johari Window (Chandra Erland Prayoga) (Responses).xlsx', '2025-10-16 08:35:21', '2025-10-16 08:35:37'),
(7, 'Tes - Alur Kerja PM - 6', 'Yoga', '2025-10-16', 'Chandra', 'approved_fm', 5, NULL, '2025-10-16 15:38:56', NULL, 'PRJ-2025-001', 'uploads/tor/1760603919_Timothy Ronald Journey - 5E - 3202316065 - Chandra Erland Prayoga.pdf', 'uploads/budgets/1760603919_chan of Personal Feedback – Johari Window (Chandra Erland Prayoga) (Responses).xlsx', '2025-10-16 08:38:39', '2025-10-16 08:38:56'),
(8, 'Tes - Alur Kerja PM - 7', 'Yoga', '2025-10-16', 'Chandra', 'approved_fm', 5, NULL, '2025-10-16 15:44:14', NULL, 'PRJ-2025-001', 'uploads/tor/1760604226_Timothy Ronald Journey - 5E - 3202316065 - Chandra Erland Prayoga.pdf', 'uploads/budgets/1760604226_chan of Personal Feedback – Johari Window (Chandra Erland Prayoga) (Responses).xlsx', '2025-10-16 08:43:46', '2025-10-16 08:44:14'),
(9, 'TEST - Proposal untuk DIR Approval', 'Test PJ', '2025-10-16', 'Chandra', 'approved', 5, 4, '2025-10-16 15:50:46', '2025-10-19 20:01:23', 'PRJ-2025-001', 'uploads/tor/TEST_TOR_FILE.pdf', 'uploads/budgets/TEST_BUDGET_FILE.xlsx', '2025-10-16 08:50:46', '2025-10-19 13:01:23'),
(10, 'TEST - Proposal untuk DIR Approval', 'Test PJ', '2025-10-16', 'Chandra', 'approved', 5, NULL, '2025-10-16 15:51:01', '2025-10-17 18:35:03', 'PRJ-2025-001', 'uploads/tor/1760600730_KWU2_5E_3202316065_Chandra Erland Prayoga.pdf', 'uploads/budgets/1760600730_chan of Personal Feedback – Johari Window (Chandra Erland Prayoga) (Responses).xlsx', '2025-10-16 08:51:01', '2025-10-17 11:35:03'),
(11, 'Tes - Alur Kerja PM - 8', 'Yoga', '2025-10-16', 'Chandra', 'approved', 5, 4, '2025-10-16 16:01:27', '2025-10-16 16:05:24', 'PRJ-2025-001', 'uploads/tor/1760605273_Timothy Ronald Journey - 5E - 3202316065 - Chandra Erland Prayoga.pdf', 'uploads/budgets/1760605273_chan of Personal Feedback – Johari Window (Chandra Erland Prayoga) (Responses).xlsx', '2025-10-16 09:01:13', '2025-10-16 09:05:24'),
(12, 'Tes - Akun ke-2', 'Aam Wijaya', '2025-10-20', 'Ferrosi', 'rejected', NULL, NULL, NULL, NULL, 'PRJ-2025-001', '../../uploads/tor/1760880399_KWU2_KELAS_3202316027_FERROSI_PRATAMA_5E.pdf', '../../uploads/budgets/1760880399_KWU2_KELAS_3202316027_FERROSI_PRATAMA_5E.pdf', '2025-10-19 13:26:39', '2025-10-19 13:27:51'),
(13, 'Tes - Akun ke-2.2', 'Aam', '2025-10-20', 'Ferrosi', 'approved', 5, 4, '2025-10-19 20:31:15', '2025-10-19 20:31:54', 'PRJ-2025-001', '../../uploads/tor/1760880617_KWU2_KELAS_3202316027_FERROSI_PRATAMA_5E.pdf', '../../uploads/budgets/1760880617_KWU2_KELAS_3202316027_FERROSI_PRATAMA_5E.pdf', '2025-10-19 13:30:17', '2025-10-19 13:31:54');

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

--
-- Dumping data for table `proyek`
--

INSERT INTO `proyek` (`kode_proyek`, `nama_proyek`, `status_proyek`, `donor`, `nilai_anggaran`, `periode_mulai`, `periode_selesai`, `rekening_khusus`, `created_at`, `updated_at`) VALUES
('PRJ-2025-001', 'Tes - Prototype 1', 'ongoing', 'Chandra', 9999999999999.99, '2025-10-16', '2025-10-31', '12345678', '2025-10-16 07:16:31', '2025-10-16 07:16:31');

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
-- Dumping data for table `user`
--

INSERT INTO `user` (`id_user`, `nama`, `role`, `email`, `no_HP`, `password_hash`, `created_at`, `updated_at`) VALUES
(1, 'Chandra', 'Project Manager', '12345c4n12345@gmail.com', '6283153505411', '$2y$10$771q172G/MaqwIXKE9nOVutIrnjC6BEat7lD0KAUsJWywfG8UdG0C', '2025-10-16 06:36:23', '2025-10-16 07:02:14'),
(4, 'Ferrosi Pratama', 'Finance Manager', 'ferrosipratamaq@gmail.com', '6289521340602', '$2y$10$4Dc7kmGgHY5WxZDfGw2zm.KIegC1xrX7GirUlXyj40Rl4/RzsopZu', '2025-10-16 06:43:55', '2025-10-20 02:36:45'),
(5, 'zheamanda', 'Finance Manager', 'zheaamandavitaloka@gmail.com', '6283836609877', '$2y$10$aYQPYZIRplq3lYsN4s4TrOtR.UPafFF7zDCS/pF30MkABJ5G/Z/CW', '2025-10-16 06:50:22', '2025-10-19 13:06:36'),
(6, 'Mione', 'Staff Accountant', 'hermionepriciliaa@gmail.com', '6282192831013', '$2y$10$sScVPxccDeOJ7Vboy0YWBecj497e/kPVdmz3sLuR0eiAmfLFwn0qO', '2025-10-16 07:01:18', '2025-10-16 07:03:39'),
(8, 'Ferrosi', 'Project Manager', 'ferrosipratamaqu@gmail.com', '6282134812641', '$2y$10$c.kiil8chyMEiAqZgzpR7u5sqVVftQIJJLyKIckfatqprVRXNLuqO', '2025-10-19 13:22:11', '2025-10-19 13:22:11'),
(9, 'lutfi', 'Project Manager', 'lutfifirdaus238@gmail.com', '6285752706608', '$2y$10$2fH773o5wxutvRZtlEKm2O/PNR6riXAOfdta1jT26dKhKyJnDFqnS', '2025-10-20 01:02:46', '2025-10-20 01:02:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buku_bank_detail`
--
ALTER TABLE `buku_bank_detail`
  ADD PRIMARY KEY (`id_detail_bank`),
  ADD KEY `id_bank_header` (`id_bank_header`);

--
-- Indexes for table `buku_bank_header`
--
ALTER TABLE `buku_bank_header`
  ADD PRIMARY KEY (`id_bank_header`),
  ADD KEY `kode_proyek` (`kode_proyek`);

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
  ADD KEY `kode_proyek` (`kode_proyek`),
  ADD KEY `fk_proposal_fm` (`approved_by_fm`),
  ADD KEY `fk_proposal_dir` (`approved_by_dir`);

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
  MODIFY `id_detail_keu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `laporan_keuangan_header`
--
ALTER TABLE `laporan_keuangan_header`
  MODIFY `id_laporan_keu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `proposal`
--
ALTER TABLE `proposal`
  MODIFY `id_proposal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `buku_bank_detail`
--
ALTER TABLE `buku_bank_detail`
  ADD CONSTRAINT `buku_bank_detail_ibfk_1` FOREIGN KEY (`id_bank_header`) REFERENCES `buku_bank_header` (`id_bank_header`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `buku_bank_header`
--
ALTER TABLE `buku_bank_header`
  ADD CONSTRAINT `buku_bank_header_ibfk_1` FOREIGN KEY (`kode_proyek`) REFERENCES `proyek` (`kode_proyek`) ON DELETE CASCADE ON UPDATE CASCADE;

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
  ADD CONSTRAINT `fk_proposal_dir` FOREIGN KEY (`approved_by_dir`) REFERENCES `user` (`id_user`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_proposal_fm` FOREIGN KEY (`approved_by_fm`) REFERENCES `user` (`id_user`) ON DELETE SET NULL,
  ADD CONSTRAINT `proposal_ibfk_1` FOREIGN KEY (`kode_proyek`) REFERENCES `proyek` (`kode_proyek`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
