@echo off
echo ========================================
echo  Add Major Column to Students Table
echo ========================================
echo.

php artisan migrate --path=database/migrations/2026_01_02_000001_add_major_to_students_table.php

echo.
echo ========================================
echo  Migration Complete!
echo ========================================
pause
