import urllib.request
import json
req = urllib.request.Request('http://127.0.0.1:1234/load', data=json.dumps({"model_name": "Paddle OCR"}).encode(), headers={'Content-Type': 'application/json'})
try:
    res = urllib.request.urlopen(req)
    print(res.read())
except Exception as e:
    print(e)
