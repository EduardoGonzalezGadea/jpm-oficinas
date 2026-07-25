<?php

namespace App\Http\Livewire\Tesoreria\LibroDiario;

use App\Models\Tesoreria\LbMedio as Model;
use Livewire\Component;
use Livewire\WithPagination;

class LbMedios extends Component
{
    use WithPagination;

    protected $listeners = ['resetForm', 'destroy' => 'destroy', 'refreshComponent' => '$refresh'];

    protected $paginationTheme = 'bootstrap';

    public $search;
    public $item_id, $nombre, $nombre_corto;
    public $selectedItem = null;

    public function render()
    {
        $items = Model::search($this->search)
            ->ordenado()
            ->paginate(10);

        return view('livewire.tesoreria.libro-diario.lb-medios', [
            'items' => $items,
        ]);
    }

    public function create()
    {
        $this->resetInput();
        $this->dispatchBrowserEvent('show-modal', ['id' => 'modal']);
    }

    public function store()
    {
        $this->validate([
            'nombre' => 'required|string|max:100|unique:tes_lb_medios,nombre',
            'nombre_corto' => 'required|string|max:100',
        ]);

        Model::create([
            'nombre' => $this->nombre,
            'nombre_corto' => $this->nombre_corto,
        ]);

        $this->resetInput();
        $this->emit('itemStore');
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Medio creado con éxito!', 'toast' => true]);
    }

    public function edit($id)
    {
        $item = Model::findOrFail($id);

        $this->item_id = $id;
        $this->nombre = $item->nombre;
        $this->nombre_corto = $item->nombre_corto;

        $this->dispatchBrowserEvent('show-modal', ['id' => 'modal']);
    }

    public function update()
    {
        $this->validate([
            'nombre' => 'required|string|max:100|unique:tes_lb_medios,nombre,' . $this->item_id,
            'nombre_corto' => 'required|string|max:100',
        ]);

        if ($this->item_id) {
            $item = Model::findOrFail($this->item_id);
            $item->update([
                'nombre' => $this->nombre,
                'nombre_corto' => $this->nombre_corto,
            ]);
            $this->resetInput();
            $this->emit('itemUpdate');
            $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Medio actualizado con éxito!', 'toast' => true]);
        }
    }

    public function destroy($id)
    {
        $item = Model::findOrFail($id);
        $item->delete();
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Medio eliminado con éxito!', 'toast' => true]);
    }

    public function showDetails($id)
    {
        $this->selectedItem = Model::findOrFail($id);
    }

    public function resetDetails()
    {
        $this->selectedItem = null;
    }

    public function resetForm()
    {
        $this->resetInput();
    }

    private function resetInput()
    {
        $this->item_id = null;
        $this->nombre = null;
        $this->nombre_corto = null;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
