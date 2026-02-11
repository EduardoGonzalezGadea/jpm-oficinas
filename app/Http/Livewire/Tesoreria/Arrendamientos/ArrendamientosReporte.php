<?php

namespace App\Http\Livewire\Tesoreria\Arrendamientos;

use Livewire\Component;
use App\Models\Tesoreria\Arrendamiento;

class ArrendamientosReporte extends Component
{
    public $filters = [];
    public $resultados = null;

    public function mount()
    {
        $this->filters = [
            'mes' => '',
            'year' => date('Y'),
            'nombre' => '',
            'cedula' => '',
            'monto_min' => '',
            'monto_max' => '',
            'recibo' => '',
            'orden_cobro' => '',
            'fecha_desde' => '',
            'fecha_hasta' => '',
        ];
    }

    public function resetFilters()
    {
        $this->filters = [
            'mes' => '',
            'year' => date('Y'),
            'nombre' => '',
            'cedula' => '',
            'monto_min' => '',
            'monto_max' => '',
            'recibo' => '',
            'orden_cobro' => '',
            'fecha_desde' => '',
            'fecha_hasta' => '',
        ];
        $this->resultados = null;
    }

    public function buscar()
    {
        $query = Arrendamiento::query();

        // Aplicar Filtros
        $hasDateRange = !empty($this->filters['fecha_desde']) || !empty($this->filters['fecha_hasta']);

        if (!empty($this->filters['fecha_desde'])) {
            $query->whereDate('fecha', '>=', $this->filters['fecha_desde']);
        }
        if (!empty($this->filters['fecha_hasta'])) {
            $query->whereDate('fecha', '<=', $this->filters['fecha_hasta']);
        }

        // Solo aplicar Mes/Año si NO hay rango de fechas seleccionado
        // PERO: Si el usuario borra la fecha manualmente, la cadena vacía llega.
        if (!$hasDateRange) {
            if (!empty($this->filters['mes'])) {
                $query->whereMonth('fecha', $this->filters['mes']);
            }
            if (!empty($this->filters['year'])) {
                $query->whereYear('fecha', $this->filters['year']);
            }
        }

        if (!empty($this->filters['nombre'])) {
            $query->where('nombre', 'like', '%' . $this->filters['nombre'] . '%');
        }
        if (!empty($this->filters['cedula'])) {
            $query->where('cedula', 'like', '%' . $this->filters['cedula'] . '%');
        }
        if (!empty($this->filters['monto_min'])) {
            $query->where('monto', '>=', $this->filters['monto_min']);
        }
        if (!empty($this->filters['monto_max'])) {
            $query->where('monto', '<=', $this->filters['monto_max']);
        }
        if (!empty($this->filters['recibo'])) {
            $query->where('recibo', 'like', '%' . $this->filters['recibo'] . '%');
        }
        if (!empty($this->filters['orden_cobro'])) {
            $query->where('orden_cobro', 'like', '%' . $this->filters['orden_cobro'] . '%');
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

        $url = route('tesoreria.arrendamientos.imprimir-avanzado', $activeFilters);
        $this->emit('openInNewTab', $url);
    }

    public function render()
    {
        return view('livewire.tesoreria.arrendamientos.arrendamientos-reporte')
            ->extends('layouts.app')
            ->section('content');
    }
}
