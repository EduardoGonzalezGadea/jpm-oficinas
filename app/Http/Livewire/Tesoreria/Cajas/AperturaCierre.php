<?php

namespace App\Http\Livewire\Tesoreria\Cajas;

use Livewire\Component;
use App\Models\Tesoreria\Cajas\Caja;
use App\Models\Tesoreria\Cajas\Denominacion;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AperturaCierre extends Component
{
    public $cajaAbierta = null;
    public $saldo_inicial = 0;
    public $fecha_apertura;
    public $observaciones;
    public $modo_calculo = 'cantidad';
    // Para el desglose de efectivo
    public $desglose = [];

    protected $rules = [
        'saldo_inicial' => 'required|numeric|min:0',
        'fecha_apertura' => 'required|date',
        'observaciones' => 'nullable|string',
        'desglose' => 'array'
    ];

    public function mount()
    {
        $this->fecha_apertura = now()->format('Y-m-d');
        $this->cajaAbierta = Caja::with('usuarioApertura')->where('estado', 'ABIERTA')->first();

        // Inicializar el desglose con las denominaciones activas
        if (!$this->cajaAbierta) {
            $this->inicializarDesglose();
        }
    }

    public function inicializarDesglose()
    {
        $denominaciones = Denominacion::where('activo', true)
            ->orderBy('orden', 'asc')
            ->get();

        foreach ($denominaciones as $denominacion) {
            $this->desglose[$denominacion->idDenominacion] = [
                'valor' => $denominacion->valor,
                'cantidad' => 0,
                'total' => 0,
                'tipo' => $denominacion->tipo,
            ];
        }
    }

    public function updatedDesglose($value, $key)
    {
        // Extraer el ID de denominación y el campo actualizado del key (ejemplo: "1.cantidad" o "1.total")
        $partes = explode('.', $key);
        if (count($partes) !== 2) return;

        $idDenominacion = $partes[0];
        $campo = $partes[1];

        if (!isset($this->desglose[$idDenominacion])) return;

        $valor = $this->desglose[$idDenominacion]['valor'];

        // Si se actualizó la cantidad, calcular el total
        if ($campo === 'cantidad') {
            $cantidad = is_numeric($value) ? $value : 0;
            $this->desglose[$idDenominacion]['total'] = $cantidad * $valor;
        }
        // Si se actualizó el total, calcular la cantidad
        elseif ($campo === 'total') {
            $total = is_numeric($value) ? $value : 0;
            // Asegurarse de que el total sea un múltiplo del valor de la denominación
            $this->desglose[$idDenominacion]['cantidad'] = $valor > 0 ? floor($total / $valor) : 0;
            //Ajustar el total para que sea exacto
            $this->desglose[$idDenominacion]['total'] = $this->desglose[$idDenominacion]['cantidad'] * $valor;
        }

        // Recalcular el saldo inicial
        $this->calcularSaldoInicial();
    }

    protected function calcularSaldoInicial()
    {
        $total = 0;
        foreach ($this->desglose as $detalle) {
            if (isset($detalle['cantidad']) && is_numeric($detalle['cantidad'])) {
                $total += $detalle['valor'] * $detalle['cantidad'];
            }
        }
        $this->saldo_inicial = $total;
    }

    public function updatedModoCalculo($value)
    {
        // Limpiar los valores al cambiar de modo para evitar inconsistencias
        $this->saldo_inicial = 0;
        $this->inicializarDesglose();
    }

    public function abrirCaja()
    {
        $this->validate();

        try {
            $caja = Caja::create([
                'fecha_apertura' => $this->fecha_apertura,
                'hora_apertura' => Carbon::now()->format('H:i:s'),
                'saldo_inicial' => $this->saldo_inicial,
                'estado' => 'ABIERTA',
                'usuario_apertura' => Auth::id(),
                'observaciones' => $this->observaciones
            ]);

            $this->cajaAbierta = $caja;
            $this->emit('cajaAbierta');

            session()->flash('message', 'Caja abierta correctamente.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al abrir la caja: ' . $e->getMessage());
        }
    }

    public function cerrarCaja()
    {
        if (!$this->cajaAbierta) {
            session()->flash('error', 'No hay una caja abierta para cerrar.');
            return;
        }

        try {
            $this->cajaAbierta->update([
                'fecha_cierre' => now()->format('Y-m-d'),
                'hora_cierre' => now()->format('H:i:s'),
                'saldo_final' => $this->cajaAbierta->obtenerSaldoActual(),
                'estado' => 'CERRADA',
                'usuario_cierre' => Auth::id()
            ]);

            $this->cajaAbierta = null;
            $this->emit('cajaCerrada');
            $this->inicializarDesglose();

            session()->flash('message', 'Caja cerrada correctamente.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al cerrar la caja: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.tesoreria.cajas.apertura-cierre', [
            'denominaciones' => Denominacion::where('activo', true)
                ->orderBy('orden', 'asc')
                ->get()
        ]);
    }
}
