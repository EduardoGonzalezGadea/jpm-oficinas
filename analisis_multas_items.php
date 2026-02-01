<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ANÁLISIS DE MULTAS: ART.141 (Por Items) ===\n";
echo "Rango: 22/01/2026 - 29/01/2026\n\n";

$concepto = 'ART.141'; // Búsqueda más amplia para asegurar coincidencias

$items = \App\Models\Tesoreria\TesMultasItems::where('descripcion', 'LIKE', "%$concepto%")
    ->orWhere('detalle', 'LIKE', "%$concepto%")
    ->with('cobrada')
    ->get();

$encontrados = 0;
$agrupados = [];

foreach ($items as $item) {
    $multa = $item->cobrada;

    // Validar rango de fechas
    if (!$multa || $multa->fecha->format('Y-m-d') < '2026-01-22' || $multa->fecha->format('Y-m-d') > '2026-01-29') {
        continue;
    }

    $encontrados++;

    echo "ID Item: {$item->id}\n";
    echo "Recibo: {$multa->recibo}\n"; // Campo 'recibo' según modelo TesMultasCobradas
    echo "Fecha: {$multa->fecha->format('d/m/Y')}\n";
    echo "Descripción Item: {$item->descripcion}\n";
    echo "Detalle Item: {$item->detalle}\n";
    echo "Importe Item: {$item->importe}\n";
    echo "Adicional Multa: {$multa->adicional}\n";
    echo "Adenda Multa: {$multa->adenda}\n";
    echo str_repeat("-", 40) . "\n";

    // Agrupar para resumen
    $desc = $item->descripcion ?: $item->detalle;
    $key = trim($desc) . '|' . $item->importe;
    if (!isset($agrupados[$key])) {
        $agrupados[$key] = [];
    }
    $agrupados[$key][] = $multa->recibo;
}

if ($encontrados == 0) {
    echo "No se encontraron registros en el rango de fechas.\n";
} else {
    echo "\n=== RESUMEN DE VARIACIONES ===\n";
    foreach ($agrupados as $key => $recibos) {
        list($desc, $monto) = explode('|', $key);
        echo "Concepto: $desc\n";
        echo "Monto: $monto\n";
        echo "Cantidad: " . count($recibos) . "\n";
        echo "Recibos: " . implode(', ', $recibos) . "\n\n";
    }
}
