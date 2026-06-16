"""
PaddleOCR Processing Module with Advanced Preprocessing Pipeline
"""
import os

# Disable oneDNN/MKLDNN as early as possible (must be set before importing Paddle/PaddleOCR)
os.environ.setdefault('FLAGS_use_mkldnn', '0')
os.environ.setdefault('FLAGS_use_onednn', '0')
os.environ.setdefault('KMP_DUPLICATE_LIB_OK', 'True')
os.environ.setdefault('OMP_NUM_THREADS', '1')

from paddleocr import PaddleOCR
from config import settings
from typing import List, Tuple, Dict, Any, Optional
import numpy as np
from PIL import Image
import cv2
import logging
from pathlib import Path
import inspect

# Setup logging
logger = logging.getLogger(__name__)


class OCRProcessor:
    """PaddleOCR wrapper for text detection and recognition

    Implements preprocessing pipeline:
    - Resize dengan menjaga rasio
    - Grayscale & denoise (jika perlu)
    - Deskew / rotation correction (untuk foto miring)
    - Normalisasi nilai piksel: I'(x,y) = (I(x,y) - μ) / σ
    - Augmentasi: brightness adjustment, contrast adjustment, gaussian noise
    """
    
    def __init__(self) -> None:
        """Initialize PaddleOCR"""
        logger.info("Initializing PaddleOCR processor...")
        try:
            # Defensive: ensure flags are applied even if env was set late
            os.environ['FLAGS_use_mkldnn'] = '0'
            os.environ['FLAGS_use_onednn'] = '0'
            try:
                import paddle
                paddle.set_flags({'FLAGS_use_mkldnn': False, 'FLAGS_use_onednn': False})
            except Exception:
                pass
            
            ocr_kwargs: Dict[str, Any] = {
                'use_angle_cls': True,  # Deteksi rotasi otomatis
                'lang': settings.OCR_LANG,
                'use_gpu': settings.OCR_USE_GPU,
                'show_log': False,

                # Windows stability knobs (may not exist in newer PaddleOCR)
                'use_mp': False,  # Disable multiprocessing which can cause issues
                'enable_mkldnn': False,
                'use_mkldnn': False,
                'use_onnx': True, # Use ONNX runtime to bypass Paddle inference crash

                # === DETECTION PARAMETERS (DBNet) ===
                'det_algorithm': 'DB',
                'det_db_thresh': 0.3,           # Turunkan dari 0.5 → lebih sensitif
                'det_db_box_thresh': 0.5,       # Threshold box confidence
                'det_db_unclip_ratio': 1.5,     # Turunkan ke 1.5 agar kotak teks tidak tumpang tindih (gabung baris atas-bawah)
                'det_limit_side_len': 960,      # Max size untuk detection

                # === RECOGNITION PARAMETERS ===
                'rec_algorithm': 'SVTR_LCNet',  # Model terbaru, lebih akurat
                'rec_model_dir': 'models/rec_custom', # Menunjuk ke folder ekstrak model buatan Anda
                'rec_batch_num': 6,
                'rec_char_dict_path': 'models/en_dict.txt', # Dictionary bahasa inggris yang didownload
                'drop_score': 0.5,              # Drop hasil confidence rendah

                # === OPTIONAL: untuk gambar berkualitas rendah ===
                'use_dilation': True,           # Dilasi untuk teks tipis
                'use_space_char': True,
            }

            # PaddleOCR's init kwargs can differ by version. Filter unsupported kwargs
            # to avoid hard failures during upgrades.
            supported_params = inspect.signature(PaddleOCR.__init__).parameters
            filtered_kwargs = {k: v for k, v in ocr_kwargs.items() if k in supported_params}
            dropped = sorted(set(ocr_kwargs.keys()) - set(filtered_kwargs.keys()))
            if dropped:
                logger.debug(f"Dropping unsupported PaddleOCR kwargs: {dropped}")

            self.ocr = PaddleOCR(**filtered_kwargs)
            logger.info("PaddleOCR initialized successfully")
        except Exception as e:
            logger.error(f"Failed to initialize PaddleOCR: {e}")
            raise

    def _run_ocr(self, image_path: str):
        """Run OCR with compatibility across PaddleOCR versions.

        PaddleOCR 3.x may not accept `cls` kwarg anymore (angle classifier can be
        enabled via init). Older versions accept `cls=True`.
        """

        attempts = [
            {"cls": True},
            {"use_cls": True},
            {},
        ]
        last_exc: Optional[Exception] = None
        for kwargs in attempts:
            try:
                return self.ocr.ocr(image_path, **kwargs)
            except TypeError as e:
                # Typical: PaddleOCR.predict() got an unexpected keyword argument 'cls'
                msg = str(e)
                if "unexpected keyword argument" in msg and ("cls" in msg or "use_cls" in msg):
                    last_exc = e
                    continue
                raise
            except Exception as e:
                last_exc = e
                break
        if last_exc:
            raise last_exc
        return None

    def _parse_ocr_result(self, result) -> Tuple[str, List[Dict[str, Any]], float]:
        """Parse OCR output into (full_text, detections, avg_confidence_percent)."""

        if not result:
            return "", [], 0.0

        # PaddleOCR 3.1 (via PaddleX pipeline) returns a list of OCRResult dict-like objects
        # with keys: rec_texts, rec_scores, dt_polys, etc.
        page0 = result[0] if isinstance(result, list) and result else result
        if isinstance(page0, dict) and "rec_texts" in page0 and "rec_scores" in page0:
            texts = page0.get("rec_texts") or []
            scores = page0.get("rec_scores") or []
            polys = page0.get("dt_polys") or []

            full_text_lines: List[str] = []
            detections: List[Dict[str, Any]] = []
            confidences: List[float] = []

            for text, score, poly in zip(texts, scores, polys):
                try:
                    conf = float(score)
                except Exception:
                    continue
                if conf < settings.OCR_CONFIDENCE_THRESHOLD:
                    continue

                box = None
                if poly is not None:
                    try:
                        box = poly.tolist()
                    except Exception:
                        box = poly

                full_text_lines.append(str(text))
                confidences.append(conf)
                detections.append({
                    "text": str(text),
                    "confidence": round(conf, 4),
                    "box": box,
                })

            full_text = "\n".join(full_text_lines)
            avg_confidence = (sum(confidences) / len(confidences) * 100) if confidences else 0.0
            return full_text, detections, round(avg_confidence, 2)

        # Legacy PaddleOCR format: result[0] is a list of [box, (text, score)]
        lines = None
        if isinstance(result, list) and result and isinstance(result[0], list):
            lines = result[0]
        elif isinstance(result, list):
            lines = result
        else:
            lines = []

        full_text_lines = []
        detections = []
        confidences = []

        for line in lines:
            try:
                box = line[0]
                text, confidence = line[1]
            except Exception:
                continue

            try:
                conf = float(confidence)
            except Exception:
                continue

            if conf < settings.OCR_CONFIDENCE_THRESHOLD:
                continue

            full_text_lines.append(str(text))
            confidences.append(conf)
            detections.append({
                "text": str(text),
                "confidence": round(conf, 4),
                "box": box,
            })

        full_text = "\n".join(full_text_lines)
        avg_confidence = (sum(confidences) / len(confidences) * 100) if confidences else 0.0
        return full_text, detections, round(avg_confidence, 2)
    
    def process_image_variant(self, image_path: str, variant: str = 'original') -> Tuple[str, List[Dict[str, Any]], float]:
        """
        Process image with specific preprocessing variant.

        Variants:
            'original'  — tanpa preprocessing (langsung OCR)
            'enhanced'  — deskew + CLAHE contrast enhancement + denoise
            'upscaled'  — upscale 2x + deskew
            'augmented' — brightness adjustment + contrast adjustment + deskew
                          (augmentasi untuk gambar gelap / kontras rendah)

        Args:
            image_path: Path to input image
            variant: Preprocessing variant name

        Returns:
            Tuple of (full_text, detections, avg_confidence)
        """
        try:
            # IMPORTANT: don't re-encode the original image to JPEG.
            # JPEG compression can degrade digits/labels (e.g., account numbers).
            if variant == 'original':
                result = self._run_ocr(image_path)
                full_text, detections, avg_confidence = self._parse_ocr_result(result)
                return full_text, detections, avg_confidence

            img = cv2.imread(image_path)
            if img is None:
                raise ValueError(f"Failed to read image: {image_path}")

            # Apply variant-specific preprocessing
            if variant == 'enhanced':
                img = self.deskew_image(img)
                img = self.enhance_contrast_advanced(img)
            elif variant == 'upscaled':
                img = self.upscale_image(img, scale=2.0)
                img = self.deskew_image(img)
            elif variant == 'augmented':
                # Augmentasi: brightness → contrast → deskew
                # Berguna untuk gambar dengan pencahayaan buruk atau kontras rendah
                img = self.adjust_brightness(img, factor=1.3)
                img = self.adjust_contrast(img, alpha=1.4, beta=10)
                img = self.deskew_image(img)
                logger.debug("Augmented variant: brightness + contrast + deskew applied")
            # 'original' = no preprocessing (handled above)

            # Save temporary processed image
            temp_path = str(Path(image_path).parent / f"temp_{variant}.png")
            cv2.imwrite(temp_path, img)

            # Run OCR
            result = self._run_ocr(temp_path)

            # Clean up temp file
            Path(temp_path).unlink(missing_ok=True)

            full_text, detections, avg_confidence = self._parse_ocr_result(result)
            return full_text, detections, avg_confidence

        except Exception as e:
            logger.error(f"Error in variant '{variant}': {e}")
            return "", [], 0.0
    
    def process_image(self, image_path: str, use_preprocessing: bool = False, use_multivariant: bool = True) -> Tuple[str, List[Dict[str, Any]], float]:
        """
        Process image with Multi-variant OCR strategy:
        (1) original    — langsung OCR
        (2) enhanced    — deskew + CLAHE + denoise
        (3) upscaled    — upscale 2x + deskew
        (4) augmented   — brightness + contrast + deskew (fallback jika semua < 70%)

        Scoring formula:
            score = confidence×0.5 + min(det_count×5, 50)×0.3 + min(text_len×0.1, 50)×0.2

        Args:
            image_path: Path to input image
            use_preprocessing: Apply preprocessing pipeline (default: False)
            use_multivariant: Try multiple variants and pick best (default: True)

        Returns:
            Tuple of (full_text, detections, avg_confidence)
        """
        logger.info(f"Processing image: {image_path} (multivariant={use_multivariant})")

        try:
            if not use_multivariant:
                # Single-variant processing (fast mode)
                if use_preprocessing:
                    logger.debug("Applying preprocessing...")
                    processed_path = self.preprocess_image(image_path, fast_mode=True)
                    ocr_input = processed_path
                else:
                    ocr_input = image_path

                result = self._run_ocr(ocr_input)

                full_text, detections, avg_confidence = self._parse_ocr_result(result)
                if not full_text:
                    logger.warning("No text detected in image")
                logger.info(f"OCR completed: {len(detections)} detections, {avg_confidence}% confidence")
                return full_text, detections, avg_confidence

            # ── Multi-variant OCR strategy ────────────────────────────────────
            logger.info("Running multi-variant OCR...")
            variants = ['original', 'enhanced', 'upscaled']
            results = []

            for variant in variants:
                logger.debug(f"Processing variant: {variant}")
                text, dets, conf = self.process_image_variant(image_path, variant)

                # Fast path: jika original sudah bagus, skip varian mahal
                if variant == 'original' and text and conf >= 90.0 and len(dets) >= 12:
                    logger.info(
                        f"Original variant good enough: {len(dets)} detections, {conf}% confidence"
                    )
                    return text, dets, conf

                if text:  # Only keep non-empty results
                    results.append({
                        'variant'         : variant,
                        'text'            : text,
                        'detections'      : dets,
                        'confidence'      : conf,
                        'detection_count' : len(dets)
                    })

            # ── Varian ke-4: augmented (fallback jika semua < 70%) ───────────
            best_conf_so_far = max((r['confidence'] for r in results), default=0.0)
            if best_conf_so_far < 70.0:
                logger.info(
                    f"Best confidence so far {best_conf_so_far:.1f}% < 70% — "
                    f"trying augmented variant (brightness + contrast)..."
                )
                text, dets, conf = self.process_image_variant(image_path, 'augmented')
                if text:
                    results.append({
                        'variant'         : 'augmented',
                        'text'            : text,
                        'detections'      : dets,
                        'confidence'      : conf,
                        'detection_count' : len(dets)
                    })
                    logger.info(
                        f"Augmented variant: {len(dets)} detections, {conf:.1f}% confidence"
                    )

            if not results:
                logger.warning("All variants failed to detect text")
                return "", [], 0.0

            # ── Pilih hasil terbaik berdasarkan scoring ───────────────────────
            # Bobot: confidence 50%, jumlah deteksi 30%, panjang teks 20%
            for r in results:
                score = (
                    r['confidence'] * 0.5 +
                    min(r['detection_count'] * 5, 50) * 0.3 +
                    min(len(r['text']) * 0.1, 50) * 0.2
                )
                r['score'] = score

            # Sort by score and pick best
            best = max(results, key=lambda x: x['score'])

            logger.info(
                f"Best variant: {best['variant']} "
                f"(score: {best['score']:.2f}, "
                f"conf: {best['confidence']:.2f}%, "
                f"dets: {best['detection_count']})"
            )

            return best['text'], best['detections'], best['confidence']

        except Exception as e:
            logger.error(f"Error processing image: {e}", exc_info=True)
            return "", [], 0.0
    
    def deskew_image(self, img: np.ndarray) -> np.ndarray:
        """
        Deskew image using Hough Line Transform
        Corrects rotation for camera photos
        """
        try:
            # Convert to grayscale
            gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY) if len(img.shape) == 3 else img
            
            # Edge detection
            edges = cv2.Canny(gray, 50, 150, apertureSize=3)
            
            # Detect lines
            lines = cv2.HoughLinesP(edges, 1, np.pi/180, 100, minLineLength=100, maxLineGap=10)
            
            if lines is None:
                return img
            
            # Calculate rotation angles
            angles = []
            for line in lines:
                x1, y1, x2, y2 = line[0]
                angle = np.degrees(np.arctan2(y2 - y1, x2 - x1))
                angles.append(angle)
            
            # Get median angle
            if angles:
                median_angle = np.median(angles)
                
                # Only correct if angle is significant (> 0.5 degrees)
                if abs(median_angle) > 0.5:
                    logger.debug(f"Deskewing image by {median_angle:.2f} degrees")
                    h, w = img.shape[:2]
                    center = (w // 2, h // 2)
                    M = cv2.getRotationMatrix2D(center, median_angle, 1.0)
                    img = cv2.warpAffine(img, M, (w, h), flags=cv2.INTER_CUBIC, borderMode=cv2.BORDER_REPLICATE)
            
            return img
        except Exception as e:
            logger.warning(f"Deskew failed: {e}")
            return img
    
    def perspective_correction(self, img: np.ndarray) -> np.ndarray:
        """
        Apply perspective correction for skewed photos
        Detects document edges and corrects perspective
        """
        try:
            gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY) if len(img.shape) == 3 else img
            
            # Find edges
            edges = cv2.Canny(gray, 50, 150)
            
            # Find contours
            contours, _ = cv2.findContours(edges, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
            
            if not contours:
                return img
            
            # Get largest contour (likely document)
            largest_contour = max(contours, key=cv2.contourArea)
            
            # Approximate to quadrilateral
            epsilon = 0.02 * cv2.arcLength(largest_contour, True)
            approx = cv2.approxPolyDP(largest_contour, epsilon, True)
            
            # If we found a quadrilateral with significant area
            if len(approx) == 4:
                area = cv2.contourArea(approx)
                image_area = img.shape[0] * img.shape[1]
                
                # Only apply if contour is at least 30% of image
                if area > image_area * 0.3:
                    logger.debug("Applying perspective correction")
                    
                    # Order points: top-left, top-right, bottom-right, bottom-left
                    pts = approx.reshape(4, 2)
                    rect = np.zeros((4, 2), dtype="float32")
                    
                    s = pts.sum(axis=1)
                    rect[0] = pts[np.argmin(s)]
                    rect[2] = pts[np.argmax(s)]
                    
                    diff = np.diff(pts, axis=1)
                    rect[1] = pts[np.argmin(diff)]
                    rect[3] = pts[np.argmax(diff)]
                    
                    # Compute width and height
                    (tl, tr, br, bl) = rect
                    widthA = np.linalg.norm(br - bl)
                    widthB = np.linalg.norm(tr - tl)
                    maxWidth = max(int(widthA), int(widthB))
                    
                    heightA = np.linalg.norm(tr - br)
                    heightB = np.linalg.norm(tl - bl)
                    maxHeight = max(int(heightA), int(heightB))
                    
                    # Destination points
                    dst = np.array([
                        [0, 0],
                        [maxWidth - 1, 0],
                        [maxWidth - 1, maxHeight - 1],
                        [0, maxHeight - 1]
                    ], dtype="float32")
                    
                    # Perspective transform
                    M = cv2.getPerspectiveTransform(rect, dst)
                    img = cv2.warpPerspective(img, M, (maxWidth, maxHeight))
            
            return img
        except Exception as e:
            logger.warning(f"Perspective correction failed: {e}")
            return img
    
    def upscale_image(self, img: np.ndarray, scale: float = 2.0) -> np.ndarray:
        """
        Upscale image for better small text recognition
        Uses INTER_CUBIC for quality
        """
        try:
            h, w = img.shape[:2]
            new_h, new_w = int(h * scale), int(w * scale)
            logger.debug(f"Upscaling image {scale}x: {w}x{h} -> {new_w}x{new_h}")
            return cv2.resize(img, (new_w, new_h), interpolation=cv2.INTER_CUBIC)
        except Exception as e:
            logger.warning(f"Upscaling failed: {e}")
            return img
    
    def enhance_contrast_advanced(self, img: np.ndarray) -> np.ndarray:
        """
        Advanced contrast enhancement with CLAHE + denoise
        """
        try:
            # Convert to LAB color space
            lab = cv2.cvtColor(img, cv2.COLOR_BGR2LAB) if len(img.shape) == 3 else cv2.cvtColor(cv2.cvtColor(img, cv2.COLOR_GRAY2BGR), cv2.COLOR_BGR2LAB)
            
            # Split channels
            l, a, b = cv2.split(lab)
            
            # Apply CLAHE to L channel
            clahe = cv2.createCLAHE(clipLimit=3.0, tileGridSize=(8, 8))
            l = clahe.apply(l)
            
            # Merge and convert back
            lab = cv2.merge([l, a, b])
            img = cv2.cvtColor(lab, cv2.COLOR_LAB2BGR)
            
            # Denoise
            img = cv2.fastNlMeansDenoisingColored(img, None, 10, 10, 7, 21)
            
            return img
        except Exception as e:
            logger.warning(f"Contrast enhancement failed: {e}")
            return img
    
    def preprocess_image(self, image_path: str, output_path: Optional[str] = None, fast_mode: bool = True) -> str:
        """
        Advanced preprocessing pipeline:
        (1) Deskew + Perspective correction (for camera photos)
        (2) Contrast enhancement + denoise
        (3) Upscale 2x for small text
        
        Args:
            image_path: Path to input image
            output_path: Optional output path (default: auto-generated)
            fast_mode: Use fast preprocessing (default: True for speed)
        
        Returns:
            Path to preprocessed image
        """
        logger.debug(f"Preprocessing image: {image_path} (fast_mode={fast_mode})")
        
        try:
            img = cv2.imread(image_path)
            if img is None:
                raise ValueError(f"Failed to read image: {image_path}")
            
            # (1) Deskew + Perspective Correction (for camera photos)
            if not fast_mode:
                img = self.perspective_correction(img)
            img = self.deskew_image(img)
            
            # 1. Resize dengan menjaga rasio (max 1600px for faster processing)
            height, width = img.shape[:2]
            max_dimension = 1600 if fast_mode else 2000
            if max(height, width) > max_dimension:
                scale = max_dimension / max(height, width)
                new_width = int(width * scale)
                new_height = int(height * scale)
                img = cv2.resize(img, (new_width, new_height), interpolation=cv2.INTER_LINEAR)
            
            # 2. Convert to grayscale
            if len(img.shape) == 3:
                gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
            else:
                gray = img
            
            if fast_mode:
                # Fast mode: Only CLAHE enhancement
                clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
                processed = clahe.apply(gray)
            else:
                # Full mode: Denoise + Normalization
                denoised = cv2.fastNlMeansDenoising(gray, None, h=10, templateWindowSize=7, searchWindowSize=21)
                normalized = self._normalize_pixels(denoised)
                clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
                processed = clahe.apply(normalized)
            
            # Save preprocessed image
            if output_path is None:
                path_obj = Path(image_path)
                output_path = str(path_obj.parent / f"{path_obj.stem}_processed{path_obj.suffix}")
            
            cv2.imwrite(output_path, processed)
            logger.debug(f"Preprocessing completed")
            
            return output_path
            
        except Exception as e:
            logger.warning(f"Preprocessing failed, using original: {e}")
            # Fallback: return original image path
            return image_path
    
    def _normalize_pixels(self, img: np.ndarray) -> np.ndarray:
        """
        Normalisasi nilai piksel dengan rumus statistik:

            I'(x,y) = (I(x,y) - μ) / σ

        Keterangan:
            I(x,y) = intensitas piksel asli
            μ      = rata-rata intensitas citra
            σ      = standar deviasi intensitas citra
            I'(x,y)= nilai piksel hasil normalisasi (di-rescale ke 0–255)

        Args:
            img: Input grayscale image

        Returns:
            Normalized image (0-255 range)
        """
        # Calculate mean (μ) and standard deviation (σ)
        mean = np.mean(img)
        std  = np.std(img)

        if std == 0:
            logger.warning("Standard deviation is 0, skipping normalization")
            return img

        # Apply normalization: I'(x,y) = (I(x,y) - μ) / σ
        normalized = (img.astype(np.float32) - mean) / std

        # Scale back to 0-255 range
        normalized = (
            (normalized - normalized.min()) /
            (normalized.max() - normalized.min()) * 255
        )

        return normalized.astype(np.uint8)


    # =========================================================
    # AUGMENTASI DATA
    # =========================================================

    def adjust_brightness(self, img: np.ndarray, factor: float = 1.3) -> np.ndarray:
        """
        Sesuaikan kecerahan gambar (Brightness Adjustment).

        Bekerja pada channel Value (V) di color space HSV sehingga
        hue dan saturation warna tidak terpengaruh.

        Args:
            img   : Input image (BGR)
            factor: Faktor perkalian kecerahan.
                    > 1.0 → lebih terang, < 1.0 → lebih gelap (default: 1.3)

        Returns:
            Image dengan kecerahan yang disesuaikan (BGR)
        """
        try:
            hsv        = cv2.cvtColor(img, cv2.COLOR_BGR2HSV)
            h, s, v    = cv2.split(hsv)
            v          = np.clip(v.astype(np.float32) * factor, 0, 255).astype(np.uint8)
            hsv_bright = cv2.merge([h, s, v])
            result     = cv2.cvtColor(hsv_bright, cv2.COLOR_HSV2BGR)
            logger.debug(f"Brightness adjusted: factor={factor}")
            return result
        except Exception as e:
            logger.warning(f"Brightness adjustment failed: {e}")
            return img

    def adjust_contrast(self, img: np.ndarray, alpha: float = 1.4, beta: int = 10) -> np.ndarray:
        """
        Sesuaikan kontras gambar (Contrast Adjustment).

        Menggunakan transformasi linear:
            O(x,y) = α · I(x,y) + β

        Keterangan:
            α (alpha) = faktor pengali kontras (> 1 → kontras naik)
            β (beta)  = penambahan kecerahan konstan
            I(x,y)   = nilai piksel input
            O(x,y)   = nilai piksel output

        Args:
            img  : Input image (BGR)
            alpha: Faktor kontras (default: 1.4)
            beta : Offset kecerahan (default: 10)

        Returns:
            Image dengan kontras yang disesuaikan
        """
        try:
            result = cv2.convertScaleAbs(img, alpha=alpha, beta=beta)
            logger.debug(f"Contrast adjusted: alpha={alpha}, beta={beta}")
            return result
        except Exception as e:
            logger.warning(f"Contrast adjustment failed: {e}")
            return img

    def add_gaussian_noise(self, img: np.ndarray, mean: float = 0.0, std: float = 12.0) -> np.ndarray:
        """
        Tambahkan Gaussian noise ke gambar (untuk evaluasi robustness).

        Berguna untuk menguji ketahanan sistem OCR terhadap gambar
        berkualitas rendah atau yang memiliki noise dari kamera.

        Noise model:
            I_noisy(x,y) = I(x,y) + N(μ, σ²)

        Keterangan:
            N(μ, σ²) = distribusi Gaussian dengan mean μ dan std σ
            μ        = mean noise (default: 0 → noise simetris)
            σ        = standar deviasi noise (default: 12)

        Args:
            img : Input image (BGR)
            mean: Mean distribusi Gaussian (default: 0.0)
            std : Standar deviasi noise (default: 12.0)

        Returns:
            Image dengan Gaussian noise ditambahkan
        """
        try:
            noise  = np.random.normal(mean, std, img.shape).astype(np.float32)
            noisy  = np.clip(img.astype(np.float32) + noise, 0, 255)
            result = noisy.astype(np.uint8)
            logger.debug(f"Gaussian noise added: mean={mean}, std={std}")
            return result
        except Exception as e:
            logger.warning(f"Gaussian noise addition failed: {e}")
            return img
