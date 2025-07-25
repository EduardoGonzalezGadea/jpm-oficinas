<?php
// app/Http/Livewire/Tesoreria/CajaChica/ModalNuevoFondo.php

namespace App\Http\Livewire\Tesoreria\CajaChica;

use Livewire\Component;
use App\Models\Tesoreria\CajaChica;

class ModalNuevoFondo extends Component
{
    public $mes;
    public $anio;
    public $monto;
    public $mostrarModal = false;

    protected $rules = [
        'mes' => 'required|string|max:20',
        'anio' => 'required|integer',
        'monto' => 'required|numeric|min:0',
    ];

    protected $messages = [
        'mes.required' => 'El mes es obligatorio.',
        'anio.required' => 'El año es obligatorio.',
        'anio.integer' => 'El año debe ser un número entero.',
        'monto.required' => 'El monto es obligatorio.',
        'monto.numeric' => 'El monto debe ser un número.',
        'monto.min' => 'El monto debe ser mayor o igual a cero.',
    ];

    protected $listeners = [
        'mostrarModalNuevoFondo' => 'abrirModal',
        'cerrarModalNuevoFondo' => 'cerrarModal',
    ];

    public function mount()
    {
        // Valores por defecto o vacíos
        $this->mes = '';
        $this->anio = date('Y');
        $this->monto = '';
    }

    public function abrirModal()
    {
        // Los datos se precargan desde Index.php si es necesario
        // o se pueden establecer aquí valores por defecto
        // $this->mes = now()->locale('es_ES')->isoFormat('MMMM');
        // $this->anio = now()->year;
        // $this->monto = '350000';

        $this->mostrarModal = true;
        $this->dispatchBrowserEvent('actualizar-modal-fondo', ['mostrar' => true]);
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->reset(['mes', 'anio', 'monto']);
        $this->resetErrorBag();
        $this->dispatchBrowserEvent('actualizar-modal-fondo', ['mostrar' => false]);
    }

    public function guardar()
    {
        $this->validate();

        $existe = CajaChica::where('mes', $this->mes)
            ->where('anio', $this->anio)
            ->exists();

        if ($existe) {
            // Usar addError para mostrar el error en el campo específico o un mensaje general
            // $this->addError('mes', 'Ya existe un Fondo Permanente para este mes y año.');
            session()->flash('error', 'Ya existe un Fondo Permanente para este mes y año.');
            // Emitir un evento para que el Index pueda mostrar el error si es necesario
            // o simplemente dejar que se muestre en el modal.
            // $this->emit('fondoDuplicado');
        } else {
            CajaChica::create([
                'mes' => $this->mes,
                'anio' => $this->anio,
                'montoCajaChica' => $this->monto,
            ]);

            session()->flash('message', 'Fondo Permanente creado correctamente.');
            $this->cerrarModal();
            // Notificar al componente principal para recargar datos
            $this->emitTo('tesoreria.caja-chica.index', 'fondoCreado');
        }
    }

    public function render()
    {
        return view('livewire.tesoreria.caja-chica.modal-nuevo-fondo');
    }
}
