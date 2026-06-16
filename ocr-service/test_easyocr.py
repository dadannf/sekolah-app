import sys
import easyocr

try:
    sys.stdout.reconfigure(encoding='utf-8')
    reader = easyocr.Reader(['id', 'en'], gpu=False)
    result = reader.readtext('test_image.png')
    
    print("--- EASYOCR OUTPUT ---")
    for (bbox, text, prob) in result:
        print(f"[{prob:.2f}] {text}")
        
except Exception as e:
    print(f"Error: {e}")
