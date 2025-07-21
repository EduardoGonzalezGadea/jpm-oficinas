<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\TesoreriaController;
use App\Http\Controllers\ContabilidadController;
use App\Http\Controllers\ThemeController;

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

// Cambiar tema
Route::post('/tema/cambiar', [ThemeController::class, 'switchTheme'])->name('theme.switch');


// Rutas de autenticación (no requieren JWT)
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas por JWT
Route::middleware(['jwt.verify'])->group(function () {
    // Cerrar sesión
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Panel principal
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
        Route::put('/cambiar-contrasena', [UsuarioController::class, 'cambiarContrasena'])->name('cambiarContraseña');
        Route::post('/{usuario}/resetear-contrasena', [UsuarioController::class, 'resetPassword'])->name('reset-password');
        Route::patch('/{usuario}/cambiar-estado', [UsuarioController::class, 'toggleStatus'])->name('toggle-status');

        // Rutas con parámetros (DESPUÉS de las rutas específicas)
        Route::get('/{usuario}', [UsuarioController::class, 'show'])->name('show');
        Route::get('/{usuario}/editar', [UsuarioController::class, 'edit'])->name('edit');
        Route::put('/{usuario}', [UsuarioController::class, 'update'])->name('update');
        Route::delete('/{usuario}', [UsuarioController::class, 'destroy'])->name('destroy');
    });

    // Rutas de roles
    Route::get('roles', [RolePermissionController::class, 'rolesIndex'])->name('roles.index');
    Route::get('roles/crear', [RolePermissionController::class, 'createRole'])->name('roles.create');
    Route::post('roles', [RolePermissionController::class, 'storeRole'])->name('roles.store');
    Route::get('roles/{role}/editar', [RolePermissionController::class, 'editRole'])->name('roles.edit');
    Route::put('roles/{role}', [RolePermissionController::class, 'updateRole'])->name('roles.update');
    Route::delete('roles/{role}', [RolePermissionController::class, 'destroyRole'])->name('roles.destroy');

    // Rutas de permisos
    Route::get('permisos', [RolePermissionController::class, 'permissionsIndex'])->name('permissions.index');
    Route::get('permisos/create', [RolePermissionController::class, 'createPermission'])->name('permissions.create');
    Route::post('permisos', [RolePermissionController::class, 'storePermission'])->name('permissions.store');
    Route::get('permisos/{permission}/editar', [RolePermissionController::class, 'editPermission'])->name('permissions.edit');
    Route::put('permisos/{permission}', [RolePermissionController::class, 'updatePermission'])->name('permissions.update');
    Route::delete('permisos/{permission}', [RolePermissionController::class, 'destroyPermission'])->name('permissions.destroy');

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
