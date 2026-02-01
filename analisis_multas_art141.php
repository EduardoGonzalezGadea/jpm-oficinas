<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ANÁLISIS DE MULTAS: ART.141 ===\n";
echo "Rango: 22/01/2026 - 29/01/2026\n\n";

$concepto = 'ART.141 NO PORTAR LIC.COND. DOC. IDENT. VEHICULO O NEGAR /PR';

$multas = \App\Models\Tesoreria\TesMultasCobradas::whereDate('fecha', '>=', '2026-01-22')
    ->whereDate('fecha', '<=', '2026-01-29')
    ->where('descripcion', 'LIKE', '%' . substr($concepto, 0, 20) . '%') // Búsqueda parcial por si hay variaciones leves
    ->get();

if ($multas->count() == 0) {
    echo "No se encontraron registros para este concepto en las fechas indicadas.\n";
    exit;
}

echo "Se encontraron " . $multas->count() . " registros.\n\n";

$agrupados = [];

foreach ($multas as $multa) {
    echo "ID: {$multa->id}\n";
    echo "Recibo: {$multa->numero_recibo}\n";
    echo "Fecha: {$multa->fecha}\n";
    echo "Descripción: {$multa->descripcion}\n";
    echo "Monto: {$multa->monto}\n";
    echo "Adicional: {$multa->adicional}\n";
    echo "Adenda: {$multa->adenda}\n";
    echo str_repeat("-", 40) . "\n";

    // Agrupar por descripción exacta y monto para ver diferencias
    $key = $multa->descripcion . '|' . $multa->monto;
    if (!isset($agrupados[$key])) {
        $agrupados[$key] = 0;
    }
    $agrupados[$key]++;
}

echo "\n=== RESUMEN DE VARIACIONES ===\n";
foreach ($agrupados as $key => $count) {
    list($desc, $monto) = explode('|', $key);
    echo "Descripción: $desc\n";
    echo "Monto: $monto\n";
    echo "Cantidad: $count\n\n";
}
