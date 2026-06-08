import json

json_path = r"C:\xampp\htdocs\oficinas\.agent\brain\2d0f5d1a-ccb2-4843-8409-dbf7f4dbc4aa\scratch\multas_from_docx.json"

with open(json_path, 'r', encoding='utf-8') as f:
    data = json.load(f)

print("First few items:")
for item in data[:5]:
    print(item)
    # Check if there are  characters in the string
    for k, v in item.items():
        if '' in v:
            print(f"FOUND  in {k}: {v.encode('utf-8')}")
            # Try to see if it's CP1252 misdecoded as UTF-8 or something
