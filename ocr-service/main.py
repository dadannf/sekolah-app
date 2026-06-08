# -*- coding: utf-8 -*-
"""
FastAPI Application - OCR Payment Service
Production-ready with proper logging, type hints, and error handling
"""

# Disable OneDNN backend for Windows PaddleOCR compatibility (MUST be first)
import os
os.environ['FLAGS_use_mkldnn'] = '0'
os.environ['FLAGS_use_onednn'] = '0'

# Force UTF-8 output so Unicode box-drawing chars render in Windows terminals
import sys
if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

from fastapi import FastAPI, File, UploadFile, HTTPException, Form
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse

import shutil
import logging

from datetime import datetime
from pathlib import Path
from typing import Optional

from config import settings
from ocr_processor import OCRProcessor
from field_extractor import FieldExtractor
from validator import PaymentValidator
from evaluator import evaluate_ocr

# DATABASE DISABLED
# from database import OcrDatabase

from models import (
    OCRProcessResponse,
    UploadResponse,
    ErrorResponse,
    ExtractedFields,
    OCRDetection
)

from logging_config import setup_logging


# =========================================================
# SETUP LOGGING
# =========================================================

setup_logging(
    log_level="INFO" if not settings.DEBUG else "DEBUG"
)

logger = logging.getLogger(__name__)


# =========================================================
# INITIALIZE FASTAPI APP
# =========================================================

app = FastAPI(
    title="OCR Payment Service",
    description="PaddleOCR service for payment receipt processing with advanced preprocessing",
    version="2.0.0"
)


# =========================================================
# CORS MIDDLEWARE
# =========================================================

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # In production, specify your Laravel domain
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


# =========================================================
# INITIALIZE COMPONENTS
# =========================================================

# ── ANSI color constants ───────────────────────────────────────────────────────
CYAN   = "\033[96m"
GREEN  = "\033[92m"
YELLOW = "\033[93m"
RED    = "\033[91m"
GRAY   = "\033[90m"
WHITE  = "\033[97m"
BOLD   = "\033[1m"
RESET  = "\033[0m"

# Enable ANSI VT100 on Windows 10+
import ctypes
try:
    _k32 = ctypes.windll.kernel32
    _k32.SetConsoleMode(_k32.GetStdHandle(-11), 7)
except Exception:
    pass

# ── Box drawing helpers (all alignment computed, no emoji inside boxes) ─────────
_BW = 65  # total box width (fits 80-col terminal with 2-space indent)

def _c(color: str, text: str) -> str:
    return f"{color}{text}{RESET}"

def _btop(d=True):
    ch, l, r = ('═', '╔', '╗') if d else ('─', '┌', '┐')
    return '  ' + _c(CYAN, l + ch * (_BW - 2) + r)

def _bbot(d=True):
    ch, l, r = ('═', '╚', '╝') if d else ('─', '└', '┘')
    return '  ' + _c(CYAN, l + ch * (_BW - 2) + r)

def _bmid():
    return '  ' + _c(CYAN, '╠' + '═' * (_BW - 2) + '╣')

def _bsep(d=True):
    l, r = ('║', '║') if d else ('│', '│')
    return '  ' + _c(CYAN, l + ' ' * (_BW - 2) + r)

def _brow(text='', color=None, d=True):
    """Left-aligned row — text is plain string (no ANSI), padding computed correctly"""
    l, r = ('║', '║') if d else ('│', '│')
    inner = _BW - 4  # content width
    padded = text.ljust(inner)
    styled = _c(color, padded) if color else padded
    return '  ' + _c(CYAN, l) + ' ' + styled + ' ' + _c(CYAN, r)

def _bctr(text='', color=None, d=True):
    """Centered row"""
    l, r = ('║', '║') if d else ('│', '│')
    inner = _BW - 4
    padded = text.center(inner)
    styled = _c(color, padded) if color else padded
    return '  ' + _c(CYAN, l) + ' ' + styled + ' ' + _c(CYAN, r)


# ── Component initialization ───────────────────────────────────────────────────
print()
print(_btop(d=False))
print(_bctr('INISIALISASI KOMPONEN SISTEM', BOLD + YELLOW, d=False))
print(_bbot(d=False))
print()

try:

    print(_c(YELLOW, '  [1/3] Memuat PaddleOCR processor ...'))
    ocr_processor = OCRProcessor()
    print(_c(GREEN,  '  [OK]  PaddleOCR berhasil diinisialisasi'))
    print()

    print(_c(YELLOW, '  [2/3] Memuat field extractor ...'))
    field_extractor = FieldExtractor()
    print(_c(GREEN,  '  [OK]  Field extractor berhasil dimuat'))
    print()

    print(_c(YELLOW, '  [3/3] Memuat payment validator ...'))
    payment_validator = PaymentValidator()
    print(_c(GREEN,  '  [OK]  Payment validator berhasil dimuat'))
    print()

    # DATABASE DISABLED
    # print("    [WAIT] Connecting to database...")
    # db = OcrDatabase()
    # print("    [OK] Database connected successfully")

    print('  ' + _c(CYAN, '=' * (_BW - 2)))
    print(_c(GREEN + BOLD, '  [READY] Semua komponen berhasil diinisialisasi!'))
    print('  ' + _c(CYAN, '=' * (_BW - 2)))
    print()

    logger.info("All components initialized successfully")

except Exception as e:

    print(_c(RED, f'  [ERROR] Gagal menginisialisasi komponen: {e}'))

    logger.critical(
        f"Failed to initialize components: {e}",
        exc_info=True
    )

    raise


# =========================================================
# ENSURE UPLOAD DIRECTORY EXISTS
# =========================================================

UPLOAD_DIR = Path(settings.UPLOAD_DIR)
UPLOAD_DIR.mkdir(parents=True, exist_ok=True)


# =========================================================
# STARTUP EVENT
# =========================================================

@app.on_event("startup")
async def startup_event():

    """Event handler when server starts"""

    now = datetime.now().strftime("%Y-%m-%d  %H:%M:%S")
    port = settings.API_PORT

    print()
    print(_btop())
    print(_bsep())
    print(_bctr('OCR  PAYMENT  RECEIPT  SERVICE', BOLD + CYAN))
    print(_bctr('Powered by PaddleOCR + FastAPI + Uvicorn', GRAY))
    print(_bsep())
    print(_bmid())
    print(_brow('  Status    :  ONLINE  --  Siap memproses bukti pembayaran', GREEN + BOLD))
    print(_bmid())
    print(_brow(f'  Endpoint  :  http://localhost:{port}/api/ocr/process', WHITE))
    print(_brow(f'  API Docs  :  http://localhost:{port}/docs', WHITE))
    print(_brow(f'  Bank      :  {settings.ALLOWED_BANKS}  ({settings.SCHOOL_ACCOUNT_NAME})', WHITE))
    print(_brow(f'  Version   :  2.0.0', WHITE))
    print(_brow(f'  Started   :  {now}', GRAY))
    print(_bmid())
    print(_brow('  Developer :  dadann.f', GRAY))
    print(_brow('  Project   :  Sistem Informasi SMK BIT Bina Aulia', GRAY))
    print(_brow('  Copyright :  (c) 2026 dadann.f - All rights reserved', GRAY))
    print(_bbot())
    print()


# =========================================================
# SHUTDOWN EVENT
# =========================================================

@app.on_event("shutdown")
async def shutdown_event():

    """Event handler when server shuts down"""

    now = datetime.now().strftime("%Y-%m-%d  %H:%M:%S")
    print()
    print(_btop())
    print(_bctr('OCR PAYMENT SERVICE  --  STOPPING', BOLD + YELLOW))
    print(_brow(f'  Stopped   :  {now}', GRAY))
    print(_brow('  Semua koneksi ditutup. Sampai jumpa!', YELLOW))
    print(_bbot())
    print()


# =========================================================
# VALIDATE FILE EXTENSION
# =========================================================

def allowed_file(filename: str) -> bool:

    """Check if file extension is allowed"""

    return '.' in filename and \
           filename.rsplit('.', 1)[1].lower() in settings.allowed_extensions_list


# =========================================================
# SAVE UPLOADED FILE
# =========================================================

def save_upload_file(upload_file: UploadFile):

    """Save uploaded file and return path"""

    # Generate unique filename
    timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')

    ext = upload_file.filename.rsplit('.', 1)[1].lower()

    filename = f"ocr_{timestamp}.{ext}"

    # Create year/month subdirectory
    year_month = datetime.now().strftime('%Y/%m')

    save_dir = UPLOAD_DIR / year_month

    save_dir.mkdir(parents=True, exist_ok=True)

    # Save file
    file_path = save_dir / filename

    with open(file_path, "wb") as buffer:
        shutil.copyfileobj(upload_file.file, buffer)

    # Return relative path for Laravel database storage
    relative_path = f"payments/{year_month}/{filename}"

    return str(relative_path), str(file_path)


# =========================================================
# ROOT ENDPOINT
# =========================================================

@app.get("/")
async def root():

    """Health check endpoint"""

    return {
        "service": "OCR Payment Service",
        "status": "running",
        "version": "2.0.0"
    }


# =========================================================
# OCR PROCESS ENDPOINT
# =========================================================

@app.post("/api/ocr/process")
async def process_receipt(

    file: UploadFile = File(...),

    student_id: Optional[int] = Form(None),

    uploaded_by: Optional[int] = Form(None),

    expected_amount: Optional[float] = Form(None),

    expected_date: Optional[str] = Form(None),

    expected_bank: Optional[str] = Form(None),

    ground_truth: Optional[str] = Form(None),

):

    """
    Upload and process payment receipt with OCR
    Returns extracted fields immediately with validation
    """

    # =====================================================
    # VALIDATE FILE
    # =====================================================

    if not allowed_file(file.filename):

        raise HTTPException(
            status_code=400,
            detail=f"File type not allowed. Allowed: {', '.join(settings.allowed_extensions_list)}"
        )

    try:

        # =================================================
        # SAVE FILE
        # =================================================

        relative_path, full_path = save_upload_file(file)

        # =================================================
        # PROCESS OCR
        # =================================================

        print()
        print('  ' + _c(CYAN, '┌───────────────────────────────────────────────────────────────┐'))
        print('  ' + _c(CYAN, '│') + _c(BOLD + YELLOW, '  NEW OCR REQUEST: ').ljust(63) + _c(CYAN, '│'))
        print('  ' + _c(CYAN, '├───────────────────────────────────────────────────────────────┤'))
        print('  ' + _c(CYAN, '│') + f"  File    : {file.filename}".ljust(63) + _c(CYAN, '│'))
        
        print('  ' + _c(CYAN, '│') + _c(GRAY, '  Status  : [1/4] Menjalankan Preprocessing & OCR Engine...').ljust(72) + _c(CYAN, '│'))
        
        full_text, detections, confidence = ocr_processor.process_image(
            full_path,
            use_preprocessing=False,
            use_multivariant=True
        )

        print('  ' + _c(CYAN, '│') + _c(GRAY, '  Status  : [2/4] Ekstraksi Field (Nominal, Tgl, Bank)...').ljust(72) + _c(CYAN, '│'))

        # =================================================
        # EXTRACT FIELDS
        # =================================================

        extracted = field_extractor.extract_all_fields(
            full_text,
            detections
        )

        # =================================================
        # VALIDATE EXTRACTION
        # =================================================
        
        print('  ' + _c(CYAN, '│') + _c(GRAY, '  Status  : [3/4] Memvalidasi Ekstraksi dengan Data...').ljust(72) + _c(CYAN, '│'))

        expected_date_obj = None

        if expected_date:

            try:

                from dateutil import parser as date_parser

                expected_date_obj = date_parser.parse(expected_date)

            except Exception:

                expected_date_obj = None

        validation = payment_validator.validate_payment(
            ocr_data=extracted,
            expected_amount=expected_amount,
            expected_date=expected_date_obj,
            expected_bank=expected_bank
        )

        # =================================================
        # DETERMINE STATUS
        # =================================================

        if validation.get('is_valid'):

            status = 'completed'

        else:

            status = 'pending'

        # =================================================
        # EVALUASI CER & WER (opsional — jika ground_truth dikirim)
        # =================================================

        print('  ' + _c(CYAN, '│') + _c(GRAY, '  Status  : [4/4] Evaluasi CER & WER...').ljust(72) + _c(CYAN, '│'))
        
        evaluation = None
        cer_str = "N/A"
        wer_str = "N/A"
        
        if ground_truth and ground_truth.strip() and full_text:
            evaluation = evaluate_ocr(
                ground_truth=ground_truth.strip(),
                prediction=full_text
            )
            cer_str = f"{evaluation['cer']['percent']}%"
            wer_str = f"{evaluation['wer']['percent']}%"
            logger.info(
                f"Evaluation — CER: {evaluation['cer']['percent']}%, "
                f"WER: {evaluation['wer']['percent']}%"
            )

        print('  ' + _c(CYAN, '├───────────────────────────────────────────────────────────────┤'))
        print('  ' + _c(CYAN, '│') + _c(BOLD + GREEN, '  ✓ SELESAI ').ljust(72) + _c(CYAN, '│'))
        print('  ' + _c(CYAN, '│') + f"  Confidence : {confidence:.2f}%".ljust(63) + _c(CYAN, '│'))
        if evaluation:
            print('  ' + _c(CYAN, '│') + f"  CER        : {cer_str}".ljust(63) + _c(CYAN, '│'))
            print('  ' + _c(CYAN, '│') + f"  WER        : {wer_str}".ljust(63) + _c(CYAN, '│'))
            print('  ' + _c(CYAN, '│') + f"  Quality    : {evaluation['quality']}".ljust(63) + _c(CYAN, '│'))
        else:
            print('  ' + _c(CYAN, '│') + _c(GRAY, '  CER/WER    : Tidak ada ground_truth untuk dievaluasi').ljust(72) + _c(CYAN, '│'))
            
        print('  ' + _c(CYAN, '└───────────────────────────────────────────────────────────────┘'))
        print()

        # =================================================
        # RETURN FINAL JSON
        # =================================================

        response = {

            "success": True,

            "status": status,

            "file_path": relative_path,

            "full_text": full_text,

            "confidence": confidence,

            "student_id": student_id,

            "uploaded_by": uploaded_by,

            "extracted_fields": extracted,

            "detections": detections,

            "validation": validation,

            "message": validation.get(
                'message',
                'OCR processing completed'
            )
        }

        # Tambahkan evaluation ke response hanya jika ground_truth disediakan
        if evaluation is not None:
            response["evaluation"] = evaluation

        return response

    except Exception as e:

        logger.exception("OCR PROCESS ERROR")

        raise HTTPException(
            status_code=500,
            detail=str(e)
        )


# =========================================================
# EVALUATE ENDPOINT (CER & WER standalone)
# =========================================================

@app.post("/api/ocr/evaluate")
async def evaluate_ocr_result(
    ocr_text: str = Form(...),
    ground_truth: str = Form(...),
):
    """
    Evaluasi teks hasil OCR terhadap ground truth menggunakan CER & WER.

    Endpoint ini digunakan untuk mengukur akurasi OCR secara kuantitatif
    tanpa perlu mengupload gambar.

    Form Fields:
        ocr_text     : Teks hasil OCR (prediksi)
        ground_truth : Teks referensi yang benar

    Returns:
        CER (Character Error Rate), WER (Word Error Rate), dan kualitas
    """
    if not ocr_text.strip() or not ground_truth.strip():
        raise HTTPException(
            status_code=400,
            detail="ocr_text dan ground_truth tidak boleh kosong"
        )

    try:
        result = evaluate_ocr(
            ground_truth=ground_truth.strip(),
            prediction=ocr_text.strip()
        )
        return {
            "success"     : True,
            "ground_truth": ground_truth.strip(),
            "ocr_text"    : ocr_text.strip(),
            "evaluation"  : result,
        }
    except Exception as e:
        logger.exception("EVALUATE ERROR")
        raise HTTPException(status_code=500, detail=str(e))


# =========================================================
# START APPLICATION
# =========================================================

def print_startup_banner():

    """Print startup banner — shown when launching as python main.py"""

    print()
    print('  ' + _c(CYAN, '=' * (_BW - 2)))
    print(_c(BOLD + CYAN, '  OCR PAYMENT SERVICE  --  Starting ...'))
    print('  ' + _c(CYAN, '=' * (_BW - 2)))
    print()


# =========================================================
# MAIN
# =========================================================

if __name__ == "__main__":

    import uvicorn

    # Startup Banner
    print_startup_banner()

    # Run FastAPI
    uvicorn.run(
        app,
        host=settings.API_HOST,
        port=settings.API_PORT,
        reload=False,
        log_level="warning"
    )