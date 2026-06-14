<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * Justificación de cada exclusión:
     * - 'hora-uruguay': Endpoint público para widgets externos que muestran la hora oficial de Uruguay (consulta GET)
     * - 'valor-ur': Endpoint público para widgets externos que muestran el valor de UR (consulta GET)
     * - 'tema/cambiar': Endpoint para cambio de tema (modo oscuro/claro) consumido por JavaScript del frontend
     * - 'api/cfe/*': Endpoints de la API REST para CFE (Administración Nacional de Combustibles, Alcohol y Portland),
     *                consumidos por servicios externos que se autentican mediante API tokens (Sanctum)
     *
     * @var array<int, string>
     */
    protected $except = [
        'hora-uruguay',
        'valor-ur',
        'tema/cambiar',
        'api/cfe/*',
    ];
}
