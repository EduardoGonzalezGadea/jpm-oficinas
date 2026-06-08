#!/usr/bin/env python3
"""
Detector CFE Tesorería - Firefox Extension Packager (build.py)
Este script depura los archivos de la extensión, valida su estructura,
realiza las modificaciones necesarias para la compatibilidad con Firefox 139+
y genera el paquete final .xpi en la carpeta dist/.
"""

import os
import json
import shutil
import zipfile
import tempfile
import sys

# Definir directorios
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DIST_DIR = os.path.join(BASE_DIR, "dist")
TEMP_DIR_NAME = "firefox_temp_build"

# Archivos obligatorios para verificar integridad
REQUIRED_FILES = [
    "manifest.json",
    "background.js",
    "content.js",
    "analyze.html",
    "analyze.js",
    os.path.join("popup", "popup.html"),
    os.path.join("popup", "popup.js"),
    os.path.join("popup", "popup.css"),
    os.path.join("icons", "icon16.png"),
    os.path.join("icons", "icon48.png"),
    os.path.join("icons", "icon128.png"),
]

def log_info(msg):
    print(f"\033[94m[INFO]\033[0m {msg}")

def log_success(msg):
    print(f"\033[92m[SUCCESS]\033[0m {msg}")

def log_error(msg):
    print(f"\033[91m[ERROR]\033[0m {msg}", file=sys.stderr)

def main():
    print("=" * 65)
    print("      DETECTOR CFE - COMPILADOR Y EMPAQUETADOR DE FIREFOX")
    print("=" * 65)

    # 1. Verificar integridad del directorio origen
    log_info("Auditando integridad de los archivos de la extensión...")
    missing_files = []
    for rel_path in REQUIRED_FILES:
        full_path = os.path.join(BASE_DIR, rel_path)
        if not os.path.exists(full_path):
            missing_files.append(rel_path)
            
    if missing_files:
        log_error("Faltan archivos requeridos para el empaquetado:")
        for f in missing_files:
            print(f"  - {f}")
        sys.exit(1)
        
    log_success("Integridad auditada correctamente. Todos los archivos están presentes.")

    # 2. Asegurar que existe el directorio de salida
    if not os.path.exists(DIST_DIR):
        os.makedirs(DIST_DIR)
        log_info(f"Creado directorio de distribución: {DIST_DIR}")

    # 3. Crear directorio temporal para el build de Firefox
    with tempfile.TemporaryDirectory() as temp_build_dir:
        log_info(f"Creado directorio temporal de compilación.")

        # 4. Copiar archivos del proyecto al temporal
        log_info("Copiando archivos al entorno de compilación...")
        for root, dirs, files in os.walk(BASE_DIR):
            # Excluir la carpeta dist y carpetas ocultas / temporales
            dirs[:] = [d for d in dirs if not d.startswith('.') and d != 'dist' and d != TEMP_DIR_NAME]
            
            for file in files:
                src_path = os.path.join(root, file)
                # No copiar el propio script de compilación
                if src_path == os.path.abspath(__file__):
                    continue
                
                rel_path = os.path.relpath(src_path, BASE_DIR)
                dest_path = os.path.join(temp_build_dir, rel_path)
                
                os.makedirs(os.path.dirname(dest_path), exist_ok=True)
                shutil.copy2(src_path, dest_path)

        log_success("Copiado de recursos completado.")

        # 5. Modificar manifest.json para cumplir requerimientos estrictos de Firefox
        log_info("Modificando manifest.json para compatibilidad con Firefox 139+...")
        manifest_path = os.path.join(temp_build_dir, "manifest.json")
        
        try:
            with open(manifest_path, 'r', encoding='utf-8') as f:
                manifest_data = json.load(f)

            # A. Eliminar permiso 'file:///*' que Firefox prohíbe de forma estricta
            if "host_permissions" in manifest_data:
                original_len = len(manifest_data["host_permissions"])
                manifest_data["host_permissions"] = [
                    perm for perm in manifest_data["host_permissions"] if not perm.startswith("file://")
                ]
                new_len = len(manifest_data["host_permissions"])
                if original_len != new_len:
                    log_info("  - Removido permiso restringido 'file:///*' de host_permissions.")

            # B. Inyectar 'browser_specific_settings' (Obligatorio para la firma en Mozilla)
            manifest_data["browser_specific_settings"] = {
                "gecko": {
                    "id": "detector-cfe@tesoreria.oficinas",
                    "strict_min_version": "139.0",
                    "data_collection_permissions": {
                        "required": ["none"]
                    }
                }
            }
            log_info("  - Inyectada configuración 'browser_specific_settings.gecko.id' (detector-cfe@tesoreria.oficinas)")
            log_info("  - Inyectada configuración 'data_collection_permissions: none' para cumplir directivas de privacidad.")

            # C. Corregir propiedad background para compatibilidad de Firefox con scripts de fondo
            if "background" in manifest_data:
                bg = manifest_data["background"]
                if "service_worker" in bg:
                    service_worker_script = bg["service_worker"]
                    bg["scripts"] = [service_worker_script]
                    del bg["service_worker"]
                    log_info("  - Reemplazado background.service_worker por background.scripts para Firefox.")

            # Guardar el manifest de Firefox modificado
            with open(manifest_path, 'w', encoding='utf-8') as f:
                json.dump(manifest_data, f, indent=2, ensure_ascii=False)
                
            log_success("manifest.json adaptado para Firefox de forma exitosa.")
            
        except Exception as e:
            log_error(f"Error procesando manifest.json: {str(e)}")
            sys.exit(1)

        # 6. Empaquetar todo en un archivo zip / .xpi
        xpi_filename = "detector-cfe-tesoreria-firefox.xpi"
        xpi_path = os.path.join(DIST_DIR, xpi_filename)
        
        log_info(f"Comprimiendo recursos en paquete XPI ({xpi_filename})...")
        
        try:
            # Si el archivo anterior existe, removerlo
            if os.path.exists(xpi_path):
                os.remove(xpi_path)
                
            with zipfile.ZipFile(xpi_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
                for root, dirs, files in os.walk(temp_build_dir):
                    for file in files:
                        file_path = os.path.join(root, file)
                        rel_path = os.path.relpath(file_path, temp_build_dir)
                        zipf.write(file_path, rel_path)
                        
            file_size_kb = os.path.getsize(xpi_path) / 1024
            log_success(f"Empaquetado exitoso.")
            print("-" * 65)
            print(f"  ARCHIVO GENERADO: {xpi_path}")
            print(f"  TAMAÑO: {file_size_kb:.2f} KB")
            print("  TIPO: Extensión Firefox Firmable (.xpi / ZIP)")
            print("-" * 65)
            
        except Exception as e:
            log_error(f"Error comprimiendo paquete: {str(e)}")
            sys.exit(1)

    print("\nEl paquete está listo para instalar temporalmente en Firefox o")
    print("para subir a la consola de firmas de Mozilla Developer Center.")
    print("=" * 65)

if __name__ == "__main__":
    main()
