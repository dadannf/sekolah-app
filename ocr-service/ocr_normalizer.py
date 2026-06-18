"""
OCR Text Normalizer Module
Responsible for fixing common OCR typos before parsing.
"""

import re

class OcrNormalizer:
    @classmethod
    def normalize(cls, text: str) -> str:
        if not text:
            return text
            
        text = str(text).strip()
        
        # 1. Normalisasi Bank
        text = cls._normalize_banks(text)
        
        # 2. Normalisasi Mata Uang (RP)
        text = cls._normalize_currency(text)
        
        # 3. Normalisasi Kesalahan Tanggal
        text = cls._normalize_date(text)
        
        # 4. Normalisasi Angka murni & Referensi (Typos O->0, S->5 dll)
        text = cls._normalize_numbers(text)
        
        # 5. Normalisasi Alias Sekolah (SMK BIT BINA AULIA)
        text = cls._normalize_school_name(text)
        
        # 6. Normalisasi Spasi Berlebih
        text = re.sub(r'\s+', ' ', text).strip()
        
        return text
        
    @classmethod
    def _normalize_banks(cls, text: str) -> str:
        # Tambahkan spasi di antara kata BANK dan nama bank jika tergabung
        banks = ['BCA', 'BRI', 'BNI', 'BTN', 'MANDIRI', 'DANAMON', 'MEGA', 'SYARIAH']
        for b in banks:
            # BANKBCA -> BANK BCA
            text = re.sub(r'(?i)\b(BANK)(' + b + r')\b', r'\1 \2', text)
            # BANK BCA -> BANK BCA (already handled if correctly spaced)
        return text

    @classmethod
    def _normalize_currency(cls, text: str) -> str:
        # Typo OCR pada awalan Rp
        # RPS, RPF, RP.S, RF, RR -> RP
        # Kita menggunakan re.IGNORECASE untuk menangkap variasi huruf besar/kecil
        
        # RPS / RPF / RP.S -> Rp
        text = re.sub(r'(?i)\b(?:RP[\.\s]*[SF]|RF|RPS)\b', 'Rp', text)
        
        # RP. -> Rp (menghapus titik agar seragam)
        text = re.sub(r'(?i)\bRP\s*\.', 'Rp ', text)
        
        return text
        
    @classmethod
    def _normalize_date(cls, text: str) -> str:
        # Seringkali angka 2 terbaca 1 pada tahun, e.g. 1023 -> 2023, 1024 -> 2024
        # Pola DD/MM/YYYY atau DD-MM-YYYY
        
        def fix_year(match):
            year = match.group(2)
            if year.startswith('102') or year.startswith('101'): # 1023, 1019
                year = '2' + year[1:]
            return f"{match.group(1)}{year}"
            
        # Mencari pola separator / atau - lalu 4 digit angka
        text = re.sub(r'([/-]\s*)(10[12]\d)\b', fix_year, text)
        
        # Normalisasi singkatan bulan bahasa indonesia
        text = re.sub(r'(?i)\b(JAN|PEB|FEB|MAR|APR|MEI|JUN|JUL|AGU|AGT|SEP|OKT|NOV|DES)\b', lambda m: m.group(1).capitalize(), text)
        
        return text

    @classmethod
    def _normalize_numbers(cls, text: str) -> str:
        # Jika teks dominan angka (>70% angka atau setidaknya block panjang), kita perbaiki typo huruf
        # Namun hati-hati jangan sampai mengubah nama "DANO" jadi "DAN0"
        
        # Pertama, perbaiki nominal uang yang menggunakan koma atau titik. 
        # Misal 1OO.OOO -> 100.000
        # Mencari sekumpulan angka dan koma/titik yang mengandung huruf 'O' atau 'S' atau 'l'
        
        def fix_number_block(match):
            block = match.group(0)
            # Hanya ganti jika blok ini dominan seperti angka/uang/referensi
            block = block.replace('O', '0').replace('o', '0')
            block = block.replace('l', '1').replace('I', '1')
            block = block.replace('S', '5').replace('s', '5')
            block = block.replace('B', '8')
            return block

        # Pola angka bercampur typo huruf khusus untuk Nominal: e.g. "1O.OOO", "15O,OOO", "Rp 1S.OOO"
        text = re.sub(r'(?i)(?<=Rp\s)\s*[0-9OSIBl\.,]+', fix_number_block, text)
        text = re.sub(r'(?i)(?<=Rp)\s*[0-9OSIBl\.,]+', fix_number_block, text)
        
        # Pola Nomor Rekening (panjang 10-16 tanpa titik koma)
        # Jika ada string panjang yang sebagian besar angka tapi terselip huruf
        # Misal: "123S456O78"
        def fix_account_block(match):
            cand = match.group(0)
            # Hitung jumlah angka riil
            digits = sum(c.isdigit() for c in cand)
            if digits >= 5 and len(cand) >= 10:
                return cand.replace('O', '0').replace('o', '0')\
                           .replace('l', '1').replace('I', '1')\
                           .replace('S', '5').replace('s', '5')\
                           .replace('B', '8')
            return cand
            
        text = re.sub(r'\b[0-9A-Za-z]{10,25}\b', fix_account_block, text)
        
        # Normalisasi spasi di dalam nomor referensi 
        # Kadang OCR memberi spasi: "123 456 789" -> "123456789" jika di dekat kata "REF"
        # Kita tidak lakukan ini secara agresif agar nama tidak hilang spasinya, hanya jika yakin itu blok angka.
        
        return text

    @classmethod
    def _normalize_school_name(cls, text: str) -> str:
        text_upper = text.upper()
        
        if not re.search(r'\b(SMK|BIT|BINA|AULIA)\b', text_upper):
            return text
            
        # Pola kombinasi yang umum salah terbaca oleh OCR
        # Menggunakan lookbehind/lookahead untuk mencegah duplikasi jika sudah benar "SMK BIT BINA AULIA"
        patterns = [
            r'(?i)\bSMK\s+BIT\s+AULIA\b',
            r'(?i)\bSMK\s+BIT\b(?!\s+BINA\s+AULIA)',
            r'(?i)(?<!SMK\s)\bBIT\s+BINA\s+AULIA\b',
            r'(?i)\bSMK\s+BINA\s+AULIA\b',
            r'(?i)(?<!SMK\s)\bBIT\s+AULIA\b'
        ]
        
        for p in patterns:
            if re.search(p, text):
                text = re.sub(p, 'SMK BIT BINA AULIA', text)
                
        # Handle trailing double SMKs if it accidentally happened
        text = re.sub(r'(?i)\bSMK\s+SMK\b', 'SMK', text)
        text = re.sub(r'(?i)\bSMK\s+BIT\s+BINA\s+AULIA\s+BINA\s+AULIA\b', 'SMK BIT BINA AULIA', text)
                
        return text

