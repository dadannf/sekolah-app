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
        if len(parts) == 2 and len(parts[1]) == 2 and parts[1].isdigit():
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
        'sumber dana', 'sumberdana', 'rekening sumber', 'rekeningsumber', 'dari rekening', 'dari', 'pengirim',
        'rekening asal', 'asal', 'from', 'sender'
    ]

    # Section headers that indicate we're now in the RECIPIENT section
    RECIPIENT_HEADERS = [
        'tujuan', 'rekening tujuan', 'rekeningtujuan', 'ke rekening', 'ke', 'penerima',
        'nama tujuan', 'namatujuan', 'bank tujuan', 'banktujuan', 'rekening penerima', 'to', 'recipient', 'destination'
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
        # Match Rp patterns
        m = re.search(r'(?:Rp\.?\s*)([\d.,]+)', line, re.IGNORECASE)
        if m:
            return _normalize_amount(m.group(1))
        # Match plain numbers (at least 5 digits, e.g. 770000)
        m = re.search(r'\b(\d{1,3}(?:[.,]\d{3})+)\b', line)
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
        m = re.search(r'(\d{1,2}):(\d{2})(?::(\d{2}))?(?:\s*WIB|WIT|WITA)?', line)
        if m:
            h, mn = m.group(1), m.group(2)
            s = m.group(3) or '00'
            return f"{h.zfill(2)}:{mn}:{s}"
        return None

    def extract_account_from_line(self, line: str) -> Optional[str]:
        """
        Extract account number from line. 
        Handles masked formats: "5857 **** **** 532"
        """
        # Check for masked format first: digits *** digits
        m = re.search(r'(\d{3,6}[\s\*•X]+\d{2,6})', line)
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
        print("DEBUG: lines list inside StructuralParser.parse:")
        for idx, ln in enumerate(lines):
            print(f"{idx}: {ln}")
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
            is_sender_header = any(
                re.match(rf'^{re.escape(h)}[\s:]*$', line_low)
                for h in self.SENDER_HEADERS
            ) or any(h == line_low for h in self.SENDER_HEADERS)

            is_recipient_header = any(
                re.match(rf'^{re.escape(h)}[\s:]*$', line_low)
                for h in self.RECIPIENT_HEADERS
            ) or any(h == line_low for h in self.RECIPIENT_HEADERS)

            # "Transfer Bank BRI" or "Jenis Transaksi" lines
            is_transaction_header = bool(re.search(r'jenis\s+transaksi|keterangan|catatan', line_low))

            if is_sender_header:
                section = 'sender'
                i += 1
                continue
            elif is_recipient_header:
                section = 'recipient'
                i += 1
                continue
            elif is_transaction_header:
                section = 'transaction'
                i += 1
                continue

            # ── Labeled field extraction (key: value patterns) ────────────
            # Handle "No. Ref    155741545916" or "Nominal    Rp770.000"
            if self._try_extract_labeled(line, result):
                i += 1
                continue

            # ── Section-aware extraction ──────────────────────────────────
            if section == 'sender':
                self._update_data_from_line(line, sender_data)
            elif section == 'recipient':
                print(f"DEBUG: Section RECIPIENT evaluating line: {line}")
                self._update_data_from_line(line, recipient_data)
                print(f"DEBUG: recipient_data state: {recipient_data}")

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

        # Reference number: "No. Ref   155741545916"
        if re.search(r'no\.?\s*ref(?:erensi)?|nomor\s*referensi', line_low):
            m = re.search(r'[\s:]+(\w{6,25})$', line)
            if m and result.nomor_referensi is None:
                result.nomor_referensi = m.group(1).strip()
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

        # Name heuristic: mostly letters, 2-50 chars, no digits run > 3
        if (re.search(r'[A-Za-z]', line_stripped)
                and not re.search(r'\d{4,}', line_stripped)
                and len(line_stripped) >= 2
                and len(line_stripped) <= 50):
            # Skip lines that are bank names or transaction keywords
            skip_words = {
                'transfer', 'berhasil', 'transaksi', 'informasi',
                'keterangan', 'catatan', 'biaya', 'admin',
                'nominal', 'total', 'jumlah', 'pembayaran', 'lihat', 'detail'
            }
            low = line_stripped.lower()
            if not any(w in low for w in skip_words):
                if data['name'] is None:
                    data['name'] = line_stripped

    def _apply_full_text_fallbacks(self, raw_text: str, result: ParsedReceipt):
        """Apply fallback extractions on full text for missing fields."""
        text_no_nl = re.sub(r'\s+', ' ', raw_text)

        # Nominal fallback
        if result.nominal is None:
            m = re.search(r'(?:Total\s+Transaksi|Nominal|Jumlah)[^\d]*([\d.,]{5,})', raw_text, re.IGNORECASE)
            if m:
                result.nominal = _normalize_amount(m.group(1))

        # Reference number fallback  
        if result.nomor_referensi is None:
            m = re.search(r'(?:No\.?\s*Ref|Referensi|ID\s+Transaksi)[\s:]+(\w{6,25})', raw_text, re.IGNORECASE)
            if m:
                result.nomor_referensi = m.group(1).strip()
            else:
                # Try pure long digits as ref (12+ digits)
                m = re.search(r'\b(\d{12,20})\b', raw_text)
                if m and result.nomor_referensi is None:
                    result.nomor_referensi = m.group(1)

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
        if result.nomor_referensi and len(re.sub(r'\D', '', result.nomor_referensi)) > 16:
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
        if result.nominal is None:
            result.nominal = structural.nominal
        if result.tanggal is None:
            result.tanggal = structural.tanggal

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
    Parser for e-wallet receipts: DANA, OVO, GoPay, ShopeePay.

    DANA format:
        Kirim Uang
        Dari Ria Suprihatin DANA: **********4071
        Ke Daniel Setya Dharma SeaBank: ****4360
        Rp 200.000
        23 Des 2021 • 13:09
        ID Transaksi    3019...
    """

    WALLET_PATTERNS = {
        'DANA': r'\bDANA\b',
        'OVO': r'\bOVO\b',
        'GoPay': r'\bGOPAY\b',
        'ShopeePay': r'\bSHOPEEPAY\b|\bSHOPEE\s+PAY\b',
        'LinkAja': r'\bLINKAJA\b',
    }

    def can_handle(self, lines: List[str], raw_text: str) -> float:
        text_upper = raw_text.upper()
        score = 0.0
        for wallet, pattern in self.WALLET_PATTERNS.items():
            if re.search(pattern, text_upper):
                score += 0.5
                break
        if re.search(r'ID\s+TRANSAKSI|KIRIM\s+UANG|SALDO\s+DANA', text_upper):
            score += 0.3
        return min(score, 1.0)

    def parse(self, lines: List[str], raw_text: str) -> ParsedReceipt:
        result = ParsedReceipt(bank_format="E-Wallet")
        sp = StructuralParser()

        text_single = re.sub(r'\s+', ' ', raw_text)

        # Detect wallet type
        for wallet, pattern in self.WALLET_PATTERNS.items():
            if re.search(pattern, raw_text.upper()):
                result.bank_pengirim = wallet
                break

        # DANA: "Dari Ria Suprihatin DANA: **********4071"
        m = re.search(
            r'(?:Dari|From)\s+([A-Za-z\s\.]+?)\s+(DANA|OVO|GOPAY|SHOPEEPAY|LINKAJA|SEABANK|BCA|MANDIRI|BRI|BNI|BSI)[\s:]+([*•\d\s]{6,25})',
            text_single, re.IGNORECASE
        )
        if m:
            result.nama_pengirim = _clean_name(m.group(1))
            result.bank_pengirim = m.group(2).upper()
            result.rekening_pengirim = m.group(3).strip()

        # DANA: "Ke Daniel Setya Dharma SeaBank: ****4360"
        m = re.search(
            r'(?:Ke|To)\s+([A-Za-z\s\.]+?)\s+(DANA|OVO|GOPAY|SHOPEEPAY|LINKAJA|SEABANK|BCA|MANDIRI|BRI|BNI|BSI)[\s:]+([*•\d\s]{4,25})',
            text_single, re.IGNORECASE
        )
        if m:
            result.nama_penerima = _clean_name(m.group(1))
            result.bank_tujuan = m.group(2).upper()
            result.rekening_tujuan = m.group(3).strip()

        # Amount: "Rp 200.000"
        amt_m = re.search(r'Rp\.?\s*([\d.,]+)', raw_text, re.IGNORECASE)
        if amt_m:
            result.nominal = _normalize_amount(amt_m.group(1))

        # Date from structural
        structural = sp.parse(lines, raw_text)
        if result.tanggal is None:
            result.tanggal = structural.tanggal
        if result.waktu is None:
            result.waktu = structural.waktu

        # ID Transaksi
        m = re.search(r'(?:ID\s+Transaksi|Transaction\s+ID)[:\s]+(\w+)', raw_text, re.IGNORECASE)
        if m:
            result.nomor_referensi = m.group(1).strip()

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
            # They can't both be the same number. Trust rekening_tujuan; clear ref
            logger.warning("Reference number equals recipient account — this likely indicates "
                           "the ref field was incorrectly detected. Keeping both, but flagging.")

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
            except ValueError:
                try:
                    from dateutil import parser as dp
                    paid_at = dp.parse(result.tanggal)
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
            # Raw parsed receipt for debugging
            '_bank_format': result.bank_format,
            '_confidence': result.confidence_score,
        }
