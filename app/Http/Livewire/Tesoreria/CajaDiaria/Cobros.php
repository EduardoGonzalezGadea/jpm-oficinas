<?php

namespace App\Http\Livewire\Tesoreria\CajaDiaria;

use Livewire\Component;

class Cobros extends Component
{
    public $fecha;

    public function mount($fecha = null)
    {
        $this->fecha = $fecha ?: now()->format('Y-m-d');
    }

    public function render()
    {
        return view('livewire.tesoreria.caja-diaria.cobros');
    }
}
