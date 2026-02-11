<?php

require __DIR__.'/vendor/autoload.php';

use App\Services\Tesoreria\MedioPagoService;
use Tests\Unit\Services\Tesoreria\MedioPagoServiceSimpleTest;

$test = new MedioPagoServiceSimpleTest();
$test->setUp();
$service = new MedioPagoService();

$mediosInvalidos = [
    'EFECTIVO|TARJETA',
    'EFECTIVO:TARJETA',
    'EFECTIVO/1000',
    'TARJETA:abc',
];

echo "=== Testing invalid formats ===" . PHP_EOL;
foreach ($mediosInvalidos as $medio) {
    try {
        $isValid = $service->validarFormato($medio);
        echo "Case: \"$medio\" -> " . ($isValid ? 'Valid' : 'Invalid') . PHP_EOL;
    } catch (Exception $e) {
        echo "Case: \"$medio\" -> Error: " . $e->getMessage() . PHP_EOL;
    }
}
