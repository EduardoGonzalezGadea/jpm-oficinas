<?php

namespace App\Http\Livewire\Tesoreria\LibroDiario;

use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
use App\Models\Tesoreria\LbMedio;
use App\Models\Tesoreria\LbTipo;
use App\Models\Tesoreria\LibroDiario;
use App\Models\Tesoreria\TesCfe;
use App\Services\Tesoreria\LibroDiarioService;
use Livewire\Component;

class Index extends Component
{
    protected LibroDiarioService $service;

    public $search = '';
    public $fecha_desde = '';
    public $fecha_hasta = '';
    public $filtro_tipo_id = '';
    public $filtro_concepto_id = '';
    public $filtro_detalle_id = '';
    public $anio;

    public $selectedItem = null;
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showRedistribucionModal = false;
    public $showDetailsModal = false;

    public $fecha;
    public $tipo_id;
    public $concepto_id;
    public $detalle_id;
    public $medio_id;
    public $monto;
    public $identidad;
    public $denominacion;
    public $descripcion;
    public $asiento_base_id;

    public $conceptos = [];
    public $detalles = [];
    public $detallesFiltro = [];
    public $tipos = [];
    public $medios = [];
    public $asientos_base = [];

    public $edit_id;
    public $edit_identidad;
    public $edit_denominacion;
    public $edit_descripcion;

    public $rd_fecha;
    public $rd_origen_concepto_id;
    public $rd_origen_detalle_id;
    public $rd_asiento_id;
    public $rd_monto;
    public $rd_concepto_id;
    public $rd_detalle_id;
    public $rd_medio_id;
    public $rd_identidad;
    public $rd_denominacion;

    public $rd_origen_conceptos = [];
    public $rd_origen_detalles = [];
    public $rd_detalles = [];
    public $rd_asientos = [];

    public $showPersonalPolicialModal = false;
    public $pp_fecha;
    public $pp_datos = [];

    public $showLibroDiarioReportModal = false;
    public $ld_fecha;
    public $ld_datos = [];
    public $ld_mediosEnTabla = [];
    public $ld_saldosAnterioresPorMedio = [];
    public $ld_saldosPeriodoPorMedio = [];
    public $ld_saldosActualesPorMedio = [];
    public $ld_fechaAnterior = null;

    protected $listeners = ['destroy' => 'destroy', 'confirmarEliminarAsientoConCfe', 'refreshComponent' => '$refresh'];

    public function boot(LibroDiarioService $service)
    {
        $this->service = $service;
    }

    public function mount()
    {
        $this->anio = now()->year;
        $this->fecha_desde = now()->startOfMonth()->format('Y-m-d');
        $this->fecha_hasta = now()->endOfMonth()->format('Y-m-d');
        $this->tipos = LbTipo::ordenado()->get();
        $this->medios = LbMedio::ordenado()->get();
        $this->conceptos = LbConcepto::ordenado()->get();
        $this->detallesFiltro = LbDetalle::with('concepto')
            ->join('tes_lb_conceptos', 'tes_lb_detalle.concepto_id', '=', 'tes_lb_conceptos.id')
            ->select('tes_lb_detalle.*')
            ->orderBy('tes_lb_conceptos.nombre')
            ->orderBy('tes_lb_detalle.nombre')
            ->get();
    }

    public function render()
    {
        $filtros = [
            'fecha_desde' => $this->fecha_desde,
            'fecha_hasta' => $this->fecha_hasta,
            'tipo_id' => $this->filtro_tipo_id ?: null,
            'concepto_id' => $this->filtro_concepto_id ?: null,
            'detalle_id' => $this->filtro_detalle_id ?: null,
            'search' => $this->search,
            'anio' => $this->anio,
        ];

        $items = $this->service->listar($filtros);
        $saldosActuales = $this->service->saldosActualesPorFlujo([
            'anio' => $this->anio,
        ]);
        $saldosPorMedio = $saldosActuales->groupBy(function ($item) {
            return $item->medio_id;
        })->map(function ($items) {
            return (object) [
                'medio_id' => $items->first()->medio_id,
                'medio_nombre' => $items->first()->medio->nombre ?? '—',
                'saldo_actual' => $items->sum('saldo_actual'),
            ];
        })->values();

        $mediosEnTabla = \App\Models\Tesoreria\LbMedio::where('id', '!=', 4)->ordenado()->get();

        $fechaAnterior = null;
        $saldosAnterioresPorMedio = collect();
        $saldosPeriodoPorMedio = collect();
        if ($this->fecha_desde) {
            $fechaAnterior = \Carbon\Carbon::parse($this->fecha_desde)->subDay()->format('d/m/Y');
            $saldosAnteriores = $this->service->saldosActualesPorFlujo([
                'anio' => $this->anio,
                'hasta' => \Carbon\Carbon::parse($this->fecha_desde)->subDay()->format('Y-m-d'),
            ]);
            $saldosAnterioresPorMedio = $saldosAnteriores->groupBy(function ($item) {
                return $item->medio_id;
            })->map(function ($items) {
                return (object) [
                    'medio_id' => $items->first()->medio_id,
                    'medio_nombre' => $items->first()->medio->nombre ?? '—',
                    'saldo_actual' => $items->sum('saldo_actual'),
                ];
            })->values();

            $hastaPeriodo = $this->fecha_hasta ?: \Carbon\Carbon::parse($this->fecha_desde)->endOfYear()->format('Y-m-d');
            $movimientosPeriodo = $this->service->saldosActualesPorFlujo([
                'desde' => $this->fecha_desde,
                'hasta' => $hastaPeriodo,
            ]);
            $saldosPeriodoPorMedio = $movimientosPeriodo->groupBy(function ($item) {
                return $item->medio_id;
            })->map(function ($items) use ($hastaPeriodo) {
                $primerMedio = $items->first();
                $entradasSalidas = \App\Models\Tesoreria\LibroDiario::where('medio_id', $primerMedio->medio_id)
                    ->whereDate('fecha', '>=', $this->fecha_desde)
                    ->whereDate('fecha', '<=', $hastaPeriodo)
                    ->selectRaw("SUM(CASE WHEN signo_efectivo = 1 THEN monto ELSE 0 END) as total_entradas, SUM(CASE WHEN signo_efectivo = -1 THEN monto ELSE 0 END) as total_salidas")
                    ->first();

                return (object) [
                    'medio_id' => $primerMedio->medio_id,
                    'medio_nombre' => $primerMedio->medio->nombre ?? '—',
                    'saldo_actual' => $items->sum('saldo_actual'),
                    'total_entradas' => (float) ($entradasSalidas->total_entradas ?? 0),
                    'total_salidas' => (float) ($entradasSalidas->total_salidas ?? 0),
                ];
            })->values();
        }

        $totales = [
            'entradas' => $items->sum(fn($i) => $i->signo_efectivo === 1 ? $i->monto : 0),
            'salidas' => $items->sum(fn($i) => $i->signo_efectivo === -1 ? $i->monto : 0),
            'saldo_actual' => $saldosActuales->sum('saldo_actual'),
        ];

        return view('livewire.tesoreria.libro-diario.index', [
            'items' => $items,
            'totales' => $totales,
            'saldosActuales' => $saldosActuales,
            'saldosPorMedio' => $saldosPorMedio,
            'fechaAnterior' => $fechaAnterior,
            'saldosAnterioresPorMedio' => $saldosAnterioresPorMedio,
            'saldosPeriodoPorMedio' => $saldosPeriodoPorMedio,
            'mediosEnTabla' => $mediosEnTabla,
            'detallesFiltro' => $this->detallesFiltro,
            'detallesAgrupados' => $this->detallesFiltro->groupBy(fn($d) => $d->concepto->nombre ?? '—')->sortKeys(),
        ])->extends('layouts.app')->section('content');
    }

    public function openCreateModal()
    {
        $this->resetCreateForm();
        $this->dispatchBrowserEvent('show-modal', ['id' => 'createModal']);
    }

    public function openRedistribucionModal()
    {
        $this->resetRedistribucionForm();
        $subcuentas = $this->service->saldosActualesPorFlujo()
            ->filter(fn (LibroDiario $asiento) => $asiento->saldo_actual > 0);
        $this->rd_origen_conceptos = LbConcepto::whereIn('id', $subcuentas->pluck('concepto_id')->unique())
            ->ordenado()->get();
        $this->dispatchBrowserEvent('show-modal', ['id' => 'redistribucionModal']);
    }

    public function updatedTipoId()
    {
        $this->concepto_id = null;
        $this->detalle_id = null;
        $this->detalles = [];
        $this->resetAsientoBase();
    }

    public function updatedConceptoId()
    {
        $this->detalle_id = null;
        $this->resetAsientoBase();
        $this->detalles = $this->concepto_id
            ? LbDetalle::where('concepto_id', $this->concepto_id)->activos()->ordenado()->get()
            : [];
    }

    public function updatedDetalleId()
    {
        $this->resetAsientoBase();

        $tipo = LbTipo::find($this->tipo_id);

        if (!$this->concepto_id || !$this->detalle_id || !$tipo) {
            return;
        }

        if ($tipo->signo === -1) {
            $this->asientos_base = $this->service->listarAsientosBaseDisponibles(
                (int) $this->concepto_id,
                (int) $this->detalle_id
            );
        } elseif ($tipo->signo === 1) {
            $this->asientos_base = $this->service->listarAsientosBaseDisponiblesEntradas(
                (int) $this->concepto_id,
                (int) $this->detalle_id
            );
        }
    }

    public function updatedAsientoBaseId()
    {
        if (!$this->asiento_base_id) {
            return;
        }

        $asiento = collect($this->asientos_base)->firstWhere('id', (int) $this->asiento_base_id);

        if (!$asiento) {
            $this->resetAsientoBase();
            return;
        }

        $this->medio_id = data_get($asiento, 'medio_id');
        $this->identidad = data_get($asiento, 'identidad');
        $this->denominacion = data_get($asiento, 'denominacion');

        $tipo = LbTipo::find($this->tipo_id);
        if ($tipo && $tipo->signo === 1) {
            $this->monto = (float) abs(data_get($asiento, 'saldo_actual'));
        } else {
            $this->monto = (float) data_get($asiento, 'saldo');
        }
    }

    public function updatedRdConceptoId()
    {
        $this->rd_detalle_id = null;
        $this->rd_detalles = $this->rd_concepto_id
            ? LbDetalle::where('concepto_id', $this->rd_concepto_id)->activos()->ordenado()->get()
            : [];
    }

    public function updatedRdOrigenConceptoId()
    {
        $this->rd_origen_detalle_id = null;
        $this->rd_asiento_id = null;
        $this->rd_monto = null;
        $this->rd_origen_detalles = [];
        $this->rd_asientos = [];

        if (!$this->rd_origen_concepto_id) return;

        $detalleIds = $this->service->saldosActualesPorFlujo([
            'concepto_id' => $this->rd_origen_concepto_id,
        ])->filter(fn (LibroDiario $asiento) => $asiento->saldo_actual > 0)
            ->pluck('detalle_id');

        $this->rd_origen_detalles = LbDetalle::whereIn('id', $detalleIds->unique())
            ->activos()->ordenado()->get();
    }

    public function updatedRdOrigenDetalleId()
    {
        $this->rd_asiento_id = null;
        $this->rd_monto = null;
        $this->rd_asientos = [];

        if (!$this->rd_origen_concepto_id || !$this->rd_origen_detalle_id) return;

        $this->rd_asientos = $this->service->listarAsientosBaseDisponibles(
            (int) $this->rd_origen_concepto_id,
            (int) $this->rd_origen_detalle_id
        );
    }

    public function updatedRdAsientoId()
    {
        if ($this->rd_asiento_id) {
            $asiento = LibroDiario::find($this->rd_asiento_id);
            if ($asiento) {
                $this->rd_monto = $this->service->saldoActualFlujo($asiento->medio_id, $asiento->concepto_id, $asiento->detalle_id);
                $this->rd_medio_id = $asiento->medio_id;
            } else {
                $this->rd_monto = null;
                $this->rd_medio_id = null;
            }
        } else {
            $this->rd_monto = null;
            $this->rd_medio_id = null;
        }
    }

    public function updatedIdentidad($value)
    {
        $this->identidad = mb_strtoupper($value);
    }

    public function updatedDenominacion($value)
    {
        $this->denominacion = mb_strtoupper($value);
    }

    public function store()
    {
        $this->identidad = mb_strtoupper($this->identidad ?? '');
        $this->denominacion = mb_strtoupper($this->denominacion ?? '');
        $this->descripcion = mb_strtoupper($this->descripcion ?? '');
        $this->validate([
            'fecha' => 'required|date',
            'tipo_id' => 'required|exists:tes_lb_tipos,id',
            'concepto_id' => 'required|exists:tes_lb_conceptos,id',
            'detalle_id' => 'required|exists:tes_lb_detalle,id',
            'medio_id' => 'required|exists:tes_lb_medios,id',
            'monto' => 'required|numeric|min:0.01',
            'identidad' => 'nullable|string|max:255',
            'denominacion' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'asiento_base_id' => 'nullable|exists:tes_libro_diario,id',
        ]);

        if ($this->asiento_base_id) {
            $tipo = LbTipo::findOrFail($this->tipo_id);

            if ($tipo->signo === -1) {
                $asientoBase = $this->service->listarAsientosBaseDisponibles(
                    (int) $this->concepto_id,
                    (int) $this->detalle_id
                )->firstWhere('id', (int) $this->asiento_base_id);

                if (!$asientoBase) {
                    $this->addError('asiento_base_id', 'El asiento base ya no tiene saldo disponible.');
                    return;
                }

                if ((float) $this->monto > (float) $asientoBase->saldo_actual) {
                    $this->addError('monto', 'El monto no puede superar el saldo disponible del asiento base.');
                    return;
                }
            } elseif ($tipo->signo === 1) {
                $asientoBase = $this->service->listarAsientosBaseDisponiblesEntradas(
                    (int) $this->concepto_id,
                    (int) $this->detalle_id
                )->firstWhere('id', (int) $this->asiento_base_id);

                if (!$asientoBase) {
                    $this->addError('asiento_base_id', 'El asiento base ya no tiene saldo negativo disponible.');
                    return;
                }

                if ((float) $this->monto > (float) abs($asientoBase->saldo_actual)) {
                    $this->addError('monto', 'El monto no puede superar el saldo pendiente del asiento base.');
                    return;
                }
            } else {
                $this->addError('asiento_base_id', 'Solo se puede usar un asiento base al registrar una entrada o salida.');
                return;
            }

            $this->medio_id = $asientoBase->medio_id;
        }

        $this->service->registrarAsiento([
            'fecha' => $this->fecha,
            'tipo_id' => $this->tipo_id,
            'concepto_id' => $this->concepto_id,
            'detalle_id' => $this->detalle_id,
            'medio_id' => $this->medio_id,
            'monto' => $this->monto,
            'identidad' => $this->identidad,
            'denominacion' => $this->denominacion,
            'descripcion' => $this->descripcion,
            'asociar' => $this->asiento_base_id,
        ]);

        $this->resetCreateForm();
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Asiento registrado con éxito!', 'toast' => true]);
        $this->dispatchBrowserEvent('close-modal', ['id' => 'createModal']);
    }

    public function storeRedistribucion()
    {
        $this->rd_identidad = mb_strtoupper($this->rd_identidad ?? '');
        $this->rd_denominacion = mb_strtoupper($this->rd_denominacion ?? '');
        $this->validate([
            'rd_fecha' => 'required|date',
            'rd_asiento_id' => 'required|exists:tes_libro_diario,id',
            'rd_monto' => 'required|numeric|min:0.01',
            'rd_concepto_id' => 'required|exists:tes_lb_conceptos,id',
            'rd_detalle_id' => 'required|exists:tes_lb_detalle,id',
            'rd_medio_id' => 'required|exists:tes_lb_medios,id',
            'rd_identidad' => 'nullable|string|max:255',
            'rd_denominacion' => 'nullable|string|max:255',
        ]);

        $asientoOrigen = LibroDiario::findOrFail($this->rd_asiento_id);
        $saldoOrigen = $this->service->saldoActualFlujo(
            $asientoOrigen->medio_id,
            $asientoOrigen->concepto_id,
            $asientoOrigen->detalle_id
        );

        if ((float) $this->rd_monto > $saldoOrigen) {
            $this->addError('rd_monto', 'El monto no puede superar el saldo actual del flujo de origen.');
            return;
        }

        $origen = [
            'fecha' => $this->rd_fecha,
            'concepto_id' => $asientoOrigen->concepto_id,
            'detalle_id' => $asientoOrigen->detalle_id,
            'medio_id' => $asientoOrigen->medio_id,
            'monto' => $this->rd_monto,
            'identidad' => $asientoOrigen->identidad,
            'denominacion' => $asientoOrigen->denominacion,
        ];

        $destino = [
            'fecha' => $this->rd_fecha,
            'concepto_id' => $this->rd_concepto_id,
            'detalle_id' => $this->rd_detalle_id,
            'medio_id' => $this->rd_medio_id,
            'monto' => $this->rd_monto,
            'identidad' => $this->rd_identidad,
            'denominacion' => $this->rd_denominacion,
        ];

        $this->service->registrarRedistribucion($origen, $destino);

        $this->resetRedistribucionForm();
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Redistribución registrada con éxito!', 'toast' => true]);
        $this->dispatchBrowserEvent('close-modal', ['id' => 'redistribucionModal']);
    }

    public function showDetails($id)
    {
        $this->selectedItem = LibroDiario::with(['tipo', 'concepto', 'detalle', 'medio', 'parent', 'children'])->findOrFail($id);
        $this->dispatchBrowserEvent('show-modal', ['id' => 'detailsModal']);
    }

    public function openEditModal($id)
    {
        $entry = LibroDiario::findOrFail($id);
        $this->edit_id = $id;
        $this->edit_identidad = $entry->identidad;
        $this->edit_denominacion = $entry->denominacion;
        $this->edit_descripcion = $entry->descripcion;
        $this->dispatchBrowserEvent('show-modal', ['id' => 'editModal']);
    }

    public function update()
    {
        $this->edit_identidad = mb_strtoupper($this->edit_identidad ?? '');
        $this->edit_denominacion = mb_strtoupper($this->edit_denominacion ?? '');
        $this->edit_descripcion = mb_strtoupper($this->edit_descripcion ?? '');
        $this->validate([
            'edit_identidad' => 'nullable|string|max:255',
            'edit_denominacion' => 'nullable|string|max:255',
            'edit_descripcion' => 'nullable|string|max:1000',
        ]);

        $this->service->actualizarCamposNoFinancieros($this->edit_id, [
            'identidad' => $this->edit_identidad,
            'denominacion' => $this->edit_denominacion,
            'descripcion' => $this->edit_descripcion,
        ]);

        $this->resetEditForm();
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Asiento actualizado con éxito!', 'toast' => true]);
        $this->dispatchBrowserEvent('close-modal', ['id' => 'editModal']);
    }

    public function destroy($id)
    {
        try {
            $entry = LibroDiario::with('cfe')->find($id);

            if (!$entry) {
                throw new \RuntimeException('Asiento no encontrado.');
            }

            if ($entry->cfe_id) {
                $cfe = TesCfe::withCount(['items as items_en_planilla_count' => fn($q) => $q->whereNotNull('planilla_er_id')])->find($entry->cfe_id);
                if ($cfe && $cfe->items_en_planilla_count > 0) {
                    throw new \RuntimeException('No se puede eliminar el asiento porque la recaudación asociada ya tiene ítems en Planilla para Estado de Recaudación.');
                }

                $this->dispatchBrowserEvent('swal:confirmar-eliminar-asiento-con-cfe', [
                    'asientoId' => $id,
                    'cfeSerie' => $cfe->documento_serie ? "{$cfe->documento_serie}-" : '',
                    'cfeNumero' => $cfe->documento_numero,
                    'cfeTipo' => $cfe->documento_tipo,
                ]);
            } else {
                $this->service->eliminarAsiento($id);
                $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Asiento eliminado y saldos recalculados.', 'toast' => true]);
            }
        } catch (\RuntimeException $e) {
            $this->dispatchBrowserEvent('alert', ['type' => 'error', 'message' => $e->getMessage(), 'toast' => true]);
        }
    }

    public function confirmarEliminarAsientoConCfe($id)
    {
        try {
            $this->service->eliminarAsientoConCfe($id);
            $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Asiento y CFE asociado eliminados. Saldos recalculados.', 'toast' => true]);
        } catch (\RuntimeException $e) {
            $this->dispatchBrowserEvent('alert', ['type' => 'error', 'message' => $e->getMessage(), 'toast' => true]);
        }
    }

    public function resetCreateForm()
    {
        $this->showCreateModal = false;
        $this->fecha = now()->format('Y-m-d');
        $this->tipo_id = null;
        $this->concepto_id = null;
        $this->detalle_id = null;
        $this->medio_id = null;
        $this->monto = null;
        $this->identidad = null;
        $this->denominacion = null;
        $this->detalles = [];
        $this->resetAsientoBase();
    }

    public function resetAsientoBase()
    {
        $this->asiento_base_id = null;
        $this->asientos_base = [];
    }

    public function resetRedistribucionForm()
    {
        $this->showRedistribucionModal = false;
        $this->rd_fecha = now()->format('Y-m-d');
        $this->rd_origen_concepto_id = null;
        $this->rd_origen_detalle_id = null;
        $this->rd_asiento_id = null;
        $this->rd_monto = null;
        $this->rd_concepto_id = null;
        $this->rd_detalle_id = null;
        $this->rd_medio_id = null;
        $this->rd_identidad = null;
        $this->rd_denominacion = null;
        $this->rd_origen_conceptos = [];
        $this->rd_origen_detalles = [];
        $this->rd_detalles = [];
        $this->rd_asientos = [];
    }

    public function resetEditForm()
    {
        $this->showEditModal = false;
        $this->edit_id = null;
        $this->edit_identidad = null;
        $this->edit_denominacion = null;
        $this->edit_descripcion = null;
    }

    public function updatedFiltroDetalleId($value)
    {
        if (str_starts_with((string) $value, 'concepto-')) {
            $this->filtro_concepto_id = (int) str_replace('concepto-', '', $value);
            $this->filtro_detalle_id = '';
        } else {
            $this->filtro_concepto_id = '';
        }
    }

    public function setHoy()
    {
        $this->fecha_desde = now()->format('Y-m-d');
        $this->fecha_hasta = now()->format('Y-m-d');
    }

    public function limpiarFiltros()
    {
        $this->search = '';
        $this->filtro_tipo_id = '';
        $this->filtro_concepto_id = '';
        $this->filtro_detalle_id = '';
        $this->fecha_desde = now()->startOfMonth()->format('Y-m-d');
        $this->fecha_hasta = now()->endOfMonth()->format('Y-m-d');
    }

    public function openPersonalPolicialReport()
    {
        $this->pp_fecha = now()->format('Y-m-d');
        $this->pp_loadReport();
        $this->dispatchBrowserEvent('show-modal', ['id' => 'personalPolicialModal']);
    }

    public function pp_loadReport()
    {
        $concepto = LbConcepto::where('nombre', 'Boletos en ventanilla')->first();
        if (!$concepto) {
            $this->pp_datos = [];
            return;
        }

        $entries = LibroDiario::with(['detalle', 'medio'])
            ->where('concepto_id', $concepto->id)
            ->where('signo_efectivo', -1)
            ->whereDate('fecha', $this->pp_fecha)
            ->orderBy('detalle_id')
            ->orderBy('numero')
            ->get();

        $this->pp_datos = $entries->groupBy('detalle_id')->map(function ($items) {
            $detalle = $items->first()->detalle;
            return [
                'detalle_nombre' => $detalle->nombre ?? '—',
                'cantidad' => $items->count(),
                'total' => $items->sum('monto'),
                'items' => $items->toArray(),
            ];
        })->values()->toArray();
    }

    public function updatedPpFecha()
    {
        $this->pp_loadReport();
    }

    public function resetPersonalPolicialModal()
    {
        $this->showPersonalPolicialModal = false;
        $this->pp_fecha = null;
        $this->pp_datos = [];
    }

    public function openLibroDiarioReport()
    {
        $this->ld_fecha = now()->format('Y-m-d');
        $this->ld_loadReport();
        $this->dispatchBrowserEvent('show-modal', ['id' => 'libroDiarioReportModal']);
    }

    public function ld_loadReport()
    {
        $anio = \Carbon\Carbon::parse($this->ld_fecha)->year;

        $this->ld_datos = $this->service->listar([
            'fecha_desde' => $this->ld_fecha,
            'fecha_hasta' => $this->ld_fecha,
            'anio' => $anio,
        ]);

        $this->ld_mediosEnTabla = \App\Models\Tesoreria\LbMedio::where('id', '!=', 4)->ordenado()->get();

        $fechaAnterior = \Carbon\Carbon::parse($this->ld_fecha)->subDay()->format('Y-m-d');
        $this->ld_fechaAnterior = \Carbon\Carbon::parse($this->ld_fecha)->subDay()->format('d/m/Y');

        $saldosAnteriores = $this->service->saldosActualesPorFlujo([
            'anio' => $anio,
            'hasta' => $fechaAnterior,
        ]);
        $this->ld_saldosAnterioresPorMedio = $saldosAnteriores->groupBy(function ($item) {
            return $item->medio_id;
        })->map(function ($items) {
            return (object) [
                'medio_id' => $items->first()->medio_id,
                'medio_nombre' => $items->first()->medio->nombre ?? '—',
                'saldo_actual' => $items->sum('saldo_actual'),
            ];
        })->values()->toArray();

        $movimientosDia = $this->service->saldosActualesPorFlujo([
            'desde' => $this->ld_fecha,
            'hasta' => $this->ld_fecha,
        ]);
        $this->ld_saldosPeriodoPorMedio = $movimientosDia->groupBy(function ($item) {
            return $item->medio_id;
        })->map(function ($items) {
            $primerMedio = $items->first();
            $entradasSalidas = \App\Models\Tesoreria\LibroDiario::where('medio_id', $primerMedio->medio_id)
                ->whereDate('fecha', $this->ld_fecha)
                ->selectRaw("SUM(CASE WHEN signo_efectivo = 1 THEN monto ELSE 0 END) as total_entradas, SUM(CASE WHEN signo_efectivo = -1 THEN monto ELSE 0 END) as total_salidas")
                ->first();
            return (object) [
                'medio_id' => $primerMedio->medio_id,
                'medio_nombre' => $primerMedio->medio->nombre ?? '—',
                'saldo_actual' => $items->sum('saldo_actual'),
                'total_entradas' => (float) ($entradasSalidas->total_entradas ?? 0),
                'total_salidas' => (float) ($entradasSalidas->total_salidas ?? 0),
            ];
        })->values()->toArray();

        $saldosActuales = $this->service->saldosActualesPorFlujo([
            'anio' => $anio,
        ]);
        $this->ld_saldosActualesPorMedio = $saldosActuales->groupBy(function ($item) {
            return $item->medio_id;
        })->map(function ($items) {
            return (object) [
                'medio_id' => $items->first()->medio_id,
                'medio_nombre' => $items->first()->medio->nombre ?? '—',
                'saldo_actual' => $items->sum('saldo_actual'),
            ];
        })->values()->toArray();
    }

    public function updatedLdFecha()
    {
        $this->ld_loadReport();
    }

    public function resetLibroDiarioReportModal()
    {
        $this->showLibroDiarioReportModal = false;
        $this->ld_fecha = null;
        $this->ld_datos = [];
    }
}
