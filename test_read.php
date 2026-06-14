<?php
require __DIR__.'/vendor/autoload.php';

$files = [
    'C:\DESARROLLO\CFEs\CFE_214988770019_101_A_2710.pdf',
    'C:\DESARROLLO\CFEs\CFE_214988770019_111_A_1052.pdf',
];

$service = new \App\Services\Tesoreria\CfeUniversalParserService();

foreach ($files as $file) {
    if (!file_exists($file)) { echo "$file no encontrado\n"; continue; }
    echo "=== " . basename($file) . " ===\n";
    $datos = $service->parsePdf($file);
    echo "REFERENCIAS: " . ($datos['referencias'] ?: '(vacío)') . "\n";
    echo "ADENDA:      " . ($datos['adenda'] ?: '(vacío)') . "\n";
    echo "\n";
}
