<?php

namespace App\Livewire\Tesoreria\CajaDiaria;

use App\Models\Tesoreria\Cajas\CajaApertura;
use App\Models\Tesoreria\Cajas\CajaArqueo;
use App\Models\Tesoreria\Cajas\CajaDesglose;
use App\Models\Tesoreria\TesDiscriminacionMonetaria;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Arqueo extends Component
{
    public $caja_actual = null;
    public $modo_calculo = 'total'; // 'cantidad' | 'total'
    public $desglose = [];
    public $total_efectivo = 0;
    public $total_transferencias = 0;
    public $total_cheques = 0;
    public $total_otros = 0;
    public $diferencia = 0;
    public $observaciones = '';
    public $arqueos_previos;
    public $denominaciones = [];
    public $desglose_invalido = []; // IDs de denominaciones con valor no exacto

    public function mount()
    {
        $this->caja_actual = CajaApertura::abiertas()
            ->porCajero(auth()->id())
            ->first();

        if ($this->caja_actual) {
            $this->arqueos_previos = $this->caja_actual->arqueos()
                ->with('usuarioRegistro')
                ->latest()->take(5)->get();

            $this->denominaciones = TesDiscriminacionMonetaria::activos()
                ->ordenado()
                ->get();

            $this->calcularTotalesMedios();
            $this->inicializarDesglose();
        }
    }

    protected function inicializarDesglose()
    {
        foreach ($this->denominaciones as $den) {
            $this->desglose[$den->id] = [
                'cantidad' => 0,
                'total' => 0,
            ];
        }
    }

    /**
     * Cargar el desglose del último arqueo realizado en esta caja
     */
    public function cargarUltimoArqueo()
    {
        if (!$this->caja_actual) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'No hay una caja abierta.']);
            return;
        }

        $ultimoArqueo = $this->caja_actual->arqueos()
            ->with('desgloses')
            ->latest()
            ->first();

        if (!$ultimoArqueo) {
            $this->dispatch('alert', ['type' => 'warning', 'message' => 'No hay arqueos previos para cargar.']);
            return;
        }

        // Reiniciar desglose
        $this->inicializarDesglose();

        // Cargar desglose del último arqueo
        foreach ($ultimoArqueo->desgloses as $desg) {
            if (isset($this->desglose[$desg->tes_discriminacion_monetaria_id])) {
                $this->desglose[$desg->tes_discriminacion_monetaria_id] = [
                    'cantidad' => $desg->cantidad,
                    'total' => $desg->subtotal,
                ];
            }
        }

        $this->recalcular();
        $this->dispatch('alert', [
            'type' => 'success',
            'message' => 'Desglose del último arqueo cargado exitosamente.'
        ]);
    }

    /**
     * Cargar el desglose del saldo inicial (apertura de caja)
     */
    public function cargarSaldoInicial()
    {
        if (!$this->caja_actual) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'No hay una caja abierta.']);
            return;
        }

        // Reiniciar desglose
        $this->inicializarDesglose();

        // Cargar desglose de la apertura
        $desglosesApertura = CajaDesglose::where('caja_apertura_id', $this->caja_actual->id)
            ->where('tipo_referencia', 'apertura')
            ->whereNull('arqueo_id')
            ->get();

        if ($desglosesApertura->isEmpty()) {
            $this->dispatch('alert', ['type' => 'warning', 'message' => 'No hay desglose de apertura para cargar.']);
            return;
        }

        foreach ($desglosesApertura as $desg) {
            if (isset($this->desglose[$desg->tes_discriminacion_monetaria_id])) {
                $this->desglose[$desg->tes_discriminacion_monetaria_id] = [
                    'cantidad' => $desg->cantidad,
                    'total' => $desg->subtotal,
                ];
            }
        }

        $this->recalcular();
        $this->dispatch('alert', [
            'type' => 'success',
            'message' => 'Desglose del saldo inicial cargado exitosamente.'
        ]);
    }

    protected function calcularTotalesMedios()
    {
        $this->total_transferencias = (float) $this->caja_actual->movimientos()
            ->where('tipo_movimiento', 'INGRESO')
            ->whereHas('medioPago', function ($q) {
                $q->where('nombre', 'like', '%Transferencia%');
            })
            ->sum('monto');

        $this->total_cheques = (float) $this->caja_actual->movimientos()
            ->where('tipo_movimiento', 'INGRESO')
            ->whereHas('medioPago', function ($q) {
                $q->where('nombre', 'like', '%Cheque%');
            })
            ->sum('monto');

        // Otros medios de pago no efectivo (tarjetas, etc.) que se contabilizan aparte
        $this->total_otros = $this->caja_actual->totalIngresosOtros()
            - $this->total_transferencias
            - $this->total_cheques;
    }

    /**
     * Normaliza un valor numérico proveniente de inputs.
     */
    protected function numeroDeDesglose($valor): float
    {
        if ($valor === null || $valor === '') {
            return 0.0;
        }

        if (is_numeric($valor)) {
            return (float) $valor;
        }

        if (is_string($valor)) {
            $normalizado = str_replace([',', ' '], ['.', ''], trim($valor));
            if (is_numeric($normalizado)) {
                return (float) $normalizado;
            }
        }

        return 0.0;
    }

    public function updatedDesglose()
    {
        $this->validarDesglose();
        $this->sincronizarDesglose();
        $this->recalcular();
    }

    public function updatedModoCalculo()
    {
        $this->sincronizarDesglose();
        $this->recalcular();
    }

    protected function sincronizarDesglose()
    {
        foreach ($this->desglose as $denId => $valores) {
            $den = $this->denominaciones->firstWhere('id', $denId);
            if (!$den) continue;

            $cantidad = $this->numeroDeDesglose($valores['cantidad'] ?? 0);
            $total = $this->numeroDeDesglose($valores['total'] ?? 0);

            if ($this->modo_calculo === 'cantidad') {
                $this->desglose[$denId]['total'] = $cantidad * (float) $den->valor;
            } else {
                $this->desglose[$denId]['cantidad'] = $total > 0
                    ? floor($total / (float) $den->valor)
                    : 0;
            }
        }
    }

    protected function validarDesglose()
    {
        $nuevosInvalidos = [];

        foreach ($this->desglose as $denId => $valores) {
            $den = $this->denominaciones->firstWhere('id', $denId);
            if (!$den || $den->valor <= 0) continue;

            $esInvalido = false;

            if ($this->modo_calculo === 'cantidad') {
                $cantidad = $this->numeroDeDesglose($valores['cantidad'] ?? 0);
                if ($cantidad != floor($cantidad) || $cantidad < 0) {
                    $esInvalido = true;
                }
            } else {
                $total = $this->numeroDeDesglose($valores['total'] ?? 0);
                if ($total > 0) {
                    $cociente = $total / (float) $den->valor;
                    if (abs($cociente - round($cociente)) > 0.0001) {
                        $esInvalido = true;
                    }
                }
            }

            if ($esInvalido) {
                $nuevosInvalidos[] = (string) $denId;
            }
        }

        if (!empty($nuevosInvalidos)) {
            $campoInvalidado = $this->modo_calculo === 'cantidad' ? 'cantidad' : 'total';
            $this->dispatch('swal:toast:warning', [
                'title' => 'Valor no exacto',
                'text'  => 'El monto ingresado no es divisible exactamente por el valor de esa denominación.',
                'focoDenId' => (int) $nuevosInvalidos[0],
                'focoCampo' => $campoInvalidado,
            ]);
        }

        $this->desglose_invalido = $nuevosInvalidos;
    }

    public function guardarArqueo()
    {
        if (!$this->caja_actual) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'No hay una caja abierta.']);
            return;
        }

        DB::transaction(function () {
            $arqueo = CajaArqueo::create([
                'caja_apertura_id' => $this->caja_actual->id,
                'total_efectivo' => $this->total_efectivo,
                'total_transferencias' => $this->total_transferencias,
                'total_cheques' => $this->total_cheques,
                'diferencia' => $this->diferencia,
                'observaciones' => $this->observaciones,
                'usuario_id' => auth()->id(),
            ]);

            // Guardar desglose del arqueo
            foreach ($this->desglose as $denId => $valores) {
                $cantidad = (int) $this->numeroDeDesglose($valores['cantidad'] ?? 0);
                if ($cantidad > 0) {
                    $den = $this->denominaciones->firstWhere('id', $denId);
                    $subtotal = $cantidad * ($den ? (float) $den->valor : 0);

                    CajaDesglose::create([
                        'caja_apertura_id' => $this->caja_actual->id,
                        'arqueo_id' => $arqueo->id,
                        'tes_discriminacion_monetaria_id' => $denId,
                        'cantidad' => $cantidad,
                        'subtotal' => $subtotal,
                        'tipo_referencia' => 'arqueo',
                    ]);
                }
            }
        });

        $this->arqueos_previos = $this->caja_actual->arqueos()->with('usuarioRegistro')->latest()->take(5)->get();
        $this->observaciones = '';
        
        session()->flash('alert', [
            'type' => 'success',
            'message' => 'Arqueo guardado exitosamente.'
        ]);
        
        return redirect()->route('tesoreria.caja-diaria.index');
    }

    protected function recalcular()
    {
        $this->total_efectivo = 0;
        foreach ($this->desglose as $denId => $valores) {
            $den = $this->denominaciones->firstWhere('id', $denId);
            if ($den) {
                $cantidad = $this->numeroDeDesglose($valores['cantidad'] ?? 0);
                $total = $this->numeroDeDesglose($valores['total'] ?? 0);

                if ($this->modo_calculo === 'total') {
                    $this->total_efectivo += $total;
                    $this->desglose[$denId]['cantidad'] = $total > 0
                        ? floor($total / (float) $den->valor)
                        : 0;
                } else {
                    $this->total_efectivo += $cantidad * (float) $den->valor;
                    $this->desglose[$denId]['total'] = $cantidad * (float) $den->valor;
                }
            }
        }

        // La diferencia solo compara efectivo contado contra el saldo esperado en efectivo
        if ($this->caja_actual) {
            $saldoEsperado = (float) $this->caja_actual->obtenerSaldoActual();
            $this->diferencia = $this->total_efectivo - $saldoEsperado;
        }
    }

    public function render()
    {
        return view('livewire.tesoreria.cajas.arqueo', [
            'caja_actual' => $this->caja_actual,
            'denominaciones' => $this->denominaciones,
            'desglose' => $this->desglose,
            'modo_calculo' => $this->modo_calculo,
            'total_efectivo' => $this->total_efectivo,
            'total_transferencias' => $this->total_transferencias,
            'total_cheques' => $this->total_cheques,
            'total_otros' => $this->total_otros,
            'diferencia' => $this->diferencia,
            'observaciones' => $this->observaciones,
            'arqueos_previos' => $this->arqueos_previos,
            'desglose_invalido' => $this->desglose_invalido,
        ]);
    }
}