<?php

namespace App\Services\Tesoreria;

use PhpOffice\PhpSpreadsheet\IOFactory;

class CargaMasivaHaberesService
{
    protected array $resumen = [];
    protected array $detalle = [];
    protected array $totalesPorTipo = [];
    protected array $errores = [];
    protected ?string $monthOverride = null;

    public function procesarCarpeta(string $ruta, ?string $monthOverride = null): array
    {
        if (!is_dir($ruta)) {
            throw new \InvalidArgumentException("La ruta no es una carpeta válida: $ruta");
        }

        $this->resumen = [];
        $this->detalle = [];
        $this->totalesPorTipo = [];
        $this->errores = [];
        $this->monthOverride = $monthOverride;

        $archivos = $this->scanArchivos($ruta);

        foreach ($archivos as $archivo) {
            try {
                $this->procesarArchivo($archivo);
            } catch (\Exception $e) {
                $this->errores[] = [
                    'archivo' => $archivo['relative'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Sort totals by type
        uasort($this->totalesPorTipo, fn($a, $b) => strcmp($a['tipo'], $b['tipo']));

        return [
            'resumen' => $this->resumen,
            'detalle' => $this->detalle,
            'totales' => array_values($this->totalesPorTipo),
            'errores' => $this->errores,
        ];
    }

    protected function scanArchivos(string $ruta): array
    {
        $archivos = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($ruta, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $ext = strtolower($file->getExtension());
                if (!in_array($ext, ['xls', 'xlsx', 'ods'])) {
                    continue;
                }

                $relative = str_replace($ruta, '', $file->getPathname());
                $relative = ltrim($relative, '\\/');

                $pathParts = explode(DIRECTORY_SEPARATOR, $file->getPath());
                $monthFolder = $this->detectMonthFolder($pathParts);

                $archivos[] = [
                    'path' => $file->getPathname(),
                    'relative' => $relative,
                    'filename' => $file->getFilename(),
                    'month_folder' => $monthFolder,
                    'subfolder' => count($pathParts) > 0 ? end($pathParts) : '',
                ];
            }
        }

        // Sort by folder then filename
        usort($archivos, fn($a, $b) => [$a['month_folder'], $a['filename']] <=> [$b['month_folder'], $b['filename']]);

        return $archivos;
    }

    protected function detectMonthFolder(array $pathParts): string
    {
        $months = [
            'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO',
            'JULIO', 'AGOSTO', 'SETIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE',
        ];

        foreach ($pathParts as $part) {
            $upper = mb_strtoupper($part);
            foreach ($months as $month) {
                if (str_contains($upper, $month)) {
                    return $part;
                }
            }
        }

        return end($pathParts) ?: 'DESCONOCIDO';
    }

    protected function procesarArchivo(array $archivo): void
    {
        $spreadsheet = IOFactory::load($archivo['path']);
        $worksheet = $spreadsheet->getActiveSheet();
        $data = $worksheet->toArray(null, true, false);

        if (count($data) < 3) {
            return;
        }

        $rows = $this->parseDataRows($data, $archivo);
        $tipoPago = $this->classifyPaymentType($archivo['filename']);

        $entry = [
            'archivo' => $archivo['filename'],
            'ruta' => $archivo['relative'],
            'mes' => $this->monthOverride ?? $archivo['month_folder'],
            'tipo' => $tipoPago,
            'pattern' => $rows['pattern'],
            'cantidad_ventanilla' => count($rows['ventanilla']),
            'cantidad_otros' => count($rows['otros']),
            'total_ventanilla' => round(array_sum(array_column($rows['ventanilla'], 'monto')), 2),
            'total_otros' => round(array_sum(array_column($rows['otros'], 'monto')), 2),
            'total_general' => 0,
            'total_ventanilla_excel' => $rows['totalVentanillaExcel'],
        ];
        $entry['total_general'] = round($entry['total_ventanilla'] + $entry['total_otros'], 2);

        $this->resumen[] = $entry;
        $this->detalle = array_merge($this->detalle, $rows['ventanilla'], $rows['otros']);

        if (!isset($this->totalesPorTipo[$tipoPago])) {
            $this->totalesPorTipo[$tipoPago] = [
                'tipo' => $tipoPago,
                'total_ventanilla' => 0,
                'total_otros' => 0,
                'cantidad_ventanilla' => 0,
                'cantidad_otros' => 0,
            ];
        }
        $this->totalesPorTipo[$tipoPago]['total_ventanilla'] += $entry['total_ventanilla'];
        $this->totalesPorTipo[$tipoPago]['total_otros'] += $entry['total_otros'];
        $this->totalesPorTipo[$tipoPago]['cantidad_ventanilla'] += $entry['cantidad_ventanilla'];
        $this->totalesPorTipo[$tipoPago]['cantidad_otros'] += $entry['cantidad_otros'];
    }

    protected function parseDataRows(array $data, array $archivo): array
    {
        $ventanilla = [];
        $otros = [];
        $isRechazo = stripos($archivo['filename'], 'RECHAZO') !== false;
        $totalVentanillaExcel = 0;
        
        $mapping = null;
        $firstPatternName = 'DYNAM';
        
        $keywords = ['CEDULA', 'C.I.', 'IMPORTE', 'NOMBRE', 'APELLIDO', 'CI'];

        foreach ($data as $i => $row) {
            $row = array_map(fn($v) => is_string($v) ? trim($v) : $v, $row);

            if (empty(array_filter($row, fn($v) => $v !== '' && $v !== null))) {
                continue;
            }

            $rowText = mb_strtoupper(implode('|', array_map('strval', $row)));
            
            // Check if this row looks like a header row
            $matches = 0;
            foreach ($keywords as $kw) {
                if (str_contains($rowText, $kw)) {
                    $matches++;
                }
            }
            
            if ($matches >= 2) {
                // Determine column indices
                $ci_col = null;
                $monto_col = null;
                $nombre_col = null;
                $apellido_col = null;
                $nombre_completo_col = null;
                
                foreach ($row as $colIdx => $val) {
                    if ($val === null || $val === '') {
                        continue;
                    }
                    $valUpper = mb_strtoupper((string)$val);
                    
                    if (str_contains($valUpper, 'C.I.') || str_contains($valUpper, 'CEDULA') || $valUpper === 'CI') {
                        if ($ci_col === null) {
                            $ci_col = $colIdx;
                        }
                    }
                    if (str_contains($valUpper, 'IMPORTE') || str_contains($valUpper, 'MONTO')) {
                        if ($monto_col === null) {
                            $monto_col = $colIdx;
                        }
                    }
                    if (str_contains($valUpper, 'NOMBRE Y APELLIDO') || str_contains($valUpper, 'NOMBRE COMPLETO')) {
                        if ($nombre_completo_col === null) {
                            $nombre_completo_col = $colIdx;
                        }
                    } elseif (str_contains($valUpper, 'NOMBRE')) {
                        if ($nombre_col === null) {
                            $nombre_col = $colIdx;
                        }
                    }
                    if (str_contains($valUpper, 'APELLIDO')) {
                        if ($apellido_col === null) {
                            $apellido_col = $colIdx;
                        }
                    }
                }
                
                if ($ci_col !== null && $monto_col !== null) {
                    $mapping = [
                        'ci' => $ci_col,
                        'monto' => $monto_col,
                        'nombre' => $nombre_col,
                        'apellido' => $apellido_col,
                        'nombre_completo' => $nombre_completo_col,
                    ];
                }
                continue; // Skip header row from data parsing
            }

            if (str_contains($rowText, 'TOTAL VENTANILLA')) {
                if ($mapping !== null) {
                    $totalVentRow = $this->extractMonto($row[$mapping['monto']] ?? 0);
                    $totalVentanillaExcel = $totalVentRow ?? 0;
                }
                continue;
            }
            if (str_contains($rowText, 'TOTAL GIROS') ||
                str_contains($rowText, 'TOTAL GENERAL') ||
                str_contains($rowText, 'LISTIN')) {
                continue;
            }

            if ($mapping !== null) {
                $parsed = $this->extractDynamicRowData($row, $mapping);
                if ($parsed === null) {
                    continue;
                }

                $nombreUpper = mb_strtoupper($parsed['nombre']);
                if (str_contains($nombreUpper, 'TOTAL')) {
                    continue;
                }

                $esVentanilla = $this->isVentanilla($parsed, $isRechazo);

                $item = [
                    'archivo' => $archivo['filename'],
                    'mes' => $archivo['month_folder'],
                    'tipo' => $this->classifyPaymentType($archivo['filename']),
                    'ci' => $parsed['ci'],
                    'nombre' => $parsed['nombre'],
                    'monto' => $parsed['monto'],
                    'es_ventanilla' => $esVentanilla,
                ];

                if ($esVentanilla) {
                    $ventanilla[] = $item;
                } else {
                    $otros[] = $item;
                }
            }
        }

        return [
            'ventanilla' => $ventanilla,
            'otros' => $otros,
            'totalVentanillaExcel' => $totalVentanillaExcel,
            'pattern' => $firstPatternName,
        ];
    }

    protected function extractDynamicRowData(array $row, array $mapping): ?array
    {
        $ci = $this->extractCi($row[$mapping['ci']] ?? '');
        $monto = $this->extractMonto($row[$mapping['monto']] ?? 0);

        $nombre = '';
        if ($mapping['nombre'] !== null && $mapping['apellido'] !== null) {
            $nombre = trim(($row[$mapping['nombre']] ?? '') . ' ' . ($row[$mapping['apellido']] ?? ''));
        } elseif ($mapping['nombre_completo'] !== null) {
            $ncCol = $mapping['nombre_completo'];
            if ($mapping['monto'] === $ncCol + 1) {
                $nombre = $row[$ncCol] ?? '';
            } else {
                $nombre = trim(($row[$ncCol] ?? '') . ' ' . ($row[$ncCol + 1] ?? ''));
            }
        } elseif ($mapping['nombre'] !== null) {
            $nCol = $mapping['nombre'];
            if ($mapping['monto'] === $nCol + 1) {
                $nombre = $row[$nCol] ?? '';
            } else {
                $nombre = trim(($row[$nCol] ?? '') . ' ' . ($row[$nCol + 1] ?? ''));
            }
        }

        if ($ci === null && empty($nombre)) {
            return null;
        }

        if ($monto === null || $monto <= 0) {
            return null;
        }

        $medioPago = isset($row[$mapping['monto'] + 1]) ? trim((string)$row[$mapping['monto'] + 1]) : '';

        return compact('ci', 'nombre', 'monto', 'medioPago');
    }

    protected function extractCi($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $ci = preg_replace('/[^0-9]/', '', (string) $value);
        return $ci !== '' ? $ci : null;
    }

    protected function extractMonto($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Si ya es numérico nativo (int/float de PhpSpreadsheet), retornar directo
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $clean = trim((string) $value);
        // Remover símbolo de moneda, espacios normales y no-breaking spaces
        $clean = str_replace(['$', ' ', "\xc2\xa0", "\u{00A0}"], '', $clean);

        if ($clean === '') {
            return null;
        }

        // Detectar negativos entre paréntesis: (1.234,56)
        $negative = str_starts_with($clean, '(') && str_ends_with($clean, ')');
        $clean = trim($clean, '()');

        // Formato uruguayo: puntos como separadores de millares, coma como decimal.
        // Ejemplos: "1.234.567,89" → 1234567.89 | "1.234.567" → 1234567 | "1.234" → 1234
        //
        // Estrategia: si hay coma, es decimal uruguayo → quitar puntos, cambiar coma a punto.
        // Si solo hay puntos, determinar si son millares:
        //   - Múltiples puntos → son millares (ej: "1.234.567")
        //   - Un solo punto con exactamente 3 dígitos después → es millar (ej: "1.234" = 1234)
        //   - Un solo punto con != 3 dígitos después → es decimal anglosajón (ej: "1.5" = 1.5)

        $cantPuntos = substr_count($clean, '.');
        $tieneComa = str_contains($clean, ',');

        if ($tieneComa) {
            // Formato UY con decimales: quitar puntos de millar, coma → punto decimal
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif ($cantPuntos > 0) {
            // Solo puntos, sin coma
            if ($cantPuntos > 1) {
                // Múltiples puntos = separadores de millar (ej: "1.234.567")
                $clean = str_replace('.', '', $clean);
            } else {
                // Un solo punto: verificar si es millar o decimal
                $partes = explode('.', $clean);
                if (strlen($partes[1]) === 3) {
                    // Exactamente 3 dígitos después del punto → separador de millar (ej: "1.234" = 1234)
                    $clean = str_replace('.', '', $clean);
                }
                // Si no tiene 3 dígitos después, se deja como decimal (ej: "1.5" = 1.5)
            }
        }

        if (is_numeric($clean)) {
            $monto = (float) $clean;
            return $negative ? -$monto : $monto;
        }

        return null;
    }

    protected function isVentanilla(array $parsed, bool $isRechazo): bool
    {
        if ($isRechazo) {
            return true;
        }

        $medioPago = trim($parsed['medioPago'] ?? '');
        if ($medioPago === '') {
            return true;
        }

        return false;
    }

    protected function classifyPaymentType(string $filename): string
    {
        $upper = mb_strtoupper($filename);

        // More specific matches first
        if (str_contains($upper, 'EV. INAU')) return 'EV. INAU';
        if (str_contains($upper, 'EV. ASSE')) return 'EV. ASSE';
        if (str_contains($upper, 'EV. MIDES')) return 'EV. MIDES';
        if (str_contains($upper, 'EV. IMM')) return 'EV. IMM';
        if (str_contains($upper, 'ART. 222') || str_contains($upper, 'ART.222') || str_contains($upper, 'AR.2222')) return 'ART. 222';
        if (str_contains($upper, 'LICENCIA')) return 'LICENCIA';
        if (str_contains($upper, 'AGUINALDO') || str_contains($upper, 'AGLDO')) return 'AGUINALDO';
        if (str_contains($upper, 'PRESUPUESTADOS')) return 'PRESUPUESTADOS';
        if (str_contains($upper, 'PADO')) return 'PADO';
        if (str_contains($upper, 'STIP')) return 'STIP';
        if (str_contains($upper, 'RECHAZO')) return 'RECHAZO';
        if (str_contains($upper, 'RJ')) return 'RJ';
        if (str_contains($upper, 'VIATICOS')) return 'VIÁTICOS';
        if (str_contains($upper, 'IMPAGOS')) return 'IMPAGOS';

        return 'OTROS';
    }
}
