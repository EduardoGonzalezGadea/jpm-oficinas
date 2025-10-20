<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\Tesoreria\TesoreriaController;
use App\Http\Controllers\ContabilidadController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\Tesoreria\ArrendamientoController;
use App\Http\Controllers\Tesoreria\CajaChica\ImpresionController;
use App\Http\Controllers\Tesoreria\CajaChica\CajaChicaController;
use App\Http\Controllers\Tesoreria\CajaChica\PendienteController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\Tesoreria\ArmasController;
use App\Http\Controllers\ThemeController;
use App\Http\Livewire\Tesoreria\Arrendamientos\PrintArrendamientos;
use App\Http\Livewire\Tesoreria\Arrendamientos\PrintArrendamientosFull;
use App\Http\Livewire\Tesoreria\Eventuales\PrintEventuales;
use App\Http\Livewire\Tesoreria\Eventuales\PrintEventualesFull;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PendriveController;

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

// Ruta pública para acceso como invitado a multas de tránsito
Route::get('/multas-transito-publico', function () {
    return view('tesoreria.multas-publico');
})->name('multas-transito-publico');

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
    // GESTIÓN DE RESPALDOS
    // ------------------------------------------------------------------------
    Route::prefix('system/backups')->name('system.backups.')->middleware(['can:administrar_sistema,web'])->group(function () {
        Route::get('/', [BackupController::class, 'index'])->name('index');
        Route::get('/create', [BackupController::class, 'create'])->name('create');
        Route::post('/restore', [BackupController::class, 'restore'])->name('restore');
        Route::get('/download/{file}', [BackupController::class, 'download'])->name('download');
    });

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
        Route::delete('/remove-user/{user_id}/{role_id}', [RoleController::class, 'removeFromUser'])->name('remove.user');
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
    // GESTIÓN DE MÓDULOS
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
    // PENDRIVE VIRTUAL
    // ------------------------------------------------------------------------
    Route::prefix('pendrive')->name('pendrive.')->group(function () {
        Route::get('/', [PendriveController::class, 'index'])->name('index');
        Route::post('upload', [PendriveController::class, 'upload'])->name('upload');
        Route::get('download/{filename}', [PendriveController::class, 'download'])->name('download');
        Route::get('thumbnail/{filename}', [PendriveController::class, 'getThumbnail'])->name('thumbnail');
        Route::delete('{filename}', [PendriveController::class, 'destroy'])->name('destroy');
    });

    // ------------------------------------------------------------------------
    // MÓDULOS DE NEGOCIO
    // ------------------------------------------------------------------------

    // Tesorería
    Route::prefix('tesoreria')->name('tesoreria.')->group(function () {
        Route::get('/', [TesoreriaController::class, 'index'])->name('index');

        // Caja Diaria
        Route::get('/caja-diaria/{tab?}', [TesoreriaController::class, 'cajaDiaria'])
            ->name('caja_diaria');

        // Pagos
        Route::prefix('pagos')->name('pagos.')->group(function () {
            Route::get('/', 'PagoController@index')->name('index');
            Route::post('/', 'PagoController@store')->name('store');
            Route::get('/{id}', 'PagoController@show')->name('show');
            Route::put('/{id}', 'PagoController@update')->name('update');
            Route::delete('/{id}', 'PagoController@destroy')->name('destroy');
            Route::get('/conceptos/lista', 'PagoController@getConceptos')->name('conceptos.lista');
        });

        // Conceptos de Pago
        Route::prefix('conceptos-pago')->name('conceptos-pago.')->group(function () {
            Route::get('/', 'ConceptoPagoController@index')->name('index');
            Route::post('/', 'ConceptoPagoController@store')->name('store');
            Route::get('/{id}', 'ConceptoPagoController@show')->name('show');
            Route::put('/{id}', 'ConceptoPagoController@update')->name('update');
            Route::delete('/{id}', 'ConceptoPagoController@destroy')->name('destroy');
        });


        // Rutas de multas
        Route::get('/multas-transito', function () {
            return view('tesoreria.multas');
        })->name('multas-transito');

        // Rutas de Eventuales
        Route::prefix('eventuales')->name('eventuales.')->group(function () {
            Route::get('/', function () {
                return view('tesoreria.eventuales.index');
            })->name('index');

            Route::get('/instituciones', function () {
                return view('tesoreria.eventuales.instituciones');
            })->name('instituciones');

            Route::get('/planillas/imprimir/{id}', function ($id) {
                $planilla = App\Models\Tesoreria\EventualPlanilla::findOrFail($id);
                return view('tesoreria.eventuales.planillas-print', compact('planilla'));
            })->name('planillas-print');

            // Rutas de Impresión de Eventuales
            Route::get('/imprimir/{year}/{mes}', PrintEventuales::class)->name('imprimir');
            Route::get('/imprimir-detalles/{year}/{mes}', PrintEventualesFull::class)->name('imprimir-detalles');
        });

        // Rutas de Arrendamientos
        Route::prefix('arrendamientos')->name('arrendamientos.')->group(function () {
            Route::get('/', [ArrendamientoController::class, 'index'])->name('index');
            Route::get('/planillas/imprimir/{id}', function ($id) {
                $planilla = App\Models\Tesoreria\Planilla::findOrFail($id);
                return view('tesoreria.arrendamientos.planillas-print', compact('planilla'));
            })->name('planillas-print');
            Route::get('/imprimir/{year}/{mes}', PrintArrendamientos::class)->name('imprimir');
            Route::get('/imprimir-todo/{year}/{mes}', PrintArrendamientosFull::class)->name('imprimir-todo');
        });

        // Rutas de Armas
        Route::prefix('armas')->name('armas.')->group(function () {
            Route::get('/porte', [ArmasController::class, 'porte'])->name('porte');
            Route::get('/tenencia', [ArmasController::class, 'tenencia'])->name('tenencia');
        });

        // Rutas de Configuración - Medios de Pago
        Route::get('/configuracion/medios-de-pago', function () {
            return view('tesoreria.configuracion.medios-de-pago.index-livewire');
        })->name('configuracion.medios-de-pago.index');

        // Rutas de Configuración - Tipos de Monedas
        Route::get('/configuracion/tipos-monedas', function () {
            return view('tesoreria.configuracion.tes-tipos-monedas.index-livewire');
        })->name('configuracion.tes-tipos-monedas.index');

        // Rutas de Configuración - Denominaciones de Monedas
        Route::get('/configuracion/denominaciones-monedas', function () {
            return view('tesoreria.configuracion.tes-denominaciones-monedas.index-livewire');
        })->name('configuracion.tes-denominaciones-monedas.index');
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
