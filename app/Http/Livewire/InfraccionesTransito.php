<?php

namespace App\Http\Livewire;

use App\Models\InfraccionTransito;
use Livewire\Component;
use Livewire\WithPagination;

class InfraccionesTransito extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Propiedades del formulario
    public $infraccion_id;
    public $articulo;
    public $apartado;
    public $descripcion;
    public $importe_ur;
    public $decreto;
    public $activo = true;

    // Propiedades de control
    public $isOpen = false;
    public $isEdit = false;
    public $search = '';
    public $sortField = 'articulo';
    public $sortDirection = 'asc';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'articulo'],
        'sortDirection' => ['except' => 'asc'],
        'page' => ['except' => 1],
    ];

    protected $rules = [
        'articulo' => 'required|string|max:10',
        'apartado' => 'nullable|string|max:10',
        'descripcion' => 'required|string',
        'importe_ur' => 'required|numeric|min:0|max:999.9',
        'decreto' => 'nullable|string|max:100',
        'activo' => 'boolean'
    ];

    protected $messages = [
        'articulo.required' => 'El artículo es obligatorio.',
        'descripcion.required' => 'La descripción es obligatoria.',
        'importe_ur.required' => 'El importe es obligatorio.',
        'importe_ur.numeric' => 'El importe debe ser un número.',
        'importe_ur.min' => 'El importe debe ser mayor a 0.',
        'importe_ur.max' => 'El importe no puede ser mayor a 999.9.',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
        $this->resetPage();
    }

    public function create()
    {
        $this->resetInputFields();
        $this->isEdit = false;
        $this->openModal();
    }

    public function edit($id)
    {
        $infraccion = InfraccionTransito::findOrFail($id);
        $this->infraccion_id = $infraccion->id;
        $this->articulo = $infraccion->articulo;
        $this->apartado = $infraccion->apartado;
        $this->descripcion = $infraccion->descripcion;
        $this->importe_ur = $infraccion->importe_ur;
        $this->decreto = $infraccion->decreto;
        $this->activo = $infraccion->activo;

        $this->isEdit = true;
        $this->openModal();
    }

    public function store()
    {
        $this->validate();

        InfraccionTransito::updateOrCreate(
            ['id' => $this->infraccion_id],
            [
                'articulo' => $this->articulo,
                'apartado' => $this->apartado,
                'descripcion' => $this->descripcion,
                'importe_ur' => $this->importe_ur,
                'decreto' => $this->decreto,
                'activo' => $this->activo
            ]
        );

        session()->flash('message', $this->isEdit ? 'Infracción actualizada exitosamente.' : 'Infracción creada exitosamente.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function delete($id)
    {
        InfraccionTransito::find($id)->delete();
        session()->flash('message', 'Infracción eliminada exitosamente.');
    }

    public function toggleStatus($id)
    {
        $infraccion = InfraccionTransito::find($id);
        $infraccion->update(['activo' => !$infraccion->activo]);

        session()->flash('message', 'Estado actualizado exitosamente.');
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    private function resetInputFields()
    {
        $this->infraccion_id = null;
        $this->articulo = '';
        $this->apartado = '';
        $this->descripcion = '';
        $this->importe_ur = '';
        $this->decreto = '';
        $this->activo = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $infracciones = InfraccionTransito::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('articulo', 'like', '%' . $this->search . '%')
                        ->orWhere('apartado', 'like', '%' . $this->search . '%')
                        ->orWhere('descripcion', 'like', '%' . $this->search . '%')
                        ->orWhere('decreto', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.infracciones-transito', compact('infracciones'));
    }
}
