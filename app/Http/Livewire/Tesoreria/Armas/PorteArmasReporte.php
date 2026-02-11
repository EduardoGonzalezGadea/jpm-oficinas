<?php

namespace App\Http\Livewire\Tesoreria\Armas;

use Livewire\Component;
use App\Models\Tesoreria\TesPorteArmas;

class PorteArmasReporte extends Component
{
    public $filters = [];
    public $resultados = null;

    public function mount()
    {
        $this->filters = [
            'mes' => '',
            'year' => date('Y'),
            'titular' => '',
            'cedula' => '',
            'numero_tramite' => '',
            'monto_min' => '',
            'monto_max' => '',
            'fecha_desde' => '',
            'fecha_hasta' => '',
        ];
    }

    public function resetFilters()
    {
        $this->filters = [
            'mes' => '',
            'year' => date('Y'),
            'titular' => '',
            'cedula' => '',
            'numero_tramite' => '',
            'monto_min' => '',
            'monto_max' => '',
            'fecha_desde' => '',
            'fecha_hasta' => '',
        ];
        $this->resultados = null;
    }

    public function buscar()
    {
        $query = TesPorteArmas::query();

        // Aplicar Filtros
        $hasDateRange = !empty($this->filters['fecha_desde']) || !empty($this->filters['fecha_hasta']);

        if (!empty($this->filters['fecha_desde'])) {
            $query->whereDate('fecha', '>=', $this->filters['fecha_desde']);
        }
        if (!empty($this->filters['fecha_hasta'])) {
            $query->whereDate('fecha', '<=', $this->filters['fecha_hasta']);
        }

        if (!$hasDateRange) {
            if (!empty($this->filters['mes'])) {
                $query->whereMonth('fecha', $this->filters['mes']);
            }
            if (!empty($this->filters['year'])) {
                $query->whereYear('fecha', $this->filters['year']);
            }
        }

        if (!empty($this->filters['titular'])) {
            $query->where('titular', 'like', '%' . $this->filters['titular'] . '%');
        }
        if (!empty($this->filters['cedula'])) {
            $query->where('cedula', 'like', '%' . $this->filters['cedula'] . '%');
        }
        if (!empty($this->filters['numero_tramite'])) {
            $query->where('numero_tramite', 'like', '%' . $this->filters['numero_tramite'] . '%');
        }
        if (!empty($this->filters['monto_min'])) {
            $query->where('monto', '>=', $this->filters['monto_min']);
        }
        if (!empty($this->filters['monto_max'])) {
            $query->where('monto', '<=', $this->filters['monto_max']);
        }

        $this->resultados = $query->orderBy('fecha', 'asc')
            ->limit(500)
            ->get();
    }

    public function imprimir()
    {
        if (empty($this->resultados) || $this->resultados->isEmpty()) {
            return;
        }

        $activeFilters = array_filter($this->filters, function ($value) {
            return $value !== '' && $value !== null;
        });

        // Forzar generación de PDF
        $activeFilters['pdf'] = 1;

        $url = route('tesoreria.armas.porte.imprimir-avanzado', $activeFilters);
        $this->emit('openInNewTab', $url);
    }

    public function render()
    {
        return view('livewire.tesoreria.armas.porte-armas-reporte')
            ->extends('layouts.app')
            ->section('content');
    }
}
