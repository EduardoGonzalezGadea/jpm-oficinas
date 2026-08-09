<?php

namespace App\Livewire\Tesoreria\CajaDiaria;

use App\Models\Tesoreria\Cajas\CajaApertura;
use App\Models\Tesoreria\Cajas\CajaMovimiento;
use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
use App\Models\Tesoreria\LbTipo;
use App\Models\Tesoreria\MedioDePago;
use App\Services\Tesoreria\LibroDiarioService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Movimientos extends Component
{
    public $caja_actual = null;

    // Filtros del listado
    public $filtroTipo = '';
    public $search = '';

    // Datos del nuevo movimiento
    public $tipo_id = null;
    public $concepto_id = null;
    public $detalle_id = null;
    public $medio_id = null;
    public $asiento_base_id = null;
    public $monto = null;
    public $identidad = '';
    public $denominacion = '';
    public $descripcion = '';
    public $documento_referencia = '';
    public $entrada_confirmada = false;

    // Listas reactivas
    public $detalles = [];
    public $asientos_base = [];
    public $tick = 0;

    protected LibroDiarioService $service;

    public function boot(LibroDiarioService $service)
    {
        $this->service = $service;
    }

    public function mount()
    {
        $this->caja_actual = CajaApertura::abiertas()
            ->porCajero(auth()->id())
            ->first();
    }

    protected function cajaOperable(): ?CajaApertura
    {
        if (!$this->caja_actual || empty($this->caja_actual->id)) {
            return null;
        }

        return CajaApertura::abiertas()
            ->porCajero(auth()->id())
            ->find($this->caja_actual->id);
    }

    public function updatedConceptoId($conceptoId)
    {
        $this->detalle_id = null;
        $this->detalles = [];
        $this->asientos_base = [];

        if (!$conceptoId) {
            return;
        }

        $this->detalles = LbDetalle::where('concepto_id', $conceptoId)
            ->orderBy('nombre')
            ->get();
    }

    public function updatedDetalleId()
    {
        $this->cargarAsientosBaseSiListo();
    }

    public function updatedTipoId()
    {
        $this->cargarAsientosBaseSiListo();
    }

    public function updatedMedioId()
    {
        $this->cargarAsientosBaseSiListo();
    }

    protected function cargarAsientosBaseSiListo()
    {
        if (!$this->tipo_id || !$this->concepto_id || !$this->detalle_id || !$this->medio_id) {
            $this->asientos_base = [];
            return;
        }

        $tipo = LbTipo::find($this->tipo_id);
        if (!$tipo || $tipo->signo !== -1) {
            $this->asientos_base = [];
            return;
        }

        $this->asientos_base = $this->service->listarAsientosBaseDisponibles(
            $this->concepto_id,
            $this->detalle_id,
            $this->medio_id
        )->toArray();
    }

    public function updatedAsientoBaseId()
    {
        if (!$this->asiento_base_id) {
            return;
        }

        if (!empty($this->asientos_base)) {
            $asiento = collect($this->asientos_base)->firstWhere('id', (int) $this->asiento_base_id);

            if (!$asiento) {
                $this->asiento_base_id = null;
                return;
            }

            $this->medio_id = data_get($asiento, 'medio_id');
            $this->identidad = data_get($asiento, 'identidad') ?? '';
            $this->denominacion = data_get($asiento, 'denominacion') ?? '';

            $tipo = LbTipo::find($this->tipo_id);
            if ($tipo && $tipo->signo === 1) {
                $this->monto = (float) abs(data_get($asiento, 'saldo_actual'));
            } else {
                $this->monto = (float) data_get($asiento, 'saldo');
            }
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

    public function registrarMovimiento()
    {
        $this->caja_actual = $this->cajaOperable();
        if (!$this->caja_actual) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'No tenés autorización para registrar movimientos en esta caja. Solo el usuario que creó la caja puede realizar movimientos en efectivo.']);
            return;
        }

        $this->identidad   = mb_strtoupper($this->identidad ?? '');
        $this->denominacion = mb_strtoupper($this->denominacion ?? '');
        $this->descripcion  = mb_strtoupper($this->descripcion ?? '');

        $this->validate([
            'tipo_id'     => 'required|exists:tes_lb_tipos,id',
            'concepto_id' => 'required|exists:tes_lb_conceptos,id',
            'detalle_id'  => 'required|exists:tes_lb_detalle,id',
            'medio_id'    => 'required|exists:tes_medio_de_pagos,id',
            'monto'       => 'required|numeric|min:0.01',
            'identidad'   => 'nullable|string|max:255',
            'denominacion'=> 'nullable|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'asiento_base_id' => 'nullable|exists:tes_libro_diario,id',
            'documento_referencia' => 'nullable|string|max:255',
        ]);

        $tipo = LbTipo::findOrFail($this->tipo_id);

        // Validar asiento base para salidas
        if ($this->asiento_base_id) {
            if ($tipo->signo === -1) {
                $asientoBase = collect($this->asientos_base)->firstWhere('id', (int) $this->asiento_base_id);

                if (!$asientoBase) {
                    $this->addError('asiento_base_id', 'El asiento base ya no tiene saldo disponible.');
                    return;
                }

                if ((float) $this->monto > (float) data_get($asientoBase, 'saldo_actual')) {
                    $this->addError('monto', 'El monto no puede superar el saldo disponible del asiento base.');
                    return;
                }

                $this->medio_id = data_get($asientoBase, 'medio_id');
            } else {
                $this->addError('asiento_base_id', 'Solo se puede usar un asiento base al registrar una salida.');
                return;
            }
        }

        DB::transaction(function () use ($tipo) {
            $data = [
                'fecha'        => $this->caja_actual->fecha_apertura->format('Y-m-d'),
                'tipo_id'      => $this->tipo_id,
                'concepto_id'  => $this->concepto_id,
                'detalle_id'   => $this->detalle_id,
                'medio_id'     => $this->medio_id,
                'monto'        => $this->monto,
                'identidad'    => $this->identidad,
                'denominacion' => $this->denominacion,
                'descripcion'  => $this->descripcion,
                'asociar'      => $this->asiento_base_id,
                'documento_referencia' => $this->documento_referencia ?: null,
                'confirmado'   => $tipo->signo === -1 ? true : $this->entrada_confirmada,
            ];

            $asiento = $tipo->signo === -1
                ? $this->service->registrarSalida($data)
                : $this->service->registrarAsiento($data);

            CajaMovimiento::create([
                'caja_apertura_id' => $this->caja_actual->id,
                'tipo_movimiento'  => $tipo->signo === 1 ? 'INGRESO' : 'EGRESO',
                'monto'            => $this->monto,
                'medio_pago_id'    => $this->medio_id,
                'libro_diario_id'  => $asiento->id,
                'concepto'         => LbConcepto::find($this->concepto_id)?->nombre . ' / ' . LbDetalle::find($this->detalle_id)?->nombre,
                'descripcion'      => $this->descripcion ?: null,
                'created_by'       => auth()->id(),
            ]);
        });

        $this->reset(['tipo_id', 'concepto_id', 'detalle_id', 'medio_id', 'monto', 'identidad', 'denominacion', 'descripcion', 'asiento_base_id', 'documento_referencia', 'entrada_confirmada', 'detalles', 'asientos_base']);
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Movimiento registrado y asentado en el Libro Diario.']);
    }

    public function confirmarIngreso(int $asientoId): void
    {
        $this->service->confirmarEntrada($asientoId);
        $this->tick++;
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Ingreso confirmado. Saldos recalculados.']);
    }

    public function render()
    {
        $tipos     = LbTipo::ordenado()->get();
        $conceptos = LbConcepto::activos()->ordenado()
            ->whereNotIn('nombre', [LbConcepto::CAJA_CHICA, LbConcepto::RECAUDACION_DIARIA, LbConcepto::RECAUDACION_222])
            ->get();
        $medios    = MedioDePago::libroDiario()->activos()->ordenado()->get();

        if (!$this->caja_actual) {
            return view('livewire.tesoreria.cajas.movimientos', [
                'caja_actual' => null,
                'movimientos' => collect(),
                'totalesPorMedio' => collect(),
            ])->extends('layouts.app')->section('content');
        }

        $query = CajaMovimiento::where('caja_apertura_id', $this->caja_actual->id)
            ->conLibroVigente()
            ->with(['medioPago', 'creador', 'libroDiario.concepto', 'libroDiario.detalle', 'libroDiario.tipo']);

        if ($this->filtroTipo) {
            $query->where('tipo_movimiento', $this->filtroTipo);
        }

        if ($this->search) {
            $query->where('concepto', 'like', '%' . $this->search . '%');
        }

        $movimientos = $query->orderByDesc('created_at')->get();

        return view('livewire.tesoreria.cajas.movimientos', [
            'caja_actual'     => $this->caja_actual,
            'movimientos'     => $movimientos,
            'totalesPorMedio' => $this->caja_actual->totalesPorMedio(),
            'tipos'           => $tipos,
            'conceptos'       => $conceptos,
            'medios'          => $medios,
            'detalles'        => $this->detalles,
            'asientos_base'   => $this->asientos_base,
        ])->extends('layouts.app')->section('content');
    }
}
