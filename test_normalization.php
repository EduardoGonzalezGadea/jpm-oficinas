<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new \App\Services\Tesoreria\MultasNormalizationService();

echo "=== VERIFICACIÓN DE CLASIFICACIÓN ===\n\n";

// Simular items problemáticos con descripciones reales
$casos = [
    [
        'id' => 1,
        'descripcion' => 'MULTAS DE TRANSITO- ART.141 NO PORTAR LIC.COND. DOC. IDENT. VEHICULO O NEGAR /PR 1,000 11.088,00 11.088,00 MULTAS DE TRANSITO- ART.170 CIRCULAR SIN CASCO',
        'detalle' => '',
        'monto' => 3696.00
    ],
    [
        'id' => 2,
        'descripcion' => 'MULTAS DE TRANSITO- ART.141 NO PORTAR LIC.COND. DOC. IDENT. VEHICULO O NEGAR /PR',
        'detalle' => '',
        'monto' => 11088.00
    ]
];

foreach ($casos as $caso) {
    // Crear objeto mock simple
    $item = new \App\Models\Tesoreria\TesMultasItems();
    $item->descripcion = $caso['descripcion'];
    $item->detalle = $caso['detalle'];

    // Usar reflexión para acceder al método privado normalizeItem
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('normalizeItem');
    $method->setAccessible(true);

    $normalized = $method->invoke($service, $item);

    echo "Caso ID: {$caso['id']}\n";
    echo "Descripción Original: " . substr($caso['descripcion'], 0, 100) . "...\n";
    echo "Artículo Detectado: {$normalized['articulo']}\n";
    echo "Apartado Detectado: {$normalized['apartado']}\n";

    $esperado = $caso['id'] == 1 ? '170' : '141';
    echo "Resultado: " . ($normalized['articulo'] == $esperado ? "CORRECTO" : "ERROR (Esperaba $esperado)") . "\n";
    echo str_repeat("-", 40) . "\n";
}
