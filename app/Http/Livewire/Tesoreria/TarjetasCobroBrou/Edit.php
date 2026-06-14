<?php

namespace App\Http\Livewire\Tesoreria\TarjetasCobroBrou;

use App\Models\Tesoreria\TarjetaCobroBrou;
use Livewire\Component;

class Edit extends Component
{
    public $tarjeta_id;
    public $fecha_recibido;
    public $titular_cedula;
    public $titular_nombre;
    public $titular_apellido;
    public $numero_tarjeta;
    public $observaciones;

    protected $listeners = ['showEditModal'];

    public function showEditModal($id)
    {
        $tarjeta = TarjetaCobroBrou::find($id);
        $this->tarjeta_id = $tarjeta->id;
        $this->fecha_recibido = $tarjeta->fecha_recibido;
        $this->titular_cedula = $tarjeta->titular_cedula;
        $this->titular_nombre = $tarjeta->titular_nombre;
        $this->titular_apellido = $tarjeta->titular_apellido;
        $this->numero_tarjeta = $tarjeta->numero_tarjeta;
        $this->observaciones = $tarjeta->observaciones;
        $this->dispatchBrowserEvent('show-modal', ['id' => 'editModal']);
    }

    public function render()
    {
        return view('livewire.tesoreria.tarjetas-cobro-brou.edit');
    }

    public function update()
    {
        $this->validate([
            'fecha_recibido' => 'required|date',
            'titular_cedula' => 'required|string|max:255',
            'titular_nombre' => 'required|string|max:255',
            'titular_apellido' => 'required|string|max:255',
            'numero_tarjeta' => 'required|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        $tarjeta = TarjetaCobroBrou::find($this->tarjeta_id);
        $tarjeta->update([
            'fecha_recibido' => $this->fecha_recibido,
            'titular_cedula' => $this->titular_cedula,
            'titular_nombre' => $this->titular_nombre,
            'titular_apellido' => $this->titular_apellido,
            'numero_tarjeta' => $this->numero_tarjeta,
            'observaciones' => $this->observaciones,
        ]);

        $this->emit('pg:eventRefresh-default');
        $this->dispatchBrowserEvent('hide-modal', ['id' => 'editModal']);
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Tarjeta actualizada correctamente.']);
    }
}