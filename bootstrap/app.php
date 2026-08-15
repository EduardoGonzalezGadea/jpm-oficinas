<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Middleware global de la aplicación
        // Los middleware específicos se configuran en routes/web.php
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Configuración de excepciones
        // El handler principal está en app/Exceptions/Handler.php
    })
    ->create();
