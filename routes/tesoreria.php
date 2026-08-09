<?php

/**
 * routes/tesoreria.php
 *
 * Rutas del módulo de Tesorería.
 * Se incluye dentro del grupo JWT + prefix('tesoreria') de web.php.
 * Sin closures — compatible con route:cache.
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
use App\Http\Controllers\Tesoreria\ViewController;
use App\Livewire\Tesoreria\Arrendamientos\PrintArrendamientos;
use App\Livewire\Tesoreria\Arrendamientos\PrintArrendamientosFull;
use App\Livewire\Tesoreria\Eventuales\PrintEventuales;
use App\Livewire\Tesoreria\Eventuales\PrintEventualesFull;
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
    ->middleware('modulo:tesoreria');

Route::get('cuentas-bancarias', [CuentaBancariaController::class, 'index'])
    ->name('cuentas-bancarias.index')
    ->middleware('modulo:tesoreria');

// ============================================================================
// CHEQUES
// ============================================================================

Route::prefix('cheques')->name('cheques.')->group(function () {
    Route::view('/', 'tesoreria.cheques.index')->name('index');
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

Route::view('multas-transito', 'tesoreria.multas')->name('multas-transito');
Route::view('multas-303-2023', 'tesoreria.multas-303-2023')->name('multas-303-2023');
Route::get('multas-transito/exportar-pdf',
    \App\Livewire\Tesoreria\PrintMultasArticulos::class
)->name('multas-transito.exportar-pdf');

// ============================================================================
// MULTAS COBRADAS
// ============================================================================

Route::prefix('multas-cobradas')->name('multas-cobradas.')->middleware(['modulo:tesoreria'])->group(function () {
    Route::view('/', 'tesoreria.multas-cobradas.index')->name('index');
    Route::view('cargar-cfe', 'tesoreria.multas-cobradas.cargar-cfe')->name('cargar-cfe');

    Route::get('imprimir-detalles/{fechaDesde}/{fechaHasta}',
        \App\Livewire\Tesoreria\MultasCobradas\PrintMultasCobradasFull::class
    )->name('imprimir-detalles');

    Route::get('imprimir-resumen/{fechaDesde}/{fechaHasta}',
        \App\Livewire\Tesoreria\MultasCobradas\PrintMultasCobradasResumen::class
    )->name('imprimir-resumen');

    Route::get('reportes',
        \App\Livewire\Tesoreria\MultasCobradas\MultasCobradasReporte::class
    )->name('reportes');

    Route::get('imprimir-avanzado',
        \App\Livewire\Tesoreria\MultasCobradas\PrintMultasCobradasAdvanced::class
    )->name('imprimir-avanzado');
});

// ============================================================================
// EVENTUALES
// ============================================================================

Route::prefix('eventuales')->name('eventuales.')->group(function () {
    Route::view('/', 'tesoreria.eventuales.index')->name('index');
    Route::view('instituciones', 'tesoreria.eventuales.instituciones')->name('instituciones');

    Route::get('planillas/imprimir/{id}', [ViewController::class, 'eventualPlanillaPrint'])->name('planillas-print');

    Route::get('imprimir/{year}/{mes}',         PrintEventuales::class)    ->name('imprimir');
    Route::get('imprimir-detalles/{year}/{mes}', PrintEventualesFull::class) ->name('imprimir-detalles');

    Route::get('cargar-efactura', [EventualesController::class, 'cargarEfactura'])->name('cargar-efactura');

    Route::get('reportes',
        \App\Livewire\Tesoreria\Eventuales\EventualesReporte::class
    )->name('reportes');

    Route::get('imprimir-avanzado',
        \App\Livewire\Tesoreria\Eventuales\PrintEventualesAdvanced::class
    )->name('imprimir-avanzado');
});

// ============================================================================
// ARRENDAMIENTOS
// ============================================================================

Route::prefix('arrendamientos')->name('arrendamientos.')->group(function () {
    Route::get('/', [ArrendamientoController::class, 'index'])->name('index');
    Route::view('cargar-cfe', 'tesoreria.arrendamientos.cargar-cfe')->name('cargar-cfe');

    Route::get('planillas/imprimir/{id}', [ViewController::class, 'arrendamientoPlanillaPrint'])->name('planillas-print');

    Route::get('imprimir/{year}/{mes}',      PrintArrendamientos::class)     ->name('imprimir');
    Route::get('imprimir-todo/{year}/{mes}', PrintArrendamientosFull::class) ->name('imprimir-todo');

    Route::get('reportes',
        \App\Livewire\Tesoreria\Arrendamientos\ArrendamientosReporte::class
    )->name('reportes');

    Route::get('imprimir-avanzado',
        \App\Livewire\Tesoreria\Arrendamientos\PrintArrendamientosAdvanced::class
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
        \App\Livewire\Tesoreria\Armas\PorteArmasReporte::class
    )->name('porte.reportes');

    Route::get('porte/imprimir-avanzado',
        \App\Livewire\Tesoreria\Armas\PrintPorteArmasAdvanced::class
    )->name('porte.imprimir-avanzado');

    Route::get('tenencia/reportes',
        \App\Livewire\Tesoreria\Armas\TenenciaArmasReporte::class
    )->name('tenencia.reportes');

    Route::get('tenencia/imprimir-avanzado',
        \App\Livewire\Tesoreria\Armas\PrintTenenciaArmasAdvanced::class
    )->name('tenencia.imprimir-avanzado');

    Route::prefix('porte/planillas')->name('porte.planillas.')->group(function () {
        Route::get('/',            \App\Livewire\Tesoreria\Armas\Planillas\TesPorteArmasPlanillasIndex::class) ->name('index');
        Route::get('/{id}',        \App\Livewire\Tesoreria\Armas\Planillas\TesPorteArmasPlanillasShow::class)  ->name('show');
        Route::get('/{id}/imprimir', [ArmasImpresionController::class, 'imprimirPlanillaPorte'])                      ->name('imprimir');
    });

    Route::prefix('tenencia/planillas')->name('tenencia.planillas.')->group(function () {
        Route::get('/',            \App\Livewire\Tesoreria\Armas\Planillas\TesTenenciaArmasPlanillasIndex::class) ->name('index');
        Route::get('/{id}',        \App\Livewire\Tesoreria\Armas\Planillas\TesTenenciaArmasPlanillasShow::class)  ->name('show');
        Route::get('/{id}/imprimir', [ArmasImpresionController::class, 'imprimirPlanillaTenencia'])                      ->name('imprimir');
    });
});

// ============================================================================
// CERTIFICADOS DE RESIDENCIA
// ============================================================================

Route::prefix('certificados-residencia')->name('certificados-residencia.')->group(function () {
    Route::get('/', \App\Livewire\Tesoreria\CertificadosResidencia\Index::class)->name('index');
    Route::view('cargar-cfe', 'tesoreria.certificados-residencia.cargar-cfe')->name('cargar-cfe');

    Route::get('reportes',
        \App\Livewire\Tesoreria\CertificadosResidencia\CertificadosReporte::class
    )->name('reportes');

    Route::get('imprimir-avanzado',
        \App\Livewire\Tesoreria\CertificadosResidencia\PrintCertificadosAdvanced::class
    )->name('imprimir-avanzado');
});

// ============================================================================
// TARJETAS DE COBRO BROU
// ============================================================================

Route::prefix('tarjetas-cobro-brou')->name('tarjetas-cobro-brou.')->group(function () {
    Route::get('/', \App\Livewire\Tesoreria\TarjetasCobroBrou\Index::class)->name('index');

    Route::get('reportes',
        \App\Livewire\Tesoreria\TarjetasCobroBrou\TarjetasReporte::class
    )->name('reportes');

    Route::get('imprimir-avanzado',
        \App\Livewire\Tesoreria\TarjetasCobroBrou\PrintTarjetasAdvanced::class
    )->name('imprimir-avanzado');
});
// ============================================================================
// GESTIÓN DE CFES
// ============================================================================

Route::prefix('gestion-cfe')->name('gestion-cfe.')->group(function () {
    Route::get('/', \App\Livewire\Tesoreria\GestionCfe\Index::class)->name('index');
    Route::get('/dashboard', \App\Livewire\Tesoreria\GestionCfe\Dashboard::class)->name('dashboard');
    Route::get('/estados-recaudacion', \App\Livewire\Tesoreria\EstadosRecaudacion\Index::class)
        ->name('estados-recaudacion');
    Route::get('/estados-recaudacion/no-confirmadas', \App\Livewire\Tesoreria\EstadosRecaudacion\NoConfirmadas::class)
        ->name('estados-recaudacion.no-confirmadas');
    Route::get('/estados-recaudacion/confirmar/{planilla}', \App\Livewire\Tesoreria\EstadosRecaudacion\Confirmar::class)
        ->name('estados-recaudacion.confirmar');
    Route::get('/recaudaciones', \App\Livewire\Tesoreria\Recaudaciones\Index::class)
        ->name('recaudaciones');
});

Route::prefix('libro-diario')->name('libro-diario.')->group(function () {
    Route::get('/', \App\Livewire\Tesoreria\LibroDiario\Index::class)
        ->name('index');
    Route::redirect('asientos', '/tesoreria/libro-diario')
        ->name('asientos');

    Route::view('lb-tipos', 'tesoreria.libro-diario.lb-tipos.index-livewire')
        ->name('lb-tipos.index');
    Route::view('lb-conceptos', 'tesoreria.libro-diario.lb-conceptos.index-livewire')
        ->name('lb-conceptos.index');
    Route::view('lb-detalle', 'tesoreria.libro-diario.lb-detalle.index-livewire')
        ->name('lb-detalle.index');
    Route::view('medios-de-pago', 'tesoreria.libro-diario.medios-de-pago.index-livewire')
        ->name('medios-de-pago.index');

    Route::get('/carga-masiva-haberes', \App\Livewire\Tesoreria\CargaMasivaHaberes\Index::class)
        ->name('carga-masiva-haberes');
});

Route::prefix('caja-diaria')->name('caja-diaria.')->middleware(['modulo:tesoreria'])->group(function () {
    Route::get('/', \App\Livewire\Tesoreria\CajaDiaria\Index::class)->name('index');
    Route::get('apertura-cierre', \App\Livewire\Tesoreria\CajaDiaria\AperturaCierre::class)->name('apertura-cierre');
    Route::get('arqueo', \App\Livewire\Tesoreria\CajaDiaria\Arqueo::class)->name('arqueo');
    Route::get('movimientos', \App\Livewire\Tesoreria\CajaDiaria\Movimientos::class)->name('movimientos');
});

Route::prefix('cfe')->name('cfe.')->group(function () {
    Route::get('/pendientes', \App\Livewire\CfePendientesIndex::class)->name('pendientes');
    Route::get('/monitoring', \App\Livewire\Tesoreria\CfeMonitoring\Index::class)->name('monitoring');
});

// ============================================================================
// PRENDAS
// ============================================================================

Route::prefix('prendas')->name('prendas.')->group(function () {
    Route::get('/', \App\Livewire\Tesoreria\Prendas\Index::class)->name('index');
    Route::view('cargar-cfe', 'tesoreria.prendas.cargar-cfe')->name('cargar-cfe');

    Route::get('reportes',
        \App\Livewire\Tesoreria\Prendas\PrendasReporte::class
    )->name('reportes');

    Route::get('imprimir-avanzado',
        \App\Livewire\Tesoreria\Prendas\PrintPrendasAdvanced::class
    )->name('imprimir-avanzado');

    Route::prefix('planillas')->name('planillas.')->group(function () {
        Route::get('/',              \App\Livewire\Tesoreria\Prendas\Planillas\Index::class) ->name('index');
        Route::get('/{id}',          \App\Livewire\Tesoreria\Prendas\Planillas\Show::class)  ->name('show');
        Route::get('/{id}/imprimir', [ViewController::class, 'prendaPlanillaPrint'])->name('print');
    });
});

// ============================================================================
// DEPÓSITO DE VEHÍCULOS
// ============================================================================

Route::prefix('deposito-vehiculos')->name('deposito-vehiculos.')->group(function () {
    Route::get('/', [DepositoVehiculosController::class, 'index'])->name('index');

    Route::get('reportes',
        \App\Livewire\Tesoreria\DepositoVehiculos\DepositoVehiculosReporte::class
    )->name('reportes');

    Route::get('imprimir-avanzado', [ViewController::class, 'imprimirAvanzadoNoImplementado'])
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

Route::prefix('configuracion')->name('configuracion.')->middleware('modulo:tesoreria')->group(function () {
    Route::view('medios-de-pago', 'tesoreria.configuracion.medios-de-pago.index-livewire')
        ->name('medios-de-pago.index');

    Route::view('tipos-monedas', 'tesoreria.configuracion.tes-tipos-monedas.index-livewire')
        ->name('tes-tipos-monedas.index');

    Route::view('discriminaciones-monetarias', 'tesoreria.configuracion.tes-discriminaciones-monetarias.index-livewire')
        ->name('tes-discriminaciones-monetarias.index');

    Route::view('caja-conceptos', 'tesoreria.configuracion.caja-conceptos.index-livewire')
        ->name('caja-conceptos.index');

    Route::view('siif-distribucion-dependencias', 'tesoreria.configuracion.siif-distribucion-dependencias.index-livewire')
        ->name('siif-distribucion-dependencias.index');

    Route::view('siif-distribucion-tipos', 'tesoreria.configuracion.siif-distribucion-tipos.index-livewire')
        ->name('siif-distribucion-tipos.index');

    Route::view('siif-distribuciones', 'tesoreria.configuracion.siif-distribuciones.index-livewire')
        ->name('siif-distribuciones.index');
});
// ============================================================================
// CAJA CHICA
// ============================================================================

Route::prefix('caja-chica')->name('caja-chica.')->middleware(['modulo:tesoreria'])->group(function () {
    Route::get('/',                             [CajaChicaController::class, 'index'])  ->name('index');
    Route::get('pendientes/{id}/editar',        [PendienteController::class, 'edit'])   ->name('pendientes.editar');
    Route::get('imprimir/pendiente/{id}', [CajaChicaImpresionController::class, 'imprimirPendiente'])->name('imprimir.pendiente');
    Route::get('imprimir/pago/{id}',      [CajaChicaImpresionController::class, 'imprimirPago'])     ->name('imprimir.pago');
    Route::get('exportar-excel',          [CajaChicaController::class, 'exportarExcel'])->name('exportar-excel');
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
    ->middleware(['modulo:tesoreria,supervisor'])
    ->group(function () {
        Route::get('/',       \App\Livewire\Tesoreria\ReporteRecibos\ReporteRecibosIndex::class) ->name('index');
        Route::get('imprimir', \App\Livewire\Tesoreria\ReporteRecibos\PrintReporteRecibos::class) ->name('imprimir');
        Route::get('exportar-excel', [ReporteRecibosController::class, 'exportarExcel'])               ->name('exportar-excel');
    });
