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
    public $isRecovering = false; // Nueva propiedad para el modo de recuperación

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

        // Definir reglas de validación condicionales
        if ($this->isRecovering) {
            $rules = [
                'fechaMovimientos' => 'required|date|before_or_equal:today',
                'documentos' => 'nullable|string|max:255',
                'recuperado' => 'required|numeric|min:0.01', // Debe ser un monto positivo
            ];
            $messages = [
                'recuperado.required' => 'El monto a recuperar es obligatorio.',
                'recuperado.numeric' => 'El monto a recuperar debe ser numérico.',
                'recuperado.min' => 'El monto a recuperar debe ser mayor a 0.',
            ];
            $this->validate($rules, $messages);

            // Asegurarse de que rendido y reintegrado sean 0 en modo recuperación
            $this->rendido = 0;
            $this->reintegrado = 0;

        } else {
            $this->validate(); // Usar reglas por defecto
        }

        // Totales existentes (excluyendo el movimiento actual si se está editando)
        $totalRendido = $this->pendiente->movimientos()
            ->when($this->editMode, fn($q) => $q->where('idMovimientos', '!=', $this->movimientoId))
            ->sum('rendido');

        $totalReintegrado = $this->pendiente->movimientos()
            ->when($this->editMode, fn($q) => $q->where('idMovimientos', '!=', $this->movimientoId))
            ->sum('reintegrado');

        $totalRecuperado = $this->pendiente->movimientos()
            ->when($this->editMode, fn($q) => $q->where('idMovimientos', '!=', $this->movimientoId))
            ->sum('recuperado');

        // Nuevos totales propuestos con los valores del formulario
        $nuevoTotalRendido = $totalRendido + ($this->rendido ?: 0);
        $nuevoTotalReintegrado = $totalReintegrado + ($this->reintegrado ?: 0);
        $nuevoTotalRecuperado = $totalRecuperado + ($this->recuperado ?: 0);

        // Regla 1: El total recuperado no puede exceder el total rendido del pendiente.
        // En modo recuperación, el rendido del movimiento actual es 0, pero la validación es sobre el total del pendiente.
        if ($nuevoTotalRecuperado > $this->getBalancePendiente()['total_rendido']) {
            $this->addError('recuperado', 'El monto total recuperado no puede ser mayor que el monto total rendido del pendiente.');
            $this->loading = false;
            return;
        }

        // Regla 2: La suma del total rendido y el total reintegrado no puede superar el monto del pendiente.
        // Esta regla solo aplica si NO estamos en modo recuperación, o si el movimiento actual tiene rendido/reintegrado.
        if (!$this->isRecovering && ($nuevoTotalRendido + $nuevoTotalReintegrado) > ($this->pendiente->montoPendientes + 0.001)) {
            $this->addError('rendido', 'La suma de rendido y reintegrado supera el monto total del pendiente.');
            $this->addError('reintegrado', 'La suma de rendido y reintegrado supera el monto total del pendiente.');
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

            // Evento para notificación toast con SweetAlert2
            $this->dispatchBrowserEvent('swal:success', [
                'title' => '¡Éxito!',
                'text' => $mensaje,
            ]);
        } catch (\Exception $e) {
            $error = 'Error al guardar el movimiento: ' . $e->getMessage();
            
            // Evento para notificación de error con SweetAlert2
            $this->dispatchBrowserEvent('swal:error', [
                'title' => 'Error',
                'text' => $error,
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
            
            // Evento para notificación con SweetAlert2
            $this->dispatchBrowserEvent('swal:success', [
                'title' => 'Eliminado',
                'text' => $mensaje,
            ]);
        } catch (\Exception $e) {
            $error = 'Error al eliminar el movimiento: ' . $e->getMessage();

            // Evento para notificación de error con SweetAlert2
            $this->dispatchBrowserEvent('swal:error', [
                'title' => 'Error',
                'text' => $error,
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
        $this->isRecovering = false; // Resetear el modo de recuperación
    }

    public function abrirModalRecuperarRendido()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->isRecovering = true; // Activar modo de recuperación

        $balance = $this->getBalancePendiente();
        $saldoPendienteRecuperar = $balance['total_rendido'] - $balance['total_recuperado'];

        $this->fechaMovimientos = Carbon::now()->format('Y-m-d');
        $this->recuperado = max(0, round($saldoPendienteRecuperar, 2)); // Asegurarse de que no sea negativo
        $this->rendido = 0; // No se rinde en este modo
        $this->reintegrado = 0; // No se reintegra en este modo

        $this->showModal = true;
        $this->resetErrorBag();
        $this->dispatchBrowserEvent('modal-opened');
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
    public function updatedRendido($value)
    {
        if ($this->isRecovering) {
            return; // No hacer nada si estamos en modo recuperación
        }
        $this->validateOnly('rendido');

        // Totales existentes (excluyendo el movimiento actual si se está editando)
        $totalRendido = $this->pendiente->movimientos()
            ->when($this->editMode, fn($q) => $q->where('idMovimientos', '!=', $this->movimientoId))
            ->sum('rendido');

        $totalReintegrado = $this->pendiente->movimientos()
            ->when($this->editMode, fn($q) => $q->where('idMovimientos', '!=', $this->movimientoId))
            ->sum('reintegrado');

        $rendidoActual = is_numeric($value) ? (float)$value : 0;

        // Saldo restante antes de este movimiento
        $saldoPendiente = $this->pendiente->montoPendientes - ($totalRendido + $totalReintegrado);

        // El reintegro es la diferencia entre el saldo y lo que se está rindiendo ahora
        $reintegro = $saldoPendiente - $rendidoActual;

        $this->reintegrado = $reintegro > 0 ? round($reintegro, 2) : 0;
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
