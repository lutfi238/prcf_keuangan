@echo off
echo ================================================
echo    Enable Email OTP - PHP Extension Setup
echo ================================================
echo.

REM Check if running as admin
net session >nul 2>&1
if %errorLevel% == 0 (
    echo [OK] Running as Administrator
) else (
    echo [ERROR] Please run as Administrator!
    echo Right-click this file and select "Run as Administrator"
    pause
    exit
)

echo.
echo [1/3] Backing up php.ini...
copy /Y "C:\xampp\php\php.ini" "C:\xampp\php\php.ini.backup"
echo [OK] Backup created: php.ini.backup

echo.
echo [2/3] Enabling required extensions...

REM Enable OpenSSL
powershell -Command "(gc C:\xampp\php\php.ini) -replace ';extension=openssl', 'extension=openssl' | Out-File -encoding ASCII C:\xampp\php\php.ini"
echo [OK] OpenSSL enabled

REM Enable cURL (untuk alternatif)
powershell -Command "(gc C:\xampp\php\php.ini) -replace ';extension=curl', 'extension=curl' | Out-File -encoding ASCII C:\xampp\php\php.ini"
echo [OK] cURL enabled

REM Enable sockets
powershell -Command "(gc C:\xampp\php\php.ini) -replace ';extension=sockets', 'extension=sockets' | Out-File -encoding ASCII C:\xampp\php\php.ini"
echo [OK] Sockets enabled

echo.
echo [3/3] Configuration complete!
echo.
echo ================================================
echo NEXT STEPS:
echo 1. Restart Apache di XAMPP Control Panel
echo 2. Test email dengan: http://localhost/prcf_keuangan/test_email.php
echo ================================================
echo.

pause

