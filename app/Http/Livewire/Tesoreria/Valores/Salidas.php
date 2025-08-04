<?php

namespace App\Http\Livewire\Tesoreria\Valores;

use App\Models\Tesoreria\Valores\Valor;
use App\Models\Tesoreria\Valores\ValorConcepto;
use App\Models\Tesoreria\Valores\ValorSalida;
use App\Models\Tesoreria\Valores\ValorEntrada;
use Livewire\Component;
use Livewire\WithPagination;

class Salidas extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $filterValor = '';
    public $filterConcepto = '';
    public $filterFecha = '';
    public $sortField = 'fecha';
    public $sortDirection = 'desc';
    public $perPage = 10;

    public $showCreateModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;
    public $showDetailModal = false;

    public $selectedSalida;

    // Campos del formulario
    public $valores_id = '';
    public $conceptos_id = '';
    public $fecha = '';
    public $comprobante = '';
    public $desde = '';
    public $hasta = '';
    public $interno = '';
    public $responsable = '';
    public $observaciones = '';

    // Para cargar conceptos dinámicamente
    public $conceptosDisponibles = [];
    public $stockDisponible = 0;
    public $rangosSugeridos = [];

    protected $rules = [
        'valores_id' => 'required|exists:tes_valores,id',
        'conceptos_id' => 'required|exists:tes_val_conceptos,id',
        'fecha' => 'required|date',
        'comprobante' => 'required|string|max:50',
        'desde' => 'required|integer|min:1',
        'hasta' => 'required|integer|min:1',
        'interno' => 'nullable|string|max:50',
        'responsable' => 'nullable|string|max:100',
        'observaciones' => 'nullable|string|max:1000'
    ];

    protected $messages = [
        'valores_id.required' => 'El valor es obligatorio.',
        'valores_id.exists' => 'El valor seleccionado no es válido.',
        'conceptos_id.required' => 'El concepto es obligatorio.',
        'conceptos_id.exists' => 'El concepto seleccionado no es válido.',
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
        'responsable.max' => 'El responsable no puede superar los 100 caracteres.',
        'observaciones.max' => 'Las observaciones no pueden superar los 1000 caracteres.'
    ];

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'salidaDeleted' => 'handleSalidaDeleted'
    ];

    public function mount()
    {
        $this->fecha = now()->format('Y-m-d');
    }

    public function updatedValoresId()
    {
        $this->conceptos_id = '';
        $this->conceptosDisponibles = [];
        $this->stockDisponible = 0;
        $this->rangosSugeridos = [];

        if ($this->valores_id) {
            $this->cargarConceptos();
            $this->calcularStockDisponible();
            $this->generarRangosSugeridos();
        }
    }

    public function updatedConceptosId()
    {
        // Lógica adicional si es necesaria cuando cambia el concepto
    }

    private function cargarConceptos()
    {
        $this->conceptosDisponibles = ValorConcepto::where('valores_id', $this->valores_id)
            ->where('activo', true)
            ->orderBy('concepto')
            ->get();
    }

    private function calcularStockDisponible()
    {
        if (!$this->valores_id) return;

        $valor = Valor::find($this->valores_id);
        if ($valor) {
            $this->stockDisponible = $valor->getStockDisponible();
        }
    }

    private function generarRangosSugeridos()
    {
        if (!$this->valores_id) return;

        $valor = Valor::find($this->valores_id);
        if (!$valor) return;

        // Obtener entradas disponibles (que no han sido completamente asignadas)
        $entradas = ValorEntrada::where('valores_id', $this->valores_id)
            ->orderBy('desde')
            ->get();

        $salidas = ValorSalida::where('valores_id', $this->valores_id)
            ->orderBy('desde')
            ->get();

        $rangosDisponibles = [];

        foreach ($entradas as $entrada) {
            $inicioDisponible = $entrada->desde;
            $finDisponible = $entrada->hasta;

            // Verificar qué parte de esta entrada ya fue asignada
            foreach ($salidas as $salida) {
                if ($salida->desde >= $entrada->desde && $salida->hasta <= $entrada->hasta) {
                    // Esta salida está completamente dentro de la entrada
                    if ($salida->desde <= $inicioDisponible && $salida->hasta >= $inicioDisponible) {
                        $inicioDisponible = $salida->hasta + 1;
                    }
                }
            }

            if ($inicioDisponible <= $finDisponible) {
                $cantidadDisponible = $finDisponible - $inicioDisponible + 1;
                if ($cantidadDisponible > 0) {
                    $rangosDisponibles[] = [
                        'desde' => $inicioDisponible,
                        'hasta' => $finDisponible,
                        'cantidad' => $cantidadDisponible,
                        'interno' => $entrada->interno
                    ];
                }
            }
        }

        $this->rangosSugeridos = $rangosDisponibles;
    }

    public function aplicarRangoSugerido($desde, $hasta, $interno = null)
    {
        $this->desde = $desde;
        $this->hasta = $hasta;
        $this->interno = $interno;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterValor()
    {
        $this->resetPage();
    }

    public function updatingFilterConcepto()
    {
        $this->resetPage();
    }

    public function updatingFilterFecha()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'filterValor', 'filterConcepto', 'filterFecha', 'perPage']);
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
        $this->dispatchBrowserEvent('show-modal', ['id' => 'createEditModal']);
    }

    public function openEditModal($salidaId)
    {
        $salida = ValorSalida::with(['valor', 'concepto'])->findOrFail($salidaId);
        $this->selectedSalida = $salida;

        $this->valores_id = $salida->valores_id;
        $this->cargarConceptos();
        $this->conceptos_id = $salida->conceptos_id;
        $this->fecha = $salida->fecha->format('Y-m-d');
        $this->comprobante = $salida->comprobante;
        $this->desde = $salida->desde;
        $this->hasta = $salida->hasta;
        $this->interno = $salida->interno;
        $this->responsable = $salida->responsable;
        $this->observaciones = $salida->observaciones;

        $this->showEditModal = true;
        $this->dispatchBrowserEvent('show-modal', ['id' => 'createEditModal']);
    }

    public function openDeleteModal($salidaId)
    {
        $this->selectedSalida = ValorSalida::with(['valor', 'concepto'])->findOrFail($salidaId);
        $this->showDeleteModal = true;
        $this->dispatchBrowserEvent('show-modal', ['id' => 'deleteModal']);
    }

    public function openDetailModal($salidaId)
    {
        $this->selectedSalida = ValorSalida::with(['valor', 'concepto'])->findOrFail($salidaId);
        $this->showDetailModal = true;
        $this->dispatchBrowserEvent('show-modal', ['id' => 'detailModal']);
    }

    public function create()
    {
        $this->validate();

        // Validar que 'desde' sea menor o igual a 'hasta'
        if ($this->desde > $this->hasta) {
            $this->addError('hasta', 'El número final debe ser mayor o igual al número inicial.');
            return;
        }

        // Validar que el concepto pertenezca al valor seleccionado
        $concepto = ValorConcepto::where('id', $this->conceptos_id)
            ->where('valores_id', $this->valores_id)
            ->first();

        if (!$concepto) {
            $this->addError('conceptos_id', 'El concepto no pertenece al valor seleccionado.');
            return;
        }

        // Validar que hay stock suficiente
        $cantidadSolicita = ($this->hasta - $this->desde) + 1;
        if ($cantidadSolicita > $this->stockDisponible) {
            $this->addError('hasta', 'No hay stock suficiente. Disponible: ' . number_format($this->stockDisponible));
            return;
        }

        // Verificar que el rango esté dentro de las entradas existentes
        $rangoValido = ValorEntrada::where('valores_id', $this->valores_id)
            ->where('desde', '<=', $this->desde)
            ->where('hasta', '>=', $this->hasta)
            ->exists();

        if (!$rangoValido) {
            $this->addError('desde', 'El rango especificado no está dentro de las entradas disponibles.');
            return;
        }

        // Verificar solapamiento con salidas existentes
        $solapamiento = ValorSalida::where('valores_id', $this->valores_id)
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
            $this->addError('desde', 'El rango de recibos se solapa con una salida existente.');
            return;
        }

        ValorSalida::create([
            'valores_id' => $this->valores_id,
            'conceptos_id' => $this->conceptos_id,
            'fecha' => $this->fecha,
            'comprobante' => $this->comprobante,
            'desde' => $this->desde,
            'hasta' => $this->hasta,
            'interno' => $this->interno,
            'responsable' => $this->responsable,
            'observaciones' => $this->observaciones
        ]);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatchBrowserEvent('hide-modal', ['id' => 'createEditModal']);
        $this->emit('alert', ['type' => 'success', 'message' => 'Salida registrada exitosamente.']);
    }

    public function update()
    {
        $this->validate();

        // Validaciones similares al create...
        if ($this->desde > $this->hasta) {
            $this->addError('hasta', 'El número final debe ser mayor o igual al número inicial.');
            return;
        }

        $concepto = ValorConcepto::where('id', $this->conceptos_id)
            ->where('valores_id', $this->valores_id)
            ->first();

        if (!$concepto) {
            $this->addError('conceptos_id', 'El concepto no pertenece al valor seleccionado.');
            return;
        }

        // Verificar solapamiento excluyendo el registro actual
        $solapamiento = ValorSalida::where('valores_id', $this->valores_id)
            ->where('id', '!=', $this->selectedSalida->id)
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
            $this->addError('desde', 'El rango de recibos se solapa con otra salida existente.');
            return;
        }

        $this->selectedSalida->update([
            'valores_id' => $this->valores_id,
            'conceptos_id' => $this->conceptos_id,
            'fecha' => $this->fecha,
            'comprobante' => $this->comprobante,
            'desde' => $this->desde,
            'hasta' => $this->hasta,
            'interno' => $this->interno,
            'responsable' => $this->responsable,
            'observaciones' => $this->observaciones
        ]);

        $this->showEditModal = false;
        $this->resetForm();
        $this->dispatchBrowserEvent('hide-modal', ['id' => 'createEditModal']);
        $this->emit('alert', ['type' => 'success', 'message' => 'Salida actualizada exitosamente.']);
    }

    public function delete()
    {
        // Actualizar el registro de uso correspondiente
        $uso = \App\Models\Tesoreria\Valores\ValorUso::where('conceptos_id', $this->selectedSalida->conceptos_id)
            ->where('desde', $this->selectedSalida->desde)
            ->where('hasta', $this->selectedSalida->hasta)
            ->first();

        if ($uso) {
            $uso->delete();
        }

        $this->selectedSalida->delete();
        $this->showDeleteModal = false;
        $this->dispatchBrowserEvent('hide-modal', ['id' => 'deleteModal']);
        $this->emit('alert', ['type' => 'success', 'message' => 'Salida eliminada exitosamente.']);
    }

    public function resetForm()
    {
        $this->valores_id = '';
        $this->conceptos_id = '';
        $this->fecha = '';
        $this->comprobante = '';
        $this->desde = '';
        $this->hasta = '';
        $this->interno = '';
        $this->responsable = '';
        $this->observaciones = '';
        $this->selectedSalida = null;
        $this->conceptosDisponibles = [];
        $this->stockDisponible = 0;
        $this->rangosSugeridos = [];
        $this->resetErrorBag();
    }

    public function handleSalidaDeleted()
    {
        $this->emit('alert', ['type' => 'success', 'message' => 'Salida eliminada exitosamente.']);
    }

    public function render()
    {
        $salidas = ValorSalida::with(['valor', 'concepto'])
            ->when($this->search, function ($query) {
                $query->where('comprobante', 'like', '%' . $this->search . '%')
                    ->orWhere('interno', 'like', '%' . $this->search . '%')
                    ->orWhere('responsable', 'like', '%' . $this->search . '%')
                    ->orWhere('observaciones', 'like', '%' . $this->search . '%')
                    ->orWhereHas('valor', function ($q) {
                        $q->where('nombre', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('concepto', function ($q) {
                        $q->where('concepto', 'like', '%' . $this->search . '%');
                    });
            })
            ->when($this->filterValor, function ($query) {
                $query->where('valores_id', $this->filterValor);
            })
            ->when($this->filterConcepto, function ($query) {
                $query->where('conceptos_id', $this->filterConcepto);
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
        $conceptos = ValorConcepto::activos()
            ->with('valor')
            ->orderBy('concepto')
            ->get();

        return view('livewire.tesoreria.valores.salidas', compact('salidas', 'valores', 'conceptos'));
    }
}
