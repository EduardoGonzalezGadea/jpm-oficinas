<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'livewire/*', 'hora-uruguay'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [],

    'allowed_origins_patterns' => ['#^https?://(.+\.)?10\.100\.\d{1,3}\.\d{1,3}(:\d+)?$#'],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
