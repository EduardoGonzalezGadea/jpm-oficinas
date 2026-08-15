# Script para Migrar un Componente Livewire 3 → 4
# Uso: .\scripts\migrar-componente-livewire.ps1 -FilePath "app\Livewire\Tesoreria\Multa.php"

param(
    [Parameter(Mandatory=$true)]
    [string]$FilePath
)

Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "   Migrador Automático Livewire 3 → 4" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host ""

# Verificar que el archivo existe
if (-Not (Test-Path $FilePath)) {
    Write-Host "ERROR: El archivo no existe: $FilePath" -ForegroundColor Red
    exit 1
}

Write-Host "Migrando: $FilePath" -ForegroundColor Yellow
Write-Host ""

# Crear backup
$backupPath = "$FilePath.bak"
Copy-Item $FilePath $backupPath
Write-Host "[✓] Backup creado: $backupPath" -ForegroundColor Green

# Leer contenido
$content = Get-Content $FilePath -Raw

# Contador de cambios
$cambios = 0

# 1. Migrar $this->emit() → $this->dispatch()
Write-Host "[1/8] Migrando emit() → dispatch()..." -ForegroundColor Yellow
$pattern1 = '\$this->emit\('
if ($content -match $pattern1) {
    $content = $content -replace '\$this->emit\(', '$this->dispatch('
    $cambios++
    Write-Host "    ✓ Migrados emit() → dispatch()" -ForegroundColor Green
}

# 2. Migrar $this->emitTo() → $this->dispatch()->to()
Write-Host "[2/8] Migrando emitTo() → dispatch()->to()..." -ForegroundColor Yellow
$pattern2 = '\$this->emitTo\(([^,]+),\s*([^)]+)\)'
if ($content -match $pattern2) {
    $content = $content -replace '\$this->emitTo\(([^,]+),\s*([^)]+)\)', '$this->dispatch($2)->to($1)'
    $cambios++
    Write-Host "    ✓ Migrados emitTo() → dispatch()->to()" -ForegroundColor Green
}

# 3. Migrar $this->emitSelf() → $this->dispatch()->self()
Write-Host "[3/8] Migrando emitSelf() → dispatch()->self()..." -ForegroundColor Yellow
$pattern3 = '\$this->emitSelf\('
if ($content -match $pattern3) {
    $content = $content -replace '\$this->emitSelf\(([^)]+)\)', '$this->dispatch($1)->self()'
    $cambios++
    Write-Host "    ✓ Migrados emitSelf() → dispatch()->self()" -ForegroundColor Green
}

# 4. Migrar $this->emitUp() → $this->dispatch()->up()
Write-Host "[4/8] Migrando emitUp() → dispatch()->up()..." -ForegroundColor Yellow
$pattern4 = '\$this->emitUp\('
if ($content -match $pattern4) {
    $content = $content -replace '\$this->emitUp\(([^)]+)\)', '$this->dispatch($1)->up()'
    $cambios++
    Write-Host "    ✓ Migrados emitUp() → dispatch()->up()" -ForegroundColor Green
}

# 5. Agregar use statements si es necesario
Write-Host "[5/8] Verificando use statements..." -ForegroundColor Yellow
$needsAttributes = $false

# Verificar si necesita Livewire\Attributes
if ($content -match 'protected \$listeners' -or 
    $content -match 'protected \$rules' -or
    $content -match 'public function get\w+Property') {
    $needsAttributes = $true
}

if ($needsAttributes -and $content -notmatch 'use Livewire\\Attributes') {
    # Encontrar la línea después de namespace
    $content = $content -replace '(namespace [^;]+;)', "`$1`n`nuse Livewire\Attributes\Locked;`nuse Livewire\Attributes\On;`nuse Livewire\Attributes\Computed;`nuse Livewire\Attributes\Validate;"
    $cambios++
    Write-Host "    ✓ Agregados use statements de Attributes" -ForegroundColor Green
}

# 6. Comentar protected $listeners (necesita revisión manual)
Write-Host "[6/8] Comentando \$listeners (requiere migración manual)..." -ForegroundColor Yellow
$pattern6 = 'protected \$listeners'
if ($content -match $pattern6) {
    $content = $content -replace '(\s+)(protected \$listeners[^;]+;)', "`$1// TODO LIVEWIRE 4: Migrar a #[On('evento')] - Ver docs`n`$1// `$2"
    $cambios++
    Write-Host "    ⚠ \$listeners comentado - REQUIERE REVISIÓN MANUAL" -ForegroundColor Yellow
}

# 7. Comentar protected $rules (necesita revisión manual)
Write-Host "[7/8] Comentando \$rules (requiere migración manual)..." -ForegroundColor Yellow
$pattern7 = 'protected \$rules'
if ($content -match $pattern7) {
    $content = $content -replace '(\s+)(protected \$rules[^;]+;)', "`$1// TODO LIVEWIRE 4: Migrar a #[Validate('rules')] - Ver docs`n`$1// `$2"
    $cambios++
    Write-Host "    ⚠ \$rules comentado - REQUIERE REVISIÓN MANUAL" -ForegroundColor Yellow
}

# 8. Comentar getXxxProperty (necesita revisión manual)
Write-Host "[8/8] Comentando computed properties (requiere migración manual)..." -ForegroundColor Yellow
$pattern8 = 'public function (get\w+Property)\('
if ($content -match $pattern8) {
    $content = $content -replace '(\s+)(public function get(\w+)Property\(\))', "`$1// TODO LIVEWIRE 4: Migrar a #[Computed] public function `$3() - Ver docs`n`$1`$2"
    $cambios++
    Write-Host "    ⚠ Computed properties comentadas - REQUIERE REVISIÓN MANUAL" -ForegroundColor Yellow
}

# Guardar cambios
if ($cambios -gt 0) {
    $content | Set-Content $FilePath -NoNewline
    Write-Host ""
    Write-Host "==================================================" -ForegroundColor Cyan
    Write-Host "           Migración Completada" -ForegroundColor Green
    Write-Host "==================================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Cambios aplicados: $cambios" -ForegroundColor Green
    Write-Host "Archivo migrado: $FilePath" -ForegroundColor Green
    Write-Host "Backup en: $backupPath" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "⚠  IMPORTANTE: Revisar TODOs manualmente" -ForegroundColor Yellow
    Write-Host "⚠  Ejecutar tests: php artisan test --filter=NombreTest" -ForegroundColor Yellow
    Write-Host ""
} else {
    Write-Host ""
    Write-Host "No se encontraron patrones para migrar." -ForegroundColor Green
    Remove-Item $backupPath
    Write-Host "Backup eliminado (no era necesario)." -ForegroundColor Cyan
    Write-Host ""
}

Write-Host "==================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Próximos pasos:" -ForegroundColor White
Write-Host "1. Abrir el archivo y revisar los comentarios TODO" -ForegroundColor White
Write-Host "2. Completar migraciones manuales requeridas" -ForegroundColor White
Write-Host "3. Ejecutar tests del módulo" -ForegroundColor White
Write-Host "4. Si todo pasa, commit los cambios" -ForegroundColor White
Write-Host ""
