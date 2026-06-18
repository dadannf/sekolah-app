import glob
import os
from ocr_processor import OCRProcessor

def main():
    processor = OCRProcessor()
    pattern = r"f:\laragon\www\sekolah\storage\app\payments\2026\06\*.jpg"
    files = glob.glob(pattern)
    files.sort(key=os.path.getmtime, reverse=True)
    
    for f in files[:20]:
        print(f"Testing {f}...")
        try:
            text, _, _ = processor.process_image_variant(f, 'original')
            if '2180' in text:
                print("FOUND IN:", f)
                break
        except Exception as e:
            print("Error on", f, e)

if __name__ == '__main__':
    main()
