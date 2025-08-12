<?php

namespace App\Http\Livewire\Tesoreria\CajaChica;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tesoreria\TesCchAcreedor;

class Acreedores extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $nombre;
    public $acreedorId;
    public $modalFormVisible = false;
    public $modalConfirmDeleteVisible = false;

    protected function rules()
    {
        return [
            'nombre' => 'required|string|max:255|unique:tes_cch_acreedores,acreedor,' . $this->acreedorId . ',idAcreedores',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre del acreedor es obligatorio.',
        'nombre.unique' => 'Ya existe un acreedor con este nombre.',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function resetForm()
    {
        $this->nombre = '';
        $this->acreedorId = null;
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
        $this->acreedorId = $id;
        $acreedor = TesCchAcreedor::findOrFail($id);
        $this->nombre = $acreedor->acreedor;
        $this->modalFormVisible = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->acreedorId) {
            $acreedor = TesCchAcreedor::findOrFail($this->acreedorId);
            $acreedor->update(['acreedor' => $this->nombre]);
            session()->flash('message', 'Acreedor actualizado correctamente.');
            $this->emit('acreedorActualizado');
        } else {
            TesCchAcreedor::create(['acreedor' => $this->nombre]);
            session()->flash('message', 'Acreedor creado correctamente.');
            $this->emit('acreedorCreado');
        }

        $this->modalFormVisible = false;
        $this->resetForm();
    }

    public function confirmDelete($id)
    {
        $this->acreedorId = $id;
        $this->modalConfirmDeleteVisible = true;
    }

    public function delete()
    {
        TesCchAcreedor::findOrFail($this->acreedorId)->delete();
        session()->flash('message', 'Acreedor eliminado correctamente.');
        $this->modalConfirmDeleteVisible = false;
        $this->resetForm();
        $this->emit('acreedorEliminado');
    }

    public function render()
    {
        $acreedores = TesCchAcreedor::where('acreedor', 'like', '%' . $this->search . '%')
            ->orderBy('acreedor', 'asc')
            ->paginate(10);

        return view('livewire.tesoreria.caja-chica.acreedores', [
            'acreedores' => $acreedores
        ]);
    }
}