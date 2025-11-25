<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$colsTipos = Schema::getColumnListing('tes_tipos_libretas');
$colsLibretas = Schema::getColumnListing('tes_libretas_valores');

echo "JSON_START\n";
echo json_encode([
    'tes_tipos_libretas' => $colsTipos,
    'tes_libretas_valores' => $colsLibretas
], JSON_PRETTY_PRINT);
echo "\nJSON_END\n";
