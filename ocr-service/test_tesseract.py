import pytesseract
from PIL import Image
import os

try:
    # Set tesseract path for Windows
    pytesseract.pytesseract.tesseract_cmd = r'C:\Program Files\Tesseract-OCR\tesseract.exe'
    
    # Try common local laragon path if standard path doesn't exist
    if not os.path.exists(pytesseract.pytesseract.tesseract_cmd):
        pytesseract.pytesseract.tesseract_cmd = r'F:\laragon\bin\tesseract\tesseract.exe'
        
    img = Image.open("test_image.png")
    text = pytesseract.image_to_string(img, lang='ind+eng')
    print("--- TESSERACT OCR OUTPUT ---")
    print(text)
except Exception as e:
    print(f"Error: {e}")
