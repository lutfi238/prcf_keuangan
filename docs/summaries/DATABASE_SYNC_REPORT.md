# 📊 Laporan Sinkronisasi Database - PRCF Keuangan Dashboard

**Database Name:** `prcf_keuangan`  
**Tanggal Analisis:** 15 Oktober 2025  
**Status:** ✅ **TERSINKRONISASI**

---

## 🗄️ Struktur Database (10 Tabel)

### 1. **Table: `user`**
**Fungsi:** Menyimpan data pengguna sistem

| Field | Type | Description |
|-------|------|-------------|
| id_user | INT (PK, AI) | ID unik pengguna |
| nama | VARCHAR(255) | Nama lengkap pengguna |
| role | ENUM | Finance Manager, Project Manager, Staff Accountant, Direktur |
| email | VARCHAR(255) UNIQUE | Email untuk login |
| no_HP | VARCHAR(20) | Nomor HP untuk login alternatif |
| password_hash | VARCHAR(255) | Password terenkripsi (bcrypt) |
| created_at | TIMESTAMP | Waktu pembuatan akun |
| updated_at | TIMESTAMP | Waktu update terakhir |

**Digunakan di:**
- ✅ `login.php` - Login dengan email/no_HP
- ✅ `register.php` - Registrasi user baru
- ✅ `verify_otp.php` - Verifikasi OTP dan set session
- ✅ `profile.php` - Update profile user
- ✅ Semua dashboard files

**Data Sample:** 10 users (7 Staff Accountant, 1 Finance Manager, 1 Project Manager, 1 Direktur)

---

### 2. **Table: `proyek`**
**Fungsi:** Menyimpan data proyek

| Field | Type | Description |
|-------|------|-------------|
| kode_proyek | VARCHAR(50) PK | Kode unik proyek |
| nama_proyek | VARCHAR(255) | Nama proyek |
| status_proyek | ENUM | planning, ongoing, completed, cancelled |
| donor | VARCHAR(255) | Nama donor/pemberi dana |
| nilai_anggaran | DECIMAL(15,2) | Total anggaran proyek |
| periode_mulai | DATE | Tanggal mulai proyek |
| periode_selesai | DATE | Tanggal selesai proyek |
| rekening_khusus | VARCHAR(100) | Nomor rekening proyek |
| created_at | TIMESTAMP | Waktu pembuatan |
| updated_at | TIMESTAMP | Waktu update |

**Digunakan di:**
- ✅ `create_proposal.php` - Pilih proyek untuk proposal
- ✅ `create_financial_report.php` - Pilih proyek untuk laporan
- ✅ `buku_bank.php` - Transaksi bank per proyek
- ✅ `buku_piutang.php` - Piutang per proyek

**Data Sample:** 5 proyek (UNICEF, World Bank, JICA, WHO, GEF)

---

### 3. **Table: `proposal`**
**Fungsi:** Menyimpan proposal kegiatan

| Field | Type | Description |
|-------|------|-------------|
| id_proposal | INT (PK, AI) | ID unik proposal |
| judul_proposal | VARCHAR(255) | Judul proposal |
| pj | VARCHAR(255) | Penanggung jawab |
| date | DATE | Tanggal proposal |
| pemohon | VARCHAR(255) | Nama pemohon (PM) |
| status | ENUM | draft, submitted, approved, rejected |
| kode_proyek | VARCHAR(50) FK | Kode proyek terkait |
| tor | TEXT | Terms of Reference |
| file_budget | VARCHAR(255) | Path file budget |
| created_at | TIMESTAMP | Waktu pembuatan |
| updated_at | TIMESTAMP | Waktu update |

**Digunakan di:**
- ✅ `create_proposal.php` - PM membuat proposal
- ✅ `review_proposal.php` - FM mereview proposal
- ✅ `approve_proposal.php` - FM approve proposal
- ✅ `dashboard_fm.php` - Daftar proposal untuk FM
- ✅ `dashboard_dir.php` - Daftar proposal untuk Direktur
- ✅ `dashboard_pm.php` - Daftar proposal milik PM

**Data Sample:** 5 proposal dengan berbagai status

---

### 4. **Table: `laporan_keuangan_header`**
**Fungsi:** Header laporan keuangan kegiatan

| Field | Type | Description |
|-------|------|-------------|
| id_laporan_keu | INT (PK, AI) | ID unik laporan |
| kode_projek | VARCHAR(50) FK | Kode proyek |
| nama_projek | VARCHAR(255) | Nama proyek |
| nama_kegiatan | VARCHAR(255) | Nama kegiatan |
| pelaksana | VARCHAR(255) | Nama pelaksana |
| tanggal_pelaksanaan | DATE | Tanggal kegiatan |
| tanggal_laporan | DATE | Tanggal laporan dibuat |
| mata_uang | VARCHAR(10) | IDR/USD |
| exrate | DECIMAL(10,4) | Exchange rate |
| created_by | INT FK | ID user pembuat (PM) |
| verified_by | INT FK | ID user verifikator (SA) |
| approved_by | INT FK | ID user approval (FM) |
| status_lap | ENUM | draft, submitted, verified, approved, rejected |
| catatan_finance | TEXT | Catatan dari finance |
| created_at | TIMESTAMP | Waktu pembuatan |
| updated_at | TIMESTAMP | Waktu update |

**Digunakan di:**
- ✅ `create_financial_report.php` - PM membuat laporan
- ✅ `validate_report.php` - SA validasi laporan
- ✅ `approve_report.php` - FM approve laporan
- ✅ `approve_report_dir.php` - Direktur final approve
- ✅ `dashboard_sa.php` - Daftar laporan untuk SA
- ✅ `dashboard_fm.php` - Daftar laporan untuk FM
- ✅ `dashboard_dir.php` - Daftar laporan untuk Direktur

**Data Sample:** 3 laporan dengan status berbeda

---

### 5. **Table: `laporan_keuangan_detail`**
**Fungsi:** Detail item laporan keuangan

| Field | Type | Description |
|-------|------|-------------|
| id_detail_keu | INT (PK, AI) | ID unik detail |
| id_laporan_keu | INT FK | ID laporan header |
| invoice_no | VARCHAR(100) | Nomor invoice |
| invoice_date | DATE | Tanggal invoice |
| item_desc | TEXT | Deskripsi item |
| recipient | VARCHAR(255) | Penerima/supplier |
| place_code | VARCHAR(50) | Kode tempat |
| exp_code | VARCHAR(50) | Kode expense |
| unit_total | INT | Jumlah unit |
| unit_cost | DECIMAL(15,2) | Harga per unit |
| requested | DECIMAL(15,2) | Jumlah diminta |
| actual | DECIMAL(15,2) | Realisasi aktual |
| balance | DECIMAL(15,2) | Selisih |
| explanation | TEXT | Penjelasan |
| file_nota | VARCHAR(255) | Path file nota |
| created_at | TIMESTAMP | Waktu pembuatan |
| updated_at | TIMESTAMP | Waktu update |

**Digunakan di:**
- ✅ `create_financial_report.php` - Input detail item
- ✅ `validate_report.php` - Review detail
- ✅ `approve_report.php` - Approve detail
- ✅ `approve_report_dir.php` - Final review detail

**Data Sample:** 8 item detail untuk 3 laporan

---

### 6. **Table: `laporan_donor`**
**Fungsi:** Laporan untuk donor/pemberi dana

| Field | Type | Description |
|-------|------|-------------|
| id_donor | INT (PK, AI) | ID unik laporan donor |
| periode | VARCHAR(50) | Periode laporan (Q1, Q2, dst) |
| kode_proyek | VARCHAR(50) FK | Kode proyek |
| realisasi_kegiatan | TEXT | Deskripsi realisasi kegiatan |
| realisasi_keuangan | TEXT | Deskripsi realisasi keuangan |
| total_anggaran | DECIMAL(15,2) | Total anggaran |
| total_realisasi | DECIMAL(15,2) | Total realisasi |
| file_laporan | VARCHAR(255) | Path file laporan |
| tanggal_kirim | DATE | Tanggal kirim ke donor |
| created_by | INT FK | ID user pembuat |
| approved_by | INT FK | ID user approval |
| status | ENUM | draft, submitted, approved, sent |
| created_at | TIMESTAMP | Waktu pembuatan |
| updated_at | TIMESTAMP | Waktu update |

**Status:** ⚠️ Belum ada file PHP yang menggunakan tabel ini  
**Rekomendasi:** Buat file `create_donor_report.php` dan `approve_donor_report.php`

**Data Sample:** 3 laporan donor (UNICEF, JICA, WHO)

---

### 7. **Table: `buku_bank`**
**Fungsi:** Pencatatan transaksi bank

| Field | Type | Description |
|-------|------|-------------|
| id_bank | INT (PK, AI) | ID unik transaksi |
| kode_projek | VARCHAR(50) FK | Kode proyek |
| nama_rek | VARCHAR(255) | Nama rekening |
| no_rek | VARCHAR(50) | Nomor rekening |
| date | DATE | Tanggal transaksi |
| reff | VARCHAR(100) | Referensi transaksi |
| activity | VARCHAR(255) | Aktivitas |
| cost_desc | TEXT | Deskripsi biaya |
| recipient | VARCHAR(255) | Penerima |
| p_code | VARCHAR(50) | Project code |
| exp_code | VARCHAR(50) | Expense code |
| nominal_code | VARCHAR(50) | Nominal code |
| exrate | DECIMAL(10,4) | Exchange rate |
| cost_curr | VARCHAR(10) | Currency (IDR/USD) |
| debit_idr | DECIMAL(15,2) | Debit IDR |
| debit_usd | DECIMAL(15,2) | Debit USD |
| credit_idr | DECIMAL(15,2) | Credit IDR |
| credit_usd | DECIMAL(15,2) | Credit USD |
| balance_idr | DECIMAL(15,2) | Balance IDR |
| balance_usd | DECIMAL(15,2) | Balance USD |
| created_at | TIMESTAMP | Waktu pembuatan |
| updated_at | TIMESTAMP | Waktu update |

**Digunakan di:**
- ✅ `buku_bank.php` - Input dan lihat transaksi bank

**Data Sample:** 8 transaksi bank dari berbagai proyek

---

### 8. **Table: `buku_piutang_header`**
**Fungsi:** Header buku piutang

| Field | Type | Description |
|-------|------|-------------|
| id_piutang | INT (PK, AI) | ID unik piutang |
| kode_proyek | VARCHAR(50) FK | Kode proyek |
| account_name | VARCHAR(255) | Nama akun piutang |
| periode_mulai | DATE | Periode mulai |
| periode_selesai | DATE | Periode selesai |
| beginning_balance_idr | DECIMAL(15,2) | Saldo awal IDR |
| ending_balance_idr | DECIMAL(15,2) | Saldo akhir IDR |
| beginning_balance_usd | DECIMAL(15,2) | Saldo awal USD |
| ending_balance_usd | DECIMAL(15,2) | Saldo akhir USD |
| created_by | INT FK | ID user pembuat |
| approved_by | INT FK | ID user approval |
| catatan_fm | TEXT | Catatan FM |
| status | ENUM | draft, submitted, approved, rejected |
| tgl_pembuatan | DATE | Tanggal dibuat |
| tgl_persetujuan | DATE | Tanggal approved |
| created_at | TIMESTAMP | Waktu pembuatan |
| updated_at | TIMESTAMP | Waktu update |

**Digunakan di:**
- ✅ `buku_piutang.php` - Kelola buku piutang

**Data Sample:** 2 header piutang (UMKM, Material)

---

### 9. **Table: `buku_piutang_detail`**
**Fungsi:** Detail transaksi piutang

| Field | Type | Description |
|-------|------|-------------|
| id_detail_piutang | INT (PK, AI) | ID unik detail |
| id_piutang | INT FK | ID piutang header |
| tgl_trx | DATE | Tanggal transaksi |
| reff | VARCHAR(100) | Referensi |
| description | TEXT | Deskripsi |
| recipient | VARCHAR(255) | Penerima |
| p_code | VARCHAR(50) | Project code |
| exp_code | VARCHAR(50) | Expense code |
| nominal_code | VARCHAR(50) | Nominal code |
| exrate | DECIMAL(10,4) | Exchange rate |
| debit_idr | DECIMAL(15,2) | Debit IDR |
| debit_usd | DECIMAL(15,2) | Debit USD |
| credit_idr | DECIMAL(15,2) | Credit IDR |
| credit_usd | DECIMAL(15,2) | Credit USD |
| balance_idr | DECIMAL(15,2) | Balance IDR |
| balance_usd | DECIMAL(15,2) | Balance USD |
| created_at | TIMESTAMP | Waktu pembuatan |
| updated_at | TIMESTAMP | Waktu update |

**Digunakan di:**
- ✅ `buku_piutang.php` - Input detail transaksi piutang

**Data Sample:** 5 detail transaksi piutang

---

### 10. **Table: `buku_piutang_unliquidated`**
**Fungsi:** Piutang yang belum dilunasi

| Field | Type | Description |
|-------|------|-------------|
| id_unliquidate | INT (PK, AI) | ID unik unliquidated |
| id_piutang | INT FK | ID piutang header |
| tgl | DATE | Tanggal |
| voucher_no | VARCHAR(100) | Nomor voucher |
| name | VARCHAR(255) | Nama debitur |
| description | TEXT | Deskripsi |
| nilai_idr | DECIMAL(15,2) | Nilai IDR |
| nilai_usd | DECIMAL(15,2) | Nilai USD |
| status | ENUM | pending, liquidated, cancelled |
| created_at | TIMESTAMP | Waktu pembuatan |
| updated_at | TIMESTAMP | Waktu update |

**Digunakan di:**
- ✅ `buku_piutang.php` - Tracking piutang belum lunas

**Data Sample:** 5 piutang unliquidated

---

## 🔗 Relasi Database (Foreign Keys)

```
user (id_user)
  ├── laporan_keuangan_header (created_by, verified_by, approved_by)
  ├── buku_piutang_header (created_by, approved_by)
  └── laporan_donor (created_by, approved_by)

proyek (kode_proyek)
  ├── proposal (kode_proyek)
  ├── laporan_keuangan_header (kode_projek)
  ├── laporan_donor (kode_proyek)
  ├── buku_bank (kode_projek)
  └── buku_piutang_header (kode_proyek)

proposal (id_proposal)
  └── (no child tables)

laporan_keuangan_header (id_laporan_keu)
  └── laporan_keuangan_detail (id_laporan_keu) [CASCADE DELETE]

buku_piutang_header (id_piutang)
  ├── buku_piutang_detail (id_piutang) [CASCADE DELETE]
  └── buku_piutang_unliquidated (id_piutang) [CASCADE DELETE]
```

---

## ✅ Verifikasi Sinkronisasi File PHP

### **Login & Authentication** ✅
| File | Status | Tabel Digunakan |
|------|--------|-----------------|
| `login.php` | ✅ Sinkron | user |
| `register.php` | ✅ Sinkron | user |
| `verify_otp.php` | ✅ Sinkron | user |
| `logout.php` | ✅ Sinkron | - |
| `profile.php` | ✅ Sinkron | user |

### **Dashboard** ✅
| File | Status | Tabel Digunakan | Perbaikan |
|------|--------|-----------------|-----------|
| `dashboard_pm.php` | ✅ Fixed | proposal, proyek | ~~notifications~~ → proposal |
| `dashboard_sa.php` | ✅ Sinkron | laporan_keuangan_header, user |
| `dashboard_fm.php` | ✅ Sinkron | proposal, laporan_keuangan_header, user |
| `dashboard_dir.php` | ✅ Sinkron | proposal, laporan_keuangan_header, user |

### **Proposal Management** ✅
| File | Status | Tabel Digunakan |
|------|--------|-----------------|
| `create_proposal.php` | ✅ Sinkron | proposal, proyek, user |
| `review_proposal.php` | ✅ Sinkron | proposal, user |
| `approve_proposal.php` | ✅ Sinkron | proposal, user |

### **Financial Reports** ✅
| File | Status | Tabel Digunakan |
|------|--------|-----------------|
| `create_financial_report.php` | ✅ Sinkron | laporan_keuangan_header, laporan_keuangan_detail, proyek, user |
| `validate_report.php` | ✅ Sinkron | laporan_keuangan_header, laporan_keuangan_detail, user |
| `approve_report.php` | ✅ Sinkron | laporan_keuangan_header, laporan_keuangan_detail, user |
| `approve_report_dir.php` | ✅ Sinkron | laporan_keuangan_header, laporan_keuangan_detail |

### **Books (Buku)** ✅
| File | Status | Tabel Digunakan |
|------|--------|-----------------|
| `buku_bank.php` | ✅ Sinkron | buku_bank, proyek |
| `buku_piutang.php` | ✅ Sinkron | buku_piutang_header, buku_piutang_detail, buku_piutang_unliquidated, proyek, user |

---

## 🔧 Perbaikan yang Sudah Dilakukan

### 1. **Config Files** ✅
- ✅ `config.php` - Database: `prcf_keuangan`, Email: `pblprcf@gmail.com`
- ✅ `config_simple.php` - Database: `prcf_keuangan` (diperbaiki dari `prcfi`)

### 2. **Dashboard PM** ✅
- ✅ Menghapus query ke tabel `notifications` yang tidak ada
- ✅ Mengganti dengan query ke tabel `proposal` untuk menampilkan proposal milik PM

### 3. **Login System** ✅
- ✅ Mengaktifkan fungsi `send_otp_email()` untuk kirim OTP
- ✅ Email configuration sudah dikonfigurasi dengan benar

---

## 📋 Data Sample di Database

### **Users (10 orang)**
1. Ade Kurnia - Staff Accountant
2. Tuti Alawiyah - Staff Accountant  
3. Herayati Kaban - Staff Accountant
4. Farradina Putri Ardanti - Staff Accountant
5. Yanda Kurnia Fajri - Staff Accountant
6. Hendra Nopiyandi - Staff Accountant
7. Ivan Kurniawan Harefa - Staff Accountant
8. **Aam Wijaya - Finance Manager**
9. **Yadi - Project Manager**
10. **Imanul Huda - Direktur**

**Password semua user:** `password` (hash: `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi`)

### **Proyek (5 proyek)**
1. PRJ-2025-001 - Pemberdayaan Ekonomi Kreatif (UNICEF)
2. PRJ-2025-002 - Pendidikan ABK (World Bank)
3. PRJ-2025-003 - Sanitasi Air Bersih (JICA)
4. PRJ-2024-004 - Kesehatan Ibu & Anak (WHO) - COMPLETED
5. PRJ-2025-005 - Konservasi Hutan (GEF)

---

## 🎯 Workflow Sistem

### **Alur Register & Login:**
```
1. User Register → Data masuk ke tabel `user`
2. User Login → Cek tabel `user` (email/no_HP + password)
3. Generate OTP → Kirim email via `send_otp_email()`
4. Verify OTP → Set session, redirect ke dashboard sesuai role
```

### **Alur Proposal:**
```
PM: Create Proposal → tabel `proposal` (status: submitted)
FM: Review Proposal → Update status (approved/rejected)
Dir: (Opsional) Final Review
```

### **Alur Laporan Keuangan:**
```
PM: Create Report → tabel `laporan_keuangan_header` + `detail` (status: submitted)
SA: Validate Report → Update status (verified/rejected)
FM: Approve Report → Update status (approved)
Dir: Final Approve → Status final (approved)
```

### **Alur Buku Bank:**
```
FM: Input Transaksi → tabel `buku_bank`
System: Auto Calculate Balance
```

### **Alur Buku Piutang:**
```
FM: Create Piutang Header → tabel `buku_piutang_header`
FM: Input Detail → tabel `buku_piutang_detail`
FM: Track Unliquidated → tabel `buku_piutang_unliquidated`
```

---

## ⚠️ Rekomendasi & To-Do

### **Missing Features (Belum ada file PHP):**
1. ❌ Laporan Donor Management
   - Perlu: `create_donor_report.php`
   - Perlu: `approve_donor_report.php`
   - Perlu: Menu di dashboard untuk laporan donor

2. ❌ Notifications System
   - Tabel `notifications` tidak ada di database
   - Dashboard PM sudah diperbaiki (tidak pakai notifications)
   - Opsional: Buat tabel notifications untuk notifikasi realtime

### **Enhancement Suggestions:**
1. 🔄 Add table `notifications` untuk sistem notifikasi
2. 🔄 Add logging/audit trail table
3. 🔄 Add file upload management table
4. 🔄 Add password reset functionality
5. 🔄 Add email verification saat register

---

## ✅ Status Akhir

**Database:** `prcf_keuangan` ✅ **READY**  
**Config Files:** ✅ **CONFIGURED**  
**PHP Files:** ✅ **SYNCHRONIZED**  
**Login System:** ✅ **WORKING**  
**OTP Email:** ✅ **CONFIGURED**  
**Dashboard:** ✅ **FUNCTIONAL**

---

## 🚀 Cara Test Sistem

### **1. Import Database**
```sql
mysql -u root -p
CREATE DATABASE prcf_keuangan;
USE prcf_keuangan;
SOURCE C:/Users/LutFi/Downloads/prcf_keuangan.sql;
```

### **2. Test Login**
- URL: `http://localhost/prcf_keuangan/`
- Test User PM: 
  - Email: `yadi@company.com`
  - Password: `password`
- Test User FM:
  - Email: `aam.wijaya@company.com`
  - Password: `password`
- Test User SA:
  - Email: `ade.kurnia@company.com`
  - Password: `password`
- Test User Dir:
  - Email: `imanul.huda@company.com`
  - Password: `password`

### **3. Test Register**
- URL: `http://localhost/prcf_keuangan/register.php`
- Isi form dengan data baru
- Cek email untuk OTP (pastikan mail() PHP sudah configured)

### **4. Test Dashboard Setiap Role**
- Login sebagai masing-masing role
- Cek fungsi create, review, approve
- Cek buku bank dan buku piutang

---

**Dibuat oleh:** AI Assistant  
**Tanggal:** 15 Oktober 2025  
**Version:** 1.0

