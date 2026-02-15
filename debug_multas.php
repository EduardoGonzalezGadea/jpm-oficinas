<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$fecha = '2026-02-12';
$multas = \App\Models\Tesoreria\TesMultasCobradas::whereDate('fecha', $fecha)->with('items')->get();
$totalCabecera = $multas->sum('monto');
$totalItems = $multas->flatMap->items->sum('importe');

echo "Total Cabecera: $totalCabecera\n";
echo "Total Items: $totalItems\n";

foreach ($multas as $m) {
    $sumItems = $m->items->sum('importe');
    if (abs($m->monto - $sumItems) > 0.01) {
        echo "Inconsistencia ID {$m->id} Recibo {$m->recibo}: Cabecera {$m->monto} vs Items {$sumItems}\n";
    }
}
