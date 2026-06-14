<?php

namespace App\Http\Livewire\Tesoreria\TarjetasCobroBrou;

use App\Models\Tesoreria\TarjetaCobroBrou;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $year;
    public $years = [];
    public $estado = '';

    protected $listeners = ['pg:eventRefresh-default' => 'refreshData', 'delete'];

    public function confirmDelete($id)
    {
        $this->dispatchBrowserEvent('swal:confirm', [
            'title' => '¿Eliminar Tarjeta?',
            'text' => 'Esta acción no se puede revertir.',
            'method' => 'delete',
            'id' => $id,
            'componentId' => $this->id,
        ]);
    }

    public function delete($id)
    {
        TarjetaCobroBrou::find($id)->delete();
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Tarjeta eliminada correctamente.']);
        $this->refreshData();
    }

    public function mount()
    {
        $this->loadYears();
        $this->year = date('Y');
        $this->estado = 'Recibido';
    }

    public function refreshData()
    {
        // Al llamar a este método desde un evento, Livewire vuelve a renderizar el componente.
        // Esto vuelve a ejecutar el método render(), que obtiene los datos actualizados.
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->year = date('Y');
        $this->estado = 'Recibido';
        $this->resetPage();
    }

    public function loadYears()
    {
        $years = TarjetaCobroBrou::selectRaw('YEAR(fecha_recibido) as year')
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

        $this->years = collect($years);
    }

    public function render()
    {
        $query = TarjetaCobroBrou::with('receptor')
            ->where(function ($query) {
                $query->where('titular_nombre', 'like', '%' . $this->search . '%')
                    ->orWhere('titular_apellido', 'like', '%' . $this->search . '%')
                    ->orWhere('titular_cedula', 'like', '%' . $this->search . '%')
                    ->orWhere('numero_tarjeta', 'like', '%' . $this->search . '%');
            });

        // Si el estado es "Recibido", NO filtrar por año
        // Si el estado es diferente, SÍ filtrar por año
        if ($this->estado !== 'Recibido') {
            $query->whereYear('fecha_recibido', $this->year);
        }

        if (!empty($this->estado)) {
            $query->where('estado', $this->estado);
        }

        $tarjetas = $query->orderBy('fecha_recibido', 'desc')->paginate(10);

        $totalRegistros = $query->count();

        return view('livewire.tesoreria.tarjetas-cobro-brou.index', [
            'tarjetas' => $tarjetas,
            'totalRegistros' => $totalRegistros,
        ])->extends('layouts.app');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingYear()
    {
        $this->resetPage();
    }

    public function updatingEstado()
    {
        $this->resetPage();
    }
}