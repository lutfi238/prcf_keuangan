-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 22, 2025 at 12:57 PM
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
('BD-20251020-074455-a668', 'BH-20251020-073558-25fb', '2025-01-01', 'BP01-24-07-01', 'Advance Staff', 'Advance - Forest patrol and monitoring, July 2024 (Yadi Purwanto)', 'Yadi Purwanto', '-', '-', 'Adv', 16116.00, '0', 0.00, 0.00, 52240000.00, 3241.50, 2447760000.00, -3241.50, 'ongoing'),
('BD-20251027-134628-4ec9', 'BH-20251027-134131-c6b0', '2025-10-27', 'BP01-24-06-02', 'Project Running', 'Advance - Operational for LPHD, Q1 (Penepian Raya)', 'Penepian Raya', '-', '-', 'Adv', 16116.15, '0', 0.00, 0.00, 15000000.00, 930.74, 3208000000.00, 199069.26, 'ongoing');

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

INSERT INTO `buku_bank_header` (`id_bank_header`, `kode_proyek`, `account_name`, `bank_name`, `account_number`, `exrate`, `currency`, `periode_bulan`, `periode_tahun`, `saldo_awal_idr`, `saldo_awal_usd`, `current_period_change_idr`, `current_period_change_usd`, `saldo_akhir_idr`, `saldo_akhir_usd`, `prepared_by`, `approved_by`, `status_laporan`, `tanggal_pembuatan`, `tanggal_persetujuan`) VALUES
('BH-20251020-073558-25fb', 'PRJ-2025-001', 'Aam', 'Bank M', '146 1231 123123', 1.00, 'IDR', '01', '2025', 2500000000.00, 0.00, -52240000.00, -3241.50, 2447760000.00, -3241.50, 'Ferrosi Pratama', NULL, 'draft', '2025-10-20', NULL),
('BH-20251022-104252-3a9a', 'PRJ-2025-001', 'Aam', 'Bank M', '146 1231 123123', 16100.12, 'IDR', '02', '2025', 3220024000.00, 200000.00, 0.00, 0.00, 3220024000.00, 200000.00, 'lutfi', NULL, 'draft', '2025-10-22', NULL),
('BH-20251027-134131-c6b0', 'PRJ-2025-001', 'Aam', 'Bank M', '146 1231 123123', 16115.00, 'USD', '10', '2025', 3223000000.00, 200000.00, -15000000.00, -930.74, 3208000000.00, 199069.26, 'Ferrosi Pratama', NULL, 'draft', '2025-10-27', NULL);

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

--
-- Dumping data for table `buku_piutang_header`
--

INSERT INTO `buku_piutang_header` (`id_piutang`, `kode_proyek`, `periode_bulan`, `periode_tahun`, `exrate`, `beginning_balance_idr`, `ending_balance_idr`, `beginning_balance_usd`, `ending_balance_usd`, `created_by`, `approved_by`, `catatan_fm`, `status`, `tgl_pembuatan`, `tgl_persetujuan`, `created_at`, `updated_at`) VALUES
(1, 'PRJ-2025-001', '08', '2025', 16100.12, 3381026810.01, 3381026810.01, 210000.10, 210000.10, 9, NULL, NULL, 'draft', '2025-10-22', NULL, '2025-10-22 04:34:06', '2025-10-22 04:34:06');

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
  `status_lap` enum('draft','submitted','verified','approved','rejected','revision_requested') DEFAULT 'draft' COMMENT 'draft=PM draft, submitted=waiting SA, verified=SA verified, approved=FM approved, rejected=rejected, revision_requested=FM requested revision',
  `catatan_finance` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_codes`
--

CREATE TABLE `project_codes` (
  `id` int(10) UNSIGNED NOT NULL,
  `subcategory_id` int(10) UNSIGNED NOT NULL,
  `kode_proyek` varchar(20) NOT NULL,
  `place_code` varchar(50) NOT NULL COMMENT 'Full code e.g., 10101-PR-01, 20208-NJ-01',
  `exp_code` varchar(20) NOT NULL COMMENT 'Expense code part e.g., 10101, 20208',
  `activity_code` varchar(10) NOT NULL COMMENT 'Activity code part e.g., PR, NJ, RJ',
  `description` text DEFAULT NULL COMMENT 'Activity description',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Project-specific place codes and expense codes';

-- --------------------------------------------------------

--
-- Table structure for table `project_code_categories`
--

CREATE TABLE `project_code_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `kode_proyek` varchar(20) NOT NULL,
  `category_number` varchar(10) NOT NULL COMMENT 'e.g., 1, 2, 3, 5, 11',
  `category_name` varchar(255) NOT NULL COMMENT 'e.g., Forest Governance, Forest Protection',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Project code categories (top level hierarchy)';

-- --------------------------------------------------------

--
-- Table structure for table `project_code_subcategories`
--

CREATE TABLE `project_code_subcategories` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `subcategory_number` varchar(10) NOT NULL COMMENT 'e.g., 101, 102, 201, 202',
  `subcategory_name` varchar(255) NOT NULL COMMENT 'e.g., Forest Management Institution, Legal Recognition',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Project code subcategories (second level hierarchy)';

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
  `catatan_fm` text DEFAULT NULL,
  `approved_by_fm` int(11) DEFAULT NULL,
  `fm_approval_date` datetime DEFAULT NULL,
  `kode_proyek` varchar(50) DEFAULT NULL,
  `tor` text DEFAULT NULL COMMENT 'Terms of Reference',
  `file_budget` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proposal`
--

INSERT INTO `proposal` (`id_proposal`, `judul_proposal`, `pj`, `date`, `pemohon`, `status`, `catatan_fm`, `approved_by_fm`, `fm_approval_date`, `kode_proyek`, `tor`, `file_budget`, `created_at`, `updated_at`) VALUES
(19, 'Tes - Alur Kerja PM - Near Final Test', 'Chandra', '2025-11-03', 'Chandra', 'approved_fm', NULL, 5, '2025-11-03 02:04:40', 'PRJ-2025-001', '../../uploads/tor/1762109982_1605-Article Text-10164-1-10-20250130.pdf', '../../uploads/budgets/1762109982_1605-Article Text-10164-1-10-20250130.pdf', '2025-11-02 18:59:42', '2025-11-02 19:04:40'),
(20, 'Tes - Alur Kerja - Progress 90%', 'Chandra', '2025-11-03', 'Chandra', 'approved_fm', NULL, 5, '2025-11-03 09:42:11', 'PRJ-2025-001', '../../uploads/tor/1762137600_LAPORAN_UAS_WEBPRO_PCRFI_KEL-1_5-E (1).docx', '../../uploads/budgets/1762137600_LAPORAN_UAS_WEBPRO_PCRFI_KEL-1_5-E (1).docx', '2025-11-03 02:40:00', '2025-11-03 02:42:11'),
(21, 'Test Judul Proposal 1', 'Situs', '2025-11-06', 'Chandra', 'approved_fm', NULL, 5, '2025-11-07 00:04:46', 'PRJ-2025-002', '../../uploads/tor/1762447717_KWU5_5E_3202316041_MUHAMMAD LUTFI FIRDAUS.pdf', '../../uploads/budgets/1762447717_3202316041_MUHAMMAD_LUTFI_FIRDAUS_5E_proposal_bab_1-2-3.pdf', '2025-11-06 16:48:37', '2025-11-06 17:04:46'),
(23, 'Test Judul Proposal 1', 'Situs', '2025-11-06', 'Chandra', 'rejected', 'revisi ya', NULL, NULL, 'PRJ-2025-002', '../../uploads/tor/1762447859_KWU5_5E_3202316041_MUHAMMAD LUTFI FIRDAUS.pdf', '../../uploads/budgets/1762447859_3202316041_MUHAMMAD_LUTFI_FIRDAUS_5E_proposal_bab_1-2-3.pdf', '2025-11-06 16:50:59', '2025-11-06 17:01:55'),
(24, 'Test Judul Proposal 2', 'Kincai', '2025-11-06', 'Chandra', 'rejected', NULL, NULL, NULL, 'PRJ-2025-002', '../../uploads/tor/1762448144_3202316041_MUHAMMAD_LUTFI_FIRDAUS_5E_proposal_bab_1-2-3.pdf', '../../uploads/budgets/1762448144_KWU7_5E_KELOMPOK_chandra.pdf', '2025-11-06 16:55:44', '2025-11-06 16:57:39'),
(25, 'Test Judul Proposal 3', 'yubi', '2025-11-07', 'Chandra', 'submitted', NULL, NULL, NULL, 'PRJ-2026-012', '../../uploads/tor/1762448800_KWU7_5E_KELOMPOK_chandra.pdf', '../../uploads/budgets/1762448800_3202316041_MUHAMMAD_LUTFI_FIRDAUS_5E_proposal_bab_1-2-3.pdf', '2025-11-06 17:06:40', '2025-11-06 17:06:40');

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
('PRJ-2025-001', 'Tes - Prototype 1', 'ongoing', 'Chandra', 9999999999999.99, '2025-10-16', '2025-10-31', '12345678', '2025-10-16 07:16:31', '2025-10-16 07:16:31'),
('PRJ-2025-002', 'Test Proyek 2', 'ongoing', 'Fyz', 9999999999999.99, '2025-11-12', '2025-11-27', '123456789 (BCA)', '2025-11-06 16:41:53', '2025-11-06 16:41:53'),
('PRJ-2025-005', 'Proyek Keterampilan Digital Komunitas', 'ongoing', 'Yayasan Cahaya Masa Depan', 75000000.00, '2025-11-06', '2025-11-20', '123456789 (BCA)', '2025-11-06 16:35:45', '2025-11-06 16:35:45'),
('PRJ-2025-008', 'Test Proyek 3', 'ongoing', 'Fsu', 9999999999999.99, '2025-11-14', '2025-11-28', '123456789 (BCA)', '2025-11-06 16:45:48', '2025-11-06 16:45:48'),
('PRJ-2026-012', 'Inisiatif Konservasi Hutan Bakau Pesisir Nusantara', 'ongoing', 'Global Green Fund (GGF)', 150000000.00, '2025-11-13', '2025-12-06', '9876543219 (Bank Mandiri)', '2025-11-06 16:37:22', '2025-11-06 16:37:22'),
('PRJ-2027-020', 'Proyek Gamer Tournament MPL Mobile Legend', 'ongoing', 'Montoon', 2000000000.00, '2025-11-09', '2025-12-01', '12394829342 (Bank Mandiri)', '2025-11-06 16:40:32', '2025-11-06 16:40:32');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `role` enum('Project Manager','Finance Manager','Staff Accountant','Direktur','Admin') NOT NULL COMMENT 'User role: PM, FM, SA, Direktur, or Admin',
  `status` enum('active','inactive','pending') NOT NULL DEFAULT 'inactive' COMMENT 'Account status: active=can login, inactive=deactivated by admin, pending=awaiting admin approval',
  `email` varchar(255) NOT NULL,
  `no_HP` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_notification_check` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id_user`, `nama`, `role`, `status`, `email`, `no_HP`, `password_hash`, `created_at`, `updated_at`, `last_notification_check`) VALUES
(1, 'Chandra', 'Project Manager', 'active', '12345c4n12345@gmail.com', '6283153505411', '$2y$10$cT3VuBv4Meofh8h69fPWKOCLt/Ym5qVvNNX79huxDwso69Io7sTBG', '2025-10-16 06:36:23', '2025-11-06 16:12:12', '2025-10-28 16:42:54'),
(4, 'Ferrosi Pratama', 'Finance Manager', 'active', 'ferrosipratamaq@gmail.com', '6289521340602', '$2y$10$4Dc7kmGgHY5WxZDfGw2zm.KIegC1xrX7GirUlXyj40Rl4/RzsopZu', '2025-10-16 06:43:55', '2025-11-03 09:24:14', NULL),
(5, 'zheamandaa', 'Finance Manager', 'active', 'zheaamandavitaloka@gmail.com', '6283836609877', '$2y$10$J6bFE0liRualyjXvQNyFqeksiunQ03inSjPLU.9bmLc9nnOMKLW4a', '2025-10-16 06:50:22', '2025-11-03 09:24:14', NULL),
(6, 'Mione', 'Staff Accountant', 'active', 'hermionepriciliaa@gmail.com', '6282192831013', '$2y$10$fKZ11m.6VhV88ciUKB0wheMWP0fbpBos/0WsTas/aOivd5IhEBxH2', '2025-10-16 07:01:18', '2025-11-06 16:12:07', NULL),
(8, 'Ferrosi', 'Staff Accountant', 'active', 'ferrosipratamaqu@gmail.com', '6282134812641', '$2y$10$c.kiil8chyMEiAqZgzpR7u5sqVVftQIJJLyKIckfatqprVRXNLuqO', '2025-10-19 13:22:11', '2025-11-03 09:24:14', NULL),
(9, 'lutfi', 'Admin', 'active', 'lutfifirdaus238@gmail.com', '6285752706608', '$2y$10$84faVqe4dv045JPvdigeJeQjXVg2ZEsOjtnykqKnCbexOJevAMFLi', '2025-10-20 01:02:46', '2025-11-22 11:36:40', NULL),
(11, 'lutfi2', 'Direktur', 'active', 'lutfifirdaus236@gmail.com', '0857', '$2y$10$FW1HOjE/m4IWGULT7hSyM.SpkLqJ0HaOrEot1F6DKqyc5aV0yuMBm', '2025-10-29 16:07:23', '2025-11-22 11:39:37', NULL),
(13, 'SA', 'Staff Accountant', 'active', '12345cc4nn12345@gmail.com', '6283154567881', '$2y$10$lTLvJFdopBIPuVmM61NqEuTq3KG3XA2364XnLM2Eu66lBFdPDJr5K', '2025-11-02 18:57:27', '2025-11-06 16:12:39', NULL);

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
-- Indexes for table `project_codes`
--
ALTER TABLE `project_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_place_code_per_project` (`kode_proyek`,`place_code`),
  ADD KEY `subcategory_id` (`subcategory_id`),
  ADD KEY `idx_kode_proyek` (`kode_proyek`),
  ADD KEY `idx_place_code` (`place_code`),
  ADD KEY `idx_exp_code_project` (`kode_proyek`,`exp_code`);

--
-- Indexes for table `project_code_categories`
--
ALTER TABLE `project_code_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kode_proyek` (`kode_proyek`);

--
-- Indexes for table `project_code_subcategories`
--
ALTER TABLE `project_code_subcategories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category_id` (`category_id`);

--
-- Indexes for table `proposal`
--
ALTER TABLE `proposal`
  ADD PRIMARY KEY (`id_proposal`),
  ADD KEY `kode_proyek` (`kode_proyek`),
  ADD KEY `fk_proposal_fm` (`approved_by_fm`);

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
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_status` (`status`);

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
  MODIFY `id_piutang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id_detail_keu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `laporan_keuangan_header`
--
ALTER TABLE `laporan_keuangan_header`
  MODIFY `id_laporan_keu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `project_codes`
--
ALTER TABLE `project_codes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_code_categories`
--
ALTER TABLE `project_code_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `project_code_subcategories`
--
ALTER TABLE `project_code_subcategories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `proposal`
--
ALTER TABLE `proposal`
  MODIFY `id_proposal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

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
-- Constraints for table `project_codes`
--
ALTER TABLE `project_codes`
  ADD CONSTRAINT `project_codes_ibfk_1` FOREIGN KEY (`subcategory_id`) REFERENCES `project_code_subcategories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_codes_ibfk_2` FOREIGN KEY (`kode_proyek`) REFERENCES `proyek` (`kode_proyek`) ON DELETE CASCADE;

--
-- Constraints for table `project_code_categories`
--
ALTER TABLE `project_code_categories`
  ADD CONSTRAINT `project_code_categories_ibfk_1` FOREIGN KEY (`kode_proyek`) REFERENCES `proyek` (`kode_proyek`) ON DELETE CASCADE;

--
-- Constraints for table `project_code_subcategories`
--
ALTER TABLE `project_code_subcategories`
  ADD CONSTRAINT `project_code_subcategories_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `project_code_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `proposal`
--
ALTER TABLE `proposal`
  ADD CONSTRAINT `fk_proposal_fm` FOREIGN KEY (`approved_by_fm`) REFERENCES `user` (`id_user`) ON DELETE SET NULL,
  ADD CONSTRAINT `proposal_ibfk_1` FOREIGN KEY (`kode_proyek`) REFERENCES `proyek` (`kode_proyek`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
