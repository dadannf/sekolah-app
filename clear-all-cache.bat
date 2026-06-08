@echo off
echo ===================================
echo CLEAR LARAVEL CACHE AND SESSION
echo ===================================
echo.

cd /d F:\laragon\www\sekolah

echo [1/5] Clearing application cache...
F:\laragon\bin\php\php-8.3.26-nts-Win32-vs16-x64\php.exe artisan cache:clear

echo.
echo [2/5] Clearing config cache...
F:\laragon\bin\php\php-8.3.26-nts-Win32-vs16-x64\php.exe artisan config:clear

echo.
echo [3/5] Clearing route cache...
F:\laragon\bin\php\php-8.3.26-nts-Win32-vs16-x64\php.exe artisan route:clear

echo.
echo [4/5] Clearing view cache...
F:\laragon\bin\php\php-8.3.26-nts-Win32-vs16-x64\php.exe artisan view:clear

echo.
echo [5/5] Clearing session...
F:\laragon\bin\php\php-8.3.26-nts-Win32-vs16-x64\php.exe artisan session:flush

echo.
echo ===================================
echo DONE! All caches cleared.
echo ===================================
echo.
echo NEXT STEPS:
echo 1. Close your browser completely
echo 2. Open browser again
echo 3. Go to: http://sekolah.test:8080/login
echo 4. Login with student credentials:
echo    Email: 22211161
echo    Password: [your student password]
echo.
pause
