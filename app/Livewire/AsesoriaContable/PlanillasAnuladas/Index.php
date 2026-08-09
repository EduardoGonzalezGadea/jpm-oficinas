<?php

namespace App\Livewire\AsesoriaContable\PlanillasAnuladas;

use App\Models\Tesoreria\TesPlanillaEr;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public array $filtroMeses = [];
    public $filtroAno = null;

    public function mount()
    {
        $this->filtroAno = (int) date('Y');
        $this->filtroMeses = [(int) date('m')];
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFiltroMeses()
    {
        $this->resetPage();
    }

    public function updatedFiltroAno()
    {
        $this->resetPage();
    }

    public function limpiarFiltroMeses(): void
    {
        $this->filtroMeses = [];
    }

    public function resetearBusqueda(): void
    {
        $this->search = '';
        $this->filtroMeses = [(int) date('m')];
        $this->filtroAno = (int) date('Y');
    }

    public function render()
    {
        $anosRegistrados = TesPlanillaEr::whereNotNull('fecha')
            ->selectRaw('YEAR(fecha) as year')
            ->distinct()
            ->pluck('year')
            ->sort()
            ->values()
            ->toArray();

        $currentYear = (int) date('Y');
        if (!in_array($currentYear, $anosRegistrados)) {
            array_unshift($anosRegistrados, $currentYear);
        }

        $query = TesPlanillaEr::with(['tipo', 'dependencia', 'deletedBy'])
            ->onlyTrashed()
            ->when($this->search, function ($query) {
                $query->where('numero', 'like', '%' . $this->search . '%')
                    ->orWhere('er_numero', 'like', '%' . $this->search . '%');
            })
            ->when($this->filtroAno, fn($q) => $q->whereYear('fecha', $this->filtroAno))
            ->when(!empty($this->filtroMeses), function ($q) {
                $q->where(function ($query) {
                    foreach ($this->filtroMeses as $mes) {
                        $query->orWhereMonth('fecha', (int) $mes);
                    }
                });
            })
            ->orderBy('deleted_at', 'desc');

        $planillas = $query->paginate(15);

        return view('livewire.asesoria-contable.planillas-anuladas', compact('planillas', 'anosRegistrados'))
            ->extends('layouts.app')
            ->section('content');
    }
}
