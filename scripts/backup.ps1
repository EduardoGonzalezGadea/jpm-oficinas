# Script de copia de seguridad para Laravel
$ErrorActionPreference = "Stop"

# Configuración
$projectPath = "C:\xampp\htdocs\oficinas"
$logPath = "$projectPath\storage\logs\backup.log"

# Función para escribir en el log
function Write-Log {
    param($Message)
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    "$timestamp - $Message" | Out-File -FilePath $logPath -Append
    Write-Host $Message
}

try {
    # Cambiar al directorio del proyecto
    Set-Location -Path $projectPath
    Write-Log "Iniciando proceso de copia de seguridad..."

    # Limpiar la caché de configuración
    Write-Log "Limpiando caché..."
    & php artisan config:clear
    & php artisan cache:clear

    # Ejecutar la copia de seguridad
    Write-Log "Ejecutando copia de seguridad..."
    $backupOutput = & php artisan backup:run 2>&1
    Write-Log $backupOutput

    Write-Log "Copia de seguridad completada exitosamente."
} catch {
    Write-Log "Error durante la copia de seguridad: $_"
    exit 1
}
