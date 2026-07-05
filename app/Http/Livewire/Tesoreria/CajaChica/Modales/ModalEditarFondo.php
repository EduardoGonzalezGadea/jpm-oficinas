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
            $this->dispatchBrowserEvent('swal', ['type' => 'error', 'text' => 'Error al cargar los datos del fondo: ' . $e->getMessage()]);
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
                session()->flash('message', 'No se realizaron cambios en el monto del fondo.');
                return redirect()->route('tesoreria.caja-chica.index');
            }

            $this->cerrarModal();

            $mensaje = sprintf(
                'Fondo actualizado exitosamente. Monto anterior: $%s, Monto nuevo: $%s',
                number_format($resultado['montoAnterior'], 2, ',', '.'),
                number_format($resultado['montoNuevo'], 2, ',', '.')
            );

            session()->flash('message', $mensaje);
            return redirect()->route('tesoreria.caja-chica.index');

        } catch (\Exception $e) {
            session()->flash('error', 'Error al actualizar el fondo: ' . $e->getMessage());
            return redirect()->route('tesoreria.caja-chica.index');
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
