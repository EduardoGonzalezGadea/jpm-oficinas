<?php

namespace App\Http\Livewire\Tesoreria\Valores;

use App\Models\Tesoreria\LibretaValor;
use App\Models\Tesoreria\TipoLibreta;
use App\Services\Tesoreria\ValoresService;
use App\Traits\ConvertirMayusculas;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination, ConvertirMayusculas;

    protected $paginationTheme = 'bootstrap';

    public $showModal = false;
    public $tipo_libreta_id, $serie, $numero_inicial, $fecha_recepcion;
    public $cantidad_libretas = 1;
    public $numero_final_calculado = '';

    public $showEntregaModal = false;
    public $libretaSeleccionada = null;
    public $servicio_entrega_id, $numero_recibo_entrega, $fecha_entrega, $observaciones_entrega;
    public $campoEnfoque = 'servicio_entrega_id'; // Campo que debe tener el foco

    public $search = '';
    public $estado = 'en_stock';
    public $selectedTipo = '';
    public $year;
    public $years = [];

    public function mount()
    {
        $this->year = date('Y');
        $this->loadYears();
    }

    public function loadYears()
    {
        $years = LibretaValor::selectRaw('YEAR(fecha_recepcion) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Agregar siempre el año actual si no está presente
        $currentYear = (int) date('Y');
        if (!in_array($currentYear, $years)) {
            $years[] = $currentYear;
        }

        // Ordenar descendentemente
        rsort($years);

        $this->years = collect($years);
    }

    protected function rules()
    {
        return [
            'tipo_libreta_id' => 'required|exists:tes_tipos_libretas,id',
            'serie' => 'nullable|string|max:255',
            'numero_inicial' => 'required|integer|min:1',
            'fecha_recepcion' => 'required|date',
            'cantidad_libretas' => 'required|integer|min:1',
        ];
    }

    protected function rulesEntrega()
    {
        return [
            'servicio_entrega_id' => 'required|exists:tes_servicios,id',
            'numero_recibo_entrega' => 'required|string|max:255',
            'fecha_entrega' => 'required|date|before_or_equal:today',
            'observaciones_entrega' => 'nullable|string|max:1000',
        ];
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['numero_inicial', 'cantidad_libretas', 'tipo_libreta_id'])) {
            $this->calcularNumeroFinal();
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingEstado()
    {
        $this->resetPage();
    }

    public function updatingSelectedTipo()
    {
        $this->resetPage();
    }

    public function updatingYear()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->estado = 'en_stock';
        $this->selectedTipo = '';
        $this->year = date('Y');
        $this->resetPage();
    }

    public function confirmDelete($id)
    {
        $this->dispatchBrowserEvent('swal:confirm', [
            'title' => '¿Eliminar Libreta?',
            'text' => 'Esta acción no se puede revertir.',
            'method' => 'delete',
            'id' => $id,
            'componentId' => $this->id,
        ]);
    }

    public function delete($id)
    {
        LibretaValor::find($id)->delete();
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Libreta eliminada correctamente.']);
    }

    public function entregarLibreta($libretaId)
    {
        $libreta = LibretaValor::find($libretaId);
        if ($libreta && $libreta->estado === 'en_stock') {
            $this->libretaSeleccionada = $libreta;
            $this->resetEntregaInput();
            $this->showEntregaModal = true;
            $this->dispatchBrowserEvent('modalOpened');
        }
    }

    public function updatedLibretaSeleccionada()
    {
        // Reset servicio selection when libreta changes
        $this->servicio_entrega_id = null;
    }

    public function registrarEntrega(ValoresService $valoresService)
    {
        $validatedData = $this->validate($this->rulesEntrega());

        // Convertir campos de texto a mayúsculas
        $validatedData = $this->convertirCamposAMayusculas(['numero_recibo_entrega', 'observaciones_entrega'], $validatedData);

        try {
            $valoresService->registrarEntrega($this->libretaSeleccionada, $validatedData);

            $this->dispatchBrowserEvent('swal', [
                'title' => 'Éxito',
                'text' => 'Entrega de libreta registrada correctamente.',
                'type' => 'success'
            ]);

            $this->showEntregaModal = false;
            $this->libretaSeleccionada = null;
        } catch (\Exception $e) {
            $this->addError('servicio_entrega_id', $e->getMessage());
        }
    }

    public function closeEntregaModal()
    {
        $this->showEntregaModal = false;
        $this->libretaSeleccionada = null;
        $this->resetEntregaInput();
    }


    private function resetEntregaInput()
    {
        $this->servicio_entrega_id = null;
        $this->numero_recibo_entrega = '';
        $this->fecha_entrega = now()->format('Y-m-d');
        $this->observaciones_entrega = '';
    }

    public function calcularNumeroFinal()
    {
        $numero_inicial = intval($this->numero_inicial);
        $cantidad_libretas = intval($this->cantidad_libretas);

        if (!$this->tipo_libreta_id || $numero_inicial < 1 || $cantidad_libretas < 1) {
            $this->numero_final_calculado = '';
            return;
        }

        $tipoLibreta = TipoLibreta::find($this->tipo_libreta_id);
        if ($tipoLibreta) {
            $totalRecibos = $cantidad_libretas * $tipoLibreta->cantidad_recibos;
            $this->numero_final_calculado = $numero_inicial + $totalRecibos - 1;
        }
    }

    public function render()
    {
        $query = LibretaValor::with('tipoLibreta');

        // Aplicar filtros de búsqueda
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->whereHas('tipoLibreta', function ($subQuery) {
                    $subQuery->where('nombre', 'like', '%' . $this->search . '%');
                })
                    ->orWhere('serie', 'like', '%' . $this->search . '%')
                    ->orWhere('numero_inicial', 'like', '%' . $this->search . '%')
                    ->orWhere('numero_final', 'like', '%' . $this->search . '%');
            });
        }

        // Aplicar filtro de estado
        if (!empty($this->estado)) {
            if ($this->estado === 'agotada') {
                $query->where(function ($q) {
                    $q->whereIn('estado', ['agotada', 'finalizada'])
                        ->orWhere('proximo_recibo_disponible', 0);
                });
            } else {
                $query->where('estado', $this->estado);
            }
        }

        // Aplicar filtro de tipo
        if (!empty($this->selectedTipo)) {
            $query->where('tipo_libreta_id', $this->selectedTipo);
        }

        // Aplicar filtro de año (excepto si el estado es en_stock)
        if ($this->estado !== 'en_stock') {
            $query->whereYear('fecha_recepcion', $this->year);
        }

        $libretas = $query->join('tes_tipos_libretas', 'tes_libretas_valores.tipo_libreta_id', '=', 'tes_tipos_libretas.id')
            ->select('tes_libretas_valores.*')
            ->orderBy('tes_tipos_libretas.nombre', 'asc')
            ->orderBy('tes_libretas_valores.fecha_recepcion', 'asc')
            ->orderBy('tes_libretas_valores.serie', 'asc')
            ->orderBy('tes_libretas_valores.numero_inicial', 'asc')
            ->paginate(10);

        $tiposLibreta = Cache::remember('tipos_libreta_all', now()->addDay(), function () {
            return TipoLibreta::orderBy('nombre')->get();
        });

        // Si hay una libreta seleccionada, obtener solo los servicios asociados a su tipo
        $servicios = [];
        if ($this->libretaSeleccionada) {
            $servicios = $this->libretaSeleccionada->tipoLibreta->servicios()->where('activo', true)->orderBy('nombre')->get();

            // Si hay solo un servicio asociado, preseleccionarlo y enfocar en número de recibo
            if ($servicios->count() === 1 && !$this->servicio_entrega_id) {
                $this->servicio_entrega_id = $servicios->first()->id;
                $this->campoEnfoque = 'numero_recibo_entrega';
            } else {
                // Si hay múltiples servicios, enfocar en el selector de servicios
                $this->campoEnfoque = 'servicio_entrega_id';
            }
        } else {
            $servicios = Cache::remember('servicios_activos_all', now()->addDay(), function () {
                return \App\Models\Tesoreria\Servicio::where('activo', true)->orderBy('nombre')->get();
            });
            $this->campoEnfoque = 'servicio_entrega_id';
        }

        return view('livewire.tesoreria.valores.index', compact('libretas', 'tiposLibreta', 'servicios'))
            ->extends('layouts.app')
            ->section('content');
    }

    public function create()
    {
        $this->resetInput();
        $this->showModal = true;
    }

    public function save(ValoresService $valoresService)
    {
        $validatedData = $this->validate();

        // Convertir campos de texto a mayúsculas
        $validatedData = $this->convertirCamposAMayusculas(['serie'], $validatedData);

        try {
            $valoresService->crearLibretas($validatedData);

            $this->dispatchBrowserEvent('swal', [
                'title' => 'Éxito',
                'text' => $this->cantidad_libretas . ' libreta(s) de valores registrada(s) correctamente.',
                'type' => 'success'
            ]);

            $this->showModal = false;
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal', [
                'title' => 'Error',
                'text' => 'Ocurrió un error al registrar las libretas: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetInput();
    }

    private function resetInput()
    {
        $this->tipo_libreta_id = null;
        $this->serie = '';
        $this->numero_inicial = '';
        $this->fecha_recepcion = now()->format('Y-m-d');
        $this->cantidad_libretas = 1;
        $this->numero_final_calculado = '';
    }
}
