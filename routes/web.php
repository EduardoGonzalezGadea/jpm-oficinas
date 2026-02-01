<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\Tesoreria\TesoreriaController;

use App\Http\Controllers\PermissionController;
use App\Http\Controllers\Tesoreria\ArrendamientoController;
use App\Http\Controllers\Tesoreria\CajaChica\ImpresionController;
use App\Http\Controllers\Tesoreria\CajaChica\CajaChicaController;
use App\Http\Controllers\Tesoreria\CajaChica\PendienteController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\Tesoreria\ArmasController;
use App\Http\Controllers\Tesoreria\Armas\ImpresionController as ArmasImpresionController;

use App\Http\Controllers\ThemeController;
use App\Http\Livewire\Tesoreria\Arrendamientos\PrintArrendamientos;
use App\Http\Livewire\Tesoreria\Arrendamientos\PrintArrendamientosFull;
use App\Http\Livewire\Tesoreria\Eventuales\PrintEventuales;
use App\Http\Livewire\Tesoreria\Eventuales\PrintEventualesFull;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PendriveController;

use App\Http\Controllers\Tesoreria\BancoController;
use App\Http\Controllers\Tesoreria\CuentaBancariaController;
use App\Http\Controllers\Tesoreria\ChequeController;
use App\Http\Controllers\UtilidadController;

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


// Ruta para obtener el valor de la UR de forma asíncrona
Route::get('/valor-ur', [UtilidadController::class, 'getValorUr'])->name('utilidad.valor_ur');

// Ruta para obtener la hora actual de Uruguay (sincronizada con Internet)
Route::get('/hora-uruguay', [UtilidadController::class, 'getHoraUruguay'])->name('utilidad.hora-uruguay');

// Ruta para actualizar valores SOA (Art. 184)
Route::get('/utilidad/actualizar-soa-art-184', [UtilidadController::class, 'actualizarValoresSoa'])->name('utilidad.actualizar-soa');


// ============================================================================
// RUTAS PROTEGIDAS POR JWT
// ============================================================================

Route::middleware(['web', 'jwt.verify', 'two-factor'])->group(function () {

    // ------------------------------------------------------------------------
    // AUTENTICACIÓN Y SESIÓN
    // ------------------------------------------------------------------------
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Rutas de Desafío 2FA (Protegidas por auth pero exentas del middleware 2FA por lógica interna)
    Route::get('/two-factor-challenge', [App\Http\Controllers\TwoFactorController::class, 'showChallenge'])->name('two-factor.login');
    Route::post('/two-factor-challenge', [App\Http\Controllers\TwoFactorController::class, 'verifyChallenge'])->name('two-factor.verify');

    // Rutas de Gestión 2FA
    Route::get('/two-factor-authentication', [App\Http\Controllers\TwoFactorController::class, 'index'])->name('two-factor.index');
    Route::post('/two-factor-authentication/enable', [App\Http\Controllers\TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::delete('/two-factor-authentication/disable', [App\Http\Controllers\TwoFactorController::class, 'disable'])->name('two-factor.disable');
    Route::post('/two-factor-authentication/regenerate', [App\Http\Controllers\TwoFactorController::class, 'regenerateRecoveryCodes'])->name('two-factor.regenerate');

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
        Route::get('/download/{file}', [BackupController::class, 'download'])->name('download')->where('file', '.*');
        Route::delete('/', [BackupController::class, 'delete'])->name('delete');
    });

    // ------------------------------------------------------------------------
    // HISTORIAL DE AUDITORÍA
    // ------------------------------------------------------------------------
    Route::middleware(['role:administrador|gerente_tesoreria|supervisor_tesoreria'])->group(function () {
        Route::get('/sistema/auditoria', \App\Http\Livewire\Sistema\Auditoria\Index::class)->name('sistema.auditoria.index');
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

        // Rutas 2FA - MOVIDAS ARRIBA

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

        // Rutas de Bancos y Cuentas Bancarias
        Route::get('bancos', [BancoController::class, 'index'])->name('bancos.index')->middleware('auth');
        Route::get('cuentas-bancarias', [CuentaBancariaController::class, 'index'])->name('cuentas-bancarias.index');

        // Rutas de cheques
        Route::get('cheques', function () {
            return view('tesoreria.cheques.index');
        })->name('cheques.index');
        Route::get('cheques/libreta', [ChequeController::class, 'libreta'])->name('cheques.libreta');
        Route::get('cheques/emitir', [ChequeController::class, 'emitir'])->name('cheques.emitir');
        Route::get('cheques/planilla/generar', [ChequeController::class, 'planillaGenerar'])->name('cheques.planilla.generar');
        Route::get('cheques/planilla/{id}', [ChequeController::class, 'planillaVer'])->name('cheques.planilla.ver');
        Route::get('cheques/planilla/{id}/imprimir', [ChequeController::class, 'imprimirPlanilla'])->name('cheques.planilla.imprimir');
        Route::get('cheques/reportes', [ChequeController::class, 'reportes'])->name('cheques.reportes');


        // Rutas de multas
        Route::get('/multas-transito', function () {
            return view('tesoreria.multas');
        })->name('multas-transito');
        Route::get('/multas-transito/exportar-pdf', \App\Http\Livewire\Tesoreria\PrintMultasArticulos::class)->name('multas-transito.exportar-pdf');

        // Rutas de Multas Cobradas
        Route::prefix('multas-cobradas')->name('multas-cobradas.')->middleware(['can:operador_tesoreria'])->group(function () {
            Route::get('/', function () {
                return view('tesoreria.multas-cobradas.index');
            })->name('index');
            Route::get('/cargar-cfe', function () {
                return view('tesoreria.multas-cobradas.cargar-cfe');
            })->name('cargar-cfe');
            Route::get('/imprimir-detalles/{fechaDesde}/{fechaHasta}', \App\Http\Livewire\Tesoreria\MultasCobradas\PrintMultasCobradasFull::class)->name('imprimir-detalles');
            Route::get('/imprimir-resumen/{fechaDesde}/{fechaHasta}', \App\Http\Livewire\Tesoreria\MultasCobradas\PrintMultasCobradasResumen::class)->name('imprimir-resumen');
        });

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

            Route::get('/cargar-efactura', [App\Http\Controllers\Tesoreria\EventualesController::class, 'cargarEfactura'])->name('cargar-efactura');
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
            Route::get('/cargar-cfe', [ArmasController::class, 'cargarCfe'])->name('cargar-cfe');
            Route::get('/tenencia/imprimir/{id}', [ArmasImpresionController::class, 'imprimirTenencia'])->name('tenencia.imprimir');
            Route::get('/porte/imprimir/{id}', [ArmasImpresionController::class, 'imprimirPorte'])->name('porte.imprimir');

            // Planillas Porte
            Route::prefix('porte/planillas')->name('porte.planillas.')->group(function () {
                Route::get('/', \App\Http\Livewire\Tesoreria\Armas\Planillas\TesPorteArmasPlanillasIndex::class)->name('index');
                Route::get('/{id}', \App\Http\Livewire\Tesoreria\Armas\Planillas\TesPorteArmasPlanillasShow::class)->name('show');
                Route::get('/{id}/imprimir', [ArmasImpresionController::class, 'imprimirPlanillaPorte'])->name('imprimir');
            });

            // Planillas Tenencia
            Route::prefix('tenencia/planillas')->name('tenencia.planillas.')->group(function () {
                Route::get('/', \App\Http\Livewire\Tesoreria\Armas\Planillas\TesTenenciaArmasPlanillasIndex::class)->name('index');
                Route::get('/{id}', \App\Http\Livewire\Tesoreria\Armas\Planillas\TesTenenciaArmasPlanillasShow::class)->name('show');
                Route::get('/{id}/imprimir', [ArmasImpresionController::class, 'imprimirPlanillaTenencia'])->name('imprimir');
            });
        });


        // Rutas de Certificados de Residencia
        Route::get('/certificados-residencia', \App\Http\Livewire\Tesoreria\CertificadosResidencia\Index::class)->name('certificados-residencia.index');

        // Rutas de Gestión de Prendas
        Route::get('/prendas', \App\Http\Livewire\Tesoreria\Prendas\Index::class)->name('prendas.index');

        // Rutas de Planillas de Prendas
        Route::prefix('prendas/planillas')->name('prendas.planillas.')->group(function () {
            Route::get('/', \App\Http\Livewire\Tesoreria\Prendas\Planillas\Index::class)->name('index');
            Route::get('/{id}', \App\Http\Livewire\Tesoreria\Prendas\Planillas\Show::class)->name('show');
            Route::get('/{id}/imprimir', function ($id) {
                $planilla = App\Models\Tesoreria\PrendaPlanilla::findOrFail($id);
                return view('tesoreria.prendas.planillas-print', compact('planilla'));
            })->name('print');
        });

        // Rutas de Gestión de Depósito de Vehículos
        Route::get('/deposito-vehiculos', [App\Http\Controllers\Tesoreria\DepositoVehiculosController::class, 'index'])->name('deposito-vehiculos.index');

        // Rutas de Planillas de Depósito de Vehículos
        Route::prefix('deposito-vehiculos/planillas')->name('deposito-vehiculos.planillas.')->group(function () {
            Route::get('/', [App\Http\Controllers\Tesoreria\DepositoVehiculosController::class, 'planillasIndex'])->name('index');
            Route::get('/{id}', [App\Http\Controllers\Tesoreria\DepositoVehiculosController::class, 'planillaShow'])->name('show');
            Route::get('/{id}/imprimir', [App\Http\Controllers\Tesoreria\DepositoVehiculosController::class, 'planillaPrint'])->name('print');
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

        // Rutas para Gestión de Valores
        require __DIR__ . '/valores.php';

        // Rutas para Reportes de Stock de Valores (PDF)
        Route::post('/valores/reportes/upload-stock', [App\Http\Controllers\Tesoreria\StockReporteController::class, 'upload'])->name('valores.reportes.upload-stock');
        Route::get('/valores/reportes/download-stock/{filename}', [App\Http\Controllers\Tesoreria\StockReporteController::class, 'download'])->name('valores.reportes.download-stock');

        // Rutas para Reportes de Stock de Cheques (PDF)
        Route::post('/cheques/reportes/upload-stock', [App\Http\Controllers\Tesoreria\StockChequesController::class, 'upload'])->name('cheques.reportes.upload-stock');
        Route::get('/cheques/reportes/download-stock/{filename}', [App\Http\Controllers\Tesoreria\StockChequesController::class, 'download'])->name('cheques.reportes.download-stock');
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
