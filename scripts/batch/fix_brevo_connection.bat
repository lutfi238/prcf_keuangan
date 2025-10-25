@echo off
echo ================================================
echo    Fix Brevo Connection - Enable OpenSSL
echo ================================================
echo.

REM Check if running as admin
net session >nul 2>&1
if %errorLevel% == 0 (
    echo [OK] Running as Administrator
) else (
    echo [ERROR] Please run as Administrator!
    echo.
    echo Right-click this file and select "Run as Administrator"
    pause
    exit
)

echo.
echo [1/3] Backing up php.ini...
copy /Y "C:\xampp\php\php.ini" "C:\xampp\php\php.ini.backup.brevo"
echo [OK] Backup created: php.ini.backup.brevo

echo.
echo [2/3] Enabling OpenSSL extension...
powershell -Command "(gc C:\xampp\php\php.ini) -replace ';extension=openssl', 'extension=openssl' | Out-File -encoding ASCII C:\xampp\php\php.ini"
echo [OK] OpenSSL enabled

echo.
echo [3/3] Verifying OpenSSL DLL...
if exist "C:\xampp\php\ext\php_openssl.dll" (
    echo [OK] php_openssl.dll found
) else (
    echo [ERROR] php_openssl.dll not found!
    echo Please reinstall XAMPP or download missing file.
)

if exist "C:\xampp\apache\bin\libeay32.dll" (
    echo [OK] libeay32.dll found
) else (
    echo [WARNING] libeay32.dll not found in apache\bin
)

if exist "C:\xampp\apache\bin\ssleay32.dll" (
    echo [OK] ssleay32.dll found
) else (
    echo [WARNING] ssleay32.dll not found in apache\bin
)

echo.
echo ================================================
echo NEXT STEPS:
echo 1. RESTART Apache di XAMPP Control Panel
echo 2. Test lagi: http://localhost/prcf_keuangan/test_email.php
echo ================================================
echo.

pause

