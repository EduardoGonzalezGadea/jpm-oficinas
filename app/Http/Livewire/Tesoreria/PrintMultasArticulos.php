<?php

namespace App\Http\Livewire\Tesoreria;

use App\Models\Tesoreria\Multa;
use App\Http\Controllers\UtilidadController;
use Livewire\Component;

class PrintMultasArticulos extends Component
{
    public $multas;
    public $valorUr;
    public $mesUr;

    public function mount()
    {
        // Obtener todas las multas ordenadas por artículo y apartado
        $this->multas = Multa::orderBy('articulo', 'asc')
            ->orderBy('apartado', 'asc')
            ->get();

        // Obtener valor de la UR para el encabezado
        $utilidad = new UtilidadController();
        $urData = $utilidad->getValorUr()->getData();

        $this->valorUr = $urData->valorUr ?? 'N/A';
        $this->mesUr = $urData->mesUr ?? '';
    }

    public function render()
    {
        return view('livewire.tesoreria.print-multas-articulos')
            ->layout('layouts.print');
    }
}
