@echo off
echo ================================================
echo    PRCF Keuangan Dashboard - Ngrok Launcher
echo ================================================
echo.

REM Check if XAMPP is running
echo [1/4] Checking XAMPP Status...
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [OK] Apache is running
) else (
    echo [ERROR] Apache is NOT running!
    echo Please start XAMPP Control Panel and start Apache
    pause
    exit
)

tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [OK] MySQL is running
) else (
    echo [ERROR] MySQL is NOT running!
    echo Please start XAMPP Control Panel and start MySQL
    pause
    exit
)

echo.
echo [2/4] Detecting Apache Port...
netstat -ano | findstr :80 >NUL
if "%ERRORLEVEL%"=="0" (
    set PORT=80
    echo [OK] Apache running on port 80
) else (
    netstat -ano | findstr :8080 >NUL
    if "%ERRORLEVEL%"=="0" (
        set PORT=8080
        echo [OK] Apache running on port 8080
    ) else (
        set PORT=80
        echo [WARNING] Cannot detect Apache port, using default port 80
    )
)

echo.
echo [3/4] Checking Ngrok Installation...
where ngrok >NUL 2>&1
if "%ERRORLEVEL%"=="0" (
    echo [OK] Ngrok found in PATH
) else (
    if exist "C:\ngrok\ngrok.exe" (
        echo [OK] Ngrok found in C:\ngrok\
        cd /d C:\ngrok
    ) else (
        echo [ERROR] Ngrok not found!
        echo.
        echo Please download ngrok from: https://ngrok.com/download
        echo Then extract to C:\ngrok\ or add to PATH
        pause
        exit
    )
)

echo.
echo [4/4] Starting Ngrok Tunnel...
echo ================================================
echo.
echo IMPORTANT: Your application will be accessible at:
echo https://YOUR-NGROK-URL.ngrok-free.app/prcf_keuangan/
echo.
echo NOTE: Don't forget to add /prcf_keuangan/ at the end!
echo.
echo Share the FULL URL (including /prcf_keuangan/) with your friends!
echo.
echo Test Users (Password: password):
echo - PM:  yadi@company.com
echo - FM:  aam.wijaya@company.com
echo - SA:  ade.kurnia@company.com
echo - Dir: imanul.huda@company.com
echo.
echo Press Ctrl+C to stop ngrok when done
echo ================================================
echo.

REM Start ngrok
ngrok http %PORT%

pause

