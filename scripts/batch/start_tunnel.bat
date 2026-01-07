@echo off
title Cloudflare Tunnel - prcf-test.indevs.in
echo ===================================================
echo  PRCF Keuangan - Cloudflare Tunnel
echo  Domain: https://prcf-test.indevs.in
echo ===================================================
echo.
echo [%date% %time%] Starting Cloudflare Tunnel...
echo.

REM Token tetap untuk prcf-tunnel
set TUNNEL_TOKEN=eyJhIjoiM2Q3NzYxMWM1ZGMxMWVlMDA2OGZmMTZlNmU4Yzk1NTEiLCJ0IjoiNWMyMWQ5NDEtYzZhMC00NDhjLWE0ZjMtNDkxZDZhMTc2ZjZmIiwicyI6ImdBK2Raai9FRDBGbkkxblZRd1Z1K2daTFRIMm0rdEU5MWNKbmtwaWxsRGs9In0=

cloudflared.exe tunnel run --token %TUNNEL_TOKEN%

echo.
echo [%date% %time%] Tunnel disconnected.
pause
