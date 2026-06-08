@echo off
echo Setting up storage directories and permissions...

REM Create storage directories if they don't exist
if not exist "storage\app\public\information_images" (
    mkdir "storage\app\public\information_images"
    echo Created information_images directory
)

REM Create symbolic link
echo Creating storage symbolic link...
php artisan storage:link

REM Check if public/storage exists
if exist "public\storage" (
    echo Storage link created successfully!
) else (
    echo Warning: Storage link creation failed. Please run manually: php artisan storage:link
)

echo.
echo Setup completed!
echo You can now upload images to information.
pause