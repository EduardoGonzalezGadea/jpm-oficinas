<?php

namespace App\Livewire\Tesoreria\CajaDiaria;

use App\Models\Tesoreria\Cajas\CajaApertura;
use App\Models\Tesoreria\Cajas\CajaArqueo;
use App\Models\Tesoreria\Cajas\CajaDesglose;
use App\Models\Tesoreria\TesDiscriminacionMonetaria;
use App\Models\Tesoreria\MedioDePago;
use App\Models\Tesoreria\LibroDiario;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AperturaCierre extends Component
{
    public $cajaAbierta = null;
    public $fecha_apertura;
    public $modo_calculo = 'total'; // 'cantidad' | 'total'
    public $desglose = [];
    public $saldo_inicial = 0;
    public $saldo_inicial_sugerido = 0;
    public $observaciones = '';
    public $denominaciones = [];
    public $total_efectivo = 0;
    public $total_transferencias = 0;
    public $total_cheques = 0;
    public $total_otros = 0;
    public $diferencia = 0;
    public $saldo_esperado_ld = 0;
    public $desglose_invalido = []; // IDs de denominaciones con valor no exacto

    public function mount()
    {
        $this->cajaAbierta = CajaApertura::abiertas()
            ->porCajero(auth()->id())
            ->first();

        $this->fecha_apertura = today()->format('Y-m-d');

        // Usar TesDiscriminacionMonetaria (Sistema → Opciones → Discriminaciones Monetarias)
        $this->denominaciones = TesDiscriminacionMonetaria::activos()
            ->ordenado()
            ->get();

        // Inicializar desglose
        foreach ($this->denominaciones as $den) {
            $this->desglose[$den->id] = [
                'cantidad' => 0,
                'total' => 0,
            ];
        }

        // Sugerir saldo inicial desde caja anterior
        $this->calcularSaldoInicialSugerido();

        if ($this->cajaAbierta) {
            $this->calcularTotalesMedios();
            $this->recalcular();
        }
    }

    public function updatedFechaApertura()
    {
        $this->calcularSaldoInicialSugerido();
    }

    protected function calcularSaldoInicialSugerido()
    {
        $this->saldo_inicial_sugerido = (float) CajaApertura::saldoCierreAnterior(
            auth()->id(),
            $this->fecha_apertura
        );
        
        // Si no hay caja anterior, usar 0
        if ($this->saldo_inicial_sugerido === null) {
            $this->saldo_inicial_sugerido = 0;
        }
        
        // Si el usuario no ha modificado el saldo_inicial, usar el sugerido
        if ($this->saldo_inicial === 0) {
            $this->saldo_inicial = $this->saldo_inicial_sugerido;
            $this->recalcularSaldoInicial();
        }
    }

    public function cargarDesgloseCajaAnterior()
    {
        $cajaAnterior = CajaApertura::cerradas()
            ->porCajero(auth()->id())
            ->latest('id')
            ->first();

        if (!$cajaAnterior) {
            $this->dispatch('alert', [
                'type' => 'warning',
                'message' => 'No hay una caja anterior cerrada registrada.',
            ]);
            return;
        }

        // Buscar desglose de cierre o último arqueo de esa caja anterior
        $desgloses = CajaDesglose::where('caja_apertura_id', $cajaAnterior->id)
            ->where('tipo_referencia', 'cierre')
            ->get();

        if ($desgloses->isEmpty()) {
            $ultimoArqueo = $cajaAnterior->arqueos()->latest()->first();
            if ($ultimoArqueo) {
                $desgloses = $ultimoArqueo->desgloses;
            }
        }

        if ($desgloses->isEmpty()) {
            $this->dispatch('alert', [
                'type' => 'warning',
                'message' => 'La caja anterior no registra discriminación monetaria de cierre.',
            ]);
            return;
        }

        // Reiniciar desglose actual
        foreach ($this->denominaciones as $den) {
            $this->desglose[$den->id] = [
                'cantidad' => 0,
                'total' => 0,
            ];
        }

        // Poblar con el desglose de la caja anterior
        foreach ($desgloses as $item) {
            $denId = $item->tes_discriminacion_monetaria_id;
            if (isset($this->desglose[$denId])) {
                $den = $this->denominaciones->firstWhere('id', $denId);
                $cant = (int) $item->cantidad;
                $this->desglose[$denId]['cantidad'] = $cant;
                $this->desglose[$denId]['total'] = $cant * ($den ? $den->valor : 0);
            }
        }

        // Recalcular el saldo inicial y totales
        $this->recalcularSaldoInicial();
        $this->recalcular();

        $fechaStr = $cajaAnterior->fecha_apertura ? $cajaAnterior->fecha_apertura->format('d/m/Y') : '';
        $this->dispatch('alert', [
            'type' => 'success',
            'message' => "Se cargó el desglose monetario de la caja anterior" . ($fechaStr ? " ({$fechaStr})" : "") . ".",
        ]);
    }

    public function updatedDesglose()
    {
        $this->validarDesglose();
        $this->recalcularSaldoInicial();
        $this->recalcular();
    }

    /**
     * Normaliza un valor proveniente del input (string vacío, texto, decimales
     * con coma, etc.) a un número float seguro para evitar TypeErrors.
     */
    protected function numeroDeDesglose($valor): float
    {
        if ($valor === null || $valor === '') {
            return 0.0;
        }

        if (is_numeric($valor)) {
            return (float) $valor;
        }

        // Reemplazar coma decimal por punto por si el usuario la ingresa manualmente
        if (is_string($valor)) {
            $normalizado = str_replace([',', ' '], ['.', ''], trim($valor));
            if (is_numeric($normalizado)) {
                return (float) $normalizado;
            }
        }

        return 0.0;
    }

    protected function validarDesglose()
    {
        $nuevosInvalidos = [];

        foreach ($this->desglose as $denId => $valores) {
            $den = $this->denominaciones->firstWhere('id', $denId);
            if (!$den || $den->valor <= 0) continue;

            $esInvalido = false;

            if ($this->modo_calculo === 'cantidad') {
                // En modo cantidad: la cantidad debe ser un entero no negativo
                $cantidad = $this->numeroDeDesglose($valores['cantidad'] ?? 0);
                if ($cantidad != floor($cantidad) || $cantidad < 0) {
                    $esInvalido = true;
                }
            } else {
                // En modo total: el total debe ser divisible exactamente por el valor de la denominación
                $total = $this->numeroDeDesglose($valores['total'] ?? 0);
                if ($total > 0) {
                    $cociente = $total / (float) $den->valor;
                    // Usar tolerancia para punto flotante
                    if (abs($cociente - round($cociente)) > 0.0001) {
                        $esInvalido = true;
                    }
                }
            }

            if ($esInvalido) {
                $nuevosInvalidos[] = (string) $denId;
            }
        }

        // Disparar la advertencia cada vez que al actualizar el desglose quede
        // al menos un valor no exacto (no solo cuando aparecen filas nuevas).
        if (!empty($nuevosInvalidos)) {
            $campoInvalidado = $this->modo_calculo === 'cantidad' ? 'cantidad' : 'total';
            $this->dispatch('swal:toast:warning', [
                'title' => 'Valor no exacto',
                'text'  => 'El monto ingresado no es divisible exactamente por el valor de esa denominación.',
                // Devuelve el foco al primer campo con valor incorrecto.
                'focoDenId' => (int) $nuevosInvalidos[0],
                'focoCampo' => $campoInvalidado,
            ]);
        }

        $this->desglose_invalido = $nuevosInvalidos;
    }

    public function updatedModoCalculo()
    {
        $this->recalcularSaldoInicial();
    }

    protected function recalcularSaldoInicial()
    {
        $this->saldo_inicial = 0;
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
            $this->saldo_inicial += $this->desglose[$denId]['total'] ?? 0;
        }
    }

    protected function calcularTotalesMedios()
    {
        $totales = $this->cajaAbierta->totalesPorMedio();

        $this->total_transferencias = (float) $totales
            ->filter(fn($f) => str_contains(mb_strtolower($f->medio_nombre), 'transferencia'))
            ->sum(fn($f) => $f->entradas - $f->salidas);

        $this->total_cheques = (float) $totales
            ->filter(fn($f) => str_contains(mb_strtolower($f->medio_nombre), 'cheque'))
            ->sum(fn($f) => $f->entradas - $f->salidas);

        $this->total_otros = (float) $totales
            ->filter(fn($f) => !str_contains(mb_strtolower($f->medio_nombre), 'efectivo')
                && !str_contains(mb_strtolower($f->medio_nombre), 'transferencia')
                && !str_contains(mb_strtolower($f->medio_nombre), 'cheque'))
            ->sum(fn($f) => $f->entradas - $f->salidas);
    }

    protected function recalcular()
    {
        $this->total_efectivo = 0;
        foreach ($this->desglose as $denId => $valores) {
            $den = $this->denominaciones->firstWhere('id', $denId);
            if (!$den) continue;

            $cantidad = $this->numeroDeDesglose($valores['cantidad'] ?? 0);
            $total = $this->numeroDeDesglose($valores['total'] ?? 0);

            if ($this->modo_calculo === 'total') {
                $this->total_efectivo += $total;
            } else {
                $this->total_efectivo += $cantidad * (float) $den->valor;
            }
        }

        if (!$this->cajaAbierta) {
            return;
        }

        // Obtener saldo esperado según Libro Diario
        $this->saldo_esperado_ld = $this->cajaAbierta->obtenerSaldoActual();
        
        // La diferencia compara efectivo contado contra el saldo esperado en efectivo
        $this->diferencia = $this->total_efectivo - $this->saldo_esperado_ld;
    }

    public function abrirCaja()
    {
        $this->validate([
            'fecha_apertura' => 'required|date',
            'saldo_inicial' => 'required|numeric|min:0',
        ]);

        if ($this->cajaAbierta) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Ya tienes una caja abierta.']);
            return;
        }

        DB::transaction(function () {
            $apertura = CajaApertura::create([
                'cajero_id' => auth()->id(),
                'fecha_apertura' => $this->fecha_apertura,
                'hora_apertura' => now()->format('H:i:s'),
                'saldo_inicial' => $this->saldo_inicial,
                'estado' => 'abierta',
                'observaciones' => $this->observaciones,
                'created_by' => auth()->id(),
            ]);

            // Guardar desglose
            foreach ($this->desglose as $denId => $valores) {
                $cantidad = $this->numeroDeDesglose($valores['cantidad'] ?? 0);
                $total = $this->numeroDeDesglose($valores['total'] ?? 0);
                if ($cantidad > 0 || $total > 0) {
                    CajaDesglose::create([
                        'caja_apertura_id' => $apertura->id,
                        'tes_discriminacion_monetaria_id' => $denId,
                        'cantidad' => $cantidad,
                        'subtotal' => $total,
                        'tipo_referencia' => 'apertura',
                    ]);
                }
            }

            $this->cajaAbierta = $apertura;
        });

        // Al abrir la caja se redirige al index de Caja Diaria, que queda
        // actualizado con la caja recién creada.
        return redirect()->route('tesoreria.caja-diaria.index');
    }

    public function cargarUltimoArqueo()
    {
        if (!$this->cajaAbierta) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'No hay una caja abierta.']);
            return;
        }

        $ultimoArqueo = $this->cajaAbierta->arqueos()
            ->with('desgloses')
            ->latest()
            ->first();

        if (!$ultimoArqueo) {
            $this->dispatch('alert', [
                'type' => 'warning',
                'message' => 'No hay arqueos previos registrados en esta caja.',
            ]);
            return;
        }

        // Reiniciar desgloses a 0
        foreach ($this->denominaciones as $den) {
            $this->desglose[$den->id] = [
                'cantidad' => 0,
                'total' => 0,
            ];
        }

        // Cargar cantidades del último arqueo
        foreach ($ultimoArqueo->desgloses as $item) {
            $denId = $item->tes_discriminacion_monetaria_id;
            if (isset($this->desglose[$denId])) {
                $this->desglose[$denId]['cantidad'] = (int) $item->cantidad;
                $this->desglose[$denId]['total'] = (float) $item->subtotal;
            }
        }

        // Recalcular saldo efectivo y diferencia de cierre
        $this->recalcular();

        $fecha = $ultimoArqueo->created_at ? $ultimoArqueo->created_at->format('d/m/Y H:i') : '';
        $this->dispatch('alert', [
            'type' => 'success',
            'message' => "Se cargó el desglose del último arqueo registrado" . ($fecha ? " ({$fecha})" : "") . ".",
        ]);
    }

    public function cerrarCaja()
    {
        if (!$this->cajaAbierta) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'No hay una caja abierta.']);
            return;
        }

        $this->validate([
            'total_efectivo' => 'required|numeric|min:0',
        ]);

        // Validar cuadratura: diferencia no debe superar tolerancia
        $tolerancia = 0.50; // 50 centésimos
        if (abs($this->diferencia) > $tolerancia) {
            $this->dispatch('swal:error', [
                'title' => 'No se puede cerrar la caja',
                'text' => 'Diferencia supera tolerancia (50 centésimos). No se puede cerrar hasta corregir.',
            ]);
            $this->addError('total_efectivo', 
                "Diferencia de $ " . number_format(abs($this->diferencia), 2, ',', '.') . 
                " vs Libro Diario (esperado: $ " . number_format($this->saldo_esperado_ld, 2, ',', '.') . "). Revisar arqueo."
            );
            return;
        }

        DB::transaction(function () {
            // Registrar el arqueo de cierre con la discriminación monetaria actual
            $arqueo = CajaArqueo::create([
                'caja_apertura_id' => $this->cajaAbierta->id,
                'total_efectivo' => $this->total_efectivo,
                'total_transferencias' => $this->total_transferencias,
                'total_cheques' => $this->total_cheques,
                'diferencia' => $this->diferencia,
                'observaciones' => $this->observaciones,
                'usuario_id' => auth()->id(),
            ]);

            // Guardar desglose del cierre
            foreach ($this->desglose as $denId => $valores) {
                $cantidad = $this->numeroDeDesglose($valores['cantidad'] ?? 0);
                if ($cantidad > 0) {
                    $den = $this->denominaciones->firstWhere('id', $denId);
                    CajaDesglose::create([
                        'caja_apertura_id' => $this->cajaAbierta->id,
                        'arqueo_id' => $arqueo->id,
                        'tes_discriminacion_monetaria_id' => $denId,
                        'cantidad' => $cantidad,
                        'subtotal' => $cantidad * ($den ? (float) $den->valor : 0),
                        'tipo_referencia' => 'cierre',
                    ]);
                }
            }

            $this->cajaAbierta->cerrar([
                'saldo_cierre' => $this->total_efectivo,
                'observaciones' => $this->observaciones,
            ]);
        });

        $this->cajaAbierta = null;
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Caja cerrada exitosamente con arqueo de cierre registrado.']);
    }

    public function render()
    {
        return view('livewire.tesoreria.cajas.apertura-cierre', [
            'cajaAbierta' => $this->cajaAbierta,
            'denominaciones' => $this->denominaciones,
            'desglose' => $this->desglose,
            'saldo_inicial' => $this->saldo_inicial,
            'saldo_inicial_sugerido' => $this->saldo_inicial_sugerido,
            'modo_calculo' => $this->modo_calculo,
            'fecha_apertura' => $this->fecha_apertura,
            'observaciones' => $this->observaciones,
            'total_efectivo' => $this->total_efectivo,
            'total_transferencias' => $this->total_transferencias,
            'total_cheques' => $this->total_cheques,
            'total_otros' => $this->total_otros,
            'diferencia' => $this->diferencia,
            'saldo_esperado_ld' => $this->saldo_esperado_ld,
            'desglose_invalido' => $this->desglose_invalido,
        ])->extends('layouts.app')->section('content');
    }
}