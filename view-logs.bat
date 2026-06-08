@echo off
echo ========================================
echo LATEST LOG ENTRIES
echo ========================================
echo.

powershell -Command "Get-Content 'storage\logs\laravel.log' -Tail 100"

echo.
echo ========================================
echo END OF LOG
echo ========================================
pause
