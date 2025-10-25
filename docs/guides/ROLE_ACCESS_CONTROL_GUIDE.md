# 🔐 Role-Based Access Control Guide

## 📋 Overview

Sistem Financial Management PRCF Indonesia sekarang dilengkapi dengan **Role-Based Access Control (RBAC)** yang lebih ketat dan informatif. Ketika user mencoba mengakses halaman yang tidak sesuai dengan role mereka, mereka akan melihat halaman khusus yang menjelaskan situasi dan memberikan navigasi yang jelas.

---

## 🎯 Fitur Utama

### 1. **Halaman Unauthorized Access (`unauthorized.php`)**
Halaman khusus yang ditampilkan ketika user mencoba mengakses halaman yang tidak sesuai dengan role mereka.

**Fitur halaman:**
- ✅ Menampilkan informasi user yang login (nama & role)
- ✅ Menjelaskan mengapa akses ditolak
- ✅ Menampilkan informasi tentang semua role dalam sistem
- ✅ Tombol untuk kembali ke dashboard yang sesuai dengan role user
- ✅ Design modern dengan animasi dan visual yang menarik

### 2. **Validasi Role yang Ditingkatkan**
Semua halaman kini memisahkan pengecekan:
1. **Session Check**: Apakah user sudah login?
2. **Role Check**: Apakah role user sesuai dengan halaman?

```php
// Check if logged in
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit();
}

// Check role permission
if ($_SESSION['user_role'] !== 'Required Role') {
    header('Location: unauthorized.php');
    exit();
}
```

---

## 📂 File yang Telah Diperbarui

### Dashboard Files (Role-Specific)
| File | Required Role |
|------|---------------|
| `dashboard_fm.php` | Finance Manager |
| `dashboard_sa.php` | Staff Accountant |
| `dashboard_pm.php` | Project Manager |
| `dashboard_dir.php` | Direktur |

### Proposal Management
| File | Required Role |
|------|---------------|
| `create_proposal.php` | Project Manager |
| `review_proposal.php` | Finance Manager, Direktur, Project Manager |
| `approve_proposal.php` | Direktur |

### Financial Report Management
| File | Required Role |
|------|---------------|
| `create_financial_report.php` | Project Manager |
| `validate_report.php` | Staff Accountant |
| `approve_report.php` | Finance Manager, Project Manager |
| `approve_report_dir.php` | Direktur |

### Accounting Books
| File | Required Role |
|------|---------------|
| `buku_bank.php` | Finance Manager |
| `buku_piutang.php` | Finance Manager |

---

## 👥 Role Information

### 1. Finance Manager (FM)
**Akses ke:**
- Dashboard FM
- Review & Approve Proposals
- Approve Financial Reports
- Buku Bank & Buku Piutang

**Tanggung Jawab:**
- Mengelola proposal dan laporan keuangan
- Menyetujui permintaan dana
- Mengelola cash flow

### 2. Staff Accountant (SA)
**Akses ke:**
- Dashboard SA
- Validate Financial Reports
- View Proposals & Reports

**Tanggung Jawab:**
- Memvalidasi laporan keuangan
- Memastikan data akuntansi akurat

### 3. Project Manager (PM)
**Akses ke:**
- Dashboard PM
- Create Proposals
- Create Financial Reports
- View Proposals & Reports (read-only)

**Tanggung Jawab:**
- Membuat proposal proyek
- Membuat laporan keuangan proyek
- Mengelola anggaran proyek

### 4. Direktur (Dir)
**Akses ke:**
- Dashboard Dir
- Final Approval untuk Reports
- Final Approval untuk Proposals
- View all activities

**Tanggung Jawab:**
- Approval akhir untuk laporan
- Approval akhir untuk proposal
- Oversight terhadap semua aktivitas

---

## 🎨 Design Halaman Unauthorized

Halaman `unauthorized.php` memiliki design yang modern dan user-friendly:

1. **Header Section**
   - Icon shield dengan animasi float
   - Judul "Akses Ditolak" yang jelas
   - Background gradient merah

2. **User Info Section**
   - Menampilkan nama user yang login
   - Menampilkan role user saat ini
   - Background biru dengan icon

3. **Warning Section**
   - Penjelasan mengapa akses ditolak
   - Informasi detail tentang role dan permission
   - Background merah muda

4. **Role Information Section**
   - Card untuk setiap role
   - Penjelasan tanggung jawab masing-masing role
   - Background biru muda

5. **Action Buttons**
   - "Kembali ke Dashboard Saya" - redirect ke dashboard yang sesuai
   - "Lihat Profile" - melihat informasi profile

---

## 🔒 Keamanan

### Level 1: Client-Side (Preventive)
- UI elements yang disabled/hidden berdasarkan role
- Navigation yang disesuaikan dengan role

### Level 2: Server-Side (Enforced)
- Session validation di setiap halaman
- Role checking di setiap halaman
- Redirect ke `unauthorized.php` jika tidak sesuai

### Level 3: Database (Backup)
- Foreign key constraints
- User role validation di database

---

## 📝 Testing Checklist

Untuk memastikan sistem bekerja dengan baik:

### Test Case 1: Finance Manager
- [x] Dapat akses dashboard_fm.php
- [x] Tidak dapat akses dashboard_sa.php → redirect ke unauthorized.php
- [x] Tidak dapat akses dashboard_pm.php → redirect ke unauthorized.php
- [x] Tidak dapat akses dashboard_dir.php → redirect ke unauthorized.php

### Test Case 2: Staff Accountant
- [x] Dapat akses dashboard_sa.php
- [x] Tidak dapat akses dashboard_fm.php → redirect ke unauthorized.php
- [x] Tidak dapat akses dashboard_pm.php → redirect ke unauthorized.php
- [x] Tidak dapat akses dashboard_dir.php → redirect ke unauthorized.php

### Test Case 3: Project Manager
- [x] Dapat akses dashboard_pm.php
- [x] Tidak dapat akses dashboard_fm.php → redirect ke unauthorized.php
- [x] Tidak dapat akses dashboard_sa.php → redirect ke unauthorized.php
- [x] Tidak dapat akses dashboard_dir.php → redirect ke unauthorized.php

### Test Case 4: Direktur
- [x] Dapat akses dashboard_dir.php
- [x] Tidak dapat akses dashboard_fm.php → redirect ke unauthorized.php
- [x] Tidak dapat akses dashboard_sa.php → redirect ke unauthorized.php
- [x] Tidak dapat akses dashboard_pm.php → redirect ke unauthorized.php

---

## 🚀 Cara Testing Manual

1. **Login sebagai Finance Manager**
   ```
   - Coba akses: http://localhost/prcf_keuangan/dashboard_sa.php
   - Expected: Redirect ke unauthorized.php dengan pesan yang sesuai
   ```

2. **Login sebagai Project Manager**
   ```
   - Coba akses: http://localhost/prcf_keuangan/buku_bank.php
   - Expected: Redirect ke unauthorized.php dengan pesan yang sesuai
   ```

3. **Login sebagai Staff Accountant**
   ```
   - Coba akses: http://localhost/prcf_keuangan/create_proposal.php
   - Expected: Redirect ke unauthorized.php dengan pesan yang sesuai
   ```

4. **Login sebagai Direktur**
   ```
   - Coba akses: http://localhost/prcf_keuangan/validate_report.php
   - Expected: Redirect ke unauthorized.php dengan pesan yang sesuai
   ```

---

## 💡 Best Practices

1. **Selalu Validasi di Server-Side**
   - Jangan hanya mengandalkan JavaScript untuk security
   - Selalu check session dan role di setiap halaman

2. **Berikan Feedback yang Jelas**
   - Halaman unauthorized memberikan informasi yang berguna
   - User tahu mengapa akses ditolak dan apa yang harus dilakukan

3. **Maintain Consistency**
   - Semua halaman menggunakan pattern yang sama
   - Mudah untuk maintain dan extend

4. **Keep It User-Friendly**
   - Error page yang informatif bukan menakutkan
   - Navigasi yang jelas untuk user

---

## 📞 Support

Jika ada pertanyaan atau issue terkait Role-Based Access Control:

1. Check dokumentasi ini terlebih dahulu
2. Test dengan berbagai role untuk reproduce issue
3. Check logs untuk error messages
4. Hubungi administrator sistem

---

## 🔄 Update Log

### Version 1.0 (October 2025)
- ✅ Implementasi halaman `unauthorized.php`
- ✅ Update semua dashboard files dengan role checking
- ✅ Update semua functional pages dengan role checking
- ✅ Design modern untuk unauthorized page
- ✅ Auto-redirect ke correct dashboard
- ✅ Role information display

---

## 📚 References

- `unauthorized.php` - Halaman unauthorized access
- `config.php` - Configuration dan session management
- `maintenance_config.php` - Maintenance mode configuration

---

**Last Updated:** October 16, 2025  
**Version:** 1.0  
**Status:** ✅ Production Ready

