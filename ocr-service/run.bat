@echo off
setlocal EnableDelayedExpansion
cls

REM ───────────────────────────────────────────────────────────────────
REM  OCR PAYMENT SERVICE — Startup Script
REM  Copyright (c) 2026 dadann.f  |  SMK BIT Bina Aulia
REM ───────────────────────────────────────────────────────────────────

REM --- Use PowerShell for colored output ---
powershell -NoProfile -Command ^
  "Write-Host ''; ^
   Write-Host '  ╔══════════════════════════════════════════════════════════════════════╗' -ForegroundColor Cyan; ^
   Write-Host '  ║                                                                      ║' -ForegroundColor Cyan; ^
   Write-Host '  ║    ██████╗  ██████╗ ██████╗     ██████╗  ██████╗██╗   ██╗           ║' -ForegroundColor Cyan; ^
   Write-Host '  ║   ██╔═══██╗██╔════╝██╔══██╗    ██╔═══██╗██╔════╝██║   ██║           ║' -ForegroundColor Cyan; ^
   Write-Host '  ║   ██║   ██║██║     ██████╔╝    ██║   ██║██║     ██║   ██║           ║' -ForegroundColor Cyan; ^
   Write-Host '  ║   ██║   ██║██║     ██╔══██╗    ██║   ██║██║     ██║   ██║           ║' -ForegroundColor Cyan; ^
   Write-Host '  ║   ╚██████╔╝╚██████╗██║  ██║    ╚██████╔╝╚██████╗╚██████╔╝           ║' -ForegroundColor Cyan; ^
   Write-Host '  ║    ╚═════╝  ╚═════╝╚═╝  ╚═╝     ╚═════╝  ╚═════╝ ╚═════╝           ║' -ForegroundColor Cyan; ^
   Write-Host '  ║                                                                      ║' -ForegroundColor Cyan; ^
   Write-Host '  ║              P A Y M E N T   R E C E I P T   S E R V I C E          ║' -ForegroundColor Yellow; ^
   Write-Host '  ║                                                                      ║' -ForegroundColor Cyan; ^
   Write-Host '  ╚══════════════════════════════════════════════════════════════════════╝' -ForegroundColor Cyan; ^
   Write-Host ''; ^
   Write-Host '  ┌──────────────────────────────────────────────────────────────────────┐' -ForegroundColor DarkCyan; ^
   Write-Host '  │  📸  Optical Character Recognition for Payment Receipts              │' -ForegroundColor White; ^
   Write-Host '  │  ⚡  Powered by PaddleOCR  +  FastAPI  +  Uvicorn                   │' -ForegroundColor White; ^
   Write-Host '  │  🔗  Endpoint  :  http://localhost:8002/api/ocr/process              │' -ForegroundColor White; ^
   Write-Host '  │  📖  API Docs  :  http://localhost:8002/docs                         │' -ForegroundColor White; ^
   Write-Host '  │  🏦  Bank      :  BRI  (SMK BIT Bina Aulia)                         │' -ForegroundColor White; ^
   Write-Host '  │  🏷️   Version   :  2.0.0                                             │' -ForegroundColor White; ^
   Write-Host '  └──────────────────────────────────────────────────────────────────────┘' -ForegroundColor DarkCyan; ^
   Write-Host ''; ^
   Write-Host '  ┌──────────────────────────────────────────────────────────────────────┐' -ForegroundColor DarkGray; ^
   Write-Host '  │  👨‍💻  Developer  :  dadann.f                                          │' -ForegroundColor Gray; ^
   Write-Host '  │  🏫  Project    :  Sistem Informasi SMK BIT Bina Aulia               │' -ForegroundColor Gray; ^
   Write-Host '  │  ©   Copyright  :  2026 dadann.f  —  All rights reserved             │' -ForegroundColor Gray; ^
   Write-Host '  └──────────────────────────────────────────────────────────────────────┘' -ForegroundColor DarkGray; ^
   Write-Host ''"

echo.

REM ───────────────────────────────────────────────────────────────────
REM  Resolve Python executable from project root .venv
REM ───────────────────────────────────────────────────────────────────
cd /d "%~dp0"
set "ROOT_DIR=%~dp0.."
set "PYTHON_EXE=%ROOT_DIR%\.venv\Scripts\python.exe"

if not exist "%PYTHON_EXE%" (
    powershell -NoProfile -Command "Write-Host '  [WARN] Root .venv not found — falling back to system Python' -ForegroundColor Yellow"
    set "PYTHON_EXE=python"
) else (
    powershell -NoProfile -Command "Write-Host '  [OK] Virtual environment found' -ForegroundColor Green"
)

powershell -NoProfile -Command ^
  "Write-Host '  ──────────────────────────────────────────────────────────────────────' -ForegroundColor DarkCyan; ^
   Write-Host '  🚀  Launching FastAPI server on port 8002 ...' -ForegroundColor Green; ^
   Write-Host '  ──────────────────────────────────────────────────────────────────────' -ForegroundColor DarkCyan; ^
   Write-Host ''"

REM ───────────────────────────────────────────────────────────────────
REM  Start FastAPI via Uvicorn (alur kerja tidak berubah)
REM ───────────────────────────────────────────────────────────────────
"%PYTHON_EXE%" -m uvicorn main:app --host 0.0.0.0 --port 8002 --log-level warning

REM ───────────────────────────────────────────────────────────────────
REM  Shutdown message
REM ───────────────────────────────────────────────────────────────────
echo.
powershell -NoProfile -Command ^
  "Write-Host '  ══════════════════════════════════════════════════════════════════════' -ForegroundColor DarkCyan; ^
   Write-Host '  👋  OCR Payment Service stopped.  Goodbye!' -ForegroundColor Yellow; ^
   Write-Host '  ══════════════════════════════════════════════════════════════════════' -ForegroundColor DarkCyan; ^
   Write-Host ''"

pause
endlocal
