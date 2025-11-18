<?php

namespace App\Http\Livewire\Tesoreria\Valores\Servicio;

use App\Models\Tesoreria\Servicio;
use App\Traits\ConvertirMayusculas;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination, ConvertirMayusculas;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $showModal = false;
    public $showDeleteModal = false;
    public $servicioId;
    public $servicioIdToDelete;
    public $nombre, $valor_ur, $activo;

    protected $rules = [
        'nombre' => 'required|string|max:255|unique:tes_servicios,nombre',
        'valor_ur' => 'nullable|numeric|min:0',
        'activo' => 'boolean',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function clearSearch()
    {
        $this->search = '';
        $this->resetPage();
    }

    public function render()
    {
        $searchTerm = trim($this->search);
        $query = Servicio::query();

        if (!empty($searchTerm)) {
            $query->where('nombre', 'like', '%' . $searchTerm . '%');
        }

        $servicios = $query->orderBy('nombre')->paginate(10);

        return view('livewire.tesoreria.valores.servicio.index', compact('servicios'))
            ->extends('layouts.app')
            ->section('content');
    }

    public function toggleStatus($id)
    {
        $servicio = Servicio::findOrFail($id);
        $servicio->activo = !$servicio->activo;
        $servicio->save();

        $this->dispatchBrowserEvent('swal', [
            'title' => 'Éxito',
            'text' => 'Estado del servicio actualizado.',
            'type' => 'success'
        ]);
    }

    public function create()
    {
        $this->resetInput();
        $this->showModal = true;
    }

    public function edit($id)
    {
        $servicio = Servicio::findOrFail($id);
        $this->servicioId = $id;
        $this->nombre = $servicio->nombre;
        $this->valor_ur = $servicio->valor_ur;
        $this->activo = $servicio->activo;
        $this->showModal = true;
    }

    public function save()
    {
        $this->rules['nombre'] = 'required|string|max:255|unique:tes_servicios,nombre,' . $this->servicioId;
        $this->validate();

        // Convertir nombre a mayúsculas
        $nombre = $this->toUpper($this->nombre);

        Servicio::updateOrCreate(
            ['id' => $this->servicioId],
            [
                'nombre' => $nombre,
                'valor_ur' => $this->valor_ur,
                'activo' => $this->activo,
            ]
        );

        $this->dispatchBrowserEvent('swal', [
            'title' => 'Éxito',
            'text' => 'Servicio guardado correctamente.',
            'type' => 'success'
        ]);

        $this->showModal = false;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetInput();
    }

    public function confirmDelete($id)
    {
        $this->servicioIdToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->servicioIdToDelete = null;
    }

    public function destroy()
    {
        Servicio::find($this->servicioIdToDelete)->delete();
        $this->showDeleteModal = false;

        $this->dispatchBrowserEvent('swal', [
            'title' => 'Éxito',
            'text' => 'Servicio eliminado correctamente.',
            'type' => 'success'
        ]);
    }

    private function resetInput()
    {
        $this->servicioId = null;
        $this->nombre = '';
        $this->valor_ur = null;
        $this->activo = true;
    }
}
