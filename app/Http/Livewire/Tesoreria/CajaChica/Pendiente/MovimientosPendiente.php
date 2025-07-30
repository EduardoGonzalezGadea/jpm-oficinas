<?php

namespace App\Http\Livewire\Tesoreria\CajaChica\Pendiente;

use Livewire\Component;
use App\Models\Tesoreria\Pendiente;
use App\Models\Tesoreria\Movimiento;
use Carbon\Carbon;

class MovimientosPendiente extends Component
{
    public $pendiente;
    public $movimientos;

    // Variables para el modal de creación/edición
    public $showModal = false;
    public $editMode = false;
    public $movimientoId;

    // Campos del formulario
    public $fechaMovimientos;
    public $documentos;
    public $rendido = 0;
    public $reintegrado = 0;
    public $recuperado = 0;

    // Variables para confirmación de eliminación
    public $movimientoAEliminar = null;
    public $showDeleteModal = false;

    // Variables para loading states
    public $loading = false;

    protected $rules = [
        'fechaMovimientos' => 'required|date|before_or_equal:today',
        'documentos' => 'nullable|string|max:255',
        'rendido' => 'required|numeric|min:0',
        'reintegrado' => 'nullable|numeric|min:0',
        'recuperado' => 'nullable|numeric|min:0',
    ];

    protected $messages = [
        'fechaMovimientos.required' => 'La fecha es obligatoria.',
        'fechaMovimientos.date' => 'Debe ser una fecha válida.',
        'fechaMovimientos.before_or_equal' => 'La fecha no puede ser futura.',
        'rendido.required' => 'El monto rendido es obligatorio.',
        'rendido.numeric' => 'El monto rendido debe ser numérico.',
        'rendido.min' => 'El monto rendido no puede ser negativo.',
        'reintegrado.numeric' => 'El monto reintegrado debe ser numérico.',
        'reintegrado.min' => 'El monto reintegrado no puede ser negativo.',
        'recuperado.numeric' => 'El monto recuperado debe ser numérico.',
        'recuperado.min' => 'El monto recuperado no puede ser negativo.',
    ];

    public function mount($id)
    {
        $this->pendiente = Pendiente::findOrFail($id);
        $this->cargarMovimientos();
        $this->fechaMovimientos = Carbon::now()->format('Y-m-d');
    }

    public function render()
    {
        return view('livewire.tesoreria.caja-chica.pendiente.movimientos-pendiente');
    }

    public function cargarMovimientos()
    {
        $this->movimientos = $this->pendiente->movimientos()
            ->orderBy('fechaMovimientos', 'desc')
            ->get();
    }

    public function abrirModalCrear()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
        $this->fechaMovimientos = Carbon::now()->format('Y-m-d');
        $this->resetErrorBag();

        // Emisión de evento para focus en el primer campo
        $this->dispatchBrowserEvent('modal-opened');
    }

    public function abrirModalEditar($id)
    {
        $movimiento = Movimiento::findOrFail($id);

        $this->movimientoId = $id;
        $this->fechaMovimientos = Carbon::parse($movimiento->fechaMovimientos)->format('Y-m-d');
        $this->documentos = $movimiento->documentos;
        $this->rendido = $movimiento->rendido;
        $this->reintegrado = $movimiento->reintegrado;
        $this->recuperado = $movimiento->recuperado;

        $this->editMode = true;
        $this->showModal = true;
        $this->resetErrorBag();

        $this->dispatchBrowserEvent('modal-opened');
    }

    public function guardarMovimiento()
    {
        $this->loading = true;

        // Validación básica de Livewire
        $this->validate();

        // Regla 1: El monto reintegrado nunca puede ser mayor que el monto del pendiente.
        if ($this->reintegrado > $this->pendiente->montoPendientes) {
            $this->addError('reintegrado', 'El monto reintegrado no puede superar el monto total del pendiente.');
            $this->loading = false;
            return;
        }

        // Regla 2: Si el monto reintegrado es mayor a cero, la suma de rendido + reintegrado no puede ser mayor al monto del pendiente.
        if ($this->reintegrado > 0 && ($this->rendido + $this->reintegrado) > $this->pendiente->montoPendientes) {
            $this->addError('rendido', 'Si hay reintegro, la suma de Rendido y Reintegrado no puede superar el monto del pendiente.');
            $this->addError('reintegrado', 'Si hay reintegro, la suma de Rendido y Reintegrado no puede superar el monto del pendiente.');
            $this->loading = false;
            return;
        }

        // Regla 4: El monto recuperado nunca puede ser mayor que el monto rendido.
        if ($this->recuperado > $this->rendido) {
            $this->addError('recuperado', 'El monto recuperado no puede ser mayor que el monto rendido.');
            $this->loading = false;
            return;
        }

        try {
            if ($this->editMode) {
                $movimiento = Movimiento::findOrFail($this->movimientoId);
                $mensaje = 'Movimiento actualizado exitosamente.';
            } else {
                $movimiento = new Movimiento();
                $movimiento->relPendiente = $this->pendiente->idPendientes;
                $mensaje = 'Movimiento creado exitosamente.';
            }

            $movimiento->fechaMovimientos = $this->fechaMovimientos;
            $movimiento->documentos = $this->documentos;
            $movimiento->rendido = $this->rendido ?: 0;
            $movimiento->reintegrado = $this->reintegrado ?: 0;
            $movimiento->recuperado = $this->recuperado ?: 0;

            $movimiento->save();

            $this->cargarMovimientos();
            $this->pendiente->refresh();
            $this->emit('movimientoActualizado');
            $this->cerrarModal();

            session()->flash('success', $mensaje);

            // Evento para notificación toast
            $this->dispatchBrowserEvent('show-toast', [
                'type' => 'success',
                'message' => $mensaje
            ]);
        } catch (\Exception $e) {
            $error = 'Error al guardar el movimiento: ' . $e->getMessage();
            session()->flash('error', $error);

            $this->dispatchBrowserEvent('show-toast', [
                'type' => 'error',
                'message' => $error
            ]);
        } finally {
            $this->loading = false;
        }
    }

    public function eliminarMovimiento($id)
    {
        try {
            $movimiento = Movimiento::findOrFail($id);
            $movimiento->delete();

            $this->cargarMovimientos();
            $this->pendiente->refresh();
            $this->emit('movimientoActualizado');

            $mensaje = 'Movimiento eliminado exitosamente.';
            session()->flash('success', $mensaje);

            $this->dispatchBrowserEvent('show-toast', [
                'type' => 'success',
                'message' => $mensaje
            ]);
        } catch (\Exception $e) {
            $error = 'Error al eliminar el movimiento: ' . $e->getMessage();
            session()->flash('error', $error);

            $this->dispatchBrowserEvent('show-toast', [
                'type' => 'error',
                'message' => $error
            ]);
        }
    }

    public function cerrarModal()
    {
        $this->showModal = false;
        $this->resetForm();
        $this->resetErrorBag();
        $this->loading = false;
    }

    private function resetForm()
    {
        $this->movimientoId = null;
        $this->fechaMovimientos = Carbon::now()->format('Y-m-d');
        $this->documentos = null;
        $this->rendido = 0;
        $this->reintegrado = 0;
        $this->recuperado = 0;
        $this->editMode = false;
    }

    public function confirmarEliminacion($id)
    {
        $this->movimientoAEliminar = $id;
        $this->showDeleteModal = true;
    }

    public function cancelarEliminacion()
    {
        $this->movimientoAEliminar = null;
        $this->showDeleteModal = false;
    }

    public function confirmarEliminarMovimiento()
    {
        if ($this->movimientoAEliminar) {
            $this->eliminarMovimiento($this->movimientoAEliminar);
            $this->cancelarEliminacion();
        }
    }

    // Métodos para validación en tiempo real
    public function updatedRendido()
    {
        $this->validateOnly('rendido');
    }

    public function updatedReintegrado()
    {
        $this->validateOnly('reintegrado');
    }

    public function updatedRecuperado()
    {
        $this->validateOnly('recuperado');
    }

    public function updatedFechaMovimientos()
    {
        $this->validateOnly('fechaMovimientos');
    }

    // Método para obtener el balance del pendiente
    public function getBalancePendiente()
    {
        $montoPendiente = $this->pendiente->montoPendientes;
        $totalRendido = $this->movimientos->sum('rendido');
        $totalReintegrado = $this->movimientos->sum('reintegrado');
        $totalRecuperado = $this->movimientos->sum('recuperado');

        $saldo = 0;
        $totalRendidoReintegrado = $totalRendido + $totalReintegrado;

        if ($totalRendidoReintegrado > $montoPendiente) {
            $saldo = $totalRendido - $totalRecuperado;
        } else {
            $saldo = $montoPendiente - ($totalReintegrado + $totalRecuperado);
        }

        return [
            'total_rendido' => $totalRendido,
            'total_reintegrado' => $totalReintegrado,
            'total_recuperado' => $totalRecuperado,
            'saldo' => $saldo
        ];
    }
}
