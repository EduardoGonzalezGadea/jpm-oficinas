<?php

namespace App\Http\Livewire\Tesoreria\Prendas;

use App\Models\Tesoreria\MedioDePago;
use App\Models\Tesoreria\Prenda;
use Livewire\Component;

class Create extends Component
{
    public $recibo_serie;
    public $recibo_numero;
    public $recibo_fecha;
    public $orden_cobro;
    public $titular_nombre;
    public $titular_cedula;
    public $titular_telefono;
    public $medio_pago_id;
    public $monto;
    public $concepto;
    public $transferencia;
    public $transferencia_fecha;

    public $mediosPago;
    public $showDuplicateAlert = false;

    protected $listeners = ['showCreateModal', 'confirmStore'];

    protected $rules = [
        'recibo_serie' => 'required|string|max:255',
        'recibo_numero' => 'required|string|max:255',
        'recibo_fecha' => 'required|date',
        'orden_cobro' => 'required|string|max:255',
        'titular_nombre' => 'required|string|max:255',
        'titular_cedula' => 'nullable|string|max:255',
        'titular_telefono' => 'nullable|string|max:255',
        'medio_pago_id' => 'required|exists:tes_medio_de_pagos,id',
        'monto' => 'required|numeric|min:0',
        'concepto' => 'required|string|max:255',
        'transferencia' => 'nullable|string|max:255',
        'transferencia_fecha' => 'nullable|date',
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

    public function updatedTransferencia($value)
    {
        if (!empty($value)) {
            $exists = Prenda::where('transferencia', $value)->exists();
            if ($exists) {
                $this->showDuplicateAlert = true;
                $this->dispatchBrowserEvent('swal:warning', [
                    'title' => 'Transferencia Duplicada',
                    'text' => 'El número de transferencia ya existe en otro registro. ¿Desea continuar?',
                ]);
            } else {
                $this->showDuplicateAlert = false;
            }
        } else {
            $this->showDuplicateAlert = false;
        }
    }

    public function store()
    {
        $this->validate();

        // Validar unicidad de serie y número de recibo
        $existsRecibo = Prenda::where('recibo_serie', $this->recibo_serie)
            ->where('recibo_numero', $this->recibo_numero)
            ->exists();

        if ($existsRecibo) {
            $this->dispatchBrowserEvent('swal:error', [
                'title' => 'Error de Validación',
                'text' => 'La combinación de Serie y Número de Recibo ya existe.',
            ]);
            return;
        }

        // Verificar si hay transferencia duplicada
        if (!empty($this->transferencia)) {
            $exists = Prenda::where('transferencia', $this->transferencia)->exists();
            if ($exists) {
                $this->emit('swal:confirm-duplicate-create', [
                    'title' => 'Transferencia Duplicada',
                    'text' => 'El número de transferencia ya existe en otro registro. ¿Desea continuar de todas formas?',
                ]);
                return;
            }
        }

        $this->confirmStore();
    }

    public function confirmStore()
    {
        Prenda::create([
            'recibo_serie' => mb_strtoupper($this->recibo_serie, 'UTF-8'),
            'recibo_numero' => mb_strtoupper($this->recibo_numero, 'UTF-8'),
            'recibo_fecha' => $this->recibo_fecha,
            'orden_cobro' => mb_strtoupper($this->orden_cobro, 'UTF-8'),
            'titular_nombre' => mb_strtoupper($this->titular_nombre, 'UTF-8'),
            'titular_cedula' => !empty($this->titular_cedula) ? mb_strtoupper($this->titular_cedula, 'UTF-8') : null,
            'titular_telefono' => mb_strtoupper($this->titular_telefono, 'UTF-8'),
            'medio_pago_id' => $this->medio_pago_id,
            'monto' => $this->monto,
            'concepto' => mb_strtoupper($this->concepto, 'UTF-8'),
            'transferencia' => !empty($this->transferencia) ? mb_strtoupper($this->transferencia, 'UTF-8') : null,
            'transferencia_fecha' => $this->transferencia_fecha ?: null,
        ]);

        $this->emit('pg:eventRefresh-default');
        $this->dispatchBrowserEvent('hide-modal', ['id' => 'createModal']);
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Prenda creada correctamente.']);
        $this->resetInput();
    }

    private function resetInput()
    {
        $this->recibo_serie = '';
        $this->recibo_numero = '';
        $this->recibo_fecha = date('Y-m-d');
        $this->orden_cobro = '';
        $this->titular_nombre = '';
        $this->titular_cedula = '';
        $this->titular_telefono = '';
        $this->medio_pago_id = '';
        $this->monto = '';
        $this->concepto = '';
        $this->transferencia = '';
        $this->transferencia_fecha = '';
        $this->showDuplicateAlert = false;
    }

    public function render()
    {
        return view('livewire.tesoreria.prendas.create', [
            'mediosPago' => MedioDePago::where('activo', true)->get()
        ]);
    }
}
