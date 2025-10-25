# 📱 Setup Fonnte WhatsApp OTP - Step by Step Guide

**Last Updated:** October 16, 2025

---

## 🎯 **OVERVIEW**

Fonnte adalah WhatsApp Business API provider yang menyediakan **100 pesan gratis per bulan** tanpa perlu kartu kredit.

### **Fitur:**
- ✅ **100 messages/month FREE** - cukup untuk testing & demo
- ✅ **No credit card required** - daftar langsung pakai email
- ✅ **Instant delivery** - OTP sampai dalam hitungan detik
- ✅ **High reliability** - 99% uptime
- ✅ **Easy integration** - REST API sederhana

---

## 📋 **PREREQUISITES**

Sebelum mulai, pastikan Anda punya:

1. ✅ **Nomor WhatsApp aktif** (untuk connect device)
2. ✅ **Smartphone Android/iOS** (untuk scan QR code)
3. ✅ **Email address** (untuk registrasi)
4. ✅ **Database sudah ada kolom** `whatsapp` di tabel `user`

---

## 🚀 **STEP-BY-STEP SETUP**

### **STEP 1: Daftar Akun Fonnte** 📝

1. **Buka website Fonnte:**
   ```
   https://fonnte.com
   ```

2. **Klik "Daftar Gratis" / "Sign Up"**

3. **Isi form registrasi:**
   - Email: `your-email@gmail.com`
   - Password: `minimum 8 karakter`
   - Confirm Password

4. **Verifikasi email:**
   - Cek inbox email Anda
   - Klik link verifikasi dari Fonnte
   - Login ke dashboard

---

### **STEP 2: Connect WhatsApp Device** 📱

1. **Login ke dashboard:** https://app.fonnte.com

2. **Klik "Connect Device"** atau **"Tambah Device"**

3. **Pilih method:** 
   - Scan QR Code (paling mudah)
   - Atau pairing code

4. **Scan QR Code:**
   - Buka WhatsApp di smartphone Anda
   - Pilih **Menu (⋮) → Linked Devices → Link a Device**
   - Scan QR code yang muncul di dashboard Fonnte
   - Tunggu sampai status jadi **"Connected ✅"**

5. **Verify connection:**
   - Setelah connected, test kirim pesan ke nomor Anda sendiri
   - Klik **"Test Message"** di dashboard
   - Cek apakah pesan masuk di WhatsApp

---

### **STEP 3: Dapatkan API Token** 🔑

1. **Di dashboard Fonnte, klik "API" atau "Settings"**

2. **Copy API Token:**
   ```
   Contoh format: xxxxxxxxxxx-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
   ```

3. **Save token ini** - akan digunakan di config.php

---

### **STEP 4: Update config.php** ⚙️

1. **Buka file:** `c:\xampp\htdocs\prcf_keuangan\config.php`

2. **Cari baris ini:**
   ```php
   define('FONNTE_TOKEN', 'YOUR_FONNTE_TOKEN_HERE');
   ```

3. **Ganti dengan token Anda:**
   ```php
   define('FONNTE_TOKEN', 'xxxxxxxxxxx-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');
   ```

4. **Pastikan enabled:**
   ```php
   define('WA_OTP_ENABLED', true); // Jangan ubah ini
   ```

5. **Save file!**

---

### **STEP 5: Update Database** 🗄️

1. **Buka phpMyAdmin:**
   ```
   http://localhost/phpmyadmin
   ```

2. **Pilih database:** `prcf_keuangan`

3. **Klik tab "SQL"**

4. **Copy & Paste SQL ini:**
   ```sql
   -- Add whatsapp column to user table
   ALTER TABLE user 
   ADD COLUMN whatsapp VARCHAR(20) NULL AFTER email,
   ADD INDEX idx_whatsapp (whatsapp);
   
   -- Update existing test users (optional)
   UPDATE user SET whatsapp = '6281234567890' WHERE username = 'pm_test';
   UPDATE user SET whatsapp = '6281234567891' WHERE username = 'sa_test';
   UPDATE user SET whatsapp = '6281234567892' WHERE username = 'fm_test';
   UPDATE user SET whatsapp = '6281234567893' WHERE username = 'dir_test';
   ```

5. **Klik "Go" untuk eksekusi**

6. **Verify:**
   ```sql
   SELECT id_user, username, email, whatsapp, role FROM user;
   ```

---

### **STEP 6: Test WhatsApp OTP** 🧪

#### **Test 1: Register User Baru**

1. **Buka:**
   ```
   http://localhost/prcf_keuangan/register.php
   ```

2. **Isi form dengan nomor WhatsApp ANDA:**
   ```
   Username: test_wa
   Email: test@example.com
   Password: password123
   Confirm Password: password123
   Nomor Handphone: 081234567890
   Nomor WhatsApp: 081234567890  ← GANTI dengan nomor Anda!
   Role: Project Manager
   ```

3. **Klik "Buat Akun"**

4. **Harusnya muncul:** "Akun berhasil dibuat! Silakan login."

#### **Test 2: Login & Terima OTP**

1. **Buka:**
   ```
   http://localhost/prcf_keuangan/login.php
   ```

2. **Login dengan akun yang baru dibuat:**
   ```
   Email/No HP: test@example.com
   Password: password123
   ```

3. **Klik "Login"**

4. **CEK WHATSAPP ANDA!** 💬

   **Seharusnya muncul pesan seperti ini:**
   ```
   🔐 *Kode OTP Login - PRCFI Financial*

   Kode OTP Anda: *123456*

   ⏱️ Berlaku selama 60 detik.
   🔒 Jangan bagikan kode ini kepada siapapun!

   PRCFI Financial Management System
   ```

5. **Copy kode OTP dari WhatsApp**

6. **Paste di form verify_otp.php**

7. **Klik "Verifikasi"**

8. **✅ Berhasil masuk dashboard!**

---

## 🎉 **SUCCESS!**

Jika Anda berhasil terima OTP di WhatsApp dan login, berarti setup sudah **BERHASIL!** 🎊

---

## 🔧 **TROUBLESHOOTING**

### **Problem 1: Token tidak valid**

**Error:**
```
⚠️ Fonnte token not configured
```

**Solution:**
1. Cek `config.php` - pastikan token sudah diupdate
2. Pastikan tidak ada extra space atau typo
3. Copy token langsung dari dashboard Fonnte

---

### **Problem 2: WhatsApp OTP tidak terkirim**

**Error:**
```
❌ Failed to send WhatsApp OTP
```

**Solution:**

1. **Cek koneksi device di Fonnte:**
   - Login ke https://app.fonnte.com
   - Pastikan device status: **"Connected ✅"**
   - Jika disconnected, scan QR code ulang

2. **Cek nomor WhatsApp:**
   - Harus format: `08xxxxxxxxxx` atau `628xxxxxxxxxx`
   - Nomor harus aktif & terdaftar di WhatsApp
   - Test dulu kirim pesan manual dari dashboard Fonnte

3. **Cek quota:**
   - Dashboard Fonnte → Lihat sisa quota
   - Free plan: 100 messages/month
   - Jika habis, tunggu bulan depan atau upgrade

4. **Cek error log:**
   ```
   C:\xampp\apache\logs\error.log
   ```
   Cari baris dengan:
   ```
   📱 WhatsApp OTP Debug:
   ❌ Failed to send WhatsApp OTP
   ```

---

### **Problem 3: OTP masuk tapi kodenya salah**

**Symptoms:**
- WhatsApp OTP masuk
- Tapi kode yang di database beda

**Solution:**
1. Restart Apache (untuk refresh PHP session)
2. Logout dan login ulang
3. Pastikan tidak ada multiple login attempts

---

### **Problem 4: Manual OTP muncul terus (tidak kirim WhatsApp)**

**Symptoms:**
- Selalu muncul "⚠️ Mode fallback - WhatsApp tidak tersedia"
- OTP ditampilkan manual di halaman

**Solution:**

1. **Cek apakah user punya nomor WhatsApp:**
   ```sql
   SELECT id_user, username, whatsapp FROM user WHERE username = 'test_wa';
   ```
   
2. **Jika NULL, update:**
   ```sql
   UPDATE user SET whatsapp = '628xxxxxxxxxx' WHERE username = 'test_wa';
   ```

3. **Logout dan login ulang**

---

### **Problem 5: Format nomor WhatsApp salah**

**Error:**
```
Nomor WhatsApp tidak valid. Gunakan format: 08xxxxxxxxxx
```

**Valid formats:**
```
✅ 081234567890  (dimulai 08)
✅ 628123456789   (dimulai 62, tanpa 0)
❌ +6281234567890 (tidak boleh ada +)
❌ 8123456789     (harus ada 0 atau 62 di depan)
```

**Auto-conversion:**
- System akan otomatis convert `08xx` ke `628xx`
- Anda bisa input format apapun, system yang format

---

## 💡 **TIPS & BEST PRACTICES**

### **1. Manage Quota**

Free plan: 100 messages/month

**Saran:**
- Save quota untuk production/demo
- Pakai manual OTP untuk development
- Upgrade ke paid plan jika perlu lebih

**Cara disable sementara:**
```php
// In config.php
define('WA_OTP_ENABLED', false); // ← Set false untuk development
```

---

### **2. Multiple Devices**

Anda bisa connect **multiple WhatsApp devices** di Fonnte:

- Device 1: Nomor bisnis (production)
- Device 2: Nomor pribadi (testing)

Setiap device punya token sendiri.

---

### **3. Monitoring**

**Dashboard Fonnte menampilkan:**
- ✅ Message sent count
- ✅ Success rate
- ✅ Failed messages
- ✅ Quota remaining
- ✅ Device status

**Cek secara berkala!**

---

### **4. Fallback Strategy**

System sudah punya auto-fallback:

```
1. Try send via WhatsApp
   ↓ (if failed)
2. Display OTP manually on page
   ↓
3. User still can login
```

**User tidak akan stuck** jika WhatsApp gagal!

---

### **5. Security**

**JANGAN:**
- ❌ Commit/push API token ke Git
- ❌ Share token publicly
- ❌ Hardcode token di multiple files

**DO:**
- ✅ Save token hanya di `config.php`
- ✅ Add `config.php` ke `.gitignore`
- ✅ Use environment variables (production)

---

## 📊 **PRICING (Optional Upgrade)**

| Plan | Price | Messages | Features |
|------|-------|----------|----------|
| **FREE** | Rp 0 | 100/month | 1 device, basic features |
| **Starter** | Rp 50,000/month | 500/month | 2 devices, priority support |
| **Business** | Rp 150,000/month | 2,000/month | 5 devices, analytics |
| **Enterprise** | Custom | Unlimited | Unlimited devices, SLA |

**Recommendation:**
- **Demo/Testing:** FREE plan sudah cukup
- **Small Production:** Starter (500 msg)
- **Medium Business:** Business (2,000 msg)

---

## 🎯 **QUICK REFERENCE**

### **Config Settings:**
```php
// config.php
define('FONNTE_API_URL', 'https://api.fonnte.com/send');
define('FONNTE_TOKEN', 'YOUR_TOKEN_HERE'); // ← Update ini!
define('WA_OTP_ENABLED', true); // true = enabled, false = disabled
```

### **Database:**
```sql
-- Column: user.whatsapp
-- Format: VARCHAR(20)
-- Example: 628123456789
```

### **Testing Credentials:**
```
URL: http://localhost/prcf_keuangan
Test User: pm_test / password123
WhatsApp: (sesuaikan dengan nomor Anda di database)
```

---

## 📚 **DOKUMENTASI LAIN**

- **`EMAIL_OTP_GUIDE.md`** - Email OTP alternative
- **`OTP_STATUS.txt`** - Current OTP status
- **`README_DASHBOARD.md`** - Dashboard overview
- **`test_whatsapp_otp.php`** - WhatsApp OTP testing page

---

## 🆘 **NEED HELP?**

1. **Fonnte Support:**
   - Website: https://fonnte.com/faq
   - Email: support@fonnte.com
   - WhatsApp: 081234567890 (check website)

2. **Check Logs:**
   ```
   C:\xampp\apache\logs\error.log
   ```
   Search for: `📱 WhatsApp OTP`

3. **Test Page:**
   ```
   http://localhost/prcf_keuangan/test_whatsapp_otp.php
   ```

---

**🎊 Selamat! WhatsApp OTP sudah siap digunakan!** 💪

**Status:** ✅ **WhatsApp OTP ACTIVE**

Last Updated: October 16, 2025

