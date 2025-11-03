# 🚀 HEROKU DEPLOYMENT GUIDE - PRCF KEUANGAN

## 📋 **WHY HEROKU?**
Heroku punya free tier yang support PHP applications penuh!

## 🚀 **HEROKU DEPLOYMENT STEPS**

### **Step 1: Install Heroku CLI**
```bash
# Download dari https://devcenter.heroku.com/articles/heroku-cli
# Atau via npm:
npm install -g heroku
```

### **Step 2: Login & Create App**
```bash
# Login
heroku login

# Create app
heroku create prcf-keuangan --region asia

# Add MySQL add-on (free tier available)
heroku addons:create jawsdb:kitefin --as=DATABASE
```

### **Step 3: Configure Environment Variables**
```bash
# Set environment variables
heroku config:set SMTP_HOST=smtp.gmail.com
heroku config:set SMTP_ENCRYPTION=tls
heroku config:set SMTP_PORT=587
heroku config:set SMTP_USER=prcfpbl@gmail.com
heroku config:set SMTP_PASS=ykyc bsdb vxlp xdcv
heroku config:set FROM_EMAIL=prcfpbl@gmail.com
heroku config:set FROM_NAME="PRCF INDONESIA Financial"
heroku config:set DEVELOPER_MODE=false
```

### **Step 4: Deploy**
```bash
# Push ke Heroku
git push heroku main

# Atau jika branch berbeda
git push heroku your-branch:main
```

### **Step 5: Import Database**
```bash
# Get database URL
heroku config:get DATABASE_URL

# Import menggunakan MySQL client
mysql -u user -p -h host database_name < database/prcf_keuangan.sql
```

---

## 💰 **HEROKU FREE TIER:**
- ✅ **550 jam/bulan** (full application)
- ✅ **JawsDB MySQL** (free tier: 5MB)
- ✅ **SSL otomatis**
- ✅ **Auto-sleep** setelah 30 menit

---

## 🔄 **AUTO-DEPLOYMENT**
Heroku otomatis deploy ketika push ke branch yang di-set

---

## 📋 **Procfile (Buat file ini)**
```
web: vendor/bin/heroku-php-apache2
```

---

## 🎯 **KEUNTUNGAN HEROKU:**
- Native PHP support (tidak perlu Docker)
- Mudah setup untuk PHP applications
- Banyak dokumentasi
- Community besar

**Coba Heroku dulu, lebih mudah untuk PHP!** 🚀
