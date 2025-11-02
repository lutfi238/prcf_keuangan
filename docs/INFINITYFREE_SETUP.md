# 🚀 InfinityFree Hosting Setup Guide

## ⚠️ Common Issues & Solutions

### Issue 1: Halaman Blank (White Screen)
**Penyebab:**
- Database credentials salah
- PHP error tidak ditampilkan
- File tidak ter-upload lengkap

**Solusi:**
1. Upload file `tests/check_hosting_errors.php`
2. Akses: `https://your-domain.infinityfree.me/tests/check_hosting_errors.php`
3. Cek error yang muncul

### Issue 2: Database Connection Failed
**Penyebab:**
- Hostname salah (bukan `localhost`!)
- Database name tidak pakai prefix username

**Solusi:**
```php
// InfinityFree database format:
DB_HOST: sql200.infinityfree.com (or sqlXXX.infinityfree.com)
DB_USER: if0_12345678 (dari cPanel)
DB_NAME: if0_12345678_prcf_keuangan (HARUS pakai prefix!)
DB_PASS: (password dari cPanel MySQL)
```

### Issue 3: Email OTP Tidak Terkirim
**Penyebab:**
- InfinityFree memblokir `mail()` function
- SMTP Gmail kadang diblokir

**Solusi:**
Gunakan Developer Mode untuk testing:
```php
// includes/config.php
define('DEVELOPER_MODE', true);
define('SKIP_OTP_FOR_ALL', true);
```

## 📋 Step-by-Step Setup

### Step 1: Persiapan File

1. **Upload semua file via File Manager atau FTP**
   ```
   /htdocs/
   ├── api/
   ├── assets/
   ├── auth/
   ├── includes/
   ├── pages/
   ├── public/
   ├── sql/
   ├── tests/
   ├── uploads/
   ├── .htaccess
   └── index.php
   ```

2. **Set permissions untuk folder uploads**
   - Di File Manager, klik kanan folder `uploads/`
   - Pilih "Change Permissions"
   - Set ke `755` atau `777`

### Step 2: Setup Database

1. **Buat Database di cPanel**
   - Login ke InfinityFree Control Panel
   - Klik "MySQL Databases"
   - Buat database baru: `prcf_keuangan`
   - **Note:** Nama lengkap akan jadi `if0_XXXXXXXX_prcf_keuangan`

2. **Import Database**
   - Klik "phpMyAdmin"
   - Pilih database yang baru dibuat
   - Klik tab "Import"
   - Upload file `assets/other/prcf_keuangan.sql`
   - Klik "Go"

3. **Catat Credentials**
   ```
   MySQL Hostname: sql200.infinityfree.com (cek di cPanel)
   MySQL Username: if0_12345678 (dari cPanel)
   MySQL Password: (yang kamu set saat buat database)
   Database Name: if0_12345678_prcf_keuangan
   ```

### Step 3: Update config.php

**Option A: Gunakan config_infinityfree.php**
```bash
# Via FTP atau File Manager:
1. Rename `config.php` jadi `config_local.php`
2. Rename `config_infinityfree.php` jadi `config.php`
3. Edit config.php dengan credentials dari Step 2
```

**Option B: Edit config.php manual**
```php
// includes/config.php
define('DB_HOST', 'sql200.infinityfree.com'); // Sesuaikan!
define('DB_USER', 'if0_12345678');            // Sesuaikan!
define('DB_PASS', 'your-password');           // Sesuaikan!
define('DB_NAME', 'if0_12345678_prcf_keuangan'); // Sesuaikan!

// Enable DEVELOPER_MODE untuk testing tanpa OTP
define('DEVELOPER_MODE', true);
define('SKIP_OTP_FOR_ALL', true); // Bypass OTP sementara
```

### Step 4: Test Installation

1. **Run Check Script**
   ```
   https://your-domain.infinityfree.me/tests/check_hosting_errors.php
   ```
   
   Harus muncul:
   - ✅ PHP Version
   - ✅ PHP Extensions
   - ✅ config.php loaded successfully
   - ✅ Database connected!

2. **Test Login Page**
   ```
   https://your-domain.infinityfree.me/auth/login.php
   ```

3. **Login dengan Default Account**
   ```
   Email: fm@prcf.id
   Password: password
   ```
   
   (OTP akan di-bypass jika DEVELOPER_MODE = true)

## 🔧 Troubleshooting

### Error: "Database connection failed"
```php
// Check these in config.php:
1. DB_HOST harus sql***.infinityfree.com (BUKAN localhost!)
2. DB_NAME harus pakai prefix username (if0_XXXXXXXX_nama_db)
3. DB_USER juga pakai prefix (if0_XXXXXXXX)
4. DB_PASS pastikan benar (case-sensitive!)
```

### Error: "Headers already sent"
```php
// Pastikan tidak ada spasi atau BOM di awal file PHP
// Check file dengan Notepad++, set encoding ke "UTF-8 without BOM"
```

### Error: "Session not working"
```php
// InfinityFree kadang ada issue dengan session
// Add ini di awal config.php:
session_save_path(sys_get_temp_dir());
```

### Upload File Tidak Bisa
```
// Set permissions folder uploads:
chmod 777 uploads/budgets/
chmod 777 uploads/receipts/
chmod 777 uploads/tor/

// Atau via File Manager → Change Permissions → 777
```

### Email OTP Tidak Terkirim
```php
// Solusi 1: Pakai Developer Mode
define('DEVELOPER_MODE', true);
define('SKIP_OTP_FOR_ALL', true);

// Solusi 2: Ganti SMTP (misal SendGrid)
// InfinityFree sering blokir Gmail SMTP
```

## 📊 Performance Tips

### 1. Enable Caching
```php
// Add di awal file yang sering diakses:
header("Cache-Control: max-age=3600"); // 1 hour
```

### 2. Optimize Images
- Compress gambar sebelum upload
- Pakai format WebP untuk ukuran lebih kecil

### 3. Minimize Database Queries
- Gunakan caching untuk data yang jarang berubah
- Index database columns yang sering di-query

## 🚫 InfinityFree Limitations

| Feature | Status | Workaround |
|---------|--------|------------|
| Mail() function | ❌ Blocked | Use SMTP/PHPMailer |
| Cron Jobs | ❌ Not available | Use external cron service |
| SSH Access | ❌ Not available | Use FTP/File Manager |
| Custom PHP.ini | ⚠️ Limited | Use .htaccess |
| File Upload Size | ⚠️ Max 10MB | Split large files |
| Execution Time | ⚠️ Max 60s | Optimize queries |

## ✅ Post-Setup Checklist

- [ ] Database imported successfully
- [ ] config.php updated with correct credentials
- [ ] .htaccess uploaded to root directory
- [ ] Uploads folder permissions set to 755/777
- [ ] Test script shows all ✅ green checks
- [ ] Login page loads without blank screen
- [ ] Can login with default account
- [ ] Dashboard loads after login
- [ ] File upload works (test with proposal)
- [ ] Disable DEVELOPER_MODE after testing

## 🔐 Security Recommendations

After everything works:

1. **Disable Error Display**
   ```php
   // config.php
   error_reporting(0);
   ini_set('display_errors', 0);
   ```

2. **Disable Developer Mode**
   ```php
   define('DEVELOPER_MODE', false);
   ```

3. **Change Default Passwords**
   - Login as admin
   - Go to User Management
   - Change all default account passwords

4. **Backup Database Regularly**
   - phpMyAdmin → Export → Save SQL file

## 🆘 Still Not Working?

1. Check error.log file in root directory
2. Try accessing: `your-domain.infinityfree.me/tests/check_hosting_errors.php`
3. Screenshot error dan share untuk troubleshooting
4. Contact InfinityFree support (mereka responsive!)

## 📱 Contact Support

- **InfinityFree Forum**: https://forum.infinityfree.com/
- **Documentation**: https://docs.infinityfree.net/
- **Status Page**: https://status.infinityfree.net/

