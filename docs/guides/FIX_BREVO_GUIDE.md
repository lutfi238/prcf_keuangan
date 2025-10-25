# 🔧 Fix Brevo Connection Error

## ❌ Error yang Muncul:

```
fsockopen(): Failed to enable crypto
Unable to connect to tls://smtp-relay.brevo.com:587
```

---

## ✅ SOLUSI (3 Langkah Mudah):

### **Solusi 1: Restart Apache (Paling Simple!)**

**Problem:** Apache belum reload PHP configuration

**Fix:**
```
1. Buka XAMPP Control Panel
2. Klik "Stop" di Apache
3. Tunggu 2 detik
4. Klik "Start" di Apache
5. Test lagi!
```

**Langsung test:** http://localhost/prcf_keuangan/test_email.php

---

### **Solusi 2: Enable OpenSSL (Jika Solusi 1 Gagal)**

**Problem:** OpenSSL extension belum enabled

**Fix:**

**A. Pakai Script Otomatis:**
```
1. Right-click: fix_brevo_connection.bat
2. Run as Administrator
3. Tunggu selesai
4. Restart Apache
5. Test lagi!
```

**B. Manual:**

1. Buka: `C:\xampp\php\php.ini`
2. Cari baris: `;extension=openssl`
3. Hapus titik koma (;) jadi: `extension=openssl`
4. Save file
5. Restart Apache
6. Test lagi!

---

### **Solusi 3: Pakai cURL SMTP (Sudah Saya Update!)**

**Problem:** fsockopen tidak reliable

**Fix:** ✅ **Sudah saya update!**

Saya sudah ganti fungsi SMTP di `config.php` pakai **cURL** yang lebih reliable!

**Keuntungan cURL:**
- ✅ Lebih stable untuk Brevo
- ✅ Handle TLS lebih baik
- ✅ Error handling lebih baik

**Tinggal restart Apache dan test!**

---

## 🧪 CARA TEST:

### **Test 1: cURL Extension**

Buka Command Prompt:
```bash
php -m | findstr curl
```

Harus muncul: `curl`

Jika tidak muncul:
1. Buka `php.ini`
2. Cari: `;extension=curl`
3. Hapus `;` jadi: `extension=curl`
4. Restart Apache

### **Test 2: Test Email**

Buka browser:
```
http://localhost/prcf_keuangan/test_email.php
```

Masukkan email Anda dan test!

---

## 🔍 Troubleshooting Lanjutan:

### **Error: cURL not found**

**Fix:**
```
1. Buka: C:\xampp\php\php.ini
2. Cari: ;extension=curl
3. Hapus ; jadi: extension=curl
4. Save
5. Restart Apache
```

### **Error: Could not resolve host**

**Fix:**
```
1. Cek internet connection
2. Ping smtp-relay.brevo.com
3. Disable firewall sementara
4. Test lagi
```

### **Error: SSL certificate problem**

**Fix:**
```
1. Download: https://curl.se/ca/cacert.pem
2. Save ke: C:\xampp\php\extras\ssl\cacert.pem
3. Buka php.ini
4. Cari: curl.cainfo
5. Set: curl.cainfo = "C:\xampp\php\extras\ssl\cacert.pem"
6. Restart Apache
```

### **Error: Port blocked**

**Fix:**
```
1. Disable antivirus sementara
2. Check firewall rules
3. Test dengan port lain (25, 465, 2525)
```

---

## 💡 Alternatif Port Brevo:

Jika port 587 tidak work, coba port lain:

### **Port 25 (No TLS):**
```php
define('SMTP_PORT', 25);
```

### **Port 465 (SSL):**
```php
define('SMTP_PORT', 465);
```
Update di config.php dan ganti `smtp://` jadi `smtps://` di fungsi cURL.

### **Port 2525 (Alternative):**
```php
define('SMTP_PORT', 2525);
```

---

## 🚀 Quick Fix Steps (Copy-Paste):

```bash
# 1. Restart Apache
# 2. Test
# 3. Jika gagal, jalankan:

# Check PHP extensions
php -m | findstr "openssl curl"

# Should show:
# openssl
# curl

# If not, edit php.ini:
# - extension=openssl
# - extension=curl

# Then restart Apache
```

---

## ✅ After Fix Checklist:

- [ ] Apache restarted
- [ ] OpenSSL enabled
- [ ] cURL enabled
- [ ] test_email.php shows success
- [ ] Email received in inbox
- [ ] Login OTP works

---

## 📞 Still Not Working?

**Cek Error Log:**
```
C:\xampp\apache\logs\error.log
```

Cari baris error terbaru dan share.

**Atau pakai alternatif:**
1. Resend API (lebih simple, pakai HTTP)
2. WhatsApp OTP (via Fonnte)
3. Manual OTP display (current working)

---

**Most likely fix: Just restart Apache!** 🚀

---

**Updated:** 16 Oktober 2025
**Version:** 1.0 - Brevo Connection Fix

