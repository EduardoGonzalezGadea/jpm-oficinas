<?php

namespace App\Http\Livewire\Tesoreria\CajaChica\Pendiente;

use Livewire\Component;
use App\Models\Tesoreria\Pendiente;

class EditarPendiente extends Component
{
    public $idPendiente;

    protected $listeners = ['movimientoActualizado' => 'actualizarVista', 'pendienteActualizado' => 'actualizarVista'];

    public function actualizarVista()
    {
        $this->render();
    }

    /**
     * Emite evento para abrir el modal de edición.
     */
    public function abrirModalEditar()
    {
        $this->emit('openModal');
    }

    /**
     * El método Mount.
     */
    public function mount($id)
    {
        $this->idPendiente = $id;
    }

    /**
     * El método Render.
     */
    public function render()
    {
        $pendiente = Pendiente::with('cajaChica', 'dependencia', 'movimientos')->findOrFail($this->idPendiente);

        return view('livewire.tesoreria.caja-chica.pendiente.editar-pendiente', [
            'pendiente' => $pendiente
        ]);
    }
}
