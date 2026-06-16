from ocr_processor import OCRProcessor
from bank_parser import BankReceiptParser
import json
import traceback

try:
    processor = OCRProcessor()
    
    img_path = r'C:\Users\masda\.gemini\antigravity-ide\brain\6737ad74-ffd7-4afc-9980-c17822508ef4\media__1781525150913.jpg'
    
    print("Testing original variant...")
    text, detections, conf = processor.process_image_variant(img_path, 'original')
    
    print("RAW TEXT:")
    print("=========================")
    print(text)
    print("=========================")
    
    parser = BankReceiptParser()
    parsed_result = parser.parse(text, detections)
    
    print("\n--- EXTRACTED FIELDS ---")
    print(json.dumps(parsed_result.to_dict(), indent=2))
except Exception as e:
    print(f"Error: {e}")
    traceback.print_exc()
