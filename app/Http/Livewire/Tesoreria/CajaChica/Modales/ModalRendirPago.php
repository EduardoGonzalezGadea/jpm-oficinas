<?php

namespace App\Http\Livewire\Tesoreria\CajaChica\Modales;

use Livewire\Component;
use App\Models\Tesoreria\Pago;

class ModalRendirPago extends Component
{
    public $showModal = false;

    public $rendirPagoData = [
        'relPago' => null,
        'fecha_rendicion' => '',
        'monto_rendido' => 0,
        'monto_reintegrado' => 0,
        'monto_extra' => 0,
        'monto_original' => 0,
        'saldo_actual' => 0,
        'monto_recuperado_momento' => 0,
        'ingreso_reintegro' => '',
    ];

    protected $listeners = ['abrirModalRendirPago' => 'abrirModal'];

    protected function rules()
    {
        return [
            'rendirPagoData.relPago' => 'required|exists:tes_cch_pagos,idPagos',
            'rendirPagoData.fecha_rendicion' => 'required|date',
            'rendirPagoData.monto_rendido' => 'required|numeric|min:0',
            'rendirPagoData.monto_reintegrado' => 'required|numeric|min:0',
        ];
    }

    protected function messages()
    {
        return [
            'rendirPagoData.fecha_rendicion.required' => 'La fecha de rendición es obligatoria.',
            'rendirPagoData.monto_rendido.required' => 'El monto rendido es obligatorio.',
            'rendirPagoData.monto_rendido.numeric' => 'El monto rendido debe ser un número.',
            'rendirPagoData.monto_reintegrado.required' => 'El monto reintegrado es obligatorio.',
            'rendirPagoData.monto_reintegrado.numeric' => 'El monto reintegrado debe ser un número.',
        ];
    }

    public function abrirModal($pagoId)
    {
        $this->resetErrorBag();

        try {
            $pago = Pago::findOrFail($pagoId);

            $this->rendirPagoData = [
                'relPago' => $pagoId,
                'fecha_rendicion' => $pago->fechaRendicionPagos
                    ? $pago->fechaRendicionPagos->format('Y-m-d')
                    : now()->format('Y-m-d'),
                'monto_rendido' => $pago->rendidoPagos ?? 0,
                'monto_reintegrado' => $pago->reintegradoPagos ?? 0,
                'monto_extra' => max(0, round(($pago->rendidoPagos ?? 0) - $pago->montoPagos, 2)),
                'monto_original' => $pago->montoPagos,
                'saldo_actual' => $pago->saldo_pagos,
                'monto_recuperado_momento' => $pago->recuperadoPagos ?: 0,
                'ingreso_reintegro' => $pago->ingresoReintegroPagos ?? '',
            ];

            $this->showModal = true;
        } catch (\Exception $e) {
            $this->emit('mostrarAlerta', ['icon' => 'error', 'text' => 'Pago no encontrado o error al cargar datos.', 'toast' => true, 'position' => 'top-end', 'timer' => 5000]);
        }
    }

    /**
     * Auto-cálculo cuando el usuario cambia el monto rendido.
     */
    public function updatedRendirPagoDataMontoRendido($value)
    {
        $rendido = floatval($value);
        $otorgado = floatval($this->rendirPagoData['monto_original']);
        $recuperado = floatval($this->rendirPagoData['monto_recuperado_momento']);

        if ($rendido <= $otorgado) {
            $this->rendirPagoData['monto_reintegrado'] = round($otorgado - $rendido, 2);
            $this->rendirPagoData['monto_extra'] = 0;
        } else {
            $this->rendirPagoData['monto_reintegrado'] = 0;
            $this->rendirPagoData['monto_extra'] = round($rendido - $otorgado, 2);
        }

        // El saldo se calcula como rendido - recuperado (mínimo 0)
        // Si no hubiera rendido, sería monto_original - recuperado, pero al estar en este modal
        // asumimos que el valor ingresado es la rendición que se va a aplicar.
        $this->rendirPagoData['saldo_actual'] = max(0, round($rendido - $recuperado, 2));
    }

    public function saveRendicionPago()
    {
        $this->validate();

        try {
            $service = app(\App\Services\Tesoreria\CajaChicaService::class);
            $service->guardarRendicionPago($this->rendirPagoData);

            $this->cerrarModal();
            $this->emit('datosRecargados');
            $this->emit('fondoActualizado');
            $this->emit('mostrarAlerta', ['icon' => 'success', 'text' => 'Rendición guardada exitosamente.', 'toast' => true, 'position' => 'top-end', 'timer' => 5000]);
        } catch (\Exception $e) {
            $this->emit('mostrarAlerta', ['icon' => 'error', 'text' => 'Error al guardar la rendición: ' . $e->getMessage(), 'toast' => true, 'position' => 'top-end', 'timer' => 5000]);
        }
    }

    public function cerrarModal()
    {
        $this->showModal = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.tesoreria.caja-chica.modales.modal-rendir-pago');
    }
}
