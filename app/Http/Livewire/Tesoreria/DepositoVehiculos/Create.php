<?php

namespace App\Http\Livewire\Tesoreria\DepositoVehiculos;

use App\Models\Tesoreria\DepositoVehiculo;
use App\Models\Tesoreria\MedioDePago;
use Livewire\Component;

class Create extends Component
{
    public $recibo_serie;
    public $recibo_numero;
    public $recibo_fecha;
    public $orden_cobro;
    public $titular;
    public $cedula;
    public $telefono;
    public $medio_pago_id;
    public $monto;
    public $concepto;

    public $mediosPago;

    protected $listeners = ['showCreateModal'];

    protected $rules = [
        'recibo_serie' => 'required|string|max:255',
        'recibo_numero' => 'required|string|max:255',
        'recibo_fecha' => 'required|date',
        'orden_cobro' => 'nullable|string|max:255',
        'titular' => 'required|string|max:255',
        'cedula' => 'required|string|max:255',
        'telefono' => 'nullable|string|max:255',
        'medio_pago_id' => 'required|exists:tes_medio_de_pagos,id',
        'monto' => 'required|numeric|min:0',
        'concepto' => 'required|string',
    ];

    public function mount()
    {
        $this->mediosPago = MedioDePago::where('activo', true)->get();
        $this->recibo_fecha = date('Y-m-d');
    }

    public function showCreateModal()
    {
        $this->resetInput();
        $this->dispatchBrowserEvent('show-modal', ['id' => 'createModal']);
    }

    public function store()
    {
        $this->validate();

        // Validar unicidad de serie y número de recibo
        $existsRecibo = DepositoVehiculo::where('recibo_serie', $this->recibo_serie)
            ->where('recibo_numero', $this->recibo_numero)
            ->exists();

        if ($existsRecibo) {
            $this->dispatchBrowserEvent('swal:error', [
                'title' => 'Error de Validación',
                'text' => 'La combinación de Serie y Número de Recibo ya existe.',
            ]);
            return;
        }

        DepositoVehiculo::create([
            'recibo_serie' => mb_strtoupper($this->recibo_serie, 'UTF-8'),
            'recibo_numero' => mb_strtoupper($this->recibo_numero, 'UTF-8'),
            'recibo_fecha' => $this->recibo_fecha,
            'orden_cobro' => $this->orden_cobro ? mb_strtoupper($this->orden_cobro, 'UTF-8') : null,
            'titular' => mb_strtoupper($this->titular, 'UTF-8'),
            'cedula' => mb_strtoupper($this->cedula, 'UTF-8'),
            'telefono' => mb_strtoupper($this->telefono, 'UTF-8'),
            'medio_pago_id' => $this->medio_pago_id,
            'monto' => $this->monto,
            'concepto' => mb_strtoupper($this->concepto, 'UTF-8'),
            'created_by' => auth()->id(),
        ]);

        $this->emit('pg:eventRefresh-default');
        $this->dispatchBrowserEvent('hide-modal', ['id' => 'createModal']);
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Depósito creado correctamente.']);
        $this->resetInput();
    }

    private function resetInput()
    {
        $this->recibo_serie = '';
        $this->recibo_numero = '';
        $this->recibo_fecha = date('Y-m-d');
        $this->orden_cobro = '';
        $this->titular = '';
        $this->cedula = '';
        $this->telefono = '';
        $this->medio_pago_id = '';
        $this->monto = '';
        $this->concepto = '';
    }

    public function render()
    {
        return view('livewire.tesoreria.deposito-vehiculos.create', [
            'mediosPago' => MedioDePago::where('activo', true)->get()
        ]);
    }
}
