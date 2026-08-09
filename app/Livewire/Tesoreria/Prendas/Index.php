<?php

namespace App\Livewire\Tesoreria\Prendas;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Tesoreria\Prenda;

#[Layout('layouts.app')]
/**
 * Componente Livewire: Índice de Prendas
 *
 * Este componente muestra la lista principal de prendas registradas en el sistema.
 * Proporciona funcionalidades de:
 * - Búsqueda por múltiples campos
 * - Filtrado por año
 * - Selección múltiple de prendas
 * - Creación de planillas desde prendas seleccionadas
 * - Eliminación de prendas
 *
 * @package App\Http\Livewire\Tesoreria\Prendas
 */
class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $selectedYear;
    public $years = [];
    public $selectedPrendas = [];
    public $selectAll = false;
    public $autoEditPrendaId = null;

    protected $listeners = [
        'pg:eventRefresh-default' => 'refreshData',
        'delete',
        'showCreateModal' => 'forwardShowCreateModal',
        'showDetailModal' => 'forwardShowDetailModal',
        'showEditModal' => 'forwardShowEditModal',
    ];

    public function mount()
    {
        $years = Prenda::selectRaw('YEAR(recibo_fecha) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        $currentYear = (int) date('Y');
        if (!in_array($currentYear, $years)) {
            $years[] = $currentYear;
        }

        rsort($years);
        $this->years = $years;
        $this->selectedYear = $currentYear;

        // Si venimos de cargar un CFE, capturar el ID para abrir el modal de edición
        if (session()->has('edit_prenda_id')) {
            $this->autoEditPrendaId = session('edit_prenda_id');
        }
    }

    public function confirmDelete($id)
    {
        $this->dispatch('swal:confirm', title: '¿Eliminar Prenda?', text: 'Esta acción no se puede revertir.', method: 'delete', id: $id, componentId: $this->getId());
    }

    public function delete($id)
    {
        $prenda = Prenda::find($id);

        if (!$prenda) {
            $this->dispatch('swal:toast-error', text: 'La prenda no existe.');
            return;
        }

        if ($prenda->planilla_id) {
            $this->dispatch('swal:toast-error', text: 'No se puede eliminar una prenda que pertenece a una planilla.');
            return;
        }

        $prenda->delete();
        $this->dispatch('swal:success', text: 'Prenda eliminada correctamente.');
        $this->refreshData();
    }

    public function refreshData()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedYear()
    {
        $this->resetPage();
    }

    public function clearSearch()
    {
        $this->search = '';
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedPrendas = Prenda::whereNull('planilla_id')
                ->whereYear('recibo_fecha', $this->selectedYear)
                ->pluck('id')
                ->toArray();
        } else {
            $this->selectedPrendas = [];
        }
    }

    public function createPlanilla()
    {
        if (empty($this->selectedPrendas)) {
            $this->dispatch('swal:error', title: 'Error', text: 'Debe seleccionar al menos una prenda.');
            return;
        }

        try {
            $planilla = \App\Models\Tesoreria\PrendaPlanilla::create([
                'fecha' => now(),
            ]);

            Prenda::whereIn('id', $this->selectedPrendas)
                ->update(['planilla_id' => $planilla->id]);

            $this->selectedPrendas = [];
            $this->selectAll = false;

            $this->dispatch('swal:success', title: 'Éxito', text: 'Planilla creada correctamente. Número: ' . $planilla->numero);

            $this->refreshData();
        } catch (\Exception $e) {
            $this->dispatch('swal:error', title: 'Error', text: 'Error al crear la planilla: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $prendas = Prenda::with('medioPago')
            ->whereYear('recibo_fecha', $this->selectedYear)
            ->where(function ($query) {
                $query->where('titular_nombre', 'like', '%' . $this->search . '%')
                    ->orWhere('titular_cedula', 'like', '%' . $this->search . '%')
                    ->orWhere('recibo_numero', 'like', '%' . $this->search . '%')
                    ->orWhere('orden_cobro', 'like', '%' . $this->search . '%')
                    ->orWhere('transferencia', 'like', '%' . $this->search . '%');
            })
            ->orderBy('recibo_fecha', 'desc')
            ->orderBy('recibo_numero', 'desc')
            ->paginate(10);

        return view('livewire.tesoreria.prendas.index', compact('prendas'));
    }

    public function forwardShowCreateModal(): void
    {
        $this->dispatch('showCreateModal')->to('tesoreria.prendas.create');
    }

    public function forwardShowDetailModal($id): void
    {
        $this->dispatch('showDetailModal', id: (int) $id)->to('tesoreria.prendas.show');
    }

    public function forwardShowEditModal($id): void
    {
        $this->dispatch('showEditModal', id: (int) $id)->to('tesoreria.prendas.edit');
    }
}
