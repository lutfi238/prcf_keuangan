# 🚀 RAILWAY DEPLOYMENT GUIDE - PRCF KEUANGAN

## 📋 **PRE-DEPLOYMENT CHECKLIST**

### ✅ **1. GitHub Repository Setup**
```bash
# Initialize git if not already done
git init

# Add all files
git add .

# Commit changes
git commit -m "Initial commit for Railway deployment"

# Create GitHub repository and push
git remote add origin https://github.com/YOUR_USERNAME/prcf-keuangan.git
git push -u origin main
```

### ✅ **2. Environment Variables Preparation**
Backup these values from your local setup:
- SMTP_USER: `prcfpbl@gmail.com`
- SMTP_PASS: `ykyc bsdb vxlp xdcv`
- Any other custom configurations

---

## 🚀 **RAILWAY DEPLOYMENT STEPS**

### **Step 1: Create Railway Account**
1. Go to [railway.app](https://railway.app)
2. Sign up with GitHub account (recommended)
3. Verify your email

### **Step 2: Create New Project**
1. Click **"New Project"**
2. Select **"Deploy from GitHub repo"**
3. Connect your GitHub account
4. Select your `prcf-keuangan` repository
5. Click **"Deploy"**

### **Step 3: Configure Database**
Railway will automatically create a MySQL database for your project.

### **Step 4: Set Environment Variables**
In Railway dashboard:
1. Go to **"Variables"** tab
2. Add these variables:

```
# Email Configuration
SMTP_HOST=smtp.gmail.com
SMTP_ENCRYPTION=tls
SMTP_PORT=587
SMTP_USER=prcfpbl@gmail.com
SMTP_PASS=ykyc bsdb vxlp xdcv
FROM_EMAIL=prcfpbl@gmail.com
FROM_NAME=PRCF INDONESIA Financial

# Developer Mode (set to false for production)
DEVELOPER_MODE=false

# OTP Configuration
EMAIL_OTP_ENABLED=true
PHONE_LOGIN_ENABLED=true
SKIP_OTP_FOR_ALL=false

# Maintenance Mode (optional)
MAINTENANCE_MODE=false
```

### **Step 5: Import Database**
1. Go to Railway dashboard → **"Data"** tab
2. Click on your MySQL database
3. Go to **"Connect"** tab
4. Copy the MySQL connection command
5. Use a MySQL client or phpMyAdmin to connect
6. Import your database from `database/prcf_keuangan.sql`

**Alternative: Using Railway CLI**
```bash
# Install Railway CLI
npm install -g @railway/cli

# Login
railway login

# Link project
railway link

# Import database
railway connect mysql < database/prcf_keuangan.sql
```

### **Step 6: Configure Domain (Optional)**
1. Go to **"Settings"** tab
2. Add your custom domain
3. Update DNS records as instructed

---

## 🔧 **POST-DEPLOYMENT CHECKS**

### ✅ **1. Test Application Access**
- Visit your Railway URL (e.g., `https://prcf-keuangan.up.railway.app`)
- Check if login page loads

### ✅ **2. Test Database Connection**
Create a simple test file to verify database connection:
```php
// test_db.php
<?php
include 'includes/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Database connected successfully!";
?>
```

### ✅ **3. Test File Uploads**
- Try uploading a file through the application
- Check if files are stored properly

### ✅ **4. Test Email Functionality**
- Try the forgot password feature
- Check if emails are sent

---

## 📊 **MONITORING & LOGS**

### **View Application Logs**
```bash
railway logs
```

### **View Database Logs**
- Go to Railway dashboard → Data → Your Database → Logs

### **Monitor Performance**
- Railway dashboard → Metrics tab
- Check CPU, Memory, and Response times

---

## 🔄 **AUTO-DEPLOYMENT SETUP**

Railway automatically deploys when you push to the main branch. To set this up:

1. **Protected Main Branch** (Recommended):
   - Go to GitHub repo → Settings → Branches
   - Add rule for `main` branch
   - Require pull requests before merging

2. **Development Workflow**:
   ```bash
   # Create feature branch
   git checkout -b feature/new-feature

   # Make changes
   # Commit and push
   git add .
   git commit -m "Add new feature"
   git push origin feature/new-feature

   # Create Pull Request on GitHub
   # Merge to main → Auto-deploy to Railway
   ```

---

## 🛠️ **TROUBLESHOOTING**

### **Common Issues:**

#### **1. Database Connection Failed**
- Check environment variables in Railway dashboard
- Ensure database is properly created
- Verify database credentials

#### **2. 500 Internal Server Error**
- Check Railway logs: `railway logs`
- Look for PHP errors
- Verify file permissions in Dockerfile

#### **3. File Upload Issues**
- Check if uploads directory exists
- Verify permissions (777 for uploads)
- Check file size limits

#### **4. Email Not Sending**
- Verify SMTP credentials
- Check Railway logs for SMTP errors
- Ensure Gmail "Less secure app access" is enabled

---

## 💰 **COST MANAGEMENT**

### **Free Tier Limits:**
- 512MB RAM
- 1GB disk space
- 1GB bandwidth/month
- 100 hours active time/month

### **Upgrade Considerations:**
- If you need more resources, upgrade to $5/month plan
- Monitor usage in Railway dashboard

---

## 🔒 **SECURITY BEST PRACTICES**

### **Environment Variables:**
- ✅ Never commit secrets to GitHub
- ✅ Use Railway's environment variables
- ✅ Rotate SMTP passwords regularly

### **Database:**
- ✅ Use strong passwords
- ✅ Enable database backups
- ✅ Monitor database usage

### **Application:**
- ✅ Keep PHP updated
- ✅ Enable HTTPS (Railway does this automatically)
- ✅ Use secure session configuration

---

## 📞 **SUPPORT**

- **Railway Docs:** [docs.railway.app](https://docs.railway.app)
- **Railway Discord:** [discord.gg/railway](https://discord.gg/railway)
- **GitHub Issues:** Create issues in your repo for tracking

---

## 🎯 **NEXT STEPS**

1. **Test thoroughly** on Railway staging
2. **Migrate data** from Infinity Free
3. **Update DNS** to point to Railway
4. **Monitor performance** and scale as needed
5. **Set up monitoring** and alerts

**Happy deploying! 🚀**
