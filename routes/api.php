<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CfeController;

// Rutas de CFE - Sin sesión para evitar conflictos con la extensión
Route::post('/cfe/procesar', [CfeController::class, 'procesarCfe'])->middleware('throttle:30,1');
Route::get('/cfe/pendientes', [CfeController::class, 'pendientes']);
Route::post('/cfe/{id}/confirmar', [CfeController::class, 'confirmarCfe'])->middleware('throttle:20,1');
Route::post('/cfe/{id}/rechazar', [CfeController::class, 'rechazarCfe'])->middleware('throttle:20,1');
Route::post('/cfe/analizar', [CfeController::class, 'analizarCfe'])->middleware('throttle:30,1');
Route::post('/cfe/analizar-archivo', [CfeController::class, 'analizarCfeConArchivo'])->middleware('throttle:30,1');
Route::post('/cfe/crear-registro', [CfeController::class, 'crearRegistro'])->middleware('throttle:30,1');
Route::post('/cfe/registrar-multa-auto', [CfeController::class, 'registrarMultaAuto'])->middleware('throttle:20,1');

