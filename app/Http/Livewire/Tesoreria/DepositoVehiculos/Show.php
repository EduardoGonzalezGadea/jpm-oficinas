<?php

namespace App\Http\Livewire\Tesoreria\DepositoVehiculos;

use App\Models\Tesoreria\DepositoVehiculo;
use Livewire\Component;

class Show extends Component
{
    public $deposito;

    protected $listeners = ['showDetailModal'];

    public function showDetailModal($id)
    {
        $this->deposito = DepositoVehiculo::with(['medioPago', 'createdBy', 'updatedBy', 'deletedBy'])->find($id);
        $this->dispatchBrowserEvent('show-modal', ['id' => 'showModal']);
    }

    public function render()
    {
        return view('livewire.tesoreria.deposito-vehiculos.show');
    }
}
