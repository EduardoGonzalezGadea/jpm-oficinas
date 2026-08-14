<?php

namespace App\Livewire\Tesoreria\Configuracion;

use App\Models\Tesoreria\TesTipoMoneda as Model;
use Livewire\Component;
use Livewire\WithPagination;

class TesTiposMonedas extends Component
{
    use WithPagination;

    protected $listeners = ['resetForm', 'destroy' => 'destroy', 'refreshComponent' => '$refresh'];

    protected $paginationTheme = 'bootstrap';

    public $search;
    public $tipo_moneda_id, $nombre, $descripcion, $activo;
    public $selectedTipoMoneda = null;

    public function mount()
    {
        $this->activo = true;
    }

    public function render()
    {
        $tiposMonedas = Model::search($this->search)
            ->ordenado()
            ->paginate(10);

        return view('livewire.tesoreria.configuracion.tes-tipos-monedas', [
            'tiposMonedas' => $tiposMonedas,
        ]);
    }

    public function create()
    {
        $this->resetInput();
        $this->dispatch('show-modal', id: 'tipoMonedaModal');
    }

    public function store()
    {
        $this->validate([
            'nombre' => 'required|string|max:100|unique:tes_tipos_monedas,nombre',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'boolean'
        ]);

        Model::create([
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo,
        ]);

        $this->resetInput();
        $this->dispatch('tipoMonedaStore');
        $this->dispatch('alert', type: 'success', message: 'Tipo de moneda creado con éxito!', toast: true);
    }

    public function edit($id)
    {
        $tipoMoneda = Model::findOrFail($id);

        $this->tipo_moneda_id = $id;
        $this->nombre = $tipoMoneda->nombre;
        $this->descripcion = $tipoMoneda->descripcion;
        $this->activo = $tipoMoneda->activo;

        $this->dispatch('show-modal', id: 'tipoMonedaModal');
    }

    public function update()
    {
        $this->validate([
            'nombre' => 'required|string|max:100|unique:tes_tipos_monedas,nombre,' . $this->tipo_moneda_id,
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'boolean'
        ]);

        if ($this->tipo_moneda_id) {
            $tipoMoneda = Model::findOrFail($this->tipo_moneda_id);
            $tipoMoneda->update([
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'activo' => $this->activo,
            ]);
            $this->resetInput();
            $this->dispatch('tipoMonedaUpdate');
            $this->dispatch('alert', type: 'success', message: 'Tipo de moneda actualizado con éxito!', toast: true);
        }
    }

    public function destroy($id)
    {
        $tipoMoneda = Model::findOrFail($id);
        $tipoMoneda->delete();
        $this->dispatch('alert', type: 'success', message: 'Tipo de moneda eliminado con éxito!', toast: true);
    }

    public function showDetails($id)
    {
        $this->selectedTipoMoneda = Model::findOrFail($id);
        $this->dispatch('show-modal', id: 'detailsTipoMonedaModal');
    }

    public function resetDetails()
    {
        $this->selectedTipoMoneda = null;
    }

    public function resetForm()
    {
        $this->resetInput();
    }

    private function resetInput()
    {
        $this->tipo_moneda_id = null;
        $this->nombre = null;
        $this->descripcion = null;
        $this->activo = true;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
