<?php

namespace App\Livewire\Tesoreria\LibroDiario;

use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
use App\Models\Tesoreria\LbTipo;
use App\Models\Tesoreria\MedioDePago;
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
    public $filtro_monto = '';
    public $anio;

    public $selectedItem = null;
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showRedistribucionModal = false;
    public $showDetailsModal = false;

    public $fecha;
    public $tipo_id;
    public $tipoEsEntrada = false;
    public $concepto_id;
    public $detalle_id;
    public $medio_id;
    public $monto;
    public $identidad;
    public $denominacion;
    public $descripcion;
    public $asiento_base_id;
    public $documento_referencia = '';
    public $confirmado = true;

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
    public $tick = 0;

    // False = asientos con medio de pago Efectivo (Libro Diario).
    // True  = todos los medios de pago (Libro Diario Ampliado).
    public $ld_ampliado = false;

    public $showConfirmModal = false;
    public $confirmAsientoId = null;
    public $confirmFecha = '';
    public $confirmLoteDocRef = null;
    public $seleccionadosConfirmar = [];

    protected $listeners = ['destroy' => 'destroy', 'confirmarEliminarAsientoConCfe'];

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
        $this->medios = MedioDePago::libroDiario()->activos()->ordenado()->get();
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
            'monto' => $this->filtro_monto !== '' ? (float) $this->filtro_monto : null,
        ];

        $items = $this->service->listar($filtros);

        $itemsConfirmados = $items->filter(fn($i) => !is_null($i->fecha_confirmacion))
            ->sortByDesc(fn($i) => $i->fecha_efectiva)
            ->values();
        $itemsPendientes = $items->filter(fn($i) => is_null($i->fecha_confirmacion))
            ->sortByDesc('fecha')
            ->sortByDesc('id')
            ->values();

        $saldosActuales = $this->service->saldosActualesPorFlujo(array_filter([
            'anio' => $this->anio,
            'hasta' => $this->fecha_hasta ?: null,
        ]));
        $saldosPorMedio = $saldosActuales->groupBy(function ($item) {
            return $item->medio_id;
        })->map(function ($items) {
            return (object) [
                'medio_id' => $items->first()->medio_id,
                'medio_nombre' => $items->first()->medio->nombre ?? '—',
                'saldo_actual' => $items->sum('saldo_actual'),
            ];
        })->values();

        $mediosEnTabla = \App\Models\Tesoreria\MedioDePago::where('es_libro_diario', true)->where('activo', true)->where('nombre', 'not like', '%Tarjeta%')->ordenado()->get();

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
                    ->whereRaw("COALESCE(fecha_confirmacion, fecha) >= ?", [$this->fecha_desde . ' 00:00:00'])
                    ->whereRaw("COALESCE(fecha_confirmacion, fecha) <= ?", [$hastaPeriodo . ' 23:59:59'])
                    ->selectRaw("SUM(CASE WHEN signo_efectivo = 1 AND confirmado = 1 THEN monto ELSE 0 END) as total_entradas, SUM(CASE WHEN signo_efectivo = -1 THEN monto ELSE 0 END) as total_salidas")
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
            'entradas' => $items->sum(fn($i) => $i->signo_efectivo === 1 && $i->confirmado ? $i->monto : 0),
            'salidas' => $items->sum(fn($i) => $i->signo_efectivo === -1 ? $i->monto : 0),
            'saldo_actual' => $saldosActuales->sum('saldo_actual'),
        ];

        $totalesConfirmados = [
            'entradas' => $itemsConfirmados->sum(fn($i) => $i->signo_efectivo === 1 ? $i->monto : 0),
            'salidas' => $itemsConfirmados->sum(fn($i) => $i->signo_efectivo === -1 ? $i->monto : 0),
        ];
        $totalesPendientes = [
            'entradas' => $itemsPendientes->sum(fn($i) => $i->signo_efectivo === 1 ? $i->monto : 0),
            'salidas' => $itemsPendientes->sum(fn($i) => $i->signo_efectivo === -1 ? $i->monto : 0),
        ];

        return view('livewire.tesoreria.libro-diario.index', [
            'items' => $items,
            'itemsConfirmados' => $itemsConfirmados,
            'itemsPendientes' => $itemsPendientes,
            'totales' => $totales,
            'totalesConfirmados' => $totalesConfirmados,
            'totalesPendientes' => $totalesPendientes,
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
        $this->dispatch('show-modal', ['id' => 'createModal']);
    }

    public function openRedistribucionModal()
    {
        $this->resetRedistribucionForm();
        $subcuentas = $this->service->saldosActualesPorFlujo()
            ->filter(fn (LibroDiario $asiento) => $asiento->saldo_actual > 0);
        $this->rd_origen_conceptos = LbConcepto::whereIn('id', $subcuentas->pluck('concepto_id')->unique())
            ->ordenado()->get();
        $this->dispatch('show-modal', ['id' => 'redistribucionModal']);
    }

    public function updatedTipoId()
    {
        $this->concepto_id = null;
        $this->detalle_id = null;
        $this->detalles = [];
        $this->resetAsientoBase();
        $this->actualizarTipoEsEntrada();
    }

    public function actualizarTipoEsEntrada()
    {
        $tipo = LbTipo::find($this->tipo_id);
        $this->tipoEsEntrada = $tipo && $tipo->signo === 1;
        if (!$this->tipoEsEntrada) {
            $this->documento_referencia = '';
        }
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
                $this->rd_monto = $this->service->saldoActualFlujo(
                    $asiento->medio_id,
                    $asiento->concepto_id,
                    $asiento->detalle_id,
                    $asiento->identidad ?? ''
                );
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
            'medio_id' => 'required|exists:tes_medio_de_pagos,id',
            'monto' => 'required|numeric|min:0.01',
            'identidad' => 'nullable|string|max:255',
            'denominacion' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'asiento_base_id' => 'nullable|exists:tes_libro_diario,id',
            'documento_referencia' => 'nullable|string|max:255',
            'confirmado' => 'boolean',
        ]);

        $tipo = LbTipo::findOrFail($this->tipo_id);

        if ($this->asiento_base_id) {
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

        $data = [
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
            'documento_referencia' => $this->documento_referencia ?: null,
            'confirmado' => $this->confirmado,
        ];

        if ($tipo->signo === -1) {
            $this->service->registrarSalida($data);
        } else {
            $this->service->registrarAsiento($data);
        }

        $this->resetCreateForm();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Asiento registrado con éxito!', 'toast' => true]);
        $this->dispatch('close-modal', ['id' => 'createModal']);
    }

    public function confirmarPorDocumento(string $docRef): void
    {
        $this->confirmLoteDocRef = $docRef;
        $this->confirmAsientoId = null;
        $this->confirmFecha = now()->format('Y-m-d');
        $this->showConfirmModal = true;
        $this->dispatch('openConfirmModal');
    }

    public function desconfirmarPorDocumento(string $docRef): void
    {
        try {
            $cantidad = $this->service->desconfirmarPorDocumento($docRef);
            $this->tick++;
            $this->dispatch('alert', [
                'type' => 'warning',
                'message' => "{$cantidad} asiento(s) desconfirmado(s) para documento: {$docRef}. Saldos recalculados."
            ]);
        } catch (\DomainException $e) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function toggleConfirmacion(int $asientoId): void
    {
        $asiento = LibroDiario::findOrFail($asientoId);

        if (!is_null($asiento->fecha_confirmacion)) {
            try {
                $this->service->toggleConfirmacion($asientoId);
                $this->tick++;
                $this->dispatch('alert', [
                    'type' => 'warning',
                    'message' => 'Asiento desconfirmado. Saldos recalculados.'
                ]);
            } catch (\DomainException $e) {
                $this->dispatch('alert', [
                    'type' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
        } else {
            $this->confirmAsientoId = $asientoId;
            $this->confirmLoteDocRef = null;
            $this->confirmFecha = now()->format('Y-m-d');
            $this->showConfirmModal = true;
            $this->dispatch('openConfirmModal');
        }
    }

    public function confirmarConFecha(): void
    {
        if ($this->confirmLoteDocRef) {
            $this->confirmarLoteConFecha();
            return;
        }

        $fecha = \Carbon\Carbon::parse($this->confirmFecha);
        $asiento = LibroDiario::findOrFail($this->confirmAsientoId);

        if ($asiento->documento_referencia) {
            $cantidadOtrosPendientes = LibroDiario::pendientes()
                ->where('documento_referencia', $asiento->documento_referencia)
                ->where('id', '!=', $asiento->id)
                ->count();

            if ($cantidadOtrosPendientes > 0) {
                $this->dispatch('swal:confirmar-documento', [
                    'asientoId' => $asiento->id,
                    'documentoReferencia' => $asiento->documento_referencia,
                    'cantidad' => $cantidadOtrosPendientes,
                ]);
                return;
            }
        }

        $this->confirmarSoloEste();
    }

    public function confirmarLoteConFecha(): void
    {
        $docRef = $this->confirmLoteDocRef;
        $fecha = \Carbon\Carbon::parse($this->confirmFecha);
        $cantidad = $this->service->confirmarPorDocumento($docRef, $fecha);
        $this->tick++;
        $this->showConfirmModal = false;
        $this->confirmAsientoId = null;
        $this->confirmFecha = '';
        $this->confirmLoteDocRef = null;
        $this->dispatch('close-modal', ['id' => 'confirmModal']);
        $this->dispatch('alert', [
            'type' => 'success',
            'message' => "{$cantidad} asiento(s) con documento {$docRef} confirmado(s) el {$fecha->format('d/m/Y H:i')}. Saldos recalculados."
        ]);
    }

    public function confirmarSoloEste(): void
    {
        $fecha = \Carbon\Carbon::parse($this->confirmFecha);
        $nuevoEstado = $this->service->toggleConfirmacion($this->confirmAsientoId, $fecha);

        // Confirmar de la misma forma el resto de los asientos seleccionados.
        $loteSeleccionado = collect($this->seleccionadosConfirmar)
            ->map(fn($id) => (int) $id)
            ->reject(fn($id) => $id === (int) $this->confirmAsientoId)
            ->unique()
            ->values();

        foreach ($loteSeleccionado as $id) {
            try {
                $this->service->toggleConfirmacion($id, $fecha);
            } catch (\DomainException $e) {
                // Se ignora para no interrumpir el lote.
            }
        }

        $total = 1 + $loteSeleccionado->count();

        $this->tick++;
        $this->showConfirmModal = false;
        $this->confirmAsientoId = null;
        $this->confirmFecha = '';
        $this->confirmLoteDocRef = null;
        $this->seleccionadosConfirmar = [];
        $this->dispatch('close-modal', ['id' => 'confirmModal']);
        $this->dispatch('alert', [
            'type' => 'success',
            'message' => "{$total} asiento(s) confirmado(s) el {$fecha->format('d/m/Y H:i')}. Saldos recalculados."
        ]);
    }

    public function confirmarTodosDocumento(): void
    {
        $asiento = LibroDiario::findOrFail($this->confirmAsientoId);
        $fecha = \Carbon\Carbon::parse($this->confirmFecha);
        $cantidad = $this->service->confirmarPorDocumento($asiento->documento_referencia, $fecha);
        $this->tick++;
        $this->showConfirmModal = false;
        $this->confirmAsientoId = null;
        $this->confirmFecha = '';
        $this->confirmLoteDocRef = null;
        $this->dispatch('close-modal', ['id' => 'confirmModal']);
        $this->dispatch('alert', [
            'type' => 'success',
            'message' => "{$cantidad} asiento(s) con documento {$asiento->documento_referencia} confirmado(s) el {$fecha->format('d/m/Y H:i')}. Saldos recalculados."
        ]);
    }

    public function resetConfirmModal(): void
    {
        $this->showConfirmModal = false;
        $this->confirmAsientoId = null;
        $this->confirmFecha = '';
        $this->confirmLoteDocRef = null;
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
            'rd_medio_id' => 'required|exists:tes_medio_de_pagos,id',
            'rd_identidad' => 'nullable|string|max:255',
            'rd_denominacion' => 'nullable|string|max:255',
        ]);

        $asientoOrigen = LibroDiario::findOrFail($this->rd_asiento_id);
        $saldoOrigen = $this->service->saldoActualFlujo(
            $asientoOrigen->medio_id,
            $asientoOrigen->concepto_id,
            $asientoOrigen->detalle_id,
            $asientoOrigen->identidad ?? ''
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
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Redistribución registrada con éxito!', 'toast' => true]);
        $this->dispatch('close-modal', ['id' => 'redistribucionModal']);
    }

    public function showDetails($id)
    {
        $this->selectedItem = LibroDiario::with(['tipo', 'concepto', 'detalle', 'medio', 'parent', 'children'])->findOrFail($id);
        $this->dispatch('show-modal', ['id' => 'detailsModal']);
    }

    public function openEditModal($id)
    {
        $entry = LibroDiario::findOrFail($id);
        $this->edit_id = $id;
        $this->edit_identidad = $entry->identidad;
        $this->edit_denominacion = $entry->denominacion;
        $this->edit_descripcion = $entry->descripcion;
        $this->dispatch('show-modal', ['id' => 'editModal']);
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
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Asiento actualizado con éxito!', 'toast' => true]);
        $this->dispatch('close-modal', ['id' => 'editModal']);
    }

    public function destroy($id)
    {
        try {
            $entry = LibroDiario::with('cfe')->find($id);

            if (!$entry) {
                throw new \RuntimeException('Asiento no encontrado.');
            }

            if ($entry->cch_origen_type) {
                throw new \RuntimeException('No se puede eliminar un asiento generado por Caja Chica desde el Libro Diario. Use el módulo de Caja Chica.');
            }

            if ($entry->cfe_id) {
                $cfe = TesCfe::withCount(['items as items_en_planilla_count' => fn($q) => $q->whereNotNull('planilla_er_id')])->find($entry->cfe_id);
                if ($cfe && $cfe->items_en_planilla_count > 0) {
                    throw new \RuntimeException('No se puede eliminar el asiento porque la recaudación asociada ya tiene ítems en Planilla para Estado de Recaudación.');
                }

                $this->dispatch('swal:confirmar-eliminar-asiento-con-cfe', [
                    'asientoId' => $id,
                    'cfeSerie' => $cfe->documento_serie ? "{$cfe->documento_serie}-" : '',
                    'cfeNumero' => $cfe->documento_numero,
                    'cfeTipo' => $cfe->documento_tipo,
                ]);
            } else {
                $this->service->eliminarAsiento($id);
                $this->dispatch('alert', ['type' => 'success', 'message' => 'Asiento eliminado y saldos recalculados.', 'toast' => true]);
            }
        } catch (\RuntimeException $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => $e->getMessage(), 'toast' => true]);
        }
    }

    public function confirmarEliminarAsientoConCfe($id)
    {
        try {
            $this->service->eliminarAsientoConCfe($id);
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Asiento y CFE asociado eliminados. Saldos recalculados.', 'toast' => true]);
        } catch (\RuntimeException $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => $e->getMessage(), 'toast' => true]);
        }
    }

    public function resetCreateForm()
    {
        $this->showCreateModal = false;
        $this->fecha = now()->format('Y-m-d');
        $this->tipo_id = null;
        $this->tipoEsEntrada = false;
        $this->concepto_id = null;
        $this->detalle_id = null;
        $this->medio_id = null;
        $this->monto = null;
        $this->identidad = null;
        $this->denominacion = null;
        $this->documento_referencia = '';
        $this->confirmado = true;
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
        $this->filtro_monto = '';
        $this->fecha_desde = now()->startOfMonth()->format('Y-m-d');
        $this->fecha_hasta = now()->endOfMonth()->format('Y-m-d');
    }

    public function openPersonalPolicialReport()
    {
        $this->pp_fecha = now()->format('Y-m-d');
        $this->pp_loadReport();
        $this->dispatch('show-modal', ['id' => 'personalPolicialModal']);
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
            ->whereRaw("DATE(COALESCE(fecha_confirmacion, fecha)) = ?", [$this->pp_fecha])
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
        $this->ld_ampliado = false;
        $this->ld_fecha = now()->format('Y-m-d');
        $this->ld_loadReport();
        $this->dispatch('show-modal', ['id' => 'libroDiarioReportModal']);
    }

    public function openLibroDiarioAmpliadoReport()
    {
        $this->ld_ampliado = true;
        $this->ld_fecha = now()->format('Y-m-d');
        $this->ld_loadReport();
        $this->dispatch('show-modal', ['id' => 'libroDiarioReportModal']);
    }

    public function ld_loadReport()
    {
        $anio = \Carbon\Carbon::parse($this->ld_fecha)->year;

        // En modo acotado (Libro Diario) todo se acota al medio Efectivo.
        $efectivoId = MedioDePago::efectivo()->id;
        $filtroMedioId = $this->ld_ampliado ? null : $efectivoId;

        $query = LibroDiario::with(['tipo', 'concepto', 'detalle', 'medio'])
            ->whereNotNull('fecha_confirmacion')
            ->whereDate('fecha_confirmacion', $this->ld_fecha)
            ->whereRaw('YEAR(COALESCE(fecha_confirmacion, fecha)) = ?', [$anio]);

        if ($filtroMedioId !== null) {
            $query->where('medio_id', $filtroMedioId);
        }

        $this->ld_datos = $query
            ->orderByDesc('fecha_confirmacion')
            ->orderByDesc('id')
            ->get();

        if ($this->ld_ampliado) {
            $this->ld_mediosEnTabla = \App\Models\Tesoreria\MedioDePago::where('es_libro_diario', true)->where('activo', true)->where('nombre', 'not like', '%Tarjeta%')->ordenado()->get();
        } else {
            $this->ld_mediosEnTabla = collect([MedioDePago::find($efectivoId)])->filter();
        }

        $fechaAnterior = \Carbon\Carbon::parse($this->ld_fecha)->subDay()->format('Y-m-d');
        $this->ld_fechaAnterior = \Carbon\Carbon::parse($this->ld_fecha)->subDay()->format('d/m/Y');

        $saldosAnteriores = $this->service->saldosActualesPorFlujo([
            'anio' => $anio,
            'hasta' => $fechaAnterior,
        ]);
        $this->ld_saldosAnterioresPorMedio = $saldosAnteriores
            ->when($filtroMedioId !== null, fn($c) => $c->where('medio_id', $filtroMedioId))
            ->groupBy(function ($item) {
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
        $this->ld_saldosPeriodoPorMedio = $movimientosDia
            ->when($filtroMedioId !== null, fn($c) => $c->where('medio_id', $filtroMedioId))
            ->groupBy(function ($item) {
                return $item->medio_id;
            })->map(function ($items) use ($filtroMedioId) {
                $primerMedio = $items->first();
                $entradasSalidas = \App\Models\Tesoreria\LibroDiario::where('medio_id', $primerMedio->medio_id)
                    ->when($filtroMedioId !== null, fn($q) => $q->where('medio_id', $filtroMedioId))
                    ->whereRaw("DATE(COALESCE(fecha_confirmacion, fecha)) = ?", [$this->ld_fecha])
                    ->selectRaw("SUM(CASE WHEN signo_efectivo = 1 AND confirmado = 1 THEN monto ELSE 0 END) as total_entradas, SUM(CASE WHEN signo_efectivo = -1 THEN monto ELSE 0 END) as total_salidas")
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
            'hasta' => $this->ld_fecha,
        ]);
        $this->ld_saldosActualesPorMedio = $saldosActuales
            ->when($filtroMedioId !== null, fn($c) => $c->where('medio_id', $filtroMedioId))
            ->groupBy(function ($item) {
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
        $this->ld_ampliado = false;
    }
}
