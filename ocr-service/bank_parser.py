"""
Bank-Specific Receipt Parser
=============================
Specialized parsers for each Indonesian bank format.
Each parser uses structural analysis (layout-aware) + regex patterns
to extract fields with maximum accuracy.

Supported formats:
- BRI Mobile Banking (Sumber Dana / Tujuan sections)
- BCA m-BCA Transfer
- Mandiri Online/Livin'
- DANA / OVO / GoPay e-wallets
- Generic fallback

Author: OCR System
Version: 3.0 - Professional Edition
"""
import re
import logging
from typing import Optional, Dict, Any, List, Tuple
from dataclasses import dataclass, field

logger = logging.getLogger(__name__)


# ─── Data Classes ─────────────────────────────────────────────────────────────

@dataclass
class ParsedReceipt:
    """Structured result from bank receipt parsing."""
    bank_format: str = "unknown"       # Which bank/format was detected
    confidence_score: float = 0.0      # How confident we are in this parse (0-1)

    nominal: Optional[str] = None
    tanggal: Optional[str] = None
    waktu: Optional[str] = None

    nama_pengirim: Optional[str] = None
    bank_pengirim: Optional[str] = None
    rekening_pengirim: Optional[str] = None   # may contain **** masks

    nama_penerima: Optional[str] = None
    bank_tujuan: Optional[str] = None
    rekening_tujuan: Optional[str] = None

    nomor_referensi: Optional[str] = None
    jenis_transaksi: Optional[str] = None
    catatan: Optional[str] = None

    def to_dict(self) -> Dict[str, Any]:
        return {k: v for k, v in self.__dict__.items() if v is not None}


# ─── Utility helpers ──────────────────────────────────────────────────────────

_MONTHS_ID = {
    'januari': '01', 'februari': '02', 'maret': '03', 'april': '04',
    'mei': '05', 'juni': '06', 'juli': '07', 'agustus': '08',
    'september': '09', 'oktober': '10', 'november': '11', 'desember': '12',
    'jan': '01', 'feb': '02', 'mar': '03', 'apr': '04', 'mei': '05',
    'jun': '06', 'jul': '07', 'agu': '08', 'agt': '08', 'sep': '09',
    'okt': '10', 'nov': '11', 'des': '12',
}

def _normalize_amount(raw: str) -> Optional[str]:
    """
    Normalize Indonesian number format to plain integer string.
    Handles: 770.000 / 770,000 / 770000 / Rp770.000 / Rp 770.000,00
    """
    if not raw:
        return None
    s = re.sub(r'[Rr][Pp]\.?\s*', '', raw).strip()
    # If both dot and comma exist, determine decimal separator
    if '.' in s and ',' in s:
        if s.rfind(',') > s.rfind('.'):
            # Indonesian: 770.000,00  → int part before comma
            s = s.split(',')[0].replace('.', '')
        else:
            # English: 770,000.00 → int part before dot
            s = s.split('.')[0].replace(',', '')
    elif ',' in s:
        parts = s.split(',')
        if len(parts) == 2 and len(parts[1]) == 2 and parts[1].isdigit():
            # cents: 770000,00
            s = parts[0]
        else:
            s = s.replace(',', '')
    elif '.' in s:
        parts = s.split('.')
        if len(parts) == 2 and parts[0].isdigit() and parts[1] == '00' and 1 <= len(parts[0]) <= 3:
            # Common OCR loss: Rp190.000 becomes Rp190.00. In Indonesian receipts,
            # a 1-3 digit amount prefix with ".00" is much more likely thousands.
            s = str(int(parts[0]) * 1000)
        elif len(parts) == 2 and len(parts[1]) == 2 and parts[1].isdigit():
            # 770000.00
            s = parts[0]
        elif all(len(p) == 3 and p.isdigit() for p in parts[1:]):
            # 770.000 or 1.234.567
            s = ''.join(parts)
        else:
            s = s.replace('.', '')
    digits = re.sub(r'\D', '', s)
    if digits and 1000 <= int(digits) <= 999_999_999:
        return digits
    return None


def _normalize_date(raw: str) -> Optional[str]:
    """
    Normalize various date formats to YYYY-MM-DD.
    Handles: 07 Juni 2026, 07/06/2026, 07-06-2026
    """
    if not raw:
        return None
    raw = raw.strip()

    # Try "07 Juni 2026" or "7 Jun 2026"
    m = re.match(r'^(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})$', raw)
    if m:
        day = m.group(1).zfill(2)
        month_str = m.group(2).lower()
        year = m.group(3)
        month = _MONTHS_ID.get(month_str)
        if month:
            return f"{year}-{month}-{day}"

    # Try dd/mm/yyyy or dd-mm-yyyy or dd.mm.yyyy
    m = re.match(r'^(\d{1,2})[/\-\.](\d{1,2})[/\-\.](\d{2,4})$', raw)
    if m:
        day = m.group(1).zfill(2)
        month = m.group(2).zfill(2)
        year = m.group(3)
        if len(year) == 2:
            year = '20' + year
        if 1 <= int(month) <= 12 and 1 <= int(day) <= 31:
            return f"{year}-{month}-{day}"

    # Try yyyy-mm-dd
    m = re.match(r'^(\d{4})-(\d{1,2})-(\d{1,2})$', raw)
    if m:
        return f"{m.group(1)}-{m.group(2).zfill(2)}-{m.group(3).zfill(2)}"

    return None


def _clean_name(raw: str) -> Optional[str]:
    """Clean and validate a person/institution name."""
    if not raw:
        return None
    # Remove leading/trailing punctuation and whitespace
    name = re.sub(r'^[\s\-:,;.]+|[\s\-:,;.]+$', '', raw)
    name = re.sub(r'\s+', ' ', name).strip()
    if len(name) < 2 or len(name) > 100:
        return None
    # Must contain at least one letter
    if not re.search(r'[A-Za-z]', name):
        return None
    return name


def _is_masked_account(s: str) -> bool:
    """Check if string is a masked account number like '5857 **** **** 532'."""
    return bool(re.search(r'\*{4}|\*{3}|•{4}|X{4}', s))


def _clean_account(raw: str) -> Optional[str]:
    """Clean account number, preserving masked characters."""
    if not raw:
        return None
    # Keep digits, spaces, asterisks, dots, X
    cleaned = re.sub(r'[^0-9\s\*\.Xx]', '', raw).strip()
    if not cleaned:
        return None
    digits_only = re.sub(r'\D', '', cleaned)
    # Must have at least 6 digits (even masked)
    if len(digits_only) < 6 and not _is_masked_account(cleaned):
        return None
    return cleaned


# ─── Structural line-based parser ────────────────────────────────────────────

class StructuralParser:
    """
    Parse receipt by treating it as a sequence of labeled sections.
    Each "section header" (like "Sumber Dana", "Tujuan") introduces a context
    for the next few lines.
    """

    # Section headers that indicate we're now in the SENDER section
    SENDER_HEADERS = [
        'sumber dana', 'sumberdana', 'rekening sumber', 'rekeningsumber', 'dari rekening', 'dari rek.', 'dari rek', 'dari', 'pengirim',
        'rekening asal', 'asal', 'from', 'sender'
    ]

    # Section headers that indicate we're now in the RECIPIENT section
    RECIPIENT_HEADERS = [
        'tujuan', 'rekening tujuan', 'rekeningtujuan', 'ke rekening', 'ke rek.', 'ke rek', 'ke', 'penerima',
        'nama tujuan', 'namatujuan', 'bank tujuan', 'banktujuan', 'rekening penerima', 'to', 'recipient', 'destination',
        'bank tuj.', 'bank tuj', 'bank tuj:', 'bank tuj.:'
    ]

    # Known bank names
    BANK_PATTERNS = {
        'BRI': r'\bBRI\b|BANK\s*RAKYAT\s*INDONESIA|BANKBRI',
        'BCA': r'\bBCA\b|BANK\s*CENTRAL\s*ASIA|BANKBCA|MYBCA',
        'Mandiri': r'\bMANDIRI\b|BANK\s*MANDIRI|BANKMANDIRI',
        'BNI': r'\bBNI\b|BANK\s*NEGARA\s*INDONESIA|BANKBNI',
        'BTN': r'\bBTN\b|BANK\s*TABUNGAN\s*NEGARA|BANKBTN',
        'BSI': r'\bBSI\b|BANK\s*SYARIAH\s*INDONESIA|BANKBSI',
        'CIMB Niaga': r'\bCIMB\b|\bNIAGA\b|CIMB\s*NIAGA|CIMBNIAGA',
        'Danamon': r'\bDANAMON\b',
        'Permata': r'\bPERMATA\b',
        'Mega': r'\bMEGA\b',
        'SeaBank': r'\bSEABANK\b',
        'Jago': r'\bJAGO\b|\bBANK\s*JAGO\b|BANKJAGO',
        'DANA': r'\bDANA\b',
        'OVO': r'\bOVO\b',
        'GoPay': r'\bGOPAY\b',
        'ShopeePay': r'\bSHOPEEPAY\b|\bSHOPEE\s*PAY\b',
        'LinkAja': r'\bLINKAJA\b|\bLINK\s*AJA\b',
    }

    def detect_bank_in_line(self, line: str) -> Optional[str]:
        """Detect bank name in a single line."""
        line_upper = line.upper()
        for bank_name, pattern in self.BANK_PATTERNS.items():
            if re.search(pattern, line_upper):
                return bank_name
        return None

    def extract_amount_from_line(self, line: str) -> Optional[str]:
        """Extract amount from a single line."""
        # Fix common OCR typos for amounts: RPS -> RP5, RP.S -> RP.5, S.000 -> 5.000
        line_fixed = re.sub(r'(?i)(RP\.?\s*)S', r'\g<1>5', line)
        line_fixed = re.sub(r'(?i)\bS(\.\d{3})\b', r'5\1', line_fixed)
        
        # Match Rp patterns
        m = re.search(r'(?:Rp\.?\s*)([\d.,]+)', line_fixed, re.IGNORECASE)
        if m:
            return _normalize_amount(m.group(1))
        # Match plain numbers (at least 5 digits, e.g. 770000)
        m = re.search(r'\b(\d{1,3}(?:[.,]\d{3})+)\b', line_fixed)
        if m:
            return _normalize_amount(m.group(1))
        return None

    def extract_date_from_line(self, line: str) -> Optional[str]:
        """Extract date from a single line."""
        # Pattern: "2022-01-19" or "2022/01/19" (allow trailing digits from time stuck to it)
        m = re.search(r'\b(\d{4})[/\-\.](\d{1,2})[/\-\.](\d{1,2})', line)
        if m:
            return f"{m.group(1)}-{m.group(2).zfill(2)}-{m.group(3).zfill(2)}"
            
        # Pattern: "07 Juni 2026" or "07/06/2026" or "07-06-2026"
        months = '|'.join(_MONTHS_ID.keys())
        m = re.search(rf'(\d{{1,2}})\s+({months})\s+(\d{{4}})', line, re.IGNORECASE)
        if m:
            day = m.group(1).zfill(2)
            month = _MONTHS_ID.get(m.group(2).lower(), '??')
            year = m.group(3)
            return f"{year}-{month}-{day}"
            
        m = re.search(r'\b(\d{1,2})[/\-\.](\d{1,2})[/\-\.](\d{2,4})\b', line)
        if m:
            return _normalize_date(f"{m.group(1)}/{m.group(2)}/{m.group(3)}")
        return None

    def extract_time_from_line(self, line: str) -> Optional[str]:
        """Extract time from a single line."""
        # 1. Three parts strictly separated by colon or dot (e.g., 12:28:00 or 12.28.00)
        m = re.search(r'\b(\d{1,2})[:.](\d{2})[:.](\d{2})\b(?:\s*WIB|WIT|WITA)?', line, re.IGNORECASE)
        if m:
            return f"{m.group(1).zfill(2)}:{m.group(2)}:{m.group(3)}"
            
        # 2. Two parts with keyword or timezone
        m = re.search(r'(?:waktu|jam|time|pukul)\s*[:=]?\s*(\d{1,2})[:.](\d{2})\b|\b(\d{1,2})[:.](\d{2})\s+(?:WIB|WIT|WITA)\b', line, re.IGNORECASE)
        if m:
            if m.group(1):
                return f"{m.group(1).zfill(2)}:{m.group(2)}:00"
            else:
                return f"{m.group(3).zfill(2)}:{m.group(4)}:00"
                
        # 3. Two parts strictly separated by colon
        m = re.search(r'\b(\d{1,2}):(\d{2})\b', line)
        if m:
            return f"{m.group(1).zfill(2)}:{m.group(2)}:00"
            
        return None

    def extract_account_from_line(self, line: str) -> Optional[str]:
        """
        Extract account number from line. 
        Handles masked formats: "5857 **** **** 532"
        """
        # Check for masked format first: digits *** digits
        # Requires at least one actual mask character (*, •, or X)
        m = re.search(r'(\d{3,6}[\s\*•X]*[\*•X]+[\s\*•X]*\d{2,6})', line, re.IGNORECASE)
        if m:
            cand = m.group(1).strip()
            return cand if len(re.sub(r'\D', '', cand)) >= 4 else None
        # Plain account: 10-16 contiguous digits (with optional spaces/dashes)
        m = re.search(r'\b(\d[\d\s\-]{8,20}\d)\b', line)
        if m:
            digits = re.sub(r'\D', '', m.group(1))
            if 10 <= len(digits) <= 16:
                return digits
        return None

    def parse(self, lines: List[str], raw_text: str) -> ParsedReceipt:
        """
        Parse a receipt using structural line-by-line analysis.
        """
        result = ParsedReceipt()
        result.bank_format = "structural"

        section = None  # 'sender', 'recipient', 'transaction', None
        section_buffer = []  # Lines accumulated in current section

        # State for collecting sender/recipient data
        sender_data = {'name': None, 'bank': None, 'account': None}
        recipient_data = {'name': None, 'bank': None, 'account': None}

        i = 0
        while i < len(lines):
            line = lines[i].strip()
            if not line:
                i += 1
                continue

            line_low = line.lower()

            # ── Detect section transitions ─────────────────────────────────
            # Match strict headers (e.g. "DARI :", "TUJUAN")
            is_sender_header_only = any(
                re.match(rf'^{re.escape(h)}[\s:]*$', line_low)
                for h in self.SENDER_HEADERS
            ) or any(h == line_low for h in self.SENDER_HEADERS)

            is_recipient_header_only = any(
                re.match(rf'^{re.escape(h)}[\s:]*$', line_low)
                for h in self.RECIPIENT_HEADERS
            ) or any(h == line_low for h in self.RECIPIENT_HEADERS)

            # Match headers that contain data (e.g. "DARI REK. : 5345...")
            sender_header_match = next(
                (h for h in self.SENDER_HEADERS if re.match(rf'^{re.escape(h)}[\s:]+(.+)$', line_low)), 
                None
            )
            recipient_header_match = next(
                (h for h in self.RECIPIENT_HEADERS if re.match(rf'^{re.escape(h)}[\s:]+(.+)$', line_low)), 
                None
            )

            # "Transfer Bank BRI" or "Jenis Transaksi" lines
            is_transaction_header = bool(re.search(r'jenis\s+transaksi|keterangan|catatan', line_low))

            if is_sender_header_only:
                section = 'sender'
                i += 1
                continue
            elif sender_header_match:
                section = 'sender'
                # Strip the header from the line and process the rest
                line = re.sub(rf'(?i)^{re.escape(sender_header_match)}\s*[:\-\.]?\s*', '', line).strip()
                if line:
                    self._update_data_from_line(line, sender_data)
                i += 1
                continue
            elif is_recipient_header_only:
                section = 'recipient'
                i += 1
                continue
            elif recipient_header_match:
                section = 'recipient'
                # Strip the header from the line and process the rest
                line = re.sub(rf'(?i)^{re.escape(recipient_header_match)}\s*[:\-\.]?\s*', '', line).strip()
                if line:
                    self._update_data_from_line(line, recipient_data)
                i += 1
                continue
            elif is_transaction_header:
                section = 'transaction'
                i += 1
                continue

            # ── Labeled field extraction (key: value patterns) ────────────
            # Lookahead for Reference Number on next line
            if re.match(r'(?i)^(?:id\s*transaksi|nomor\s*referensi|reference|no\.?\s*ref|transaction\s*id)[\s:]*$', line_low):
                if i + 1 < len(lines):
                    next_line = lines[i + 1].strip()
                    if re.match(r'^[A-Za-z0-9]{4,30}$', next_line) and not getattr(self, 'reference_locked', False):
                        cand = next_line
                        if not re.match(r'(?i)^(rekening|rek|nama|jumlah|total|bank|dari|kepada)$', cand):
                            result.nomor_referensi = cand
                            self.reference_locked = True
                            i += 2
                            continue

            # Handle "No. Ref    155741545916" or "Nominal    Rp770.000"
            if self._try_extract_labeled(line, result):
                i += 1
                continue

            # ── Section-aware extraction ──────────────────────────────────
            if section == 'sender':
                old_acc = sender_data['account']
                self._update_data_from_line(line, sender_data)
                
                # Multi-line Account Reconstruction
                if sender_data['account'] and sender_data['account'] != old_acc:
                    while True:
                        digits = re.sub(r'\D', '', sender_data['account'])
                        if len(digits) >= 15: # typical bank length
                            break
                        if i + 1 < len(lines):
                            next_line = lines[i + 1].strip()
                            # If next line is just numbers/spaces/dashes
                            if next_line and re.match(r'^[\d\s\.\-]+$', next_line):
                                sender_data['account'] += next_line
                                i += 1
                            else:
                                break
                        else:
                            break

            elif section == 'recipient':
                old_acc = recipient_data['account']
                self._update_data_from_line(line, recipient_data)
                
                # Multi-line Account Reconstruction
                if recipient_data['account'] and recipient_data['account'] != old_acc:
                    while True:
                        digits = re.sub(r'\D', '', recipient_data['account'])
                        if len(digits) >= 15: # typical bank length
                            break
                        if i + 1 < len(lines):
                            next_line = lines[i + 1].strip()
                            # If next line is just numbers/spaces/dashes
                            if next_line and re.match(r'^[\d\s\.\-]+$', next_line):
                                recipient_data['account'] += next_line
                                i += 1
                            else:
                                break
                        else:
                            break

            i += 1

        # ── Apply collected data ──────────────────────────────────────────
        if sender_data['name']:
            result.nama_pengirim = sender_data['name']
        if sender_data['bank']:
            result.bank_pengirim = sender_data['bank']
        if sender_data['account']:
            result.rekening_pengirim = sender_data['account']

        if recipient_data['name']:
            result.nama_penerima = recipient_data['name']
        if recipient_data['bank']:
            result.bank_tujuan = recipient_data['bank']
        if recipient_data['account']:
            result.rekening_tujuan = recipient_data['account']

        # ── Final fallbacks using full text ───────────────────────────────
        self._apply_full_text_fallbacks(raw_text, result)

        return result

    def _try_extract_labeled(self, line: str, result: ParsedReceipt) -> bool:
        """
        Try to extract a labeled field from line (e.g. "No. Ref  123456").
        Returns True if a field was extracted.
        """
        line_low = line.lower()

        # Reference number: "No. Ref   155741545916" or "ID Transaksi"
        if re.search(r'id\s*transaksi|nomor\s*referensi|reference|ref|transaction\s*id', line_low):
            m = re.search(r'(?:id\s*transaksi|nomor\s*referensi|reference|ref|transaction\s*id)[\s:]+([A-Za-z0-9]{4,30})', line, re.IGNORECASE)
            if m and result.nomor_referensi is None and not getattr(self, 'reference_locked', False):
                cand = m.group(1).strip()
                if not re.match(r'(?i)^(rekening|rek|nama|jumlah|total|bank|dari|kepada)$', cand):
                    result.nomor_referensi = cand
                    self.reference_locked = True
                    return True

        # Nominal: "Nominal   Rp770.000" or "Total Transaksi   Rp770.000"
        if re.search(r'\b(?:nominal|total\s+transaksi|jumlah\s+transfer|total\s+bayar)\b', line_low):
            amt = self.extract_amount_from_line(line)
            if amt and result.nominal is None:
                result.nominal = amt
                return True

        # Date + Time combined: "07 Juni 2026, 13:54:28 WIB"
        dt = self.extract_date_from_line(line)
        if dt and result.tanggal is None:
            result.tanggal = dt
            tm = self.extract_time_from_line(line)
            if tm:
                result.waktu = tm
            return True
            
        # Standalone Time: "WAKTU : 12:28:00"
        tm = self.extract_time_from_line(line)
        if tm and result.waktu is None:
            result.waktu = tm
            # If line is JUST time, we can return True. 
            # If it has other info, we might want to continue, but usually time is alone or with date.
            if re.search(r'(?:waktu|jam|time|pukul)\s*[:=]?\s*\d', line_low):
                return True

        # Jenis Transaksi: "Jenis Transaksi   Transfer Bank BRI"
        m = re.search(r'jenis\s+transaksi[\s:]+(.+)', line_low)
        if m and result.jenis_transaksi is None:
            result.jenis_transaksi = m.group(1).strip().title()
            return True

        # Catatan: "Catatan   -"
        m = re.search(r'catatan[\s:]+(.+)', line_low)
        if m and result.catatan is None:
            result.catatan = m.group(1).strip()
            return True

        return False

    def _update_data_from_line(self, line: str, data: dict):
        """
        Try to fill bank/name/account from a line within a section.
        Priority: bank > masked account > name
        """
        line_stripped = line.strip()
        if not line_stripped:
            return

        # Bank detection
        bank = self.detect_bank_in_line(line_stripped)
        if bank and data['bank'] is None:
            data['bank'] = bank
            return  # bank line, not a name line

        # Account number detection (masked or plain)
        acct = self.extract_account_from_line(line_stripped)
        if acct and data['account'] is None:
            data['account'] = acct
            return

        # Strip common label prefixes first (e.g. "NAMA : ", "DARI : ", "NAA : ")
        # This prevents the actual name from being ignored, and removes OCR artifacts like "NAA :"
        name_cand = re.sub(r'(?i)^(nama|naa|pengirim|penerima|dari|ke|kepada|atas nama)\s*[:=.\-]?\s*', '', line_stripped)
        
        # Also strip any stray leading punctuation like ": " or "- "
        name_cand = re.sub(r'^[:=.\-]\s*', '', name_cand).strip()
        
        if not name_cand:
            return

        # Name heuristic: mostly letters, 2-50 chars, no digits run > 3
        if (re.search(r'[A-Za-z]', name_cand)
                and not re.search(r'\d{4,}', name_cand)
                and len(name_cand) >= 2
                and len(name_cand) <= 50):
            # Skip lines that contain bank names or transaction keywords as standalone words
            skip_words = [
                r'\btransfer\b', r'\bberhasil\b', r'\btransaksi\b', r'\binformasi\b',
                r'\bketerangan\b', r'\bcatatan\b', r'\bbiaya\b', r'\badmin\b',
                r'\bnominal\b', r'\btotal\b', r'\bjumlah\b', r'\bpembayaran\b', r'\blihat\b', r'\bdetail\b',
                r'\bbank\b', r'\bref\b', r'\breferensi\b', r'\brekening\b', r'\brek\b', r'\bnama\b', r'\bpengirim\b',
                r'\bpenerima\b', r'\bwaktu\b', r'\btanggal\b', r'\blokasi\b', r'\bstruk\b', r'\batm\b'
            ]
            
            # Combine into a single regex for exact word matching
            skip_pattern = re.compile('|'.join(skip_words), re.IGNORECASE)
            
            if not skip_pattern.search(name_cand):
                if data['name'] is None:
                    data['name'] = name_cand

    def _apply_full_text_fallbacks(self, raw_text: str, result: ParsedReceipt):
        """Apply fallback extractions on full text for missing fields."""
        text_no_nl = re.sub(r'\s+', ' ', raw_text)

        # Total/final amount wins over nominal when both exist. On receipts with
        # admin fees, "Nominal" is the transfer base and "Total" is the paid value.
        total_patterns = [
            r'\b(?:total\s+transaksi|total\s+bayar|total)\b[^\dRr]{0,80}(?:Rp\.?\s*)?([\d.,]+)',
        ]
        for pattern in total_patterns:
            m = re.search(pattern, raw_text, re.IGNORECASE | re.DOTALL)
            if m:
                total_amount = _normalize_amount(m.group(1))
                if total_amount:
                    result.nominal = total_amount
                    break

        # Nominal fallback
        if result.nominal is None:
            m = re.search(r'(?:Total\s+Transaksi|Nominal|Jumlah)[^\d]*([\d.,]{5,})', raw_text, re.IGNORECASE)
            if m:
                result.nominal = _normalize_amount(m.group(1))

        # Reference number fallback  
        if result.nomor_referensi is None and not getattr(self, 'reference_locked', False):
            m = re.search(r'(?:\bid\s*transaksi\b|\bnomor\s*referensi\b|\breference\b|\bref\b|\btransaction\s*id\b)[ \t:]+([A-Za-z0-9]{4,30})', raw_text, re.IGNORECASE)
            if m:
                # Ensure it's not a label word being captured (like "REKENING" or "NAMA")
                cand = m.group(1).strip()
                if not re.match(r'(?i)^(rekening|rek|nama|jumlah|total|bank|dari|kepada)$', cand):
                    result.nomor_referensi = cand

        # Date fallback
        if result.tanggal is None:
            # Pattern: "2022-01-19"
            m = re.search(r'\b(\d{4})[/\-\.](\d{1,2})[/\-\.](\d{1,2})', raw_text)
            if m:
                result.tanggal = f"{m.group(1)}-{m.group(2).zfill(2)}-{m.group(3).zfill(2)}"
                tm = self.extract_time_from_line(m.group(0) + raw_text[m.end():m.end()+30])
                if tm:
                    result.waktu = tm
            else:
                months = '|'.join(_MONTHS_ID.keys())
                m = re.search(rf'(\d{{1,2}})\s+({months})\s+(\d{{4}})', raw_text, re.IGNORECASE)
                if m:
                    day = m.group(1).zfill(2)
                    month = _MONTHS_ID.get(m.group(2).lower(), '00')
                    year = m.group(3)
                    result.tanggal = f"{year}-{month}-{day}"
                    # Look for time nearby
                    tm = self.extract_time_from_line(m.group(0) + raw_text[m.end():m.end()+30])
                    if tm:
                        result.waktu = tm


# ─── Format-Specific Parsers ──────────────────────────────────────────────────

class BRIMobileParser:
    """
    Parser for BRI Mobile Banking receipts.
    
    Expected structure:
        Transaksi Berhasil
        07 Juni 2026, 13:54:28 WIB
        
        Total Transaksi
        Rp770.000
        
        No. Ref                    155741545916
        
        Sumber Dana
          SLAMET
          BANK BRI
          5857 **** **** 532
        
        Tujuan
          SMK BIT BINA AULIA
          BANK BRI
          2180 0100 0867 569
        
        Jenis Transaksi            Transfer Bank BRI
        Catatan
        
        Nominal                    Rp770.000
        Biaya Admin                Rp0
    """

    HEADER_SIGNATURE = re.compile(
        r'transaksi\s+berhasil|transfer\s+berhasil|pembayaran\s+berhasil',
        re.IGNORECASE
    )

    def can_handle(self, lines: List[str], raw_text: str) -> float:
        """Return confidence (0-1) that this parser can handle the receipt."""
        text_low = raw_text.lower()
        score = 0.0
        if 'transaksi berhasil' in text_low:
            score += 0.4
        if 'sumber dana' in text_low:
            score += 0.3
        if 'tujuan' in text_low:
            score += 0.2
        if re.search(r'bank\s*bri', text_low):
            score += 0.1
        return min(score, 1.0)

    def parse(self, lines: List[str], raw_text: str) -> ParsedReceipt:
        result = ParsedReceipt(bank_format="BRI_Mobile")

        sp = StructuralParser()
        structural = sp.parse(lines, raw_text)

        # Transfer structural results first
        result.nominal = structural.nominal
        result.tanggal = structural.tanggal
        result.waktu = structural.waktu
        result.nomor_referensi = structural.nomor_referensi
        result.jenis_transaksi = structural.jenis_transaksi
        result.catatan = structural.catatan
        result.nama_pengirim = structural.nama_pengirim
        result.bank_pengirim = structural.bank_pengirim
        result.rekening_pengirim = structural.rekening_pengirim
        result.nama_penerima = structural.nama_penerima
        result.bank_tujuan = structural.bank_tujuan
        result.rekening_tujuan = structural.rekening_tujuan

        # BRI-specific: fix account numbers
        # The masked sender account "5857 **** **** 532" is valid — keep it
        # The recipient account "2180 0100 0867 569" should be 16 digits
        if result.rekening_tujuan:
            clean = re.sub(r'\D', '', result.rekening_tujuan)
            if len(clean) >= 10:
                result.rekening_tujuan = clean

        # Trust reference number — it's a separate labeled field "No. Ref"
        # BRI ref is usually 12 digits
        if result.nomor_referensi and not getattr(sp, 'reference_locked', False) and len(re.sub(r'\D', '', result.nomor_referensi)) > 16:
            # Too long — likely got confused with account. Use only the ref label match
            m = re.search(r'No\.?\s*Ref[\s:]+(\d{8,20})', raw_text, re.IGNORECASE)
            if m:
                result.nomor_referensi = m.group(1)

        # Extra BRI fallback: Detect "Rp770.000" as nominal (large bold text)
        if result.nominal is None:
            m = re.search(r'Rp([\d.,]+)', raw_text)
            if m:
                result.nominal = _normalize_amount(m.group(1))

        result.confidence_score = 0.85 if result.nama_penerima else 0.5
        return result


class BCAMobileParser:
    """
    Parser for BCA m-Banking / mybca receipts.

    Typical structure:
        Transfer Sesama BCA Berhasil
        Ke  : 3491587171
               FAKRIZAL
        Nominal Transfer : Rp200.000
        Tanggal         : 23/12/2021
        No. Referensi   : 1234567890
    """

    def can_handle(self, lines: List[str], raw_text: str) -> float:
        text_low = raw_text.lower()
        score = 0.0
        if re.search(r'bca|myb?ca', text_low):
            score += 0.4
        if re.search(r'nominal\s+transfer', text_low):
            score += 0.3
        if re.search(r'no\.?\s*referensi|nomor\s+referensi', text_low):
            score += 0.2
        if re.search(r'm-transfer|mybca', text_low):
            score += 0.1
        return min(score, 1.0)

    def parse(self, lines: List[str], raw_text: str) -> ParsedReceipt:
        result = ParsedReceipt(bank_format="BCA_Mobile")
        sp = StructuralParser()

        for i, line in enumerate(lines):
            low = line.lower().strip()

            # Recipient: "Ke  : 3491587171" then next line is name
            if re.match(r'^ke\s*:', low):
                m = re.search(r':\s*(\d[\d\s\-]{6,})', line)
                if m:
                    result.rekening_tujuan = re.sub(r'\D', '', m.group(1))
                # Name is typically next line
                if i + 1 < len(lines):
                    name = _clean_name(lines[i+1].strip())
                    if name and not re.search(r'\d{5,}', name):
                        result.nama_penerima = name

            # Nominal: "Nominal Transfer : Rp200.000"
            if 'nominal' in low:
                amt = sp.extract_amount_from_line(line)
                if amt and result.nominal is None:
                    result.nominal = amt

            # Date: "Tanggal : 23/12/2021"
            if re.search(r'tanggal|tgl', low):
                dt = sp.extract_date_from_line(line)
                if dt and result.tanggal is None:
                    result.tanggal = dt

            # Reference: "No. Referensi : 1234567890"
            if re.search(r'no\.?\s*ref|referensi', low):
                m = re.search(r':\s*(\w{5,25})', line)
                if m and result.nomor_referensi is None:
                    result.nomor_referensi = m.group(1).strip()

        # BCA sender bank
        if result.bank_pengirim is None:
            result.bank_pengirim = 'BCA'
        if result.bank_tujuan is None:
            result.bank_tujuan = 'BCA'  # sesama BCA

        # Try structural for missing fields
        structural = sp.parse(lines, raw_text)
        if result.nama_penerima is None:
            result.nama_penerima = structural.nama_penerima
        if result.rekening_tujuan is None:
            result.rekening_tujuan = structural.rekening_tujuan
        if result.rekening_pengirim is None:
            result.rekening_pengirim = structural.rekening_pengirim
        if result.bank_pengirim is None:
            result.bank_pengirim = structural.bank_pengirim
        if structural.bank_tujuan and structural.rekening_tujuan:
            result.bank_tujuan = structural.bank_tujuan
        if result.nominal is None:
            result.nominal = structural.nominal
        if result.tanggal is None:
            result.tanggal = structural.tanggal
        if result.waktu is None:
            result.waktu = structural.waktu
        if result.nomor_referensi is None:
            result.nomor_referensi = structural.nomor_referensi

        result.confidence_score = 0.8
        return result


class MandiriParser:
    """
    Parser for Bank Mandiri Livin'/Online receipts.
    """

    def can_handle(self, lines: List[str], raw_text: str) -> float:
        text_low = raw_text.lower()
        score = 0.0
        if re.search(r'mandiri', text_low):
            score += 0.5
        if re.search(r'livin|internet banking mandiri', text_low):
            score += 0.3
        if re.search(r'rekening\s+tujuan|rek\.?\s*tujuan', text_low):
            score += 0.2
        return min(score, 1.0)

    def parse(self, lines: List[str], raw_text: str) -> ParsedReceipt:
        result = ParsedReceipt(bank_format="Mandiri")
        sp = StructuralParser()
        structural = sp.parse(lines, raw_text)

        # Copy structural results
        for attr in ['nominal', 'tanggal', 'waktu', 'nomor_referensi',
                     'nama_pengirim', 'bank_pengirim', 'rekening_pengirim',
                     'nama_penerima', 'bank_tujuan', 'rekening_tujuan']:
            setattr(result, attr, getattr(structural, attr))

        if result.bank_pengirim is None:
            result.bank_pengirim = 'Mandiri'

        result.confidence_score = 0.75
        return result


class EWalletParser:
    """
    Parser khusus untuk E-Wallet: GoPay, OVO, DANA, ShopeePay.
    Mencegah sistem mendeteksinya sebagai transfer bank reguler.
    """

    WALLET_PATTERNS = {
        'GoPay': r'\bgopay\b',
        'OVO': r'\bovo\b',
        'DANA': r'\bdana\b',
        'ShopeePay': r'\bshopeepay\b|\bshopee\s*pay\b',
    }

    def can_handle(self, lines: List[str], raw_text: str) -> float:
        text_low = raw_text.lower()
        score = 0.0
        for wallet, pattern in self.WALLET_PATTERNS.items():
            if re.search(pattern, text_low):
                score += 0.6
                break
        
        # Tambahan skor jika menemukan keyword e-wallet
        if re.search(r'id\s*transaksi|kirim\s*uang|saldo\s*dana|transfer\s*ke|berhasil\s*kirim', text_low):
            score += 0.3
            
        return min(score, 1.0)

    def parse(self, lines: List[str], raw_text: str) -> ParsedReceipt:
        result = ParsedReceipt(bank_format="E-Wallet")
        text_low = raw_text.lower()
        sp = StructuralParser()
        
        # 1. Platform Pengirim (E-Wallet type)
        for wallet, pattern in self.WALLET_PATTERNS.items():
            if re.search(pattern, text_low):
                result.bank_pengirim = wallet
                break

        # 2. Section-Based State Machine
        current_section = "GENERAL"
        
        for line in lines:
            low = line.lower().strip()
            
            # Deteksi transisi seksi SENDER
            m_sender = re.match(r'^(dari|sumber\s*dana|pengirim)[\s:]*(.*)', low)
            if m_sender:
                current_section = "SENDER"
                remainder = m_sender.group(2).strip()
                if not remainder:
                    continue
                # Potong label, proses sisa string di section ini
                idx = low.find(m_sender.group(1)) + len(m_sender.group(1))
                line = line[idx:].lstrip(': -')
                low = line.lower()

            # Deteksi transisi seksi RECIPIENT
            m_rec = re.match(r'^(ke|penerima|transfer\s*ke|tujuan)[\s:]*(.*)', low)
            if m_rec:
                current_section = "RECIPIENT"
                remainder = m_rec.group(2).strip()
                if not remainder:
                    continue
                idx = low.find(m_rec.group(1)) + len(m_rec.group(1))
                line = line[idx:].lstrip(': -')
                low = line.lower()
                
            # Logika per-seksi
            if current_section == "SENDER":
                # Ekstrak Rekening Pengirim (10-16 digit)
                m_acc = sp.extract_account_from_line(line)
                if m_acc and result.rekening_pengirim is None:
                    result.rekening_pengirim = m_acc
                
                # Ekstrak Nama Pengirim
                if not re.search(r'\d{5,}', line) and result.nama_pengirim is None:
                    clean_name = _clean_name(line)
                    if clean_name and not sp.detect_bank_in_line(line):
                        result.nama_pengirim = clean_name
                        
            elif current_section == "RECIPIENT":
                # Ekstrak Rekening Tujuan
                m_acc = sp.extract_account_from_line(line)
                if m_acc and result.rekening_tujuan is None:
                    result.rekening_tujuan = m_acc
                    
                # Ekstrak Nama Penerima
                if not re.search(r'\d{5,}', line) and result.nama_penerima is None:
                    clean_name = _clean_name(line)
                    if clean_name and not sp.detect_bank_in_line(line):
                        result.nama_penerima = clean_name
                        
                # Ekstrak Bank Tujuan
                if result.bank_tujuan is None:
                    detected_bank = sp.detect_bank_in_line(line)
                    if detected_bank and not re.search(r'dana|ovo|gopay|shopeepay', detected_bank.lower()):
                        result.bank_tujuan = detected_bank

        # 3. Fallback Bank Tujuan di luar section
        if result.bank_tujuan is None:
            for line in lines:
                detected_bank = sp.detect_bank_in_line(line)
                if detected_bank and not re.search(r'dana|ovo|gopay|shopeepay', detected_bank.lower()):
                    result.bank_tujuan = detected_bank
                    break

        # GLOBAL REGEX FALLBACK UNTUK REKENING TUJUAN DIHAPUS (Sesuai Aturan ke-6)
        # Menghindari rekening SENDER tertangkap sebagai RECIPIENT

        # 4. Nominal
        amt_m = re.search(r'Rp\.?\s*([\d.,]+)', raw_text, re.IGNORECASE)
        if amt_m:
            result.nominal = _normalize_amount(amt_m.group(1))

        # 5. ID Transaksi
        m = re.search(r'(?:id\s*transaksi|nomor\s*referensi|reference|ref|transaction\s*id)[\s:]+([A-Za-z0-9]{4,30})', raw_text, re.IGNORECASE)
        if m:
            cand = m.group(1).strip()
            if not re.match(r'(?i)^(rekening|rek|nama|jumlah|total|bank|dari|kepada)$', cand):
                result.nomor_referensi = cand
                
        # 6. Tanggal (Pinjam fungsionalitas StructuralParser)
        structural = sp.parse(lines, raw_text)
        result.tanggal = structural.tanggal
        result.waktu = structural.waktu

        result.confidence_score = 0.85
        return result


class BNIMobileParser:
    def can_handle(self, lines: List[str], raw_text: str) -> float:
        text_low = raw_text.lower()
        score = 0.0
        if re.search(r'bni\s*mobile', text_low):
            score += 0.5
        if re.search(r'transaksi\s+berhasil|pembayaran\s+berhasil', text_low):
            score += 0.2
        if re.search(r'bank\s+negara\s+indonesia', text_low):
            score += 0.2
        return min(score, 1.0)

    def parse(self, lines: List[str], raw_text: str) -> ParsedReceipt:
        result = ParsedReceipt(bank_format="BNI_Mobile")
        sp = StructuralParser()
        structural = sp.parse(lines, raw_text)
        for attr in ['nominal', 'tanggal', 'waktu', 'nomor_referensi', 'nama_pengirim', 'bank_pengirim', 'rekening_pengirim', 'nama_penerima', 'bank_tujuan', 'rekening_tujuan']:
            setattr(result, attr, getattr(structural, attr))
        if result.bank_pengirim is None:
            result.bank_pengirim = 'BNI'
        result.confidence_score = 0.8
        return result


class ATMBcaParser:
    def can_handle(self, lines: List[str], raw_text: str) -> float:
        text_low = raw_text.lower()
        score = 0.0
        if re.search(r'atm\s+bca', text_low):
            score += 0.5
        if re.search(r'tgl\.|no\.\s*urut|cabang:', text_low):
            score += 0.3
        if re.search(r'ke\s+bank|ke\s+rek|jumlah', text_low):
            score += 0.2
        return min(score, 1.0)

    def parse(self, lines: List[str], raw_text: str) -> ParsedReceipt:
        result = ParsedReceipt(bank_format="ATM_BCA")
        sp = StructuralParser()
        result.bank_pengirim = 'BCA'

        def line_value(index: int) -> Optional[str]:
            line = lines[index].strip()
            inline = re.search(r'[:=]\s*(.+)$', line)
            if inline and inline.group(1).strip():
                return inline.group(1).strip()

            for offset in range(1, 4):
                if index + offset >= len(lines):
                    break
                candidate = lines[index + offset].strip()
                if not candidate or candidate == ':':
                    continue
                if candidate.startswith(':'):
                    candidate = candidate[1:].strip()
                if candidate:
                    return candidate
            return None

        for i, line in enumerate(lines):
            low = line.lower().strip()

            if re.search(r'no\.?\s*urut', low):
                value = line_value(i)
                if value:
                    m = re.search(r'([A-Za-z0-9\-]{2,30})', value)
                    if m:
                        result.nomor_referensi = m.group(1).upper()

            if re.search(r'ke\s*bank', low):
                window = ' '.join(lines[i:min(i + 6, len(lines))])
                bank = sp.detect_bank_in_line(window)
                if bank:
                    result.bank_tujuan = bank
                else:
                    value = line_value(i)
                    bank = sp.detect_bank_in_line(value or '')
                    if bank:
                        result.bank_tujuan = bank

            if re.search(r'ke\s*rek\.?', low):
                value = line_value(i)
                if value:
                    digits = re.sub(r'\D', '', value)
                    if 10 <= len(digits) <= 16:
                        result.rekening_tujuan = digits

            if re.fullmatch(r'nama', low):
                value = line_value(i)
                if value:
                    name = _clean_name(value)
                    if name and not sp.detect_bank_in_line(name) and not re.search(r'\d{3,}', name):
                        result.nama_penerima = name

            if re.fullmatch(r'jumlah', low) or re.search(r'\bjumlah\b', low):
                amount_source = ' '.join(lines[i:min(i + 5, len(lines))])
                amount = sp.extract_amount_from_line(amount_source)
                if amount:
                    result.nominal = amount

        date_match = re.search(r'\b(\d{1,2})/(\d{1,2})/(\d{2,4})\b', raw_text)
        if date_match:
            result.tanggal = _normalize_date(date_match.group(0))

        time_match = re.search(r'\b(\d{1,2}[:.]\d{2}[:.]\d{2})\b', raw_text)
        if time_match:
            result.waktu = sp.extract_time_from_line(time_match.group(1))

        if result.nominal is None or result.rekening_tujuan is None or result.bank_tujuan is None:
            structural = sp.parse(lines, raw_text)
            if result.nominal is None:
                result.nominal = structural.nominal
            if result.tanggal is None:
                result.tanggal = structural.tanggal
            if result.waktu is None:
                result.waktu = structural.waktu
            if result.bank_tujuan is None:
                result.bank_tujuan = structural.bank_tujuan
            if result.rekening_tujuan is None:
                result.rekening_tujuan = structural.rekening_tujuan
            if result.nama_penerima is None:
                result.nama_penerima = structural.nama_penerima

        result.confidence_score = 0.9 if result.rekening_tujuan and result.nominal else 0.75
        return result


class ATMBriParser:
    def can_handle(self, lines: List[str], raw_text: str) -> float:
        text_low = raw_text.lower()
        score = 0.0
        if re.search(r'atm\s+(bank\s+)?bri', text_low):
            score += 0.5
        if re.search(r'lokasi:|no\.\s*record|harap\s+simpan\s+resi', text_low):
            score += 0.3
        return min(score, 1.0)

    def parse(self, lines: List[str], raw_text: str) -> ParsedReceipt:
        result = ParsedReceipt(bank_format="ATM_BRI")
        sp = StructuralParser()
        structural = sp.parse(lines, raw_text)
        for attr in ['nominal', 'tanggal', 'waktu', 'nomor_referensi', 'nama_pengirim', 'bank_pengirim', 'rekening_pengirim', 'nama_penerima', 'bank_tujuan', 'rekening_tujuan']:
            setattr(result, attr, getattr(structural, attr))
        if result.bank_pengirim is None:
            result.bank_pengirim = 'BRI'
        result.confidence_score = 0.75
        return result


class ATMBniParser:
    def can_handle(self, lines: List[str], raw_text: str) -> float:
        text_low = raw_text.lower()
        score = 0.0
        if re.search(r'atm\s+bni', text_low):
            score += 0.5
        if re.search(r'struk\s+ini\s+adalah\s+bukti|terminal\s+id', text_low):
            score += 0.3
        return min(score, 1.0)

    def parse(self, lines: List[str], raw_text: str) -> ParsedReceipt:
        result = ParsedReceipt(bank_format="ATM_BNI")
        sp = StructuralParser()
        structural = sp.parse(lines, raw_text)
        for attr in ['nominal', 'tanggal', 'waktu', 'nomor_referensi', 'nama_pengirim', 'bank_pengirim', 'rekening_pengirim', 'nama_penerima', 'bank_tujuan', 'rekening_tujuan']:
            setattr(result, attr, getattr(structural, attr))
        if result.bank_pengirim is None:
            result.bank_pengirim = 'BNI'
        result.confidence_score = 0.75
        return result


class SeaBankParser:
    def can_handle(self, lines: List[str], raw_text: str) -> float:
        text_low = raw_text.lower()
        score = 0.0
        if re.search(r'seabank', text_low):
            score += 0.5
        if re.search(r'transfer\s+berhasil', text_low):
            score += 0.2
        if re.search(r'biaya\s+transfer', text_low):
            score += 0.1
        return min(score, 1.0)

    def parse(self, lines: List[str], raw_text: str) -> ParsedReceipt:
        result = ParsedReceipt(bank_format="SeaBank")
        sp = StructuralParser()
        structural = sp.parse(lines, raw_text)
        for attr in ['nominal', 'tanggal', 'waktu', 'nomor_referensi', 'nama_pengirim', 'bank_pengirim', 'rekening_pengirim', 'nama_penerima', 'bank_tujuan', 'rekening_tujuan']:
            setattr(result, attr, getattr(structural, attr))
        if result.bank_pengirim is None:
            result.bank_pengirim = 'SeaBank'
        result.confidence_score = 0.75
        return result


class JagoParser:
    def can_handle(self, lines: List[str], raw_text: str) -> float:
        text_low = raw_text.lower()
        score = 0.0
        if re.search(r'jago', text_low):
            score += 0.5
        if re.search(r'kirim\s+uang\s+berhasil|kantong', text_low):
            score += 0.3
        return min(score, 1.0)

    def parse(self, lines: List[str], raw_text: str) -> ParsedReceipt:
        result = ParsedReceipt(bank_format="Jago")
        sp = StructuralParser()
        structural = sp.parse(lines, raw_text)
        for attr in ['nominal', 'tanggal', 'waktu', 'nomor_referensi', 'nama_pengirim', 'bank_pengirim', 'rekening_pengirim', 'nama_penerima', 'bank_tujuan', 'rekening_tujuan']:
            setattr(result, attr, getattr(structural, attr))
        if result.bank_pengirim is None:
            result.bank_pengirim = 'Jago'
        result.confidence_score = 0.75
        return result


# ─── Main BankReceiptParser (orchestrator) ────────────────────────────────────

class BankReceiptParser:
    """
    Orchestrator that selects the best bank-specific parser
    and falls back to structural parsing.
    """

    def __init__(self):
        self.parsers = [
            BRIMobileParser(),
            BCAMobileParser(),
            MandiriParser(),
            BNIMobileParser(),
            ATMBcaParser(),
            ATMBriParser(),
            ATMBniParser(),
            SeaBankParser(),
            JagoParser(),
            EWalletParser(),
        ]
        self.structural_parser = StructuralParser()

    def parse(self, raw_text: str, detections: List[Dict] = None) -> ParsedReceipt:
        """
        Parse a receipt using the best matching parser.
        
        Args:
            raw_text: Raw OCR text output
            detections: List of detection dicts with 'text' and 'box'
        
        Returns:
            ParsedReceipt with all extracted fields
        """
        # Build sorted line list from detections or raw text
        if detections:
            # Do not re-sort by Y. PaddleOCR's native order is usually more logical
            # than a strict average Y-coordinate sort, especially when labels and values
            # are slightly misaligned vertically but logically sequential.
            lines = [d['text'].strip() for d in detections if d.get('text', '').strip()]
        else:
            lines = [ln.strip() for ln in raw_text.splitlines() if ln.strip()]

        # Score each parser
        scores = []
        for parser in self.parsers:
            score = parser.can_handle(lines, raw_text)
            scores.append((score, parser))
            logger.debug(f"Parser {type(parser).__name__}: score={score:.2f}")

        best_score, best_parser = max(scores, key=lambda x: x[0])

        if best_score >= 0.3:
            logger.info(f"Using parser: {type(best_parser).__name__} (score={best_score:.2f})")
            result = best_parser.parse(lines, raw_text)
        else:
            logger.info("No specialized parser matched, using structural parser")
            result = self.structural_parser.parse(lines, raw_text)
            result.bank_format = "generic"

        # Post-process: validate and clean fields
        self._post_process(result, raw_text)

        logger.info(f"Parsed receipt: format={result.bank_format}, "
                    f"nominal={result.nominal}, ref={result.nomor_referensi}, "
                    f"penerima={result.nama_penerima}")

        return result

    def _post_process(self, result: ParsedReceipt, raw_text: str):
        """
        Final validation and cleanup of parsed result.
        """
        sp = StructuralParser()

        # Validate and clean amount
        if result.nominal:
            cleaned = _normalize_amount(result.nominal)
            result.nominal = cleaned

        # Validate date
        if result.tanggal:
            cleaned = _normalize_date(result.tanggal)
            result.tanggal = cleaned

        # Combine date + time into ISO datetime string
        if result.tanggal and result.waktu:
            result.tanggal = f"{result.tanggal}T{result.waktu}"
        elif result.tanggal:
            result.tanggal = f"{result.tanggal}T00:00:00"

        # Clean account numbers
        if result.rekening_tujuan:
            if not _is_masked_account(result.rekening_tujuan):
                result.rekening_tujuan = re.sub(r'\D', '', result.rekening_tujuan)
        if result.rekening_pengirim:
            # Keep masked format for display
            pass

        # Guard: if reference number looks like an account number (same as rekening_tujuan)
        if (result.nomor_referensi and result.rekening_tujuan
                and re.sub(r'\D', '', result.nomor_referensi) ==
                re.sub(r'\D', '', result.rekening_tujuan)):
            # ATURAN: Jangan pernah menggunakan rekening sebagai fallback referensi.
            result.nomor_referensi = None

        # Ensure bank names are standardized
        bank_normalize = {
            'BANK BRI': 'BRI',
            'BRI': 'BRI',
            'BANK RAKYAT INDONESIA': 'BRI',
            'BANK BCA': 'BCA',
            'BCA': 'BCA',
            'BANK CENTRAL ASIA': 'BCA',
            'BANK MANDIRI': 'Mandiri',
            'MANDIRI': 'Mandiri',
            'BANK BNI': 'BNI',
            'BNI': 'BNI',
            'BANK NEGARA INDONESIA': 'BNI',
            'BSI': 'BSI',
            'BANK SYARIAH INDONESIA': 'BSI',
            'SEABANK': 'SeaBank',
            'JAGO': 'Jago',
        }
        for attr in ['bank_pengirim', 'bank_tujuan']:
            val = getattr(result, attr)
            if val:
                upper = val.strip().upper()
                setattr(result, attr, bank_normalize.get(upper, val))

        # Clean names
        for attr in ['nama_pengirim', 'nama_penerima']:
            val = getattr(result, attr)
            if val:
                setattr(result, attr, _clean_name(val) or val)

    def to_field_extractor_format(self, result: ParsedReceipt) -> Dict[str, Any]:
        """
        Convert ParsedReceipt to the format expected by the existing
        field_extractor.py and main.py pipeline.
        """
        from datetime import datetime

        paid_at = None
        if result.tanggal:
            try:
                # Try ISO format first
                if 'T' in result.tanggal:
                    paid_at = datetime.fromisoformat(result.tanggal)
                else:
                    paid_at = datetime.strptime(result.tanggal, '%Y-%m-%d')
                if paid_at and result.waktu:
                    time_parts = [int(part) for part in result.waktu.split(':')[:3]]
                    while len(time_parts) < 3:
                        time_parts.append(0)
                    paid_at = paid_at.replace(
                        hour=time_parts[0],
                        minute=time_parts[1],
                        second=time_parts[2],
                    )
            except ValueError:
                try:
                    from dateutil import parser as dp
                    paid_at = dp.parse(f"{result.tanggal} {result.waktu or ''}".strip())
                except Exception:
                    pass

        amount = None
        if result.nominal:
            try:
                amount = float(re.sub(r'\D', '', result.nominal))
            except (ValueError, TypeError):
                pass

        return {
            'amount': amount,
            'paid_at': paid_at,
            'bank_name': result.bank_tujuan or result.bank_pengirim,
            'sender_bank': result.bank_pengirim,
            'recipient_bank': result.bank_tujuan,
            'sender_name': result.nama_pengirim,
            'recipient_name': result.nama_penerima,
            'recipient_account_no': result.rekening_tujuan,
            'sender_account_no': result.rekening_pengirim,
            'reference_no': result.nomor_referensi,
            'detected_format': result.bank_format,
            # Raw parsed receipt for debugging
            '_bank_format': result.bank_format,
            '_confidence': result.confidence_score,
        }
