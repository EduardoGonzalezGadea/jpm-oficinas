<?php

/**
 * Configuración centralizada para descargas de datos desde URLs externas.
 *
 * Incluye:
 * - Unidad Reajustable (UR) desde BPS
 * - Fecha/Hora sincronizada desde APIs públicas
 * - Valores SOA desde BCU
 *
 * Características:
 * - Auto-detección de proxy desde env
 * - Reintentos con exponential backoff
 * - Caché configurable por servicio
 * - Timeouts y validaciones por defecto
 */

return [
    /**
     * Configuración global de descargas
     */
    'global' => [
        // Habilitar/deshabilitar todo el sistema de descargas
        'enabled' => env('EXTERNAL_DOWNLOADS_ENABLED', true),

        // Modo debug - loguea todos los requests/responses
        'debug' => env('EXTERNAL_DOWNLOADS_DEBUG', false),

        // Verificar certificados SSL en producción
        'verify_ssl' => env('EXTERNAL_DOWNLOADS_VERIFY_SSL', false),

        // Timeout por defecto (segundos) para cualquier request
        'timeout_default' => env('EXTERNAL_DOWNLOADS_TIMEOUT', 15),

        // Connect timeout (segundos)
        'connect_timeout' => env('EXTERNAL_DOWNLOADS_CONNECT_TIMEOUT', 10),

        // User-Agent simulado para evitar bloqueos
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',

        // Circuit breaker: si falla N veces, espera X segundos antes de reintentar
        'circuit_breaker' => [
            'enabled' => true,
            'failure_threshold' => 3, // fallos antes de abrir circuito
            'recovery_timeout' => 300, // segundos antes de reintentar (5 min)
            'cache_key_prefix' => 'external_downloads_circuit_',
        ],
    ],

    /**
     * Configuración de PROXY (auto-detectado desde env)
     */
    'proxy' => [
        // Las siguientes variables se leen automáticamente:
        // - HTTP_PROXY
        // - HTTPS_PROXY
        // - NO_PROXY
        //
        // Ejemplo en .env:
        //   HTTP_PROXY=http://proxy.empresa.com:8080
        //   HTTPS_PROXY=https://proxy.empresa.com:8080
        //   NO_PROXY=localhost,127.0.0.1,.local
        //
        // El servicio HttpClientService las detecta y aplica automáticamente.

        'auto_detect' => true,
        'cache_detection' => true, // cachear detección por 1 hora
    ],

    /**
     * Servicio: Unidad Reajustable (UR) - BPS
     */
    'valor_ur' => [
        'enabled' => env('VALOR_UR_ENABLED', true),

        'url' => env(
            'VALOR_UR_URL',
            'https://www.bps.gub.uy/bps/valores.jsp?contentid=5478'
        ),

        // Timeout específico para esta descarga
        'timeout' => 45,

        // Reintentos: 3 intentos totales, cada uno intenta sin proxy y luego con proxy
        'max_retries' => 3,
        'retry_delay_ms' => 1000, // milisegundos entre reintentos (exponencial)

        // Caché: 4 horas (UR no cambia mucho)
        'cache_ttl_minutes' => 240,

        // Validación del valor extraído
        'validation' => [
            'min_value' => 100,
            'max_value' => 10000,
        ],

        // Selector/regex para extraer el valor del HTML
        // Se usa en ValorUrService para parsear la respuesta
        'parser' => [
            'type' => 'regex', // 'regex' o 'dom' (por ahora solo regex)
            'pattern' => '/Vigencia:\s*(\d+\/\d+\/\d+).*?\$\s*([\d\.,]+)/is',
            'value_group' => 2, // grupo regex que contiene el valor numérico
            'decimal_separator' => ',',
        ],

        // Si descarga falla, usar último valor en caché (aunque sea viejo)
        'fallback_to_old_cache' => true,
    ],

    /**
     * Servicio: Sincronización de Fecha/Hora
     */
    'sincronizacion_hora' => [
        'enabled' => env('SINCRONIZACION_HORA_ENABLED', true),

        // URLs de APIs de tiempo (en orden de preferencia)
        'urls' => [
            'https://worldtimeapi.org/api/timezone/America/Montevideo',
            'https://timeapi.io/api/Time/current/zone?timeZone=America/Montevideo',
            'http://worldtimeapi.org/api/timezone/America/Montevideo', // fallback HTTP
        ],

        // Timeout específico para esta descarga
        'timeout' => 20, // Aumentado a 20s (APIs de tiempo pueden ser lentas)

        // Reintentos: 2 intentos (+ fallback automático de HttpClientService)
        'max_retries' => 2,
        'retry_delay_ms' => 1000, // Aumentado a 1s entre reintentos

        // Caché: 10 minutos (compromiso entre actualización y evitar reintentos constantes)
        'cache_ttl_minutes' => 10,

        // Validación del timestamp sincronizado
        'validation' => [
            // Máxima desviación respecto al servidor local (segundos)
            'max_drift_seconds' => 60,

            // Timezone esperado
            'expected_timezone' => 'America/Montevideo',
        ],

        // Si todas las APIs fallan, usar hora del servidor
        'fallback_to_server' => true,
    ],

    /**
     * Servicio: Valores SOA (Seguros - BCU)
     */
    'valores_soa' => [
        'enabled' => env('VALORES_SOA_ENABLED', true),

        // URL de página que contiene enlace al PDF
        'url_source' => env(
            'VALORES_SOA_URL_SOURCE',
            'https://www.bcu.gub.uy/Servicios-Financieros-SSF/Paginas/ImpPromCostoDelSOA.aspx'
        ),

        // Patrón para encontrar enlace PDF en el HTML
        'pdf_pattern' => '/SOA_Prima_Promedio.*?\.pdf/i',

        // Timeout específico para esta descarga (pueden ser archivos grandes)
        'timeout' => 120,

        // Reintentos: 1 solo (descargas de PDF son costosas)
        'max_retries' => 1,
        'retry_delay_ms' => 2000,

        // Caché: 7 días (SOA cambia semanalmente aprox)
        'cache_ttl_minutes' => 10080,

        // Validación de valores extraídos
        'validation' => [
            'min_value' => 0.01,
            'max_value' => 1000000,
            'multiplier' => 2, // los valores se multiplican por 2
        ],

        // Categorías esperadas en el PDF (para validar que el parseo fue correcto)
        'expected_categories' => [
            'Motos',
            'Automóviles',
            'Camiones',
            'Ómnibus',
            'Taxis',
            'Remises',
            'Ambulancias',
            'Policías',
            'Militares',
        ],

        // Si descarga falla, usar último valor en caché
        'fallback_to_old_cache' => true,
    ],

    /**
     * Configuración de almacenamiento de log de descargas
     * (para auditoría y troubleshooting)
     */
    'logging' => [
        'enabled' => env('EXTERNAL_DOWNLOADS_LOG_ENABLED', true),

        // Tabla donde se guardan los logs
        'table' => 'external_download_logs',

        // Retención: eliminar logs más antiguos que X días
        'retention_days' => 30,

        // Qué eventos loguear
        'log_events' => [
            'request_start' => true,  // Cuando inicia un request
            'request_success' => true, // Cuando es exitoso
            'request_failure' => true, // Cuando falla
            'retry' => true,           // Cuando reintenta
            'fallback' => true,        // Cuando usa fallback/caché
            'proxy_used' => true,      // Qué proxy se usó
            'cache_hit' => true,       // Si fue hit de caché
            'circuit_breaker' => true, // Eventos del circuit breaker
        ],
    ],

    /**
     * Configuración de caché general
     */
    'cache' => [
        // Driver a usar: 'file', 'redis', 'memcached', 'database'
        // Por defecto usa el driver configurado en config/cache.php
        'driver' => env('EXTERNAL_DOWNLOADS_CACHE_DRIVER', 'file'),

        // Prefijo para las keys de caché
        'prefix' => 'external_downloads_',

        // Deshabilitar caché globalmente (para testing)
        'disabled' => env('EXTERNAL_DOWNLOADS_CACHE_DISABLED', false),
    ],
];
