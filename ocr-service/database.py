"""
Database Connection & Operations
"""
from sqlalchemy import create_engine, text
from sqlalchemy.orm import sessionmaker
from config import settings
from datetime import datetime
from typing import Optional, Dict, Any


engine = create_engine(settings.database_url, echo=settings.DEBUG)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)


def get_db():
    """Get database session"""
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()


class OcrDatabase:
    """Database operations for OCR"""
    
    def __init__(self):
        self.engine = engine
    
    def create_ocr_receipt(
        self,
        student_id: Optional[int],
        uploaded_by: Optional[int],
        file_path: str
    ) -> int:
        """Insert new OCR receipt record"""
        with SessionLocal() as session:
            query = text("""
                INSERT INTO ocr_payment_receipts 
                (student_id, uploaded_by, file_path, status)
                VALUES (:student_id, :uploaded_by, :file_path, 'pending')
            """)
            result = session.execute(query, {
                'student_id': student_id,
                'uploaded_by': uploaded_by,
                'file_path': file_path
            })
            session.commit()
            return result.lastrowid
    
    def update_ocr_result(
        self,
        receipt_id: int,
        amount: Optional[float],
        paid_at: Optional[datetime],
        bank_name: Optional[str],
        sender_name: Optional[str],
        recipient_name: Optional[str],
        reference_no: Optional[str],
        ocr_raw_text: str,
        ocr_confidence: float,
        status: str = 'completed'
    ) -> bool:
        """Update OCR receipt with extracted data"""
        with SessionLocal() as session:
            query = text("""
                UPDATE ocr_payment_receipts 
                SET 
                    amount = :amount,
                    paid_at = :paid_at,
                    bank_name = :bank_name,
                    sender_name = :sender_name,
                    reference_no = :reference_no,
                    ocr_raw_text = :ocr_raw_text,
                    ocr_confidence = :ocr_confidence,
                    status = :status,
                    updated_at = NOW()
                WHERE id = :receipt_id
            """)
            session.execute(query, {
                'receipt_id': receipt_id,
                'amount': amount,
                'paid_at': paid_at,
                'bank_name': bank_name,
                'sender_name': sender_name or recipient_name,
                'reference_no': reference_no,
                'ocr_raw_text': ocr_raw_text,
                'ocr_confidence': ocr_confidence,
                'status': status
            })
            session.commit()
            return True
    
    def get_ocr_receipt(self, receipt_id: int) -> Optional[Dict[str, Any]]:
        """Get OCR receipt by ID"""
        with SessionLocal() as session:
            query = text("""
                SELECT * FROM ocr_payment_receipts 
                WHERE id = :receipt_id
            """)
            result = session.execute(query, {'receipt_id': receipt_id})
            row = result.fetchone()
            if row:
                return dict(row._mapping)
            return None
    
    def find_matching_invoice(
        self,
        student_id: int,
        amount: float
    ) -> Optional[Dict[str, Any]]:
        """Find unpaid invoice matching student and amount"""
        with SessionLocal() as session:
            query = text("""
                SELECT * FROM spp_invoices
                WHERE student_id = :student_id
                  AND status = 'unpaid'
                  AND amount_due = :amount
                ORDER BY invoice_year, invoice_month
                LIMIT 1
            """)
            result = session.execute(query, {
                'student_id': student_id,
                'amount': amount
            })
            row = result.fetchone()
            if row:
                return dict(row._mapping)
            return None
    
    def get_student_by_nis(self, nis: str) -> Optional[Dict[str, Any]]:
        """Get student by NIS"""
        with SessionLocal() as session:
            query = text("""
                SELECT * FROM students WHERE nis = :nis LIMIT 1
            """)
            result = session.execute(query, {'nis': nis})
            row = result.fetchone()
            if row:
                return dict(row._mapping)
            return None
