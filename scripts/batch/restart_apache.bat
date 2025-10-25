@echo off
echo ============================================
echo    RESTART APACHE - XAMPP CONTROL
echo ============================================
echo.
echo Stopping Apache...
"C:\xampp\apache\bin\httpd.exe" -k stop
timeout /t 3 /nobreak >nul
echo.
echo Starting Apache...
"C:\xampp\apache\bin\httpd.exe" -k start
timeout /t 2 /nobreak >nul
echo.
echo ============================================
echo    DONE! Apache Restarted!
echo ============================================
echo.
echo Maintenance mode should be active now!
echo Test: http://localhost/prcf_keuangan/login.php
echo.
pause

