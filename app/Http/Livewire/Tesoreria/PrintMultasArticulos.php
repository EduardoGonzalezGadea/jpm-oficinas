<?php

namespace App\Http\Livewire\Tesoreria;

use App\Models\Tesoreria\Multa;
use App\Services\ValorUrService;
use Livewire\Component;

class PrintMultasArticulos extends Component
{
    public $multas;
    public $valorUr;
    public $mesUr;
    public $urVencida = false;

    public function mount()
    {
        // Obtener todas las multas ordenadas por artículo y apartado
        $this->multas = Multa::orderBy('articulo', 'asc')
            ->orderBy('apartado', 'asc')
            ->get();

        $urData = app(ValorUrService::class)->obtener();

        $this->valorUr = $urData['valorUr'] ?? 'N/A';
        $this->mesUr = $urData['mesUr'] ?? '';
        $this->urVencida = (bool) ($urData['vencido'] ?? false);
    }

    public function render()
    {
        return view('livewire.tesoreria.print-multas-articulos')
            ->layout('layouts.print');
    }
}
