<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tesoreria\TesMultasCobradas;
use App\Models\Tesoreria\TesMultasItems;

$dateFrom = '2026-01-01';
$dateTo = '2026-01-31';


$output = "Comparing Queries for range: $dateFrom to $dateTo\n";

// Query A: Service Logic (Resumen)
$itemsQuery = TesMultasItems::whereHas('cobrada', function ($q) use ($dateFrom, $dateTo) {
    $q->whereBetween('fecha', [$dateFrom, $dateTo]);
})->with('cobrada');

$items = $itemsQuery->get();
$sumItems = $items->sum('importe');
$countItems = $items->count();
$uniqueCobradasA = $items->pluck('cobrada.id')->unique()->sort()->values();

$output .= "Query A (Service/Resumen - Items):\n";
$output .= "  Count Items: $countItems\n";
$output .= "  Sum Importe: " . number_format($sumItems, 2) . "\n";
$output .= "  Unique Cobradas IDs: " . $uniqueCobradasA->count() . "\n";


// Query B: Controller Logic (Medios de Pago)
$cobradasQuery = TesMultasCobradas::whereDate('fecha', '>=', $dateFrom)
    ->whereDate('fecha', '<=', $dateTo);

$cobradas = $cobradasQuery->get();
$sumMonto = $cobradas->sum('monto');
$countCobradas = $cobradas->count();
$uniqueCobradasB = $cobradas->pluck('id')->unique()->sort()->values();

$output .= "Query B (Controller/Medios - Headers):\n";
$output .= "  Count Headers: $countCobradas\n";
$output .= "  Sum Monto: " . number_format($sumMonto, 2) . "\n";


// Comparison
$output .= "--------------------------------------------------\n";
$diffSum = $sumItems - $sumMonto;
$output .= "Difference (SumA - SumB): " . number_format($diffSum, 2) . "\n";

$diffA = $uniqueCobradasA->diff($uniqueCobradasB);
$diffB = $uniqueCobradasB->diff($uniqueCobradasA);

if ($diffA->count() > 0) {
    $output .= "IDs present in Service but NOT in Controller: " . $diffA->implode(', ') . "\n";
} else {
    $output .= "All IDs from Service are in Controller.\n";
}

if ($diffB->count() > 0) {
    $output .= "IDs present in Controller but NOT in Service (" . $diffB->count() . "): " . $diffB->implode(', ') . "\n";
} else {
    $output .= "All IDs from Controller are in Service.\n";
}

file_put_contents(__DIR__ . '/debug_queries_result.txt', $output);
echo "Result written to debug_queries_result.txt\n";
