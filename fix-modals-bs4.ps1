# Script para corregir sintaxis Bootstrap 5 a Bootstrap 4 en modales
# Ejecutar desde la raíz del proyecto: .\fix-modals-bs4.ps1

Write-Host "=== Corrector de Modales: Bootstrap 5 → Bootstrap 4 ===" -ForegroundColor Cyan
Write-Host ""

$archivosCorregidos = 0
$cambiosRealizados = 0

# Buscar todos los archivos .blade.php
$archivos = Get-ChildItem -Path "resources\views" -Filter "*.blade.php" -Recurse

foreach ($archivo in $archivos) {
    $contenido = Get-Content $archivo.FullName -Raw -Encoding UTF8
    $contenidoOriginal = $contenido
    $cambiosEnArchivo = 0

    # 1. Corregir data-bs-toggle="modal" → data-toggle="modal"
    if ($contenido -match 'data-bs-toggle="modal"') {
        $contenido = $contenido -replace 'data-bs-toggle="modal"', 'data-toggle="modal"'
        $cambiosEnArchivo++
    }

    # 2. Corregir data-bs-target="#" → data-target="#"
    if ($contenido -match 'data-bs-target=') {
        $contenido = $contenido -replace 'data-bs-target=', 'data-target='
        $cambiosEnArchivo++
    }

    # 3. Corregir data-bs-dismiss="modal" → data-dismiss="modal"
    if ($contenido -match 'data-bs-dismiss="modal"') {
        $contenido = $contenido -replace 'data-bs-dismiss="modal"', 'data-dismiss="modal"'
        $cambiosEnArchivo++
    }

    # 4. Corregir class="btn-close" → class="close" con × dentro
    # Patrón: <button...class="btn-close"...data-bs-dismiss="modal"...></button>
    if ($contenido -match 'class="btn-close"') {
        $contenido = $contenido -replace 'class="btn-close"', 'class="close"'
        # Agregar × si el botón está vacío
        $contenido = $contenido -replace '(<button[^>]*class="close"[^>]*>)\s*(</button>)', '$1<span aria-hidden="true">&times;</span>$2'
        $cambiosEnArchivo++
    }

    # 5. Corregir class="btn-close-white" → class="close text-white"
    if ($contenido -match 'btn-close-white') {
        $contenido = $contenido -replace 'btn-close-white', 'close text-white'
        $cambiosEnArchivo++
    }

    # 6. Agregar role="document" a modal-dialog si no existe
    if ($contenido -match '<div class="modal-dialog(?! [^"]*role=)') {
        $contenido = $contenido -replace '<div class="modal-dialog', '<div class="modal-dialog" role="document'
        $cambiosEnArchivo++
    }

    # 7. Agregar role="dialog" a modal si no existe
    if ($contenido -match '<div[^>]*class="modal[^"]*"(?![^>]*role="dialog")') {
        $contenido = $contenido -replace '(<div[^>]*class="modal[^"]*")', '$1 role="dialog"'
        $cambiosEnArchivo++
    }

    # 8. Corregir wire:ignore en modales (debe ser wire:ignore.self)
    if ($contenido -match '<div[^>]*class="modal[^"]*"[^>]*wire:ignore(?!\.self)') {
        $contenido = $contenido -replace 'wire:ignore([^.])', 'wire:ignore.self$1'
        $cambiosEnArchivo++
    }

    # Si hubo cambios, guardar el archivo
    if ($contenido -ne $contenidoOriginal) {
        Set-Content -Path $archivo.FullName -Value $contenido -Encoding UTF8 -NoNewline
        $archivosCorregidos++
        $cambiosRealizados += $cambiosEnArchivo
        Write-Host "Corregido: $($archivo.FullName) ($cambiosEnArchivo cambios)" -ForegroundColor Green
    }
}

Write-Host ""
Write-Host "=== Resumen ===" -ForegroundColor Cyan
Write-Host "Archivos corregidos: $archivosCorregidos" -ForegroundColor Yellow
Write-Host "Cambios realizados: $cambiosRealizados" -ForegroundColor Yellow
Write-Host ""
Write-Host "Listo! Ahora ejecuta: npm run dev" -ForegroundColor Green
