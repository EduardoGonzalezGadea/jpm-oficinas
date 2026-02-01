<?php

namespace App\Http\Livewire\Tesoreria\Armas\Planillas;

use App\Models\Tesoreria\TesTenenciaArmasPlanilla;
use Livewire\Component;

class TesTenenciaArmasPlanillasShow extends Component
{
    public $planilla;
    public $planillaId;

    public function mount($id)
    {
        $this->planillaId = $id;
        $this->planilla = TesTenenciaArmasPlanilla::with(['tenenciaArmas', 'createdBy', 'anuladaPor'])
            ->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.tesoreria.armas.planillas.tes-tenencia-armas-planillas-show')
            ->extends('layouts.app')
            ->section('content');
    }
}
