<?php

namespace App\Livewire\Tesoreria\DepositoVehiculos\Planillas;

use App\Models\Tesoreria\DepositoVehiculoPlanilla;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

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
        $this->dispatch('swal:confirm', title: '¿Anular Planilla?', text: 'Esta acción liberará todos los depósitos de la planilla. ¿Desea continuar?', method: 'anularPlanilla', id: $id, componentId: $this->getId());
    }

    public function anularPlanilla($id)
    {
        try {
            $planilla = DepositoVehiculoPlanilla::find($id);
            
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
        $planillas = DepositoVehiculoPlanilla::withCount('depositos')
            ->when($this->search, function ($query) {
                $query->where('numero', 'like', '%' . $this->search . '%');
            })
            ->orderBy('fecha', 'desc')
            ->orderBy('numero', 'desc')
            ->paginate(15);

        return view('livewire.tesoreria.deposito-vehiculos.planillas.index', compact('planillas'));
    }
}
