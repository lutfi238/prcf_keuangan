# ⚡ Quick Deployment Checklist

## Files to Upload (Modified Files Only)

### ✅ Upload These 6 Files:

```
1. pages/dashboards/dashboard_dir.php        (Fixed JS path)
2. pages/dashboards/dashboard_fm.php         (Already OK, no changes needed)
3. pages/dashboards/dashboard_admin.php      (Added spacing)
4. auth/register.php                         (Email validation + Registration control)
5. pages/admin/system_control.php            (Added Registration toggle)
6. includes/maintenance_config.php           (Added REGISTRATION_ENABLED constant)
```

---

## 🗄️ Database Migrations (If Not Run Yet)

Run these SQL files in phpMyAdmin **in order:**

### 1. Check if migrations needed:
```sql
-- Check if approved_by_fm column exists
SHOW COLUMNS FROM proposal LIKE 'approved_by%';

-- Check if Admin role exists
SELECT DISTINCT role FROM user;

-- Check if director approval columns exist  
SHOW COLUMNS FROM laporan_keuangan_header LIKE 'approved_by_dir';
```

### 2. Run migrations if needed:
```sql
-- If approved_by_fm missing:
SOURCE sql/migrations/alter_proposal_2stage_approval.sql;

-- If Admin role missing:
SOURCE sql/migrations/add_admin_role.sql;

-- If director approval columns missing:
SOURCE sql/migrations/add_director_approval_to_reports.sql;
```

---

## ⚙️ Server Configuration

### If on InfinityFree or similar hosting:

1. **Upload `.htaccess` file** (if not exists):
```apache
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 300
php_value max_input_time 300
```

2. **Verify includes/config.php has correct database credentials**

3. **Set file permissions:**
```bash
chmod 755 includes/
chmod 644 includes/*.php
chmod 777 uploads/
chmod 777 uploads/receipts/
chmod 777 uploads/budgets/
chmod 777 uploads/tor/
```

---

## 🧪 Post-Deployment Quick Test

### 1. Test Dashboards (2 minutes):
```
✅ Visit /pages/dashboards/dashboard_fm.php
   → Profile dropdown works?
   → Notification bell clickable?
   
✅ Visit /pages/dashboards/dashboard_dir.php
   → Same checks
```

### 2. Test Registration (1 minute):
```
✅ Visit /auth/register.php
   → Type email → Blur → Shows ✅ or ❌?
   → Type username → Blur → Shows status?
```

### 3. Test Admin Panel (2 minutes):
```
✅ Login as Admin
✅ Visit System Control Panel
   → Can see Registration Control section?
   → Try toggle registration
   → Logout and verify registration page changes
```

### 4. Test One Approval Flow (5 minutes):
```
✅ PM creates proposal
✅ FM approves (Stage 1/2)
✅ DIR sees proposal with "1/2 Approved" badge
✅ DIR approves (Stage 2/2)
✅ Check if emails sent
```

---

## 🚨 Troubleshooting

### Issue: "Call to undefined function"
**Fix:** Check includes/config.php has all helper functions

### Issue: "Column not found"
**Fix:** Run database migrations listed above

### Issue: JavaScript errors in console
**Fix:** Clear browser cache (Ctrl+Shift+Del)

### Issue: Can't upload files
**Fix:** Check folder permissions (chmod 777 uploads/)

### Issue: Email notifications not sending
**Fix:** 
1. Check includes/config.php email settings
2. Verify server supports mail() function
3. Check spam folder

---

## ✅ Deployment Complete When:

- [ ] All 6 files uploaded successfully
- [ ] Database migrations run (if needed)
- [ ] File permissions set correctly
- [ ] Quick tests pass (dashboards load, dropdowns work)
- [ ] Admin can toggle registration
- [ ] At least one approval flow works end-to-end

---

## 📞 Emergency Rollback

If something breaks badly:

1. **Restore previous versions of modified files**
2. **Or upload backup from git:**
   ```bash
   git checkout HEAD~1 pages/dashboards/dashboard_dir.php
   git checkout HEAD~1 auth/register.php
   # etc...
   ```

---

**Estimated Deployment Time:** 10-15 minutes  
**Estimated Testing Time:** 10-15 minutes  
**Total:** ~30 minutes

Good luck! 🚀


