<?php

namespace App\Http\Livewire\Tesoreria\TarjetasCobroBrou;

use App\Models\Tesoreria\TarjetaCobroBrou;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Update extends Component
{
    public $tarjeta_id;
    public $observaciones;
    public $tarjeta;

    protected $listeners = ['showDeliverModal', 'showReturnModal'];

    public function showDeliverModal($id)
    {
        $this->resetInput();
        $this->tarjeta = TarjetaCobroBrou::find($id);
        $this->tarjeta_id = $id;
        $this->observaciones = $this->tarjeta->observaciones;
        $this->dispatchBrowserEvent('show-modal', ['id' => 'deliverModal']);
    }

    public function showReturnModal($id)
    {
        $this->tarjeta_id = $id;
        $this->tarjeta = TarjetaCobroBrou::find($id);
        $this->observaciones = $this->tarjeta->observaciones;
        $this->dispatchBrowserEvent('show-modal', ['id' => 'returnModal']);
    }

    public function render()
    {
        return view('livewire.tesoreria.tarjetas-cobro-brou.update');
    }

    public function deliver()
    {
        $this->validate([
            'observaciones' => 'nullable|string',
        ]);

        $tarjeta = TarjetaCobroBrou::find($this->tarjeta_id);
        $tarjeta->update([
            'fecha_entregado' => date('Y-m-d'),
            'entregador_id' => Auth::id(),
            'observaciones' => $this->observaciones,
            'estado' => 'Entregado',
        ]);

        $this->emit('pg:eventRefresh-default');
        $this->dispatchBrowserEvent('hide-modal', ['id' => 'deliverModal']);
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Tarjeta entregada correctamente.']);
    }

    public function return()
    {
        $this->validate([
            'observaciones' => 'nullable|string',
        ]);

        $tarjeta = TarjetaCobroBrou::find($this->tarjeta_id);
        $tarjeta->update([
            'fecha_devuelto' => date('Y-m-d'),
            'devolucion_user_id' => Auth::id(),
            'observaciones' => $this->observaciones,
            'estado' => 'Devuelto',
        ]);

        $this->emit('pg:eventRefresh-default');
        $this->dispatchBrowserEvent('hide-modal', ['id' => 'returnModal']);
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Tarjeta devuelta correctamente.']);
    }

    private function resetInput()
    {
        $this->tarjeta_id = null;
        $this->tarjeta = null;
        $this->observaciones = '';
    }
}