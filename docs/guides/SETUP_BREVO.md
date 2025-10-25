# 🚀 Setup Brevo SMTP - Step by Step

## ✅ Kenapa Brevo?
- ✅ **300 email/hari GRATIS** (cukup untuk demo & production)
- ✅ **100% work di ngrok/hosting**
- ✅ **Setup 5 menit**
- ✅ **No credit card needed**
- ✅ **Free forever**

---

## 📋 STEP 1: Daftar Brevo (2 menit)

### **1. Buka Website Brevo**
```
https://app.brevo.com/account/register
```

### **2. Isi Form Registrasi**
```
Email: (email Anda - pakai Gmail/apapun)
Password: (buat password kuat)
First Name: (nama depan)
Last Name: (nama belakang)
Company Name: PRCFI (atau nama perusahaan Anda)
```

### **3. Klik "Create my account"**

### **4. Verify Email**
- Buka inbox email Anda
- Cari email dari Brevo
- Klik link verifikasi
- ✅ Account activated!

---

## 📋 STEP 2: Get SMTP Credentials (2 menit)

### **1. Login ke Dashboard**
```
https://app.brevo.com/
```

### **2. Buka SMTP Settings**
```
Klik menu: Settings (kanan atas, ikon gear)
  → Pilih: SMTP & API
  → Tab: SMTP
```

### **3. Create SMTP Key**
```
1. Klik tombol "Generate a new SMTP key"
2. Nama: "PRCF Dashboard" (atau nama lain)
3. Klik "Generate"
4. ✅ SMTP Key muncul!
```

### **4. Copy Credentials**
```
SMTP Server: smtp-relay.brevo.com
Port: 587
Login: (email Anda yang dipakai daftar)
SMTP Key: xsmtpsib-XXXXXXXXXXXXXX (copy ini!)
```

**PENTING:** Simpan SMTP Key ini! Tidak bisa dilihat lagi setelah ditutup.

---

## 📋 STEP 3: Update config.php (1 menit)

### **Buka file: `config.php`**

Cari bagian ini (sekitar baris 9-14):

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'pblprcf@gmail.com');
define('SMTP_PASS', 'vwkx trnf ordu sfuh');
define('FROM_EMAIL', 'pblprcf@gmail.com');
define('FROM_NAME', 'PRCFI Financial');
```

**Ganti dengan:**

```php
// Brevo SMTP Configuration
define('SMTP_HOST', 'smtp-relay.brevo.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com'); // Email yang dipake daftar Brevo
define('SMTP_PASS', 'xsmtpsib-XXXXX'); // SMTP Key dari Step 2
define('FROM_EMAIL', 'your-email@gmail.com'); // Email yang sama
define('FROM_NAME', 'PRCFI Financial');
```

**Save file!**

---

## 📋 STEP 4: Test Email (1 menit)

### **Test di Localhost:**

1. Buka: `http://localhost/prcf_keuangan/test_email.php`
2. Masukkan email Anda
3. Klik "Kirim Test Email"
4. ✅ Cek inbox email Anda!

### **Test Login:**

1. Buka: `http://localhost/prcf_keuangan/`
2. Login: `yadi@company.com` / `password`
3. Halaman verify OTP muncul
4. **Cek email Anda** - OTP akan masuk!
5. Masukkan OTP dari email
6. ✅ Login berhasil!

---

## 📋 STEP 5: Test via Ngrok (1 menit)

### **1. Start Ngrok:**
```bash
ngrok http 80
```

### **2. Buka URL Ngrok:**
```
https://xxxx-xxxx.ngrok-free.app/prcf_keuangan/
```

### **3. Login:**
```
Email: yadi@company.com
Password: password
```

### **4. Cek Email:**
- OTP akan dikirim ke email user
- ✅ **Email masuk!** (tidak ada kotak kuning lagi)
- Masukkan OTP
- Login berhasil!

---

## 🎯 FAQ & Troubleshooting

### **Q: Email tidak masuk?**

**A: Cek ini:**

1. **Verify Account Brevo:**
   - Login ke Brevo
   - Pastikan account sudah verified (cek email)

2. **Cek Spam Folder:**
   - Email mungkin masuk spam
   - Tandai "Not Spam"

3. **Cek SMTP Credentials:**
   - Pastikan SMTP_USER = email Brevo Anda
   - Pastikan SMTP_PASS = SMTP key yang benar

4. **Cek Error Log:**
   ```
   C:\xampp\apache\logs\error.log
   ```
   Cari baris error SMTP

### **Q: SMTP Authentication Failed?**

**A: Solusi:**

1. Generate SMTP key baru di Brevo
2. Copy key yang baru
3. Update di config.php
4. Test lagi

### **Q: Connection timeout?**

**A: Cek:**

1. Internet connection OK?
2. Firewall tidak block port 587?
3. Disable antivirus sementara untuk test

### **Q: Email masuk tapi OTP masih tampil di halaman?**

**A: Normal!**

Saat ini sistem hybrid:
- OTP tampil di halaman (untuk fallback)
- OTP juga dikirim email (untuk yang email work)

Jika mau hapus tampilan OTP di halaman, edit `verify_otp.php`:
- Hapus bagian `<?php if (isset($_SESSION['demo_otp_display'])): ?>`

---

## 📊 Brevo Dashboard Features

### **Monitor Email:**
```
Dashboard → Statistics → Email
```

Lihat:
- Emails sent
- Delivery rate
- Open rate
- Click rate

### **Email Templates:**
```
Dashboard → Templates
```

Bisa buat template email custom!

### **Sender Settings:**
```
Dashboard → Senders & IP
```

Verify sender email untuk reliability lebih baik.

---

## 💡 Tips Production:

### **1. Verify Sender Domain:**

Untuk production, verify domain Anda:
```
Dashboard → Senders & IP → Domains
Add your domain
Tambah DNS records
```

Benefits:
- Email tidak masuk spam
- Delivery rate 99%+
- Professional

### **2. Enable DKIM/SPF:**

Brevo automatically setup DKIM/SPF saat verify domain.

### **3. Monitor Quota:**

Free plan: 300 emails/day
- Cek dashboard untuk usage
- Upgrade jika perlu (murah!)

### **4. Email Templates:**

Buat template HTML professional di dashboard Brevo.

---

## 🎉 Selesai!

**Checklist:**
- [x] Daftar Brevo
- [x] Get SMTP credentials
- [x] Update config.php
- [x] Test email work!
- [x] Test login dengan OTP email
- [x] Test via ngrok

**Email OTP sekarang:**
- ✅ Terkirim ke email user
- ✅ Work di localhost
- ✅ Work di ngrok/hosting
- ✅ No more blocked!
- ✅ Free 300 emails/day

---

## 📞 Support

**Brevo Support:**
- Email: support@brevo.com
- Help Center: https://help.brevo.com/

**Jika ada masalah:**
1. Cek `error.log`
2. Test dengan `test_email.php`
3. Verify SMTP credentials
4. Check Brevo dashboard

---

**Selamat! Email OTP Anda sekarang work dengan Brevo!** 🎉🚀

---

**Updated:** 16 Oktober 2025
**Version:** 1.0 - Brevo Setup

