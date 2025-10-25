@echo off
echo ================================================
echo    Setup Brevo SMTP - PRCF Keuangan Dashboard
echo ================================================
echo.

echo Langkah-langkah setup Brevo:
echo.
echo [1/4] Daftar Brevo (2 menit)
echo     https://app.brevo.com/account/register
echo     - Isi email, password, nama
echo     - Verify email
echo.
echo [2/4] Get SMTP Credentials (2 menit)
echo     Login to Brevo Dashboard
echo     - Settings -^> SMTP ^& API -^> SMTP tab
echo     - Click "Generate a new SMTP key"
echo     - Copy SMTP Key
echo.
echo [3/4] Update config.php (1 menit)
echo     Buka: config.php
echo     Ganti:
echo       SMTP_HOST: smtp-relay.brevo.com
echo       SMTP_PORT: 587
echo       SMTP_USER: (email Brevo Anda)
echo       SMTP_PASS: (SMTP key dari dashboard)
echo.
echo [4/4] Test Email
echo     http://localhost/prcf_keuangan/test_email.php
echo.
echo ================================================
echo.
echo Mau buka website Brevo sekarang? (Y/N)
set /p open_brevo=

if /i "%open_brevo%"=="Y" (
    start https://app.brevo.com/account/register
    echo.
    echo Browser opened! Setelah selesai daftar dan get SMTP key,
    echo edit file config.php dan update credentials.
) else (
    echo.
    echo OK! Buka link ini untuk daftar:
    echo https://app.brevo.com/account/register
)

echo.
echo Baca panduan lengkap: SETUP_BREVO.md
echo.
pause

