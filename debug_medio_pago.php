<?php

require __DIR__.'/vendor/autoload.php';

use App\Services\Tesoreria\MedioPagoService;

$service = new MedioPagoService();

// Test cases
$testCases = [
    'EFECTIVO',
    'TARJETA',
    'EFECTIVO / TARJETA DE DÉBITO',
    'TARJETA DE DÉBITO / EFECTIVO',
    'EFECTIVO:1000/TARJETA:500',
    'TARJETA:abc', // Should fail
    'EFECTIVO|TARJETA', // Should fail
    'TARJETA:', // Should fail
    ':1000', // Should fail
];

foreach ($testCases as $case) {
    $isValid = $service->validarFormato($case);
    echo "Case: \"$case\" -> " . ($isValid ? 'Valid' : 'Invalid') . "\n";
}
