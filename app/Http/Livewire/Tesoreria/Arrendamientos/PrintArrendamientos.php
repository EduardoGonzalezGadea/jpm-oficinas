<?php

namespace App\Http\Livewire\Tesoreria\Arrendamientos;

use App\Models\Tesoreria\Arrendamiento as Model;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class PrintArrendamientos extends Component
{
    public $mes;
    public $year;
    public $arrendamientos;
    public $subtotales;
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

        $this->subtotales = Model::whereYear('fecha', $this->year)
            ->whereMonth('fecha', $this->mes)
            ->select('medio_de_pago', DB::raw('sum(monto) as total'))
            ->groupBy('medio_de_pago')
            ->get();
    }

    public function render()
    {
        return view('livewire.tesoreria.arrendamientos.print-arrendamientos')
            ->layout('layouts.print'); // Usaremos un layout de impresión simple
    }
}