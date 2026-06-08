@echo off
echo ========================================
echo   OCR Payment Service - Installation
echo ========================================
echo.

REM Check if Python is installed
python --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Python is not installed!
    echo Please install Python 3.8+ from https://www.python.org/
    pause
    exit /b 1
)

echo [OK] Python found
echo.

REM Use project root .venv to keep everything in one environment
cd /d "%~dp0"
set "ROOT_DIR=%~dp0.."
set "VENV_DIR=%ROOT_DIR%\.venv"
set "PYTHON_EXE=%VENV_DIR%\Scripts\python.exe"

if not exist "%PYTHON_EXE%" (
    echo Creating root virtual environment at %VENV_DIR% ...
    python -m venv "%VENV_DIR%"
)

echo Upgrading pip...
"%PYTHON_EXE%" -m pip install --upgrade pip

REM Install requirements
echo.
echo Installing dependencies...
echo This may take a while (downloading PaddleOCR models)...
echo.
"%PYTHON_EXE%" -m pip install -r "%~dp0requirements.txt"

if errorlevel 1 (
    echo.
    echo [ERROR] Failed to install dependencies!
    echo Try running: pip install -r requirements.txt manually
    pause
    exit /b 1
)

REM Create upload directory
if not exist "%ROOT_DIR%\storage\app\payments" (
    echo.
    echo Creating upload directory...
    mkdir "%ROOT_DIR%\storage\app\payments"
    echo [OK] Upload directory created
)

echo.
echo ========================================
echo   Installation Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Run: run.bat
echo 2. Open browser: http://localhost:8002/docs
echo.

pause
