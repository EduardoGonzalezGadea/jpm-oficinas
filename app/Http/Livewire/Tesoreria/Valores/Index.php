<?php

namespace App\Http\Livewire\Tesoreria\Valores;

use App\Models\Tesoreria\Valores\Valor;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $filterTipo = '';
    public $filterActivo = '';
    public $sortField = 'nombre';
    public $sortDirection = 'asc';
    public $perPage = 10;

    public $showCreateModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;
    public $showStockModal = false;

    public $selectedValor;
    public $stockResumen = [];

    // Campos del formulario
    public $nombre = '';
    public $recibos = '';
    public $tipo_valor = 'pesos';
    public $valor = '';
    public $descripcion = '';
    public $activo = true;

    protected $rules = [
        'nombre' => 'required|string|max:100',
        'recibos' => 'required|integer|min:1',
        'tipo_valor' => 'required|in:pesos,UR,SVE',
        'valor' => 'nullable|numeric|min:0',
        'descripcion' => 'nullable|string|max:500',
        'activo' => 'boolean'
    ];

    protected $messages = [
        'nombre.required' => 'El nombre es obligatorio.',
        'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
        'recibos.required' => 'La cantidad de recibos es obligatoria.',
        'recibos.integer' => 'La cantidad de recibos debe ser un número entero.',
        'recibos.min' => 'La cantidad de recibos debe ser al menos 1.',
        'tipo_valor.required' => 'El tipo de valor es obligatorio.',
        'tipo_valor.in' => 'El tipo de valor seleccionado no es válido.',
        'valor.numeric' => 'El valor debe ser un número.',
        'valor.min' => 'El valor no puede ser negativo.',
        'descripcion.max' => 'La descripción no puede superar los 500 caracteres.'
    ];

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'valorDeleted' => 'handleValorDeleted'
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterTipo()
    {
        $this->resetPage();
    }

    public function updatingFilterActivo()
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

    public function openCreateModal()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEditModal($valorId)
    {
        $valor = Valor::findOrFail($valorId);
        $this->selectedValor = $valor;

        $this->nombre = $valor->nombre;
        $this->recibos = $valor->recibos;
        $this->tipo_valor = $valor->tipo_valor;
        $this->valor = $valor->valor;
        $this->descripcion = $valor->descripcion;
        $this->activo = $valor->activo;

        $this->showEditModal = true;
    }

    public function openDeleteModal($valorId)
    {
        $this->selectedValor = Valor::findOrFail($valorId);
        $this->showDeleteModal = true;
    }

    public function openStockModal($valorId)
    {
        $this->selectedValor = Valor::with(['conceptos.usosActivos'])->findOrFail($valorId);
        $this->stockResumen = $this->selectedValor->getResumenStock();
        $this->showStockModal = true;
    }

    public function create()
    {
        $this->validate();

        // Validar valor según tipo
        if ($this->tipo_valor !== 'SVE' && empty($this->valor)) {
            $this->addError('valor', 'El valor es obligatorio para este tipo.');
            return;
        }

        if ($this->tipo_valor === 'SVE') {
            $this->valor = null;
        }

        Valor::create([
            'nombre' => $this->nombre,
            'recibos' => $this->recibos,
            'tipo_valor' => $this->tipo_valor,
            'valor' => $this->valor,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo
        ]);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->emit('alert', ['type' => 'success', 'message' => 'Valor creado exitosamente.']);
    }

    public function update()
    {
        $this->validate();

        // Validar valor según tipo
        if ($this->tipo_valor !== 'SVE' && empty($this->valor)) {
            $this->addError('valor', 'El valor es obligatorio para este tipo.');
            return;
        }

        if ($this->tipo_valor === 'SVE') {
            $this->valor = null;
        }

        $this->selectedValor->update([
            'nombre' => $this->nombre,
            'recibos' => $this->recibos,
            'tipo_valor' => $this->tipo_valor,
            'valor' => $this->valor,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo
        ]);

        $this->showEditModal = false;
        $this->resetForm();
        $this->emit('alert', ['type' => 'success', 'message' => 'Valor actualizado exitosamente.']);
    }

    public function delete()
    {
        // Verificar si tiene movimientos
        if ($this->selectedValor->entradas()->count() > 0 || $this->selectedValor->salidas()->count() > 0) {
            $this->emit('alert', [
                'type' => 'error',
                'message' => 'No se puede eliminar el valor porque tiene movimientos asociados.'
            ]);
            $this->showDeleteModal = false;
            return;
        }

        $this->selectedValor->delete();
        $this->showDeleteModal = false;
        $this->emit('alert', ['type' => 'success', 'message' => 'Valor eliminado exitosamente.']);
    }

    public function toggleActive($valorId)
    {
        $valor = Valor::findOrFail($valorId);
        $valor->update(['activo' => !$valor->activo]);

        $estado = $valor->activo ? 'activado' : 'desactivado';
        $this->emit('alert', ['type' => 'success', 'message' => "Valor {$estado} exitosamente."]);
    }

    private function resetForm()
    {
        $this->nombre = '';
        $this->recibos = '';
        $this->tipo_valor = 'pesos';
        $this->valor = '';
        $this->descripcion = '';
        $this->activo = true;
        $this->selectedValor = null;
        $this->resetErrorBag();
    }

    public function handleValorDeleted()
    {
        $this->emit('alert', ['type' => 'success', 'message' => 'Valor eliminado exitosamente.']);
    }

    public function render()
    {
        $valores = Valor::query()
            ->when($this->search, function ($query) {
                $query->where('nombre', 'like', '%' . $this->search . '%')
                    ->orWhere('descripcion', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterTipo, function ($query) {
                $query->where('tipo_valor', $this->filterTipo);
            })
            ->when($this->filterActivo !== '', function ($query) {
                $query->where('activo', $this->filterActivo);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.tesoreria.valores.index', compact('valores'));
    }
}