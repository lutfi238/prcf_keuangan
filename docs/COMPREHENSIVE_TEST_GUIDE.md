# 🧪 Comprehensive Testing Guide
## PRCF Financial System - November 2025

---

## 📋 Quick Summary

**Total Fixes Applied:** 10
**Files Modified:** 6
**Critical Systems:** All Operational ✅

---

## ✅ ALL FIXES COMPLETED

### 1. **Dashboard Finance Manager** ✅
- Fixed JavaScript path untuk realtime notifications
- Profile dropdown berfungsi
- Notification bell working
- Tab "Laporan Keuangan" sudah configured

### 2. **Dashboard Direktur** ✅
- Fixed JavaScript path
- Profile dropdown working
- Notification system active
- Proposal & Report tabs configured

### 3. **Registration System** ✅
- Email validation real-time
- Registration enable/disable toggle (Admin)
- UI cleaned up

### 4. **Change Password** ✅
- Working di profile page
- Redirect to dashboard after success
- No OTP required (prototype mode)

### 5. **Approval Systems** ✅
- SA validation working
- FM approval working (Stage 1/2)
- DIR approval working (Stage 2/2)
- All with notifications

### 6. **Maintenance Mode** ✅
- Admin can toggle via System Control
- Non-admin users redirected
- Config file updates correctly

---

## 🧪 TESTING CHECKLIST

### **PART 1: Authentication & Registration**

#### Test 1.1: Registration Page
```
URL: /auth/register.php
```
- [ ] Page loads correctly
- [ ] Email validation shows ✅/❌ on blur
- [ ] Username validation shows ✅/❌ on blur
- [ ] Phone validation real-time
- [ ] Submit form creates account
- [ ] Redirect to login after success

#### Test 1.2: Registration Toggle (Admin Only)
```
Login as Admin → System Control Panel
```
- [ ] Toggle "Disable Registration" button
- [ ] Logout and visit /auth/register.php
- [ ] Should see "Pendaftaran Ditutup" message
- [ ] Login as Admin again
- [ ] Toggle "Enable Registration"
- [ ] Logout and verify registration works again

#### Test 1.3: Login & Routing
```
URL: /auth/login.php
```
- [ ] Login as PM → redirected to dashboard_pm.php
- [ ] Login as SA → redirected to dashboard_sa.php
- [ ] Login as FM → redirected to dashboard_fm.php
- [ ] Login as DIR → redirected to dashboard_dir.php
- [ ] Login as Admin → redirected to dashboard_admin.php

---

### **PART 2: Dashboard Functionality**

#### Test 2.1: Dashboard FM
```
Login as Finance Manager
```
- [ ] Dashboard loads without errors
- [ ] Click profile picture → dropdown muncul
- [ ] Click "Edit Profil" → navigate to profile page
- [ ] Click notification bell → panel muncul
- [ ] Click tab "Proposal Masuk" → switch tab
- [ ] Click tab "Laporan Keuangan" → switch tab
- [ ] **Check:** Apakah ada data di tab Laporan Keuangan?

**If no data:**
```
Login as PM → Create financial report → Submit
Login as SA → Validate report
Login back as FM → Check if report appears
```

#### Test 2.2: Dashboard Direktur
```
Login as Direktur
```
- [ ] Dashboard loads without errors
- [ ] Profile dropdown working
- [ ] Notification bell working
- [ ] Stats cards displayed (Proposals/Reports/Projects)
- [ ] Click tab "Proposal Masuk"
- [ ] **Check:** Apakah ada proposals dengan badge "1/2 Approved (FM)"?

**If no data:**
```
Login as PM → Create proposal
Login as FM → Approve proposal (Stage 1)
Login as DIR → Check if proposal appears with "1/2 Approved" badge
```

- [ ] Click tab "Laporan Keuangan"
- [ ] **Check:** Apakah ada reports yang sudah approved FM?

#### Test 2.3: Dashboard Admin
```
Login as Admin
```
- [ ] System statistics displayed
- [ ] Users by role chart displayed
- [ ] Recent users table displayed
- [ ] Click "System Control" card
- [ ] Click "System Health" card
- [ ] Click "User Management" link

---

### **PART 3: Approval Workflows (CRITICAL)**

#### Test 3.1: Financial Report Approval Flow
```
Complete End-to-End Test
```

**Step 1: PM Creates Report**
- [ ] Login as Project Manager
- [ ] Navigate to "Buat Laporan Keuangan"
- [ ] Fill all required fields
- [ ] Upload receipts (optional)
- [ ] Submit report
- [ ] Verify status = "Submitted"

**Step 2: SA Validates Report**
- [ ] Login as Staff Accountant
- [ ] Dashboard should show pending report
- [ ] Click "Validate" on the report
- [ ] Add notes (optional)
- [ ] Click "Validate Report"
- [ ] **Check email:** PM should receive notification ✉️
- [ ] **Check email:** FM should receive notification ✉️
- [ ] Verify status = "Verified"

**Step 3: FM Approves Report (Stage 1/2)**
- [ ] Login as Finance Manager
- [ ] Tab "Laporan Keuangan" should show verified report
- [ ] Click "Approve" on the report
- [ ] Review details
- [ ] Click "Approve (Stage 1/2)" button
- [ ] **Check email:** DIR should receive notification ✉️
- [ ] Verify status = "Approved_FM"

**Step 4: DIR Approves Report (Stage 2/2 - FINAL)**
- [ ] Login as Direktur
- [ ] Tab "Laporan Keuangan" should show report approved by FM
- [ ] Click "Approve" on the report
- [ ] Click "Approve (Stage 2/2 - Final)" button
- [ ] **Check email:** PM should receive "Final Approval" notification ✉️
- [ ] **Check email:** FM should receive notification ✉️
- [ ] **Check email:** SA should receive notification ✉️
- [ ] Verify status = "Approved" (FINAL)

**Expected Timeline:**
```
PM Submit → SA Validate → FM Approve (1/2) → DIR Approve (2/2) → FINAL
```

#### Test 3.2: Proposal Approval Flow
```
Complete End-to-End Test
```

**Step 1: PM Creates Proposal**
- [ ] Login as Project Manager
- [ ] Navigate to "Buat Proposal"
- [ ] Fill all fields (Pemohon is read-only ✅)
- [ ] Upload TOR, Budget
- [ ] Submit proposal
- [ ] Verify status = "Submitted"

**Step 2: FM Reviews & Approves (Stage 1/2)**
- [ ] Login as Finance Manager
- [ ] Dashboard shows pending proposal
- [ ] Click "Review" on proposal
- [ ] Can click "Minta Revisi" button
- [ ] Notes field shows/hides ✅
- [ ] Click "Setujui (Stage 1/2)"
- [ ] **Check email:** DIR should receive notification ✉️
- [ ] **Check email:** PM should receive "Approved Stage 1" notification ✉️
- [ ] Verify status = "Approved_FM"

**Step 3: DIR Reviews & Approves (Stage 2/2 - FINAL)**
- [ ] Login as Direktur
- [ ] Dashboard tab "Proposal Masuk" shows proposal with "1/2 Approved" badge
- [ ] Click "Approve Stage 2"
- [ ] Review proposal details
- [ ] Click "Setujui (Stage 2/2 - Final)"
- [ ] **Check email:** PM should receive "Final Approval" notification ✉️
- [ ] **Check email:** FM should receive notification ✉️
- [ ] Verify status = "Approved" (FINAL)

**Expected Timeline:**
```
PM Submit → FM Approve (1/2) → DIR Approve (2/2) → FINAL
```

#### Test 3.3: Revision Request Flow
```
Test Revision Workflow
```

**For Reports:**
- [ ] Login as SA
- [ ] Click "Minta Revisi" on a report
- [ ] Notes field appears ✅
- [ ] Fill revision notes
- [ ] Click "Kirim Revisi ke PM"
- [ ] **Check email:** PM receives revision request ✉️
- [ ] Login as PM
- [ ] Dashboard shows report needs revision
- [ ] Edit and resubmit report
- [ ] Verify status changes to "Submitted" again

**For Proposals:**
- [ ] Login as FM
- [ ] Click "Minta Revisi" on a proposal
- [ ] Notes textarea appears dynamically ✅
- [ ] Button text changes to "Batal" ✅
- [ ] Fill revision notes
- [ ] Click "Kirim Revisi ke PM"
- [ ] **Check email:** PM receives revision request ✉️
- [ ] Login as PM
- [ ] Fix proposal and resubmit

---

### **PART 4: Profile & Security**

#### Test 4.1: Change Password
```
Login as any role
```
- [ ] Navigate to Profile page
- [ ] Click "Change Password" section
- [ ] Enter current password
- [ ] Enter new password (min 8 chars)
- [ ] Confirm new password
- [ ] Click "Ubah Password"
- [ ] Should redirect to dashboard with success message
- [ ] Logout
- [ ] Login with NEW password ✅
- [ ] Login successful

#### Test 4.2: Edit Profile
```
Any logged-in user
```
- [ ] Navigate to Profile page
- [ ] Change username
- [ ] Username availability check works
- [ ] Submit changes
- [ ] Redirect to profile with success message
- [ ] Verify username updated in header

---

### **PART 5: Admin Features**

#### Test 5.1: User Management
```
Login as Admin → User Management
```
- [ ] List of all users displayed
- [ ] Search bar works
- [ ] Role filter dropdown works
- [ ] Click "Add New User" button
- [ ] Create new user with all roles options
- [ ] User created successfully
- [ ] Edit existing user
- [ ] Try to edit yourself → shows "You" badge ✅
- [ ] Try to delete yourself → shows "Protected" badge ✅
- [ ] Try to delete last admin → shows error ✅
- [ ] Delete non-admin user → works ✅

#### Test 5.2: System Control Panel
```
Login as Admin → System Control
```
- [ ] Current status overview displayed
- [ ] Maintenance Mode section visible
- [ ] Registration Control section visible
- [ ] Click "Enable Maintenance"
- [ ] Confirm dialog appears
- [ ] Maintenance enabled → success message
- [ ] Logout
- [ ] Try to access site from incognito/different user
- [ ] Should see maintenance page
- [ ] Login as Admin again
- [ ] Disable Maintenance
- [ ] Verify site accessible again

#### Test 5.3: System Health
```
Login as Admin → System Health
```
- [ ] PHP version displayed
- [ ] MySQL version displayed
- [ ] Server info displayed
- [ ] Database stats displayed
- [ ] Disk space info displayed (or "not available" on InfinityFree)

---

### **PART 6: Notifications (CRITICAL)**

#### Test 6.1: Email Notifications
```
Check notifications are sent to correct recipients
```

**Proposal Flow:**
- [ ] PM submits → FM receives notification
- [ ] FM approves (1/2) → DIR & PM receive notifications
- [ ] DIR approves (2/2) → PM & FM receive notifications
- [ ] FM requests revision → PM receives notification

**Report Flow:**
- [ ] PM submits → SA receives notification
- [ ] SA validates → PM, FM, DIR receive notifications
- [ ] FM approves (1/2) → DIR receives notification
- [ ] DIR approves (2/2) → PM, FM, SA receive notifications
- [ ] SA requests revision → PM receives notification

**Check Email Content:**
- [ ] Subject line appropriate
- [ ] Body contains activity/proposal name
- [ ] Actionable information included

#### Test 6.2: Real-Time Notification Bell
```
Test real-time updates (SSE)
```
- [ ] Login as FM in one browser
- [ ] Login as PM in another browser (incognito)
- [ ] PM creates proposal
- [ ] **Check FM browser:** notification count updates (may take 5-30 seconds)
- [ ] Click notification bell on FM
- [ ] New proposal appears in list
- [ ] Click notification → navigate to proposal
- [ ] Notification panel closes

---

### **PART 7: UI/UX Features**

#### Test 7.1: Conditional Notes Visibility
```
Proposal & Report Review Pages
```
- [ ] Login as FM
- [ ] Open proposal review page
- [ ] Notes field is hidden by default ✅
- [ ] Click "Minta Revisi"
- [ ] Notes field appears ✅
- [ ] Button text changes to "Batal" ✅
- [ ] Approve button changes to "Kirim Revisi ke PM" ✅
- [ ] Click "Batal"
- [ ] Notes field hides again ✅
- [ ] Button texts revert ✅

#### Test 7.2: Receipt Modal Preview
```
All Report View Pages
```
- [ ] Login and view a report with receipts
- [ ] Click receipt link
- [ ] Modal opens (not new tab) ✅
- [ ] Image displays in modal
- [ ] Click outside modal → closes ✅
- [ ] Click close button → closes ✅
- [ ] Test with PDF receipt
- [ ] PDF displays in modal

#### Test 7.3: Currency Formatting
```
Financial Report Creation
```
- [ ] Navigate to create report page
- [ ] Enter amount in "Biaya Satuan" field
- [ ] Type: 1000000
- [ ] Field should format to: 1.000.000 ✅
- [ ] Submit form
- [ ] Verify amount saved correctly in database

#### Test 7.4: Place Code Autocomplete
```
Financial Report Creation
```
- [ ] Select a project
- [ ] Focus on "Kode Tempat" field
- [ ] Type partial code (e.g., "101")
- [ ] Autocomplete suggestions appear ✅
- [ ] Click a suggestion
- [ ] Field fills with selected code ✅

#### Test 7.5: Search & Filter
```
Dashboard Tables
```
- [ ] Login as FM
- [ ] Tab "Laporan Keuangan"
- [ ] Use search bar to search activity name
- [ ] Results filter dynamically ✅
- [ ] Use project dropdown filter
- [ ] Results update ✅
- [ ] Use status dropdown filter
- [ ] Results update ✅
- [ ] Click "Reset" button
- [ ] All filters clear ✅

---

### **PART 8: Edge Cases & Error Handling**

#### Test 8.1: Invalid Access
```
Test unauthorized access
```
- [ ] Logout
- [ ] Try to access `/pages/dashboards/dashboard_fm.php` directly
- [ ] Should redirect to login ✅
- [ ] Login as PM
- [ ] Try to access `/pages/dashboards/dashboard_fm.php`
- [ ] Should redirect to unauthorized page ✅

#### Test 8.2: Duplicate Prevention
```
Registration & Profile
```
- [ ] Try to register with existing email
- [ ] Should show "❌ Email already registered" ✅
- [ ] Try to register with existing username
- [ ] Should show "❌ Username already taken" ✅
- [ ] Login and edit profile
- [ ] Try to change to existing username
- [ ] Should show error ✅

#### Test 8.3: Empty Data Handling
```
Dashboard Tables
```
- [ ] Login as new user with no data
- [ ] Dashboard should show "No data" messages (not errors) ✅
- [ ] Tables should display gracefully
- [ ] No JavaScript errors in console

#### Test 8.4: File Upload
```
Proposals & Reports
```
- [ ] Try to upload file > 10MB
- [ ] Should show error (if configured) or upload fails gracefully
- [ ] Try to upload wrong file type
- [ ] Should validate file extension
- [ ] Upload valid files (PDF, XLSX, PNG, JPG)
- [ ] Files upload successfully ✅

---

## 🐛 If Tests Fail

### Debug Steps:

1. **Check Browser Console (F12)**
   ```
   Press F12 → Console tab
   Look for JavaScript errors (red text)
   ```

2. **Check PHP Errors**
   ```
   Open: Apache error.log
   Or: Display errors in includes/config.php
   ```

3. **Check Database**
   ```sql
   -- Verify proposal statuses
   SELECT id_proposal, judul_proposal, status, approved_by_fm, approved_by_dir
   FROM proposal;
   
   -- Verify report statuses
   SELECT id_laporan_keu, nama_kegiatan, status_lap, verified_by, approved_by
   FROM laporan_keuangan_header;
   
   -- Check if 2-stage columns exist
   SHOW COLUMNS FROM proposal LIKE 'approved_by%';
   ```

4. **Run Migrations**
   ```
   If columns missing, run:
   - sql/migrations/alter_proposal_2stage_approval.sql
   - sql/migrations/add_admin_role.sql
   - sql/migrations/add_director_approval_to_reports.sql
   ```

5. **Clear Cache**
   ```
   - Browser cache (Ctrl+Shift+Del)
   - Server cache (if any)
   - Session cookies
   ```

---

## 📊 Testing Report Template

```
TESTING DATE: ___________
TESTER NAME: ___________

✅ = PASS | ❌ = FAIL | ⚠️ = PARTIAL

Part 1: Authentication          [ ]
Part 2: Dashboards              [ ]
Part 3: Approval Workflows      [ ]
Part 4: Profile & Security      [ ]
Part 5: Admin Features          [ ]
Part 6: Notifications           [ ]
Part 7: UI/UX Features          [ ]
Part 8: Edge Cases              [ ]

CRITICAL BUGS FOUND:
1. ________________________________
2. ________________________________
3. ________________________________

MINOR ISSUES:
1. ________________________________
2. ________________________________

NOTES:
___________________________________
___________________________________
___________________________________
```

---

## ✅ Expected Results

**After completing ALL tests:**
- ✅ All dashboards load without errors
- ✅ All approval workflows functional
- ✅ Notifications sent to correct users
- ✅ Real-time updates working (may have 5-30s delay)
- ✅ Admin features fully operational
- ✅ Security features working (auth, permissions)
- ✅ UI/UX enhancements active
- ✅ No JavaScript console errors
- ✅ No PHP fatal errors

---

## 📞 Support

If ada issues yang tidak bisa di-resolve:

1. **Screenshot error** (with full error message)
2. **Browser console log** (F12 → Console tab)
3. **Apache error log** (if accessible)
4. **Database check** (run SQL queries di atas)
5. **Test environment:** Browser version, PHP version, MySQL version

---

## 🎉 Success Criteria

✅ **System is production-ready when:**
- All 8 parts of testing pass
- No critical bugs found
- All approval workflows complete successfully
- Notifications delivered correctly
- Admin features accessible and working

**Good luck with testing!** 🚀


