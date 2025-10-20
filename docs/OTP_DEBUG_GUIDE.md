# 🔍 DEBUG GUIDE - OTP Stuck Issue

## Current Status
✅ Test redirect methods: **ALL SUCCESSFUL**  
❌ Real OTP login: **STILL STUCK**

**Conclusion:** Masalah BUKAN di redirect method, tapi di **form submission** atau **OTP verification logic**.

---

## 🧪 DEBUGGING STEPS

### Step 1: Check Browser Console

1. Open browser Developer Tools (F12)
2. Go to **Console** tab
3. Try login with OTP
4. Check for JavaScript errors or logs

**Expected logs:**
```
✅ verify_otp.php loaded
🔍 Form submitting...
OTP value: 123456
```

**If you see errors:** Screenshot dan report!

---

### Step 2: Check Error Log (PENTING!)

```bash
# Windows
notepad C:\xampp\apache\logs\error.log

# Look for RECENT lines with:
🔍 verify_otp.php - POST received
🔍 OTP Comparison - Entered: 'xxx' vs Session: 'yyy'
❌ OTP Wrong
✅ OTP Verified
```

**What to look for:**

| Log Message | Meaning |
|------------|---------|
| `🔍 POST received` | Form submitted ✅ |
| `verify_otp_isset: false` | Button not clicked ❌ |
| `OTP Comparison` | Comparing values ✅ |
| `❌ OTP Wrong` | Values don't match ❌ |
| `❌ OTP Expired` | Timeout > 60s ❌ |
| `✅ OTP Verified` | Success! Should redirect ✅ |

---

### Step 3: Test OTP Manually

**Tool:** `tests/test_otp_manual.php`

```
http://localhost/prcf_keuangan_dashboard/tests/test_otp_manual.php
```

**How to use:**
1. OTP will be displayed in BLUE BOX
2. Copy the exact OTP
3. Paste into input field
4. Click "Verify OTP"
5. Check DEBUG INFO output

**Expected result:**
```
String comparison: MATCH ✅
Expired (>60s): NO ✅
```

**If MATCH ❌:** There's a bug in comparison logic!  
**If Expired YES ❌:** OTP timeout too short!

---

### Step 4: Use DEBUG MODE (ENABLED NOW!)

**I've enabled DEVELOPER_MODE in config.php**

Now when you go to verify_otp.php, you'll see:

```
🔧 DEBUG MODE:
OTP: 123456
Time remaining: 45 seconds
```

**Try this:**
1. Login normally
2. Go to verify_otp.php
3. **Copy the OTP from yellow DEBUG box**
4. Paste into input field
5. Submit

**If this works:** Problem was OTP not sent via email!  
**If still fails:** Check error.log for comparison issue!

---

### Step 5: Check Session in Browser

**Open Browser DevTools (F12)**

1. Go to **Application** tab (Chrome) or **Storage** tab (Firefox)
2. Click **Cookies** → `http://localhost`
3. Find `PHPSESSID` cookie

**Check:**
- ✅ Cookie exists
- ✅ Cookie has value (long string)
- ✅ Path = `/`
- ✅ HttpOnly = true

**If cookie missing:** Browser not accepting cookies! Check settings.

---

## 🔬 COMMON ISSUES & FIXES

### Issue 1: Form not submitting

**Symptoms:**
- Click button, nothing happens
- No logs in error.log
- Page doesn't refresh

**Fix:**
```
1. Check browser console for JavaScript errors
2. Check if button has name="verify_otp" value="1"
3. Try without JavaScript (disable JS in browser)
```

### Issue 2: OTP always wrong

**Symptoms:**
- Log shows: `❌ OTP Wrong - Entered: '123456' vs Expected: '123456'`
- Values LOOK the same but don't match

**Possible causes:**
- Hidden characters (space, tab, newline)
- Type mismatch (string vs integer)
- Session variable corrupted

**Fix:**
```
1. Check test_otp_manual.php - does it work?
2. If manual test WORKS but real OTP FAILS:
   → Problem with email OTP or session storage
```

### Issue 3: OTP expired immediately

**Symptoms:**
- Log shows: `Time check - Diff: 120 seconds` (> 60)
- But you just got the OTP!

**Possible causes:**
- Server time wrong
- Session timestamp not updated
- Clock skew

**Fix:**
```
1. Check server time: php -r "echo date('Y-m-d H:i:s');"
2. Increase timeout in verify_otp.php (change 60 to 300 for testing)
```

### Issue 4: Session lost after POST

**Symptoms:**
- verify_otp.php loads fine initially
- After submit: `No pending_login, redirecting to login.php`
- Session data disappeared

**Possible causes:**
- Session file deleted
- PHP session.gc_probability
- Multiple PHP processes

**Fix:**
```
1. Check C:\xampp\tmp\sess_* files exist
2. Restart Apache
3. Clear all sess_* files and retry
```

---

## 🎯 TESTING CHECKLIST

Run through these in order:

- [ ] **Test 1:** Manual OTP test (`test_otp_manual.php`)
  - ✅ Works → OTP logic OK
  - ❌ Fails → Bug in comparison logic

- [ ] **Test 2:** Real login with DEBUG MODE
  - ✅ See yellow box with OTP
  - ✅ Copy OTP from yellow box
  - ✅ Paste and submit
  - Expected: Should redirect to dashboard

- [ ] **Test 3:** Check browser console (F12)
  - ✅ No JavaScript errors
  - ✅ See "Form submitting..." log

- [ ] **Test 4:** Check error.log
  - ✅ See "POST received"
  - ✅ See "OTP Comparison"
  - ✅ See "OTP Verified" (if correct)

- [ ] **Test 5:** Check session cookie
  - ✅ PHPSESSID exists in browser
  - ✅ Same session ID before/after POST

---

## 📊 NEXT STEPS

### If Manual Test Works (test_otp_manual.php):

**Conclusion:** OTP logic is fine, problem is in:
- Email delivery (OTP not reaching user)
- User typing wrong OTP
- OTP expiring too fast (60 seconds might be too short!)

**Fix:**
1. Increase timeout to 300 seconds (5 minutes)
2. Show OTP in DEBUG MODE (already done!)
3. Or use WhatsApp OTP instead

### If Manual Test Fails:

**Conclusion:** Bug in OTP comparison logic!

**Fix:**
1. Check preg_replace('/\D/', '') removing numbers?
2. Check session variable type (int vs string)
3. Check for trailing spaces/newlines

### If DEBUG MODE Shows OTP:

**Try:**
1. Copy OTP from yellow box
2. Paste exactly
3. Submit

**If works:** Email OTP not being sent correctly  
**If fails:** Check error.log for actual comparison values

---

## 🆘 REPORT FORMAT

If still stuck, provide:

```
1. Screenshot of verify_otp.php (with yellow DEBUG box)
2. Screenshot of browser console (F12)
3. Last 50 lines of error.log (around your test time)
4. Result of test_otp_manual.php (screenshot)
5. Your session ID (shown in debug boxes)
```

**Copy error.log lines:**
```bash
# Windows PowerShell
Get-Content "C:\xampp\apache\logs\error.log" -Tail 50 | Select-String "verify_otp"
```

---

**Files Modified:**
- ✅ `auth/verify_otp.php` - Added extensive logging & DEBUG mode
- ✅ `includes/config.php` - Enabled DEVELOPER_MODE
- 🆕 `tests/test_otp_manual.php` - Manual OTP testing tool
- 🆕 `docs/OTP_DEBUG_GUIDE.md` - This guide

**Current Status:** READY FOR DEBUGGING! 🔍
