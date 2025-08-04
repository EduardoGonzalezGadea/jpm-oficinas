<?php

namespace App\Http\Livewire\Tesoreria\Valores;

use App\Models\Tesoreria\Valores\Valor;
use App\Models\Tesoreria\Valores\ValorConcepto;
use Livewire\Component;
use Livewire\WithPagination;

class Conceptos extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $filterValor = '';
    public $filterActivo = '';
    public $sortField = 'concepto';
    public $sortDirection = 'asc';
    public $perPage = 10;

    public $showCreateModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;

    public $selectedConcepto;

    // Campos del formulario
    public $valores_id = '';
    public $concepto = '';
    public $monto = '';
    public $tipo_monto = 'pesos';
    public $descripcion = '';
    public $activo = true;

    protected $rules = [
        'valores_id' => 'required|exists:tes_valores,id',
        'concepto' => 'required|string|max:150',
        'monto' => 'required|numeric|min:0',
        'tipo_monto' => 'required|in:pesos,UR,porcentaje',
        'descripcion' => 'nullable|string|max:500',
        'activo' => 'boolean'
    ];

    protected $messages = [
        'valores_id.required' => 'El valor es obligatorio.',
        'valores_id.exists' => 'El valor seleccionado no es válido.',
        'concepto.required' => 'El concepto es obligatorio.',
        'concepto.max' => 'El concepto no puede superar los 150 caracteres.',
        'monto.required' => 'El monto es obligatorio.',
        'monto.numeric' => 'El monto debe ser un número.',
        'monto.min' => 'El monto no puede ser negativo.',
        'tipo_monto.required' => 'El tipo de monto es obligatorio.',
        'tipo_monto.in' => 'El tipo de monto seleccionado no es válido.',
        'descripcion.max' => 'La descripción no puede superar los 500 caracteres.'
    ];

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'conceptoDeleted' => 'handleConceptoDeleted'
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterValor()
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
        $this->dispatchBrowserEvent('show-modal', ['id' => 'createEditModal']);
    }

    public function openEditModal($conceptoId)
    {
        $concepto = ValorConcepto::findOrFail($conceptoId);
        $this->selectedConcepto = $concepto;

        $this->valores_id = $concepto->valores_id;
        $this->concepto = $concepto->concepto;
        $this->monto = $concepto->monto;
        $this->tipo_monto = $concepto->tipo_monto;
        $this->descripcion = $concepto->descripcion;
        $this->activo = $concepto->activo;

        $this->showEditModal = true;
        $this->dispatchBrowserEvent('show-modal', ['id' => 'createEditModal']);
    }

    public function openDeleteModal($conceptoId)
    {
        $this->selectedConcepto = ValorConcepto::findOrFail($conceptoId);
        $this->showDeleteModal = true;
        $this->dispatchBrowserEvent('show-modal', ['id' => 'deleteModal']);
    }

    public function create()
    {
        $this->validate();

        ValorConcepto::create([
            'valores_id' => $this->valores_id,
            'concepto' => $this->concepto,
            'monto' => $this->monto,
            'tipo_monto' => $this->tipo_monto,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo
        ]);

        $this->closeModal();
        $this->emit('alert', ['type' => 'success', 'message' => 'Concepto creado exitosamente.']);
    }

    public function update()
    {
        $this->validate();

        $this->selectedConcepto->update([
            'valores_id' => $this->valores_id,
            'concepto' => $this->concepto,
            'monto' => $this->monto,
            'tipo_monto' => $this->tipo_monto,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo
        ]);

        $this->closeModal();
        $this->emit('alert', ['type' => 'success', 'message' => 'Concepto actualizado exitosamente.']);
    }

    public function delete()
    {
        // Verificar si tiene salidas o usos
        if ($this->selectedConcepto->salidas()->count() > 0 || $this->selectedConcepto->usos()->count() > 0) {
            $this->emit('alert', [
                'type' => 'error',
                'message' => 'No se puede eliminar el concepto porque tiene movimientos asociados.'
            ]);
            $this->closeModal();
            return;
        }

        $this->selectedConcepto->delete();
        $this->closeModal();
        $this->emit('alert', ['type' => 'success', 'message' => 'Concepto eliminado exitosamente.']);
    }

    public function toggleActive($conceptoId)
    {
        $concepto = ValorConcepto::findOrFail($conceptoId);
        $concepto->update(['activo' => !$concepto->activo]);

        $estado = $concepto->activo ? 'activado' : 'desactivado';
        $this->emit('alert', ['type' => 'success', 'message' => "Concepto {$estado} exitosamente."]);
    }

    public function closeModal()
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->showDeleteModal = false;
        $this->resetForm();
        $this->dispatchBrowserEvent('hide-modal', ['id' => 'createEditModal']);
        $this->dispatchBrowserEvent('hide-modal', ['id' => 'deleteModal']);
    }

    public function resetForm()
    {
        $this->valores_id = '';
        $this->concepto = '';
        $this->monto = '';
        $this->tipo_monto = 'pesos';
        $this->descripcion = '';
        $this->activo = true;
        $this->selectedConcepto = null;
        $this->resetErrorBag();
    }

    public function handleConceptoDeleted()
    {
        $this->emit('alert', ['type' => 'success', 'message' => 'Concepto eliminado exitosamente.']);
    }

    public function render()
    {
        $conceptos = ValorConcepto::with('valor')
            ->when($this->search, function ($query) {
                $query->where('concepto', 'like', '%' . $this->search . '%')
                    ->orWhere('descripcion', 'like', '%' . $this->search . '%')
                    ->orWhereHas('valor', function ($q) {
                        $q->where('nombre', 'like', '%' . $this->search . '%');
                    });
            })
            ->when($this->filterValor, function ($query) {
                $query->where('valores_id', $this->filterValor);
            })
            ->when($this->filterActivo !== '', function ($query) {
                $query->where('activo', $this->filterActivo);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $valores = Valor::activos()->orderBy('nombre')->get();

        return view('livewire.tesoreria.valores.conceptos', compact('conceptos', 'valores'));
    }
}
