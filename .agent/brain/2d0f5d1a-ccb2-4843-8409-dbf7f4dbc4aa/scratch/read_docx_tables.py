import zipfile
import xml.etree.ElementTree as ET

docx_path = r"C:\xampp\htdocs\oficinas\docs\DECRETO Nº 303.docx"

try:
    with zipfile.ZipFile(docx_path) as z:
        doc_xml = z.read("word/document.xml")
        root = ET.fromstring(doc_xml)
        namespaces = {'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'}
        
        tables = root.findall('.//w:tbl', namespaces)
        print(f"Total tables found: {len(tables)}")
        
        for idx, table in enumerate(tables):
            rows = table.findall('.//w:tr', namespaces)
            print(f"Table {idx+1}: {len(rows)} rows")
            
            # Let's show first 3 rows
            for r_idx, row in enumerate(rows[:5]):
                cells = row.findall('.//w:tc', namespaces)
                cell_texts = []
                for cell in cells:
                    paragraphs = cell.findall('.//w:p', namespaces)
                    p_texts = []
                    for p in paragraphs:
                        texts = [t.text for t in p.findall('.//w:t', namespaces) if t.text]
                        p_texts.append("".join(texts))
                    cell_texts.append(" | ".join(p_texts))
                print(f"  Row {r_idx+1}: {cell_texts}")
            print("-" * 50)
            
except Exception as e:
    print("Error:", e)
