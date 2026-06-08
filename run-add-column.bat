@echo off
cd /d f:\laragon\www\sekolah
if exist "f:\laragon\bin\php\php-8.3.2-Win32-vs16-x64\php.exe" (
    f:\laragon\bin\php\php-8.3.2-Win32-vs16-x64\php.exe add-place-paid-column.php
) else if exist "f:\laragon\bin\php\php-8.2.13-Win32-vs16-x64\php.exe" (
    f:\laragon\bin\php\php-8.2.13-Win32-vs16-x64\php.exe add-place-paid-column.php
) else if exist "f:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe" (
    f:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe add-place-paid-column.php
) else (
    echo PHP not found!
)
pause
