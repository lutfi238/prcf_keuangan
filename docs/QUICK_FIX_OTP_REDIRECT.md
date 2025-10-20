# 🚨 QUICK FIX - OTP Tidak Redirect ke Dashboard

## Masalah
Setelah memasukkan OTP yang benar, halaman hanya refresh dan tidak masuk ke dashboard.

## ✅ Solusi Cepat

### 1. **Test Session Tool**
Buka: `http://localhost/prcf_keuangan_dashboard/tests/check_session.php`

**Jika logged_in = YES:**
- Klik tombol "Go to Dashboard"
- Session sudah benar, hanya perlu redirect manual

**Jika logged_in = NO:**
- Clear browser cache & cookies
- Login ulang dari awal

### 2. **Clear Cache Browser**
```
Chrome: Ctrl + Shift + Delete
Firefox: Ctrl + Shift + Delete
Edge: Ctrl + Shift + Delete

Pilih:
✅ Cookies and other site data
✅ Cached images and files

Time range: All time
```

### 3. **Restart Apache**
```
1. Buka XAMPP Control Panel
2. Stop Apache
3. Start Apache lagi
```

### 4. **Clear PHP Session Files**
```
1. Tutup semua browser
2. Delete semua file di: C:\xampp\tmp\sess_*
3. Restart Apache
4. Login ulang
```

## 🔍 Test Session Persistence

Buka: `http://localhost/prcf_keuangan_dashboard/tests/test_session_write.php`

Follow the steps:
1. Step 1: Write session
2. Step 2: Redirect
3. Step 3: Should show ✅ all green checks

**Jika ada ❌ merah:**
- Session tidak tersimpan dengan benar
- Check php.ini configuration
- Check folder permissions

## 📋 Check Error Log

```bash
# Windows
notepad C:\xampp\apache\logs\error.log

# Cari baris dengan:
🔍 verify_otp.php - Session state
✅ OTP Verified - Redirecting
🔍 dashboard_pm.php - Session check
⚠️ dashboard_pm.php - Not logged in
```

## 🛠️ Manual Override (Emergency)

Jika semua gagal, disable OTP temporarily:

**File:** `includes/config.php`

```php
// Set to true to disable OTP for ALL users
define('SKIP_OTP_FOR_ALL', true);
```

⚠️ **WARNING:** Only for testing! Don't use in production!

## 📞 Still Not Working?

1. Take screenshot of `tests/check_session.php`
2. Copy last 20 lines from error.log
3. Note your:
   - PHP version: `php -v`
   - Browser & version
   - Operating system

---

**File Modified:** `auth/verify_otp.php` - Added `session_write_close()` before redirect  
**Documentation:** `docs/OTP_REDIRECT_FIX.md`
