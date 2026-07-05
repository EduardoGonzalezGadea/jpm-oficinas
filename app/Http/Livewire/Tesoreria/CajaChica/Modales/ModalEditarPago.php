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
            'recuperadoPagos' => ($pago->recuperadoPagos ?? 0) > 0 ? number_format($pago->recuperadoPagos, 2, '.', '') : '',
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

        if ($rendido >= 0 && empty($this->pago['fechaRendicionPagos'])) {
            $this->pago['fechaRendicionPagos'] = now()->format('Y-m-d');
        }
    }

    public function updatedPagoRecuperadoPagos($value)
    {
        if ($value !== '' && !is_null($value) && floatval($value) >= 0 && empty($this->pago['fechaIngresoPagos'])) {
            $this->pago['fechaIngresoPagos'] = now()->format('Y-m-d');
        }
    }

    /**
     * Recalcula reintegro y extra cuando el monto otorgado cambia.
     */
    public function updatedPagoMontoPagos($value)
    {
        $rendido = $this->pago['rendidoPagos'];

        if ($rendido === '' || is_null($rendido)) {
            return;
        }

        $rendido = floatval($rendido);
        $monto = floatval($value);

        if ($rendido <= $monto) {
            $this->pago['reintegradoPagos'] = number_format($monto - $rendido, 2, '.', '');
            $this->pago['extraPagos'] = 0;
        } else {
            $this->pago['reintegradoPagos'] = '0.00';
            $this->pago['extraPagos'] = round($rendido - $monto, 2);
        }
    }

    public function actualizarPago()
    {
        // Limpiar strings vacíos a null para que pasen las reglas nullable|numeric|integer
        foreach ($this->pago as $key => $value) {
            if ($value === '') {
                $this->pago[$key] = null;
            }
        }

        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }

        $rendido = ($this->pago['rendidoPagos'] !== '' && !is_null($this->pago['rendidoPagos'])) ? floatval($this->pago['rendidoPagos']) : null;
        $monto = floatval($this->pago['montoPagos']);
        $recuperado = ($this->pago['recuperadoPagos'] !== '' && !is_null($this->pago['recuperadoPagos'])) ? floatval($this->pago['recuperadoPagos']) : 0;

        $limiteRecupero = is_null($rendido) ? $monto : $rendido;

        if ($recuperado > $limiteRecupero) {
            $this->addError('pago.recuperadoPagos', 'El monto recuperado no puede superar el monto rendido ($' . number_format($limiteRecupero, 2, ',', '.') . ').');
            return;
        }

        $reintegrado = null;
        if (!is_null($rendido)) {
            $reintegrado = ($rendido <= $monto) ? round($monto - $rendido, 2) : 0;
        }

        DB::beginTransaction();
        try {
            $pago = Pago::findOrFail($this->idPago);

            $pago->update([
                'fechaEgresoPagos' => Carbon::parse($this->pago['fechaEgresoPagos']),
                'fechaEgresoEfectivoPagos' => $this->pago['fechaEgresoEfectivoPagos'] ? Carbon::parse($this->pago['fechaEgresoEfectivoPagos']) : null,
                'egresoPagos' => $this->pago['egresoPagos'] ?: null,
                'relAcreedores' => $this->pago['relAcreedores'] ?: null,
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

            $this->show = false;
            $this->resetErrorBag();

            session()->flash('message', 'Pago actualizado correctamente.');

            return redirect()->route('tesoreria.caja-chica.index');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error al actualizar el pago: ' . $e->getMessage());
            return redirect()->route('tesoreria.caja-chica.index');
        }
    }

    public function eliminarRendicion()
    {
        if (!empty($this->pago['recuperadoPagos']) || !empty($this->pago['fechaIngresoPagos']) || !empty($this->pago['ingresoPagos'])) {
            session()->flash('error', 'No se puede eliminar la rendición porque existen datos de recuperación.');
            return redirect()->route('tesoreria.caja-chica.index');
        }

        if (!$this->idPago) {
            session()->flash('error', 'ID de pago no encontrado.');
            return redirect()->route('tesoreria.caja-chica.index');
        }

        DB::beginTransaction();
        try {
            $pago = Pago::findOrFail($this->idPago);
            $pago->update([
                'rendidoPagos' => null,
                'reintegradoPagos' => null,
                'ingresoReintegroPagos' => null,
                'fechaRendicionPagos' => null,
            ]);

            $this->pago['rendidoPagos'] = '';
            $this->pago['reintegradoPagos'] = '';
            $this->pago['ingresoReintegroPagos'] = '';
            $this->pago['extraPagos'] = 0;
            $this->pago['fechaRendicionPagos'] = '';

            DB::commit();

            session()->flash('message', 'Rendición eliminada correctamente.');
            return redirect()->route('tesoreria.caja-chica.index');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error al eliminar rendición: ' . $e->getMessage());
            return redirect()->route('tesoreria.caja-chica.index');
        }
    }

    public function eliminarRecuperacion()
    {
        if (!$this->idPago) {
            session()->flash('error', 'ID de pago no encontrado.');
            return redirect()->route('tesoreria.caja-chica.index');
        }

        DB::beginTransaction();
        try {
            $pago = Pago::findOrFail($this->idPago);
            $pago->update([
                'recuperadoPagos' => 0,
                'fechaIngresoPagos' => null,
                'ingresoPagos' => null,
            ]);

            $this->pago['recuperadoPagos'] = '';
            $this->pago['fechaIngresoPagos'] = '';
            $this->pago['ingresoPagos'] = '';

            DB::commit();

            session()->flash('message', 'Recuperación eliminada correctamente.');
            return redirect()->route('tesoreria.caja-chica.index');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error al eliminar recuperación: ' . $e->getMessage());
            return redirect()->route('tesoreria.caja-chica.index');
        }
    }

    public function eliminarBSE()
    {
        if (!$this->idPago) {
            session()->flash('error', 'ID de pago no encontrado.');
            return redirect()->route('tesoreria.caja-chica.index');
        }

        DB::beginTransaction();
        try {
            $pago = Pago::findOrFail($this->idPago);
            $pago->update([
                'ingresoPagosBSE' => null,
                'fechaIngresoBSEPagos' => null,
            ]);

            $this->pago['ingresoPagosBSE'] = '';
            $this->pago['fechaIngresoBSEPagos'] = '';

            DB::commit();

            session()->flash('message', 'Datos BSE eliminados correctamente.');
            return redirect()->route('tesoreria.caja-chica.index');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error al eliminar datos BSE: ' . $e->getMessage());
            return redirect()->route('tesoreria.caja-chica.index');
        }
    }

    public function cerrarModal()
    {
        $this->show = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.tesoreria.caja-chica.modales.modal-editar-pago');
    }
}
