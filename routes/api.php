<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CfeController;

// Rutas de CFE - Sin sesión para evitar conflictos con la extensión
Route::post('/cfe/procesar', [CfeController::class, 'procesarCfe']);
Route::get('/cfe/pendientes', [CfeController::class, 'pendientes']);
Route::post('/cfe/{id}/confirmar', [CfeController::class, 'confirmarCfe']);
Route::post('/cfe/{id}/rechazar', [CfeController::class, 'rechazarCfe']);
Route::post('/cfe/analizar', [CfeController::class, 'analizarCfe']);
Route::post('/cfe/analizar-archivo', [CfeController::class, 'analizarCfeConArchivo']);
Route::post('/cfe/crear-registro', [CfeController::class, 'crearRegistro']);
Route::post('/cfe/registrar-multa-auto', [CfeController::class, 'registrarMultaAuto']);
