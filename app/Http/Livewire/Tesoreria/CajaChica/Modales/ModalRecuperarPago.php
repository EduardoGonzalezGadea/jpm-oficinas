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
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'text' => 'Pago no encontrado o error al cargar datos.', 'toast' => true, 'position' => 'top-end', 'timer' => 5000]);
        }
    }

    public function saveRecuperarPago()
    {
        $request = new \App\Http\Requests\Tesoreria\CajaChica\RecuperarPagoRequest();
        $rules = $request->rules();
        $rules['recuperarPagoData.numero_ingreso_bse'] = 'nullable|string|max:255';
        $rules['recuperarPagoData.fecha_ingreso_bse'] = 'nullable|date';

        $pago = Pago::find($this->recuperarPagoData['relPago']);
        $maxRecuperable = 0;
        if ($pago) {
            $rendido = $pago->rendidoPagos;
            $recuperado = $pago->recuperadoPagos ?: 0;
            $maxRecuperable = is_null($rendido)
                ? max(0, round($pago->montoPagos - $recuperado, 2))
                : max(0, round($rendido - $recuperado, 2));
        }
        $rules['recuperarPagoData.monto_recuperado'] = "required|numeric|min:0.01|max:{$maxRecuperable}";

        $messages = $request->messages() ?? [];
        $messages['recuperarPagoData.monto_recuperado.max'] = 'El monto a recuperar no puede ser mayor que el saldo disponible ($' . number_format($maxRecuperable, 2, ',', '.') . ').';

        $this->validate($rules, $messages);

        try {
            $service = app(\App\Services\Tesoreria\CajaChicaService::class);
            $service->guardarRecuperacionPago($this->recuperarPagoData);

            $this->cerrarModal();
            session()->flash('message', 'Recuperación de pago guardada exitosamente.');
            return redirect()->route('tesoreria.caja-chica.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al guardar la recuperación del pago: ' . $e->getMessage());
            return redirect()->route('tesoreria.caja-chica.index');
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
