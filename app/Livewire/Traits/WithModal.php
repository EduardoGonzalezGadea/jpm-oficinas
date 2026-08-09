<?php

namespace App\Livewire\Traits;

trait WithModal
{
    public $showModal = false;
    public $modalType = null;
    public $modalTitle = '';

    public function openModal($modalId)
    {
        // NO cerrar todos los modales - solo abrir el modal específico
        // Esto permite que modales hijos se abran sin cerrar el modal padre
        $this->dispatch($modalId . '-show'); // Abrir el modal específico
        $this->dispatch('hide-loader');
        $this->dispatch($modalId . '-show'); // Emitir evento Livewire
    }

    public function closeModal($modalId)
    {
        $this->dispatch($modalId . '-hide');
        $this->dispatch('hide-loader');
        $this->dispatch($modalId . '-hide'); // Emitir evento Livewire
    }
}
