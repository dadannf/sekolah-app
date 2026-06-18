import re
import logging
from typing import List, Dict, Optional

logger = logging.getLogger(__name__)

class PriorityNumberEngine:
    """
    Sistem lelang prioritas (Scoring) untuk mendistribusikan angka 
    ke field yang tepat berdasarkan konteks, menghilangkan konflik 
    antara rekening, referensi, dan nominal.
    """
    
    def __init__(self):
        # 1. Definisi Kamus Konteks (Label vs Target) - PRECOMPILED
        self.LABELS = {
            'NOMINAL': re.compile(r'\b(?:total\s+transaksi|total\s+bayar|total|nominal|jumlah\s+transfer|amount|rp|jumlah)\b', re.IGNORECASE),
            'REKENING_PENERIMA': re.compile(r'\b(?:rek(?:ening)?\s*tujuan|ke\s*rek|account\s*no|rekening\s*penerima|ke|tujuan)\b', re.IGNORECASE),
            'REKENING_PENGIRIM': re.compile(r'\b(?:rek(?:ening)?\s*asal|dari\s*rek|sumber\s*dana|rekening\s*pengirim)\b', re.IGNORECASE),
            'NOMOR_REFERENSI': re.compile(r'(?:\bid\s*transaksi\b|\bno\.?\s*ref(?:erensi)?\b|\breference\b|\btransaction\s*id\b|\bref\b)', re.IGNORECASE),
        }

    def process(self, lines: List[str]) -> Dict[str, Optional[str]]:
        """
        Mengeksekusi analisis pada baris teks dan mengembalikan distribusi angka.
        """
        # 2. Panen Kandidat Angka
        candidates = []
        for i, ln in enumerate(lines):
            # Mencari token yang mengandung string alphanumerik/angka
            # seperti "03202606070325100" atau "Rp150.000" atau "2180 0100 0867 569"
            
            # Kita gunakan regex kasar untuk memisahkan token per baris
            # Kemudian saring token yang memiliki > 3 digit
            tokens = re.split(r'\s{2,}|\t|(?<=:)\s*', ln) 
            for token in tokens:
                val = token.strip()
                digits_only = re.sub(r'\D', '', val)
                if len(digits_only) >= 3:
                    candidates.append({
                        'raw': val,
                        'digits': digits_only,
                        'line_idx': i,
                        'line_text': ln
                    })

        # --- Multi-Line Account Reconstruction (Penjahit Angka) ---
        stitched_candidates = []
        skip_indices = set()
        
        for idx in range(len(candidates)):
            if idx in skip_indices:
                continue
                
            curr = candidates[idx]
            curr_digits = curr['digits']
            
            # Coba menjahit dengan angka di baris-baris berikutnya
            j = idx + 1
            stitched_raw = curr['raw']
            stitched_digits = curr_digits
            last_line_idx = curr['line_idx']
            
            while j < len(candidates):
                next_cand = candidates[j]
                
                # Aturan Penggabungan:
                # 1. Baris harus berurutan (selisih line_idx == 1)
                # 2. Baris berikutnya didominasi angka (tidak ada huruf >= 3 karakter)
                if next_cand['line_idx'] == last_line_idx + 1 and not re.search(r'[A-Za-z]{3,}', next_cand['raw']):
                    proposed_len = len(stitched_digits) + len(next_cand['digits'])
                    
                    # 3. Total digit hasil gabungan maksimal rentang rekening/referensi (<= 20 digit)
                    if proposed_len <= 20:
                        stitched_raw += next_cand['raw']
                        stitched_digits += next_cand['digits']
                        last_line_idx = next_cand['line_idx']
                        skip_indices.add(j)
                        j += 1
                    else:
                        break # Mentok jika kepanjangan
                else:
                    break # Bukan baris berurutan atau mengandung kata
                    
            # Jika berhasil dijahit dan sesuai standar rekening (10-16 digit)
            if j > idx + 1 and 10 <= len(stitched_digits) <= 16:
                stitched_candidates.append({
                    'raw': stitched_raw,
                    'digits': stitched_digits,
                    'line_idx': curr['line_idx'],  # Warisi posisi label teratas
                    'line_text': curr['line_text']
                })
            else:
                # Kembalikan ke asalnya jika tidak dijahit atau sisa
                stitched_candidates.append(curr)

        candidates = stitched_candidates

        # 3. Hitung Skor (Scoring/Lelang) dengan Block-Based Parsing (State-Machine)
        scores = {k: {} for k in self.LABELS.keys()}
        
        # A. Membangun Payung Konteks (Zona Blok)
        line_blocks = {}
        active_fields = []
        blocks_age = 0
        
        for i, ln in enumerate(lines):
            triggered_fields = []
            for field, pattern in self.LABELS.items():
                if pattern.search(ln):
                    triggered_fields.append(field)
            
            if triggered_fields:
                active_fields = triggered_fields
                blocks_age = 0
            else:
                blocks_age += 1
                
            # Umur payung konteks dibatasi (maks 8 baris) untuk menghindari kebocoran blok
            if blocks_age > 8:
                active_fields = []
                
            line_blocks[i] = active_fields.copy()

        # B. Mengevaluasi Kandidat Berdasarkan Blok
        for c_idx, cand in enumerate(candidates):
            c_line_idx = cand['line_idx']
            c_line_text = cand['line_text']
            
            for field, pattern in self.LABELS.items():
                score = 0
                
                # Aturan 1: INLINE MATCH (Paling Kuat) - Angka ada di satu baris dengan label
                if pattern.search(c_line_text):
                    score += 10
                    context_text = ' '.join(lines[max(0, c_line_idx - 2):c_line_idx + 1])
                    if field == 'NOMINAL' and re.search(r'\btotal(?:\s+transaksi|\s+bayar)?\b', context_text, re.IGNORECASE):
                        score += 6
                
                # Aturan 2: BLOCK MATCH (Kuat) - Angka bernaung di bawah zona payung label ini
                if field in line_blocks.get(c_line_idx, []):
                    if score == 0:  # Jika tidak dapat inline, beri poin blok
                        # Poin blok sedikit menurun berdasarkan seberapa jauh dari label (opsional, kita pakai flat 5 untuk keamanan)
                        score += 5
                        context_text = ' '.join(lines[max(0, c_line_idx - 2):c_line_idx + 1])
                        if field == 'NOMINAL' and re.search(r'\btotal(?:\s+transaksi|\s+bayar)?\b', context_text, re.IGNORECASE):
                            score += 6

                # Masukkan ke bursa lelang jika ada skor
                if score > 0:
                    scores[field][c_idx] = score

        # 4. Distribusi Pemenang
        results = {
            'NOMINAL': None,
            'REKENING_PENERIMA': None,
            'REKENING_PENGIRIM': None,
            'NOMOR_REFERENSI': None
        }

        claimed_indices = set()
        rankings = []
        
        for field, cand_scores in scores.items():
            for c_idx, score in cand_scores.items():
                # Ranking format: (skor_tertinggi, urutan_awal, field, kandidat_idx)
                # urutan_awal untuk memprioritaskan yang muncul lebih dulu di struk jika skor seri
                rankings.append((score, -candidates[c_idx]['line_idx'], field, c_idx))
                
        # Urutkan dari skor tertinggi
        rankings.sort(key=lambda x: (x[0], x[1]), reverse=True)

        for score, _, field, c_idx in rankings:
            if c_idx not in claimed_indices and results[field] is None:
                cand = candidates[c_idx]
                raw_val = cand['raw']
                
                # 5. Normalisasi Tipe Data Field
                if field == 'NOMINAL':
                    # Ekstrak angka nominal Rp...
                    m = re.search(r'([\d.,]+)', raw_val)
                    if m:
                        amount_token = m.group(1)
                        if re.match(r'^\d{1,3}[.,]00$', amount_token):
                            clean_val = str(int(amount_token[:-3]) * 1000)
                        else:
                            clean_val = re.sub(r'[.,]', '', amount_token)
                        if clean_val.isdigit():
                            results[field] = clean_val
                            claimed_indices.add(c_idx)
                            logger.debug(f"[PriorityEngine] {field} memenangkan kandidat: {clean_val} (Skor: {score})")
                            
                elif field == 'NOMOR_REFERENSI':
                    # Ekstrak alphanumeric murni
                    clean_val = re.sub(r'[^A-Za-z0-9]', '', raw_val)
                    if clean_val and len(clean_val) >= 4:
                        # Mencegah tabrakan dengan tulisan ber-angka seperti "REK1"
                        if not re.match(r'^[A-Za-z]+$', clean_val):
                            results[field] = clean_val
                            claimed_indices.add(c_idx)
                            logger.debug(f"[PriorityEngine] {field} memenangkan kandidat: {clean_val} (Skor: {score})")
                            
                else: # REKENING (PENGIRIM/PENERIMA)
                    # Hanya ambil digit murni
                    clean_val = cand['digits']
                    if 10 <= len(clean_val) <= 16:
                        results[field] = clean_val
                        claimed_indices.add(c_idx)
                        logger.debug(f"[PriorityEngine] {field} memenangkan kandidat: {clean_val} (Skor: {score})")

        return results
