<?php

require __DIR__.'/vendor/autoload.php';

use App\Services\Tesoreria\MedioPagoService;

$service = new MedioPagoService();

// Test case that's failing
$testCase = 'TARJETA:abc';
$isValid = $service->validarFormato($testCase);
echo "Test case '$testCase' is " . ($isValid ? 'VALID' : 'INVALID') . "\n";

// Verify what's happening
$partes = explode('/', $testCase);
echo "Partes: " . print_r($partes, true) . "\n";

foreach ($partes as $parte) {
    $parte = trim($parte);
    echo "Parte procesada: '$parte'\n";

    $datos = explode(':', $parte);
    echo "Datos: " . print_r($datos, true) . "\n";

    $nombre = trim($datos[0]);
    echo "Nombre: '$nombre'\n";

    $valor = trim($datos[1]);
    echo "Valor: '$valor'\n";

    echo "is_numeric(): " . var_export(is_numeric($valor), true) . "\n";
    echo "preg_match(): " . preg_match('/^\d+(\.\d{1,2})?$/', $valor) . "\n";
}
