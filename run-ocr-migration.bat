@echo off
REM Run database migration for OCR status feature

echo.
echo ================================================
echo OCR Fallback Payment System - Database Migration
echo ================================================
echo.

echo Running Laravel migrations...
php artisan migrate

echo.
echo ================================================
echo Migration completed!
echo.
echo Next steps:
echo 1. Update views to display ocr_status if needed
echo 2. Test payment submission with OCR service
echo 3. Test payment verification by admin
echo ================================================
echo.
pause
