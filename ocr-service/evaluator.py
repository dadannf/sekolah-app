# -*- coding: utf-8 -*-
"""
OCR Evaluator - Character Error Rate (CER) & Word Error Rate (WER)

Mengimplementasikan metrik evaluasi standar untuk sistem OCR:

    CER = (S + D + I) / N × 100%
    WER = (S + D + I) / Nw × 100%

Keterangan:
    S  = jumlah substitusi (karakter/kata yang salah)
    D  = jumlah delesi    (karakter/kata yang hilang)
    I  = jumlah insersi   (karakter/kata yang ditambah)
    N  = jumlah karakter pada ground truth
    Nw = jumlah kata pada ground truth
"""

import logging
import re
from typing import Dict, Any, List

logger = logging.getLogger(__name__)


# =========================================================
# EDIT DISTANCE (Levenshtein) — digunakan untuk CER & WER
# =========================================================

def _levenshtein(seq_a: List, seq_b: List) -> int:
    """
    Hitung Levenshtein edit distance antara dua sequence.

    Menggunakan dynamic programming (DP) dengan kompleksitas O(m×n).
    Tidak membutuhkan library eksternal.

    Args:
        seq_a: Sequence referensi (ground truth sebagai list)
        seq_b: Sequence prediksi (OCR output sebagai list)

    Returns:
        Edit distance (jumlah operasi minimum: S + D + I)
    """
    m, n = len(seq_a), len(seq_b)

    # DP table: dp[i][j] = edit distance antara seq_a[:i] dan seq_b[:j]
    dp = [[0] * (n + 1) for _ in range(m + 1)]

    # Base case: transformasi string kosong
    for i in range(m + 1):
        dp[i][0] = i  # i deletions
    for j in range(n + 1):
        dp[0][j] = j  # j insertions

    # Fill DP table
    for i in range(1, m + 1):
        for j in range(1, n + 1):
            if seq_a[i - 1] == seq_b[j - 1]:
                dp[i][j] = dp[i - 1][j - 1]       # match → no cost
            else:
                dp[i][j] = 1 + min(
                    dp[i - 1][j],       # deletion
                    dp[i][j - 1],       # insertion
                    dp[i - 1][j - 1],   # substitution
                )

    return dp[m][n]


# =========================================================
# CHARACTER ERROR RATE (CER)
# =========================================================

def calculate_cer(ground_truth: str, prediction: str) -> Dict[str, Any]:
    """
    Hitung Character Error Rate (CER).

    Formula:
        CER = (S + D + I) / N × 100%

    Args:
        ground_truth: Teks referensi yang benar (ground truth)
        prediction  : Teks hasil OCR

    Returns:
        Dict dengan CER (%), edit_distance, dan jumlah karakter
    """
    # Normalisasi: hapus spasi berlebih, lowercase untuk perbandingan adil
    gt_clean   = re.sub(r'\s+', ' ', ground_truth.strip())
    pred_clean = re.sub(r'\s+', ' ', prediction.strip())

    gt_chars   = list(gt_clean)
    pred_chars = list(pred_clean)

    N = len(gt_chars)
    if N == 0:
        logger.warning("Ground truth kosong — CER tidak dapat dihitung")
        return {
            'cer_percent': 0.0,
            'edit_distance': 0,
            'total_chars_gt': 0,
            'total_chars_pred': len(pred_chars),
        }

    edit_dist = _levenshtein(gt_chars, pred_chars)
    cer = round((edit_dist / N) * 100, 2)

    logger.info(f"CER: {cer}% (edit_dist={edit_dist}, N={N})")

    return {
        'cer_percent'      : cer,
        'edit_distance'    : edit_dist,
        'total_chars_gt'   : N,
        'total_chars_pred' : len(pred_chars),
    }


# =========================================================
# WORD ERROR RATE (WER)
# =========================================================

def calculate_wer(ground_truth: str, prediction: str) -> Dict[str, Any]:
    """
    Hitung Word Error Rate (WER).

    Formula:
        WER = (S + D + I) / Nw × 100%

    Args:
        ground_truth: Teks referensi yang benar (ground truth)
        prediction  : Teks hasil OCR

    Returns:
        Dict dengan WER (%), edit_distance kata, dan jumlah kata
    """
    gt_words   = ground_truth.strip().split()
    pred_words = prediction.strip().split()

    Nw = len(gt_words)
    if Nw == 0:
        logger.warning("Ground truth kosong — WER tidak dapat dihitung")
        return {
            'wer_percent'     : 0.0,
            'edit_distance'   : 0,
            'total_words_gt'  : 0,
            'total_words_pred': len(pred_words),
        }

    edit_dist = _levenshtein(gt_words, pred_words)
    wer = round((edit_dist / Nw) * 100, 2)

    logger.info(f"WER: {wer}% (edit_dist={edit_dist}, Nw={Nw})")

    return {
        'wer_percent'     : wer,
        'edit_distance'   : edit_dist,
        'total_words_gt'  : Nw,
        'total_words_pred': len(pred_words),
    }


# =========================================================
# EVALUASI LENGKAP (CER + WER + INTERPRETASI)
# =========================================================

def evaluate_ocr(ground_truth: str, prediction: str) -> Dict[str, Any]:
    """
    Evaluasi lengkap hasil OCR: CER, WER, dan interpretasi kualitas.

    Args:
        ground_truth: Teks referensi yang benar
        prediction  : Teks hasil OCR

    Returns:
        Dict lengkap dengan CER, WER, statistik, dan rating kualitas
    """
    cer_result = calculate_cer(ground_truth, prediction)
    wer_result = calculate_wer(ground_truth, prediction)

    cer = cer_result['cer_percent']
    wer = wer_result['wer_percent']

    # Interpretasi kualitas berdasarkan CER
    if cer <= 5.0:
        quality = 'Sangat Baik'
        quality_en = 'Excellent'
    elif cer <= 15.0:
        quality = 'Baik'
        quality_en = 'Good'
    elif cer <= 30.0:
        quality = 'Cukup'
        quality_en = 'Fair'
    else:
        quality = 'Perlu Perbaikan'
        quality_en = 'Poor'

    return {
        # ── CER ──────────────────────────────────────────────────────
        'cer': {
            'percent'          : cer,
            'edit_distance'    : cer_result['edit_distance'],
            'total_chars_gt'   : cer_result['total_chars_gt'],
            'total_chars_pred' : cer_result['total_chars_pred'],
            'formula'          : 'CER = (S+D+I) / N × 100%',
        },
        # ── WER ──────────────────────────────────────────────────────
        'wer': {
            'percent'          : wer,
            'edit_distance'    : wer_result['edit_distance'],
            'total_words_gt'   : wer_result['total_words_gt'],
            'total_words_pred' : wer_result['total_words_pred'],
            'formula'          : 'WER = (S+D+I) / Nw × 100%',
        },
        # ── Kualitas ─────────────────────────────────────────────────
        'quality'   : quality,
        'quality_en': quality_en,
        'summary'   : (
            f"CER {cer}% | WER {wer}% → Kualitas: {quality}"
        ),
    }
