<?php

namespace App\Http\Livewire\Tesoreria\Valores;

use Livewire\Component;

class ValoresSpa extends Component
{
    public $componenteActivo = 'stock';

    protected $listeners = ['cambiarComponente'];

    public function cambiarComponente($componente)
    {
        $this->componenteActivo = $componente;
    }

    public function render()
    {
        return view('livewire.tesoreria.valores.valores-spa');
    }
}
