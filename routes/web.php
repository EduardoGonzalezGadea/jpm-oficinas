<?php

/**
 * routes/web.php
 *
 * Punto de entrada principal de rutas HTTP.
 * Solo orquesta: carga rutas específicas por dominio.
 *
 * Archivos de dominio:
 *  - routes/administracion.php  → Usuarios, Roles, Permisos, Módulos, API interna
 *  - routes/tesoreria.php       → Todos los submódulos de Tesorería
 *  - routes/valores.php         → Gestión de Valores (preexistente)
 */

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ExtensionController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\PendriveController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\UtilidadController;
use Illuminate\Support\Facades\Route;

// ============================================================================
// RUTAS PÚBLICAS (sin autenticación)
// ============================================================================

Route::get('/', fn () => view('welcome'))->name('home');

// Descarga de extensiones del navegador
Route::get('/download-extension',              [ExtensionController::class, 'downloadCfeDetect'])   ->name('extension.download');
Route::get('/download-text-replacer-extension', [ExtensionController::class, 'downloadTextReplacer'])->name('extension.text-replacer.download');

// Vista pública de multas de tránsito
Route::get('/multas-transito-publico', fn () => view('tesoreria.multas-publico'))->name('multas-transito-publico');

// Tema (debe funcionar sin autenticar para el formulario de login)
Route::post('/tema/cambiar', [ThemeController::class, 'switchTheme'])->name('theme.switch');

// Autenticación
Route::get('/login',  [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Utilidades públicas
Route::get('/valor-ur',                        [UtilidadController::class, 'getValorUr'])          ->name('utilidad.valor_ur');
Route::get('/hora-uruguay',                    [UtilidadController::class, 'getHoraUruguay'])       ->name('utilidad.hora-uruguay');
Route::get('/utilidad/actualizar-soa-art-184', [UtilidadController::class, 'actualizarValoresSoa']) ->name('utilidad.actualizar-soa');

// ============================================================================
// RUTAS PROTEGIDAS POR JWT
// ============================================================================

Route::middleware(['web', 'jwt.verify', 'two-factor'])->group(function () {

    // ------------------------------------------------------------------------
    // Sesión y 2FA
    // ------------------------------------------------------------------------
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/session/keep-alive', [AuthController::class, 'keepAlive'])->name('session.keep-alive');

    Route::get('/two-factor-challenge',  [TwoFactorController::class, 'showChallenge'])          ->name('two-factor.login');
    Route::post('/two-factor-challenge', [TwoFactorController::class, 'verifyChallenge'])         ->name('two-factor.verify');
    Route::get('/two-factor-authentication',            [TwoFactorController::class, 'index'])                  ->name('two-factor.index');
    Route::post('/two-factor-authentication/enable',    [TwoFactorController::class, 'enable'])                 ->name('two-factor.enable');
    Route::delete('/two-factor-authentication/disable', [TwoFactorController::class, 'disable'])                ->name('two-factor.disable');
    Route::post('/two-factor-authentication/regenerate',[TwoFactorController::class, 'regenerateRecoveryCodes'])->name('two-factor.regenerate');

    // ------------------------------------------------------------------------
    // Panel principal
    // ------------------------------------------------------------------------
    Route::get('/panel', [PanelController::class, 'index'])->name('panel');

    // ------------------------------------------------------------------------
    // Respaldos del sistema
    // ------------------------------------------------------------------------
    Route::prefix('system/backups')->name('system.backups.')->middleware(['can:administrar_sistema,web'])->group(function () {
        Route::get('/',                  [BackupController::class, 'index'])    ->name('index');
        Route::get('/create',            [BackupController::class, 'create'])   ->name('create');
        Route::post('/restore',          [BackupController::class, 'restore'])  ->name('restore');
        Route::get('/download/{file}',   [BackupController::class, 'download']) ->name('download')->where('file', '.*');
        Route::delete('/',               [BackupController::class, 'delete'])   ->name('delete');
    });

    // ------------------------------------------------------------------------
    // Historial de auditoría
    // ------------------------------------------------------------------------
    Route::middleware(['role:administrador|gerente_tesoreria|supervisor_tesoreria'])->group(function () {
        Route::get('/sistema/auditoria', \App\Http\Livewire\Sistema\Auditoria\Index::class)->name('sistema.auditoria.index');
    });

    // ------------------------------------------------------------------------
    // Pendrive virtual
    // ------------------------------------------------------------------------
    Route::prefix('pendrive')->name('pendrive.')->group(function () {
        Route::get('/',                  [PendriveController::class, 'index'])     ->name('index');
        Route::post('upload',            [PendriveController::class, 'upload'])    ->name('upload');
        Route::get('download/{filename}', [PendriveController::class, 'download']) ->name('download');
        Route::get('thumbnail/{filename}',[PendriveController::class, 'getThumbnail'])->name('thumbnail');
        Route::delete('{filename}',       [PendriveController::class, 'destroy'])  ->name('destroy');
    });

    // ------------------------------------------------------------------------
    // Módulos de dominio — cargados desde archivos específicos
    // ------------------------------------------------------------------------

    // Administración del sistema (usuarios, roles, permisos, módulos, API)
    require __DIR__ . '/administracion.php';

    // Tesorería (todos sus submódulos bajo prefix 'tesoreria')
    Route::prefix('tesoreria')->name('tesoreria.')->group(function () {
        require __DIR__ . '/tesoreria.php';
    });
});

// ============================================================================
// FALLBACK — 404 personalizado
// ============================================================================

if (!app()->runningUnitTests()) {
    Route::fallback(function () {
        if (request()->expectsJson()) {
            return response()->json(['message' => 'Ruta no encontrada'], 404);
        }

        return auth()->check()
            ? response()->view('errors.404', [], 404)
            : redirect()->route('login');
    });
}
