<?php

namespace App\Livewire;

use App\Models\TesCfePendiente;
use App\Services\Tesoreria\CfeConfirmationService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class CfePendientesIndex extends Component
{
    use WithPagination;

    public $cfePendienteToConfirm;
    public $cfePendienteId;
    public $datosModificados = [];
    public $motivoRechazo = '';
    public $mostrarModalRechazo = false;

    protected CfeConfirmationService $confirmationService;

    public function boot(CfeConfirmationService $confirmationService): void
    {
        $this->confirmationService = $confirmationService;
    }

    public function mount(): void
    {
        $this->cfePendientes = TesCfePendiente::where('estado', 'pendiente')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function verDetalles($id): void
    {
        $this->cfePendienteId = $id;
        $this->cfePendienteToConfirm = TesCfePendiente::find($id);
        $this->motivoRechazo = '';
        $this->mostrarModalRechazo = false;
        $this->dispatch('show-modal', id: 'cfePendienteModal');
    }

    public function confirmarCfe(): void
    {
        if (!$this->cfePendienteId) {
            return;
        }

        $pendiente = TesCfePendiente::find($this->cfePendienteId);

        if (!$pendiente || !in_array($pendiente->estado, ['pendiente', 'en_revision'])) {
            $this->dispatch('swal:toast-error', text: 'El CFE no se encuentra en estado confirmable.');
            return;
        }

        try {
            $cfe = $this->confirmationService->confirmar($pendiente, $this->datosModificados);

            $this->dispatch('swal:modal', type: 'success', title: 'CFE Confirmado', text: "El CFE {$cfe->documento_tipo} {$cfe->documento_serie}-{$cfe->documento_numero} ha sido confirmado y creado correctamente.");

            $this->cfePendienteToConfirm = null;
            $this->cfePendienteId = null;
            $this->datosModificados = [];
            $this->dispatch('hide-modal', id: 'cfePendienteModal');
            $this->render();
        } catch (\Exception $e) {
            $this->dispatch('swal:modal', type: 'error', title: 'Error al confirmar', text: 'Hubo un problema confirmando el CFE: ' . $e->getMessage());
        }
    }

    public function abrirModalRechazo(): void
    {
        $this->mostrarModalRechazo = true;
        $this->dispatch('show-modal', id: 'rechazoModal');
    }

    public function rechazarCfe(): void
    {
        if (!$this->cfePendienteId) {
            return;
        }

        $pendiente = TesCfePendiente::find($this->cfePendienteId);

        if (!$pendiente || !in_array($pendiente->estado, ['pendiente', 'en_revision'])) {
            $this->dispatch('swal:toast-error', text: 'El CFE no se encuentra en estado rechazable.');
            return;
        }

        if (empty(trim($this->motivoRechazo))) {
            $this->dispatch('swal:toast-error', text: 'Debe ingresar un motivo de rechazo.');
            return;
        }

        try {
            $this->confirmationService->rechazar($pendiente, $this->motivoRechazo);

            $this->dispatch('swal:modal', type: 'success', title: 'CFE Rechazado', text: 'El CFE ha sido rechazado correctamente.');

            $this->cfePendienteToConfirm = null;
            $this->cfePendienteId = null;
            $this->motivoRechazo = '';
            $this->mostrarModalRechazo = false;
            $this->dispatch('hide-modal', id: 'rechazoModal');
            $this->render();
        } catch (\Exception $e) {
            $this->dispatch('swal:modal', type: 'error', title: 'Error al rechazar', text: 'Hubo un problema rechazando el CFE: ' . $e->getMessage());
        }
    }

    public function marcarEnRevision(): void
    {
        if (!$this->cfePendienteId) {
            return;
        }

        $pendiente = TesCfePendiente::find($this->cfePendienteId);

        if ($pendiente && $pendiente->estado === 'pendiente') {
            $this->confirmationService->marcarEnRevision($pendiente);
            $this->dispatch('swal:toast-success', text: 'CFE marcado como en revisión.');
            $this->render();
        }
    }

    public function getListeners(): array
    {
        return [
            'cfe-confirmado' => 'render',
            'review-cfe' => 'forwardReviewCfe',
        ];
    }

    public function forwardReviewCfe(int $id): void
    {
        $this->dispatch('review-cfe', id: $id)->to('tesoreria.cfe-pendientes.review');
    }

    public function render()
    {
        $this->cfePendientes = TesCfePendiente::where('estado', 'pendiente')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.cfe-pendientes-index');
    }
}