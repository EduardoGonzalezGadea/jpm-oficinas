<?php

/**
 * Configuración compartida de duración de sesión entre Laravel Session y JWT.
 */
return [
    'lifetime_minutes' => (int) env('SESSION_LIFETIME', 1440),
];
