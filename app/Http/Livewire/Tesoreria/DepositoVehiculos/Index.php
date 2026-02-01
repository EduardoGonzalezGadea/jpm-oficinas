<?php

namespace App\Http\Livewire\Tesoreria\DepositoVehiculos;

use App\Models\Tesoreria\DepositoVehiculo;
use App\Models\Tesoreria\DepositoVehiculoPlanilla;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $selectedYear;
    public $years = [];
    public $selectedDepositos = [];
    public $selectAll = false;

    protected $listeners = ['pg:eventRefresh-default' => 'refreshData', 'delete'];

    public function mount()
    {
        // Obtener años disponibles de los recibos
        $years = DepositoVehiculo::selectRaw('YEAR(recibo_fecha) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Agregar siempre el año actual si no está presente
        $currentYear = (int) date('Y');
        if (!in_array($currentYear, $years)) {
            $years[] = $currentYear;
        }

        // Ordenar descendentemente
        rsort($years);

        $this->years = $years;

        // Seleccionar el año actual por defecto
        $this->selectedYear = $currentYear;
    }

    public function confirmDelete($id)
    {
        $this->dispatchBrowserEvent('swal:confirm', [
            'title' => '¿Eliminar Depósito?',
            'text' => 'Esta acción no se puede revertir.',
            'method' => 'delete',
            'id' => $id,
            'componentId' => $this->id,
        ]);
    }

    public function delete($id)
    {
        DepositoVehiculo::find($id)->delete();
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Depósito eliminado correctamente.']);
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
            $this->selectedDepositos = DepositoVehiculo::whereNull('planilla_id')
                ->whereYear('recibo_fecha', $this->selectedYear)
                ->pluck('id')
                ->toArray();
        } else {
            $this->selectedDepositos = [];
        }
    }

    public function createPlanilla()
    {
        if (empty($this->selectedDepositos)) {
            $this->dispatchBrowserEvent('swal:error', [
                'title' => 'Error',
                'text' => 'Debe seleccionar al menos un depósito.',
            ]);
            return;
        }

        try {
            $planilla = DepositoVehiculoPlanilla::create([
                'fecha' => now(),
                'created_by' => auth()->id(),
            ]);

            DepositoVehiculo::whereIn('id', $this->selectedDepositos)
                ->update(['planilla_id' => $planilla->id]);

            $this->selectedDepositos = [];
            $this->selectAll = false;

            $this->dispatchBrowserEvent('swal:success', [
                'title' => 'Éxito',
                'text' => 'Planilla creada correctamente. Número: ' . $planilla->numero,
            ]);

            $this->refreshData();
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal:error', [
                'title' => 'Error',
                'text' => 'Error al crear la planilla: ' . $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        $depositos = DepositoVehiculo::with('medioPago')
            ->whereYear('recibo_fecha', $this->selectedYear)
            ->where(function ($query) {
                $query->where('titular', 'like', '%' . $this->search . '%')
                    ->orWhere('cedula', 'like', '%' . $this->search . '%')
                    ->orWhere('recibo_numero', 'like', '%' . $this->search . '%')
                    ->orWhere('orden_cobro', 'like', '%' . $this->search . '%');
            })
            ->orderBy('recibo_fecha', 'desc')
            ->paginate(10);

        return view('livewire.tesoreria.deposito-vehiculos.index', compact('depositos'))
            ->extends('layouts.app');
    }
}
