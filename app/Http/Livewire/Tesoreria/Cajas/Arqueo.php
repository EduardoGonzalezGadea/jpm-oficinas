<?php

namespace App\Http\Livewire\Tesoreria\Cajas;

use Livewire\Component;
use App\Models\Tesoreria\Cajas\Caja;
use App\Models\Tesoreria\Cajas\Denominacion;
use App\Models\Tesoreria\Cajas\ArqueoCaja;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Arqueo extends Component
{
    public $desglose = [];

    public $total_efectivo = 0;
    public $total_transferencias = 0;
    public $total_cheques = 0;
    public $caja_actual;
    public $diferencia = 0;
    public $observaciones;

    protected $rules = [
        'desglose.*.cantidad' => 'required|numeric|min:0',
        'total_transferencias' => 'required|numeric|min:0',
        'total_cheques' => 'required|numeric|min:0',
        'total_efectivo' => 'required|numeric|min:0',
        'diferencia' => 'required|numeric',
        'observaciones' => 'nullable|string'
    ];

    public function mount()
    {
        $this->caja_actual = Caja::where('estado', 'ABIERTA')->first();

        // Inicializar el desglose con las denominaciones activas
        $denominaciones = \App\Models\Tesoreria\Cajas\Denominacion::where('activo', true)
            ->orderBy('valor', 'desc')
            ->get();

        foreach ($denominaciones as $denominacion) {
            $this->desglose[$denominacion->idDenominacion] = [
                'valor' => $denominacion->valor,
                'cantidad' => 0,
                'tipo' => $denominacion->tipo
            ];
        }

        if ($this->caja_actual) {
            $this->total_transferencias = $this->caja_actual->movimientos()
                ->where('forma_pago', 'TRANSFERENCIA')
                ->sum('monto');
            $this->total_cheques = $this->caja_actual->movimientos()
                ->where('forma_pago', 'CHEQUE')
                ->sum('monto');
        }
    }

    public function updatedDesglose()
    {
        $this->calcularTotalEfectivo();
        $this->calcularDiferencia();
    }

    public function calcularTotalEfectivo()
    {
        $total = 0;
        foreach ($this->desglose as $id => $detalle) {
            if (isset($detalle['cantidad']) && is_numeric($detalle['cantidad'])) {
                $total += $detalle['valor'] * $detalle['cantidad'];
            }
        }
        $this->total_efectivo = $total;
    }

    public function calcularDiferencia()
    {
        if (!$this->caja_actual) return;

        $saldo_teorico = $this->caja_actual->saldo_inicial;
        $saldo_teorico += $this->caja_actual->movimientos()->where('tipo_movimiento', 'INGRESO')->sum('monto');
        $saldo_teorico -= $this->caja_actual->movimientos()->where('tipo_movimiento', 'EGRESO')->sum('monto');

        $saldo_real = $this->total_efectivo + $this->total_transferencias + $this->total_cheques;

        $this->diferencia = $saldo_real - $saldo_teorico;
    }

    public function guardarArqueo()
    {
        if (!$this->caja_actual) {
            session()->flash('error', 'No hay una caja abierta para realizar el arqueo.');
            return;
        }

        $this->validate();

        // Recalcular totales para asegurar datos actualizados
        $this->calcularTotalEfectivo();
        $this->calcularDiferencia();

        try {
            // Validar que los números sean válidos
            if (
                !is_numeric($this->total_efectivo) || !is_numeric($this->total_transferencias) ||
                !is_numeric($this->total_cheques) || !is_numeric($this->diferencia)
            ) {
                session()->flash('error', 'Los montos calculados no son válidos.');
                return;
            }

            $arqueo = ArqueoCaja::create([
                'relCaja' => $this->caja_actual->idCaja,
                'fecha' => now()->format('Y-m-d'),
                'hora' => now()->format('H:i:s'),
                'total_efectivo' => $this->total_efectivo,
                'total_transferencias' => $this->total_transferencias,
                'total_cheques' => $this->total_cheques,
                'diferencia' => $this->diferencia,
                'desglose' => $this->desglose,
                'observaciones' => $this->observaciones,
                'usuario_registro' => Auth::id()
            ]);

            session()->flash('message', 'Arqueo registrado correctamente.');
            $this->emit('arqueoGuardado');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al guardar el arqueo: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.tesoreria.cajas.arqueo', [
            'arqueos_previos' => $this->caja_actual ? ArqueoCaja::where('relCaja', $this->caja_actual->idCaja)
                ->orderBy('fecha', 'desc')
                ->orderBy('hora', 'desc')
                ->take(5)
                ->get() : collect()
        ]);
    }
}
