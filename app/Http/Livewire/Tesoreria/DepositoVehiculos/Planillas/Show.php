<?php

namespace App\Http\Livewire\Tesoreria\DepositoVehiculos\Planillas;

use App\Models\Tesoreria\DepositoVehiculoPlanilla;
use Livewire\Component;

class Show extends Component
{
    public $planillaId;
    public $planilla;

    public function mount($id)
    {
        $this->planillaId = $id;
        $this->planilla = DepositoVehiculoPlanilla::with(['depositos.medioPago', 'anuladaPor'])
            ->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.tesoreria.deposito-vehiculos.planillas.show');
    }
}
