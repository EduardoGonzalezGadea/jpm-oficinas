<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Cargar el autoloader de Composer
require __DIR__ . '/vendor/autoload.php';

// Bootstrapping de Laravel (simplificado para script)
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Columnas en tes_tipos_libretas:\n";
print_r(Schema::getColumnListing('tes_tipos_libretas'));

echo "\nColumnas en tes_libretas_valores:\n";
print_r(Schema::getColumnListing('tes_libretas_valores'));

// Ver si hay algún dato en 'valor' si existe
if (Schema::hasColumn('tes_tipos_libretas', 'valor')) {
    echo "\nDatos de muestra en tes_tipos_libretas.valor:\n";
    $muestras = DB::table('tes_tipos_libretas')->whereNotNull('valor')->limit(5)->pluck('valor');
    print_r($muestras);
} else {
    echo "\nNo existe columna 'valor' en tes_tipos_libretas\n";
}
