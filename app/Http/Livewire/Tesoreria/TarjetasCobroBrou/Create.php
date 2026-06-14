<?php

namespace App\Http\Livewire\Tesoreria\TarjetasCobroBrou;

use App\Models\Tesoreria\TarjetaCobroBrou;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Create extends Component
{
    public $fecha_recibido;
    public $titular_cedula;
    public $titular_nombre;
    public $titular_apellido;
    public $numero_tarjeta;
    public $observaciones;

    protected $listeners = ['showCreateModal'];

    public function showCreateModal()
    {
        $this->resetInput();
        $this->dispatchBrowserEvent('show-modal', ['id' => 'createModal']);
    }

    public function mount()
    {
        $this->fecha_recibido = date('Y-m-d');
    }

    public function render()
    {
        return view('livewire.tesoreria.tarjetas-cobro-brou.create');
    }

    public function save()
    {
        $this->validate([
            'fecha_recibido' => 'required|date',
            'titular_cedula' => 'required|string|max:255',
            'titular_nombre' => 'required|string|max:255',
            'titular_apellido' => 'required|string|max:255',
            'numero_tarjeta' => 'required|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        TarjetaCobroBrou::create([
            'fecha_recibido' => $this->fecha_recibido,
            'receptor_id' => Auth::id(),
            'titular_cedula' => $this->titular_cedula,
            'titular_nombre' => $this->titular_nombre,
            'titular_apellido' => $this->titular_apellido,
            'numero_tarjeta' => $this->numero_tarjeta,
            'observaciones' => $this->observaciones,
            'estado' => 'Recibido',
        ]);

        $this->emit('pg:eventRefresh-default');
        $this->dispatchBrowserEvent('hide-modal', ['id' => 'createModal']);
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Tarjeta registrada correctamente.']);
    }

    private function resetInput()
    {
        $this->fecha_recibido = date('Y-m-d');
        $this->titular_cedula = '';
        $this->titular_nombre = '';
        $this->titular_apellido = '';
        $this->numero_tarjeta = '';
        $this->observaciones = '';
    }
}