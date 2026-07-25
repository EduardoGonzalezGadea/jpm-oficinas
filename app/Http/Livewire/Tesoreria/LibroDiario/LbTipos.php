<?php

namespace App\Http\Livewire\Tesoreria\LibroDiario;

use App\Models\Tesoreria\LbTipo as Model;
use Livewire\Component;
use Livewire\WithPagination;

class LbTipos extends Component
{
    use WithPagination;

    protected $listeners = ['resetForm', 'destroy' => 'destroy', 'refreshComponent' => '$refresh'];

    protected $paginationTheme = 'bootstrap';

    public $search;
    public $item_id, $nombre, $signo;
    public $selectedItem = null;

    public function mount()
    {
        $this->signo = 1;
    }

    public function render()
    {
        $items = Model::search($this->search)
            ->ordenado()
            ->paginate(10);

        return view('livewire.tesoreria.libro-diario.lb-tipos', [
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
            'nombre' => 'required|string|max:100|unique:tes_lb_tipos,nombre',
            'signo' => 'required|integer|in:-1,0,1',
        ]);

        Model::create([
            'nombre' => $this->nombre,
            'signo' => $this->signo,
        ]);

        $this->resetInput();
        $this->emit('itemStore');
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Tipo creado con éxito!', 'toast' => true]);
    }

    public function edit($id)
    {
        $item = Model::findOrFail($id);

        $this->item_id = $id;
        $this->nombre = $item->nombre;
        $this->signo = $item->signo;

        $this->dispatchBrowserEvent('show-modal', ['id' => 'modal']);
    }

    public function update()
    {
        $this->validate([
            'nombre' => 'required|string|max:100|unique:tes_lb_tipos,nombre,' . $this->item_id,
            'signo' => 'required|integer|in:-1,0,1',
        ]);

        if ($this->item_id) {
            $item = Model::findOrFail($this->item_id);
            $item->update([
                'nombre' => $this->nombre,
                'signo' => $this->signo,
            ]);
            $this->resetInput();
            $this->emit('itemUpdate');
            $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Tipo actualizado con éxito!', 'toast' => true]);
        }
    }

    public function destroy($id)
    {
        $item = Model::findOrFail($id);
        $item->delete();
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Tipo eliminado con éxito!', 'toast' => true]);
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
        $this->signo = 1;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
