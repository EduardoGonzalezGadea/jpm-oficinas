<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\TesoreriaController;
use App\Http\Controllers\ContabilidadController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Rutas públicas
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Rutas de autenticación (no requieren JWT)
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas por JWT
Route::middleware(['jwt.verify'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Panel
    Route::get('/panel', [PanelController::class, 'index'])->name('panel');

    // Gestión de Usuarios
    Route::prefix('usuarios')->name('usuarios.')->group(function () {
        // IMPORTANTE: Las rutas específicas DEBEN ir ANTES que las rutas con parámetros
        Route::get('/', [UsuarioController::class, 'index'])->name('index');
        Route::get('/crear', [UsuarioController::class, 'create'])->name('create');
        Route::post('/', [UsuarioController::class, 'store'])->name('store');

        // Rutas del perfil propio (ANTES de las rutas con {usuario})
        Route::get('/mi-perfil', [UsuarioController::class, 'miPerfil'])->name('miPerfil');
        Route::put('/actualizar-perfil', [UsuarioController::class, 'actualizarPerfil'])->name('actualizarPerfil');
        Route::put('/cambiar-contrasena', [UsuarioController::class, 'cambiarContraseña'])->name('cambiarContraseña');

        // Rutas con parámetros (DESPUÉS de las rutas específicas)
        Route::get('/{usuario}', [UsuarioController::class, 'show'])->name('show');
        Route::get('/{usuario}/editar', [UsuarioController::class, 'edit'])->name('edit');
        Route::put('/{usuario}', [UsuarioController::class, 'update'])->name('update');
        Route::delete('/{usuario}', [UsuarioController::class, 'destroy'])->name('destroy');
    });

    // Gestión de Módulos
    // Route::prefix('modulos')->name('modulos.')->group(function () {
    //     Route::get('/', [ModuloController::class, 'index'])->name('index');
    //     Route::get('/crear', [ModuloController::class, 'create'])->name('create');
    //     Route::post('/', [ModuloController::class, 'store'])->name('store');
    //     Route::get('/{modulo}/editar', [ModuloController::class, 'edit'])->name('edit');
    //     Route::put('/{modulo}', [ModuloController::class, 'update'])->name('update');
    //     Route::delete('/{modulo}', [ModuloController::class, 'destroy'])->name('destroy');
    // });

    // Tesorería
    Route::get('/tesoreria', [TesoreriaController::class, 'index'])->name('tesoreria.index');

    // Contabilidad
    Route::get('/contabilidad', [ContabilidadController::class, 'index'])->name('contabilidad.index');
});
