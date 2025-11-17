<?php
// app/Http/Livewire/Tesoreria/CajaChica/ModalNuevoPago.php

namespace App\Http\Livewire\Tesoreria\CajaChica;

use Livewire\Component;
use App\Models\Tesoreria\Pago;
use App\Models\Tesoreria\Acreedor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ModalNuevoPago extends Component
{
    public $idCajaChica;
    public $fechaEgresoPagos;
    public $egresoPagos;
    public $relAcreedores;
    public $conceptoPagos;
    public $montoPagos;
    public $acreedores = [];
    public $mostrarModal = false;

    protected $rules = [
        'fechaEgresoPagos' => 'required|date',
        'egresoPagos' => 'nullable|string|max:50',
        'relAcreedores' => 'nullable|exists:tes_cch_acreedores,idAcreedores',
        'conceptoPagos' => 'required|string|max:255',
        'montoPagos' => 'required|numeric|min:0.01',
    ];

    protected $messages = [
        'fechaEgresoPagos.required' => 'La fecha de egreso es obligatoria.',
        'fechaEgresoPagos.date' => 'La fecha de egreso no es válida.',
        'egresoPagos.required' => 'El número de egreso es obligatorio.',
        'egresoPagos.max' => 'El número de egreso es demasiado largo.',
        'relAcreedores.exists' => 'El acreedor seleccionado no es válido.',
        'conceptoPagos.required' => 'El concepto es obligatorio.',
        'conceptoPagos.max' => 'El concepto es demasiado largo.',
        'montoPagos.required' => 'El monto es obligatorio.',
        'montoPagos.numeric' => 'El monto debe ser un número.',
        'montoPagos.min' => 'El monto debe ser mayor que cero.',
    ];

    protected $listeners = [
        'mostrarModalNuevoPago' => 'abrirModal',
        'cerrarModalNuevoPago' => 'cerrarModal',
    ];

    public function mount()
    {
        $this->fechaEgresoPagos = now()->toDateString();
        $this->egresoPagos = '';
        $this->relAcreedores = '';
        $this->conceptoPagos = '';
        $this->montoPagos = '';
        $this->acreedores = [];
    }

    public function abrirModal($idCajaChica)
    {
        $this->idCajaChica = $idCajaChica;
        $this->cargarAcreedores();
        $this->mostrarModal = true;
        $this->dispatchBrowserEvent('show-modal', ['id' => 'modalNuevoPago']);
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->reset(['fechaEgresoPagos', 'egresoPagos', 'relAcreedores', 'conceptoPagos', 'montoPagos']);
        $this->resetErrorBag();
    }

    public function cargarAcreedores()
    {
        $this->acreedores = Cache::remember('caja_chica_acreedores_all', now()->addDay(), function () {
            return Acreedor::orderBy('acreedor', 'ASC')->get();
        });
    }

    public function guardar()
    {
        $this->validate();

        DB::beginTransaction();
        try {
            Pago::create([
                'relCajaChica_Pagos' => $this->idCajaChica,
                'fechaEgresoPagos' => $this->fechaEgresoPagos,
                'egresoPagos' => $this->egresoPagos ?: null,
                'relAcreedores' => $this->relAcreedores ?: null,
                'conceptoPagos' => $this->conceptoPagos,
                'montoPagos' => $this->montoPagos,
                // Los campos fechaIngresoPagos, ingresoPagos, recuperadoPagos se dejan null por defecto
            ]);

            Cache::flush();
            DB::commit();

            session()->flash('message', 'Pago Directo creado correctamente.');
            $this->dispatchBrowserEvent('hide-modal', ['id' => 'modalNuevoPago']);
            $this->emitTo('tesoreria.caja-chica.index', 'pagoCreado');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error al crear el pago directo: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.tesoreria.caja-chica.modal-nuevo-pago');
    }
}
