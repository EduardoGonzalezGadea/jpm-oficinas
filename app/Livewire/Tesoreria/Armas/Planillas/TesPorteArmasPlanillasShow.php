<?php

namespace App\Livewire\Tesoreria\Armas\Planillas;

use App\Models\Tesoreria\TesPorteArmasPlanilla;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class TesPorteArmasPlanillasShow extends Component
{
    public $planilla;
    public $planillaId;

    public function mount($id)
    {
        $this->planillaId = $id;
        $this->planilla = TesPorteArmasPlanilla::with(['porteArmas', 'createdBy', 'anuladaPor'])
            ->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.tesoreria.armas.planillas.tes-porte-armas-planillas-show');
    }
}
