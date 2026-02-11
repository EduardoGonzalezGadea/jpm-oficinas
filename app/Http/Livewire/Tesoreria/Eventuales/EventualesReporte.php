<?php

namespace App\Http\Livewire\Tesoreria\Eventuales;

use Livewire\Component;
use App\Models\Tesoreria\Eventual;

class EventualesReporte extends Component
{
    public $filters = [];
    public $resultados = null;

    public function mount()
    {
        $this->filters = [
            'mes' => '',
            'year' => date('Y'),
            'institucion' => '',
            'titular' => '',
            'monto_min' => '',
            'monto_max' => '',
            'ingreso' => '',
            'fecha_desde' => '',
            'fecha_hasta' => '',
        ];
    }

    public function resetFilters()
    {
        $this->filters = [
            'mes' => '',
            'year' => date('Y'),
            'institucion' => '',
            'titular' => '',
            'monto_min' => '',
            'monto_max' => '',
            'ingreso' => '',
            'fecha_desde' => '',
            'fecha_hasta' => '',
        ];
        $this->resultados = null;
    }

    public function buscar()
    {
        $query = Eventual::query();

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

        if (!empty($this->filters['institucion'])) {
            $query->where('institucion', 'like', '%' . $this->filters['institucion'] . '%');
        }
        if (!empty($this->filters['titular'])) {
            $query->where('titular', 'like', '%' . $this->filters['titular'] . '%');
        }
        if (!empty($this->filters['monto_min'])) {
            $query->where('monto', '>=', $this->filters['monto_min']);
        }
        if (!empty($this->filters['monto_max'])) {
            $query->where('monto', '<=', $this->filters['monto_max']);
        }
        if (!empty($this->filters['ingreso'])) {
            $query->where('ingreso', 'like', '%' . $this->filters['ingreso'] . '%');
        }

        $this->resultados = $query->orderBy('fecha', 'asc')
            ->orderBy('recibo', 'asc')
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

        $url = route('tesoreria.eventuales.imprimir-avanzado', $activeFilters);
        $this->emit('openInNewTab', $url);
    }

    public function render()
    {
        return view('livewire.tesoreria.eventuales.eventuales-reporte')
            ->extends('layouts.app')
            ->section('content');
    }
}
