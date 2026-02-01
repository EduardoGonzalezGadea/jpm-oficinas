<?php

namespace App\Http\Livewire\Tesoreria\Armas\Planillas;

use App\Models\Tesoreria\TesPorteArmasPlanilla;
use Livewire\Component;
use Livewire\WithPagination;

class TesPorteArmasPlanillasIndex extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';

    protected $listeners = ['anularPlanilla'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function confirmAnular($id)
    {
        $this->dispatchBrowserEvent('swal:confirm', [
            'title' => '¿Anular Planilla?',
            'text' => 'Esta acción liberará todos los registros de la planilla. ¿Desea continuar?',
            'method' => 'anularPlanilla',
            'id' => $id,
            'componentId' => $this->id,
        ]);
    }

    public function anularPlanilla($id)
    {
        try {
            $planilla = TesPorteArmasPlanilla::find($id);

            if (!$planilla) {
                $this->emit('error', 'Planilla no encontrada.');
                return;
            }

            if ($planilla->isAnulada()) {
                $this->emit('error', 'La planilla ya está anulada.');
                return;
            }

            $planilla->anular();
            \Illuminate\Support\Facades\Cache::flush();

            session()->flash('message', 'Planilla anulada correctamente.');
        } catch (\Exception $e) {
            $this->emit('error', 'Error al anular la planilla: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $planillas = TesPorteArmasPlanilla::withCount('porteArmas')
            ->when($this->search, function ($query) {
                $query->where('numero', 'like', '%' . $this->search . '%');
            })
            ->orderBy('fecha', 'desc')
            ->orderBy('numero', 'desc')
            ->paginate(15);

        return view('livewire.tesoreria.armas.planillas.tes-porte-armas-planillas-index', compact('planillas'))
            ->extends('layouts.app')
            ->section('content');
    }
}
