@echo off
echo ============================================
echo    RESTART APACHE + TEST EMAIL
echo ============================================
echo.

echo [1/2] Restarting Apache...
echo.
echo Please:
echo 1. Open XAMPP Control Panel
echo 2. Click "Stop" on Apache
echo 3. Wait 3 seconds
echo 4. Click "Start" on Apache
echo.
pause

echo.
echo [2/2] Opening test pages...
echo.

start http://localhost/prcf_keuangan/test_email.php
timeout /t 2 /nobreak >nul
start https://mail.google.com/mail/u/0/

echo.
echo ============================================
echo CHECKLIST:
echo ============================================
echo [ ] Apache sudah di-restart?
echo [ ] Test email dikirim?
echo [ ] Cek inbox Gmail (email normal)?
echo [ ] Cek folder SPAM Gmail?
echo [ ] Cek Brevo dashboard logs?
echo.
echo ============================================
echo NEXT: Check php_error.log untuk detail!
echo Location: C:\xampp\apache\logs\error.log
echo ============================================
pause

