<?php

/**
 * routes/tesoreria.php
 *
 * Rutas del módulo de Tesorería.
 * Se incluye dentro del grupo JWT + prefix('tesoreria') de web.php.
 */

use App\Http\Controllers\Tesoreria\ArmasController;
use App\Http\Controllers\Tesoreria\Armas\ImpresionController as ArmasImpresionController;
use App\Http\Controllers\Tesoreria\ArrendamientoController;
use App\Http\Controllers\Tesoreria\BancoController;
use App\Http\Controllers\Tesoreria\CajaChica\CajaChicaController;
use App\Http\Controllers\Tesoreria\CajaChica\ImpresionController as CajaChicaImpresionController;
use App\Http\Controllers\Tesoreria\CajaChica\PendienteController;
use App\Http\Controllers\Tesoreria\CuentaBancariaController;
use App\Http\Controllers\Tesoreria\ChequeController;
use App\Http\Controllers\Tesoreria\DepositoVehiculosController;
use App\Http\Controllers\Tesoreria\EventualesController;
use App\Http\Controllers\Tesoreria\ReporteRecibosController;
use App\Http\Controllers\Tesoreria\StockChequesController;
use App\Http\Controllers\Tesoreria\StockReporteController;
use App\Http\Controllers\Tesoreria\TesoreriaController;
use App\Http\Livewire\Tesoreria\Arrendamientos\PrintArrendamientos;
use App\Http\Livewire\Tesoreria\Arrendamientos\PrintArrendamientosFull;
use App\Http\Livewire\Tesoreria\Eventuales\PrintEventuales;
use App\Http\Livewire\Tesoreria\Eventuales\PrintEventualesFull;
use Illuminate\Support\Facades\Route;

// ============================================================================
// TESORERÍA — Dashboard
// ============================================================================

Route::get('/', [TesoreriaController::class, 'index'])->name('index');

// ============================================================================
// BANCOS Y CUENTAS BANCARIAS
// ============================================================================

Route::get('bancos', [BancoController::class, 'index'])
    ->name('bancos.index')
    ->middleware('auth');

Route::get('cuentas-bancarias', [CuentaBancariaController::class, 'index'])
    ->name('cuentas-bancarias.index');

// ============================================================================
// CHEQUES
// ============================================================================

Route::prefix('cheques')->name('cheques.')->group(function () {
    Route::get('/', fn () => view('tesoreria.cheques.index'))->name('index');
    Route::get('libreta',                   [ChequeController::class, 'libreta'])         ->name('libreta');
    Route::get('emitir',                    [ChequeController::class, 'emitir'])           ->name('emitir');
    Route::get('planilla/generar',          [ChequeController::class, 'planillaGenerar'])  ->name('planilla.generar');
    Route::get('planilla/{id}',             [ChequeController::class, 'planillaVer'])      ->name('planilla.ver');
    Route::get('planilla/{id}/imprimir',    [ChequeController::class, 'imprimirPlanilla']) ->name('planilla.imprimir');
    Route::get('reportes',                  [ChequeController::class, 'reportes'])         ->name('reportes');
});

Route::post('cheques/reportes/upload-stock',               [StockChequesController::class, 'upload'])   ->name('cheques.reportes.upload-stock');
Route::get('cheques/reportes/download-stock/{filename}',   [StockChequesController::class, 'download']) ->name('cheques.reportes.download-stock');

// ============================================================================
// MULTAS DE TRÁNSITO (vista pública dentro del área autenticada)
// ============================================================================

Route::get('multas-transito', fn () => view('tesoreria.multas'))->name('multas-transito');
Route::get('multas-303-2023', fn () => view('tesoreria.multas-303-2023'))->name('multas-303-2023');
Route::get('multas-transito/exportar-pdf',
    \App\Http\Livewire\Tesoreria\PrintMultasArticulos::class
)->name('multas-transito.exportar-pdf');

// ============================================================================
// MULTAS COBRADAS
// ============================================================================

Route::prefix('multas-cobradas')->name('multas-cobradas.')->middleware(['can:operador_tesoreria'])->group(function () {
    Route::get('/',         fn () => view('tesoreria.multas-cobradas.index'))     ->name('index');
    Route::get('cargar-cfe', fn () => view('tesoreria.multas-cobradas.cargar-cfe')) ->name('cargar-cfe');

    Route::get('imprimir-detalles/{fechaDesde}/{fechaHasta}',
        \App\Http\Livewire\Tesoreria\MultasCobradas\PrintMultasCobradasFull::class
    )->name('imprimir-detalles');

    Route::get('imprimir-resumen/{fechaDesde}/{fechaHasta}',
        \App\Http\Livewire\Tesoreria\MultasCobradas\PrintMultasCobradasResumen::class
    )->name('imprimir-resumen');

    Route::get('reportes',
        \App\Http\Livewire\Tesoreria\MultasCobradas\MultasCobradasReporte::class
    )->name('reportes');

    Route::get('imprimir-avanzado',
        \App\Http\Livewire\Tesoreria\MultasCobradas\PrintMultasCobradasAdvanced::class
    )->name('imprimir-avanzado');
});

// ============================================================================
// EVENTUALES
// ============================================================================

Route::prefix('eventuales')->name('eventuales.')->group(function () {
    Route::get('/',          fn () => view('tesoreria.eventuales.index'))        ->name('index');
    Route::get('instituciones', fn () => view('tesoreria.eventuales.instituciones')) ->name('instituciones');

    Route::get('planillas/imprimir/{id}', function ($id) {
        $planilla = \App\Models\Tesoreria\EventualPlanilla::findOrFail($id);
        return view('tesoreria.eventuales.planillas-print', compact('planilla'));
    })->name('planillas-print');

    Route::get('imprimir/{year}/{mes}',         PrintEventuales::class)    ->name('imprimir');
    Route::get('imprimir-detalles/{year}/{mes}', PrintEventualesFull::class) ->name('imprimir-detalles');

    Route::get('cargar-efactura', [EventualesController::class, 'cargarEfactura'])->name('cargar-efactura');

    Route::get('reportes',
        \App\Http\Livewire\Tesoreria\Eventuales\EventualesReporte::class
    )->name('reportes');

    Route::get('imprimir-avanzado',
        \App\Http\Livewire\Tesoreria\Eventuales\PrintEventualesAdvanced::class
    )->name('imprimir-avanzado');
});

// ============================================================================
// ARRENDAMIENTOS
// ============================================================================

Route::prefix('arrendamientos')->name('arrendamientos.')->group(function () {
    Route::get('/', [ArrendamientoController::class, 'index'])->name('index');
    Route::get('cargar-cfe', fn () => view('tesoreria.arrendamientos.cargar-cfe'))->name('cargar-cfe');

    Route::get('planillas/imprimir/{id}', function ($id) {
        $planilla = \App\Models\Tesoreria\Planilla::findOrFail($id);
        return view('tesoreria.arrendamientos.planillas-print', compact('planilla'));
    })->name('planillas-print');

    Route::get('imprimir/{year}/{mes}',      PrintArrendamientos::class)     ->name('imprimir');
    Route::get('imprimir-todo/{year}/{mes}', PrintArrendamientosFull::class) ->name('imprimir-todo');

    Route::get('reportes',
        \App\Http\Livewire\Tesoreria\Arrendamientos\ArrendamientosReporte::class
    )->name('reportes');

    Route::get('imprimir-avanzado',
        \App\Http\Livewire\Tesoreria\Arrendamientos\PrintArrendamientosAdvanced::class
    )->name('imprimir-avanzado');
});

// ============================================================================
// ARMAS (Porte y Tenencia)
// ============================================================================

Route::prefix('armas')->name('armas.')->group(function () {
    Route::get('porte',    [ArmasController::class, 'porte'])    ->name('porte');
    Route::get('tenencia', [ArmasController::class, 'tenencia']) ->name('tenencia');
    Route::get('cargar-cfe', [ArmasController::class, 'cargarCfe'])->name('cargar-cfe');

    Route::get('tenencia/imprimir/{id}', [ArmasImpresionController::class, 'imprimirTenencia'])->name('tenencia.imprimir');
    Route::get('porte/imprimir/{id}',    [ArmasImpresionController::class, 'imprimirPorte'])    ->name('porte.imprimir');

    Route::get('porte/reportes',
        \App\Http\Livewire\Tesoreria\Armas\PorteArmasReporte::class
    )->name('porte.reportes');

    Route::get('porte/imprimir-avanzado',
        \App\Http\Livewire\Tesoreria\Armas\PrintPorteArmasAdvanced::class
    )->name('porte.imprimir-avanzado');

    Route::get('tenencia/reportes',
        \App\Http\Livewire\Tesoreria\Armas\TenenciaArmasReporte::class
    )->name('tenencia.reportes');

    Route::get('tenencia/imprimir-avanzado',
        \App\Http\Livewire\Tesoreria\Armas\PrintTenenciaArmasAdvanced::class
    )->name('tenencia.imprimir-avanzado');

    Route::prefix('porte/planillas')->name('porte.planillas.')->group(function () {
        Route::get('/',            \App\Http\Livewire\Tesoreria\Armas\Planillas\TesPorteArmasPlanillasIndex::class) ->name('index');
        Route::get('/{id}',        \App\Http\Livewire\Tesoreria\Armas\Planillas\TesPorteArmasPlanillasShow::class)  ->name('show');
        Route::get('/{id}/imprimir', [ArmasImpresionController::class, 'imprimirPlanillaPorte'])                      ->name('imprimir');
    });

    Route::prefix('tenencia/planillas')->name('tenencia.planillas.')->group(function () {
        Route::get('/',            \App\Http\Livewire\Tesoreria\Armas\Planillas\TesTenenciaArmasPlanillasIndex::class) ->name('index');
        Route::get('/{id}',        \App\Http\Livewire\Tesoreria\Armas\Planillas\TesTenenciaArmasPlanillasShow::class)  ->name('show');
        Route::get('/{id}/imprimir', [ArmasImpresionController::class, 'imprimirPlanillaTenencia'])                      ->name('imprimir');
    });
});

// ============================================================================
// CERTIFICADOS DE RESIDENCIA
// ============================================================================

Route::prefix('certificados-residencia')->name('certificados-residencia.')->group(function () {
    Route::get('/', \App\Http\Livewire\Tesoreria\CertificadosResidencia\Index::class)->name('index');
    Route::get('cargar-cfe', fn () => view('tesoreria.certificados-residencia.cargar-cfe'))->name('cargar-cfe');

    Route::get('reportes',
        \App\Http\Livewire\Tesoreria\CertificadosResidencia\CertificadosReporte::class
    )->name('reportes');

    Route::get('imprimir-avanzado',
        \App\Http\Livewire\Tesoreria\CertificadosResidencia\PrintCertificadosAdvanced::class
    )->name('imprimir-avanzado');
});

// ============================================================================
// PRENDAS
// ============================================================================

Route::prefix('prendas')->name('prendas.')->group(function () {
    Route::get('/', \App\Http\Livewire\Tesoreria\Prendas\Index::class)->name('index');
    Route::get('cargar-cfe', fn () => view('tesoreria.prendas.cargar-cfe'))->name('cargar-cfe');

    Route::get('reportes',
        \App\Http\Livewire\Tesoreria\Prendas\PrendasReporte::class
    )->name('reportes');

    Route::get('imprimir-avanzado',
        \App\Http\Livewire\Tesoreria\Prendas\PrintPrendasAdvanced::class
    )->name('imprimir-avanzado');

    Route::prefix('planillas')->name('planillas.')->group(function () {
        Route::get('/',              \App\Http\Livewire\Tesoreria\Prendas\Planillas\Index::class) ->name('index');
        Route::get('/{id}',          \App\Http\Livewire\Tesoreria\Prendas\Planillas\Show::class)  ->name('show');
        Route::get('/{id}/imprimir', function ($id) {
            $planilla = \App\Models\Tesoreria\PrendaPlanilla::findOrFail($id);
            return view('tesoreria.prendas.planillas-print', compact('planilla'));
        })->name('print');
    });
});

// ============================================================================
// DEPÓSITO DE VEHÍCULOS
// ============================================================================

Route::prefix('deposito-vehiculos')->name('deposito-vehiculos.')->group(function () {
    Route::get('/', [DepositoVehiculosController::class, 'index'])->name('index');

    Route::get('reportes',
        \App\Http\Livewire\Tesoreria\DepositoVehiculos\DepositoVehiculosReporte::class
    )->name('reportes');

    Route::get('imprimir-avanzado', fn () => 'Impresión Avanzada No Implementada aún')
        ->name('imprimir-avanzado');
});

Route::prefix('deposito-vehiculos/planillas')->name('deposito-vehiculos.planillas.')->group(function () {
    Route::get('/',              [DepositoVehiculosController::class, 'planillasIndex']) ->name('index');
    Route::get('/{id}',          [DepositoVehiculosController::class, 'planillaShow'])   ->name('show');
    Route::get('/{id}/imprimir', [DepositoVehiculosController::class, 'planillaPrint'])  ->name('print');
});

// ============================================================================
// CONFIGURACIÓN
// ============================================================================

Route::prefix('configuracion')->name('configuracion.')->group(function () {
    Route::get('medios-de-pago',
        fn () => view('tesoreria.configuracion.medios-de-pago.index-livewire')
    )->name('medios-de-pago.index');

    Route::get('tipos-monedas',
        fn () => view('tesoreria.configuracion.tes-tipos-monedas.index-livewire')
    )->name('tes-tipos-monedas.index');

    Route::get('denominaciones-monedas',
        fn () => view('tesoreria.configuracion.tes-denominaciones-monedas.index-livewire')
    )->name('tes-denominaciones-monedas.index');
});
// ============================================================================
// CAJA CHICA
// ============================================================================

Route::prefix('caja-chica')->name('caja-chica.')->middleware(['permission:operador_tesoreria'])->group(function () {
    Route::get('/',                             [CajaChicaController::class, 'index'])  ->name('index');
    Route::get('pendientes/{id}/editar',        [PendienteController::class, 'edit'])   ->name('pendientes.editar');
    Route::get('imprimir/pendiente/{id}', [CajaChicaImpresionController::class, 'imprimirPendiente'])->name('imprimir.pendiente');
    Route::get('imprimir/pago/{id}',      [CajaChicaImpresionController::class, 'imprimirPago'])     ->name('imprimir.pago');
});

// ============================================================================
// VALORES (incluido desde su propio archivo)
// ============================================================================

require __DIR__ . '/valores.php';

Route::post('valores/reportes/upload-stock',             [StockReporteController::class, 'upload'])   ->name('valores.reportes.upload-stock');
Route::get('valores/reportes/download-stock/{filename}', [StockReporteController::class, 'download']) ->name('valores.reportes.download-stock');

// ============================================================================
// REPORTE DE RECIBOS PARA CONTABILIDAD
// ============================================================================

Route::prefix('reporte-recibos')
    ->name('reporte-recibos.')
    ->middleware(['role:administrador|gerente_tesoreria|supervisor_tesoreria'])
    ->group(function () {
        Route::get('/',       \App\Http\Livewire\Tesoreria\ReporteRecibos\ReporteRecibosIndex::class) ->name('index');
        Route::get('imprimir', \App\Http\Livewire\Tesoreria\ReporteRecibos\PrintReporteRecibos::class) ->name('imprimir');
        Route::get('exportar-excel', [ReporteRecibosController::class, 'exportarExcel'])               ->name('exportar-excel');
    });
