"""
Payment Receipt Validator
Validate OCR results against payment form data
"""
from datetime import datetime, timedelta
from typing import Dict, Any, Optional
import re
from config import settings


class PaymentValidator:
    """Validate OCR extracted data against expected payment data"""
    
    def __init__(self):
        self.school_name = settings.SCHOOL_ACCOUNT_NAME.upper()
        self.school_account_number = re.sub(r"\D", "", (getattr(settings, 'SCHOOL_ACCOUNT_NUMBER', '') or ''))
        self.allowed_banks = settings.allowed_banks_list
        self.max_date_diff = settings.MAX_DATE_DIFFERENCE_DAYS
        self.amount_tolerance = float(getattr(settings, 'ADMIN_AMOUNT_TOLERANCE', 2500) or 2500)
    
    def validate_payment(
        self,
        ocr_data: Dict[str, Any],
        expected_amount: Optional[float] = None,
        expected_date: Optional[datetime] = None,
        expected_bank: Optional[str] = None
    ) -> Dict[str, Any]:
        """
        Validate OCR extracted data against expected payment data
        
        Args:
            ocr_data: Dictionary containing OCR extracted fields
            expected_amount: Expected payment amount from form
            expected_date: Expected payment date from form
            expected_bank: Expected bank name from form
        
        Returns:
            Dictionary with validation results:
            {
                'is_valid': bool,
                'errors': list,
                'warnings': list,
                'details': dict
            }
        """
        errors = []
        warnings = []
        details = {}
        
        # 1. Validate Amount (Nominal)
        if expected_amount is not None:
            ocr_amount = ocr_data.get('amount')
            if not ocr_amount:
                errors.append('Nominal tidak terdeteksi di bukti transfer')
            else:
                amount_diff = abs(float(ocr_amount) - float(expected_amount))
                if amount_diff > self.amount_tolerance:
                    errors.append(
                        f'Nominal tidak sesuai! Form: Rp {expected_amount:,.0f}, '
                        f'Bukti: Rp {ocr_amount:,.0f} (toleransi Rp {self.amount_tolerance:,.0f})'
                    )
                elif amount_diff > 0:
                    warnings.append(
                        f'Nominal berbeda Rp {amount_diff:,.0f}, masih dalam toleransi Rp {self.amount_tolerance:,.0f}'
                    )
                else:
                    details['amount_match'] = True
        
        # 2. Validate Date (Tanggal)
        if expected_date:
            ocr_date = ocr_data.get('paid_at')
            if not ocr_date:
                errors.append('Tanggal tidak terdeteksi di bukti transfer')
            else:
                # Convert to date only (ignore time)
                if isinstance(ocr_date, str):
                    ocr_date = datetime.fromisoformat(ocr_date.replace('Z', '+00:00'))
                
                ocr_date_only = ocr_date.date()
                expected_date_only = expected_date.date() if isinstance(expected_date, datetime) else expected_date
                
                # Check if dates match or within acceptable range
                date_diff = abs((ocr_date_only - expected_date_only).days)
                
                if date_diff == 0:
                    details['date_match'] = True
                elif date_diff <= self.max_date_diff:
                    warnings.append(
                        f'Tanggal berbeda {date_diff} hari. '
                        f'Form: {expected_date_only}, Bukti: {ocr_date_only}'
                    )
                    details['date_match'] = 'warning'
                else:
                    errors.append(
                        f'Tanggal tidak sesuai! Form: {expected_date_only}, '
                        f'Bukti: {ocr_date_only} (selisih {date_diff} hari)'
                    )
        
        # 3. Validate Bank Name (flexible when account matches)
        ocr_bank = ocr_data.get('bank_name')
        bank_allowed = False
        if ocr_bank:
            ocr_bank_upper = ocr_bank.upper()

            # Bank harus termasuk whitelist
            for allowed_bank in self.allowed_banks:
                if allowed_bank and (allowed_bank == ocr_bank_upper or allowed_bank in ocr_bank_upper):
                    bank_allowed = True
                    break

            if expected_bank:
                expected_bank_upper = expected_bank.upper()
                if expected_bank_upper in ocr_bank_upper or ocr_bank_upper in expected_bank_upper:
                    details['bank_match'] = True
                else:
                    warnings.append(
                        f'Bank berbeda dari form. Form: {expected_bank}, Bukti: {ocr_bank}'
                    )
        else:
            warnings.append('Nama bank tidak terdeteksi di bukti transfer')

        # 3b. Validate Recipient Account Number (Nomor Rekening Tujuan)
        account_match = False
        ocr_acc_digits = ""
        if self.school_account_number:
            ocr_acc = (
                ocr_data.get('recipient_account_no')
                or ocr_data.get('account_no')
                or ocr_data.get('rekening_tujuan')
            )
            ocr_acc_digits = re.sub(r"\D", "", str(ocr_acc or ""))

            if ocr_acc_digits:
                if ocr_acc_digits == self.school_account_number:
                    details['account_match'] = True
                    account_match = True
                else:
                    warnings.append(
                        'Nomor rekening tujuan berbeda. '
                        f'Expected: {self.school_account_number}, Bukti: {ocr_acc_digits}'
                    )
            else:
                warnings.append('Nomor rekening tujuan tidak terdeteksi di bukti transfer')

        # 3c. Final bank/account acceptance rule
        if not account_match and not bank_allowed:
            errors.append('Bank penerima atau nomor rekening tujuan tidak terdeteksi/valid')
        elif bank_allowed:
            details['bank_allowed'] = True
        
        # 4. Validate Recipient Name (Nama Penerima)
        ocr_recipient = ocr_data.get('recipient_name')
        if not ocr_recipient:
            # Fallback to sender_name
            ocr_recipient = ocr_data.get('sender_name')
        
        if not ocr_recipient:
            # If bank/account already valid, treat as warning
            if account_match or bank_allowed:
                warnings.append('Nama penerima tidak terdeteksi di bukti transfer')
            else:
                errors.append('Nama penerima tidak terdeteksi di bukti transfer')
        else:
            ocr_recipient_upper = ocr_recipient.upper()

            # STRICT: semua kata pada nama sekolah harus muncul di penerima
            school_words = [w for w in self.school_name.split() if w]
            missing = [w for w in school_words if w not in ocr_recipient_upper]
            if not missing:
                details['recipient_match'] = True
            else:
                if account_match or bank_allowed:
                    warnings.append(
                        f'Nama penerima berbeda. Expected: {self.school_name}, Bukti: {ocr_recipient}'
                    )
                else:
                    errors.append(
                        f'Nama penerima tidak sesuai! Expected: {self.school_name}, Bukti: {ocr_recipient}'
                    )
        
        # Determine overall validity
        is_valid = len(errors) == 0
        
        return {
            'is_valid': is_valid,
            'errors': errors,
            'warnings': warnings,
            'details': details,
            'message': self._get_validation_message(is_valid, errors, warnings)
        }
    
    def _get_validation_message(self, is_valid: bool, errors: list, warnings: list) -> str:
        """Generate validation message"""
        if is_valid:
            if warnings:
                return f'✅ Validasi berhasil dengan {len(warnings)} peringatan'
            return '✅ Semua validasi berhasil! Bukti transfer valid.'
        else:
            return f'❌ Validasi gagal! {len(errors)} kesalahan ditemukan.'
