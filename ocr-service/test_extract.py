from ocr_processor import OCRProcessor
from bank_parser import BankReceiptParser
import json
import traceback

try:
    processor = OCRProcessor()
    
    print("Testing original variant...")
    text, detections, conf = processor.process_image_variant('test_image.png', 'original')
    
    print(f"OCR Full Text Length: {len(text)}")
    print(f"OCR Confidence: {conf}%")
    print(f"Detection Count: {len(detections)}")
    
    parser = BankReceiptParser()
    parsed_result = parser.parse(text, detections)
    
    print("\n--- EXTRACTED FIELDS ---")
    print(json.dumps(parsed_result.to_dict(), indent=2))
except Exception as e:
    print(f"Error: {e}")
    traceback.print_exc()
