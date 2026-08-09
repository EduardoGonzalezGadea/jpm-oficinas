<?php

namespace App\Livewire\Tesoreria\Prendas\Planillas;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Tesoreria\PrendaPlanilla;

/**
 * Componente Livewire: Índice de Planillas de Prendas
 *
 * Muestra la lista de planillas con paginación y anulación.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';

    protected $listeners = ['delete'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function confirmAnular($id)
    {
        $this->dispatch('swal:confirm', title: '¿Anular Planilla?', text: 'Esta acción liberará todas las prendas de la planilla. ¿Desea continuar?', method: 'anularPlanilla', id: $id, componentId: $this->getId());
    }

    public function anularPlanilla($id)
    {
        try {
            $planilla = PrendaPlanilla::find($id);

            if (!$planilla) {
                $this->dispatch('swal:error', text: 'Planilla no encontrada.');
                return;
            }

            if ($planilla->isAnulada()) {
                $this->dispatch('swal:error', text: 'La planilla ya está anulada.');
                return;
            }

            $planilla->anular();

            $this->dispatch('swal:success', text: 'Planilla anulada correctamente.');
        } catch (\Exception $e) {
            $this->dispatch('swal:error', text: 'Error al anular la planilla: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $planillas = PrendaPlanilla::withCount('prendas')
            ->when($this->search, function ($query) {
                $query->where('numero', 'like', '%' . $this->search . '%');
            })
            ->orderBy('fecha', 'desc')
            ->orderBy('numero', 'desc')
            ->paginate(15);

        return view('livewire.tesoreria.prendas.planillas.index', compact('planillas'));
    }
}
