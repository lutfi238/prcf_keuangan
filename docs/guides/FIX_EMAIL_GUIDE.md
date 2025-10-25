# 🔧 Panduan Fix Email OTP - Step by Step

## ✅ SUDAH DIPERBAIKI:

1. ✅ Error `buku_bank.php` sudah fixed
2. ✅ Tampilan OTP manual sudah dihapus
3. ✅ Sistem email Gmail SMTP siap digunakan

---

## 🚀 CARA AKTIFKAN EMAIL (3 Langkah Mudah):

### **Step 1: Enable PHP Extensions**

1. **Klik Kanan** file `enable_email.bat`
2. **Pilih "Run as Administrator"**
3. Tunggu sampai selesai
4. **RESTART Apache** di XAMPP Control Panel

**Atau Manual:**

Buka file: `C:\xampp\php\php.ini`

Cari dan hapus titik koma (;) di depan baris ini:
```ini
;extension=openssl   →   extension=openssl
;extension=curl      →   extension=curl
;extension=sockets   →   extension=sockets
```

Save, lalu restart Apache.

---

### **Step 2: Test Email**

1. Buka browser: `http://localhost/prcf_keuangan/test_email.php`
2. Masukkan **email Anda sendiri**
3. Klik "Kirim Test Email"
4. Cek inbox email Anda

**Jika Berhasil:**
- ✅ Muncul "EMAIL BERHASIL TERKIRIM!"
- ✅ Email masuk ke inbox
- ✅ Sistem siap digunakan!

**Jika Gagal:**
- Lanjut ke Step 3

---

### **Step 3: Troubleshooting**

#### **A. Disable Antivirus/Firewall Sementara**

1. Klik ikon Antivirus di taskbar
2. Disable protection sementara (10 menit)
3. Test lagi

#### **B. Cek Port 587 Terbuka**

Buka Command Prompt:
```bash
telnet smtp.gmail.com 587
```

Jika error "Telnet is not recognized":
- Buka Control Panel → Programs → Turn Windows features on or off
- Centang "Telnet Client"
- OK dan restart PC

#### **C. Cek App Password Masih Valid**

1. Login ke: https://myaccount.google.com/
2. Go to Security → 2-Step Verification
3. Scroll ke "App passwords"
4. Jika password lama expired, buat baru
5. Copy 16-digit password
6. Update di `config.php`:
   ```php
   define('SMTP_PASS', 'xxxx xxxx xxxx xxxx');
   ```

#### **D. Test Koneksi Internet**

```bash
ping smtp.gmail.com
```

Harus dapat response.

---

## 🎯 Setelah Email Bekerja:

### **Test Login:**

1. Buka: `http://localhost/prcf_keuangan/`
2. Login: `yadi@company.com` / `password`
3. **Cek email** (bukan tampilan halaman!)
4. Masukkan OTP dari email
5. Login berhasil!

### **Demo via Ngrok:**

```bash
1. Jalankan: ngrok http 80
2. Share URL ke teman
3. Teman login
4. Email OTP otomatis terkirim ke email teman
5. Teman input OTP dari email
6. Login berhasil!
```

---

## 🔍 Debug Mode:

### **Cek Log:**

Buka: `C:\xampp\apache\logs\error.log`

Cari baris terbaru:
```
✅ OTP email sent successfully to: email@example.com - OTP: 123456
```

Atau jika ada error:
```
❌ Failed to send OTP email to: email@example.com
SMTP Connection failed: ...
```

---

## 💡 Alternatif Jika Tetap Gagal:

### **Opsi 1: Pakai Gmail dengan "Less Secure App"**

⚠️ Tidak recommended, tapi bisa dicoba:

1. Login Gmail
2. Go to: https://myaccount.google.com/lesssecureapps
3. Turn ON "Allow less secure apps"
4. Edit `config.php`, ganti `SMTP_PASS` dengan password Gmail asli
5. Test lagi

### **Opsi 2: Pakai SMTP Service Lain**

**Mailtrap (For Testing):**
```php
define('SMTP_HOST', 'smtp.mailtrap.io');
define('SMTP_PORT', 2525);
define('SMTP_USER', 'your-mailtrap-user');
define('SMTP_PASS', 'your-mailtrap-pass');
```

Daftar gratis di: https://mailtrap.io

**SendGrid:**
```php
define('SMTP_HOST', 'smtp.sendgrid.net');
define('SMTP_PORT', 587);
define('SMTP_USER', 'apikey');
define('SMTP_PASS', 'your-sendgrid-api-key');
```

### **Opsi 3: Pakai PHPMailer Library**

Download PHPMailer untuk reliability lebih baik.

---

## 📋 Checklist Lengkap:

**Before Testing:**
- [ ] OpenSSL extension enabled
- [ ] Apache sudah restart
- [ ] Antivirus disabled (sementara)
- [ ] Internet connection OK
- [ ] App Password valid

**Testing:**
- [ ] Test email via `test_email.php`
- [ ] Email masuk ke inbox
- [ ] Test login dengan user sample
- [ ] OTP masuk ke email user

**Demo Ready:**
- [ ] Email confirmed working
- [ ] Ngrok running
- [ ] Share URL to friends
- [ ] Friends receive OTP email

---

## 🎉 Setelah Berhasil:

**Email OTP akan:**
- ✅ Terkirim otomatis saat login
- ✅ Tampil professional dengan template HTML
- ✅ Include OTP code
- ✅ Expire dalam 60 detik
- ✅ Tidak ada tampilan manual di halaman

**User Experience:**
1. Login → Email OTP terkirim
2. Cek inbox
3. Copy OTP
4. Paste di form
5. Masuk dashboard

---

## 🆘 Bantuan:

Jika masih gagal setelah semua step:

1. Screenshot error di `test_email.php`
2. Copy error dari `error.log`
3. Info versi PHP: `php -v`
4. Info extensions: `php -m`

---

**Selamat mencoba! Email OTP pasti bisa bekerja setelah extension diaktifkan.** 🚀

---

**Updated:** 15 Oktober 2025  
**Version:** 2.0 - Production Ready

