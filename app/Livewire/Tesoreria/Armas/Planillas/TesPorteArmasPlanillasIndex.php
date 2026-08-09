<?php

namespace App\Livewire\Tesoreria\Armas\Planillas;

use App\Models\Tesoreria\TesPorteArmasPlanilla;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
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
        $this->dispatch('swal:confirm', title: '¿Anular Planilla?', text: 'Esta acción liberará todos los registros de la planilla. ¿Desea continuar?', method: 'anularPlanilla', id: $id, componentId: $this->getId());
    }

    public function anularPlanilla($id)
    {
        try {
            $planilla = TesPorteArmasPlanilla::find($id);

            if (!$planilla) {
                $this->dispatch('swal:toast-error', text: 'Planilla no encontrada.');
                return;
            }

            if ($planilla->isAnulada()) {
                $this->dispatch('swal:toast-error', text: 'La planilla ya está anulada.');
                return;
            }

            $planilla->anular();
            \Illuminate\Support\Facades\Cache::flush();

            $this->dispatch('swal:success', text: 'Planilla anulada correctamente.');
        } catch (\Exception $e) {
            $this->dispatch('swal:toast-error', text: 'Error al anular la planilla: ' . $e->getMessage());
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

        return view('livewire.tesoreria.armas.planillas.tes-porte-armas-planillas-index', compact('planillas'));
    }
}
