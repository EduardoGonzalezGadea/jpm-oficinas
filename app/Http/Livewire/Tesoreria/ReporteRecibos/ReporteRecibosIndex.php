<?php

namespace App\Http\Livewire\Tesoreria\ReporteRecibos;

use Livewire\Component;
use Carbon\Carbon;
use App\Services\Tesoreria\ReporteRecibosService;

class ReporteRecibosIndex extends Component
{
    public string $fechaDesde;
    public string $fechaHasta;
    public ?array $reporte = null;
    public bool $mostrarDetalle = true;
    /** Índices de secciones incluidas en el resumen (todas activas por defecto) */
    public array $seccionesActivas = [];

    public function mount()
    {
        // Default: primer y último día del mes anterior
        $this->fechaDesde = now()->subMonth()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = now()->subMonth()->endOfMonth()->format('Y-m-d');
    }

    public function generarReporte()
    {
        $this->validate([
            'fechaDesde' => 'required|date',
            'fechaHasta' => 'required|date|after_or_equal:fechaDesde',
        ]);

        $service = new ReporteRecibosService();
        $this->reporte = $service->generarReporte(
            Carbon::parse($this->fechaDesde),
            Carbon::parse($this->fechaHasta)
        );

        // Activar todas las secciones al generar un nuevo reporte
        $this->seccionesActivas = array_keys($this->reporte['secciones']);
    }

    public function toggleTodas(): void
    {
        $total = count($this->reporte['secciones'] ?? []);
        if (count($this->seccionesActivas) === $total) {
            $this->seccionesActivas = [];
        } else {
            $this->seccionesActivas = array_keys($this->reporte['secciones']);
        }
    }

    public function toggleDetalle()
    {
        $this->mostrarDetalle = !$this->mostrarDetalle;
    }

    public function exportarExcel()
    {
        if (!$this->reporte) {
            return;
        }

        return redirect()->route('tesoreria.reporte-recibos.exportar-excel', [
            'desde' => $this->fechaDesde,
            'hasta' => $this->fechaHasta,
        ]);
    }



    public function limpiar()
    {
        $this->fechaDesde = now()->subMonth()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = now()->subMonth()->endOfMonth()->format('Y-m-d');
        $this->reporte = null;
        $this->seccionesActivas = [];
    }

    /**
     * Calcula los totales considerando solo las secciones activas.
     */
    public function getTotalesFiltrados(): array
    {
        if (!$this->reporte) {
            return ['cantidad' => 0, 'monto' => 0.0, 'monto_formateado' => '$\u00A0' . '0,00'];
        }

        $cantidad = 0;
        $monto = 0.0;

        foreach ($this->reporte['secciones'] as $index => $seccion) {
            if (in_array($index, $this->seccionesActivas)) {
                $cantidad += $seccion['cantidad'];
                $monto   += $seccion['monto_total'];
            }
        }

        $service = new ReporteRecibosService();
        return [
            'cantidad'  => $cantidad,
            'monto'     => $monto,
            'monto_formateado' => $service->formatearMonto($monto),
        ];
    }

    public function render()
    {
        return view('livewire.tesoreria.reporte-recibos.reporte-recibos-index', [
            'totalesFiltrados' => $this->getTotalesFiltrados(),
        ])
            ->extends('layouts.app')
            ->section('content');
    }
}
