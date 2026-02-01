<?php

namespace App\Http\Livewire\Tesoreria\CertificadosResidencia;

use App\Models\Tesoreria\CertificadoResidencia;
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
            'title' => '¿Eliminar Certificado?',
            'text' => 'Esta acción no se puede revertir.',
            'method' => 'delete',
            'id' => $id,
            'componentId' => $this->id,
        ]);
    }

    public function delete($id)
    {
        CertificadoResidencia::find($id)->delete();
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Certificado eliminado correctamente.']);
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
        $years = CertificadoResidencia::selectRaw('YEAR(fecha_recibido) as year')
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
        $query = CertificadoResidencia::with('receptor')
            ->where(function ($query) {
                $query->where('titular_nombre', 'like', '%' . $this->search . '%')
                    ->orWhere('titular_apellido', 'like', '%' . $this->search . '%')
                    ->orWhere('titular_nro_documento', 'like', '%' . $this->search . '%');
            });

        // Si el estado es "Recibido", NO filtrar por año
        // Si el estado es diferente, SÍ filtrar por año
        if ($this->estado !== 'Recibido') {
            $query->whereYear('fecha_recibido', $this->year);
        }

        if (!empty($this->estado)) {
            $query->where('estado', $this->estado);
        }

        $certificados = $query->orderBy('fecha_recibido', 'desc')->paginate(10);

        $totalRegistros = $query->count();

        return view('livewire.tesoreria.certificados-residencia.index', [
            'certificados' => $certificados,
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
