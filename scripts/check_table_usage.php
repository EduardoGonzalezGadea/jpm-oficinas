<?php
/**
 * Escanea todo el código fuente del proyecto buscando referencias a cada tabla de la BD.
 * Determina qué tablas no se usan en ningún archivo del proyecto.
 */

$tables = [
    'activity_log', 'external_download_logs', 'failed_jobs', 'migrations',
    'model_has_permissions', 'model_has_roles', 'modulos', 'password_resets',
    'permissions', 'personal_access_tokens', 'roles', 'role_has_permissions',
    'sessions', 'tes_anulaciones', 'tes_arrendamientos', 'tes_arr_planillas',
    'tes_bancos', 'tes_cajas_diarias', 'tes_caja_chica', 'tes_caja_conceptos',
    'tes_caja_movimientos', 'tes_cch_acreedores', 'tes_cch_dependencias',
    'tes_cch_movimientos', 'tes_cch_pagos', 'tes_cch_pendientes',
    'tes_certificados_residencia', 'tes_cfes', 'tes_cfe_items',
    'tes_cfe_medios_pago', 'tes_cfe_pendientes', 'tes_cheques',
    'tes_conceptos', 'tes_cuentas_bancarias', 'tes_denominaciones_monedas',
    'tes_deposito_vehiculos', 'tes_deposito_vehiculo_planillas',
    'tes_desglose_monedas', 'tes_distribuciones_er', 'tes_entregas_libretas_valores',
    'tes_er_definiciones', 'tes_er_definicion_conceptos', 'tes_estados_caja',
    'tes_estados_deposito', 'tes_estados_er', 'tes_estados_recaudacion',
    'tes_estados_recaudacion_detalles', 'tes_eventuales', 'tes_eventuales_instituciones',
    'tes_eventuales_planillas', 'tes_instancias_desglose', 'tes_libretas_valores',
    'tes_medios_pago_caja', 'tes_medio_de_pagos', 'tes_multas', 'tes_multas_303_2023',
    'tes_multas_cobradas', 'tes_multas_items', 'tes_planillas_cheques',
    'tes_porte_armas', 'tes_porte_armas_planillas', 'tes_prendas', 'tes_prendas_planillas',
    'tes_servicios', 'tes_servicio_tipo_libreta', 'tes_tarjetas_cobro_brou',
    'tes_tenencia_armas', 'tes_tenencia_armas_planillas', 'tes_tipos_libretas',
    'tes_tipos_monedas', 'tes_tipos_movimiento', 'users'
];

$baseDir = __DIR__ . '/..';

// Get all source files
$extensions = ['php', 'blade.php', 'vue', 'js', 'json', 'md', 'xml', 'yml', 'yaml', 'env'];
$files = [];

foreach ($extensions as $ext) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $fname = $file->getFilename();
            // Check extension
            if (str_ends_with($fname, ".$ext") || str_ends_with($fname, ".$ext.example")) {
                // Skip vendor, node_modules, storage, bootstrap/cache
                $path = $file->getPathname();
                $rel = str_replace('\\', '/', substr($path, strlen($baseDir)));
                if (!preg_match('#/(vendor|node_modules|storage|bootstrap/cache)/#', $rel)) {
                    $files[] = $path;
                }
            }
        }
    }
}

echo "Total archivos a escanear: " . count($files) . "\n\n";

$results = [];

foreach ($tables as $table) {
    $foundIn = [];
    
    // Variantes de búsqueda para ser exhaustivos
    $patterns = [
        "/\b" . preg_quote($table, '/') . "\b/i",               // nombre exacto (ej: WHERE tabla)
        "/\b" . preg_quote(str_replace('tes_', '', $table), '/') . "\b/i", // sin prefijo tes_
    ];
    
    // También buscar el nombre en CamelCase para modelos
    $camelCase = str_replace('_', '', ucwords(str_replace(['tes_', '_'], ['', ' '], $table)));
    $camelCase = str_replace(' ', '', $camelCase);
    $camelPlural = $camelCase . 's';
    $patterns[] = "/\b" . preg_quote($camelCase, '/') . "\b/";
    $patterns[] = "/\b" . preg_quote($camelPlural, '/') . "\b/";
    
    foreach ($files as $file) {
        // Excluir el propio script y worktrees/agent
        $relPath = str_replace($baseDir . '\\', '', $file);
        $relPath = str_replace('\\', '/', $relPath);
        if (str_contains($relPath, 'scripts/check_table_usage.php')) continue;
        if (str_contains($relPath, '.kilo/worktrees/')) continue;
        if (str_contains($relPath, '.agent/')) continue;
        if (str_contains($relPath, '.agents/')) continue;
        
        $content = file_get_contents($file);
        foreach ($patterns as $pat) {
            if (preg_match($pat, $content)) {
                $foundIn[$relPath] = true;
                break;
            }
        }
    }
    
    $results[$table] = array_keys($foundIn);
}

// Report
echo "=== TABLAS SIN NINGUNA REFERENCIA EN EL CÓDIGO ===\n";
echo "(No aparecen en models, controllers, views, migrations, seeds, config, routes, etc.)\n\n";

$unused = [];
$used = [];

foreach ($results as $table => $refs) {
    if (empty($refs)) {
        $unused[] = $table;
    } else {
        $used[$table] = $refs;
    }
}

if (empty($unused)) {
    echo "✓ TODAS las tablas tienen al menos alguna referencia en el código.\n";
} else {
    echo "✗ " . count($unused) . " tablas sin uso:\n";
    foreach ($unused as $t) {
        echo "   - $t\n";
    }
}

echo "\n\n=== TABLAS CON USO (referencias encontradas) ===\n";
foreach ($used as $table => $refs) {
    echo "\n$table ({$refs[0]})";
    if (count($refs) > 1) {
        echo " (+" . (count($refs)-1) . " más)";
    }
}

echo "\n\n=== DETALLE: Referencias por tabla ===\n\n";
foreach ($used as $table => $refs) {
    echo "--- $table ---\n";
    sort($refs);
    foreach ($refs as $r) {
        echo "   $r\n";
    }
    echo "\n";
}

echo "\n=== TABLAS SIN USO (resumen) ===\n";
foreach ($unused as $t) {
    echo $t . "\n";
}