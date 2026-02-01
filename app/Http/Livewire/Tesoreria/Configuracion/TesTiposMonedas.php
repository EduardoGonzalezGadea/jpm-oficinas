<?php

namespace App\Http\Livewire\Tesoreria\Configuracion;

use App\Models\Tesoreria\TesTipoMoneda as Model;
use Illuminate\Support\Facades\Cache;
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
        $version = Cache::get('tipos_monedas_version', 1);
        $page = $this->page ?: 1;
        $cacheKey = 'tipos_monedas_v' . $version . '_search_' . $this->search . '_page_' . $page;

        $tiposMonedas = Cache::remember($cacheKey, now()->addDay(), function () {
            return Model::search($this->search)
                ->ordenado()
                ->paginate(10);
        });

        return view('livewire.tesoreria.configuracion.tes-tipos-monedas', [
            'tiposMonedas' => $tiposMonedas,
        ]);
    }

    public function create()
    {
        $this->resetInput();
        $this->dispatchBrowserEvent('show-modal', ['id' => 'tipoMonedaModal']);
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

        $this->clearCache();
        $this->resetInput();
        $this->emit('tipoMonedaStore');
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Tipo de moneda creado con éxito!', 'toast' => true]);
    }

    public function edit($id)
    {
        $tipoMoneda = Model::findOrFail($id);

        $this->tipo_moneda_id = $id;
        $this->nombre = $tipoMoneda->nombre;
        $this->descripcion = $tipoMoneda->descripcion;
        $this->activo = $tipoMoneda->activo;

        $this->dispatchBrowserEvent('show-modal', ['id' => 'tipoMonedaModal']);
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
            $this->clearCache();
            $this->resetInput();
            $this->emit('tipoMonedaUpdate');
            $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Tipo de moneda actualizado con éxito!', 'toast' => true]);
        }
    }

    public function destroy($id)
    {
        $tipoMoneda = Model::findOrFail($id);

        $tipoMoneda->delete();
        $this->clearCache();
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Tipo de moneda eliminado con éxito!', 'toast' => true]);
    }

    public function showDetails($id)
    {
        $this->selectedTipoMoneda = Model::findOrFail($id);
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
        $this->clearCache();
    }
    private function clearCache()
    {
        $version = Cache::get('tipos_monedas_version', 1);
        Cache::put('tipos_monedas_version', $version + 1, now()->addYear());
    }
}
