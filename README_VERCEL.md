# 🚀 PRCF Keuangan - Vercel Deployment Guide

## 📋 **What's Been Converted**

This project has been converted from PHP+MySQL to **Next.js+PostgreSQL** for Vercel deployment:

### ✅ **Completed Conversions:**
- **Authentication System** - JWT-based login with OTP
- **Database Schema** - MySQL → PostgreSQL
- **Admin Dashboard** - Basic admin interface
- **API Routes** - RESTful API endpoints
- **Middleware** - Authentication protection

### 🔄 **Architecture Changes:**
- ❌ **PHP + Apache** → ✅ **Next.js + Vercel**
- ❌ **MySQL** → ✅ **PostgreSQL (Vercel)**
- ❌ **Sessions** → ✅ **JWT Tokens**
- ❌ **Local Files** → ✅ **Vercel Blob Storage**

## 🚀 **Vercel Deployment Steps**

### **Step 1: Connect to Vercel**
1. Go to [vercel.com](https://vercel.com)
2. Sign up/Login with GitHub
3. Click **"New Project"**
4. Import your `prcf-keuangan` repository

### **Step 2: Configure Project**
```
Framework Preset: Next.js
Root Directory: ./
Build Command: npm run build
Output Directory: .next
```

### **Step 3: Environment Variables**
Add these in Vercel dashboard:

```bash
# Database (auto-configured by Vercel Postgres)
# POSTGRES_URL=...
# POSTGRES_USER=...
# POSTGRES_PASSWORD=...
# POSTGRES_DATABASE=...

# JWT Secret (generate a strong one)
JWT_SECRET=your-super-secret-jwt-key-change-this

# Email Configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
FROM_EMAIL=your-email@gmail.com
FROM_NAME=PRCF INDONESIA Financial

# Application Settings
DEVELOPER_MODE=false
SKIP_OTP_FOR_ALL=false
EMAIL_OTP_ENABLED=true
PHONE_LOGIN_ENABLED=true

# Vercel Blob (for file uploads)
BLOB_READ_WRITE_TOKEN=vercel_blob_xxxxxxxxxxxxxxxxxxxx
```

### **Step 4: Set Up Database**
1. In Vercel dashboard → **"Storage"** → **"Create Database"** → **"Postgres"**
2. Connect it to your project
3. Run database initialization:
```bash
curl -X POST https://your-app.vercel.app/api/init-db
```

### **Step 5: Deploy**
1. Click **"Deploy"**
2. Wait for deployment to complete
3. Your app will be live at `https://prcf-keuangan.vercel.app`

## 🔐 **Default Admin Account**

After database initialization:
- **Email:** `admin@prcf.com`
- **Password:** `admin123`
- **Role:** Admin

## 📁 **Project Structure**

```
app/
├── api/                    # API routes
│   ├── auth/              # Authentication endpoints
│   │   ├── login/         # Login API
│   │   └── logout/        # Logout API
│   └── init-db/           # Database initialization
├── admin/                 # Admin dashboard
├── auth/                  # Authentication pages
│   └── login/            # Login page
├── dashboard/            # User dashboards
├── layout.tsx            # Root layout
├── page.tsx              # Home page
└── globals.css           # Global styles

lib/
├── db.ts                 # Database utilities
├── db-schema.sql         # PostgreSQL schema
└── init-db.ts           # Database initialization
```

## 🛠️ **Development Commands**

```bash
# Install dependencies
npm install

# Run development server
npm run dev

# Build for production
npm run build

# Start production server
npm start

# Initialize database (development only)
curl -X POST http://localhost:3000/api/init-db
```

## 🔧 **Key Differences from PHP Version**

### **Authentication:**
- JWT tokens instead of PHP sessions
- API-based authentication
- OTP via email (same functionality)

### **Database:**
- PostgreSQL instead of MySQL
- Vercel Postgres hosting
- SQL syntax adjustments

### **File Storage:**
- Vercel Blob Storage for uploads
- No local file system access

### **Deployment:**
- Serverless functions
- Automatic scaling
- Global CDN

## 🚨 **Still To Be Converted**

- [ ] Complete dashboard pages for all roles
- [ ] Proposal management system
- [ ] Report generation
- [ ] File upload handling
- [ ] Email notification system
- [ ] Real-time notifications

## 📞 **Support**

- **Vercel Docs:** [vercel.com/docs](https://vercel.com/docs)
- **Next.js Docs:** [nextjs.org/docs](https://nextjs.org/docs)
- **PostgreSQL Docs:** [postgresql.org/docs](https://postgresql.org/docs)

---

**🎉 Your PHP app is now ready for Vercel's modern, scalable platform!**
