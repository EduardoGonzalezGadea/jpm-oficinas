<?php

namespace App\Http\Livewire\Traits;

trait WithModal
{
    public $showModal = false;
    public $modalType = null;
    public $modalTitle = '';

    public function openModal($modalId)
    {
        // NO cerrar todos los modales - solo abrir el modal específico
        // Esto permite que modales hijos se abran sin cerrar el modal padre
        $this->dispatchBrowserEvent($modalId . '-show'); // Abrir el modal específico
        $this->dispatchBrowserEvent('hide-loader');
        $this->emit($modalId . '-show'); // Emitir evento Livewire
    }

    public function closeModal($modalId)
    {
        $this->dispatchBrowserEvent($modalId . '-hide');
        $this->dispatchBrowserEvent('hide-loader');
        $this->emit($modalId . '-hide'); // Emitir evento Livewire
    }
}
