<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== COLUMNAS DE TES_MULTAS_COBRADAS ===\n";
$columns = \Illuminate\Support\Facades\Schema::getColumnListing('tes_multas_cobradas');
print_r($columns);
