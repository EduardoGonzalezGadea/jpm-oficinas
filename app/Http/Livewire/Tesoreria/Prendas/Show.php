<?php

namespace App\Http\Livewire\Tesoreria\Prendas;

use App\Models\Tesoreria\Prenda;
use Livewire\Component;

class Show extends Component
{
    public $prenda;

    protected $listeners = ['showDetailModal'];

    public function showDetailModal($id)
    {
        $this->prenda = Prenda::with(['medioPago', 'createdBy', 'updatedBy', 'deletedBy'])->find($id);
        $this->dispatchBrowserEvent('show-modal', ['id' => 'showModal']);
    }

    public function render()
    {
        return view('livewire.tesoreria.prendas.show');
    }
}
