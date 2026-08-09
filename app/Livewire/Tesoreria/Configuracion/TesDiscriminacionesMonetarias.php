<?php

namespace App\Livewire\Tesoreria\Configuracion;

use App\Models\Tesoreria\TesDiscriminacionMonetaria as Model;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class TesDiscriminacionesMonetarias extends Component
{
    use WithPagination;

    protected $listeners = ['resetForm', 'destroy' => 'destroy', 'refreshComponent' => '$refresh'];

    protected $paginationTheme = 'bootstrap';

    public $search;
    public $discriminacion_monetaria_id, $tipo, $valor, $texto, $activo;
    public $selectedDiscriminacion = null;

    public function mount()
    {
        $this->activo = true;
        $this->tipo = 'Billetes'; // Valor por defecto
    }

    public function render()
    {
        $version = Cache::get('discriminaciones_monetarias_version', 1);
        $page = $this->page ?: 1;
        $cacheKey = 'discriminaciones_monetarias_v' . $version . '_search_' . $this->search . '_page_' . $page;

        $discriminaciones = Cache::remember($cacheKey, now()->addDay(), function () {
            return Model::search($this->search)
                ->ordenado()
                ->paginate(15);
        });

        return view('livewire.tesoreria.configuracion.tes-discriminaciones-monetarias', [
            'discriminaciones' => $discriminaciones,
        ]);
    }

    public function create()
    {
        $this->resetInput();
        $this->dispatch('show-modal', ['id' => 'discriminacionModal']);
    }

    public function store()
    {
        $this->validate([
            'tipo' => 'required|string|in:Billetes,Monedas',
            'valor' => 'required|numeric|min:0|max:999999.99',
            'texto' => 'required|string|max:100',
            'activo' => 'boolean'
        ]);

        // Validar que no exista la misma combinación tipo-valor
        $existe = Model::where('tipo', $this->tipo)
            ->where('valor', $this->valor)
            ->exists();

        if ($existe) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Ya existe una discriminación monetaria con este tipo y valor!', 'toast' => true]);
            return;
        }

        Model::create([
            'tipo' => $this->tipo,
            'valor' => $this->valor,
            'texto' => $this->texto,
            'activo' => $this->activo,
        ]);

        $this->clearCache();
        $this->resetInput();
        $this->dispatch('discriminacionStore');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Discriminación monetaria creada con éxito!', 'toast' => true]);
    }

    public function edit($id)
    {
        $discriminacion = Model::findOrFail($id);

        $this->discriminacion_monetaria_id = $id;
        $this->tipo = $discriminacion->tipo;
        $this->valor = $discriminacion->valor;
        $this->texto = $discriminacion->texto;
        $this->activo = $discriminacion->activo;

        $this->dispatch('show-modal', ['id' => 'discriminacionModal']);
    }

    public function update()
    {
        $this->validate([
            'tipo' => 'required|string|in:Billetes,Monedas',
            'valor' => 'required|numeric|min:0|max:999999.99',
            'texto' => 'required|string|max:100',
            'activo' => 'boolean'
        ]);

        if ($this->discriminacion_monetaria_id) {
            // Validar que no exista la misma combinación tipo-valor (excepto el actual)
            $existe = Model::where('tipo', $this->tipo)
                ->where('valor', $this->valor)
                ->where('id', '!=', $this->discriminacion_monetaria_id)
                ->exists();

            if ($existe) {
                $this->dispatch('alert', ['type' => 'error', 'message' => 'Ya existe una discriminación monetaria con este tipo y valor!', 'toast' => true]);
                return;
            }

            $discriminacion = Model::findOrFail($this->discriminacion_monetaria_id);
            $discriminacion->update([
                'tipo' => $this->tipo,
                'valor' => $this->valor,
                'texto' => $this->texto,
                'activo' => $this->activo,
            ]);
            $this->clearCache();
            $this->resetInput();
            $this->dispatch('discriminacionUpdate');
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Discriminación monetaria actualizada con éxito!', 'toast' => true]);
        }
    }

    public function destroy($id)
    {
        $discriminacion = Model::findOrFail($id);

        $discriminacion->delete();
        $this->clearCache();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Discriminación monetaria eliminada con éxito!', 'toast' => true]);
    }

    public function showDetails($id)
    {
        $this->selectedDiscriminacion = Model::findOrFail($id);
        $this->dispatch('show-modal', id: 'detailsModal');
    }

    public function resetDetails()
    {
        $this->selectedDiscriminacion = null;
    }

    public function resetForm()
    {
        $this->resetInput();
    }

    private function resetInput()
    {
        $this->discriminacion_monetaria_id = null;
        $this->tipo = 'Billetes';
        $this->valor = null;
        $this->texto = null;
        $this->activo = true;
    }

    public function updatingSearch()
    {
        $this->resetPage();
        $this->clearCache();
    }

    private function clearCache()
    {
        $version = Cache::get('discriminaciones_monetarias_version', 1);
        Cache::put('discriminaciones_monetarias_version', $version + 1, now()->addYear());
    }
}
