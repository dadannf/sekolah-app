# Script untuk generate payment records untuk siswa existing
# Author: School Management System
# Date: 2025-01-20

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Generate Payment for Existing Students" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Cari PHP executable di Laragon
$phpPaths = @(
    "F:\laragon\bin\php\php-8.3.26-nts-Win32-vs16-x64\php.exe",
    "F:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe",
    "F:\laragon\bin\php\php-8.3.6-Win32-vs16-x64\php.exe",
    "F:\laragon\bin\php\php.exe",
    "C:\laragon\bin\php\php.exe"
)

$phpPath = $null
foreach ($path in $phpPaths) {
    if (Test-Path $path) {
        $phpPath = $path
        break
    }
}

if (-not $phpPath) {
    Write-Host "ERROR: PHP executable not found!" -ForegroundColor Red
    Write-Host "Please check Laragon installation" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host "Found PHP: $phpPath" -ForegroundColor Green
Write-Host ""

# Pindah ke direktori project
Set-Location -Path "f:\laragon\www\sekolah"

# Step 1: Run migration
Write-Host "[1/2] Running migration for payments table..." -ForegroundColor Yellow
& $phpPath artisan migrate --force 2>&1 | Write-Host
Write-Host ""

# Step 2: Generate payments
Write-Host "[2/2] Generating payment records for existing students..." -ForegroundColor Yellow
& $phpPath artisan payments:generate 2>&1 | Write-Host

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "SUCCESS! Payment records generated." -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Please refresh your browser to see the data." -ForegroundColor Cyan
} else {
    Write-Host ""
    Write-Host "ERROR: Failed to generate payments!" -ForegroundColor Red
}

Write-Host ""
Read-Host "Press Enter to exit"
