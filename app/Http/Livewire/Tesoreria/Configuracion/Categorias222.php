<?php

namespace App\Http\Livewire\Tesoreria\Configuracion;

use App\Models\Tesoreria\Categoria222;
use Livewire\Component;
use Livewire\WithPagination;

class Categorias222 extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $categoria_id;
    public $nombre, $codigo, $orden = 0, $activo = true;

    protected $listeners = ['deleteCategoria'];

    protected function rules()
    {
        return [
            'nombre' => 'required|string|max:100|unique:tes_categorias_222,nombre,' . $this->categoria_id,
            'codigo' => 'required|string|max:30|unique:tes_categorias_222,codigo,' . $this->categoria_id,
            'orden'  => 'required|integer|min:0',
            'activo' => 'boolean',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Categoria222::query();

        if ($this->search) {
            $query->where('nombre', 'like', '%' . $this->search . '%')
                ->orWhere('codigo', 'like', '%' . $this->search . '%');
        }

        $categorias = $query->orderBy('orden')->orderBy('nombre')->paginate(10);

        return view('livewire.tesoreria.configuracion.categorias-222', compact('categorias'));
    }

    public function resetForm()
    {
        $this->reset(['categoria_id', 'nombre', 'codigo']);
        $this->orden = 0;
        $this->activo = true;
        $this->resetErrorBag();
    }

    public function create()
    {
        $this->resetForm();
        $this->orden = Categoria222::max('orden') + 1;
        $this->dispatchBrowserEvent('show-modal', ['id' => 'modalCategoria']);
    }

    public function edit(int $id)
    {
        $this->resetForm();
        $categoria = Categoria222::findOrFail($id);
        $this->categoria_id = $categoria->id;
        $this->nombre = $categoria->nombre;
        $this->codigo = $categoria->codigo;
        $this->orden = $categoria->orden;
        $this->activo = $categoria->activo;

        $this->dispatchBrowserEvent('show-modal', ['id' => 'modalCategoria']);
    }

    public function store()
    {
        $this->validate();

        Categoria222::updateOrCreate(
            ['id' => $this->categoria_id],
            [
                'nombre' => $this->nombre,
                'codigo' => strtoupper($this->codigo),
                'orden'  => $this->orden,
                'activo' => $this->activo,
            ]
        );

        $this->dispatchBrowserEvent('hide-modal', ['id' => 'modalCategoria']);
        $this->dispatchBrowserEvent('swal', [
            'icon'  => 'success',
            'title' => 'Éxito',
            'text'  => 'Categoría guardada correctamente.',
        ]);
        $this->resetForm();
    }

    public function confirmDelete(int $id)
    {
        $this->dispatchBrowserEvent('confirm-delete', [
            'id'    => $id,
            'event' => 'deleteCategoria',
            'title' => '¿Eliminar categoría?',
            'text'  => 'Solo se marcará como borrada lógicamente.',
        ]);
    }

    public function deleteCategoria($id)
    {
        $categoria = Categoria222::findOrFail($id);
        $categoria->delete();

        $this->dispatchBrowserEvent('swal', [
            'icon'  => 'success',
            'title' => 'Eliminado',
            'text'  => 'La categoría ha sido eliminada lógicamente.',
        ]);
    }
}
