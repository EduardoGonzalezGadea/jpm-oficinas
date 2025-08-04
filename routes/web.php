<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\Tesoreria\TesoreriaController;
use App\Http\Controllers\ContabilidadController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\Tesoreria\CajaChica\ImpresionController;
use App\Http\Controllers\Tesoreria\CajaChica\CajaChicaController;
use App\Http\Controllers\Tesoreria\CajaChica\PendienteController;
use App\Http\Controllers\Tesoreria\Valores\ValorController;
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

// ============================================================================
// RUTAS PÚBLICAS (Sin autenticación)
// ============================================================================

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Cambiar tema (debe funcionar sin autenticación para el formulario de login)
Route::post('/tema/cambiar', [ThemeController::class, 'switchTheme'])->name('theme.switch');

// Rutas de autenticación
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// ============================================================================
// RUTAS PROTEGIDAS POR JWT
// ============================================================================

Route::middleware(['web', 'jwt.verify'])->group(function () {

    // ------------------------------------------------------------------------
    // AUTENTICACIÓN Y SESIÓN
    // ------------------------------------------------------------------------
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ------------------------------------------------------------------------
    // PANEL PRINCIPAL
    // ------------------------------------------------------------------------
    Route::get('/panel', [PanelController::class, 'index'])->name('panel');

    // ------------------------------------------------------------------------
    // GESTIÓN DE USUARIOS
    // ------------------------------------------------------------------------
    Route::prefix('usuarios')->name('usuarios.')->group(function () {
        // Rutas principales CRUD
        Route::get('/', [UsuarioController::class, 'index'])->name('index');
        Route::get('/crear', [UsuarioController::class, 'create'])->name('create');
        Route::post('/', [UsuarioController::class, 'store'])->name('store');

        // Rutas del perfil propio (ANTES de las rutas con parámetros)
        Route::get('/mi-perfil', [UsuarioController::class, 'miPerfil'])->name('miPerfil');
        Route::put('/actualizar-perfil', [UsuarioController::class, 'actualizarPerfil'])->name('actualizarPerfil');
        Route::put('/cambiar-contrasena', [UsuarioController::class, 'cambiarContrasena'])->name('cambiarContraseña');

        // Rutas AJAX y datos
        Route::get('/data/roles/{usuario?}', [UsuarioController::class, 'getRolesData'])->name('roles.data');
        Route::get('/data/permissions/{usuario?}', [UsuarioController::class, 'getPermissionsData'])->name('permissions.data');

        // Rutas con parámetros específicos (ANTES de {usuario})
        Route::post('/{usuario}/resetear-contrasena', [UsuarioController::class, 'resetPassword'])->name('reset-password');
        Route::patch('/{usuario}/cambiar-estado', [UsuarioController::class, 'toggleStatus'])->name('toggle-status');
        Route::put('/{usuario}/roles', [UsuarioController::class, 'updateRoles'])->name('roles.update');
        Route::put('/{usuario}/permissions', [UsuarioController::class, 'updatePermissions'])->name('permissions.update');

        // Rutas CRUD con parámetros (AL FINAL)
        Route::get('/{usuario}', [UsuarioController::class, 'show'])->name('show');
        Route::get('/{usuario}/editar', [UsuarioController::class, 'edit'])->name('edit');
        Route::put('/{usuario}', [UsuarioController::class, 'update'])->name('update');
        Route::delete('/{usuario}', [UsuarioController::class, 'destroy'])->name('destroy');
    });

    // ------------------------------------------------------------------------
    // GESTIÓN DE ROLES
    // ------------------------------------------------------------------------
    Route::prefix('roles')->name('roles.')->group(function () {
        // Rutas principales CRUD
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/crear', [RoleController::class, 'create'])->name('create');
        Route::post('/', [RoleController::class, 'store'])->name('store');

        // Rutas AJAX y datos
        Route::get('/data/{role?}', [RoleController::class, 'getRoleData'])->name('data');
        Route::get('/export', [RoleController::class, 'export'])->name('export');
        Route::post('/import', [RoleController::class, 'import'])->name('import');

        // Rutas de asignación
        Route::post('/assign-user', [RoleController::class, 'assignToUser'])->name('assign.user');
        Route::post('/remove-user', [RoleController::class, 'removeFromUser'])->name('remove.user');
        Route::post('/bulk-assign', [RoleController::class, 'bulkAssignToUsers'])->name('bulk.assign');

        // Rutas con parámetros
        Route::get('/{role}', [RoleController::class, 'show'])->name('show');
        Route::get('/{role}/editar', [RoleController::class, 'edit'])->name('edit');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
        Route::put('/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('permissions.update');
    });

    // ------------------------------------------------------------------------
    // GESTIÓN DE PERMISOS
    // ------------------------------------------------------------------------
    Route::prefix('permisos')->name('permissions.')->group(function () {
        // Rutas principales CRUD
        Route::get('/', [PermissionController::class, 'index'])->name('index');
        Route::get('/crear', [PermissionController::class, 'create'])->name('create');
        Route::post('/', [PermissionController::class, 'store'])->name('store');

        // Rutas especiales y utilitarias
        Route::post('/bulk-create', [PermissionController::class, 'bulkCreateForModule'])->name('bulk.create');
        Route::get('/export', [PermissionController::class, 'export'])->name('export');
        Route::post('/import', [PermissionController::class, 'import'])->name('import');
        Route::get('/stats', [PermissionController::class, 'getStats'])->name('stats');
        Route::delete('/clean-unused', [PermissionController::class, 'cleanUnusedPermissions'])->name('clean');

        // Rutas AJAX y datos
        Route::get('/data/{usuario?}', [PermissionController::class, 'getPermissionsData'])->name('data');

        // Rutas de asignación a usuarios
        Route::put('/users/{usuario}/permissions', [PermissionController::class, 'updateUserPermissions'])->name('users.update');

        // Rutas con parámetros (AL FINAL)
        Route::get('/{permission}', [PermissionController::class, 'show'])->name('show');
        Route::get('/{permission}/editar', [PermissionController::class, 'edit'])->name('edit');
        Route::put('/{permission}', [PermissionController::class, 'update'])->name('update');
        Route::delete('/{permission}', [PermissionController::class, 'destroy'])->name('destroy');
        Route::get('/{permission}/roles', [PermissionController::class, 'roles'])->name('roles');
        Route::post('/{permission}/roles', [PermissionController::class, 'assignToRole'])->name('assign-role');
    });

    // ------------------------------------------------------------------------
    // GESTIÓN DE MÓDULOS (Comentado actualmente)
    // ------------------------------------------------------------------------
    Route::prefix('modulos')->name('modulos.')->group(function () {
        Route::get('/', [ModuloController::class, 'index'])->name('index');
        Route::get('/crear', [ModuloController::class, 'create'])->name('create');
        Route::post('/', [ModuloController::class, 'store'])->name('store');
        Route::get('/{modulo}', [ModuloController::class, 'show'])->name('show');
        Route::get('/{modulo}/editar', [ModuloController::class, 'edit'])->name('edit');
        Route::put('/{modulo}', [ModuloController::class, 'update'])->name('update');
        Route::delete('/{modulo}', [ModuloController::class, 'destroy'])->name('destroy');
    });

    // ------------------------------------------------------------------------
    // MÓDULOS DE NEGOCIO
    // ------------------------------------------------------------------------

    // Tesorería
    Route::prefix('tesoreria')->name('tesoreria.')->group(function () {
        Route::get('/', [TesoreriaController::class, 'index'])->name('index');
    });

    // Contabilidad
    Route::prefix('contabilidad')->name('contabilidad.')->group(function () {
        Route::get('/', [ContabilidadController::class, 'index'])->name('index');
        // Agregar más rutas según necesidades
        // Route::get('/balance', [ContabilidadController::class, 'balance'])->name('balance');
        // Route::get('/asientos', [ContabilidadController::class, 'asientos'])->name('asientos');
    });

    // ------------------------------------------------------------------------
    // RUTAS API INTERNAS (Para llamadas AJAX)
    // ------------------------------------------------------------------------
    Route::prefix('api')->name('api.')->group(function () {
        // Dashboard y estadísticas
        Route::get('/dashboard/stats', [PanelController::class, 'getDashboardStats'])->name('dashboard.stats');

        // Búsquedas rápidas
        Route::get('/search/users', [UsuarioController::class, 'searchUsers'])->name('search.users');
        Route::get('/search/roles', [RoleController::class, 'searchRoles'])->name('search.roles');
        Route::get('/search/permissions', [PermissionController::class, 'searchPermissions'])->name('search.permissions');

        // Validaciones en tiempo real
        Route::post('/validate/user-email', [UsuarioController::class, 'validateEmail'])->name('validate.email');
        Route::post('/validate/role-name', [RoleController::class, 'validateName'])->name('validate.role');
        Route::post('/validate/permission-name', [PermissionController::class, 'validateName'])->name('validate.permission');
    });

    // ------------------------------------------------------------------------
    // TESORERÍA - CAJA CHICA
    // ------------------------------------------------------------------------
    Route::middleware(['permission:operador_tesoreria'])->group(function () {
        Route::get('/tesoreria/caja-chica', [CajaChicaController::class, 'index'])->name('tesoreria.caja-chica.index');
        Route::get('tesoreria/caja-chica/pendientes/{id}/editar', [PendienteController::class, 'edit'])
            ->name('tesoreria.caja-chica.pendientes.editar');
        // Rutas para impresión
        Route::get('/tesoreria/caja-chica/imprimir/pendiente/{id}', [ImpresionController::class, 'imprimirPendiente'])
            ->name('tesoreria.caja-chica.imprimir.pendiente');
        Route::get('/tesoreria/caja-chica/imprimir/pago/{id}', [ImpresionController::class, 'imprimirPago'])
            ->name('tesoreria.caja-chica.imprimir.pago');
    });

    // ------------------------------------------------------------------------
    // TESORERÍA - VALORES
    // ------------------------------------------------------------------------
    Route::prefix('tesoreria/valores')->name('tesoreria.valores.')->middleware(['auth'])->group(function () {

        // Rutas principales
        Route::get('/', [ValorController::class, 'index'])->name('index');

        Route::get('/stock', function () {
            return view('layouts.valores')->with([
                'component' => 'tesoreria.valores.stock'
            ]);
        })->name('stock');

        Route::get('/libretas', function () {
            return view('layouts.valores')->with([
                'component' => 'tesoreria.valores.index'
            ]);
        })->name('libretas');

        Route::get('/recibos', function () {
            return view('layouts.valores')->with([
                'component' => 'tesoreria.valores.conceptos'
            ]);
        })->name('recibos');

        Route::get('/entradas', function () {
            return view('layouts.valores')->with([
                'component' => 'tesoreria.valores.entradas'
            ]);
        })->name('entradas');

        Route::get('/salidas', function () {
            return view('layouts.valores')->with([
                'component' => 'tesoreria.valores.salidas'
            ]);
        })->name('salidas');

        // Route::get('/conceptos', [ValorController::class, 'conceptos'])->name('conceptos');
        // Route::get('/entradas', [ValorController::class, 'entradas'])->name('entradas');
        // Route::get('/salidas', [ValorController::class, 'salidas'])->name('salidas');
        // Route::get('/stock', [ValorController::class, 'stock'])->name('stock');

        // APIs y endpoints adicionales
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/stock-resumen/{valor}', [ValorController::class, 'getStockResumen'])->name('stock.resumen');
            Route::post('/validar-rango', [ValorController::class, 'validarRango'])->name('validar.rango');
            Route::get('/estadisticas', [ValorController::class, 'estadisticas'])->name('estadisticas');
            Route::get('/exportar-stock', [ValorController::class, 'exportarStock'])->name('exportar.stock');
        });
    });
});

// ============================================================================
// RUTAS DE FALLBACK
// ============================================================================

// Ruta para manejar 404 personalizados dentro del área autenticada
Route::fallback(function () {
    if (request()->expectsJson()) {
        return response()->json(['message' => 'Ruta no encontrada'], 404);
    }

    // Si el usuario está autenticado, mostrar 404 del panel
    if (auth()->check()) {
        return response()->view('errors.404', [], 404);
    }

    // Si no está autenticado, redirigir al login
    return redirect()->route('login');
});
