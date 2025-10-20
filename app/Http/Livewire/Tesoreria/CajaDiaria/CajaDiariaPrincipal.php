<?php

namespace App\Http\Livewire\Tesoreria\CajaDiaria;

use Livewire\Component;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;
use App\Models\Cobro;
use App\Models\Pago;
use App\Models\Tesoreria\CajaDiaria\TesCajaDiarias;

class CajaDiariaPrincipal extends Component
{
    public $fecha;
    public $activeTab;
    public $fechaKey = 0;

    protected $listeners = [
        'cajaInicialGuardada' => 'refreshCajaDiariaExists',
        'refresh' => '$refresh'
    ];

    // Resumen properties
    public $saldoInicial = 0;
    public $totalCobros = 0;
    public $totalPagos = 0;
    public $saldoActual = 0;
    public $cajaDiariaExists = true;

    public function mount($tab = null)
    {
        $this->activeTab = $tab ?: 'resumen';
        $this->fecha = session('caja_diaria_fecha', date('Y-m-d'));
        $this->calcularSaldos();
    }

    public function updatedFecha()
    {
        session(['caja_diaria_fecha' => $this->fecha]);
        $this->fechaKey++;
        $this->calcularSaldos();
    }

    private function calcularSaldos()
    {
        try {
            $fecha = Carbon::parse($this->fecha);

            // Verificar si existe una caja diaria para la fecha
            $cajaDiaria = TesCajaDiarias::whereDate('fecha', $fecha)->first();

            if ($cajaDiaria) {
                $this->saldoInicial = floatval($cajaDiaria->monto_inicial);
                $this->cajaDiariaExists = true;
            } else {
                $this->saldoInicial = 0;
                $this->cajaDiariaExists = false;
            }

            // Obtener cobros del día (solo efectivo)
            $this->totalCobros = Cobro::whereDate('fecha', $fecha)
                ->where('medio_pago', 'efectivo')
                ->sum('monto');

            // Obtener pagos del día (solo efectivo)
            $this->totalPagos = Pago::whereDate('fecha', $fecha)
                ->where('medio_pago', 'efectivo')
                ->sum('monto');

            // Asegurarse de que los valores son numéricos
            $this->totalCobros = floatval($this->totalCobros);
            $this->totalPagos = floatval($this->totalPagos);

            // Calcular saldo actual
            $this->saldoActual = $this->saldoInicial + $this->totalCobros - $this->totalPagos;
        } catch (\Exception $e) {
            // En caso de error, registrar y mostrar.
            session()->flash('error', 'Error al calcular saldos: ' . $e->getMessage());
            $this->totalCobros = 0;
            $this->totalPagos = 0;
            $this->saldoInicial = 0;
            $this->saldoActual = 0;
            $this->cajaDiariaExists = false; // Forzar a false en caso de error
        }
    }

    public function refreshCajaDiariaExists()
    {
        $this->calcularSaldos();
    }

    public function render()
    {
        return view('livewire.tesoreria.caja-diaria.principal');
    }
}
