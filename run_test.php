<?php

require __DIR__.'/vendor/autoload.php';

use App\Services\Tesoreria\MedioPagoService;
use Tests\Unit\Services\Tesoreria\MedioPagoServiceSimpleTest;

// Create a new instance of the service
$service = new MedioPagoService();

// Get all the test cases from the test file
$test = new class extends MedioPagoServiceSimpleTest {
    public function runTest($methodName)
    {
        $this->setUp();
        $result = $this->$methodName();
        $this->tearDown();
        return $result;
    }
};

echo "=== Running test_validacion_formato_invalido ===" . PHP_EOL;
try {
    $test->test_validacion_formato_invalido();
    echo "✓ Test passed" . PHP_EOL;
} catch (Exception $e) {
    echo "✗ Test failed: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL . "=== Running debug on each case ===" . PHP_EOL;
$mediosInvalidos = [
    'EFECTIVO|TARJETA',
    'EFECTIVO:TARJETA',
    'EFECTIVO/1000',
    'TARJETA:abc',
];

foreach ($mediosInvalidos as $medio) {
    $isValid = $service->validarFormato($medio);
    echo "Case: \"$medio\" -> " . ($isValid ? 'VALID' : 'INVALID') . PHP_EOL;
}
