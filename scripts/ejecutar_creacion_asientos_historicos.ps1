# Script PowerShell para ejecutar la creación de asientos históricos de caja chica
# con validaciones y estadísticas

$ErrorActionPreference = "Stop"

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Creación de Asientos Históricos" -ForegroundColor Cyan
Write-Host "Caja Chica → Libro Diario" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# 1. Verificar que estamos en el directorio correcto
if (-not (Test-Path "artisan")) {
    Write-Host "❌ Error: No se encuentra el archivo artisan" -ForegroundColor Red
    Write-Host "   Asegúrate de ejecutar este script desde la raíz del proyecto" -ForegroundColor Yellow
    exit 1
}

Write-Host "✅ Directorio verificado" -ForegroundColor Green
Write-Host ""

# 2. Mostrar estadísticas actuales
Write-Host "📊 Estadísticas Actuales:" -ForegroundColor Cyan
php temp_check_asientos.php
Write-Host ""

# 3. Ejecutar simulación
Write-Host "🔍 Ejecutando simulación (--dry-run)..." -ForegroundColor Cyan
Write-Host ""
php artisan caja-chica:crear-asientos-historicos --dry-run --skip-confirmacion
Write-Host ""

# 4. Preguntar confirmación
$confirmacion = Read-Host "¿Desea continuar con la creación de asientos? (s/n)"

if ($confirmacion -ne "s" -and $confirmacion -ne "S") {
    Write-Host "❌ Operación cancelada por el usuario" -ForegroundColor Yellow
    exit 0
}

# 5. Sugerir backup
Write-Host ""
Write-Host "⚠️  IMPORTANTE: Se recomienda hacer un backup de la base de datos" -ForegroundColor Yellow
$backup = Read-Host "¿Ya realizó el backup? (s/n)"

if ($backup -ne "s" -and $backup -ne "S") {
    Write-Host "⏸️  Por favor, realice un backup antes de continuar" -ForegroundColor Yellow
    Write-Host "   Puede usar: php artisan backup:run" -ForegroundColor White
    exit 0
}

# 6. Ejecutar creación de asientos
Write-Host ""
Write-Host "🚀 Ejecutando creación de asientos históricos..." -ForegroundColor Cyan
Write-Host ""

try {
    php artisan caja-chica:crear-asientos-historicos --skip-confirmacion
    
    # 7. Recalcular saldos
    Write-Host ""
    Write-Host "🔄 Recalculando saldos del libro diario..." -ForegroundColor Cyan
    Write-Host ""
    php artisan libro-diario:recalcular-saldos
    
    # 8. Mostrar estadísticas finales
    Write-Host ""
    Write-Host "📊 Estadísticas Finales:" -ForegroundColor Cyan
    php temp_check_asientos.php
    
    Write-Host ""
    Write-Host "==========================================" -ForegroundColor Green
    Write-Host "✅ Proceso completado exitosamente" -ForegroundColor Green
    Write-Host "==========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Recomendaciones:" -ForegroundColor Yellow
    Write-Host "  - Verificar los asientos creados en el sistema"
    Write-Host "  - Revisar los saldos del libro diario"
    Write-Host "  - Realizar pruebas de integridad de datos"
    
} catch {
    Write-Host ""
    Write-Host "❌ Error durante la ejecución:" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    Write-Host ""
    Write-Host "El proceso se detuvo. Revise los logs y la base de datos." -ForegroundColor Yellow
    exit 1
}
