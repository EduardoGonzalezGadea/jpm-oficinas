<?php

namespace App\Http\Livewire\Tesoreria\Valores;

use App\Models\Tesoreria\Valores\Valor;
use App\Models\Tesoreria\Valores\ValorEntrada;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class Entradas extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $filterValor = '';
    public $filterFecha = '';
    public $sortField = 'fecha';
    public $sortDirection = 'desc';
    public $perPage = 10;

    public $showCreateModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;
    public $showDetailModal = false;

    public $selectedEntrada;

    // Campos del formulario
    public $valores_id = '';
    public $fecha = '';
    public $comprobante = '';
    public $desde = '';
    public $hasta = '';
    public $interno = '';
    public $observaciones = '';

    protected $rules = [
        'valores_id' => 'required|exists:tes_valores,id',
        'fecha' => 'required|date',
        'comprobante' => 'required|string|max:50',
        'desde' => 'required|integer|min:1',
        'hasta' => 'required|integer|min:1',
        'interno' => 'nullable|string|max:50',
        'observaciones' => 'nullable|string|max:1000'
    ];

    protected $messages = [
        'valores_id.required' => 'El valor es obligatorio.',
        'valores_id.exists' => 'El valor seleccionado no es válido.',
        'fecha.required' => 'La fecha es obligatoria.',
        'fecha.date' => 'La fecha debe ser válida.',
        'comprobante.required' => 'El comprobante es obligatorio.',
        'comprobante.max' => 'El comprobante no puede superar los 50 caracteres.',
        'desde.required' => 'El número inicial es obligatorio.',
        'desde.integer' => 'El número inicial debe ser un entero.',
        'desde.min' => 'El número inicial debe ser mayor a 0.',
        'hasta.required' => 'El número final es obligatorio.',
        'hasta.integer' => 'El número final debe ser un entero.',
        'hasta.min' => 'El número final debe ser mayor a 0.',
        'interno.max' => 'El número interno no puede superar los 50 caracteres.',
        'observaciones.max' => 'Las observaciones no pueden superar los 1000 caracteres.'
    ];

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'entradaDeleted' => 'handleEntradaDeleted'
    ];

    public function mount()
    {
        $this->fecha = now()->format('Y-m-d');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterValor()
    {
        $this->resetPage();
    }

    public function updatingFilterFecha()
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
        $this->fecha = now()->format('Y-m-d');
        $this->showCreateModal = true;
        $this->dispatchBrowserEvent('show-create-edit-modal');
    }

    public function openEditModal($entradaId)
    {
        $entrada = ValorEntrada::findOrFail($entradaId);
        $this->selectedEntrada = $entrada;

        $this->valores_id = $entrada->valores_id;
        $this->fecha = $entrada->fecha->format('Y-m-d');
        $this->comprobante = $entrada->comprobante;
        $this->desde = $entrada->desde;
        $this->hasta = $entrada->hasta;
        $this->interno = $entrada->interno;
        $this->observaciones = $entrada->observaciones;

        $this->showEditModal = true;
        $this->dispatchBrowserEvent('show-create-edit-modal');
    }

    public function openDeleteModal($entradaId)
    {
        $this->selectedEntrada = ValorEntrada::findOrFail($entradaId);
        $this->showDeleteModal = true;
        $this->dispatchBrowserEvent('show-delete-modal');
    }

    public function openDetailModal($entradaId)
    {
        $this->selectedEntrada = ValorEntrada::with('valor')->findOrFail($entradaId);
        $this->showDetailModal = true;
        $this->dispatchBrowserEvent('show-detail-modal');
    }

    public function create()
    {
        $this->validate();

        // Validar que 'desde' sea menor o igual a 'hasta'
        if ($this->desde > $this->hasta) {
            $this->addError('hasta', 'El número final debe ser mayor o igual al número inicial.');
            return;
        }

        // Verificar solapamiento con entradas existentes
        $solapamiento = ValorEntrada::where('valores_id', $this->valores_id)
            ->where(function ($query) {
                $query->whereBetween('desde', [$this->desde, $this->hasta])
                    ->orWhereBetween('hasta', [$this->desde, $this->hasta])
                    ->orWhere(function ($q) {
                        $q->where('desde', '<=', $this->desde)
                            ->where('hasta', '>=', $this->hasta);
                    });
            })
            ->exists();

        if ($solapamiento) {
            $this->addError('desde', 'El rango de recibos se solapa con una entrada existente.');
            return;
        }

        ValorEntrada::create([
            'valores_id' => $this->valores_id,
            'fecha' => $this->fecha,
            'comprobante' => $this->comprobante,
            'desde' => $this->desde,
            'hasta' => $this->hasta,
            'interno' => $this->interno,
            'observaciones' => $this->observaciones
        ]);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->emit('alert', ['type' => 'success', 'message' => 'Entrada registrada exitosamente.']);
    }

    public function update()
    {
        $this->validate();

        // Validar que 'desde' sea menor o igual a 'hasta'
        if ($this->desde > $this->hasta) {
            $this->addError('hasta', 'El número final debe ser mayor o igual al número inicial.');
            return;
        }

        // Verificar solapamiento con otras entradas (excluyendo la actual)
        $solapamiento = ValorEntrada::where('valores_id', $this->valores_id)
            ->where('id', '!=', $this->selectedEntrada->id)
            ->where(function ($query) {
                $query->whereBetween('desde', [$this->desde, $this->hasta])
                    ->orWhereBetween('hasta', [$this->desde, $this->hasta])
                    ->orWhere(function ($q) {
                        $q->where('desde', '<=', $this->desde)
                            ->where('hasta', '>=', $this->hasta);
                    });
            })
            ->exists();

        if ($solapamiento) {
            $this->addError('desde', 'El rango de recibos se solapa con otra entrada existente.');
            return;
        }

        $this->selectedEntrada->update([
            'valores_id' => $this->valores_id,
            'fecha' => $this->fecha,
            'comprobante' => $this->comprobante,
            'desde' => $this->desde,
            'hasta' => $this->hasta,
            'interno' => $this->interno,
            'observaciones' => $this->observaciones
        ]);

        $this->showEditModal = false;
        $this->resetForm();
        $this->emit('alert', ['type' => 'success', 'message' => 'Entrada actualizada exitosamente.']);
    }

    public function delete()
    {
        // Verificar si hay salidas que dependan de esta entrada
        $valor = $this->selectedEntrada->valor;
        $stockSinEstaEntrada = $valor->entradas()
            ->where('id', '!=', $this->selectedEntrada->id)
            ->sum('total_recibos');
        $totalSalidas = $valor->salidas()->sum('total_recibos');

        if ($stockSinEstaEntrada < $totalSalidas) {
            $this->emit('alert', [
                'type' => 'error',
                'message' => 'No se puede eliminar la entrada porque causaría un stock negativo.'
            ]);
            $this->showDeleteModal = false;
            return;
        }

        $this->selectedEntrada->delete();
        $this->showDeleteModal = false;
        $this->emit('alert', ['type' => 'success', 'message' => 'Entrada eliminada exitosamente.']);
    }

    public function resetForm()
    {
        $this->valores_id = '';
        $this->fecha = '';
        $this->comprobante = '';
        $this->desde = '';
        $this->hasta = '';
        $this->interno = '';
        $this->observaciones = '';
        $this->selectedEntrada = null;
        $this->resetErrorBag();
    }

    public function handleEntradaDeleted()
    {
        $this->emit('alert', ['type' => 'success', 'message' => 'Entrada eliminada exitosamente.']);
    }

    public function render()
    {
        $entradas = ValorEntrada::with('valor')
            ->when($this->search, function ($query) {
                $query->where('comprobante', 'like', '%' . $this->search . '%')
                    ->orWhere('interno', 'like', '%' . $this->search . '%')
                    ->orWhere('observaciones', 'like', '%' . $this->search . '%')
                    ->orWhereHas('valor', function ($q) {
                        $q->where('nombre', 'like', '%' . $this->search . '%');
                    });
            })
            ->when($this->filterValor, function ($query) {
                $query->where('valores_id', $this->filterValor);
            })
            ->when($this->filterFecha, function ($query) {
                if ($this->filterFecha === 'hoy') {
                    $query->whereDate('fecha', today());
                } elseif ($this->filterFecha === 'semana') {
                    $query->whereBetween('fecha', [now()->startOfWeek(), now()->endOfWeek()]);
                } elseif ($this->filterFecha === 'mes') {
                    $query->whereMonth('fecha', now()->month)
                        ->whereYear('fecha', now()->year);
                }
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $valores = Valor::activos()->orderBy('nombre')->get();

        return view('livewire.tesoreria.valores.entradas', compact('entradas', 'valores'));
    }
}
