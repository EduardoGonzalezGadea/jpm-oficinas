import json
import os

original_path = r"C:\xampp\htdocs\oficinas\database\data\multas_303.json"

if os.path.exists(original_path):
    with open(original_path, 'r', encoding='utf-8') as f:
        data = json.load(f)
    print(f"Original multas_303.json exists and has {len(data)} records.")
    # Check first few items
    for item in data[:5]:
        print(item)
else:
    print("Original multas_303.json does NOT exist at that path.")
