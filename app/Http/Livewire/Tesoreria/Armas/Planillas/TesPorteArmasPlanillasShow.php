<?php

namespace App\Http\Livewire\Tesoreria\Armas\Planillas;

use App\Models\Tesoreria\TesPorteArmasPlanilla;
use Livewire\Component;

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
        return view('livewire.tesoreria.armas.planillas.tes-porte-armas-planillas-show')
            ->extends('layouts.app')
            ->section('content');
    }
}
