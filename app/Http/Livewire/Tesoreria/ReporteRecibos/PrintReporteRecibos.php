<?php

namespace App\Http\Livewire\Tesoreria\ReporteRecibos;

use Livewire\Component;
use Carbon\Carbon;
use App\Services\Tesoreria\ReporteRecibosService;

class PrintReporteRecibos extends Component
{
    public ?array $reporte = null;
    public string $fechaDesde;
    public string $fechaHasta;

    public function mount()
    {
        $this->fechaDesde = request('desde', now()->subMonth()->startOfMonth()->format('Y-m-d'));
        $this->fechaHasta = request('hasta', now()->subMonth()->endOfMonth()->format('Y-m-d'));

        $service = new ReporteRecibosService();
        $this->reporte = $service->generarReporte(
            Carbon::parse($this->fechaDesde),
            Carbon::parse($this->fechaHasta)
        );
    }

    public function render()
    {
        return view('livewire.tesoreria.reporte-recibos.print-reporte-recibos')
            ->extends('layouts.app')
            ->section('content');
    }
}
