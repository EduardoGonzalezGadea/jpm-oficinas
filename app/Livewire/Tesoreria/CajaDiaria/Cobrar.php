<?php

namespace App\Livewire\Tesoreria\CajaDiaria;

use App\Models\Tesoreria\Cajas\CajaApertura;
use App\Models\Tesoreria\Cajas\CajaMovimiento;
use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
use App\Models\Tesoreria\LbTipo;
use App\Models\Tesoreria\LibroDiario;
use App\Models\Tesoreria\MedioDePago;
use App\Services\Tesoreria\LibroDiarioService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Cobrar extends Component
{
    public $caja_actual = null;

    // Filtros del listado
    public $search = '';
    public $concepto_id = null;
    public $detalle_id = null;
    public $medio_id = null;

    // Datos del cobro
    public $seleccion_id = null;
    public $monto = null;
    public $identidad = '';
    public $denominacion = '';
    public $descripcion = '';
    public $documento_referencia = '';
    public $entrada_confirmada = true;

    // Listas reactivas
    public $detalles = [];

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

        // El medio de pago primario es Efectivo
        $this->medio_id = MedioDePago::efectivo()->id;
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
        $this->limpiarSeleccion();

        if (!$conceptoId) {
            return;
        }

        $this->detalles = LbDetalle::where('concepto_id', $conceptoId)
            ->orderBy('nombre')
            ->get();
    }

    public function updatedDetalleId()
    {
        $this->limpiarSeleccion();
    }

    public function updatedMedioId()
    {
        $this->limpiarSeleccion();
    }

    public function updatedSearch()
    {
        $this->limpiarSeleccion();
    }

    public function updatedSeleccionId($seleccionId)
    {
        $item = $this->obtenerItems()->firstWhere('id', (int) $seleccionId);

        if (!$item) {
            $this->limpiarSeleccion();
            return;
        }

        // Se precarga el saldo pendiente de cobro del ítem, permitiendo un cobro parcial.
        // La identidad se precarga con la del deudor, pero puede editarse para
        // detallar quién está realizando el pago.
        $this->monto = (float) abs($item->saldo_actual);
        $this->identidad = data_get($item, 'identidad') ?? '';
        $this->denominacion = data_get($item, 'denominacion') ?? '';
    }

    public function updatedIdentidad($value)
    {
        $this->identidad = mb_strtoupper($value);
    }

    public function updatedDenominacion($value)
    {
        $this->denominacion = mb_strtoupper($value);
    }

    protected function limpiarSeleccion()
    {
        $this->seleccion_id = null;
        $this->monto = null;
        $this->identidad = '';
        $this->denominacion = '';
    }

    protected function obtenerItems()
    {
        $filtros = array_filter([
            'concepto_id' => $this->concepto_id,
            'detalle_id' => $this->detalle_id,
            'medio_id' => $this->medio_id,
        ]);

        $excluir = LbConcepto::activos()->whereIn('nombre', [
            LbConcepto::CAJA_CHICA,
            LbConcepto::RECAUDACION_DIARIA,
            LbConcepto::RECAUDACION_222,
        ])->pluck('id');

        // Se usan saldos sin truncar (permitirNegativos) para detectar los
        // flujos con saldo negativo (deudores / pendientes de cobro).
        return $this->service->saldosActualesPorFlujo($filtros, true)
            ->filter(fn (LibroDiario $asiento) => $asiento->saldo_actual < 0)
            ->filter(fn (LibroDiario $asiento) => !$excluir->contains($asiento->concepto_id))
            ->when(!empty($this->search), function ($collection) {
                $term = mb_strtolower(trim($this->search));
                return $collection->filter(fn (LibroDiario $asiento) =>
                    str_contains(mb_strtolower((string) $asiento->identidad), $term)
                    || str_contains(mb_strtolower((string) $asiento->denominacion), $term)
                    || str_contains(mb_strtolower((string) $asiento->descripcion), $term)
                    || str_contains(mb_strtolower((string) $asiento->numero), $term)
                );
            })
            ->sortBy('saldo_actual')
            ->values();
    }

    public function cobrar()
    {
        $this->caja_actual = $this->cajaOperable();
        if (!$this->caja_actual) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'No tenés autorización para realizar cobros en esta caja. Solo el usuario que creó la caja puede realizar movimientos en efectivo.']);
            return;
        }

        $this->identidad = mb_strtoupper($this->identidad ?? '');
        $this->denominacion = mb_strtoupper($this->denominacion ?? '');
        $this->descripcion = mb_strtoupper($this->descripcion ?? '');

        $this->validate([
            'seleccion_id' => 'required',
            'monto' => 'required|numeric|min:0.01',
            'identidad' => 'nullable|string|max:255',
            'denominacion' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'documento_referencia' => 'nullable|string|max:255',
        ]);

        $item = $this->obtenerItems()->firstWhere('id', (int) $this->seleccion_id);

        if (!$item) {
            $this->addError('seleccion_id', 'El ítem seleccionado ya no tiene saldo pendiente de cobro.');
            return;
        }

        if ((float) $this->monto > (float) abs($item->saldo_actual)) {
            $this->addError('monto', 'El monto no puede superar el saldo pendiente de cobro del ítem.');
            return;
        }

        $tipoEntrada = LbTipo::where('nombre', 'Entrada')->firstOrFail();

        DB::transaction(function () use ($item, $tipoEntrada) {
            $data = [
                'fecha' => $this->caja_actual->fecha_apertura->format('Y-m-d'),
                'tipo_id' => $tipoEntrada->id,
                'concepto_id' => $item->concepto_id,
                'detalle_id' => $item->detalle_id,
                'medio_id' => $item->medio_id,
                'monto' => $this->monto,
                'identidad' => $this->identidad ?: null,
                'denominacion' => $this->denominacion ?: null,
                'descripcion' => $this->descripcion,
                'documento_referencia' => $this->documento_referencia ?: null,
                'confirmado' => $this->entrada_confirmada,
            ];

            $asiento = $this->service->registrarAsiento($data);

            CajaMovimiento::create([
                'caja_apertura_id' => $this->caja_actual->id,
                'tipo_movimiento' => 'INGRESO',
                'monto' => $this->monto,
                'medio_pago_id' => $item->medio_id,
                'libro_diario_id' => $asiento->id,
                'concepto' => $item->concepto->nombre . ' / ' . $item->detalle->nombre,
                'descripcion' => $this->descripcion ?: null,
                'created_by' => auth()->id(),
            ]);
        });

        $this->limpiarSeleccion();
        $this->descripcion = '';
        $this->documento_referencia = '';
        $this->entrada_confirmada = true;
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Cobro registrado y asentado en el Libro Diario.']);
    }

    public function render()
    {
        if (!$this->caja_actual) {
            return view('livewire.tesoreria.cajas.cobrar', [
                'caja_actual' => null,
                'items' => collect(),
                'conceptos' => collect(),
                'detalles' => [],
                'medios' => collect(),
            ]);
        }

        $conceptos = LbConcepto::activos()->ordenado()
            ->whereNotIn('nombre', [LbConcepto::CAJA_CHICA, LbConcepto::RECAUDACION_DIARIA, LbConcepto::RECAUDACION_222])
            ->get();

        $medios = MedioDePago::libroDiario()->activos()->ordenado()->get();

        return view('livewire.tesoreria.cajas.cobrar', [
            'caja_actual' => $this->caja_actual,
            'items' => $this->obtenerItems(),
            'conceptos' => $conceptos,
            'detalles' => $this->detalles,
            'medios' => $medios,
        ]);
    }
}
