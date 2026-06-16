"""
Field Extraction Module with Robust Regex Patterns
Extract specific fields from OCR text (Pipeline E dari gambar)
"""
import re
from datetime import datetime
from typing import Optional, Dict, Any, List, Tuple
from dateutil import parser as date_parser
import logging

from config import settings

# Setup logging
logger = logging.getLogger(__name__)


class FieldExtractor:
    """Extract payment fields from OCR text with improved patterns"""
    
    # Bank names to detect (extended list)
    BANK_NAMES = [
        'BCA', 'MANDIRI', 'BRI', 'BNI', 'BTN', 'CIMB', 'NIAGA',
        'DANAMON', 'PERMATA', 'MAYBANK', 'MEGA', 'PANIN',
        'BANK CENTRAL ASIA', 'BANK MANDIRI', 'BANK RAKYAT INDONESIA',
        'BANK NEGARA INDONESIA', 'BSI', 'BANK SYARIAH INDONESIA',
        'JENIUS', 'ALLO BANK', 'SEABANK', 'NEOBANK',
        # E-wallets
        'DANA', 'GOPAY', 'OVO', 'SHOPEE PAY', 'SHOPEEPAY', 'LINK AJA', 'LINKAJA'
    ]
    
    def __init__(self) -> None:
        """Initialize field extractor"""
        self.recipient_name_keywords = settings.recipient_name_keywords_list
        # footer phrases to ignore when extracting recipient name
        self.recipient_footer_blacklist = settings.recipient_footer_blacklist_list
        logger.info("Field extractor initialized")
    
    def extract_all_fields(self, text: str, detections: List[Dict]) -> Dict[str, Any]:
        """
        Extract all important fields from OCR text.
        
        PRIMARY: BankReceiptParser (format-aware structural parser)
        FALLBACK: individual regex extractors for any missing field.
        
        Args:
            text: Full OCR text
            detections: List of detection boxes with text and confidence
        
        Returns:
            Dictionary with extracted fields
        """
        logger.debug(f"Extracting fields from text ({len(text)} chars, {len(detections)} detections)")
        
        # ── PRIMARY: Bank-specific structural parser ──────────────────────
        try:
            from bank_parser import BankReceiptParser
            brp = BankReceiptParser()
            parsed = brp.parse(text, detections)
            primary = brp.to_field_extractor_format(parsed)
            logger.info(
                f"BankReceiptParser: format={parsed.bank_format}, "
                f"nominal={primary.get('amount')}, ref={primary.get('reference_no')}, "
                f"penerima={primary.get('recipient_name')}"
            )
        except Exception as exc:
            logger.warning(f"BankReceiptParser failed ({exc}), falling back to individual extractors")
            primary = {}

        # ── FALLBACK: individual extractors for any field still missing ───
        # Extract datetime
        paid_at = primary.get('paid_at') or self.extract_datetime(text, detections)
        
        # Extract banks
        fb_sender_bank, fb_recipient_bank = self.extract_banks(text, detections)
        sender_bank = primary.get('sender_bank') or fb_sender_bank
        recipient_bank = primary.get('recipient_bank') or fb_recipient_bank

        # Extract names
        sender_name = primary.get('sender_name') or self.extract_sender_name(text)
        recipient_name = primary.get('recipient_name') or self.extract_recipient_name(text, detections)

        # Extract account
        recipient_account = primary.get('recipient_account_no') or self.extract_recipient_account_number(text, detections)

        # Extract amount
        amount = primary.get('amount') or self.extract_amount(text, detections)

        # Extract reference
        reference_no = primary.get('reference_no') or self.extract_reference_number(text)

        fields = {
            'amount': amount,
            'paid_at': paid_at,
            'sender_bank': sender_bank,
            'recipient_bank': recipient_bank,
            # Prefer destination bank for validation
            'bank_name': recipient_bank or sender_bank,
            'sender_name': sender_name,
            'recipient_name': recipient_name,
            'recipient_account_no': recipient_account,
            'sender_account_no': primary.get('sender_account_no'),
            'reference_no': reference_no,
            # Debug metadata
            '_bank_format': primary.get('_bank_format', 'fallback'),
        }
        
        # Log extraction results
        extracted_count = sum(1 for k, v in fields.items() if v is not None and not k.startswith('_'))
        logger.info(f"Extracted {extracted_count}/9 fields successfully")
        for k, v in fields.items():
            if v and not k.startswith('_'):
                logger.debug(f"  {k}: {v}")
        
        return fields


    def extract_recipient_account_number(self, text: str, detections: List[Dict] = None) -> Optional[str]:
        """Extract destination account number (rekening tujuan) from OCR text.

        Returns only digits (10-16 chars) if found.
        """
        # Prefer line-based scan to find labeled fields first
        raw_lines: List[str] = []
        if detections:
            dets_sorted = []
            for det in detections:
                t = det.get('text', '').strip()
                box = det.get('box')
                if not t:
                    continue
                if box:
                    ys = [pt[1] for pt in box]
                    xs = [pt[0] for pt in box]
                    centroid_y = sum(ys) / len(ys)
                    centroid_x = sum(xs) / len(xs)
                else:
                    centroid_y = 0
                    centroid_x = 0
                dets_sorted.append((centroid_y, centroid_x, t))
            dets_sorted.sort(key=lambda x: (x[0], x[1]))
            raw_lines = [t for _, _, t in dets_sorted]
        else:
            raw_lines = [ln.strip() for ln in text.splitlines() if ln.strip()]

        def is_valid_account(candidate: str) -> bool:
            if not candidate:
                return False
            if '*' in candidate or '•' in candidate:
                return False
            digits = re.sub(r'\D', '', candidate)
            return 10 <= len(digits) <= 16

        label_patterns = [
            r'(?:nomor\s+tujuan|no\.?\s*tujuan|rekening\s+tujuan|no\.?\s*rek(?:ening)?\s*tujuan|rekening\s+penerima|no\.?\s*rek(?:ening)?\s*penerima)',
        ]

        for i, ln in enumerate(raw_lines):
            low = ln.lower()
            if any(re.search(p, low) for p in label_patterns):
                inline = re.search(r'[:\-]?\s*([0-9\s\-]{10,25})', ln)
                if inline:
                    cand = inline.group(1)
                    if is_valid_account(cand):
                        return re.sub(r'\D', '', cand)
                # try next line
                for j in range(i + 1, min(i + 3, len(raw_lines))):
                    cand = raw_lines[j].strip()
                    if is_valid_account(cand):
                        return re.sub(r'\D', '', cand)

        text = text.replace('\n', ' ')

        # Context-first patterns (lebih aman daripada ambil angka panjang random)
        patterns = [
            r'(?:no\.?\s*(?:rek|rekening)|nomor\s*(?:rek|rekening)|rekening\s*(?:tujuan|penerima)?|account\s*(?:no|number)|acc(?:ount)?\s*no)\s*[:\-]?\s*([0-9\s\-]{10,25})',
            r'(?:ke\s*rekening|transfer\s*ke)\s*[:\-]?\s*([0-9\s\-]{10,25})',
        ]

        for pattern in patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                digits = re.sub(r'\D', '', match.group(1) or '')
                if 10 <= len(digits) <= 16:
                    return digits

        # Fallback: cari kandidat angka panjang 10-16 digit
        candidates = []
        for m in re.finditer(r'\b[0-9][0-9\s\-]{9,25}\b', text):
            digits = re.sub(r'\D', '', m.group(0) or '')
            if 10 <= len(digits) <= 16:
                # Exclude obvious dates (YYYYMMDD) / times etc by length and pattern
                if len(digits) == 8:
                    continue
                candidates.append(digits)

        # Prefer the longest candidate (seringkali rekening lebih panjang daripada ref)
        if candidates:
            candidates.sort(key=len, reverse=True)
            return candidates[0]

        return None
    
    def clean_ocr_text(self, text: str) -> str:
        """Enhanced text cleaning"""
        # 1. Fix common OCR errors
        replacements = {
            'O': '0',  # Huruf O → angka 0 (untuk nominal)
            'l': '1',  # Huruf l → angka 1
            'I': '1',  # Huruf I → angka 1
            'S': '5',  # Huruf S → angka 5 (context-aware)
            'B': '8',  # Huruf B → angka 8 (context-aware)
        }
        
        # 2. Normalisasi spasi
        text = re.sub(r'\s+', ' ', text)
        
        # 3. Fix format tanggal
        text = re.sub(r'(\d{2})\s*/\s*(\d{2})', r'\1/\2', text)
        
        # 4. Fix format nominal
        text = re.sub(r'Rp\s*\.', 'Rp', text)
        
        return text.strip()

    def extract_amount(self, text: str, detections: List[Dict] = None) -> Optional[float]:
        """
        Extract payment amount (nominal) from text with robust patterns
        Patterns: Rp 190.000, Rp190000, 190.000, etc.
        
        Improvements based on Pipeline E:
        - Multiple format patterns (contoh: "Rp 150.000" / "150000")
        - Proper normalization & validation
        """
        # Prefer labeled lines from OCR detections
        raw_lines: List[str] = []
        if detections:
            dets_sorted = []
            for det in detections:
                t = det.get('text', '').strip()
                box = det.get('box')
                if not t:
                    continue
                if box:
                    ys = [pt[1] for pt in box]
                    xs = [pt[0] for pt in box]
                    centroid_y = sum(ys) / len(ys)
                    centroid_x = sum(xs) / len(xs)
                else:
                    centroid_y = 0
                    centroid_x = 0
                dets_sorted.append((centroid_y, centroid_x, t))
            dets_sorted.sort(key=lambda x: (x[0], x[1]))
            raw_lines = [t for _, _, t in dets_sorted]
        else:
            raw_lines = [ln.strip() for ln in text.splitlines() if ln.strip()]

        def normalize_amount_token(token: str) -> Optional[int]:
            """Normalize OCR amount token into integer Rupiah.

            Handles Indonesian formats like:
            - 190.000
            - 190.000,00
            - 190,000
            - 190000

            Also handles a common OCR error for IDR where `190.00` actually means
            `190.000` (or `190.000,00`).
            """

            if not token:
                return None
            s = token.strip().replace(' ', '')

            # If both separators exist, assume the last separator is the decimal separator.
            if '.' in s and ',' in s:
                if s.rfind(',') > s.rfind('.'):
                    # Likely 190.000,00
                    int_part = s.split(',')[0].replace('.', '')
                    if int_part.isdigit():
                        return int(int_part)
                else:
                    # Likely 190,000.00
                    int_part = s.split('.')[0].replace(',', '')
                    if int_part.isdigit():
                        return int(int_part)

            if ',' in s:
                parts = s.split(',')
                # Decimal cents like 190000,00
                if len(parts) >= 2 and len(parts[-1]) == 2:
                    int_part = ''.join(parts[:-1])
                    if int_part.isdigit():
                        return int(int_part)
                # Thousand grouping like 190,000
                if len(parts) >= 2 and all(p.isdigit() and len(p) == 3 for p in parts[1:]):
                    joined = ''.join(parts)
                    if joined.isdigit():
                        return int(joined)
                s = s.replace(',', '')

            if '.' in s:
                parts = s.split('.')
                # Common OCR: 190.00 (should be 190.000)
                if len(parts) == 2 and parts[0].isdigit() and parts[1] == '00' and 1 <= len(parts[0]) <= 3:
                    return int(parts[0]) * 1000
                # Thousand grouping like 192.500 or 1.234.567
                if len(parts) >= 2 and all(p.isdigit() and len(p) == 3 for p in parts[1:]):
                    joined = ''.join(parts)
                    if joined.isdigit():
                        return int(joined)
                # Decimal cents like 190000.00
                if len(parts) >= 2 and parts[-1].isdigit() and len(parts[-1]) == 2:
                    int_part = ''.join(parts[:-1])
                    if int_part.isdigit():
                        return int(int_part)
                s = s.replace('.', '')

            if s.isdigit():
                # Max 9 digits (999M) to avoid confusing ref numbers as amount
                if len(s) <= 9:
                    return int(s)

            return None

        def parse_amount_from_line(line: str) -> Optional[float]:
            m = re.search(
                r'(?:Rp\.?\s*)?(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?|\d{4,})',
                line,
                re.IGNORECASE,
            )
            if not m:
                return None

            normalized = normalize_amount_token(m.group(1))
            if normalized is None:
                return None

            amount = float(normalized)
            if 10000 <= amount <= 999999999: # Up to 999M
                return amount
            return None

        # Priority: Nominal -> Jumlah Transfer -> Amount -> Total
        priority_labels = [
            ['nominal'],
            ['jumlah transfer', 'jumlah'],
            ['amount'],
            ['total'],
        ]
        amounts = []
        for labels in priority_labels:
            for i, ln in enumerate(raw_lines):
                low = ln.lower()
                if any(lbl in low for lbl in labels):
                    inline = parse_amount_from_line(ln)
                    if inline is not None:
                        logger.debug(f"Found amount (labeled): Rp {inline:,.0f}")
                        amounts.append(inline)
                    # Try next line for separated value
                    for j in range(i + 1, min(i + 3, len(raw_lines))):
                        cand = parse_amount_from_line(raw_lines[j])
                        if cand is not None:
                            logger.debug(f"Found amount (next line): Rp {cand:,.0f}")
                            amounts.append(cand)

        # Remove newlines for better matching
        text = text.replace('\n', ' ')
        
        logger.debug("Extracting amount from text...")
        
        # Enhanced patterns for amount detection
        patterns = [
            # E-wallet specific patterns (DANA, OVO, GoPay)
            r'Kirim\s+Uang\s+Rp\s*([\d.,]+)',  # DANA: "Kirim Uang Rp200.000"
            r'Total\s+Bayar[\s:]+Rp\s*([\d.,]+)',  # DANA: "Total Bayar Rp200.000"
            r'Jumlah\s+Transfer[\s:]+Rp\s*([\d.,]+)',  # Generic
            r'Nominal[\s:]+Rp\s*([\d.,]+)',  # Generic
            
            # Format: Rp 150.000 / Rp 150000 / Rp150.000
            r'(?:Rp\.?\s*)(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)',
            # Format: IDR 150000
            r'(?:IDR\s+)(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)',
            # After keywords (nominal, jumlah, total, amount)
            r'(?:nominal|jumlah|total|amount|transfer|bayar)[:\s]+(?:Rp\.?\s*)?(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)',
            # E-wallet format (Total, Jumlah)
            r'(?:Total|Jumlah)\s+Rp\s*(\d{1,3}(?:[.,]\d{3})*)',
            # BCA Mobile format
            r'(?:Nominal Transfer)\s+(\d{1,3}(?:[.,]\d{3})*)',
            # Standalone large numbers (min 6 digits)
            r'\b(\d{3}[.,]\d{3}(?:[.,]\d{3})?)\b',
        ]
        
        for pattern in patterns:
            matches = re.finditer(pattern, text, re.IGNORECASE)
            for match in matches:
                amount_str = match.group(1)
                normalized = normalize_amount_token(amount_str)
                if normalized is None:
                    continue
                amount = float(normalized)
                if 10000 <= amount <= 999999999: # Up to 999M
                    amounts.append(amount)
                    logger.debug(f"Found potential amount: Rp {amount:,.0f}")
        
        # Return the most common or largest amount
        if amounts:
            # Sort and get the most likely amount (largest)
            amounts.sort(reverse=True)
            selected_amount = amounts[0]
            logger.info(f"Extracted amount: Rp {selected_amount:,.0f}")
            return selected_amount
        
        logger.warning("No amount found in text")
        return None
    
    def extract_datetime(self, text: str, detections: List[Dict] = None) -> Optional[datetime]:
        """
        Extract transaction date and time with improved patterns
        Patterns: 02/01/2026, 2 Januari 2026, 02-01-2026 14:35:22, dd-mm-yyyy / dd/mm/yyyy
        
        Based on Pipeline E: tanggal (format dd-mm-yyyy / dd/mm/yyyy / yyyy-mm-dd)
        """
        text = text.replace('\n', ' ')
        
        logger.debug("Extracting datetime from text...")
        
        # Enhanced date patterns for Indonesian receipt formats
        date_patterns = [
            # E-wallet format: "23 Des 2021 • 13:09" or "23Des 2021.13:09" (handle OCR variations)
            r'(\d{1,2}\s*(?:Jan|Feb|Mar|Apr|Mei|Jun|Jul|Agt|Agu|Sep|Okt|Nov|Des)\s+\d{4})\s*[•·.]?\s*(\d{1,2}:\d{2})',
            # Indonesian month full: "23 Desember 2021"
            r'\b(\d{1,2}\s+(?:Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+\d{4})\b',
            # Indonesian month short: "23 Des 2021"
            r'\b(\d{1,2}\s+(?:Jan|Feb|Mar|Apr|Mei|Jun|Jul|Agt|Agu|Sep|Okt|Nov|Des)\s+\d{4})\b',
            
            # ATM receipt format: "TGL/JAN\n:09/08/1010" or "TGL:09/08/2010"
            r'TGL[:/\s]*(?:JAN|FEB|MAR|APR|MEI|JUN|JUL|AGT|AGU|SEP|OKT|NOV|DES)?[:\s]*(\d{2}[/-]\d{2}[/-]\d{2,4})',
            # Format with colon prefix: ":09/08/2010" or ":09/08/1010"
            r':(\d{2}[/-]\d{2}[/-]\d{2,4})',
            # Format: dd/mm/yyyy, dd-mm-yyyy, dd.mm.yyyy
            r'\b(\d{1,2}[/-]\d{1,2}[/-]\d{2,4})\b',
            # Format: yyyy-mm-dd (ISO format)
            r'\b(\d{4}-\d{1,2}-\d{1,2})\b',
            # After keywords
            r'(?:tanggal|date|tgl|waktu|transfer|transaksi)[:\s]+([^\n]{5,30})',
        ]
        
        # Enhanced time patterns
        time_patterns = [
            # Format: HH:MM:SS or HH:MM (including those after date like :09/08/1010:39:45)
            r'(\d{1,2}:\d{2}:\d{2})',
            r'\b(\d{1,2}:\d{2}(?::\d{2})?)\b',
            # After keywords
            r'(?:waktu|time|jam|pukul)[:\s]+(\d{1,2}:\d{2}(?::\d{2})?)',
        ]
        
        # Extract date with validation
        date_found = None
        time_found = None
        
        for pattern in date_patterns:
            matches = re.finditer(pattern, text, re.IGNORECASE)
            for match in matches:
                try:
                    date_str = match.group(1).strip().strip(':')
                    
                    # Check if this pattern also captured time (for e-wallet format)
                    if match.lastindex and match.lastindex >= 2:
                        try:
                            time_str = match.group(2)
                            time_parts = time_str.split(':')
                            if len(time_parts) >= 2:
                                hour = int(time_parts[0])
                                minute = int(time_parts[1])
                                if 0 <= hour <= 23 and 0 <= minute <= 59:
                                    time_found = (hour, minute, 0)
                        except:
                            pass
                    
                    # Translate Indonesian months to English for parsing
                    month_map = {
                        'Januari': 'January', 'Jan': 'Jan',
                        'Februari': 'February', 'Feb': 'Feb',
                        'Maret': 'March', 'Mar': 'Mar',
                        'April': 'April', 'Apr': 'Apr',
                        'Mei': 'May',
                        'Juni': 'June', 'Jun': 'Jun',
                        'Juli': 'July', 'Jul': 'Jul',
                        'Agustus': 'August', 'Agt': 'Aug', 'Agu': 'Aug',
                        'September': 'September', 'Sep': 'Sep',
                        'Oktober': 'October', 'Okt': 'Oct',
                        'November': 'November', 'Nov': 'Nov',
                        'Desember': 'December', 'Des': 'Dec',
                    }
                    
                    parsed_date_str = date_str
                    for indo, eng in month_map.items():
                        if indo in date_str:
                            parsed_date_str = date_str.replace(indo, eng)
                            break
                    
                    # Try to parse the date
                    parsed_date = date_parser.parse(parsed_date_str, fuzzy=True, dayfirst=True)
                    
                    # Validate year (must be 2000-2030)
                    if not (2000 <= parsed_date.year <= 2030):
                        logger.debug(f"Invalid year {parsed_date.year}, trying to fix...")
                        # Try to fix common OCR errors in dates
                        # Example: 1010 -> 2010, 1026 -> 2026
                        if 1000 <= parsed_date.year <= 1099:
                            parsed_date = parsed_date.replace(year=parsed_date.year + 1000)
                        elif parsed_date.year < 100:
                            parsed_date = parsed_date.replace(year=2000 + parsed_date.year)
                        else:
                            continue
                    
                    # Additional validation: month and day
                    if not (1 <= parsed_date.month <= 12 and 1 <= parsed_date.day <= 31):
                        logger.debug(f"Invalid month/day: {parsed_date.month}/{parsed_date.day}")
                        continue
                    
                    # Apply time if found
                    if time_found:
                        parsed_date = parsed_date.replace(hour=time_found[0], minute=time_found[1], second=time_found[2])
                    
                    date_found = parsed_date
                    logger.debug(f"Found valid date: {date_found.strftime('%Y-%m-%d %H:%M:%S')}")
                    break
                    
                except Exception as e:
                    logger.debug(f"Failed to parse date '{date_str}': {e}")
                    continue
            
            if date_found:
                break
        
        # Extract time separately if not found with date
        if date_found and not time_found:
            for pattern in time_patterns:
                match = re.search(pattern, text, re.IGNORECASE)
                if match:
                    time_str = match.group(1)
                    try:
                        time_parts = time_str.split(':')
                        if len(time_parts) >= 2:
                            hour = int(time_parts[0])
                            minute = int(time_parts[1])
                            second = int(time_parts[2]) if len(time_parts) > 2 else 0
                            
                            # Validate time values
                            if 0 <= hour <= 23 and 0 <= minute <= 59 and 0 <= second <= 59:
                                if date_found:
                                    date_found = date_found.replace(hour=hour, minute=minute, second=second)
                                else:
                                    date_found = datetime.now().replace(hour=hour, minute=minute, second=second)
                                logger.debug(f"Found time: {hour:02d}:{minute:02d}:{second:02d}")
                                break
                    except ValueError as e:
                        logger.debug(f"Failed to parse time '{time_str}': {e}")
                        continue
        
        if date_found:
            logger.info(f"Extracted datetime: {date_found.isoformat()}")
        else:
            logger.warning("No datetime found in text")
        
        return date_found
    
    def extract_banks(self, text: str, detections: List[Dict] = None) -> Tuple[Optional[str], Optional[str]]:
        """
        Extract sender bank and recipient bank separately
        Returns: (sender_bank, recipient_bank)
        
        For ATM receipt: Top bank = sender
        For transfer: Look for patterns like 'Dari BANK X' and 'Ke BANK Y'
        """
        text_upper = text.upper()
        sender_bank = None
        recipient_bank = None

        # Line-based preferred extraction for labeled fields
        raw_lines: List[str] = []
        if detections:
            dets_sorted = []
            for det in detections:
                t = det.get('text', '').strip()
                box = det.get('box')
                if not t:
                    continue
                if box:
                    ys = [pt[1] for pt in box]
                    xs = [pt[0] for pt in box]
                    centroid_y = sum(ys) / len(ys)
                    centroid_x = sum(xs) / len(xs)
                else:
                    centroid_y = 0
                    centroid_x = 0
                dets_sorted.append((centroid_y, centroid_x, t))
            dets_sorted.sort(key=lambda x: (x[0], x[1]))
            raw_lines = [t for _, _, t in dets_sorted]
        else:
            raw_lines = [ln.strip() for ln in text.splitlines() if ln.strip()]

        def find_bank_from_label(labels: List[str]) -> Optional[str]:
            for i, ln in enumerate(raw_lines):
                low = ln.lower()
                if any(lbl in low for lbl in labels):
                    inline = re.search(r':\s*([A-Z\s]+)$', ln, re.IGNORECASE)
                    if inline:
                        bank = self._normalize_bank_name(inline.group(1).strip())
                        if bank:
                            return bank
                    for j in range(i + 1, min(i + 3, len(raw_lines))):
                        bank = self._normalize_bank_name(raw_lines[j])
                        if bank:
                            return bank
            return None

        recipient_bank = find_bank_from_label(['bank tujuan', 'bank penerima'])
        sender_bank = find_bank_from_label(['bank asal', 'bank pengirim', 'sumber dana'])
        
        # Strategy 1: Look for e-wallet sender (DANA, OVO, GoPay)
        ewallet_patterns = [
            r'(DANA|OVO|GOPAY|SHOPEEPAY|LINKAJA)\s+ID',  # "DANA ID"
            r'Metode\s+Pembayaran[:\s]+(Saldo\s+)?(DANA|OVO|GOPAY)',  # "Metode Pembayaran Saldo DANA"
            r'^(DANA|OVO|GOPAY)',  # First line
        ]
        
        for pattern in ewallet_patterns:
            match = re.search(pattern, text_upper, re.MULTILINE)
            if match:
                # Get the e-wallet name
                ewallet_name = None
                if 'DANA' in match.group(0):
                    ewallet_name = 'DANA'
                elif 'OVO' in match.group(0):
                    ewallet_name = 'OVO'
                elif 'GOPAY' in match.group(0):
                    ewallet_name = 'GoPay'
                elif 'SHOPEE' in match.group(0):
                    ewallet_name = 'ShopeePay'
                elif 'LINKAJA' in match.group(0):
                    ewallet_name = 'LinkAja'
                
                if ewallet_name:
                    sender_bank = ewallet_name
                    logger.debug(f"Sender bank (e-wallet): {sender_bank}")
                    break
        
        # Strategy 2: Look for ATM/top bank (usually sender)
        if not sender_bank:
            atm_patterns = [
                r'ATM\s+BANK\s+([A-Z\s]+)',
                r'BANK\s+([A-Z\s]+)\s+ATM',
                r'^([A-Z\s]+)\s+BANK',  # First line bank
            ]
            
            for pattern in atm_patterns:
                match = re.search(pattern, text_upper)
                if match:
                    bank_text = match.group(1).strip()
                    sender_bank = self._normalize_bank_name(bank_text)
                    if sender_bank:
                        logger.debug(f"Sender bank (ATM): {sender_bank}")
                        break
        
        # Strategy 3: Look for transfer patterns
        if not sender_bank:
            transfer_patterns = [
                r'DARI\s+(?:BANK\s+)?([A-Z\s]+)\s+KE',
                r'FROM\s+(?:BANK\s+)?([A-Z\s]+)\s+TO',
            ]
            
            for pattern in transfer_patterns:
                match = re.search(pattern, text_upper)
                if match:
                    bank_text = match.group(1).strip()
                    sender_bank = self._normalize_bank_name(bank_text)
                    if sender_bank:
                        logger.debug(f"Sender bank (transfer): {sender_bank}")
                        break
        
        # Strategy 4: Look for recipient bank (especially in e-wallet transfers)
        recipient_patterns = [
            # E-wallet format: "ke NAMA - BANK ••••1234"
            r'ke\s+[A-Z][A-Z\s]+-\s+([A-Z]+)\s+[•\d]+',  # "ke AHMAD HILMI FAUZAN - BCA ••••2811"
            r'Akun\s+Bank[:\s]+([A-Z]+)',  # "Akun Bank BCA ••••2811"
            r'\b([A-Z]{2,})\s*[.•]{2,}\d+',  # "BCA....2811" (OCR dots instead of bullets)
            r'Bank\s+Tujuan[:\s]+([A-Z\s]+)',  # "Bank Tujuan BCA"
            
            # Traditional patterns
            r'KE\s+(?:BANK\s+)?([A-Z\s]+)',
            r'TO\s+(?:BANK\s+)?([A-Z\s]+)',
            r'TUJUAN\s+(?:BANK\s+)?([A-Z\s]+)',
        ]
        
        for pattern in recipient_patterns:
            match = re.search(pattern, text_upper)
            if match:
                bank_text = match.group(1).strip()
                recipient_bank = self._normalize_bank_name(bank_text)
                if recipient_bank:
                    logger.debug(f"Recipient bank: {recipient_bank}")
                    break
        
        # Strategy 4: Use spatial analysis if detections available
        if detections and not sender_bank:
            # Find bank names in first 3 detections (usually header)
            for i, det in enumerate(detections[:5]):
                det_text = det.get('text', '').upper()
                for bank in self.BANK_NAMES:
                    if bank in det_text:
                        sender_bank = self._normalize_bank_name(bank)
                        logger.debug(f"Sender bank (spatial, pos {i}): {sender_bank}")
                        break
                if sender_bank:
                    break
        
        return sender_bank, recipient_bank
    
    def _normalize_bank_name(self, bank_text: str) -> Optional[str]:
        """Normalize bank name from various formats"""
        bank_text = bank_text.upper().strip()
        
        # Map common variations to standard names
        bank_mapping = {
            'BCA': ['BCA', 'BANK CENTRAL ASIA', 'CENTRAL ASIA'],
            'Mandiri': ['MANDIRI', 'BANK MANDIRI'],
            'BRI': ['BRI', 'BANK RAKYAT INDONESIA', 'RAKYAT INDONESIA'],
            'BNI': ['BNI', 'BANK NEGARA INDONESIA', 'NEGARA INDONESIA'],
            'BTN': ['BTN', 'BANK TABUNGAN NEGARA'],
            'CIMB Niaga': ['CIMB', 'NIAGA', 'CIMB NIAGA', 'BANK CIMB', 'BANK NIAGA'],
            'Danamon': ['DANAMON', 'BANK DANAMON'],
            'Permata': ['PERMATA', 'BANK PERMATA'],
            'BSI': ['BSI', 'BANK SYARIAH INDONESIA', 'SYARIAH INDONESIA'],
        }
        
        for standard_name, variations in bank_mapping.items():
            for variation in variations:
                if variation in bank_text:
                    return standard_name
        
        return None
    
    def extract_bank_name(self, text: str) -> Optional[str]:
        """Extract bank name from text (legacy method)"""
        sender, recipient = self.extract_banks(text)
        return sender or recipient
    
    def extract_sender_name(self, text: str) -> Optional[str]:
        """
        Extract sender name (pengirim) with multi-line support
        Usually the person who sends money (student/parent)
        """
        raw_lines = [ln.strip() for ln in text.splitlines() if ln.strip()]
        
        # 1. Inline extraction (Single line)
        patterns = [
            r'(?:dari|pengirim|sender|dari rekening|atas nama)[:\s]+([A-Za-z][A-Za-z\s]+)',
        ]
        for ln in raw_lines:
            for pattern in patterns:
                match = re.search(pattern, ln, re.IGNORECASE)
                if match:
                    name = match.group(1).strip()
                    name = re.sub(r'\s+', ' ', name)
                    # Exclude if it captured a bank name or contains too many digits
                    if 3 <= len(name) <= 100 and not re.search(r'\d{3,}', name) and not re.search(r'(?i)(bank|ovo|dana|gopay|shopeepay|linkaja)', name):
                        return name.title()

        # 2. Multi-line extraction (Next line)
        for i, ln in enumerate(raw_lines):
            if re.search(r'^(?:dari|pengirim|sender|atas nama)[:\s]*$', ln, re.IGNORECASE):
                # Look at the next 1-2 lines
                for j in range(i+1, min(i+3, len(raw_lines))):
                    candidate = raw_lines[j].strip()
                    if candidate and not re.search(r'\d{4,}', candidate) and not re.search(r'(?i)(bank|ovo|dana|gopay|shopeepay|linkaja)', candidate):
                        return candidate.title()
                        
        return None
    
    def extract_recipient_name(self, text: str, detections: List[Dict] = None) -> Optional[str]:
        """
        Extract recipient name (penerima) with comprehensive patterns
        Covers: Nama/Kepada/BANK/Tujuan/Penerima patterns
        For DANA/e-wallet: "Kirim Uang ke NAMA"
        For Bank: "Ke/Kepada/Tujuan: NAMA"
        For receipt: "BANK\nNAMA PENERIMA"
        """
        # Fast path: if school name appears, prefer it as recipient
        school_name = settings.SCHOOL_ACCOUNT_NAME.strip()
        if school_name:
            text_upper = text.upper()
            school_upper = school_name.upper()
            if school_upper in text_upper:
                return school_name
            # Relaxed match: most school name tokens present in text
            tokens = [t for t in re.split(r'\s+', school_upper) if t]
            if tokens:
                hits = sum(1 for t in tokens if t in text_upper)
                if hits >= max(2, len(tokens) - 1):
                    return school_name

        # Prefer working by line so we can use positional heuristics
        raw_lines: List[str] = []
        # If detections include boxes, sort by visual order (top->bottom, left->right)
        if detections:
            dets_sorted = []
            for det in detections:
                t = det.get('text', '')
                box = det.get('box')
                if not t or not t.strip():
                    continue
                if box:
                    # box: [[x1,y1],[x2,y2],[x3,y3],[x4,y4]]
                    ys = [pt[1] for pt in box]
                    xs = [pt[0] for pt in box]
                    centroid_y = sum(ys) / len(ys)
                    centroid_x = sum(xs) / len(xs)
                else:
                    centroid_y = 0
                    centroid_x = 0
                dets_sorted.append((centroid_y, centroid_x, t.strip()))

            dets_sorted.sort(key=lambda x: (x[0], x[1]))
            raw_lines = [t for _, _, t in dets_sorted]
        else:
            raw_lines = [ln.strip() for ln in text.splitlines() if ln.strip()]

        # normalize lines for matching (but keep original casing for returned name)
        norm_lines = [ln.strip() for ln in raw_lines]

        keyword_pattern = '|'.join(re.escape(keyword) for keyword in self.recipient_name_keywords)

        strong_keywords = [
            'nama tujuan', 'nama penerima', 'penerima', 'tujuan', 'kepada', 'atas nama',
            'recipient', 'destination'
        ]

        def is_candidate_name(candidate: str) -> bool:
            if not candidate:
                return False
            if '*' in candidate or '•' in candidate:
                return False
            if not re.search(r'[A-Za-z]', candidate):
                return False
            digit_count = len(re.sub(r'\D', '', candidate))
            if digit_count >= 4:
                return False
            return True

        # Expanded patterns untuk Indonesia + E-wallet (used as regex fallbacks)
        patterns = [
            # E-wallet patterns (DANA, OVO, GoPay) - PRIORITY
            r'Kirim\s+Uang\s+(?:Rp[\d.,]+\s+)?ke\s+([A-Z][A-Z\s]+?)(?:\s+-\s+|\s*-|$)',  # "Kirim Uang Rp200.000 ke AHMAD HILMI FAUZAN - BCA"
            r'Transfer\s+ke\s+([A-Z][A-Z\s]+?)(?:\s+-\s+|\s*-|$)',  # "Transfer ke AHMAD HILMI FAUZAN - BCA"
            r'Penerima[:\s]+([A-Z][A-Z\s]+?)(?:\s*\n|\s*-|$)',  # "Penerima: AHMAD HILMI FAUZAN"
            
            # Detail Penerima section (common in e-wallets)
            r'Detail\s+Penerima[\s\S]{0,50}?Nama[:\s]+([A-Z][A-Z\s]+)',
            r'(?:Nama|Name)(?:\s+Penerima)?[:\s]+([A-Z][A-Z\s]+?)(?:\s*\n|$)',
            
            # Bank transfer patterns
            r'(?:ke|kepada|tujuan|penerima|recipient)[:\s]+([A-Z][A-Z\s]+?)(?:\s*-|\s*\(|\s*\d|$)',
            r'(?:transfer\s+ke|ke\s+rekening)[:\s]+([A-Z][A-Z\s]+)',
            r'(?:atas\s+nama|a\.n\.|a/n)[:\s]+([A-Z][A-Z\s]+)',

            # Keyword-based fallback for labels like "tujuan" / "nama tujuan"
            rf'(?:nama\s+tujuan|tujuan\s+nama|(?:{keyword_pattern}))[:\s]+([A-Z][A-Z\s]+?)(?:\s*\n|$)',
            rf'(?:(?:{keyword_pattern})\s*(?:rekening|penerima|tujuan|nama)?)[:\s]+([A-Z][A-Z\s]+?)(?:\s*\n|$)',
            
            # Receipt patterns (BANK diikuti NAMA)
            r'(?:BANK|BCA|MANDIRI|BRI|BNI|CIMB|NIAGA)[\s\S]{0,50}?([A-Z][A-Z\s]{5,50}?)(?:\s*\d|$)',
            
            # Nama/Name field
            r'(?:nama|name)[:\s]+([A-Z][A-Z\s]+)',
            
            # Tujuan/Destination
            r'(?:tujuan|destination)[:\s]+([A-Z][A-Z\s]+)',
            
            # After account number patterns
            r'\d{10,16}[\s-]+([A-Z][A-Z\s]{5,50})',
        ]
        
        # Comprehensive blacklist - bank names, e-wallets, transaction keywords
        # NOTE: do NOT include label words like 'NAMA', 'TUJUAN', 'PENERIMA' here;
        # we strip those from candidates instead of blacklisting them.
        blacklist = [
            # Indonesian Banks
            'BCA', 'MANDIRI', 'BRI', 'BNI', 'BTN', 'CIMB', 'NIAGA', 'BANK', 'BSI',
            'DANAMON', 'PERMATA', 'MEGA', 'PANIN', 'BUKOPIN', 'MAYBANK', 'OCBC',
            'HSBC', 'CITIBANK', 'STANDARD CHARTERED', 'ANZ', 'COMMONWEALTH',
            'MUAMALAT', 'SYARIAH', 'MAYAPADA', 'BJB', 'BPD', 'JATENG', 'JATIM',
            'RAKYAT INDONESIA', 'NEGARA INDONESIA', 'TABUNGAN NEGARA',
            'SYARIAH INDONESIA', 'CENTRAL ASIA',
            
            # E-wallets & Payment
            'DANA', 'OVO', 'GOPAY', 'SHOPEEPAY', 'LINKAJA', 'SAKUKU', 'JENIUS',
            'FLIP', 'DOKU', 'KASPRO', 'PAYTREN', 'KREDIVO', 'AKULAKU',
            
            # Transaction Keywords (Indonesian)
            'BERHASIL', 'TRANSFER', 'NOMINAL', 'TRANSAKSI', 'SUKSES', 'SUCCESS',
            'PEMBAYARAN', 'PAYMENT', 'TOTAL', 'JUMLAH', 'AMOUNT', 'SALDO', 'BALANCE',
            'TANGGAL', 'DATE', 'WAKTU', 'TIME', 'JAM', 'PUKUL', 'KETERANGAN',
            'DESCRIPTION', 'DETAIL', 'INFORMASI', 'INFORMATION', 'REKENING', 'ACCOUNT',
            'TERIMA KASIH', 'THANK YOU', 'THANKS', 'STRUK', 'RECEIPT', 'BUKTI', 'PROOF',
            'REFERENSI', 'REFERENCE', 'BIAYA', 'FEE', 'ADMIN', 'ADMINISTRASI',
            'TAGIHAN', 'BILL', 'INVOICE', 'FAKTUR', 'NOTA', 'KWITANSI',
            
            # Transaction Types
            'TRANSFER', 'KIRIM', 'UANG', 'BAYAR', 'PAY', 'SEND', 'RECEIVE', 'TERIMA',
            'TARIK', 'SETOR', 'WITHDRAW', 'DEPOSIT', 'TUNAI', 'CASH', 'ATM',
            'MOBILE', 'BANKING', 'INTERNET', 'ONLINE', 'SMS', 'USSD',
            
            # Common Receipt Words
            'PENERIMA', 'PENGIRIM', 'SENDER', 'RECIPIENT', 'FROM', 'TO', 'DARI', 'KE',
            'KEPADA', 'TUJUAN', 'DESTINATION', 'SOURCE', 'SUMBER', 'ATAS NAMA',
            'NAME', 'AKUN', 'METODE', 'METHOD', 'PROTECTION', 'BANTUAN',
            'HELP', 'CUSTOMER', 'SERVICE', 'LAYANAN', 'PELANGGAN', 'HUBUNGI',
            'CONTACT', 'BAGIKAN', 'SHARE', 'SIMPAN', 'SAVE'
        ]
        # First approach: look for label lines containing recipient keywords then take following non-empty line
        # prepare keyword regex ordered by length (prefer multi-word keywords like 'atas nama')
        sorted_keywords = sorted(self.recipient_name_keywords, key=lambda s: -len(s))
        keyword_pattern_ordered = '|'.join(re.escape(k) for k in sorted_keywords)

        # Fast path for "Ke [Nomor Rekening]" format (m-BCA style)
        for i, ln in enumerate(norm_lines):
            # Cek jika OCR menggabungkannya jadi satu baris: "Ke 3491587171 FAKRIZAL"
            inline_mbca = re.search(r'^(?:ke|kepada|tujuan|penerima|ke rekening)[:\s]*[\d\s-]+\s+([A-Za-z][A-Za-z\s]+)$', ln, re.IGNORECASE)
            if inline_mbca:
                cand = inline_mbca.group(1).strip()
                if not re.search(r'\d{3,}', cand):
                    words = cand.upper().split()
                    if not any(w in blacklist for w in words):
                        logger.debug(f"Recipient name found (m-BCA inline): {cand}")
                        return cand.title()
                        
            # Cek jika formatnya beda baris: "Ke 3491587171" \n "FAKRIZAL"
            if re.search(r'^(?:ke|kepada|tujuan|penerima|ke rekening)[:\s]*[\d\s-]*$', ln, re.IGNORECASE):
                # Look at the next 1-2 lines for the name
                for j in range(i+1, min(i+3, len(norm_lines))):
                    cand = norm_lines[j].strip()
                    if cand and not re.search(r'\d{3,}', cand):
                        words = cand.upper().split()
                        if not any(w in blacklist for w in words):
                            logger.debug(f"Recipient name found (m-BCA style): {cand}")
                            return cand.title()

        skip_until = -1
        for i, ln in enumerate(norm_lines):
            low = ln.lower()
            # skip lines that look like footer
            if any(kw in low for kw in self.recipient_footer_blacklist):
                continue
            if i <= skip_until:
                continue
            if 'sumber dana' in low or 'rekening sumber' in low or 'dari rekening' in low:
                skip_until = i + 2
                continue

            if any(k in low for k in self.recipient_name_keywords):
                if 'nama' in low and not any(sk in low for sk in strong_keywords):
                    continue
                # First, try to extract inline value on same line: 'Nama Tujuan SMK BIT BINA AULIA'
                inline_match = re.search(rf'(?:{keyword_pattern_ordered})\s*[:\-]?\s*(.+)', ln, re.IGNORECASE)
                if inline_match:
                    candidate = inline_match.group(1).strip()
                    if candidate:
                        # remove any leading keyword remnants (e.g. 'Tujuan SMK...' -> 'SMK...')
                        candidate = re.sub(rf'^(?:{keyword_pattern_ordered})\s*[:\-]?\s*', '', candidate, flags=re.IGNORECASE).strip()
                        # reject pure-numeric or masked captures
                        if not is_candidate_name(candidate):
                            logger.debug(f"Rejected inline candidate '{candidate}': not a valid name")
                        else:
                            cand_low = candidate.lower()
                            if not any(kw in cand_low for kw in self.recipient_footer_blacklist):
                                words = re.sub(r'\s+', ' ', candidate).upper().split()
                                if not any(w in blacklist for w in words):
                                    logger.debug(f"Recipient name candidate (inline): {candidate}")
                                    return candidate.title()

                # take next non-empty line as candidate
                for j in range(i+1, min(i+4, len(norm_lines))):
                    candidate = norm_lines[j].strip()
                    if not candidate:
                        continue
                    cand_low = candidate.lower()
                    # skip footer-like lines
                    if any(kw in cand_low for kw in self.recipient_footer_blacklist):
                        continue
                    # If the candidate line itself contains a recipient label, try inline extraction on it
                    if any(k in cand_low for k in self.recipient_name_keywords):
                        inline_match2 = re.search(rf'(?:{keyword_pattern_ordered})\s*[:\-]?\s*(.+)', candidate, re.IGNORECASE)
                        if inline_match2:
                            c2 = inline_match2.group(1).strip()
                            c2 = re.sub(rf'^(?:{keyword_pattern_ordered})\s*[:\-]?\s*', '', c2, flags=re.IGNORECASE).strip()
                            if re.search(r'[A-Za-z]', c2):
                                words = re.sub(r'\s+', ' ', c2).upper().split()
                                if not any(w in blacklist for w in words):
                                    logger.debug(f"Recipient name candidate (inline next-line): {c2}")
                                    return c2.title()
                    # Strip leading label words (like 'Nama', 'Tujuan', 'Nama Tujuan')
                    stripped_candidate = re.sub(rf'^(?:{keyword_pattern_ordered})\s*[:\-]?\s*', '', candidate, flags=re.IGNORECASE).strip()
                    if not stripped_candidate:
                        continue
                    # skip lines that are just numbers or masked numbers
                    if not is_candidate_name(stripped_candidate):
                        continue
                    # reject if candidate contains blacklist word
                    words = re.sub(r'\s+', ' ', stripped_candidate).upper().split()
                    if any(w in blacklist for w in words):
                        continue
                    # accept candidate
                    logger.debug(f"Recipient name candidate from labeled line: {stripped_candidate}")
                    return stripped_candidate.title()
            if re.search(r'\d{9,16}', ln):
                # look ahead a few lines
                for j in range(i+1, min(i+5, len(norm_lines))):
                    candidate = norm_lines[j].strip()
                    if not candidate:
                        continue
                    cand_low = candidate.lower()
                    if any(kw in cand_low for kw in self.recipient_footer_blacklist):
                        continue
                    words = re.sub(r'\s+', ' ', candidate).upper().split()
                    if any(w in blacklist for w in words):
                        continue
                    if len(words) >= 2 and all(len(w) >= 2 for w in words):
                        if not is_candidate_name(candidate):
                            continue
                        logger.debug(f"Recipient name candidate after account: {candidate}")
                        return candidate.title()

        # Third approach: use existing regex patterns on the whole text but reject footer matches
        for pattern in patterns:
            matches = re.finditer(pattern, text, re.IGNORECASE)
            for match in matches:
                name = match.group(1).strip()
                # Clean up the name
                name = re.sub(r'\s+', ' ', name)
                name_norm = name.strip()
                name_low = name_norm.lower()
                # reject footer-like matches
                if any(kw in name_low for kw in self.recipient_footer_blacklist):
                    logger.debug(f"Rejected regex match '{name_norm}': footer keyword present")
                    continue
                # Filter out blacklisted words (strict check - whole word match)
                words = name_norm.upper().split()
                if any(word in blacklist for word in words):
                    logger.debug(f"Rejected '{name_norm}': contains blacklisted word")
                    continue
                # skip digit-heavy matches
                if re.search(r'\d{3,}', name_norm):
                    logger.debug(f"Rejected '{name_norm}': contains digits")
                    continue
                if 3 <= len(name_norm) <= 100:
                    logger.debug(f"Recipient name found (regex fallback): {name_norm}")
                    return name_norm.title()
        
        # Fallback Strategy 1: Look for sequences of capital letters (2-4 words)
        # More strict - only use if patterns above didn't find anything
        capital_sequences = re.findall(r'\b([A-Z][A-Z\s]{10,50})\b', text)
        if capital_sequences:
            for seq in capital_sequences:
                seq = seq.strip().upper()
                
                # Strict blacklist check - whole word match only
                words_in_seq = seq.split()
                has_blacklisted = False
                for word in words_in_seq:
                    if word in blacklist:
                        logger.debug(f"Fallback rejected '{seq}': contains blacklisted word '{word}'")
                        has_blacklisted = True
                        break
                
                if has_blacklisted:
                    continue
                    
                # Must have 2-4 words (real names typically have this range)
                words = seq.split()
                if 2 <= len(words) <= 4:
                    # Each word should be at least 3 chars (avoid initials/abbreviations)
                    if all(len(w) >= 3 for w in words):
                        logger.debug(f"Recipient name found (fallback): {seq.title()}")
                        return seq.title()
                    else:
                        logger.debug(f"Fallback rejected '{seq}': words too short")
        
        # Fallback Strategy 2: Find proper names after common prefixes
        name_context_patterns = [
            r'(?:Yth|Kepada Yth)[:\s]+([A-Z][A-Za-z\s]+)',
            r'(?:Dari|From)[:\s]+([A-Z][A-Za-z\s]+?)(?:Ke|To)',
            r'(?:Ke|To)[:\s]+([A-Z][A-Za-z\s]+?)(?:Dari|From|$)',
        ]
        
        for pattern in name_context_patterns:
            match = re.search(pattern, text)
            if match:
                name = match.group(1).strip().upper()
                if not any(word in name for word in blacklist):
                    if 3 <= len(name) <= 100:
                        logger.debug(f"Recipient name found (context): {name.title()}")
                        return name.title()
        
        logger.debug("No recipient name found")
        return None
    
    def extract_reference_number(self, text: str) -> Optional[str]:
        """
        Extract transaction reference number
        Usually alphanumeric code
        """
        text = text.replace('\n', ' ')
        
        patterns = [
            # E-wallet transaction IDs - ID Transaksi has highest priority
            r'ID\s+Transaksi[:\s]+[•\s]*([A-Za-z0-9\-]+)',  # "ID Transaksi 3019" or hex
            r'(?:Transaction\s+ID)[:\s]+[•\s]*([A-Za-z0-9\-]+)',
            r'(?:DANA\s+ID|OVO\s+ID|GoPay\s+ID)[:\s]+([A-Za-z0-9\-•]+)',
            r'(?:Referensi|Reference)[:\s]+([A-Za-z0-9\-•]+)',
            
            # Bank patterns
            r'(?:no\.?\s*ref|referensi|reference|no\.?\s*transaksi|transaction)[:\s]+([A-Za-z0-9\-]+)',
            r'\b(TRF-[A-Za-z0-9]{10,})\b',  # OVO modern format
            r'\b(TRF\d{10,})\b',
            r'\b([A-Z]{3}\d{8,})\b',
            r'\b(\d{12,20})\b', # Pure long digits as fallback for ref
        ]
        
        for pattern in patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                ref = match.group(1).strip()
                # Remove bullet points
                ref = ref.replace('•', '').replace('.', '')
                # Valid length: at least 3 chars (e.g. "3019" is 4 chars)
                if 3 <= len(ref) <= 50:
                    logger.debug(f"Reference number found: {ref}")
                    return ref.upper()
        
        logger.debug("No reference number found")
        return None
    
    def validate_extraction(self, fields: Dict[str, Any]) -> Dict[str, Any]:
        """
        Validate extracted fields
        Returns validation result with status and messages
        """
        result = {
            'is_valid': True,
            'missing_fields': [],
            'warnings': []
        }
        
        # Check required fields
        required = ['amount', 'paid_at']
        for field in required:
            if not fields.get(field):
                result['missing_fields'].append(field)
                result['is_valid'] = False
        
        # Check optional but recommended fields
        recommended = ['bank_name', 'sender_name']
        for field in recommended:
            if not fields.get(field):
                result['warnings'].append(f'{field} not found')
        
        return result
