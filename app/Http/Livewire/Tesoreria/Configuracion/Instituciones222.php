<?php

namespace App\Http\Livewire\Tesoreria\Configuracion;

use App\Models\Tesoreria\Institucion222;
use Livewire\Component;
use Livewire\WithPagination;

class Instituciones222 extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $institucion_id;
    public $nombre, $codigo_siif, $activo = true;

    protected $listeners = ['deleteInstitucion'];

    protected function rules()
    {
        return [
            'nombre'      => 'required|string|max:150|unique:tes_instituciones_222,nombre,' . $this->institucion_id,
            'codigo_siif' => 'nullable|string|max:50',
            'activo'      => 'boolean',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Institucion222::query();

        if ($this->search) {
            $query->where('nombre', 'like', '%' . $this->search . '%')
                ->orWhere('codigo_siif', 'like', '%' . $this->search . '%');
        }

        $instituciones = $query->orderBy('nombre')->paginate(10);

        return view('livewire.tesoreria.configuracion.instituciones-222', compact('instituciones'));
    }

    public function resetForm()
    {
        $this->reset(['institucion_id', 'nombre', 'codigo_siif']);
        $this->activo = true;
        $this->resetErrorBag();
    }

    public function create()
    {
        $this->resetForm();
        $this->dispatchBrowserEvent('show-modal', ['id' => 'modalInstitucion']);
    }

    public function edit(int $id)
    {
        $this->resetForm();
        $institucion = Institucion222::findOrFail($id);
        $this->institucion_id = $institucion->id;
        $this->nombre = $institucion->nombre;
        $this->codigo_siif = $institucion->codigo_siif;
        $this->activo = $institucion->activo;

        $this->dispatchBrowserEvent('show-modal', ['id' => 'modalInstitucion']);
    }

    public function store()
    {
        $this->validate();

        Institucion222::updateOrCreate(
            ['id' => $this->institucion_id],
            [
                'nombre'      => $this->nombre,
                'codigo_siif' => $this->codigo_siif,
                'activo'      => $this->activo,
            ]
        );

        $this->dispatchBrowserEvent('hide-modal', ['id' => 'modalInstitucion']);
        $this->dispatchBrowserEvent('swal', [
            'icon'  => 'success',
            'title' => 'Éxito',
            'text'  => 'Institución guardada correctamente.',
        ]);
        $this->resetForm();
    }

    public function confirmDelete(int $id)
    {
        $this->dispatchBrowserEvent('confirm-delete', [
            'id'    => $id,
            'event' => 'deleteInstitucion',
            'title' => '¿Eliminar institución?',
            'text'  => 'No podrás revertir esto. Los movimientos en caja no se verán afectados porque usamos soft-deletes.',
        ]);
    }

    public function deleteInstitucion($id)
    {
        $institucion = Institucion222::findOrFail($id);
        $institucion->delete();

        $this->dispatchBrowserEvent('swal', [
            'icon'  => 'success',
            'title' => 'Eliminado',
            'text'  => 'La institución ha sido eliminada lógicamente.',
        ]);
    }
}
