<?php

namespace App\Livewire\Tesoreria\Configuracion;

use App\Models\Tesoreria\TesDenominacionMoneda as Model;
use Livewire\Component;
use Livewire\WithPagination;

class TesDenominacionesMonedas extends Component
{
    use WithPagination;

    protected $listeners = ['resetForm', 'destroy' => 'destroy', 'refreshComponent' => '$refresh'];

    protected $paginationTheme = 'bootstrap';

    public $search;
    public $denominacion_moneda_id;
    public $tipo_moneda;
    public $denominacion;
    public $descripcion;
    public $activo;
    public $selectedDenominacion = null;

    // Opciones para el dropdown de tipo de moneda
    public $tiposMoneda = [
        'Billetes' => 'Billetes',
        'Monedas' => 'Monedas',
        'Billetes extranjeros' => 'Billetes extranjeros',
        'Monedas extranjeras' => 'Monedas extranjeras'
    ];

    public function mount()
    {
        $this->activo = true;
        $this->tipo_moneda = 'Billetes';
    }

    public function render()
    {
        $denominaciones = Model::search($this->search)
            ->ordenado()
            ->paginate(15);

        return view('livewire.tesoreria.configuracion.tes-denominaciones-monedas', [
            'denominaciones' => $denominaciones,
        ]);
    }

    public function create()
    {
        $this->resetInput();
        $this->dispatch('show-modal', id: 'denominacionModal');
    }

    public function store()
    {
        $this->validate([
            'tipo_moneda' => 'required|string|max:100',
            'denominacion' => 'required|numeric|min:0|max:999999.99',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'boolean'
        ]);

        Model::create([
            'tipo_moneda' => $this->tipo_moneda,
            'denominacion' => $this->denominacion,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo ?? true,
        ]);

        $this->resetInput();
        $this->dispatch('denominacionStore');
        $this->dispatch('alert', type: 'success', message: 'Denominación creada con éxito!', toast: true);
    }

    public function edit($id)
    {
        $denominacion = Model::findOrFail($id);

        $this->denominacion_moneda_id = $id;
        $this->tipo_moneda = $denominacion->tipo_moneda;
        $this->denominacion = $denominacion->denominacion;
        $this->descripcion = $denominacion->descripcion;
        $this->activo = (bool) $denominacion->activo;

        $this->dispatch('show-modal', id: 'denominacionModal');
    }

    public function update()
    {
        $this->validate([
            'tipo_moneda' => 'required|string|max:100',
            'denominacion' => 'required|numeric|min:0|max:999999.99',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'boolean'
        ]);

        if ($this->denominacion_moneda_id) {
            $denominacion = Model::findOrFail($this->denominacion_moneda_id);
            $denominacion->update([
                'tipo_moneda' => $this->tipo_moneda,
                'denominacion' => $this->denominacion,
                'descripcion' => $this->descripcion,
                'activo' => $this->activo ?? true,
            ]);

            $this->resetInput();
            $this->dispatch('denominacionUpdate');
            $this->dispatch('alert', type: 'success', message: 'Denominación actualizada con éxito!', toast: true);
        }
    }

    public function destroy($id)
    {
        $denominacion = Model::findOrFail($id);
        $denominacion->delete();
        $this->dispatch('alert', type: 'success', message: 'Denominación eliminada con éxito!', toast: true);
    }

    public function showDetails($id)
    {
        $this->selectedDenominacion = Model::findOrFail($id);
        $this->dispatch('show-modal', id: 'detailsDenominacionModal');
    }

    public function resetDetails()
    {
        $this->selectedDenominacion = null;
    }

    public function resetForm()
    {
        $this->resetInput();
    }

    private function resetInput()
    {
        $this->denominacion_moneda_id = null;
        $this->tipo_moneda = 'Billetes';
        $this->denominacion = null;
        $this->descripcion = null;
        $this->activo = true;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
