<?php

namespace App\Http\Livewire\Tesoreria\CfePendientes;

use App\Models\TesCfePendiente;
use App\Services\Tesoreria\CfeConfirmationService;
use Livewire\Component;

class Review extends Component
{
    public $pendienteId;
    public $pendiente;
    public $datosEditados = [];
    public $mostrarModal = false;

    protected CfeConfirmationService $confirmationService;

    public function boot(CfeConfirmationService $confirmationService): void
    {
        $this->confirmationService = $confirmationService;
    }

    protected $listeners = [
        'review-cfe' => 'abrirModal',
    ];

    public function abrirModal(int $id): void
    {
        $this->pendienteId = $id;
        $this->pendiente = TesCfePendiente::find($id);

        if (!$this->pendiente) {
            $this->dispatchBrowserEvent('swal:toast-error', [
                'text' => 'CFE pendiente no encontrado.',
            ]);
            return;
        }

        $this->datosEditados = $this->pendiente->datos_extraidos ?? [];
        $this->mostrarModal = true;
        $this->dispatchBrowserEvent('show-modal', ['id' => 'reviewModal']);
    }

    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
        $this->pendienteId = null;
        $this->pendiente = null;
        $this->datosEditados = [];
        $this->dispatchBrowserEvent('cerrar-modal', ['id' => 'reviewModal']);
    }

    public function confirmar(): void
    {
        if (!$this->pendiente) {
            return;
        }

        try {
            $cfe = $this->confirmationService->confirmar($this->pendiente, $this->datosEditados);

            $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'success',
                'title' => 'CFE Confirmado',
                'text' => "El CFE {$cfe->documento_tipo} {$cfe->documento_serie}-{$cfe->documento_numero} ha sido creado correctamente.",
            ]);

            $this->cerrarModal();
            $this->emit('cfe-confirmado');
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'error',
                'title' => 'Error al confirmar',
                'text' => 'Hubo un problema: ' . $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.tesoreria.cfe-pendientes.review');
    }
}