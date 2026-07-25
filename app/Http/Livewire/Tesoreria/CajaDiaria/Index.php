<?php

namespace App\Http\Livewire\Tesoreria\CajaDiaria;

use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        return view('livewire.tesoreria.cajas.index')
            ->extends('layouts.app')
            ->section('content');
    }
}
