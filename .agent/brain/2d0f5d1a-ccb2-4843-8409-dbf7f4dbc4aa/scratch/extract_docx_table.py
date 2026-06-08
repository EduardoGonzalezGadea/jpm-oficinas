import zipfile
import xml.etree.ElementTree as ET
import json
import re

docx_path = r"C:\xampp\htdocs\oficinas\docs\DECRETO Nº 303.docx"

try:
    with zipfile.ZipFile(docx_path) as z:
        doc_xml = z.read("word/document.xml")
        root = ET.fromstring(doc_xml)
        namespaces = {'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'}
        
        tables = root.findall('.//w:tbl', namespaces)
        
        # Table 2 is the main traffic fines table (219 rows)
        main_table = tables[1]
        rows = main_table.findall('.//w:tr', namespaces)
        
        extracted_data = []
        
        # Header is in Row 1: ['Grupo', 'Código', 'Descripción indicativa', 'VALOR UR']
        for r_idx in range(1, len(rows)):
            row = rows[r_idx]
            cells = row.findall('.//w:tc', namespaces)
            cell_texts = []
            for cell in cells:
                paragraphs = cell.findall('.//w:p', namespaces)
                p_texts = []
                for p in paragraphs:
                    texts = [t.text for t in p.findall('.//w:t', namespaces) if t.text]
                    p_texts.append("".join(texts))
                # Clean multiple spaces and non-breaking spaces
                text = " ".join(p_texts).strip()
                text = text.replace('\xa0', ' ')
                text = re.sub(r'\s+', ' ', text)
                cell_texts.append(text)
                
            if len(cell_texts) >= 4:
                # Map columns
                grupo = cell_texts[0]
                codigo = cell_texts[1]
                descripcion = cell_texts[2]
                valor_ur = cell_texts[3]
                
                # Make sure code is not empty
                if codigo:
                    extracted_data.append({
                        'grupo': grupo,
                        'codigo': codigo,
                        'descripcion': descripcion,
                        'valor_ur': valor_ur
                    })
                    
        print(f"Extracted {len(extracted_data)} fine records from DOCX table.")
        
        # Write to JSON file
        output_path = r"C:\xampp\htdocs\oficinas\.agent\brain\2d0f5d1a-ccb2-4843-8409-dbf7f4dbc4aa\scratch\multas_from_docx.json"
        with open(output_path, 'w', encoding='utf-8') as f:
            json.dump(extracted_data, f, ensure_ascii=False, indent=4)
            
        print(f"Saved to {output_path}")
        
        # Print first 5 and last 5
        print("\nFirst 5 records:")
        for r in extracted_data[:5]:
            print(r)
            
        print("\nLast 5 records:")
        for r in extracted_data[-5:]:
            print(r)
            
except Exception as e:
    print("Error:", e)
