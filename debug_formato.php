<?php

require __DIR__.'/vendor/autoload.php';

use App\Services\Tesoreria\MedioPagoService;

$service = new MedioPagoService();

// Test case
$case = 'TARJETA:abc';
$isValid = $service->validarFormato($case);

echo "Case: \"$case\" -> " . ($isValid ? 'Valid' : 'Invalid') . "\n";
echo "is_numeric('abc'): " . var_export(is_numeric('abc'), true) . "\n";
echo "preg_match('/^\\d+(\\.\\d{1,2})?$/', 'abc'): " . preg_match('/^\d+(\.\d{1,2})?$/', 'abc') . "\n";

// Test other cases
echo "\n---\n";
$case2 = 'TARJETA:123';
$isValid2 = $service->validarFormato($case2);
echo "Case: \"$case2\" -> " . ($isValid2 ? 'Valid' : 'Invalid') . "\n";
echo "is_numeric('123'): " . var_export(is_numeric('123'), true) . "\n";
echo "preg_match('/^\\d+(\\.\\d{1,2})?$/', '123'): " . preg_match('/^\d+(\.\d{1,2})?$/', '123') . "\n";
