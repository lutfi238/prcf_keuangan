-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 23, 2025 at 03:38 PM
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
('BD-20251027-134628-4ec9', 'BH-20251027-134131-c6b0', '2025-10-27', 'BP01-24-06-02', 'Project Running', 'Advance - Operational for LPHD, Q1 (Penepian Raya)', 'Penepian Raya', '-', '-', 'Adv', 16116.15, '0', 0.00, 0.00, 15000000.00, 930.74, 3208000000.00, 199069.26, 'ongoing'),
('BD-20251123-112517-13bc', 'BH-20251123-112517-128c', '2025-11-23', '2025/11/TEST-PROJ-1763871917/001', 'Test Proposal', 'Advance for: Test Proposal', 'Test PJ', '-', '-', 'Adv', 15000.00, 'USD', 0.00, 0.00, 1500000.00, 100.00, 0.00, 0.00, 'ongoing'),
('BD-20251123-112542-12d3', 'BH-20251123-112542-0ef9', '2025-11-23', '2025/11/TEST-PROJ-1763871941/001', 'Test Proposal', 'Advance for: Test Proposal', 'Test PJ', '-', '-', 'Adv', 15000.00, 'USD', 0.00, 0.00, 1500000.00, 100.00, 0.00, 0.00, 'ongoing'),
('BD-20251123-112622-ae3b', 'BH-20251123-112622-aaf7', '2025-11-23', '2025/11/TEST-PROJ-1763871982/001', 'Test Proposal', 'Advance for: Test Proposal', 'Test PJ', '-', '-', 'Adv', 15000.00, 'USD', 0.00, 0.00, 1500000.00, 100.00, 0.00, 0.00, 'ongoing'),
('BD-20251123-205545-9101', 'BH-20251123-205545-8609', '2025-11-23', 'BB-BD-20251123-205545-9101-PROP-000024', 'Test Proposal', 'Advance for: Test Proposal', 'Test PJ', '-', '-', 'Adv', 15500.00, 'USD', 0.00, 0.00, 1500000.00, 100.00, 0.00, 0.00, 'ongoing');

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
('BH-20251027-134131-c6b0', 'PRJ-2025-001', 'Aam', 'Bank M', '146 1231 123123', 16115.00, 'USD', '10', '2025', 3223000000.00, 200000.00, -15000000.00, -930.74, 3208000000.00, 199069.26, 'Ferrosi Pratama', NULL, 'draft', '2025-10-27', NULL),
('BH-20251123-112517-128c', 'TEST-PROJ-1763871917', '', '', '', 1.00, '', '11', '2025', 0.00, 0.00, -1500000.00, -100.00, -1500000.00, -100.00, NULL, NULL, 'draft', '2025-11-23', NULL),
('BH-20251123-112542-0ef9', 'TEST-PROJ-1763871941', '', '', '', 1.00, '', '11', '2025', 0.00, 0.00, -1500000.00, -100.00, -1500000.00, -100.00, NULL, NULL, 'draft', '2025-11-23', NULL),
('BH-20251123-112622-aaf7', 'TEST-PROJ-1763871982', '', '', '', 1.00, '', '11', '2025', 0.00, 0.00, -1500000.00, -100.00, -1500000.00, -100.00, NULL, NULL, 'draft', '2025-11-23', NULL),
('BH-20251123-205545-8609', 'TEST-PROJ-1763871898', '', '', '', 1.00, '', '11', '2025', 0.00, 0.00, -1500000.00, -100.00, -1500000.00, -100.00, NULL, NULL, 'draft', '2025-11-23', NULL);

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

--
-- Dumping data for table `buku_piutang_detail`
--

INSERT INTO `buku_piutang_detail` (`id_detail_piutang`, `id_piutang`, `tgl_trx`, `reff`, `description`, `recipient`, `p_code`, `exp_code`, `nominal_code`, `exrate`, `debit_idr`, `debit_usd`, `credit_idr`, `credit_usd`, `balance_idr`, `balance_usd`, `created_at`, `updated_at`) VALUES
(1, 2, '2025-11-23', '2025/11/TEST-PROJ-1763871917/001', 'Advance for: Test Proposal', 'Test PJ', NULL, NULL, NULL, NULL, 1500000.00, 100.00, 0.00, 0.00, 0.00, 0.00, '2025-11-23 04:25:17', '2025-11-23 04:25:17'),
(2, 3, '2025-11-23', '2025/11/TEST-PROJ-1763871941/001', 'Advance for: Test Proposal', 'Test PJ', NULL, NULL, NULL, NULL, 1500000.00, 100.00, 0.00, 0.00, 0.00, 0.00, '2025-11-23 04:25:42', '2025-11-23 04:25:42'),
(3, 4, '2025-11-23', '2025/11/TEST-PROJ-1763871982/001', 'Advance for: Test Proposal', 'Test PJ', NULL, NULL, NULL, NULL, 1500000.00, 100.00, 0.00, 0.00, 0.00, 0.00, '2025-11-23 04:26:22', '2025-11-23 04:26:22'),
(6, 7, '2025-11-23', 'BB-BD-20251123-205545-9101-PROP-000024', 'Advance for: Test Proposal', 'Test PJ', NULL, NULL, NULL, 15500.0000, 1500000.00, 100.00, 0.00, 0.00, 0.00, 0.00, '2025-11-23 13:55:45', '2025-11-23 13:55:45');

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
(1, 'PRJ-2025-001', '08', '2025', 16100.12, 3381026810.01, 3381026810.01, 210000.10, 210000.10, 9, NULL, NULL, 'draft', '2025-10-22', NULL, '2025-10-22 04:34:06', '2025-10-22 04:34:06'),
(2, 'TEST-PROJ-1763871917', '11', '2025', 1.00, 0.00, 1500000.00, 0.00, 100.00, NULL, NULL, NULL, 'draft', '2025-11-23', NULL, '2025-11-23 04:25:17', '2025-11-23 04:25:17'),
(3, 'TEST-PROJ-1763871941', '11', '2025', 1.00, 0.00, 1500000.00, 0.00, 100.00, NULL, NULL, NULL, 'draft', '2025-11-23', NULL, '2025-11-23 04:25:42', '2025-11-23 04:25:42'),
(4, 'TEST-PROJ-1763871982', '11', '2025', 1.00, 0.00, 1500000.00, 0.00, 100.00, NULL, NULL, NULL, 'draft', '2025-11-23', NULL, '2025-11-23 04:26:22', '2025-11-23 04:26:22'),
(5, 'TEST-PROJ-1763872018', '11', '2025', 1.00, 0.00, 1500000.00, 0.00, 100.00, NULL, NULL, NULL, 'draft', '2025-11-23', NULL, '2025-11-23 04:26:58', '2025-11-23 04:26:58'),
(6, 'TEST-PROJ-1763873014', '11', '2025', 1.00, 0.00, 1500000.00, 0.00, 100.00, NULL, NULL, NULL, 'draft', '2025-11-23', NULL, '2025-11-23 04:43:34', '2025-11-23 04:43:34'),
(7, 'TEST-PROJ-1763871898', '11', '2025', 1.00, 0.00, 1500000.00, 0.00, 100.00, NULL, NULL, NULL, 'draft', '2025-11-23', NULL, '2025-11-23 13:55:45', '2025-11-23 13:55:45');

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

--
-- Dumping data for table `buku_piutang_unliquidated`
--

INSERT INTO `buku_piutang_unliquidated` (`id_unliquidate`, `id_piutang`, `tgl`, `voucher_no`, `name`, `description`, `nilai_idr`, `nilai_usd`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, '2025-11-23', '2025/11/TEST-PROJ-1763871917/001', 'Test PJ', 'Advance for: Test Proposal', 1500000.00, 100.00, 'pending', '2025-11-23 04:25:17', '2025-11-23 04:25:17'),
(2, 3, '2025-11-23', '2025/11/TEST-PROJ-1763871941/001', 'Test PJ', 'Advance for: Test Proposal', 1500000.00, 100.00, 'pending', '2025-11-23 04:25:42', '2025-11-23 04:25:42'),
(3, 4, '2025-11-23', '2025/11/TEST-PROJ-1763871982/001', 'Test PJ', 'Advance for: Test Proposal', 1500000.00, 100.00, 'pending', '2025-11-23 04:26:22', '2025-11-23 04:26:22'),
(6, 7, '2025-11-23', 'BB-BD-20251123-205545-9101-PROP-000024', 'Test PJ', 'Advance for: Test Proposal', 1500000.00, 100.00, 'pending', '2025-11-23 13:55:45', '2025-11-23 13:55:45');

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
(6, 5, '', '2025-10-30', 'Travel TO Nangga Jemah', 'LUTFI TRAVEL', '20208-NJ-01', '20208', 1, 400000.00, 0.00, 0.00, 0.00, '', '../../uploads/receipts/1761718308_1_RobloxScreenShot20251020_225818585.png', '2025-10-29 06:11:48', '2025-10-29 06:11:48'),
(7, 6, '', '2025-10-31', 'Travel', 'Elmeanual', '20208-RJ-01', '20208', 1, 400000.00, 0.00, 0.00, 0.00, '', '../../uploads/receipts/1761720169_1_RobloxScreenShot20251020_225818585.png', '2025-10-29 06:42:49', '2025-10-29 06:42:49'),
(8, 6, '', '0000-00-00', 'eat', 'rumah makan pak de', '20208-RJ-01', '20208', 5, 60000.00, 0.00, 0.00, 0.00, '', '../../uploads/receipts/1761720169_2_RobloxScreenShot20251020_225818585.png', '2025-10-29 06:42:49', '2025-10-29 06:42:49');

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

--
-- Dumping data for table `laporan_keuangan_header`
--

INSERT INTO `laporan_keuangan_header` (`id_laporan_keu`, `kode_projek`, `nama_projek`, `nama_kegiatan`, `pelaksana`, `tanggal_pelaksanaan`, `tanggal_laporan`, `mata_uang`, `exrate`, `created_by`, `verified_by`, `approved_by`, `status_lap`, `catatan_finance`, `created_at`, `updated_at`) VALUES
(5, 'PRJ-2025-001', 'Training Fire Kontrols', '-', 'Chandra', '2025-10-30', '2025-10-29', 'IDR', 16000.0000, 1, 8, 4, 'approved', '', '2025-10-29 06:11:48', '2025-10-29 06:20:22'),
(6, 'PRJ-2025-001', 'training fire kontrol 3', '0', 'Chandra', '2025-10-30', '2025-10-29', 'IDR', 16000.0000, 1, 8, NULL, 'rejected', 'nota travel tidak jelas', '2025-10-29 06:42:49', '2025-10-29 06:53:04');

-- --------------------------------------------------------

--
-- Table structure for table `project_code_budgets`
--

CREATE TABLE `project_code_budgets` (
  `id_budget` int(11) NOT NULL,
  `kode_proyek` varchar(50) NOT NULL,
  `id_village` int(11) NOT NULL,
  `exp_code` varchar(20) NOT NULL COMMENT 'Expense code, misal: 10101, 20208',
  `place_code` varchar(50) NOT NULL COMMENT 'Format: [exp_code]-[village_abbr]-01, generated by PHP',
  `budget_usd` decimal(15,2) DEFAULT 0.00 COMMENT 'Budget dalam USD',
  `budget_idr` decimal(15,2) DEFAULT 0.00 COMMENT 'Budget dalam IDR',
  `used_usd` decimal(15,2) DEFAULT 0.00 COMMENT 'Budget terpakai (USD)',
  `used_idr` decimal(15,2) DEFAULT 0.00 COMMENT 'Budget terpakai (IDR)',
  `remaining_usd` decimal(15,2) GENERATED ALWAYS AS (`budget_usd` - `used_usd`) STORED,
  `remaining_idr` decimal(15,2) GENERATED ALWAYS AS (`budget_idr` - `used_idr`) STORED,
  `exrate` decimal(10,4) DEFAULT 1.0000 COMMENT 'Exchange rate USD to IDR',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Budget allocation per exp code per village';

--
-- Dumping data for table `project_code_budgets`
--

INSERT INTO `project_code_budgets` (`id_budget`, `kode_proyek`, `id_village`, `exp_code`, `place_code`, `budget_usd`, `budget_idr`, `used_usd`, `used_idr`, `exrate`, `created_at`, `updated_at`) VALUES
(4, 'TEST-PROJ-1763871675', 999, 'TEST-EXP', 'TEST-PLACE-1763871675', 1000.00, 15000000.00, 0.00, 0.00, 1.0000, '2025-11-23 04:21:15', '2025-11-23 04:21:15'),
(5, 'TEST-PROJ-1763871689', 999, 'TEST-EXP', 'TEST-PLACE-1763871689', 1000.00, 15000000.00, 0.00, 0.00, 1.0000, '2025-11-23 04:21:29', '2025-11-23 04:21:29'),
(6, 'TEST-PROJ-1763871745', 999, 'TEST-EXP', 'TEST-PLACE-1763871745', 1000.00, 15000000.00, 0.00, 0.00, 1.0000, '2025-11-23 04:22:25', '2025-11-23 04:22:25'),
(7, 'TEST-PROJ-1763871759', 999, 'TEST-EXP', 'TEST-PLACE-1763871759', 1000.00, 15000000.00, 0.00, 0.00, 1.0000, '2025-11-23 04:22:39', '2025-11-23 04:22:39'),
(8, 'TEST-PROJ-1763871852', 999, 'TEST-EXP', 'TEST-PLACE-1763871852', 1000.00, 15000000.00, 0.00, 0.00, 1.0000, '2025-11-23 04:24:12', '2025-11-23 04:24:12'),
(9, 'TEST-PROJ-1763871864', 999, 'TEST-EXP', 'TEST-PLACE-1763871864', 1000.00, 15000000.00, 0.00, 0.00, 1.0000, '2025-11-23 04:24:24', '2025-11-23 04:24:24'),
(10, 'TEST-PROJ-1763871881', 999, 'TEST-EXP', 'TEST-PLACE-1763871881', 1000.00, 15000000.00, 0.00, 0.00, 1.0000, '2025-11-23 04:24:41', '2025-11-23 04:24:41'),
(11, 'TEST-PROJ-1763871898', 999, 'TEST-EXP', 'TEST-PLACE-1763871898', 1000.00, 15000000.00, 100.00, 1500000.00, 1.0000, '2025-11-23 04:24:58', '2025-11-23 13:55:45'),
(12, 'TEST-PROJ-1763871917', 999, 'TEST-EXP', 'TEST-PLACE-1763871917', 1000.00, 15000000.00, NULL, NULL, 1.0000, '2025-11-23 04:25:17', '2025-11-23 04:25:17'),
(13, 'TEST-PROJ-1763871941', 999, 'TEST-EXP', 'TEST-PLACE-1763871941', 1000.00, 15000000.00, NULL, NULL, 1.0000, '2025-11-23 04:25:41', '2025-11-23 04:25:42'),
(14, 'TEST-PROJ-1763871982', 999, 'TEST-EXP', 'TEST-PLACE-1763871982', 1000.00, 15000000.00, 100.00, 1500000.00, 1.0000, '2025-11-23 04:26:22', '2025-11-23 04:26:22'),
(17, 'TEST-PROJ-1763871485', 1004, '10101', '10101-NB-01', 100000.00, 1667100000.00, 0.00, 0.00, 16671.0000, '2025-11-23 13:47:53', '2025-11-23 13:47:53');

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
  `status` enum('draft','submitted','approved_fm','approved','rejected') DEFAULT 'draft' COMMENT 'draft=PM draft, submitted=waiting FM, approved_fm=FM approved (final), approved=FM approved (final), rejected=rejected',
  `approved_by_fm` int(11) DEFAULT NULL,
  `fm_approval_date` datetime DEFAULT NULL,
  `kode_proyek` varchar(50) DEFAULT NULL,
  `tor` text DEFAULT NULL COMMENT 'Terms of Reference',
  `file_budget` varchar(255) DEFAULT NULL,
  `total_budget_usd` decimal(15,2) DEFAULT 0.00,
  `total_budget_idr` decimal(15,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'USD',
  `exrate_at_submission` decimal(10,4) DEFAULT 1.0000 COMMENT 'Exchange rate saat submit proposal',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proposal`
--

INSERT INTO `proposal` (`id_proposal`, `judul_proposal`, `pj`, `date`, `pemohon`, `status`, `approved_by_fm`, `fm_approval_date`, `kode_proyek`, `tor`, `file_budget`, `total_budget_usd`, `total_budget_idr`, `currency`, `exrate_at_submission`, `created_at`, `updated_at`) VALUES
(16, 'Training Fire Kontrols', 'Immanuel Huda', '2025-10-29', 'Chandra', 'approved', 4, '2025-10-29 12:37:56', 'PRJ-2025-001', '../../uploads/tor/1761715669__Kelompok_2_-_Template_Penulisan_Proposal_Tugas_Akhir_(TA)_2025_[revisi_10_oktober].pdf', '../../uploads/budgets/1761715669__Kelompok_2_-_Template_Penulisan_Proposal_Tugas_Akhir_(TA)_2025_[revisi_10_oktober].pdf', 0.00, 0.00, 'USD', 1.0000, '2025-10-29 05:27:49', '2025-10-29 05:38:41'),
(17, 'training fire konttrol', 'immanual duda', '2025-10-29', 'Chandra', 'rejected', NULL, NULL, 'PRJ-2025-001', '../../uploads/tor/1761719311_KWU5_5E_3202316041_MUHAMMAD LUTFI FIRDAUS.pdf', '../../uploads/budgets/1761719311_KWU5_5E_3202316041_MUHAMMAD LUTFI FIRDAUS.pdf', 0.00, 0.00, 'USD', 1.0000, '2025-10-29 06:28:31', '2025-10-29 06:29:26'),
(18, 'training fire kontrol 3', 'immanual huda', '2025-10-29', 'Chandra', 'approved', 4, '2025-10-29 13:39:06', 'PRJ-2025-001', '../../uploads/tor/1761719580_KWU5_5E_3202316041_MUHAMMAD LUTFI FIRDAUS.pdf', '../../uploads/budgets/1761719580_KWU5_5E_3202316041_MUHAMMAD LUTFI FIRDAUS.pdf', 0.00, 0.00, 'USD', 1.0000, '2025-10-29 06:33:00', '2025-10-29 06:39:55'),
(19, 'Test Proposal', 'Test PJ', '2025-11-23', 'Test User', 'submitted', NULL, NULL, 'TEST-PROJ-1763871745', 'dummy_tor.pdf', 'dummy_budget.xlsx', 100.00, 1500000.00, 'USD', 15000.0000, '2025-11-23 04:22:25', '2025-11-23 04:22:25'),
(20, 'Test Proposal', 'Test PJ', '2025-11-23', 'Test User', 'submitted', NULL, NULL, 'TEST-PROJ-1763871759', 'dummy_tor.pdf', 'dummy_budget.xlsx', 100.00, 1500000.00, 'USD', 15000.0000, '2025-11-23 04:22:39', '2025-11-23 04:22:39'),
(21, 'Test Proposal', 'Test PJ', '2025-11-23', 'Test User', 'submitted', NULL, NULL, 'TEST-PROJ-1763871852', 'dummy_tor.pdf', 'dummy_budget.xlsx', 100.00, 1500000.00, 'USD', 15000.0000, '2025-11-23 04:24:12', '2025-11-23 04:24:12'),
(22, 'Test Proposal', 'Test PJ', '2025-11-23', 'Test User', 'submitted', NULL, NULL, 'TEST-PROJ-1763871864', 'dummy_tor.pdf', 'dummy_budget.xlsx', 100.00, 1500000.00, 'USD', 15000.0000, '2025-11-23 04:24:24', '2025-11-23 04:24:24'),
(23, 'Test Proposal', 'Test PJ', '2025-11-23', 'Test User', 'submitted', NULL, NULL, 'TEST-PROJ-1763871881', 'dummy_tor.pdf', 'dummy_budget.xlsx', 100.00, 1500000.00, 'USD', 15000.0000, '2025-11-23 04:24:41', '2025-11-23 04:24:41'),
(24, 'Test Proposal', 'Test PJ', '2025-11-23', 'Test User', 'approved_fm', 4, '2025-11-23 20:55:45', 'TEST-PROJ-1763871898', 'dummy_tor.pdf', 'dummy_budget.xlsx', 100.00, 1500000.00, 'USD', 15000.0000, '2025-11-23 04:24:58', '2025-11-23 13:55:45'),
(25, 'Test Proposal', 'Test PJ', '2025-11-23', 'Test User', 'approved_fm', NULL, NULL, 'TEST-PROJ-1763871917', 'dummy_tor.pdf', 'dummy_budget.xlsx', 100.00, 1500000.00, 'USD', 15000.0000, '2025-11-23 04:25:17', '2025-11-23 04:25:17'),
(26, 'Test Proposal', 'Test PJ', '2025-11-23', 'Test User', 'approved_fm', NULL, NULL, 'TEST-PROJ-1763871941', 'dummy_tor.pdf', 'dummy_budget.xlsx', 100.00, 1500000.00, 'USD', 15000.0000, '2025-11-23 04:25:41', '2025-11-23 04:25:42');

-- --------------------------------------------------------

--
-- Table structure for table `proposal_budget_details`
--

CREATE TABLE `proposal_budget_details` (
  `id_detail` int(11) NOT NULL,
  `id_proposal` int(11) NOT NULL,
  `id_village` int(11) NOT NULL,
  `exp_code` varchar(20) NOT NULL,
  `place_code` varchar(50) NOT NULL COMMENT 'Auto-filled dari project_code_budgets',
  `requested_usd` decimal(15,2) DEFAULT 0.00,
  `requested_idr` decimal(15,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'USD' COMMENT 'Currency proposal: USD atau IDR',
  `exrate` decimal(10,4) DEFAULT 1.0000 COMMENT 'Exchange rate saat proposal dibuat',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Budget details per proposal';

--
-- Dumping data for table `proposal_budget_details`
--

INSERT INTO `proposal_budget_details` (`id_detail`, `id_proposal`, `id_village`, `exp_code`, `place_code`, `requested_usd`, `requested_idr`, `currency`, `exrate`, `description`, `created_at`) VALUES
(1, 21, 999, 'TEST-EXP', 'TEST-PLACE-1763871852', 100.00, 1500000.00, 'USD', 1.0000, 'Test Item', '2025-11-23 04:24:12'),
(2, 22, 999, 'TEST-EXP', 'TEST-PLACE-1763871864', 100.00, 1500000.00, 'USD', 1.0000, 'Test Item', '2025-11-23 04:24:24'),
(3, 23, 999, 'TEST-EXP', 'TEST-PLACE-1763871881', 100.00, 1500000.00, 'USD', 1.0000, 'Test Item', '2025-11-23 04:24:41'),
(4, 24, 999, 'TEST-EXP', 'TEST-PLACE-1763871898', 100.00, 1500000.00, 'USD', 1.0000, 'Test Item', '2025-11-23 04:24:58'),
(5, 25, 999, 'TEST-EXP', 'TEST-PLACE-1763871917', 100.00, 1500000.00, 'USD', 1.0000, 'Test Item', '2025-11-23 04:25:17'),
(6, 26, 999, 'TEST-EXP', 'TEST-PLACE-1763871941', 100.00, 1500000.00, 'USD', 1.0000, 'Test Item', '2025-11-23 04:25:41');

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
('TEST-PROJ-1763871485', 'Test Project', 'ongoing', NULL, NULL, NULL, NULL, NULL, '2025-11-23 04:18:05', '2025-11-23 04:18:05'),
('TEST-PROJ-1763871641', 'Test Project', 'ongoing', 'Test Donor', 100000.00, '2025-11-23', '2025-11-23', NULL, '2025-11-23 04:20:41', '2025-11-23 04:20:41'),
('TEST-PROJ-1763871675', 'Test Project', 'ongoing', 'Test Donor', 100000.00, '2025-11-23', '2025-11-23', NULL, '2025-11-23 04:21:15', '2025-11-23 04:21:15'),
('TEST-PROJ-1763871689', 'Test Project', 'ongoing', 'Test Donor', 100000.00, '2025-11-23', '2025-11-23', NULL, '2025-11-23 04:21:29', '2025-11-23 04:21:29'),
('TEST-PROJ-1763871745', 'Test Project', 'ongoing', 'Test Donor', 100000.00, '2025-11-23', '2025-11-23', NULL, '2025-11-23 04:22:25', '2025-11-23 04:22:25'),
('TEST-PROJ-1763871759', 'Test Project', 'ongoing', 'Test Donor', 100000.00, '2025-11-23', '2025-11-23', NULL, '2025-11-23 04:22:39', '2025-11-23 04:22:39'),
('TEST-PROJ-1763871852', 'Test Project', 'ongoing', 'Test Donor', 100000.00, '2025-11-23', '2025-11-23', NULL, '2025-11-23 04:24:12', '2025-11-23 04:24:12'),
('TEST-PROJ-1763871864', 'Test Project', 'ongoing', 'Test Donor', 100000.00, '2025-11-23', '2025-11-23', NULL, '2025-11-23 04:24:24', '2025-11-23 04:24:24'),
('TEST-PROJ-1763871881', 'Test Project', 'ongoing', 'Test Donor', 100000.00, '2025-11-23', '2025-11-23', NULL, '2025-11-23 04:24:41', '2025-11-23 04:24:41'),
('TEST-PROJ-1763871898', 'Test Project', 'ongoing', 'Test Donor', 100000.00, '2025-11-23', '2025-11-23', NULL, '2025-11-23 04:24:58', '2025-11-23 04:24:58'),
('TEST-PROJ-1763871917', 'Test Project', 'ongoing', 'Test Donor', 100000.00, '2025-11-23', '2025-11-23', NULL, '2025-11-23 04:25:17', '2025-11-23 04:25:17'),
('TEST-PROJ-1763871941', 'Test Project', 'ongoing', 'Test Donor', 100000.00, '2025-11-23', '2025-11-23', NULL, '2025-11-23 04:25:41', '2025-11-23 04:25:41'),
('TEST-PROJ-1763871982', 'Test Project', 'ongoing', 'Test Donor', 100000.00, '2025-11-23', '2025-11-23', NULL, '2025-11-23 04:26:22', '2025-11-23 04:26:22'),
('TEST-PROJ-1763872018', 'Test Project', 'ongoing', 'Test Donor', 100000.00, '2025-11-23', '2025-11-23', NULL, '2025-11-23 04:26:58', '2025-11-23 04:26:58'),
('TEST-PROJ-1763873014', 'Test Project', 'ongoing', 'Test Donor', 100000.00, '2025-11-23', '2025-11-23', NULL, '2025-11-23 04:43:34', '2025-11-23 04:43:34');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `role` enum('Admin','Finance Manager','Project Manager','Staff Accountant','Direktur') NOT NULL,
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

INSERT INTO `user` (`id_user`, `nama`, `role`, `email`, `no_HP`, `password_hash`, `created_at`, `updated_at`, `last_notification_check`) VALUES
(1, 'Chandra PM', 'Project Manager', '12345c4n12345@gmail.com', '6283153505411', '$2y$10$r.zr5s5a3m9qJ3kOdCxm3.G..LqNiy9upbO0oZJUuAuBC8ee8tSC6', '2025-10-16 06:36:23', '2025-11-23 14:15:29', '2025-10-28 16:42:54'),
(4, 'Ferrosi FM', 'Finance Manager', 'ferrosipratamaq@gmail.com', '6289521340602', '$2y$10$4Dc7kmGgHY5WxZDfGw2zm.KIegC1xrX7GirUlXyj40Rl4/RzsopZu', '2025-10-16 06:43:55', '2025-11-23 14:15:21', '2025-11-23 13:49:30'),
(5, 'zheamanda FM', 'Finance Manager', 'zheaamandavitaloka@gmail.com', '6283836609877', '$2y$10$aYQPYZIRplq3lYsN4s4TrOtR.UPafFF7zDCS/pF30MkABJ5G/Z/CW', '2025-10-16 06:50:22', '2025-11-23 14:15:12', NULL),
(8, 'Ferrosi SA', 'Staff Accountant', 'ferrosipratamaqu@gmail.com', '6282134812641', '$2y$10$c.kiil8chyMEiAqZgzpR7u5sqVVftQIJJLyKIckfatqprVRXNLuqO', '2025-10-19 13:22:11', '2025-11-23 14:06:11', NULL),
(9, 'lutfi', 'Admin', 'lutfifirdaus238@gmail.com', '6285752706608', '$2y$10$2fH773o5wxutvRZtlEKm2O/PNR6riXAOfdta1jT26dKhKyJnDFqnS', '2025-10-20 01:02:46', '2025-11-23 05:06:57', NULL),
(11, 'lutfi dir', 'Direktur', 'mulfis@googlegroups.com', '085752706607', '$2y$10$9RqVBR.dhGhzT.Puz9CL1Od.WJXEAvFGO5Gje//Deh5EixSu2g3Fu', '2025-11-23 14:05:43', '2025-11-23 14:06:02', NULL),
(12, 'lutfi SA', 'Staff Accountant', 'lutfifirdaus236@gmail.com', '085752706606', '$2y$10$gouMKMgBSEnFAeCbEjk/0uRElcrfBAYHh9T0fJyk9iT1d3GsGKREC', '2025-11-23 14:06:54', '2025-11-23 14:06:54', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `villages`
--

CREATE TABLE `villages` (
  `id_village` int(11) NOT NULL,
  `village_code` varchar(10) NOT NULL COMMENT 'Kode internal desa, misal: V001',
  `village_name` varchar(100) NOT NULL COMMENT 'Nama lengkap desa',
  `village_abbr` varchar(5) NOT NULL COMMENT 'Singkatan untuk Place Code, misal: NJ, SW, PR',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Master data desa untuk budget allocation';

--
-- Dumping data for table `villages`
--

INSERT INTO `villages` (`id_village`, `village_code`, `village_name`, `village_abbr`, `description`, `created_at`, `updated_at`) VALUES
(1, 'V001', 'Nanga Jemah', 'NJ', 'Desa Nanga Jemah', '2025-11-23 03:58:07', '2025-11-23 03:58:07'),
(2, 'V002', 'Sri Wangi', 'SW', 'Desa Sri Wangi', '2025-11-23 03:58:07', '2025-11-23 03:58:07'),
(3, 'V003', 'Penepian Raya', 'PR', 'Desa Penepian Raya', '2025-11-23 03:58:07', '2025-11-23 03:58:07'),
(4, 'V004', 'Tanjung Jaya', 'TJ', 'Desa Tanjung Jaya', '2025-11-23 03:58:07', '2025-11-23 03:58:07'),
(5, 'V005', 'Riam Jaya', 'RJ', 'Desa Riam Jaya', '2025-11-23 03:58:07', '2025-11-23 03:58:07'),
(999, 'TEST', 'Test Village', 'TV', NULL, '2025-11-23 04:21:15', '2025-11-23 04:21:15'),
(1002, '1003', 'Nanga Lauk', 'NL', NULL, '2025-11-23 04:57:56', '2025-11-23 04:57:56'),
(1003, '1004', 'Tanjung Kapuas', 'TK', NULL, '2025-11-23 04:57:56', '2025-11-23 04:57:56'),
(1004, '1005', 'Nanga Betung', 'NB', NULL, '2025-11-23 04:57:56', '2025-11-23 04:57:56');

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
-- Indexes for table `project_code_budgets`
--
ALTER TABLE `project_code_budgets`
  ADD PRIMARY KEY (`id_budget`),
  ADD UNIQUE KEY `unique_budget_per_village` (`kode_proyek`,`id_village`,`exp_code`),
  ADD KEY `id_village` (`id_village`),
  ADD KEY `idx_place_code` (`place_code`);

--
-- Indexes for table `proposal`
--
ALTER TABLE `proposal`
  ADD PRIMARY KEY (`id_proposal`),
  ADD KEY `kode_proyek` (`kode_proyek`),
  ADD KEY `fk_proposal_fm` (`approved_by_fm`);

--
-- Indexes for table `proposal_budget_details`
--
ALTER TABLE `proposal_budget_details`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_village` (`id_village`),
  ADD KEY `idx_proposal_budget` (`id_proposal`);

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
-- Indexes for table `villages`
--
ALTER TABLE `villages`
  ADD PRIMARY KEY (`id_village`),
  ADD UNIQUE KEY `village_code` (`village_code`),
  ADD UNIQUE KEY `village_abbr` (`village_abbr`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buku_piutang_detail`
--
ALTER TABLE `buku_piutang_detail`
  MODIFY `id_detail_piutang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `buku_piutang_header`
--
ALTER TABLE `buku_piutang_header`
  MODIFY `id_piutang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `buku_piutang_unliquidated`
--
ALTER TABLE `buku_piutang_unliquidated`
  MODIFY `id_unliquidate` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
-- AUTO_INCREMENT for table `project_code_budgets`
--
ALTER TABLE `project_code_budgets`
  MODIFY `id_budget` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `proposal`
--
ALTER TABLE `proposal`
  MODIFY `id_proposal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `proposal_budget_details`
--
ALTER TABLE `proposal_budget_details`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `villages`
--
ALTER TABLE `villages`
  MODIFY `id_village` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1005;

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
-- Constraints for table `project_code_budgets`
--
ALTER TABLE `project_code_budgets`
  ADD CONSTRAINT `project_code_budgets_ibfk_1` FOREIGN KEY (`kode_proyek`) REFERENCES `proyek` (`kode_proyek`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_code_budgets_ibfk_2` FOREIGN KEY (`id_village`) REFERENCES `villages` (`id_village`);

--
-- Constraints for table `proposal`
--
ALTER TABLE `proposal`
  ADD CONSTRAINT `fk_proposal_fm` FOREIGN KEY (`approved_by_fm`) REFERENCES `user` (`id_user`) ON DELETE SET NULL,
  ADD CONSTRAINT `proposal_ibfk_1` FOREIGN KEY (`kode_proyek`) REFERENCES `proyek` (`kode_proyek`) ON DELETE SET NULL;

--
-- Constraints for table `proposal_budget_details`
--
ALTER TABLE `proposal_budget_details`
  ADD CONSTRAINT `proposal_budget_details_ibfk_1` FOREIGN KEY (`id_proposal`) REFERENCES `proposal` (`id_proposal`) ON DELETE CASCADE,
  ADD CONSTRAINT `proposal_budget_details_ibfk_2` FOREIGN KEY (`id_village`) REFERENCES `villages` (`id_village`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
