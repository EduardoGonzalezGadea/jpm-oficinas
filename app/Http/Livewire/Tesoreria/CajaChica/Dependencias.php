<?php

namespace App\Http\Livewire\Tesoreria\CajaChica;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tesoreria\Dependencia;

class Dependencias extends Component
{
    use WithPagination;

    public $search = '';
    public $nombre;
    public $dependenciaId;
    public $modalFormVisible = false;
    public $modalConfirmDeleteVisible = false;

    protected $rules = [
        'nombre' => 'required|string|max:255|unique:tes_cch_dependencias,dependencia',
    ];

    protected $messages = [
        'nombre.required' => 'El nombre de la dependencia es obligatorio.',
        'nombre.unique' => 'Ya existe una dependencia con este nombre.',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function resetForm()
    {
        $this->nombre = '';
        $this->dependenciaId = null;
    }

    public function create()
    {
        $this->resetValidation();
        $this->resetForm();
        $this->modalFormVisible = true;
    }

    public function edit($id)
    {
        $this->resetValidation();
        $this->dependenciaId = $id;
        $dependencia = Dependencia::findOrFail($id);
        $this->nombre = $dependencia->dependencia;
        $this->modalFormVisible = true;
    }

    public function save()
    {
        if ($this->dependenciaId) {
            $this->rules['nombre'] .= ',' . $this->dependenciaId . ',idDependencias';
        }

        $this->validate();

        if ($this->dependenciaId) {
            $dependencia = Dependencia::findOrFail($this->dependenciaId);
            $dependencia->update(['dependencia' => $this->nombre]);
            session()->flash('message', 'Dependencia actualizada correctamente.');
        } else {
            Dependencia::create(['dependencia' => $this->nombre]);
            session()->flash('message', 'Dependencia creada correctamente.');
        }

        $this->modalFormVisible = false;
    }

    public function confirmDelete($id)
    {
        $this->dependenciaId = $id;
        $this->modalConfirmDeleteVisible = true;
    }

    public function delete()
    {
        Dependencia::findOrFail($this->dependenciaId)->delete();
        session()->flash('message', 'Dependencia eliminada correctamente.');
        $this->modalConfirmDeleteVisible = false;
        $this->resetForm();
    }

    public function cerrarGestionDependencias()
    {
        $this->emit('cerrarGestionDependencias');
    }

    public function render()
    {
        $dependencias = Dependencia::where('dependencia', 'like', '%' . $this->search . '%')
            ->orderBy('dependencia', 'asc')
            ->paginate(10);

        return view('livewire.tesoreria.caja-chica.dependencias', [
            'dependencias' => $dependencias
        ]);
    }
}
