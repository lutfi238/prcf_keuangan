# 🚀 RENDER DEPLOYMENT GUIDE - PRCF KEUANGAN

## 📋 **WHY RENDER?**
Railway free tier only supports databases, but Render's free tier supports full web applications!

## 🚀 **RENDER DEPLOYMENT STEPS**

### **Step 1: Create Render Account**
1. Go to [render.com](https://render.com)
2. Sign up with GitHub account (recommended)
3. Verify your email

### **Step 2: Create Web Service**
1. Click **"New"** → **"Web Service"**
2. Connect your GitHub repository
3. Select your `prcf-keuangan` repository
4. Configure settings:

**Service Details:**
- **Name:** `prcf-keuangan`
- **Environment:** `Docker`
- **Region:** `Singapore` (recommended for Indonesia)

**Build Settings:**
- **Dockerfile Path:** `./Dockerfile` (sudah ada)

### **Step 3: Environment Variables**
Add these environment variables:

```
# Database (akan dibuat terpisah)
DB_HOST=YOUR_RENDER_DB_HOST
DB_USER=YOUR_RENDER_DB_USER
DB_PASS=YOUR_RENDER_DB_PASSWORD
DB_NAME=YOUR_RENDER_DB_NAME

# Email Configuration
SMTP_HOST=smtp.gmail.com
SMTP_ENCRYPTION=tls
SMTP_PORT=587
SMTP_USER=prcfpbl@gmail.com
SMTP_PASS=ykyc bsdb vxlp xdcv
FROM_EMAIL=prcfpbl@gmail.com
FROM_NAME=PRCF INDONESIA Financial

# Application Settings
DEVELOPER_MODE=false
EMAIL_OTP_ENABLED=true
PHONE_LOGIN_ENABLED=true
SKIP_OTP_FOR_ALL=false
```

### **Step 4: Create Database**
1. Click **"New"** → **"PostgreSQL"** (atau MySQL jika available)
2. **Name:** `prcf-keuangan-db`
3. **Database:** `prcf_keuangan`
4. **Region:** `Singapore`

### **Step 5: Connect Database**
1. Copy database credentials dari database dashboard
2. Paste ke web service environment variables
3. **Deploy** web service

### **Step 6: Import Database**
```bash
# Connect to Render database
psql "postgresql://user:password@host:5432/database"

# Import data (convert MySQL to PostgreSQL if needed)
# Atau gunakan pgloader untuk migrasi
```

---

## 💰 **RENDER FREE TIER:**
- ✅ **750 jam/bulan** (full application)
- ✅ **750 jam database/bulan**
- ✅ **Auto-sleep** setelah 15 menit tidak aktif
- ✅ **SSL otomatis**
- ✅ **Custom domain support**

---

## 🔄 **AUTO-DEPLOYMENT**
Render otomatis deploy ketika push ke branch yang di-set (default: main)

---

## 🎯 **ALTERNATIVES LAIN:**

### **Option 2: Heroku (Free Tier Available)**
- 550 jam/bulan
- PHP support penuh
- MySQL add-on (berbayar)

### **Option 3: Fly.io**
- 3 VM hours/bulan free
- PHP support
- Persistent storage

### **Option 4: DigitalOcean App Platform**
- $5/bulan untuk aplikasi kecil
- PHP + MySQL support
- Auto-deployment dari GitHub

---

## 🆚 **PERBANDINGAN:**

| Platform | Free Tier | PHP Support | Database | Auto-Deploy |
|----------|-----------|-------------|----------|-------------|
| Railway | ❌ Database only | ❌ | ✅ | ✅ |
| **Render** | ✅ 750 jam app | ✅ Docker | ✅ | ✅ |
| Heroku | ✅ 550 jam | ✅ Native | Add-on | ✅ |
| Fly.io | ✅ 3 jam VM | ✅ Docker | ✅ | ✅ |

**Rekomendasi: Render** - Free tier support full application! 🚀
