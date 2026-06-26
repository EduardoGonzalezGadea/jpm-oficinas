<?php

/**
 * routes/asesoria_contable.php
 *
 * Rutas del módulo de Asesoría Contable.
 * Se incluye dentro del grupo JWT + prefix('asesoria-contable') de web.php.
 */

use App\Http\Livewire\AsesoriaContable\EstadosRecaudacion\Index as EstadosRecaudacionIndex;
use App\Http\Livewire\AsesoriaContable\PlanillasNoCompletadas\Index as PlanillasNoCompletadasIndex;
use App\Http\Livewire\AsesoriaContable\ResumenRecaudaciones\Index as ResumenRecaudacionesIndex;
use Illuminate\Support\Facades\Route;

// ============================================================================
// ESTADOS DE RECAUDACIÓN
// ============================================================================

Route::get('estados-recaudacion', EstadosRecaudacionIndex::class)
    ->name('estados-recaudacion');

Route::get('planillas-no-completadas', PlanillasNoCompletadasIndex::class)
    ->name('planillas-no-completadas');

// ============================================================================
// RESUMEN DE RECAUDACIONES
// ============================================================================

Route::get('resumen-recaudaciones', ResumenRecaudacionesIndex::class)
    ->name('resumen-recaudaciones');
