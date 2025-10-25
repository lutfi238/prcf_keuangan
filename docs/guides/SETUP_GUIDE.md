# 🚀 PRCFI Financial Dashboard - Setup Guide

## 📋 **Prerequisites**

- PHP 7.4+ (tested with PHP 8.0)
- MySQL/MariaDB
- Apache/Nginx web server
- Composer (optional, for dependencies)

---

## ⚡ **Quick Setup (5 Minutes)**

### **Step 1: Clone Repository**

```bash
git clone git@github.com:lutfi238/prcf_keuangan.git
cd prcf_keuangan
```

### **Step 2: Database Setup**

1. **Create database:**
   ```sql
   CREATE DATABASE prcf_keuangan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Import SQL schema** (jika ada file SQL):
   ```bash
   mysql -u root -p prcf_keuangan < database/schema.sql
   ```

### **Step 3: Configuration**

1. **Copy config template:**
   ```bash
   cp config.example.php config.php
   cp maintenance_config.example.php maintenance_config.php
   ```

2. **Edit config.php:**
   ```php
   // Database credentials
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'prcf_keuangan');
   
   // WhatsApp OTP (Fonnte)
   define('FONNTE_TOKEN', 'your_fonnte_token_here');
   ```

3. **Get Fonnte Token:**
   - Daftar di [fonnte.com](https://fonnte.com)
   - Connect WhatsApp device
   - Copy API token dari dashboard
   - Paste ke `config.php`

### **Step 4: File Permissions**

```bash
chmod 755 *.php
chmod 644 config.php maintenance_config.php
```

### **Step 5: Test**

```bash
# Start development server (PHP built-in)
php -S localhost:8000

# Or use XAMPP/MAMP/Laragon
# Visit: http://localhost/prcf_keuangan
```

---

## 🔑 **Login Credentials**

### **Test Accounts:**

| Role | Username | Password |
|------|----------|----------|
| Project Manager | pm_test | password123 |
| Finance Manager | fm_test | password123 |
| Staff Accountant | sa_test | password123 |
| Director | dir_test | password123 |

> ⚠️ **PRODUCTION:** Change passwords immediately!

---

## 📱 **WhatsApp OTP Setup**

### **Fonnte (FREE - 100 msg/month)**

1. **Register:** https://fonnte.com
2. **Connect WhatsApp** (scan QR code)
3. **Get API Token** from dashboard
4. **Update config.php:**
   ```php
   define('FONNTE_TOKEN', 'YOUR_TOKEN_HERE');
   define('WA_OTP_ENABLED', true);
   ```
5. **Restart server**

**Documentation:** See `SETUP_FONNTE.md` for detailed guide

---

## 🔧 **Maintenance Mode**

### **Enable Maintenance:**

```php
// maintenance_config.php
define('MAINTENANCE_MODE', true);
```

### **Disable Maintenance:**

```php
// maintenance_config.php
define('MAINTENANCE_MODE', false);
```

**Documentation:** See `MAINTENANCE_MODE_GUIDE.md`

---

## 📚 **Documentation**

| File | Description |
|------|-------------|
| `README_DASHBOARD.md` | Dashboard overview |
| `SETUP_FONNTE.md` | WhatsApp OTP setup |
| `MAINTENANCE_MODE_GUIDE.md` | Maintenance how-to |
| `FUTURE_OTP_IMPROVEMENTS.md` | Upgrade recommendations |
| `QUICK_REFERENCE.md` | Quick commands |

---

## 🚨 **Security**

### **NEVER Commit:**

- ❌ `config.php` (contains credentials)
- ❌ `maintenance_config.php` (contains IP whitelist)
- ❌ `*.log` files
- ❌ Database backups (*.sql)

Already protected by `.gitignore` ✅

### **Production Checklist:**

- [ ] Change default passwords
- [ ] Update database credentials
- [ ] Configure Fonnte token
- [ ] Set proper file permissions
- [ ] Enable HTTPS
- [ ] Disable debug mode
- [ ] Regular backups

---

## 🐛 **Troubleshooting**

### **Database Connection Failed**

```
Error: Connection failed
```

**Fix:**
- Check DB credentials in `config.php`
- Verify MySQL is running
- Test connection: `mysql -u root -p`

### **WhatsApp OTP Not Sending**

```
Error: Fonnte token not configured
```

**Fix:**
- Verify `FONNTE_TOKEN` in `config.php`
- Check Fonnte dashboard: device online?
- Check quota: 100 messages/month (free tier)

### **Maintenance Page Not Working**

```
Error: Still can access pages
```

**Fix:**
- Check `MAINTENANCE_MODE = true` in `maintenance_config.php`
- Restart Apache/server
- Clear browser cache (Ctrl + Shift + R)

---

## 📞 **Support**

- **Issues:** [GitHub Issues](https://github.com/lutfi238/prcf_keuangan/issues)
- **Email:** pblprcf@gmail.com
- **Documentation:** See `*.md` files in project root

---

## 📄 **License**

[Add your license here]

---

## 🙏 **Credits**

- **Developer:** [Your Name]
- **Project:** PRCFI Financial Management Dashboard
- **Year:** 2025

---

**Happy Coding! 💪**

