<?php

namespace App\Http\Livewire\Tesoreria\CajaChica\Modales;

use Livewire\Component;
use App\Models\Tesoreria\Pago;

class ModalRecuperarPago extends Component
{
    public $showModal = false;

    public $recuperarPagoData = [
        'relPago' => null,
        'fecha' => '',
        'numero_ingreso' => '',
        'numero_ingreso_bse' => '',
        'fecha_ingreso_bse' => '',
        'monto_recuperado' => 0,
        'es_banco_bse' => false,
    ];

    protected $listeners = ['abrirModalRecuperarPago' => 'abrirModal'];

    protected function rules()
    {
        return (new \App\Http\Requests\Tesoreria\CajaChica\RecuperarPagoRequest())->rules();
    }

    protected function messages()
    {
        return (new \App\Http\Requests\Tesoreria\CajaChica\RecuperarPagoRequest())->messages() ?? [];
    }

    public function abrirModal($pagoId)
    {
        $this->resetErrorBag();
        
        try {
            $pago = Pago::with('acreedor')->findOrFail($pagoId);

            $this->recuperarPagoData = [
                'relPago' => $pagoId,
                'fecha' => now()->format('Y-m-d'),
                'numero_ingreso' => '',
                'numero_ingreso_bse' => '',
                'fecha_ingreso_bse' => '',
                'monto_recuperado' => number_format((float)((is_null($pago->rendidoPagos) && is_null($pago->reintegradoPagos) ? $pago->montoPagos : $pago->rendidoPagos) - $pago->recuperadoPagos), 2, '.', ''),
                'es_banco_bse' => ($pago->acreedor->acreedor ?? '') === 'Banco de Seguros del Estado',
            ];

            $this->showModal = true;
        } catch (\Exception $e) {
            $this->emit('mostrarAlerta', ['icon' => 'error', 'text' => 'Pago no encontrado o error al cargar datos.', 'toast' => true, 'position' => 'top-end', 'timer' => 5000]);
        }
    }

    public function saveRecuperarPago()
    {
        $request = new \App\Http\Requests\Tesoreria\CajaChica\RecuperarPagoRequest();
        $rules = $request->rules();
        $rules['recuperarPagoData.numero_ingreso_bse'] = 'nullable|string|max:255';
        $rules['recuperarPagoData.fecha_ingreso_bse'] = 'nullable|date';

        $this->validate($rules, $request->messages() ?? []);

        try {
            $service = app(\App\Services\Tesoreria\CajaChicaService::class);
            $service->guardarRecuperacionPago($this->recuperarPagoData);

            $this->cerrarModal();
            $this->emit('fondoActualizado'); 
            $this->emit('mostrarAlerta', ['icon' => 'success', 'text' => 'Recuperación de pago guardada exitosamente.', 'toast' => true, 'position' => 'top-end', 'timer' => 5000]);
        } catch (\Exception $e) {
            $this->emit('mostrarAlerta', ['icon' => 'error', 'text' => 'Error al guardar la recuperación del pago: ' . $e->getMessage(), 'toast' => true, 'position' => 'top-end', 'timer' => 5000]);
        }
    }

    public function cerrarModal()
    {
        $this->showModal = false;
        $this->reset('recuperarPagoData');
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.tesoreria.caja-chica.modales.modal-recuperar-pago');
    }
}
