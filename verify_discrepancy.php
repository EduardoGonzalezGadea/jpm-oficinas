<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tesoreria\TesMultasCobradas;
use App\Models\Tesoreria\TesMultasItems;


$output = "Iniciando verificación de consistencia de datos...\n";

$discrepancias = [];
$totalMontoHeader = 0;
$totalMontoItems = 0;

// Procesamos en trozos para no saturar memoria
TesMultasCobradas::whereBetween('fecha', ['2026-01-01', '2026-01-31'])
    ->with('items')
    ->chunk(100, function ($cobradas) use (&$discrepancias, &$totalMontoHeader, &$totalMontoItems) {
        foreach ($cobradas as $cobrada) {
            $sumItems = $cobrada->items->sum('importe');
            $montoHeader = $cobrada->monto;

            // Normalizamos float para comparación segura
            $sumItems = round($sumItems, 2);
            $montoHeader = round($montoHeader, 2);

            $totalMontoHeader += $montoHeader;
            $totalMontoItems += $sumItems;

            if (abs($montoHeader - $sumItems) > 0.01) {
                $discrepancias[] = [
                    'id' => $cobrada->id,
                    'fecha' => $cobrada->fecha->toDateString(),
                    'recibo' => $cobrada->recibo,
                    'monto_header' => $montoHeader,
                    'sum_items' => $sumItems,
                    'diff' => $montoHeader - $sumItems,
                    'items_count' => $cobrada->items->count()
                ];
            }
        }
    });

$output .= "--------------------------------------------------\n";
$output .= "RESULTADOS DE VERIFICACIÓN\n";
$output .= "--------------------------------------------------\n";
$output .= "Total Registros Analizados: " . TesMultasCobradas::count() . "\n";
$output .= "Total 'Monto' (Headers):    " . number_format($totalMontoHeader, 2) . "\n";
$output .= "Total 'Importe' (Items):    " . number_format($totalMontoItems, 2) . "\n";
$output .= "Diferencia Global:          " . number_format($totalMontoHeader - $totalMontoItems, 2) . "\n";
$output .= "--------------------------------------------------\n";
$output .= "Registros con discrepancias: " . count($discrepancias) . "\n";

if (count($discrepancias) > 0) {
    $output .= "\nDetalle de las primeras 10 discrepancias:\n";
    foreach (array_slice($discrepancias, 0, 10) as $d) {
        $output .= "ID: {$d['id']} | Fecha: {$d['fecha']} | Recibo: {$d['recibo']} | Header: {$d['monto_header']} | Items: {$d['sum_items']} | Diff: {$d['diff']} | Cant. Items: {$d['items_count']}\n";
    }
} else {
    $output .= "No se encontraron discrepancias entre Monto y Suma de Items.\n";
}

$output .= "--------------------------------------------------\n";

file_put_contents(__DIR__ . '/verification_result.txt', $output);
echo "Verificación completada. Resultados en verification_result.txt\n";
