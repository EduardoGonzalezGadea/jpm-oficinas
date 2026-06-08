import zipfile
import xml.etree.ElementTree as ET

docx_path = r"C:\xampp\htdocs\oficinas\docs\DECRETO Nº 303.docx"

try:
    with zipfile.ZipFile(docx_path) as z:
        doc_xml = z.read("word/document.xml")
        
        # Parse XML
        root = ET.fromstring(doc_xml)
        
        # XML namespace for Word
        namespaces = {'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'}
        
        # Extract text from paragraphs
        paragraphs = []
        for p in root.findall('.//w:p', namespaces):
            texts = [t.text for t in p.findall('.//w:t', namespaces) if t.text]
            if texts:
                paragraphs.append("".join(texts))
        
        print(f"Successfully extracted {len(paragraphs)} paragraphs.")
        print("\nFirst 30 paragraphs:")
        for i, p in enumerate(paragraphs[:30]):
            print(f"{i+1}: {p}")
            
except Exception as e:
    print("Error:", e)
