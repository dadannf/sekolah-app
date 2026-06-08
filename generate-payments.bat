@echo off
REM Script untuk generate payment records untuk siswa existing

echo ========================================
echo Generate Payment for Existing Students
echo ========================================
echo.

REM Cari PHP executable di Laragon
set PHP_PATH=
if exist "F:\laragon\bin\php\php-8.3.6-Win32-vs16-x64\php.exe" (
    set PHP_PATH=F:\laragon\bin\php\php-8.3.6-Win32-vs16-x64\php.exe
) else if exist "F:\laragon\bin\php\php.exe" (
    set PHP_PATH=F:\laragon\bin\php\php.exe
) else (
    echo ERROR: PHP executable not found!
    echo Please check Laragon installation
    pause
    exit /b 1
)

echo Found PHP: %PHP_PATH%
echo.

REM Pindah ke direktori project
cd /d "%~dp0"

REM Step 1: Run migration
echo [1/2] Running migration for payments table...
"%PHP_PATH%" artisan migrate --force
if errorlevel 1 (
    echo.
    echo WARNING: Migration failed or already executed
    echo Continuing to next step...
)
echo.

REM Step 2: Generate payments
echo [2/2] Generating payment records for existing students...
"%PHP_PATH%" artisan payments:generate
if errorlevel 1 (
    echo.
    echo ERROR: Failed to generate payments!
    pause
    exit /b 1
)

echo.
echo ========================================
echo SUCCESS! Payment records generated.
echo ========================================
echo.
echo Please refresh your browser to see the data.
echo.

pause
