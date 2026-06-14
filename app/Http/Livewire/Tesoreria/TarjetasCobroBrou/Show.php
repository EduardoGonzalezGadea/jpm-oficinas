<?php

namespace App\Http\Livewire\Tesoreria\TarjetasCobroBrou;

use App\Models\Tesoreria\TarjetaCobroBrou;
use Livewire\Component;

class Show extends Component
{
    public $tarjeta;

    protected $listeners = ['showDetailModal'];

    public function showDetailModal($id)
    {
        $this->tarjeta = TarjetaCobroBrou::with(['receptor', 'entregador', 'devolucionUser', 'createdBy', 'updatedBy', 'deletedBy'])->find($id);
        $this->dispatchBrowserEvent('show-modal', ['id' => 'showModal']);
    }

    public function render()
    {
        return view('livewire.tesoreria.tarjetas-cobro-brou.show');
    }
}