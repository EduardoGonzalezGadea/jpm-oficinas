<?php

namespace App\Http\Livewire\Tesoreria\LibroDiario;

use App\Models\Tesoreria\LbConcepto as Model;
use Livewire\Component;
use Livewire\WithPagination;

class LbConceptos extends Component
{
    use WithPagination;

    protected $listeners = ['resetForm', 'destroy' => 'destroy', 'refreshComponent' => '$refresh'];

    protected $paginationTheme = 'bootstrap';

    public $search;
    public $item_id, $nombre;
    public $selectedItem = null;

    public function render()
    {
        $items = Model::search($this->search)
            ->ordenado()
            ->withCount('detalles')
            ->paginate(10);

        return view('livewire.tesoreria.libro-diario.lb-conceptos', [
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
            'nombre' => 'required|string|max:100|unique:tes_lb_conceptos,nombre',
        ]);

        Model::create([
            'nombre' => $this->nombre,
        ]);

        $this->resetInput();
        $this->emit('itemStore');
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Concepto creado con éxito!', 'toast' => true]);
    }

    public function edit($id)
    {
        $item = Model::findOrFail($id);

        $this->item_id = $id;
        $this->nombre = $item->nombre;

        $this->dispatchBrowserEvent('show-modal', ['id' => 'modal']);
    }

    public function update()
    {
        $this->validate([
            'nombre' => 'required|string|max:100|unique:tes_lb_conceptos,nombre,' . $this->item_id,
        ]);

        if ($this->item_id) {
            $item = Model::findOrFail($this->item_id);
            $item->update([
                'nombre' => $this->nombre,
            ]);
            $this->resetInput();
            $this->emit('itemUpdate');
            $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Concepto actualizado con éxito!', 'toast' => true]);
        }
    }

    public function destroy($id)
    {
        $item = Model::findOrFail($id);

        if ($item->detalles()->count() > 0) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'No se puede eliminar el concepto porque tiene detalles asociados.',
            ]);
            return;
        }

        $item->delete();
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Concepto eliminado con éxito!', 'toast' => true]);
    }

    public function showDetails($id)
    {
        $this->selectedItem = Model::with('detalles')->findOrFail($id);
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
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
