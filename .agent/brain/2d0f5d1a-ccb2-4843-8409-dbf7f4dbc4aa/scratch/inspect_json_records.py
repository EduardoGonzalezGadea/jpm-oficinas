import json

json_path = r"C:\xampp\htdocs\oficinas\.agent\brain\2d0f5d1a-ccb2-4843-8409-dbf7f4dbc4aa\scratch\multas_from_docx.json"

with open(json_path, 'r', encoding='utf-8') as f:
    data = json.load(f)

print(f"Total records in extracted JSON: {len(data)}")

groups = {}
for r in data:
    g = r['grupo']
    groups[g] = groups.get(g, 0) + 1

print("\nGroups summary:")
for g, cnt in sorted(groups.items(), key=lambda x: x[0]):
    # Encode for safe printing
    g_print = g.encode('utf-8', errors='replace').decode('cp1252', errors='replace')
    print(f"Group: {g} | Count: {cnt}")
