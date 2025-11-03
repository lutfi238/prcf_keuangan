-- PostgreSQL Schema for PRCF Keuangan
-- Converted from MySQL

-- Create database (if not exists)
-- Note: In Vercel Postgres, database is created automatically

-- Users table
CREATE TABLE IF NOT EXISTS "user" (
  id_user SERIAL PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  no_hp VARCHAR(20),
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(50) NOT NULL CHECK (role IN ('Admin', 'Project Manager', 'Staff Accountant', 'Finance Manager', 'Direktur')),
  status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'pending')),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Projects table
CREATE TABLE IF NOT EXISTS projects (
  kode_proyek VARCHAR(20) PRIMARY KEY,
  nama_proyek VARCHAR(200) NOT NULL,
  deskripsi TEXT,
  status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'completed')),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Buku Bank Header table
CREATE TABLE IF NOT EXISTS buku_bank_header (
  id_bank_header VARCHAR(30) PRIMARY KEY,
  kode_proyek VARCHAR(20) REFERENCES projects(kode_proyek),
  account_name VARCHAR(150) NOT NULL,
  bank_name VARCHAR(100) NOT NULL,
  account_number VARCHAR(50) NOT NULL,
  exrate DECIMAL(12,2) DEFAULT 1.00,
  currency VARCHAR(10) NOT NULL,
  periode_bulan CHAR(2) NOT NULL,
  periode_tahun CHAR(4) NOT NULL,
  saldo_awal_idr DECIMAL(18,2) DEFAULT 0.00,
  saldo_awal_usd DECIMAL(18,2) DEFAULT 0.00,
  current_period_change_idr DECIMAL(18,2) DEFAULT 0.00,
  current_period_change_usd DECIMAL(18,2) DEFAULT 0.00,
  saldo_akhir_idr DECIMAL(18,2) DEFAULT 0.00,
  saldo_akhir_usd DECIMAL(18,2) DEFAULT 0.00,
  prepared_by VARCHAR(100),
  approved_by VARCHAR(100),
  status_laporan VARCHAR(20) DEFAULT 'draft' CHECK (status_laporan IN ('draft', 'submitted', 'approved')),
  tanggal_pembuatan DATE DEFAULT CURRENT_DATE,
  tanggal_persetujuan DATE
);

-- Buku Bank Detail table
CREATE TABLE IF NOT EXISTS buku_bank_detail (
  id_detail_bank VARCHAR(30) PRIMARY KEY,
  id_bank_header VARCHAR(30) REFERENCES buku_bank_header(id_bank_header),
  tanggal DATE NOT NULL,
  reff VARCHAR(50),
  title_activity VARCHAR(150),
  cost_description TEXT,
  recipient VARCHAR(100),
  place_code VARCHAR(20),
  exp_code VARCHAR(20),
  nominal_code VARCHAR(20),
  exrate DECIMAL(12,2),
  cost_curr VARCHAR(10),
  debit_idr DECIMAL(18,2) DEFAULT 0.00,
  debit_usd DECIMAL(18,2) DEFAULT 0.00,
  credit_idr DECIMAL(18,2) DEFAULT 0.00,
  credit_usd DECIMAL(18,2) DEFAULT 0.00,
  balance_idr DECIMAL(18,2) DEFAULT 0.00,
  balance_usd DECIMAL(18,2) DEFAULT 0.00,
  status VARCHAR(20) DEFAULT 'ongoing' CHECK (status IN ('ongoing', 'final'))
);

-- Buku Piutang Header table
CREATE TABLE IF NOT EXISTS buku_piutang_header (
  id_piutang_header VARCHAR(30) PRIMARY KEY,
  kode_proyek VARCHAR(20) REFERENCES projects(kode_proyek),
  account_name VARCHAR(150) NOT NULL,
  periode_bulan CHAR(2) NOT NULL,
  periode_tahun CHAR(4) NOT NULL,
  saldo_awal DECIMAL(18,2) DEFAULT 0.00,
  current_period_change DECIMAL(18,2) DEFAULT 0.00,
  saldo_akhir DECIMAL(18,2) DEFAULT 0.00,
  prepared_by VARCHAR(100),
  approved_by VARCHAR(100),
  status_laporan VARCHAR(20) DEFAULT 'draft' CHECK (status_laporan IN ('draft', 'submitted', 'approved')),
  tanggal_pembuatan DATE DEFAULT CURRENT_DATE,
  tanggal_persetujuan DATE
);

-- Buku Piutang Detail table
CREATE TABLE IF NOT EXISTS buku_piutang_detail (
  id_detail_piutang VARCHAR(30) PRIMARY KEY,
  id_piutang_header VARCHAR(30) REFERENCES buku_piutang_header(id_piutang_header),
  tanggal DATE NOT NULL,
  reff VARCHAR(50),
  title_activity VARCHAR(150),
  description TEXT,
  recipient VARCHAR(100),
  place_code VARCHAR(20),
  exp_code VARCHAR(20),
  nominal_code VARCHAR(20),
  debit DECIMAL(18,2) DEFAULT 0.00,
  credit DECIMAL(18,2) DEFAULT 0.00,
  balance DECIMAL(18,2) DEFAULT 0.00,
  status VARCHAR(20) DEFAULT 'ongoing' CHECK (status IN ('ongoing', 'final'))
);

-- Proposals table
CREATE TABLE IF NOT EXISTS proposals (
  id_proposal VARCHAR(30) PRIMARY KEY,
  kode_proyek VARCHAR(20) REFERENCES projects(kode_proyek),
  judul_proposal VARCHAR(200) NOT NULL,
  deskripsi TEXT,
  jumlah_diajukan DECIMAL(18,2) NOT NULL,
  mata_uang VARCHAR(10) DEFAULT 'IDR',
  status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft', 'submitted', 'approved_pm', 'approved_fm', 'approved_dir', 'rejected')),
  submitted_by INTEGER REFERENCES "user"(id_user),
  approved_by_pm INTEGER REFERENCES "user"(id_user),
  approved_by_fm INTEGER REFERENCES "user"(id_user),
  approved_by_dir INTEGER REFERENCES "user"(id_user),
  tanggal_submit DATE,
  tanggal_approve_pm DATE,
  tanggal_approve_fm DATE,
  tanggal_approve_dir DATE,
  komentar_pm TEXT,
  komentar_fm TEXT,
  komentar_dir TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reports table
CREATE TABLE IF NOT EXISTS reports (
  id_report VARCHAR(30) PRIMARY KEY,
  kode_proyek VARCHAR(20) REFERENCES projects(kode_proyek),
  jenis_laporan VARCHAR(50) NOT NULL,
  periode_bulan CHAR(2) NOT NULL,
  periode_tahun CHAR(4) NOT NULL,
  status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft', 'submitted', 'approved_sa', 'approved_fm', 'approved_dir', 'rejected')),
  submitted_by INTEGER REFERENCES "user"(id_user),
  approved_by_sa INTEGER REFERENCES "user"(id_user),
  approved_by_fm INTEGER REFERENCES "user"(id_user),
  approved_by_dir INTEGER REFERENCES "user"(id_user),
  tanggal_submit DATE,
  tanggal_approve_sa DATE,
  tanggal_approve_fm DATE,
  tanggal_approve_dir DATE,
  komentar_sa TEXT,
  komentar_fm TEXT,
  komentar_dir TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
  id_notification SERIAL PRIMARY KEY,
  user_id INTEGER REFERENCES "user"(id_user),
  title VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  type VARCHAR(50) DEFAULT 'info',
  is_read BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Place Codes table
CREATE TABLE IF NOT EXISTS place_codes (
  place_code VARCHAR(20) PRIMARY KEY,
  place_name VARCHAR(100) NOT NULL,
  description TEXT,
  status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive'))
);

-- Expense Codes table
CREATE TABLE IF NOT EXISTS expense_codes (
  exp_code VARCHAR(20) PRIMARY KEY,
  exp_name VARCHAR(100) NOT NULL,
  description TEXT,
  status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive'))
);

-- Indexes for better performance
CREATE INDEX IF NOT EXISTS idx_user_email ON "user"(email);
CREATE INDEX IF NOT EXISTS idx_user_role ON "user"(role);
CREATE INDEX IF NOT EXISTS idx_proposals_status ON proposals(status);
CREATE INDEX IF NOT EXISTS idx_reports_status ON reports(status);
CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_buku_bank_header_project ON buku_bank_header(kode_proyek);
CREATE INDEX IF NOT EXISTS idx_buku_piutang_header_project ON buku_piutang_header(kode_proyek);
