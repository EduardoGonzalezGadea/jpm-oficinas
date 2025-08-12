<?php

namespace App\Http\Livewire\Tesoreria\CajaChica;

use Livewire\Component;
use App\Models\Tesoreria\Pago;
use App\Models\Tesoreria\Acreedor;
use Illuminate\Support\Carbon;

class ModalEditarPago extends Component
{
    public $show = false;
    public $idPago;

    protected $listeners = ['mostrarModalEditarPago' => 'mostrarModal'];

    public $pago = [
        'fechaEgresoPagos' => '',
        'egresoPagos' => '',
        'relAcreedores' => '',
        'conceptoPagos' => '',
        'montoPagos' => '',
        'recuperadoPagos' => '',
        'fechaIngresoPagos' => '',
        'ingresoPagos' => '',
        'ingresoPagosBSE' => '',
    ];

    public $acreedores = [];

    protected $rules = [
        'pago.fechaEgresoPagos' => 'required|date',
        'pago.egresoPagos' => 'required|string|max:255',
        'pago.relAcreedores' => 'required|integer|exists:tes_cch_acreedores,idAcreedores',
        'pago.conceptoPagos' => 'required|string|max:500',
        'pago.montoPagos' => 'required|numeric|min:0',
        'pago.recuperadoPagos' => 'nullable|numeric|min:0|lte:pago.montoPagos',
        'pago.fechaIngresoPagos' => 'nullable|date',
        'pago.ingresoPagos' => 'nullable|string|max:255',
        'pago.ingresoPagosBSE' => 'nullable|string|max:255',
    ];

    public function mount()
    {
        $this->acreedores = Acreedor::orderBy('acreedor')->get();
    }

    public function mostrarModal($id)
    {
        $this->idPago = $id;
        $pago = Pago::findOrFail($id);

        $this->pago = [
            'fechaEgresoPagos' => $pago->fechaEgresoPagos ? Carbon::parse($pago->fechaEgresoPagos)->format('Y-m-d') : '',
            'egresoPagos' => $pago->egresoPagos,
            'relAcreedores' => $pago->relAcreedores,
            'conceptoPagos' => $pago->conceptoPagos,
            'montoPagos' => number_format($pago->montoPagos, 2, '.', ''),
            'recuperadoPagos' => number_format($pago->recuperadoPagos, 2, '.', ''),
            'fechaIngresoPagos' => $pago->fechaIngresoPagos ? Carbon::parse($pago->fechaIngresoPagos)->format('Y-m-d') : '',
            'ingresoPagos' => $pago->ingresoPagos,
            'ingresoPagosBSE' => $pago->ingresoPagosBSE,
        ];

        $this->show = true;
        $this->resetErrorBag();
    }

    public function actualizarPago()
    {
        $this->validate();

        try {
            $pago = Pago::findOrFail($this->idPago);
            $pago->update([
                'fechaEgresoPagos' => Carbon::parse($this->pago['fechaEgresoPagos']),
                'egresoPagos' => $this->pago['egresoPagos'],
                'relAcreedores' => $this->pago['relAcreedores'],
                'conceptoPagos' => $this->pago['conceptoPagos'],
                'montoPagos' => floatval($this->pago['montoPagos']),
                'recuperadoPagos' => floatval($this->pago['recuperadoPagos']),
                'fechaIngresoPagos' => $this->pago['fechaIngresoPagos'] ? Carbon::parse($this->pago['fechaIngresoPagos']) : null,
                'ingresoPagos' => $this->pago['ingresoPagos'],
                'ingresoPagosBSE' => $this->pago['ingresoPagosBSE'],
            ]);

            $this->emitUp('pagoCreado');
            $this->cerrarModal();
            session()->flash('message', 'Pago actualizado exitosamente.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al actualizar el pago: ' . $e->getMessage());
        }
    }

    public function cerrarModal()
    {
        $this->show = false;
        $this->dispatchBrowserEvent('cerrar-y-refrescar-editar-pago');
    }

    public function render()
    {
        return view('livewire.tesoreria.caja-chica.modal-editar-pago');
    }
}
