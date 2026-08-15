# Script para Detectar Patrones de Livewire 3 que Necesitan Migración
# Ejecutar desde la raíz del proyecto: .\scripts\detectar-patrones-livewire.ps1

Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "   Detector de Patrones Livewire 3 → 4" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host ""

$outputDir = "migracion-reports"
if (-Not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir | Out-Null
}

# 1. Detectar $this->emit()
Write-Host "[1/8] Buscando uso de emit()..." -ForegroundColor Yellow
$emitPattern = '\$this->emit\('
$emitResults = Select-String -Path "app\Livewire\**\*.php" -Pattern $emitPattern -CaseSensitive
$emitResults | Out-File "$outputDir\01-emits.txt"
Write-Host "    Encontrados: $($emitResults.Count) usos de emit()" -ForegroundColor Green

# 2. Detectar $this->emitTo()
Write-Host "[2/8] Buscando uso de emitTo()..." -ForegroundColor Yellow
$emitToPattern = '\$this->emitTo\('
$emitToResults = Select-String -Path "app\Livewire\**\*.php" -Pattern $emitToPattern -CaseSensitive
$emitToResults | Out-File "$outputDir\02-emitTo.txt"
Write-Host "    Encontrados: $($emitToResults.Count) usos de emitTo()" -ForegroundColor Green

# 3. Detectar $this->emitSelf()
Write-Host "[3/8] Buscando uso de emitSelf()..." -ForegroundColor Yellow
$emitSelfPattern = '\$this->emitSelf\('
$emitSelfResults = Select-String -Path "app\Livewire\**\*.php" -Pattern $emitSelfPattern -CaseSensitive
$emitSelfResults | Out-File "$outputDir\03-emitSelf.txt"
Write-Host "    Encontrados: $($emitSelfResults.Count) usos de emitSelf()" -ForegroundColor Green

# 4. Detectar $this->emitUp()
Write-Host "[4/8] Buscando uso de emitUp()..." -ForegroundColor Yellow
$emitUpPattern = '\$this->emitUp\('
$emitUpResults = Select-String -Path "app\Livewire\**\*.php" -Pattern $emitUpPattern -CaseSensitive
$emitUpResults | Out-File "$outputDir\04-emitUp.txt"
Write-Host "    Encontrados: $($emitUpResults.Count) usos de emitUp()" -ForegroundColor Green

# 5. Detectar protected $listeners
Write-Host "[5/8] Buscando uso de protected \$listeners..." -ForegroundColor Yellow
$listenersPattern = 'protected \$listeners'
$listenersResults = Select-String -Path "app\Livewire\**\*.php" -Pattern $listenersPattern -CaseSensitive
$listenersResults | Out-File "$outputDir\05-listeners.txt"
Write-Host "    Encontrados: $($listenersResults.Count) usos de \$listeners" -ForegroundColor Green

# 6. Detectar protected $rules
Write-Host "[6/8] Buscando uso de protected \$rules..." -ForegroundColor Yellow
$rulesPattern = 'protected \$rules'
$rulesResults = Select-String -Path "app\Livewire\**\*.php" -Pattern $rulesPattern -CaseSensitive
$rulesResults | Out-File "$outputDir\06-rules.txt"
Write-Host "    Encontrados: $($rulesResults.Count) usos de \$rules" -ForegroundColor Green

# 7. Detectar public $ (properties sin tipo)
Write-Host "[7/8] Buscando properties públicas sin tipo..." -ForegroundColor Yellow
$publicVarPattern = '^\s+public \$\w+'
$publicVarResults = Select-String -Path "app\Livewire\**\*.php" -Pattern $publicVarPattern
$publicVarResults | Out-File "$outputDir\07-public-properties.txt"
Write-Host "    Encontradas: $($publicVarResults.Count) properties sin tipo" -ForegroundColor Green

# 8. Detectar getXxxProperty() (computed properties)
Write-Host "[8/8] Buscando computed properties (getXxxProperty)..." -ForegroundColor Yellow
$computedPattern = 'public function get\w+Property\(\)'
$computedResults = Select-String -Path "app\Livewire\**\*.php" -Pattern $computedPattern
$computedResults | Out-File "$outputDir\08-computed-properties.txt"
Write-Host "    Encontradas: $($computedResults.Count) computed properties" -ForegroundColor Green

Write-Host ""
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "                   RESUMEN" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan

$totalIssues = $emitResults.Count + $emitToResults.Count + $emitSelfResults.Count + 
               $emitUpResults.Count + $listenersResults.Count + $rulesResults.Count + 
               $publicVarResults.Count + $computedResults.Count

Write-Host ""
Write-Host "Total de patrones encontrados: $totalIssues" -ForegroundColor $(if($totalIssues -gt 0) { "Red" } else { "Green" })
Write-Host ""
Write-Host "Desglose:" -ForegroundColor White
Write-Host "  - emit():              $($emitResults.Count)" -ForegroundColor Cyan
Write-Host "  - emitTo():            $($emitToResults.Count)" -ForegroundColor Cyan
Write-Host "  - emitSelf():          $($emitSelfResults.Count)" -ForegroundColor Cyan
Write-Host "  - emitUp():            $($emitUpResults.Count)" -ForegroundColor Cyan
Write-Host "  - \$listeners:          $($listenersResults.Count)" -ForegroundColor Cyan
Write-Host "  - \$rules:              $($rulesResults.Count)" -ForegroundColor Cyan
Write-Host "  - Properties sin tipo: $($publicVarResults.Count)" -ForegroundColor Cyan
Write-Host "  - Computed props:      $($computedResults.Count)" -ForegroundColor Cyan
Write-Host ""
Write-Host "Reportes guardados en: ./$outputDir/" -ForegroundColor Green
Write-Host ""

# Generar lista de componentes únicos afectados
Write-Host "Generando lista de componentes afectados..." -ForegroundColor Yellow
$allResults = @()
$allResults += $emitResults
$allResults += $emitToResults
$allResults += $emitSelfResults
$allResults += $emitUpResults
$allResults += $listenersResults
$allResults += $rulesResults
$allResults += $publicVarResults
$allResults += $computedResults

$affectedFiles = $allResults | Select-Object -ExpandProperty Path -Unique | Sort-Object
$affectedFiles | Out-File "$outputDir\00-componentes-afectados.txt"
Write-Host "Total de componentes afectados: $($affectedFiles.Count)" -ForegroundColor $(if($affectedFiles.Count -gt 0) { "Red" } else { "Green" })
Write-Host ""

# Crear resumen en formato Markdown
$markdownReport = @"
# Reporte de Migración Livewire 3 → 4

**Fecha**: $(Get-Date -Format "dd/MM/yyyy HH:mm")

## Resumen Ejecutivo

Total de patrones a migrar: **$totalIssues**  
Total de componentes afectados: **$($affectedFiles.Count)**

## Desglose por Patrón

| Patrón | Cantidad | Prioridad |
|--------|----------|-----------|
| \`emit()\` | $($emitResults.Count) | Alta |
| \`emitTo()\` | $($emitToResults.Count) | Alta |
| \`emitSelf()\` | $($emitSelfResults.Count) | Media |
| \`emitUp()\` | $($emitUpResults.Count) | Media |
| \`\$listeners\` | $($listenersResults.Count) | Alta |
| \`\$rules\` | $($rulesResults.Count) | Media |
| Properties sin tipo | $($publicVarResults.Count) | Baja |
| Computed properties | $($computedResults.Count) | Media |

## Componentes Afectados

Total: $($affectedFiles.Count) archivos

``````
$($affectedFiles -join "`n")
``````

## Próximos Pasos

1. Revisar cada archivo en \`migracion-reports/\`
2. Priorizar componentes críticos (con tests)
3. Migrar patrón por patrón
4. Ejecutar tests después de cada cambio

## Archivos de Reporte Generados

- \`00-componentes-afectados.txt\` - Lista de todos los componentes
- \`01-emits.txt\` - Usos de emit()
- \`02-emitTo.txt\` - Usos de emitTo()
- \`03-emitSelf.txt\` - Usos de emitSelf()
- \`04-emitUp.txt\` - Usos de emitUp()
- \`05-listeners.txt\` - Usos de \$listeners
- \`06-rules.txt\` - Usos de \$rules
- \`07-public-properties.txt\` - Properties públicas sin tipo
- \`08-computed-properties.txt\` - Computed properties

---

**Generado por**: Script de detección automática  
**Ubicación**: \`scripts/detectar-patrones-livewire.ps1\`
"@

$markdownReport | Out-File "$outputDir\RESUMEN.md" -Encoding UTF8
Write-Host "Resumen en Markdown generado: $outputDir\RESUMEN.md" -ForegroundColor Green
Write-Host ""
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "           Script completado!" -ForegroundColor Green
Write-Host "==================================================" -ForegroundColor Cyan
