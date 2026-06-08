@echo off
REM =========================================
REM Script untuk membuat tabel OCR Payment
REM =========================================

echo ========================================
echo   Membuat Tabel OCR Payment System
echo ========================================
echo.

REM Path ke MySQL (Laragon)
set MYSQL_PATH=C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe

REM Database credentials
set DB_USER=root
set DB_PASS=
set DB_NAME=dbsims

REM SQL file path
set SQL_FILE=%~dp0database\create_ocr_tables.sql

echo Checking MySQL connection...
"%MYSQL_PATH%" -u%DB_USER% -e "SELECT 'Connection OK' as status;" 2>nul
if errorlevel 1 (
    echo [ERROR] MySQL connection failed!
    echo Please check:
    echo 1. MySQL is running
    echo 2. MySQL path is correct
    echo 3. Database credentials are correct
    pause
    exit /b 1
)

echo [OK] MySQL connection successful
echo.

echo Checking if SQL file exists...
if not exist "%SQL_FILE%" (
    echo [ERROR] SQL file not found: %SQL_FILE%
    pause
    exit /b 1
)

echo [OK] SQL file found
echo.

echo Executing SQL script...
echo File: %SQL_FILE%
echo.

"%MYSQL_PATH%" -u%DB_USER% %DB_NAME% < "%SQL_FILE%"

if errorlevel 1 (
    echo.
    echo [ERROR] Failed to create tables!
    echo Check the error messages above for details.
    pause
    exit /b 1
)

echo.
echo ========================================
echo   Tables created successfully!
echo ========================================
echo.

echo Verifying tables...
"%MYSQL_PATH%" -u%DB_USER% %DB_NAME% -e "SHOW TABLES LIKE 'ocr%%';"

echo.
echo Checking sample data...
"%MYSQL_PATH%" -u%DB_USER% %DB_NAME% -e "SELECT bank_name, template_name FROM ocr_field_mapping_templates;"

echo.
echo ========================================
echo   Installation Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Check OCR_PAYMENT_SYSTEM_DOCUMENTATION.md for usage
echo 2. Create Laravel models for OCR tables
echo 3. Implement PaddleOCR integration
echo.

pause
