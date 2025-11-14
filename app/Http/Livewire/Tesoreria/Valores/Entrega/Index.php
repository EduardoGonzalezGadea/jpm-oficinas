<?php

namespace App\Http\Livewire\Tesoreria\Valores\Entrega;

use App\Models\Tesoreria\EntregaLibretaValor;
use App\Models\Tesoreria\LibretaValor;
use App\Models\Tesoreria\Servicio;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $showModal = false;
    public $showAnularModal = false;
    public $libreta_valor_id, $servicio_id, $numero_recibo_entrega, $fecha_entrega, $observaciones;
    public $entregaIdToAnular;

    public $search = '';
    public $fecha_desde = '';
    public $fecha_hasta = '';
    public $servicio_filtro = '';

    protected function rules()
    {
        return [
            'libreta_valor_id' => 'required|exists:tes_libretas_valores,id',
            'servicio_id' => 'required|exists:tes_servicios,id',
            'numero_recibo_entrega' => 'required|string|max:255|unique:tes_entregas_libretas_valores,numero_recibo_entrega',
            'fecha_entrega' => 'required|date|before_or_equal:today',
            'observaciones' => 'nullable|string|max:1000',
        ];
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['search', 'fecha_desde', 'fecha_hasta', 'servicio_filtro'])) {
            $this->resetPage();
        }
    }

    public function create()
    {
        $this->resetInput();
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        // Verificar que la libreta esté en stock
        $libreta = LibretaValor::find($this->libreta_valor_id);
        if ($libreta->estado !== 'en_stock') {
            $this->addError('libreta_valor_id', 'La libreta seleccionada no está disponible para entrega.');
            return;
        }

        // Verificar que el servicio esté activo
        $servicio = Servicio::find($this->servicio_id);
        if (!$servicio->activo) {
            $this->addError('servicio_id', 'El servicio seleccionado no está activo.');
            return;
        }

        // Crear la entrega
        $entrega = EntregaLibretaValor::create([
            'libreta_valor_id' => $this->libreta_valor_id,
            'servicio_id' => $this->servicio_id,
            'numero_recibo_entrega' => $this->numero_recibo_entrega,
            'fecha_entrega' => $this->fecha_entrega,
            'observaciones' => $this->observaciones,
            'estado' => 'activo',
        ]);

        // Actualizar estado de la libreta
        $libreta->update([
            'estado' => 'asignada',
            'servicio_asignado_id' => $this->servicio_id,
        ]);

        $this->dispatchBrowserEvent('swal', [
            'title' => 'Éxito',
            'text' => 'Entrega de libreta registrada correctamente.',
            'type' => 'success'
        ]);

        $this->showModal = false;
    }

    public function confirmAnular($id)
    {
        $this->entregaIdToAnular = $id;
        $this->showAnularModal = true;
    }

    public function anular()
    {
        $entrega = EntregaLibretaValor::find($this->entregaIdToAnular);
        $entrega->update(['estado' => 'anulado']);

        // Liberar la libreta
        $libreta = $entrega->libretaValor;
        $libreta->update([
            'estado' => 'en_stock',
            'servicio_asignado_id' => null,
        ]);

        $this->dispatchBrowserEvent('swal:success', ['text' => 'Entrega anulada correctamente.']);
        $this->showAnularModal = false;
        $this->entregaIdToAnular = null;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetInput();
    }

    public function closeAnularModal()
    {
        $this->showAnularModal = false;
        $this->entregaIdToAnular = null;
    }

    public function render()
    {
        $query = EntregaLibretaValor::with(['libretaValor.tipoLibreta', 'servicio'])
            ->where('estado', 'activo');

        // Aplicar filtros
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->whereHas('libretaValor', function ($subQuery) {
                    $subQuery->where('numero_inicial', 'like', '%' . $this->search . '%')
                            ->orWhere('numero_final', 'like', '%' . $this->search . '%')
                            ->orWhere('serie', 'like', '%' . $this->search . '%');
                })
                ->orWhere('numero_recibo_entrega', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->fecha_desde)) {
            $query->where('fecha_entrega', '>=', $this->fecha_desde);
        }

        if (!empty($this->fecha_hasta)) {
            $query->where('fecha_entrega', '<=', $this->fecha_hasta);
        }

        if (!empty($this->servicio_filtro)) {
            $query->where('servicio_id', $this->servicio_filtro);
        }

        $entregas = $query->orderBy('fecha_entrega', 'desc')->paginate(10);

        $libretasDisponibles = LibretaValor::where('estado', 'en_stock')
            ->with('tipoLibreta')
            ->orderBy('numero_inicial')
            ->get();

        $servicios = Servicio::where('activo', true)->orderBy('nombre')->get();

        return view('livewire.tesoreria.valores.entrega.index', compact('entregas', 'libretasDisponibles', 'servicios'))
            ->extends('layouts.app')
            ->section('content');
    }

    private function resetInput()
    {
        $this->libreta_valor_id = null;
        $this->servicio_id = null;
        $this->numero_recibo_entrega = '';
        $this->fecha_entrega = now()->format('Y-m-d');
        $this->observaciones = '';
    }
}
