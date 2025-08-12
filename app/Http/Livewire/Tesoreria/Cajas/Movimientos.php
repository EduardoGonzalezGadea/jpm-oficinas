<?php

namespace App\Http\Livewire\Tesoreria\Cajas;

use Livewire\Component;
use App\Models\Tesoreria\Cajas\Caja;
use App\Models\Tesoreria\Cajas\MovimientoCaja as Movimiento;
use App\Models\Tesoreria\Cajas\Concepto;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class Movimientos extends Component
{
    use WithPagination;

    public $caja_actual;
    public $tipo_movimiento = 'INGRESO', $concepto_id, $detalle, $monto, $forma_pago = 'EFECTIVO', $referencia;
    public $conceptos = [];
    public $modal = false;
    public $idMovimiento;

    protected $rules = [
        'tipo_movimiento' => 'required|in:INGRESO,EGRESO',
        'concepto_id' => 'required|exists:tes_caja_conceptos,idConcepto',
        'detalle' => 'nullable|string|max:255',
        'monto' => 'required|numeric|min:0.01',
        'forma_pago' => 'required|in:EFECTIVO,TRANSFERENCIA,CHEQUE',
        'referencia' => 'nullable|string|max:255',
    ];

    public function mount()
    {
        $this->caja_actual = Caja::where('estado', 'ABIERTA')->first();
        $this->updatedTipoMovimiento($this->tipo_movimiento);
    }

    public function updatedTipoMovimiento($value)
    {
        $this->conceptos = Concepto::where('tipo', $value)->where('activo', true)->get();
    }

    public function registrarMovimiento()
    {
        $this->validate();

        if (!$this->caja_actual) {
            session()->flash('error', 'No hay una caja abierta.');
            return;
        }

        $concepto = Concepto::find($this->concepto_id);

        Movimiento::updateOrCreate(['idMovimiento' => $this->idMovimiento], [
            'relCaja' => $this->caja_actual->idCaja,
            'fecha' => now()->format('Y-m-d'),
            'hora' => now()->format('H:i:s'),
            'tipo_movimiento' => $this->tipo_movimiento,
            'concepto' => $concepto->nombre,
            'detalle' => $this->detalle,
            'monto' => $this->monto,
            'forma_pago' => $this->forma_pago,
            'referencia' => $this->referencia,
            'usuario_registro' => Auth::id(),
        ]);

        session()->flash('message',
            $this->idMovimiento ? 'Movimiento actualizado correctamente.' : 'Movimiento registrado correctamente.');

        $this->closeModal();
        $this->resetInputFields();
    }

    private function resetInputFields()
    {
        $this->tipo_movimiento = 'INGRESO';
        $this->concepto_id = '';
        $this->detalle = '';
        $this->monto = '';
        $this->forma_pago = 'EFECTIVO';
        $this->referencia = '';
        $this->idMovimiento = null;
        $this->updatedTipoMovimiento('INGRESO');
    }

    public function edit($id)
    {
        $movimiento = Movimiento::findOrFail($id);
        $this->idMovimiento = $id;
        $this->tipo_movimiento = $movimiento->tipo_movimiento;
        $this->updatedTipoMovimiento($this->tipo_movimiento);

        $concepto = Concepto::where('nombre', $movimiento->concepto)->first();
        $this->concepto_id = $concepto ? $concepto->idConcepto : null;

        $this->detalle = $movimiento->detalle;
        $this->monto = $movimiento->monto;
        $this->forma_pago = $movimiento->forma_pago;
        $this->referencia = $movimiento->referencia;
        $this->openModal();
    }

    public function openModal()
    {
        $this->modal = true;
    }

    public function closeModal()
    {
        $this->modal = false;
        $this->resetInputFields();
    }

    public function render()
    {
        $movimientosAgrupados = [];
        if ($this->caja_actual) {
            $movimientos = Movimiento::where('relCaja', $this->caja_actual->idCaja)
                ->orderBy('tipo_movimiento')
                ->orderBy('concepto')
                ->orderBy('forma_pago')
                ->get();

            $movimientosAgrupados = $movimientos->groupBy('tipo_movimiento')->map(function ($movimientosPorTipo) {
                return $movimientosPorTipo->groupBy('concepto')->map(function ($movimientosPorConcepto) {
                    return $movimientosPorConcepto->groupBy('forma_pago')->map(function ($movimientosPorFormaPago) {
                        return [
                            'movimientos' => $movimientosPorFormaPago,
                            'total' => $movimientosPorFormaPago->sum('monto')
                        ];
                    });
                });
            });
        }

        return view('livewire.tesoreria.cajas.movimientos', [
            'movimientosAgrupados' => $movimientosAgrupados,
            'conceptos' => Concepto::where('tipo', $this->tipo_movimiento)->where('activo', true)->get()
        ]);
    }
}
