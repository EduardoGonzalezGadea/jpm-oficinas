<?php

namespace App\Http\Livewire\Tesoreria\Cajas;

use Livewire\Component;
use App\Models\Tesoreria\Cajas\Caja;
use App\Models\Tesoreria\Cajas\MovimientoCaja;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class Movimientos extends Component
{
    use WithPagination;

    public $tipo_movimiento = 'INGRESO';
    public $concepto;
    public $monto;
    public $forma_pago = 'EFECTIVO';
    public $referencia;
    public $caja_actual;

    protected $rules = [
        'tipo_movimiento' => 'required|in:INGRESO,EGRESO',
        'concepto' => 'required|string|min:3',
        'monto' => 'required|numeric|min:0.01',
        'forma_pago' => 'required|in:EFECTIVO,TRANSFERENCIA,CHEQUE',
        'referencia' => 'required_unless:forma_pago,EFECTIVO'
    ];

    public function mount()
    {
        $this->caja_actual = Caja::where('estado', 'ABIERTA')->first();
    }

    public function registrarMovimiento()
    {
        $this->validate();

        if (!$this->caja_actual) {
            session()->flash('error', 'No hay una caja abierta para registrar movimientos');
            return;
        }

        MovimientoCaja::create([
            'relCaja' => $this->caja_actual->idCaja,
            'fecha' => now()->format('Y-m-d'),
            'hora' => now()->format('H:i:s'),
            'tipo_movimiento' => $this->tipo_movimiento,
            'concepto' => $this->concepto,
            'monto' => $this->monto,
            'forma_pago' => $this->forma_pago,
            'referencia' => $this->referencia,
            'usuario_registro' => Auth::id()
        ]);

        $this->reset(['concepto', 'monto', 'referencia']);
        session()->flash('message', 'Movimiento registrado correctamente');
        $this->emit('movimientoRegistrado');
    }

    public function render()
    {
        $movimientos = MovimientoCaja::where('relCaja', $this->caja_actual?->idCaja)
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc')
            ->paginate(10);

        return view('livewire.tesoreria.cajas.movimientos', [
            'movimientos' => $movimientos
        ]);
    }
}
