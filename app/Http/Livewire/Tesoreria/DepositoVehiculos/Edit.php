<?php

namespace App\Http\Livewire\Tesoreria\DepositoVehiculos;

use App\Models\Tesoreria\DepositoVehiculo;
use App\Models\Tesoreria\MedioDePago;
use Livewire\Component;

class Edit extends Component
{
    public $deposito_id;
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

    protected $listeners = ['showEditModal'];

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
    }

    public function showEditModal($id)
    {
        $deposito = DepositoVehiculo::find($id);
        $this->deposito_id = $deposito->id;
        $this->recibo_serie = $deposito->recibo_serie;
        $this->recibo_numero = $deposito->recibo_numero;
        $this->recibo_fecha = $deposito->recibo_fecha ? $deposito->recibo_fecha->format('Y-m-d') : null;
        $this->orden_cobro = $deposito->orden_cobro;
        $this->titular = $deposito->titular;
        $this->cedula = $deposito->cedula;
        $this->telefono = $deposito->telefono;
        $this->medio_pago_id = $deposito->medio_pago_id;
        $this->monto = $deposito->monto;
        $this->concepto = $deposito->concepto;

        $this->dispatchBrowserEvent('show-modal', ['id' => 'editModal']);
    }

    public function update()
    {
        $this->validate();

        // Validar unicidad de serie y número de recibo (excluyendo el actual)
        $existsRecibo = DepositoVehiculo::where('recibo_serie', $this->recibo_serie)
            ->where('recibo_numero', $this->recibo_numero)
            ->where('id', '!=', $this->deposito_id)
            ->exists();

        if ($existsRecibo) {
            $this->dispatchBrowserEvent('swal:error', [
                'title' => 'Error de Validación',
                'text' => 'La combinación de Serie y Número de Recibo ya existe.',
            ]);
            return;
        }

        if (!$this->deposito_id) {
            $this->dispatchBrowserEvent('swal:error', ['text' => 'Error: No se pudo identificar el registro a actualizar.']);
            return;
        }

        $deposito = DepositoVehiculo::find($this->deposito_id);

        if (!$deposito) {
            $this->dispatchBrowserEvent('swal:error', ['text' => 'Error: No se encontró el registro.']);
            return;
        }

        $deposito->update([
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
            'updated_by' => auth()->id(),
        ]);

        $this->emit('pg:eventRefresh-default');
        $this->dispatchBrowserEvent('hide-modal', ['id' => 'editModal']);
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Depósito actualizado correctamente.']);
    }

    public function render()
    {
        return view('livewire.tesoreria.deposito-vehiculos.edit', [
            'mediosPago' => MedioDePago::where('activo', true)->get()
        ]);
    }
}
