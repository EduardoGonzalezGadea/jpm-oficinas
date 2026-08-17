<?php

namespace App\Livewire\Tesoreria\CajaDiaria;

use App\Exceptions\Tesoreria\CfeNotFoundException;
use App\Exceptions\Tesoreria\CfeValidationException;
use App\Models\Tesoreria\Cajas\CajaApertura;
use App\Models\Tesoreria\Cajas\CajaMovimiento;
use App\Models\Tesoreria\LibroDiario;
use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\MedioDePago;
use App\Services\Tesoreria\CfeCreatorService;
use App\Services\Tesoreria\LibroDiarioService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Index extends Component
{
    public $fechaSeleccionada;
    public $cajaTrabajo = null;
    public $tab = 'mi-caja'; // 'mi-caja' | 'cajas' | 'movimientos'
    public $cajaSeleccionada = null;
    public $movimientosCaja = [];

    protected LibroDiarioService $service;
    protected CfeCreatorService $cfeCreator;

    public function boot(LibroDiarioService $service, CfeCreatorService $cfeCreator)
    {
        $this->service = $service;
        $this->cfeCreator = $cfeCreator;
    }

    public function mount()
    {
        $this->fechaSeleccionada = today()->format('Y-m-d');
        $this->cargarCajaTrabajo();
        $this->tab = $this->cajaTrabajo ? 'mi-caja' : 'cajas';
    }

    public function cambiarTab($tab)
    {
        $this->tab = $tab;
    }

    public function updatedFechaSeleccionada()
    {
        $this->cajaTrabajo = null;
        $this->cargarCajaTrabajo();
    }

    public function irAHoy()
    {
        $this->fechaSeleccionada = today()->format('Y-m-d');
        $this->cargarCajaTrabajo();
    }

    public function verMovimientos($cajaId)
    {
        $caja = CajaApertura::with('cajero')->find($cajaId);
        if (!$caja) {
            return;
        }

        $this->cajaSeleccionada = $caja;
        $this->movimientosCaja = $caja->movimientos()
            ->conLibroVigente()
            ->with(['medioPago', 'creador', 'libroDiario'])
            ->orderByDesc('created_at')
            ->get();

        $this->dispatch('show-modal', ['id' => 'modalMovimientosCaja']);
    }

    /**
     * Refresca el listado de movimientos de la caja seleccionada.
     */
    protected function refrescarMovimientosCaja()
    {
        if (!$this->cajaSeleccionada) {
            return;
        }

        // Recargar la caja seleccionada para obtener datos actualizados
        $this->cajaSeleccionada = CajaApertura::with('cajero')->find($this->cajaSeleccionada->id);

        if (!$this->cajaSeleccionada) {
            return;
        }

        $this->movimientosCaja = $this->cajaSeleccionada->movimientos()
            ->conLibroVigente()
            ->with(['medioPago', 'creador', 'libroDiario'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Elimina un movimiento de caja y su asiento asociado en el Libro Diario,
     * manteniendo la coherencia entre ambas secciones.
     *
     * Si el asiento pertenece a una recaudación (CFE), delega en la misma lógica
     * de Gestión de Recaudaciones (manteniendo sus restricciones: ítems no integrados
     * en planilla, recálculo de saldos) para que el impacto se refleje también en
     * el libro diario y en la gestión de CFEs.
     */
    public function eliminarMovimiento($movimientoId)
    {
        $movimiento = CajaMovimiento::with('cajaApertura')->find($movimientoId);
        if (!$movimiento) {
            return;
        }

        // No se permite eliminar movimientos de una caja ya cerrada.
        if ($movimiento->cajaApertura && $movimiento->cajaApertura->estado === 'cerrada') {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'No se puede eliminar un movimiento de una caja cerrada.',
            ]);
            return;
        }

        $asiento = $movimiento->libro_diario_id
            ? LibroDiario::find($movimiento->libro_diario_id)
            : null;

        // Recaudación: impacta CFE + asientos del libro diario + saldos.
        if ($asiento && $asiento->cfe_id) {
            $this->eliminarMovimientoRecaudacion($movimiento, $asiento->cfe_id);
            return;
        }

        try {
            DB::transaction(function () use ($movimiento) {
                // Elimina el asiento del Libro Diario con toda su lógica contable
                // (redistribución, asociados, recálculo de saldos y validaciones).
                // Su evento "deleted" limpia los CajaMovimiento vinculados.
                if ($movimiento->libro_diario_id) {
                    $this->service->eliminarAsiento($movimiento->libro_diario_id);
                }

                // Cobertura por si el movimiento no tenía asiento o el evento
                // no alcanzó a limpiarlo.
                $movimiento->delete();
            });
        } catch (\RuntimeException $e) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
            return;
        }

        $this->refrescarMovimientosCaja();
        $this->dispatch('alert', [
            'type' => 'success',
            'message' => 'Movimiento eliminado y su asiento del Libro Diario.',
        ]);
    }

    /**
     * Elimina una recaudación (CFE) desde la caja diaria reutilizando la lógica y
     * restricciones de Gestión de Recaudaciones: valida que ningún ítem integre una
     * planilla, elimina los asientos del Libro Diario, recalcula saldos y borra el CFE.
     * El modelo LibroDiario limpia en cascada los movimientos de caja vinculados.
     */
    protected function eliminarMovimientoRecaudacion(CajaMovimiento $movimiento, int $cfeId): void
    {
        try {
            DB::transaction(function () use ($movimiento, $cfeId) {
                // Esta operación ya toma sus propias precauciones internas
                // (assertItemsNotInPlanilla). El evento "deleted" de los asientos
                // asociados limpiará los CajaMovimiento vinculados.
                $this->cfeCreator->deleteCfeWithLibroDiarioEntries($cfeId);

                // Cobertura por si quedó algún movimiento residual vinculado.
                $movimiento->delete();
            });
        } catch (CfeNotFoundException | CfeValidationException | \RuntimeException $e) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
            return;
        }

        $this->refrescarMovimientosCaja();
        $this->dispatch('alert', [
            'type' => 'success',
            'message' => 'Recaudación (CFE), asientos del Libro Diario y movimiento eliminados. Saldos recalculados.',
        ]);
    }

    /**
     * Solicita confirmación antes de eliminar un movimiento, distinguiendo si se
     * trata de una recaudación (CFE) para mostrar el flujo específico.
     */
    public function confirmarEliminarMovimiento($movimientoId)
    {
        $movimiento = CajaMovimiento::find($movimientoId);
        if (!$movimiento) {
            return;
        }

        $asiento = $movimiento->libro_diario_id
            ? LibroDiario::find($movimiento->libro_diario_id)
            : null;

        if ($asiento && $asiento->cfe_id) {
            $cfe = \App\Models\Tesoreria\TesCfe::with('items')->find($asiento->cfe_id);
            $cantAsientos = $this->cfeCreator->countLibroDiarioEntries($asiento->cfe_id);

            $this->dispatch('swal:confirmar-eliminar-recaudacion', [
                'movimientoId' => $movimientoId,
                'cfeId' => $asiento->cfe_id,
                'cantidad' => $cantAsientos,
                'cfeTipo' => $cfe->documento_tipo ?? '',
                'cfeSerie' => ($cfe->documento_serie ?? '') ? "{$cfe->documento_serie}-" : '',
                'cfeNumero' => $cfe->documento_numero ?? '',
            ]);
            return;
        }

        $this->dispatch('confirm-eliminar-movimiento', ['id' => $movimientoId]);
    }

    /**
     * Determina la caja sobre la que el usuario logueado puede operar.
     *
     * Siempre es la caja abierta actual del usuario, sin importar la fecha
     * seleccionada ni en qué fecha se abrió (un mismo usuario puede abrir una
     * caja, cerrarla y abrir otra en el mismo día). Si el usuario no tiene
     * ninguna caja abierta, queda null y la pestaña "Mi Caja" muestra el
     * mensaje correspondiente.
     */
    protected function cargarCajaTrabajo()
    {
        if (empty($this->fechaSeleccionada)) {
            $this->cajaTrabajo = null;
            return;
        }

        $this->cajaTrabajo = CajaApertura::abiertas()
            ->porCajero(auth()->id())
            ->first();
    }

    public function render()
    {
        // Todas las cajas de la jornada seleccionada (de todos los usuarios)
        $cajasDelDia = CajaApertura::whereDate('fecha_apertura', $this->fechaSeleccionada)
            ->with('cajero')
            ->get();

        // Incluir la caja de trabajo si es una caja abierta de un día anterior
        if ($this->cajaTrabajo && $cajasDelDia->doesntContain('id', $this->cajaTrabajo->id)) {
            $cajasDelDia = $cajasDelDia->push($this->cajaTrabajo);
        }

        // La caja del usuario logueado se muestra primero
        $cajasDelDia = $cajasDelDia->sortBy('cajero.name')->values();
        $indicePropia = $cajasDelDia->search(fn ($c) => $c->cajero_id === auth()->id());
        if ($indicePropia !== false) {
            $cajaPropia = $cajasDelDia->pull($indicePropia);
            $cajasDelDia->prepend($cajaPropia);
        }

        $data = [
            'fechaSeleccionada' => $this->fechaSeleccionada,
            'esHoy' => $this->fechaSeleccionada === today()->format('Y-m-d'),
            'cajaTrabajo' => $this->cajaTrabajo,
            'tab' => $this->tab,
            'cajasDelDia' => $cajasDelDia,
        ];

        // Recaudaciones del día: asientos del libro diario con cfe_id (generados al cargar CFEs)
        $recaudacionesQuery = LibroDiario::whereDate('fecha', $this->fechaSeleccionada)
            ->whereNotNull('cfe_id')
            ->whereNull('deleted_at');

        $data['recaudacionesDia']   = $recaudacionesQuery->count();
        $data['totalRecaudadoDia']  = (float) $recaudacionesQuery->sum('monto');

        // Totales por medio del día (todos los asientos de la fecha, excluyendo caja chica)
        $conceptoCajaChicaId = LbConcepto::where('nombre', LbConcepto::CAJA_CHICA)->value('id');
        $data['totalesDiaPorMedio'] = LibroDiario::whereDate('tes_libro_diario.fecha', $this->fechaSeleccionada)
            ->whereNull('tes_libro_diario.deleted_at')
            ->when($conceptoCajaChicaId, fn($q) => $q->where('tes_libro_diario.concepto_id', '!=', $conceptoCajaChicaId))
            ->join('tes_medio_de_pagos', 'tes_libro_diario.medio_id', '=', 'tes_medio_de_pagos.id')
            ->whereNull('tes_medio_de_pagos.deleted_at')
            ->selectRaw('
                tes_libro_diario.medio_id,
                tes_medio_de_pagos.nombre as medio_nombre,
                SUM(CASE WHEN tes_libro_diario.signo_efectivo = 1  THEN tes_libro_diario.monto ELSE 0 END) as entradas,
                SUM(CASE WHEN tes_libro_diario.signo_efectivo = -1 THEN tes_libro_diario.monto ELSE 0 END) as salidas
            ')
            ->groupBy('tes_libro_diario.medio_id', 'tes_medio_de_pagos.nombre')
            ->get();

        // Referencia de caja chica del día (informativo, no forma parte de la caja diaria)
        if ($conceptoCajaChicaId) {
            $data['cajaChicaDia'] = LibroDiario::whereDate('fecha', $this->fechaSeleccionada)
                ->whereNull('deleted_at')
                ->where('concepto_id', $conceptoCajaChicaId)
                ->selectRaw('
                    SUM(CASE WHEN signo_efectivo = 1  THEN monto ELSE 0 END) as entradas,
                    SUM(CASE WHEN signo_efectivo = -1 THEN monto ELSE 0 END) as salidas
                ')
                ->first();
        } else {
            $data['cajaChicaDia'] = null;
        }

        if ($this->cajaTrabajo) {
            $data['movimientos'] = $this->cajaTrabajo->movimientos()
                ->conLibroVigente()
                ->with(['medioPago', 'creador', 'libroDiario'])
                ->orderByDesc('created_at')
                ->take(10)
                ->get();
            $data['totalIngresos'] = $this->cajaTrabajo->totalIngresos();
            $data['totalEgresos'] = $this->cajaTrabajo->totalEgresos();
            $data['totalIngresosOtros'] = $this->cajaTrabajo->totalIngresosOtros();
            $data['totalEgresosOtros'] = $this->cajaTrabajo->totalEgresosOtros();
            $data['saldoActual'] = $this->cajaTrabajo->obtenerSaldoActual();
            $data['saldoFinal'] = $this->cajaTrabajo->estado === 'cerrada'
                && $this->cajaTrabajo->saldo_cierre !== null
                ? (float) $this->cajaTrabajo->saldo_cierre
                : $data['saldoActual'];
        }

        return view('livewire.tesoreria.cajas.index', $data);
    }
}
