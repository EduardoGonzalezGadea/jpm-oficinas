<?php

namespace App\Http\Livewire\Tesoreria\CajaChica\Modales;

use Livewire\Component;
use App\Models\Tesoreria\CajaChica;

class ModalEditarFondo extends Component
{
    public $showModal = false;

    public $editandoFondo = [
        'id' => null,
        'mes' => '',
        'anio' => '',
        'monto' => '',
        'montoOriginal' => ''
    ];

    protected $listeners = ['abrirModalEditarFondo' => 'abrirModal'];

    protected function rules()
    {
        return (new \App\Http\Requests\Tesoreria\CajaChica\EditarFondoRequest())->rules();
    }

    protected function messages()
    {
        return (new \App\Http\Requests\Tesoreria\CajaChica\EditarFondoRequest())->messages();
    }

    public function abrirModal($idCajaChica, $montoActual)
    {
        $this->resetErrorBag();
        try {
            $fondo = CajaChica::findOrFail($idCajaChica);

            $this->editandoFondo = [
                'id' => $idCajaChica,
                'mes' => ucfirst($fondo->mes),
                'anio' => $fondo->anio,
                'monto' => number_format((float)$montoActual, 2, '.', ''),
                'montoOriginal' => number_format((float)$montoActual, 2, '.', '')
            ];

            $this->showModal = true;
            $this->dispatchBrowserEvent('modal-edit-fondo-opened');
        } catch (\Exception $e) {
            $this->emit('mostrarAlerta', ['type' => 'error', 'text' => 'Error al cargar los datos del fondo: ' . $e->getMessage()]);
        }
    }

    public function updatedEditandoFondoMonto()
    {
        $this->validateOnly('editandoFondo.monto');
    }

    public function actualizarFondo()
    {
        $this->validate();

        try {
            $service = app(\App\Services\Tesoreria\CajaChicaService::class);
            $resultado = $service->actualizarFondo($this->editandoFondo['id'], floatval($this->editandoFondo['monto']));

            if (!$resultado) {
                $this->cerrarModal();
                $this->emit('mostrarAlerta', ['icon' => 'success', 'text' => 'No se realizaron cambios en el monto del fondo.', 'toast' => true, 'position' => 'top-end', 'timer' => 3000]);
                return;
            }

            $this->cerrarModal();
            $this->emit('fondoActualizado');

            $mensaje = sprintf(
                'Fondo actualizado exitosamente. Monto anterior: $%s, Monto nuevo: $%s',
                number_format($resultado['montoAnterior'], 2, ',', '.'),
                number_format($resultado['montoNuevo'], 2, ',', '.')
            );

            $this->emit('mostrarAlerta', ['icon' => 'success', 'text' => $mensaje, 'toast' => true, 'position' => 'top-end', 'timer' => 5000]);

        } catch (\Exception $e) {
            $this->emit('mostrarAlerta', ['icon' => 'error', 'text' => 'Error al actualizar el fondo: ' . $e->getMessage(), 'toast' => true, 'position' => 'top-end', 'timer' => 5000]);
        }
    }

    public function cerrarModal()
    {
        $this->showModal = false;
        $this->reset('editandoFondo');
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.tesoreria.caja-chica.modales.modal-editar-fondo');
    }
}
