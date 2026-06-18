import os
import sys
import json
import cv2
import traceback
from ocr_processor import OCRProcessor

def main():
    img_path = 'test_image.png'
    if len(sys.argv) > 1:
        img_path = sys.argv[1]

    if not os.path.exists(img_path):
        print(f"Error: {img_path} not found.")
        return

    processor = OCRProcessor()
    text, detections, conf = processor.process_image_variant(img_path, 'original')
    
    with open('audit_results.txt', 'w', encoding='utf-8') as f:
        f.write(f"=== AUDIT OCR UNTUK GAMBAR: {img_path} ===\n")
        f.write("\n1. OUTPUT RAW TEXT\n")
        f.write("-" * 50 + "\n")
        f.write(text + "\n")
        f.write("-" * 50 + "\n")
        
        f.write("\n2. DETAIL DETECTIONS (Text, Confidence, BBox)\n")
        f.write("-" * 50 + "\n")
        for i, det in enumerate(detections):
            t = det.get('text', '')
            c = det.get('confidence', 0.0)
            box = det.get('box', [])
            f.write(f"[{i:02d}] Text: {t!r}\n")
            f.write(f"     Conf: {c:.4f}\n")
            f.write(f"     BBox: {box}\n")
    
    print("Audit results written to audit_results.txt")
    
    # Draw boxes
    try:
        img = cv2.imread(img_path)
        for i, det in enumerate(detections):
            box = det.get('box', [])
            if box and len(box) == 4:
                # Convert to integer coordinates
                pts = [[int(pt[0]), int(pt[1])] for pt in box]
                # Draw polygon
                import numpy as np
                pts_np = np.array(pts, np.int32)
                pts_np = pts_np.reshape((-1, 1, 2))
                cv2.polylines(img, [pts_np], isClosed=True, color=(0, 255, 0), thickness=2)
                
                # Add text label (just the index)
                cv2.putText(img, str(i), (pts[0][0], max(0, pts[0][1] - 5)), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 0, 255), 1)
        
        out_path = 'test_image_annotated.png'
        cv2.imwrite(out_path, img)
        print(f"\n[INFO] Visualisasi bounding box disimpan ke {out_path}")
    except Exception as e:
        print(f"Error drawing boxes: {e}")

if __name__ == '__main__':
    main()
