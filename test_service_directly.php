<?php

require __DIR__.'/vendor/autoload.php';

use App\Services\Tesoreria\MedioPagoService;

echo "=== Testing MedioPagoService ===\n\n";

$service = new MedioPagoService();

// Test 1: Verify normalization of combined payment methods
echo "1. Testing normalization of combined payment methods:\n";
$medio1 = 'EFECTIVO / TARJETA DE DÉBITO';
$medio2 = 'TARJETA DE DÉBITO / EFECTIVO';
$normalizado1 = $service->normalizar($medio1);
$normalizado2 = $service->normalizar($medio2);

echo "   '$medio1' → '$normalizado1'\n";
echo "   '$medio2' → '$normalizado2'\n";
echo "   Both normalized to the same value: " . ($normalizado1 === $normalizado2 ? '✅' : '❌') . "\n";
echo "   Normalization is alphabetical: " . ($normalizado1 === 'EFECTIVO/TARJETA DE DÉBITO' ? '✅' : '❌') . "\n\n";

// Test 2: Verify normalization with values
echo "2. Testing normalization with values:\n";
$medioConValores = 'TARJETA DE DÉBITO:500 / EFECTIVO:1000';
$normalizadoConValores = $service->normalizar($medioConValores);

echo "   '$medioConValores' → '$normalizadoConValores'\n";
echo "   Normalization includes sorted names and proper decimal places: " . ($normalizadoConValores === 'EFECTIVO:1000.00/TARJETA DE DÉBITO:500.00' ? '✅' : '❌') . "\n\n";

// Test 3: Verify format validation
echo "3. Testing format validation:\n";

// Valid formats
$validFormats = [
    'EFECTIVO',
    'TARJETA',
    'CHEQUE',
    'EFECTIVO/TARJETA',
    'EFECTIVO:1000/TARJETA:500',
    'TRANSFERENCIA:2500',
    'PAYPAL',
    'SIN DATOS',
];

foreach ($validFormats as $format) {
    $isValid = $service->validarFormato($format);
    echo "   '$format': " . ($isValid ? '✅ Valid' : '❌ Invalid') . "\n";
}

// Invalid formats
echo "\n";
$invalidFormats = [
    'EFECTIVO|TARJETA',
    'EFECTIVO:TARJETA',
    'EFECTIVO/1000',
    'TARJETA:abc',
];

foreach ($invalidFormats as $format) {
    $isValid = $service->validarFormato($format);
    echo "   '$format': " . ($isValid ? '❌ Valid (should be invalid)' : '✅ Invalid') . "\n";
}

// Test 4: Verify consistency validation
echo "\n4. Testing consistency validation:\n";
$medioCombinado = 'EFECTIVO:1000/TARJETA:500';
$consistente = $service->validarConsistencia($medioCombinado, 1500);
$inconsistente = $service->validarConsistencia($medioCombinado, 2000);

echo "   '$medioCombinado' with total 1500: " . ($consistente ? '✅ Consistent' : '❌ Inconsistent') . "\n";
echo "   '$medioCombinado' with total 2000: " . (!$inconsistente ? '✅ Inconsistent' : '❌ Consistent') . "\n\n";

// Test 5: Verify validation and normalization
echo "5. Testing validation and normalization:\n";
$medioNormalizar = 'TARJETA DE DÉBITO / EFECTIVO';

try {
    $resultado = $service->validarYNormalizar($medioNormalizar);
    echo "   '$medioNormalizar' → '$resultado'\n";
    echo "   Result is valid and normalized: " . ($resultado === 'EFECTIVO/TARJETA DE DÉBITO' ? '✅' : '❌') . "\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 6: Verify validation with invalid format
echo "\n6. Testing validation with invalid format:\n";
$medioInvalido = 'TARJETA:abc';

try {
    $resultado = $service->validarYNormalizar($medioInvalido);
    echo "   ❌ Error: '$medioInvalido' should have failed validation\n";
} catch (Exception $e) {
    echo "   ✅ Validation failed as expected: " . $e->getMessage() . "\n";
}

// Test 7: Verify calculation of values for combined media
echo "\n7. Testing calculation of values for combined media:\n";
$medioCalculo = 'EFECTIVO/TARJETA DE DÉBITO';
$valorTotal = 1500;
$valoresCalculados = $service->calcularValoresMedios($medioCalculo, $valorTotal);

echo "   '$medioCalculo' with total $valorTotal:\n";

foreach ($valoresCalculados as $valor) {
    echo "      " . $valor['nombre'] . ": " . $valor['valor'] . "\n";
}

// Verify the total is correct
$sumCalculado = array_sum(array_column($valoresCalculados, 'valor'));
echo "   Sum of calculated values: $sumCalculado\n";
echo "   Sum is correct: " . (abs($sumCalculado - $valorTotal) < 0.01 ? '✅' : '❌') . "\n";

echo "\n=== All tests completed ===";
