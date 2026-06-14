<?php

namespace App\Http\Livewire\Tesoreria\Configuracion;

use App\Models\Tesoreria\SiifDistribucionDependencia as Model;
use Livewire\Component;
use Livewire\WithPagination;

class SiifDistribucionDependencias extends Component
{
    use WithPagination;

    protected $listeners = ['resetForm', 'destroy' => 'destroy', 'refreshComponent' => '$refresh'];

    protected $paginationTheme = 'bootstrap';

    public $search;
    public $siif_distribucion_dependencia_id;
    public $dependencia;
    public $abreviatura;
    public $selectedDependencia = null;

    public function render()
    {
        $dependencias = Model::search($this->search)
            ->ordenado()
            ->paginate(10);

        return view('livewire.tesoreria.configuracion.siif-distribucion-dependencias', [
            'dependencias' => $dependencias,
        ]);
    }

    public function create()
    {
        $this->resetInput();
        $this->dispatchBrowserEvent('show-modal', ['id' => 'siifDependenciaModal']);
    }

    public function store()
    {
        $this->validate([
            'dependencia' => 'required|string|max:255|unique:siif_distribucion_dependencias,dependencia',
            'abreviatura' => 'required|string|max:100|unique:siif_distribucion_dependencias,abreviatura',
        ]);

        Model::create([
            'dependencia' => $this->dependencia,
            'abreviatura' => $this->abreviatura,
        ]);

        $this->resetInput();
        $this->emit('siifDependenciaStore');
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Dependencia de distribución SIIF creada con éxito.', 'toast' => true]);
    }

    public function edit($id)
    {
        $dep = Model::findOrFail($id);

        $this->siif_distribucion_dependencia_id = $id;
        $this->dependencia = $dep->dependencia;
        $this->abreviatura = $dep->abreviatura;

        $this->dispatchBrowserEvent('show-modal', ['id' => 'siifDependenciaModal']);
    }

    public function update()
    {
        $this->validate([
            'dependencia' => 'required|string|max:255|unique:siif_distribucion_dependencias,dependencia,' . $this->siif_distribucion_dependencia_id,
            'abreviatura' => 'required|string|max:100|unique:siif_distribucion_dependencias,abreviatura,' . $this->siif_distribucion_dependencia_id,
        ]);

        if ($this->siif_distribucion_dependencia_id) {
            $dep = Model::findOrFail($this->siif_distribucion_dependencia_id);
            $dep->update([
                'dependencia' => $this->dependencia,
                'abreviatura' => $this->abreviatura,
            ]);

            $this->resetInput();
            $this->emit('siifDependenciaUpdate');
            $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Dependencia de distribución SIIF actualizada con éxito.', 'toast' => true]);
        }
    }

    public function destroy($id)
    {
        $dep = Model::findOrFail($id);
        $dep->delete();

        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Dependencia de distribución SIIF eliminada con éxito.', 'toast' => true]);
    }

    public function showDetails($id)
    {
        $this->selectedDependencia = Model::findOrFail($id);
    }

    public function resetDetails()
    {
        $this->selectedDependencia = null;
    }

    public function resetForm()
    {
        $this->resetInput();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    private function resetInput()
    {
        $this->siif_distribucion_dependencia_id = null;
        $this->dependencia = null;
        $this->abreviatura = null;
    }
}
