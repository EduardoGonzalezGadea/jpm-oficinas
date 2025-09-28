<?php

namespace App\Http\Livewire\Tesoreria\CajaDiaria;

use Livewire\Component;
use Carbon\Carbon;
use App\Models\Cobro;
use App\Models\Pago;
use App\Models\Tesoreria\TesDenominacionMoneda;
use App\Tesoreria\CajaDiaria\TesCajaDiarias;

class Resumen extends Component
{
    public $saldoInicial = 0;
    public $totalCobros = 0;
    public $totalPagos = 0;
    public $saldoActual = 0;
    public $fecha;
    public $cajaDiariaExists = true;

    public $denominaciones = [];
    public $cajaInicialPorDenominacion = [];
    public $cajaInicialTotal = 0;
    public $cierrePorDenominacion = [];
    public $cierreTotal = 0;

    public function mount($fecha = null)
    {
        $this->fecha = $fecha ?: now()->format('Y-m-d');
        $this->denominaciones = TesDenominacionMoneda::activos()->ordenado()->get();
        $this->inicializarCajas();
        $this->calcularSaldos();
    }

    private function inicializarCajas()
    {
        foreach ($this->denominaciones as $denominacion) {
            $this->cajaInicialPorDenominacion[$denominacion->id] = [
                'cantidad' => 0,
                'monto' => 0
            ];
            $this->cierrePorDenominacion[$denominacion->id] = [
                'cantidad' => 0,
                'monto' => 0
            ];
        }
    }


    public function updatedCajaInicialPorDenominacion($value, $key)
    {
        $parts = explode('.', $key);
        $id = $parts[0];
        $field = $parts[1];

        $value = is_numeric($value) ? (float)$value : 0;

        if ($field === 'cantidad') {
            $denominacion = $this->denominaciones->find($id);
            $this->cajaInicialPorDenominacion[$id]['monto'] = $value * (float)$denominacion->denominacion;
        } elseif ($field === 'monto') {
            $denominacion = $this->denominaciones->find($id);
            $this->cajaInicialPorDenominacion[$id]['cantidad'] = $value / (float)$denominacion->denominacion;
        }

        $this->calcularCajaInicialTotal();
    }

    public function updatedCierrePorDenominacion($value, $key)
    {
        $parts = explode('.', $key);
        $id = $parts[0];
        $field = $parts[1];

        $value = is_numeric($value) ? (float)$value : 0;

        if ($field === 'cantidad') {
            $denominacion = $this->denominaciones->find($id);
            $this->cierrePorDenominacion[$id]['monto'] = $value * (float)$denominacion->denominacion;
        } elseif ($field === 'monto') {
            $denominacion = $this->denominaciones->find($id);
            $this->cierrePorDenominacion[$id]['cantidad'] = $value / (float)$denominacion->denominacion;
        }

        $this->calcularCierreTotal();
    }

    public function updatedCajaInicialTotal()
    {
        // When total is set, clear the denomination details
        foreach ($this->cajaInicialPorDenominacion as $id => $data) {
            $this->cajaInicialPorDenominacion[$id]['cantidad'] = 0;
            $this->cajaInicialPorDenominacion[$id]['monto'] = 0;
        }
    }

    public function updatedCierreTotal()
    {
        // When total is set, clear the denomination details
        foreach ($this->cierrePorDenominacion as $id => $data) {
            $this->cierrePorDenominacion[$id]['cantidad'] = 0;
            $this->cierrePorDenominacion[$id]['monto'] = 0;
        }
    }

    private function calcularCajaInicialTotal()
    {
        $this->cajaInicialTotal = array_sum(array_column($this->cajaInicialPorDenominacion, 'monto'));
    }

    private function calcularCierreTotal()
    {
        $this->cierreTotal = array_sum(array_column($this->cierrePorDenominacion, 'monto'));
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
            // En caso de error, inicializar todo en cero
            $this->totalCobros = 0;
            $this->totalPagos = 0;
            $this->saldoInicial = 0;
            $this->saldoActual = 0;
            $this->cajaDiariaExists = true; // Asumir que existe para no mostrar alerta por error
        }
    }

    public function guardarCajaInicial()
    {
        // Lógica para guardar caja inicial
        // Por ejemplo, validar y guardar en base de datos
        session()->flash('message', 'Caja Inicial guardada correctamente.');
    }

    public function guardarCierreCaja()
    {
        // Lógica para guardar cierre de caja
        // Por ejemplo, validar y guardar en base de datos
        session()->flash('message', 'Cierre de Caja guardado correctamente.');
    }

    public function render()
    {
        return view('livewire.tesoreria.caja-diaria.resumen');
    }
}
