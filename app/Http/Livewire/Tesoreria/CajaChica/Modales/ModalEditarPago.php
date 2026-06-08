<?php

namespace App\Http\Livewire\Tesoreria\CajaChica\Modales;

use Livewire\Component;
use App\Models\Tesoreria\Pago;
use App\Models\Tesoreria\Acreedor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ModalEditarPago extends Component
{
    public $show = false;
    public $idPago;

    protected $listeners = ['mostrarModalEditarPago' => 'mostrarModal'];

    public $pago = [
        'fechaEgresoPagos' => '',
        'fechaEgresoEfectivoPagos' => '',
        'egresoPagos' => '',
        'relAcreedores' => '',
        'conceptoPagos' => '',
        'montoPagos' => '',
        'rendidoPagos' => '',
        'reintegradoPagos' => '',
        'extraPagos' => 0,
        'recuperadoPagos' => '',
        'fechaIngresoPagos' => '',
        'ingresoPagos' => '',
        'ingresoReintegroPagos' => '',
        'ingresoPagosBSE' => '',
        'fechaIngresoBSEPagos' => '',
        'fechaRendicionPagos' => '',
    ];

    public $acreedores = [];

    protected $rules = [
        'pago.fechaEgresoPagos' => 'required|date',
        'pago.fechaEgresoEfectivoPagos' => 'nullable|date',
        'pago.egresoPagos' => 'nullable|string|max:50',
        'pago.relAcreedores' => 'nullable|integer|exists:tes_cch_acreedores,idAcreedores',
        'pago.conceptoPagos' => 'required|string|max:500',
        'pago.montoPagos' => 'required|numeric|min:0',
        'pago.rendidoPagos' => 'nullable|numeric|min:0',
        'pago.reintegradoPagos' => 'nullable|numeric|min:0',
        'pago.recuperadoPagos' => 'nullable|numeric|min:0',
        'pago.fechaIngresoPagos' => 'nullable|date',
        'pago.ingresoPagos' => 'nullable|string|max:255',
        'pago.ingresoReintegroPagos' => 'nullable|string|max:255',
        'pago.ingresoPagosBSE' => 'nullable|string|max:255',
        'pago.fechaIngresoBSEPagos' => 'nullable|date',
        'pago.fechaRendicionPagos' => 'nullable|date',
    ];

    public function mount()
    {
        $this->acreedores = Acreedor::orderBy('acreedor')->get();
    }

    public function mostrarModal($id)
    {
        $this->idPago = $id;
        $pago = Pago::findOrFail($id);

        $rendido = $pago->rendidoPagos;
        $monto = $pago->montoPagos;
        $extra = (!is_null($rendido) && $rendido > $monto) ? round($rendido - $monto, 2) : 0;

        $this->pago = [
            'fechaEgresoPagos' => $pago->fechaEgresoPagos ? Carbon::parse($pago->fechaEgresoPagos)->format('Y-m-d') : '',
            'fechaEgresoEfectivoPagos' => $pago->fechaEgresoEfectivoPagos ? Carbon::parse($pago->fechaEgresoEfectivoPagos)->format('Y-m-d') : '',
            'egresoPagos' => $pago->egresoPagos,
            'relAcreedores' => $pago->relAcreedores,
            'conceptoPagos' => $pago->conceptoPagos,
            'montoPagos' => number_format($monto, 2, '.', ''),
            'rendidoPagos' => !is_null($rendido) ? number_format($rendido, 2, '.', '') : '',
            'reintegradoPagos' => !is_null($pago->reintegradoPagos) ? number_format($pago->reintegradoPagos, 2, '.', '') : '',
            'extraPagos' => $extra,
            'recuperadoPagos' => number_format($pago->recuperadoPagos, 2, '.', ''),
            'fechaIngresoPagos' => $pago->fechaIngresoPagos ? Carbon::parse($pago->fechaIngresoPagos)->format('Y-m-d') : '',
            'ingresoPagos' => $pago->ingresoPagos,
            'ingresoReintegroPagos' => $pago->ingresoReintegroPagos,
            'ingresoPagosBSE' => $pago->ingresoPagosBSE,
            'fechaIngresoBSEPagos' => $pago->fechaIngresoBSEPagos ? Carbon::parse($pago->fechaIngresoBSEPagos)->format('Y-m-d') : '',
            'fechaRendicionPagos' => $pago->fechaRendicionPagos ? Carbon::parse($pago->fechaRendicionPagos)->format('Y-m-d') : '',
        ];

        $this->show = true;
        $this->resetErrorBag();
    }

    /**
     * Auto-cálculo cuando cambia rendido.
     */
    public function updatedPagoRendidoPagos($value)
    {
        if ($value === '' || is_null($value)) {
            $this->pago['reintegradoPagos'] = '';
            $this->pago['extraPagos'] = 0;
            return;
        }

        $rendido = floatval($value);
        $monto = floatval($this->pago['montoPagos']);

        if ($rendido <= $monto) {
            $this->pago['reintegradoPagos'] = number_format($monto - $rendido, 2, '.', '');
            $this->pago['extraPagos'] = 0;
        } else {
            $this->pago['reintegradoPagos'] = '0.00';
            $this->pago['extraPagos'] = round($rendido - $monto, 2);
        }
    }

    /**
     * Auto-cálculo cuando cambia monto (recalcula rendición si existe).
     */
    public function updatedPagoMontoPagos($value)
    {
        if (!empty($this->pago['rendidoPagos'])) {
            $this->updatedPagoRendidoPagos($this->pago['rendidoPagos']);
        }
    }

    public function eliminarRendicion()
    {
        $this->pago['rendidoPagos'] = '';
        $this->pago['reintegradoPagos'] = '';
        $this->pago['ingresoReintegroPagos'] = '';
        $this->pago['extraPagos'] = 0;
        $this->pago['fechaRendicionPagos'] = '';
    }

    public function actualizarPago()
    {
        $this->validate();

        DB::beginTransaction();
        try {
            $pago = Pago::findOrFail($this->idPago);

            $rendido = ($this->pago['rendidoPagos'] !== '' && !is_null($this->pago['rendidoPagos'])) ? floatval($this->pago['rendidoPagos']) : null;
            $monto = floatval($this->pago['montoPagos']);
            $reintegrado = null;

            if (!is_null($rendido)) {
                $reintegrado = ($rendido <= $monto) ? round($monto - $rendido, 2) : 0;
            }

            $recuperado = ($this->pago['recuperadoPagos'] !== '' && !is_null($this->pago['recuperadoPagos'])) ? floatval($this->pago['recuperadoPagos']) : null;

            $pago->update([
                'fechaEgresoPagos' => Carbon::parse($this->pago['fechaEgresoPagos']),
                'fechaEgresoEfectivoPagos' => $this->pago['fechaEgresoEfectivoPagos'] ? Carbon::parse($this->pago['fechaEgresoEfectivoPagos']) : null,
                'egresoPagos' => $this->pago['egresoPagos'] ?: null,
                'relAcreedores' => $this->pago['relAcreedores'],
                'conceptoPagos' => $this->pago['conceptoPagos'],
                'montoPagos' => $monto,
                'rendidoPagos' => $rendido,
                'reintegradoPagos' => $reintegrado,
                'recuperadoPagos' => $recuperado,
                'fechaIngresoPagos' => $this->pago['fechaIngresoPagos'] ? Carbon::parse($this->pago['fechaIngresoPagos']) : null,
                'ingresoPagos' => $this->pago['ingresoPagos'] ?: null,
                'ingresoReintegroPagos' => $this->pago['ingresoReintegroPagos'] ?: null,
                'ingresoPagosBSE' => $this->pago['ingresoPagosBSE'] ?: null,
                'fechaIngresoBSEPagos' => $this->pago['fechaIngresoBSEPagos'] ? Carbon::parse($this->pago['fechaIngresoBSEPagos']) : null,
                'fechaRendicionPagos' => $this->pago['fechaRendicionPagos'] ? Carbon::parse($this->pago['fechaRendicionPagos']) : null,
            ]);

            DB::commit();
            $this->emitUp('pagoCreado');
            $this->cerrarModal();
            session()->flash('message', 'Pago actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
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
        return view('livewire.tesoreria.caja-chica.modales.modal-editar-pago');
    }
}
