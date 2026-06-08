<?php

namespace App\Http\Livewire\Tesoreria\CajaChica\Modales;

use Livewire\Component;
use App\Models\Tesoreria\CajaChica;

class ModalRecuperarSaldos extends Component
{
    public $showModal = false;

    public $cajaChicaSeleccionadaId;
    public $mesActual;
    public $anioActual;

    public $recuperacion = [
        'fecha' => '',
        'numero_ingreso' => ''
    ];
    public $itemsParaRecuperar = [];
    public $itemsSeleccionados = [];
    public $totalARecuperar = 0.00;
    public $seleccionarTodos = false;

    protected $listeners = ['abrirModalRecuperar' => 'abrirModal'];

    protected function rules()
    {
        return (new \App\Http\Requests\Tesoreria\CajaChica\RecuperarFondosRequest())->rules();
    }

    protected function messages()
    {
        return (new \App\Http\Requests\Tesoreria\CajaChica\RecuperarFondosRequest())->messages();
    }

    public function abrirModal($cajaChicaId, $mes, $anio)
    {
        $this->cajaChicaSeleccionadaId = $cajaChicaId;
        $this->mesActual = $mes;
        $this->anioActual = $anio;

        $this->reset(['itemsParaRecuperar', 'itemsSeleccionados', 'totalARecuperar', 'seleccionarTodos']);
        $this->resetErrorBag();
        
        $this->recuperacion['fecha'] = now()->format('Y-m-d');
        $this->recuperacion['numero_ingreso'] = '';

        $fechaRecuperacionActual = now()->endOfDay()->toDateTimeString();
        $service = app(\App\Services\Tesoreria\CajaChicaService::class);
        $cajaChica = CajaChica::find($cajaChicaId);

        $items = $service->obtenerElementosParaRecuperar($cajaChica, $this->mesActual, $this->anioActual, $fechaRecuperacionActual);

        if ($items->isEmpty()) {
            $this->emit('mostrarAlerta', ['icon' => 'success', 'text' => 'No hay saldos pendientes de recuperar para el período y fecha seleccionados.', 'toast' => true, 'position' => 'top-end', 'timer' => 5000]);
            return;
        }
        
        $this->itemsParaRecuperar = $items->toArray();
        $this->showModal = true;
    }

    public function updatedSeleccionarTodos($value)
    {
        if ($value) {
            $this->itemsSeleccionados = collect($this->itemsParaRecuperar)->pluck('id')->toArray();
        } else {
            $this->itemsSeleccionados = [];
        }
        $this->recalcularTotal();
    }

    public function updatedItemsSeleccionados()
    {
        if (count($this->itemsSeleccionados) === count($this->itemsParaRecuperar) && count($this->itemsParaRecuperar) > 0) {
            $this->seleccionarTodos = true;
        } else {
            $this->seleccionarTodos = false;
        }
        $this->recalcularTotal();
    }

    public function recalcularTotal()
    {
        $this->totalARecuperar = collect($this->itemsParaRecuperar)
            ->whereIn('id', $this->itemsSeleccionados)
            ->sum('saldo');
    }

    public function guardarRecuperacion()
    {
        $this->validate([
            'recuperacion.fecha' => 'required|date',
            'recuperacion.numero_ingreso' => 'required|string|max:50',
            'itemsSeleccionados' => 'required|array|min:1',
        ], [
            'recuperacion.fecha.required' => 'La fecha es obligatoria.',
            'recuperacion.numero_ingreso.required' => 'El número de ingreso es obligatorio.',
            'itemsSeleccionados.min' => 'Debe seleccionar al menos un ítem.',
        ]);

        try {
            $service = app(\App\Services\Tesoreria\CajaChicaService::class);
            $service->guardarRecuperacion(
                $this->recuperacion['fecha'],
                $this->recuperacion['numero_ingreso'],
                $this->itemsSeleccionados,
                $this->itemsParaRecuperar
            );

            $this->cerrarModal();
            $this->emit('fondoActualizado'); // Trigger refresh in parent
            $this->emit('mostrarAlerta', ['icon' => 'success', 'text' => 'Recuperación guardada exitosamente.', 'toast' => true, 'position' => 'top-end', 'timer' => 5000]);
            
        } catch (\Exception $e) {
            $this->emit('mostrarAlerta', ['icon' => 'error', 'text' => 'Error al guardar la recuperación: ' . $e->getMessage(), 'toast' => true, 'position' => 'top-end', 'timer' => 5000]);
        }
    }

    public function cerrarModal()
    {
        $this->showModal = false;
        $this->reset(['recuperacion', 'itemsParaRecuperar', 'itemsSeleccionados', 'totalARecuperar', 'seleccionarTodos']);
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.tesoreria.caja-chica.modales.modal-recuperar-saldos');
    }
}
