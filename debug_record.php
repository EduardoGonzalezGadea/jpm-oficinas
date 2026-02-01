<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== UN REGISTRO DE TES_MULTAS_COBRADAS ===\n";
$multa = \App\Models\Tesoreria\TesMultasCobradas::first();
if ($multa) {
    print_r($multa->toArray());
} else {
    echo "La tabla está vacía.\n";
}
