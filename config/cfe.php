<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Auto-confirmación CFE
    |--------------------------------------------------------------------------
    |
    | Define qué tipos de CFE se confirman automáticamente después de
    | procesar el PDF, sin intervención manual.
    |
    */
    'auto_confirm_types' => [
        'multas_cobradas' => true,
        'eventuales' => false,
        'prendas' => false,
        'arrendamientos' => false,
        'certificado_residencia' => false,
        'tenencia_armas' => false,
        'porte_armas' => false,
        'generico' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Expiración de Pendientes
    |--------------------------------------------------------------------------
    |
    | Días después de los cuales un CFE pendiente se marca como expirado
    | si no ha sido procesado.
    |
    */
    'expiracion_dias' => 7,

    /*
    |--------------------------------------------------------------------------
    | Configuración de Caché
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => true,
        'ttl_dias' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de OCR (Futuro)
    |--------------------------------------------------------------------------
    */
    'ocr' => [
        'enabled' => false,
        'engine' => 'tesseract',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Validación
    |--------------------------------------------------------------------------
    */
    'validacion' => [
        'tolerancia_monto' => 0.1,
        'requerir_items_multas' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Jobs (Colas)
    |--------------------------------------------------------------------------
    |
    | Define las colas, reintentos y timeouts para los jobs de CFE.
    |
    */
    'jobs' => [
        'process_pdf' => [
            'queue' => env('CFE_QUEUE_PROCESS', 'cfe-processing'),
            'tries' => 3,
            'timeout' => 120,
            'backoff' => [30, 60, 120],
        ],
        'confirm' => [
            'queue' => env('CFE_QUEUE_CONFIRM', 'cfe-confirmation'),
            'tries' => 3,
            'timeout' => 60,
            'backoff' => [30, 60, 120],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Modo de Procesamiento
    |--------------------------------------------------------------------------
    |
    | 'sync':  Procesa el PDF en el mismo request (comportamiento actual).
    | 'async': Crea el pendiente y encola un job para procesar en segundo plano.
    |          Requiere un worker de colas configurado.
    |
    */
    'processing' => [
        'mode' => env('CFE_PROCESSING_MODE', 'sync'),
    ],
];