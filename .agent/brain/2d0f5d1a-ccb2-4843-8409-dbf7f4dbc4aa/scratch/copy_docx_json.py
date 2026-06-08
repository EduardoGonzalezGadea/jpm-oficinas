import json
import shutil
import os

src_path = r"C:\xampp\htdocs\oficinas\.agent\brain\2d0f5d1a-ccb2-4843-8409-dbf7f4dbc4aa\scratch\multas_from_docx.json"
dest_path = r"C:\xampp\htdocs\oficinas\database\data\multas_303.json"

try:
    if os.path.exists(src_path):
        # Read the file to ensure it's valid JSON
        with open(src_path, 'r', encoding='utf-8') as f:
            data = json.load(f)
            
        print(f"Read {len(data)} records from source JSON.")
        
        # Write to destination as pretty-printed JSON with UTF-8 encoding
        with open(dest_path, 'w', encoding='utf-8') as f:
            json.dump(data, f, ensure_ascii=False, indent=4)
            
        print(f"Successfully copied and pretty-printed to {dest_path}")
    else:
        print("Source JSON file does not exist!")
except Exception as e:
    print("Error:", e)
