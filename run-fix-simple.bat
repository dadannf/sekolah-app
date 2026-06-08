@echo off
echo =========================================
echo FIX PAYMENTS TABLE - SIMPLE VERSION
echo =========================================
echo.
echo This script will add missing columns to payments table
echo.

REM Try different PHP paths
set PHP_PATH=

if exist "f:\laragon\bin\php\php-8.3.2-Win32-vs16-x64\php.exe" (
    set PHP_PATH=f:\laragon\bin\php\php-8.3.2-Win32-vs16-x64\php.exe
) else if exist "f:\laragon\bin\php\php-8.2.13-Win32-vs16-x64\php.exe" (
    set PHP_PATH=f:\laragon\bin\php\php-8.2.13-Win32-vs16-x64\php.exe
) else if exist "f:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe" (
    set PHP_PATH=f:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe
) else if exist "f:\laragon\bin\php\php-8.0.30-Win32-vs16-x64\php.exe" (
    set PHP_PATH=f:\laragon\bin\php\php-8.0.30-Win32-vs16-x64\php.exe
) else if exist "C:\xampp\php\php.exe" (
    set PHP_PATH=C:\xampp\php\php.exe
) else (
    echo ERROR: PHP not found!
    echo.
    echo Please install PHP or update the path in this batch file
    echo Or run the SQL manually in phpMyAdmin
    pause
    exit /b 1
)

echo Using PHP: %PHP_PATH%
echo.

cd /d "%~dp0"
"%PHP_PATH%" fix-payments-simple.php

echo.
pause
