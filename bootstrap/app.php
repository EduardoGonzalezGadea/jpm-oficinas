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
        
        // Registrar alias de middleware (antes en Kernel::$routeMiddleware)
        $middleware->alias([
            // JWT Middlewares personalizados
            'jwt.verify' => \App\Http\Middleware\JWTVerify::class,
            'jwt.role' => \App\Http\Middleware\JWTRole::class,
            'jwt.permission' => \App\Http\Middleware\JWTPermission::class,
            
            // Spatie Permission Middlewares
            'role' => \Spatie\Permission\Middlewares\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middlewares\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class,
            
            // Middleware personalizado para verificar rol de administrador
            'admin.only' => \App\Http\Middleware\CheckAdminRole::class,
            
            // Middleware de autenticación de dos factores
            'two-factor' => \App\Http\Middleware\TwoFactorMiddleware::class,
            
            // Middleware unificado de módulo y nivel
            'modulo' => \App\Http\Middleware\ModuloAcceso::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Configuración de excepciones
        // El handler principal está en app/Exceptions/Handler.php
    })
    ->create();
