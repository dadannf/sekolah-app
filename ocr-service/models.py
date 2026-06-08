"""
Pydantic Models for API
"""
from pydantic import BaseModel, Field
from typing import Optional, List, Dict, Any
from datetime import datetime


class OCRDetection(BaseModel):
    text: str
    confidence: float
    box: List[List[float]]


class ExtractedFields(BaseModel):
    amount: Optional[float] = None
    paid_at: Optional[datetime] = None
    bank_name: Optional[str] = None
    sender_name: Optional[str] = None
    recipient_name: Optional[str] = None
    recipient_account_no: Optional[str] = None
    reference_no: Optional[str] = None


class OCRProcessResponse(BaseModel):
    receipt_id: int
    status: str
    full_text: str
    confidence: float
    extracted_fields: ExtractedFields
    detections: List[OCRDetection]
    validation: Dict[str, Any]
    message: str


class UploadResponse(BaseModel):
    receipt_id: int
    file_path: str
    message: str


class ErrorResponse(BaseModel):
    error: str
    detail: Optional[str] = None
