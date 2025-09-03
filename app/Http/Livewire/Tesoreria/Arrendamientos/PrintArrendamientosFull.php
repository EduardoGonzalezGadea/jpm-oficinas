<?php

namespace App\Http\Livewire\Tesoreria\Arrendamientos;

use App\Models\Tesoreria\Arrendamiento as Model;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class PrintArrendamientosFull extends Component
{
    public $mes;
    public $year;
    public $arrendamientos;
    public $total;

    public function mount($mes, $year)
    {
        $this->mes = $mes;
        $this->year = $year;

        $this->arrendamientos = Model::whereYear('fecha', $this->year)
            ->whereMonth('fecha', $this->mes)
            ->orderBy('fecha', 'asc')
            ->orderBy('recibo', 'asc')
            ->get();

        $this->total = $this->arrendamientos->sum('monto');
    }

    public function render()
    {
        return view('livewire.tesoreria.arrendamientos.print-arrendamientos-full')
            ->layout('layouts.print');
    }
}