@echo off
title QoS Monitoring System

echo ==========================================
echo   QoS Monitoring System - Starting...
echo ==========================================
echo.

:: Jalankan Python API di terminal terpisah
echo [1/2] Menjalankan Python API (port 5000)...
start "Python API - QoS" cmd /k "cd /d d:\TA\project-qos\api && .venv\Scripts\python.exe predict_api.py"

:: Tunggu sebentar agar Python API sempat start
timeout /t 2 /nobreak >nul

:: Jalankan Laravel di terminal terpisah
echo [2/2] Menjalankan Laravel (port 8000)...
start "Laravel Server - QoS" cmd /k "cd /d d:\TA\project-qos && C:\laragon\bin\php\php-8.3.31-nts-Win32-vs16-x64\php.exe artisan serve"

:: Tunggu sebentar lalu buka browser
timeout /t 3 /nobreak >nul

echo.
echo ==========================================
echo   Membuka browser...
echo ==========================================
start http://127.0.0.1:8000

exit
