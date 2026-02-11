<?php

namespace App\Http\Livewire;

use App\Models\TesCfePendiente;
use Livewire\Component;
use Livewire\WithPagination;

class CfePendientesIndex extends Component
{
    use WithPagination;

    public $cfePendienteToConfirm;
    public $cfePendienteId;
    public $datosModificados = [];

    public function mount()
    {
        $this->cfePendientes = TesCfePendiente::where('estado', 'pendiente')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function verDetalles($id)
    {
        $this->cfePendienteId = $id;
        $this->cfePendienteToConfirm = TesCfePendiente::find($id);
        $this->dispatchBrowserEvent('show-modal', ['id' => 'cfePendienteModal']);
    }

    public function confirmarCfe()
    {
        $cfe = TesCfePendiente::find($this->cfePendienteId);

        // TODO: Implementar lógica de confirmación
        // Por ahora, solo cambiamos el estado
        $cfe->estado = 'confirmado';
        $cfe->procesado_por = auth()->id();
        $cfe->procesado_at = now();
        $cfe->save();

        // TODO: Guardar datos modificados si los hay
        // $cfe->datos_extraidos = $this->datosModificados;

        $this->cfePendienteToConfirm = null;
    }

    public function rechazarCfe($motivo = null)
    {
        $cfe = TesCfePendiente::find($this->cfePendienteId);
        $cfe->estado = 'rechazado';
        $cfe->motivo_rechazo = $motivo;
        $cfe->save();

        $this->cfePendienteToConfirm = null;
    }

    public function getListeners()
    {
        return [
            'show-modal' => 'showModal',
        ];
    }
}
