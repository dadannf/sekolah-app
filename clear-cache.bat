@echo off
echo Clearing Laravel Cache...

echo.
echo Clearing application cache...
php artisan cache:clear

echo.
echo Clearing configuration cache...
php artisan config:clear

echo.
echo Clearing route cache...
php artisan route:clear

echo.
echo Clearing view cache...
php artisan view:clear

echo.
echo Optimizing configuration...
php artisan config:cache

echo.
echo Cache cleared successfully!
echo.
pause