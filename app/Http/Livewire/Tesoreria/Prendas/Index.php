<?php

namespace App\Http\Livewire\Tesoreria\Prendas;

use App\Models\Tesoreria\Prenda;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $selectedYear;
    public $years = [];
    public $selectedPrendas = [];
    public $selectAll = false;

    protected $listeners = ['pg:eventRefresh-default' => 'refreshData', 'delete'];

    public function mount()
    {
        // Obtener años disponibles de los recibos
        $this->years = Prenda::selectRaw('YEAR(recibo_fecha) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Si no hay años, usar el año actual
        if (empty($this->years)) {
            $this->years = [date('Y')];
        }

        // Seleccionar el año más reciente por defecto
        $this->selectedYear = $this->years[0];
    }

    public function confirmDelete($id)
    {
        $this->dispatchBrowserEvent('swal:confirm', [
            'title' => '¿Eliminar Prenda?',
            'text' => 'Esta acción no se puede revertir.',
            'method' => 'delete',
            'id' => $id,
            'componentId' => $this->id,
        ]);
    }

    public function delete($id)
    {
        Prenda::find($id)->delete();
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Prenda eliminada correctamente.']);
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
            $this->dispatchBrowserEvent('swal:error', [
                'title' => 'Error',
                'text' => 'Debe seleccionar al menos una prenda.',
            ]);
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
            ->paginate(10);

        return view('livewire.tesoreria.prendas.index', compact('prendas'))
            ->extends('layouts.app');
    }
}
