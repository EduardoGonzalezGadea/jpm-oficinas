<?php

namespace App\Http\Livewire\Tesoreria\Eventuales;

use App\Models\Tesoreria\Eventual as Model;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class PrintEventuales extends Component
{
    public $mes;
    public $year;
    public $eventuales;
    public $subtotales;
    public $total;
    public $totalesPorInstitucion = [];

    public function mount($mes, $year)
    {
        $this->mes = $mes;
        $this->year = $year;

        $this->eventuales = Model::whereYear('fecha', $this->year)
            ->whereMonth('fecha', $this->mes)
            ->orderBy('fecha', 'asc')
            ->orderBy('recibo', 'asc')
            ->get();

        $this->total = $this->eventuales->sum('monto');

        $this->subtotales = Model::whereYear('fecha', $this->year)
            ->whereMonth('fecha', $this->mes)
            ->select('medio_de_pago', DB::raw('sum(monto) as total'))
            ->groupBy('medio_de_pago')
            ->get();

        $this->totalesPorInstitucion = Model::whereYear('fecha', $this->year)
            ->whereMonth('fecha', $this->mes)
            ->select('institucion', DB::raw('SUM(monto) as total_monto'))
            ->groupBy('institucion')
            ->orderBy('institucion', 'asc')
            ->toBase()
            ->get();
    }

    public function render()
    {
        return view('livewire.tesoreria.eventuales.print-eventuales')
            ->layout('layouts.print');
    }
}