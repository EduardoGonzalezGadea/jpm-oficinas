<?php

namespace App\Livewire\Tesoreria\LibroDiario;

use App\Models\Tesoreria\MedioDePago as Model;
use Livewire\Component;
use Livewire\WithPagination;

class MediosDePago extends Component
{
    use WithPagination;

    protected $listeners = ['resetForm', 'destroy' => 'destroy', 'refreshComponent' => '$refresh'];

    protected $paginationTheme = 'bootstrap';

    public $search;
    public $item_id, $nombre, $nombre_corto, $descripcion, $activo, $contado, $codigo_soniar, $es_libro_diario, $es_recaudacion, $orden;
    public $selectedItem = null;

    public function render()
    {
        $items = Model::search($this->search)
            ->ordenado()
            ->paginate(10);

        return view('livewire.tesoreria.libro-diario.medios-de-pago', [
            'items' => $items,
        ]);
    }

    public function create()
    {
        $this->resetInput();
        $this->dispatch('show-modal', id: 'modal');
    }

    public function store()
    {
        $this->validate([
            'nombre' => 'required|string|max:255|unique:tes_medio_de_pagos,nombre',
            'nombre_corto' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'codigo_soniar' => 'nullable|string|max:50',
            'activo' => 'boolean',
            'contado' => 'boolean',
            'es_libro_diario' => 'boolean',
            'es_recaudacion' => 'boolean',
            'orden' => 'integer|min:0',
        ]);

        Model::create([
            'nombre' => $this->nombre,
            'nombre_corto' => $this->nombre_corto,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo ?? true,
            'contado' => $this->contado ?? false,
            'codigo_soniar' => $this->codigo_soniar,
            'es_libro_diario' => $this->es_libro_diario ?? true,
            'es_recaudacion' => $this->es_recaudacion ?? true,
            'orden' => $this->orden ?? 0,
        ]);

        $this->resetInput();
        $this->dispatch('itemStore');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Medio de pago creado con éxito!', 'toast' => true]);
    }

    public function edit($id)
    {
        $item = Model::findOrFail($id);

        $this->item_id = $id;
        $this->nombre = $item->nombre;
        $this->nombre_corto = $item->nombre_corto;
        $this->descripcion = $item->descripcion;
        $this->codigo_soniar = $item->codigo_soniar;
        $this->activo = $item->activo;
        $this->contado = $item->contado;
        $this->es_libro_diario = $item->es_libro_diario;
        $this->es_recaudacion = $item->es_recaudacion;
        $this->orden = $item->orden;

        $this->dispatch('show-modal', id: 'modal');
    }

    public function update()
    {
        $this->validate([
            'nombre' => 'required|string|max:255|unique:tes_medio_de_pagos,nombre,' . $this->item_id,
            'nombre_corto' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'codigo_soniar' => 'nullable|string|max:50',
            'activo' => 'boolean',
            'contado' => 'boolean',
            'es_libro_diario' => 'boolean',
            'es_recaudacion' => 'boolean',
            'orden' => 'integer|min:0',
        ]);

        if ($this->item_id) {
            $item = Model::findOrFail($this->item_id);
            $item->update([
                'nombre' => $this->nombre,
                'nombre_corto' => $this->nombre_corto,
                'descripcion' => $this->descripcion,
                'codigo_soniar' => $this->codigo_soniar,
                'activo' => $this->activo ?? true,
                'contado' => $this->contado ?? false,
                'es_libro_diario' => $this->es_libro_diario ?? true,
                'es_recaudacion' => $this->es_recaudacion ?? true,
                'orden' => $this->orden ?? 0,
            ]);
            $this->resetInput();
            $this->dispatch('itemUpdate');
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Medio de pago actualizado con éxito!', 'toast' => true]);
        }
    }

    public function destroy($id)
    {
        $item = Model::findOrFail($id);
        $item->delete();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Medio de pago eliminado con éxito!', 'toast' => true]);
    }

    public function showDetails($id)
    {
        $this->selectedItem = Model::findOrFail($id);
        $this->dispatch('show-modal', id: 'detailsModal');
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
        $this->descripcion = null;
        $this->codigo_soniar = null;
        $this->activo = true;
        $this->contado = false;
        $this->es_libro_diario = true;
        $this->es_recaudacion = true;
        $this->orden = 0;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
