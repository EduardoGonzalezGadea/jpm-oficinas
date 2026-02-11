<?php

namespace App\Http\Livewire\Tesoreria\Prendas\Planillas;

use Livewire\Component;
use App\Models\Tesoreria\PrendaPlanilla;

/**
 * Componente Livewire: Ver Detalle de Planilla
 *
 * Muestra los detalles de una planilla específica.
 */
class Show extends Component
{
    public $planilla;
    public $planillaId;

    public function mount($id)
    {
        $this->planillaId = $id;
        $this->planilla = PrendaPlanilla::with([
            'prendas.medioPago',
            'createdBy',
            'anuladaPor'
        ])
            ->findOrFail($id);
    }

    public function generarPDF()
    {
        return redirect()->route('tesoreria.prendas.planillas.pdf', $this->planillaId);
    }

    public function render()
    {
        return view('livewire.tesoreria.prendas.planillas.show')
            ->extends('layouts.app')
            ->section('content');
    }
}
