<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$tables = [
    'tes_tenencia_armas',
    'tes_porte_armas',
    'tes_prendas',
    'tes_eventuales',
    'tes_arrendamientos',
    'tes_deposito_vehiculos',
    'tes_multas_cobradas'
];

foreach ($tables as $table) {
    echo "--- TABLE: $table ---\n";
    if (Schema::hasTable($table)) {
        $columns = Schema::getColumnListing($table);
        foreach ($columns as $column) {
            $type = Schema::getColumnType($table, $column);
            echo "  $column: $type\n";
        }
    } else {
        echo "  Table does not exist!\n";
    }
}
