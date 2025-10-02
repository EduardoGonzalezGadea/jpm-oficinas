<?php

namespace App\Http\Livewire\Tesoreria\CajaDiaria;

use Livewire\Component;
use Carbon\Carbon;
use App\Models\Cobro;
use App\Models\Pago;
use App\Models\Tesoreria\TesDenominacionMoneda;
use App\Models\Tesoreria\CajaDiaria\TesCajaDiarias;
use App\Models\Tesoreria\CajaDiaria\TesCdInicial;
use App\Models\Tesoreria\CajaDiaria\TesCdCierre;
use App\Models\Tesoreria\CajaDiaria\TesCdCierreDenominacion;

class Resumen extends Component
{
    public $fecha;
    public $cajaDiariaExists;
    public $saldoInicial;
    public $totalCobros;
    public $totalPagos;
    public $saldoActual;

    public $denominaciones = [];
    public $cajaInicialPorDenominacion = [];
    public $cajaInicialTotal = 0;
    public $cierrePorDenominacion = [];
    public $cierreTotal = 0;

    public function mount($fecha, $cajaDiariaExists, $saldoInicial, $totalCobros, $totalPagos, $saldoActual)
    {
        $this->fecha = $fecha;
        $this->cajaDiariaExists = $cajaDiariaExists;
        $this->saldoInicial = $saldoInicial;
        $this->totalCobros = $totalCobros;
        $this->totalPagos = $totalPagos;
        $this->saldoActual = $saldoActual;

        $this->denominaciones = TesDenominacionMoneda::activos()->ordenado()->get();
        $this->inicializarCajas();
        $this->loadCajaData();
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

    private function loadCajaData()
    {
        if ($this->cajaDiariaExists) {
            try {
                $fecha = Carbon::parse($this->fecha);
                $cajaDiaria = TesCajaDiarias::whereDate('fecha', $fecha)->with('iniciales')->first();

                if ($cajaDiaria) {
                    foreach ($cajaDiaria->iniciales as $inicial) {
                        if (isset($this->cajaInicialPorDenominacion[$inicial->tes_denominaciones_monedas_id])) {
                            $denominacion = $this->denominaciones->find($inicial->tes_denominaciones_monedas_id);
                            $this->cajaInicialPorDenominacion[$inicial->tes_denominaciones_monedas_id]['monto'] = (float)$inicial->monto;
                            if ($denominacion && (float)$denominacion->denominacion > 0) {
                                $this->cajaInicialPorDenominacion[$inicial->tes_denominaciones_monedas_id]['cantidad'] = (float)$inicial->monto / (float)$denominacion->denominacion;
                            }
                        }
                    }
                    $this->calcularCajaInicialTotal();
                }
            } catch (\Exception $e) {
                // Log or handle error, for now, do nothing to prevent crash
            }
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
            $this->inicializarCajas(); // Reiniciar siempre

            $cajaDiaria = TesCajaDiarias::whereDate('fecha', $fecha)->with('iniciales')->first();

            if ($cajaDiaria) {
                $this->saldoInicial = floatval($cajaDiaria->monto_inicial);
                $this->cajaDiariaExists = true;

                foreach ($cajaDiaria->iniciales as $inicial) {
                    if (isset($this->cajaInicialPorDenominacion[$inicial->tes_denominaciones_monedas_id])) {
                        $denominacion = $this->denominaciones->find($inicial->tes_denominaciones_monedas_id);
                        $this->cajaInicialPorDenominacion[$inicial->tes_denominaciones_monedas_id]['monto'] = (float)$inicial->monto;
                        if ($denominacion && (float)$denominacion->denominacion > 0) {
                            $this->cajaInicialPorDenominacion[$inicial->tes_denominaciones_monedas_id]['cantidad'] = (float)$inicial->monto / (float)$denominacion->denominacion;
                        }
                    }
                }
                $this->calcularCajaInicialTotal();

            } else {
                $this->saldoInicial = 0;
                $this->cajaDiariaExists = false;
            }

            $this->totalCobros = Cobro::whereDate('fecha', $fecha)
                ->where('medio_pago', 'efectivo')
                ->sum('monto');

            $this->totalPagos = Pago::whereDate('fecha', $fecha)
                ->where('medio_pago', 'efectivo')
                ->sum('monto');

            $this->totalCobros = floatval($this->totalCobros);
            $this->totalPagos = floatval($this->totalPagos);

            $this->saldoActual = $this->saldoInicial + $this->totalCobros - $this->totalPagos;
        } catch (\Exception $e) {
            $this->totalCobros = 0;
            $this->totalPagos = 0;
            $this->saldoInicial = 0;
            $this->saldoActual = 0;
            $this->cajaDiariaExists = true;
        }
    }

    public function guardarCajaInicial()
    {
        if ($this->cajaInicialTotal <= 0) {
            $this->dispatchBrowserEvent('swal:error', ['title' => 'Error', 'text' => 'Debe ingresar valores para guardar la Caja Inicial.']);
            return;
        }

        try {
            $cajaDiaria = TesCajaDiarias::firstOrNew(['fecha' => $this->fecha]);

            if (!$cajaDiaria->exists) {
                $cajaDiaria->created_by = auth()->id();
            } else {
                $cajaDiaria->updated_by = auth()->id();
            }

            $cajaDiaria->monto_inicial = $this->cajaInicialTotal;
            $cajaDiaria->save();

            foreach ($this->cajaInicialPorDenominacion as $denominacionId => $datos) {
                if (is_numeric($datos['monto']) && $datos['monto'] > 0) {
                    $cajaInicial = TesCdInicial::firstOrNew([
                        'tes_caja_diarias_id' => $cajaDiaria->id,
                        'tes_denominaciones_monedas_id' => $denominacionId,
                    ]);

                    if (!$cajaInicial->exists) {
                        $cajaInicial->created_by = auth()->id();
                    } else {
                        $cajaInicial->updated_by = auth()->id();
                    }

                    $cajaInicial->monto = $datos['monto'];
                    $cajaInicial->save();
                }
            }

            $this->calcularSaldos();
            $this->emit('cajaInicialGuardada');
            $this->dispatchBrowserEvent('swal:success', ['text' => 'Caja Inicial guardada correctamente.']);

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal:error', ['title' => 'Error al guardar', 'text' => 'Error al guardar la caja inicial: ' . $e->getMessage()]);
        }
    }

    public function guardarCierreCaja()
    {
        if (!$this->cajaDiariaExists) {
            $this->dispatchBrowserEvent('swal:error', ['title' => 'Error', 'text' => 'No se ha abierto la caja para la fecha seleccionada.']);
            return;
        }

        if ($this->cierreTotal <= 0) {
            $this->dispatchBrowserEvent('swal:error', ['title' => 'Error', 'text' => 'Debe ingresar valores para guardar el Cierre de Caja.']);
            return;
        }

        try {
            $cajaDiaria = TesCajaDiarias::whereDate('fecha', $this->fecha)->first();

            if (!$cajaDiaria) {
                $this->dispatchBrowserEvent('swal:error', ['title' => 'Error', 'text' => 'No se encontró la caja diaria para la fecha seleccionada.']);
                return;
            }

            // Guardar el registro de cierre de caja
            $cierreCaja = TesCdCierre::firstOrNew([
                'tes_caja_diarias_id' => $cajaDiaria->id,
            ]);

            if (!$cierreCaja->exists) {
                $cierreCaja->created_by = auth()->id();
            } else {
                $cierreCaja->updated_by = auth()->id();
            }
            $cierreCaja->monto_cierre = $this->cierreTotal;
            $cierreCaja->save();

            // Guardar el detalle de las denominaciones del cierre
            foreach ($this->cierrePorDenominacion as $denominacionId => $datos) {
                if (is_numeric($datos['monto']) && $datos['monto'] > 0) {
                    $cierreDenominacion = TesCdCierreDenominacion::firstOrNew([
                        'tes_cd_cierres_id' => $cierreCaja->id,
                        'tes_denominaciones_monedas_id' => $denominacionId,
                    ]);

                    if (!$cierreDenominacion->exists) {
                        $cierreDenominacion->created_by = auth()->id();
                    } else {
                        $cierreDenominacion->updated_by = auth()->id();
                    }
                    $cierreDenominacion->monto = $datos['monto'];
                    $cierreDenominacion->save();
                }
            }

            $this->dispatchBrowserEvent('swal:success', ['text' => 'Cierre de Caja guardado correctamente.']);
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal:error', ['title' => 'Error al guardar', 'text' => 'Error al guardar el cierre de caja: ' . $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.tesoreria.caja-diaria.resumen');
    }
}
