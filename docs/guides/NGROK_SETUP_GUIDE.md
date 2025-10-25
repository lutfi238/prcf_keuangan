# 🌐 Panduan Hosting PRCF Keuangan dengan Ngrok

## 📋 Apa itu Ngrok?

Ngrok adalah tool yang membuat tunnel dari localhost ke internet, sehingga aplikasi di komputer Anda bisa diakses oleh orang lain dari internet.

---

## 🚀 Cara Setup Ngrok

### **Step 1: Download Ngrok**

1. Kunjungi: https://ngrok.com/download
2. Download sesuai OS Anda (Windows/Mac/Linux)
3. Extract file zip ke folder yang mudah diakses (misal: `C:\ngrok`)

### **Step 2: Daftar Akun Ngrok (Gratis)**

1. Kunjungi: https://dashboard.ngrok.com/signup
2. Daftar dengan Google/GitHub atau email
3. Setelah login, dapatkan **Authtoken** di dashboard

### **Step 3: Setup Authtoken**

Buka Command Prompt atau PowerShell, jalankan:
```bash
cd C:\ngrok
ngrok config add-authtoken YOUR_AUTHTOKEN_HERE
```

Ganti `YOUR_AUTHTOKEN_HERE` dengan authtoken dari dashboard ngrok Anda.

---

## 🎯 Cara Menjalankan

### **1. Start XAMPP**

Pastikan XAMPP sudah running:
- ✅ Apache (Port 80 atau 8080)
- ✅ MySQL

### **2. Jalankan Ngrok**

**Jika Apache di port 80:**
```bash
cd C:\ngrok
ngrok http 80
```

**Jika Apache di port 8080:**
```bash
cd C:\ngrok
ngrok http 8080
```

### **3. Copy URL Public**

Setelah ngrok jalan, Anda akan melihat:
```
Forwarding    https://xxxx-xx-xxx-xxx-xx.ngrok-free.app -> http://localhost:80
```

Copy URL `https://xxxx-xx-xxx-xxx-xx.ngrok-free.app` dan share ke teman Anda!

---

## 🔗 Akses Aplikasi

### **URL untuk Teman:**
```
https://xxxx-xx-xxx-xxx-xx.ngrok-free.app/prcf_keuangan/
```

### **Test Login:**
Berikan kredensial test user ke teman:

**Project Manager:**
- Email: `yadi@company.com`
- Password: `password`

**Finance Manager:**
- Email: `aam.wijaya@company.com`
- Password: `password`

**Staff Accountant:**
- Email: `ade.kurnia@company.com`
- Password: `password`

**Direktur:**
- Email: `imanul.huda@company.com`
- Password: `password`

---

## ⚙️ Konfigurasi Tambahan (Opsional)

### **Gunakan Domain Custom (Berbayar)**

Jika punya akun ngrok berbayar, bisa pakai custom domain:
```bash
ngrok http 80 --domain=your-custom-domain.ngrok.app
```

### **Jalankan di Background**

Gunakan nssm (Non-Sucking Service Manager) untuk menjalankan ngrok sebagai service Windows.

### **Multiple Ports**

Jalankan beberapa ngrok sekaligus (butuh akun berbayar):
```bash
# Terminal 1
ngrok http 80

# Terminal 2
ngrok http 3306
```

---

## ⚠️ Perhatian Penting!

### **Keamanan:**
1. 🔒 **Jangan share URL ke publik** - Hanya share ke orang yang dipercaya
2. 🔒 **Ganti password default** - Ubah password test user sebelum demo
3. 🔒 **Nonaktifkan setelah demo** - Stop ngrok setelah selesai demo
4. 🔒 **Backup database** - Backup database sebelum demo

### **Limitasi Ngrok Free:**
- ⏱️ **Session timeout:** Tunnel akan mati setelah 2 jam (harus restart)
- 🔄 **URL berubah:** Setiap restart, URL akan berubah
- 👥 **40 koneksi/menit:** Cukup untuk demo kecil
- 📦 **1 tunnel aktif:** Hanya bisa 1 tunnel bersamaan

### **Solusi Limitasi:**
- **Ngrok Pro ($8/bulan):** Unlimited time, custom domain, multiple tunnels
- **Alternatif:** Serveo, LocalTunnel, Pagekite, Cloudflare Tunnel

---

## 🐛 Troubleshooting

### **Error: "Port 80 is already in use"**

**Solusi 1:** Stop service yang pakai port 80
```bash
netstat -ano | findstr :80
taskkill /PID [PID_NUMBER] /F
```

**Solusi 2:** Ganti port Apache di XAMPP (misal ke 8080), lalu:
```bash
ngrok http 8080
```

### **Ngrok tidak jalan di Windows**

**Solusi:** Jalankan sebagai Administrator
1. Klik kanan Command Prompt
2. Pilih "Run as Administrator"
3. Jalankan perintah ngrok

### **Teman tidak bisa akses**

**Cek:**
1. ✅ XAMPP Apache running?
2. ✅ Ngrok tunnel masih aktif?
3. ✅ URL yang dishare benar? (harus include `/prcf_keuangan/`)
4. ✅ Firewall tidak block ngrok?

### **OTP Email tidak terkirim saat diakses dari ngrok**

**Normal!** Fungsi `mail()` PHP biasanya tidak bekerja dari localhost/ngrok.

**Solusi Sementara untuk Demo:**
1. Comment fungsi send_otp_email() di `login.php` dan `verify_otp.php`
2. Atau tampilkan OTP langsung di halaman (untuk demo saja)
3. Atau skip OTP verification (untuk demo saja)

**Atau gunakan Mailtrap untuk testing:**
- Daftar di https://mailtrap.io (gratis)
- Configure SMTP di `config.php`

---

## 🎬 Quick Start Guide

### **Langkah Cepat:**

1. **Download & Extract Ngrok**
   ```
   https://ngrok.com/download
   ```

2. **Setup Authtoken**
   ```bash
   ngrok config add-authtoken YOUR_TOKEN
   ```

3. **Start XAMPP**
   - Apache ✅
   - MySQL ✅

4. **Start Ngrok**
   ```bash
   ngrok http 80
   ```

5. **Copy & Share URL**
   ```
   https://xxxx.ngrok-free.app/prcf_keuangan/
   ```

6. **Demo ke Teman!** 🎉

---

## 📱 Alternatif Ngrok

Jika ngrok ada masalah, bisa coba:

### **1. LocalTunnel (Gratis)**
```bash
npm install -g localtunnel
lt --port 80
```

### **2. Serveo (Gratis, tanpa install)**
```bash
ssh -R 80:localhost:80 serveo.net
```

### **3. Cloudflare Tunnel (Gratis)**
```bash
# Install cloudflared
cloudflared tunnel --url http://localhost:80
```

### **4. Pagekite (Gratis trial)**
```bash
pagekite.py 80 yourdomain.pagekite.me
```

---

## 💡 Tips untuk Demo Sukses

1. **Siapkan Data Sample**
   - Sudah ada 10 user test
   - Sudah ada 5 proyek sample
   - Sudah ada proposal dan laporan sample

2. **Test Dulu Sebelum Demo**
   - Login sebagai setiap role
   - Cek semua fitur berjalan
   - Pastikan tidak ada error

3. **Buat Skenario Demo**
   - PM: Buat proposal baru
   - SA: Validasi laporan
   - FM: Approve proposal & laporan
   - Dir: Final approval

4. **Dokumentasi**
   - Screenshot flow sistem
   - Video demo (opsional)
   - Panduan user (README)

5. **Disable OTP untuk Demo** (Opsional)
   - Edit `login.php` - skip OTP
   - Langsung redirect ke dashboard
   - Aktifkan lagi setelah demo

---

## 🔐 Disable OTP untuk Demo (Opsional)

Jika mau demo tanpa OTP (karena email tidak bekerja):

Edit `login.php` sekitar baris 20-32:

```php
// BEFORE (dengan OTP):
if (password_verify($password, $user['password_hash'])) {
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_time'] = time();
    $_SESSION['user_id'] = $user['id_user'];
    $_SESSION['pending_login'] = true;
    send_otp_email($user['email'], $otp);
    header('Location: verify_otp.php');
    exit();
}

// AFTER (tanpa OTP - untuk demo saja):
if (password_verify($password, $user['password_hash'])) {
    // Set session langsung
    $_SESSION['user_id'] = $user['id_user'];
    $_SESSION['user_name'] = $user['nama'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['logged_in'] = true;
    
    // Redirect ke dashboard sesuai role
    switch ($user['role']) {
        case 'Project Manager':
            header('Location: dashboard_pm.php');
            break;
        case 'Staff Accountant':
            header('Location: dashboard_sa.php');
            break;
        case 'Finance Manager':
            header('Location: dashboard_fm.php');
            break;
        case 'Direktur':
            header('Location: dashboard_dir.php');
            break;
    }
    exit();
}
```

**INGAT:** Aktifkan kembali OTP setelah demo selesai!

---

## 📞 Support

**Jika ada masalah:**
- Email: pblprcf@gmail.com
- Cek dokumentasi: `README_DASHBOARD.md`
- Cek database sync: `DATABASE_SYNC_REPORT.md`

---

## ✅ Checklist Demo

**Sebelum Demo:**
- [ ] XAMPP running (Apache + MySQL)
- [ ] Database sudah diimport
- [ ] Ngrok sudah setup authtoken
- [ ] Test login dengan user sample
- [ ] Test create proposal/laporan
- [ ] Backup database

**Saat Demo:**
- [ ] Start ngrok
- [ ] Share URL ke teman
- [ ] Berikan kredensial login
- [ ] Tunjukkan fitur utama setiap role
- [ ] Jelaskan workflow sistem

**Setelah Demo:**
- [ ] Stop ngrok (Ctrl+C)
- [ ] Aktifkan kembali OTP (jika di-disable)
- [ ] Restore database (jika perlu)
- [ ] Delete data testing (jika perlu)

---

**Selamat Demo! 🎉**

Aplikasi Anda siap di-demo ke teman dengan ngrok!

---

**Created:** 15 Oktober 2025  
**Version:** 1.0

