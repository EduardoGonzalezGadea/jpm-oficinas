<?php

namespace App\Http\Livewire\Tesoreria\CajaChica\Modales;

use Livewire\Component;
use App\Models\Tesoreria\Pendiente;
use Carbon\Carbon;

class ModalRecuperarRendido extends Component
{
    public $showModal = false;

    public $recuperarRendidoData = [
        'relPendiente' => null,
        'fecha' => '',
        'documentos' => '',
        'monto_rendido' => 0,
        'monto_reintegrado' => 0,
        'monto_recuperado' => 0,
    ];
    public $selectedPendienteId;
    public $fechaHastaGlobal;

    protected $listeners = ['abrirModalRecuperarRendido' => 'abrirModal'];

    protected function rules()
    {
        return (new \App\Http\Requests\Tesoreria\CajaChica\RecuperarRendidoRequest())->rules();
    }

    protected function messages()
    {
        return (new \App\Http\Requests\Tesoreria\CajaChica\RecuperarRendidoRequest())->messages();
    }

    public function abrirModal($pendienteId, $fechaHastaGlobal)
    {
        $this->resetErrorBag();
        $this->selectedPendienteId = $pendienteId;
        $this->fechaHastaGlobal = $fechaHastaGlobal;
        $pendiente = Pendiente::find($pendienteId);

        if (!$pendiente) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'text' => 'Pendiente no encontrado.', 'toast' => true, 'position' => 'top-end', 'timer' => 5000]);
            return;
        }

        $fechaHastaStr = Carbon::createFromFormat('Y-m-d', $this->fechaHastaGlobal)->endOfDay()->toDateTimeString();
        $service = app(\App\Services\Tesoreria\CajaChicaService::class);
        $montoRecuperable = $service->calcularMontoRecuperableRendido($pendienteId, $fechaHastaStr);

        $this->recuperarRendidoData = [
            'relPendiente' => $pendienteId,
            'fecha' => now()->format('Y-m-d'),
            'documentos' => '',
            'monto_rendido' => 0,
            'monto_reintegrado' => 0,
            'monto_recuperado' => number_format((float)$montoRecuperable, 2, '.', ''),
        ];

        $this->showModal = true;
    }

    public function saveRecuperarRendido()
    {
        $this->validate();

        try {
            $fechaHastaStr = Carbon::createFromFormat('Y-m-d', $this->fechaHastaGlobal)->endOfDay()->toDateTimeString();
            $service = app(\App\Services\Tesoreria\CajaChicaService::class);
            $service->guardarRecuperacionRendido($this->recuperarRendidoData, $fechaHastaStr);

            $this->cerrarModal();
            session()->flash('message', 'Dinero rendido recuperado exitosamente.');
            return redirect()->route('tesoreria.caja-chica.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al guardar la recuperación del dinero rendido: ' . $e->getMessage());
            return redirect()->route('tesoreria.caja-chica.index');
        }
    }

    public function cerrarModal()
    {
        $this->showModal = false;
        $this->reset(['recuperarRendidoData', 'selectedPendienteId', 'fechaHastaGlobal']);
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.tesoreria.caja-chica.modales.modal-recuperar-rendido');
    }
}
