<?php

namespace App\Http\Livewire\Tesoreria\Configuracion;

use App\Models\Tesoreria\SiifDistribucionTipo as Model;
use Livewire\Component;
use Livewire\WithPagination;

class SiifDistribucionTipos extends Component
{
    use WithPagination;

    protected $listeners = ['resetForm', 'destroy' => 'destroy', 'refreshComponent' => '$refresh'];

    protected $paginationTheme = 'bootstrap';

    public $search;
    public $siif_distribucion_tipo_id;
    public $tipo;
    public $selectedTipo = null;

    public function render()
    {
        $tipos = Model::search($this->search)
            ->ordenado()
            ->paginate(10);

        return view('livewire.tesoreria.configuracion.siif-distribucion-tipos', [
            'tipos' => $tipos,
        ]);
    }

    public function create()
    {
        $this->resetInput();
        $this->dispatchBrowserEvent('show-modal', ['id' => 'siifTipoModal']);
    }

    public function store()
    {
        $this->validate([
            'tipo' => 'required|string|max:255|unique:siif_distribucion_tipos,tipo',
        ]);

        Model::create([
            'tipo' => $this->tipo,
        ]);

        $this->resetInput();
        $this->emit('siifTipoStore');
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Tipo de distribución SIIF creado con éxito.', 'toast' => true]);
    }

    public function edit($id)
    {
        $t = Model::findOrFail($id);

        $this->siif_distribucion_tipo_id = $id;
        $this->tipo = $t->tipo;

        $this->dispatchBrowserEvent('show-modal', ['id' => 'siifTipoModal']);
    }

    public function update()
    {
        $this->validate([
            'tipo' => 'required|string|max:255|unique:siif_distribucion_tipos,tipo,' . $this->siif_distribucion_tipo_id,
        ]);

        if ($this->siif_distribucion_tipo_id) {
            $t = Model::findOrFail($this->siif_distribucion_tipo_id);
            $t->update([
                'tipo' => $this->tipo,
            ]);

            $this->resetInput();
            $this->emit('siifTipoUpdate');
            $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Tipo de distribución SIIF actualizado con éxito.', 'toast' => true]);
        }
    }

    public function destroy($id)
    {
        $t = Model::findOrFail($id);
        $t->delete();

        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Tipo de distribución SIIF eliminado con éxito.', 'toast' => true]);
    }

    public function showDetails($id)
    {
        $this->selectedTipo = Model::findOrFail($id);
    }

    public function resetDetails()
    {
        $this->selectedTipo = null;
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
        $this->siif_distribucion_tipo_id = null;
        $this->tipo = null;
    }
}
