import sys

try:
    import docx
    print("python-docx is installed")
except ImportError:
    print("python-docx is NOT installed")

try:
    import zipfile
    import xml.etree.ElementTree as ET
    print("zipfile and xml are available")
except ImportError as e:
    print("Standard libraries missing:", e)
