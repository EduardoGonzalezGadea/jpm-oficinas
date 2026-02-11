<?php

namespace App\Http\Livewire\Tesoreria\Arrendamientos;

use App\Http\Livewire\Shared\BaseReportComponent;
use App\Models\Tesoreria\Arrendamiento;

class PrintArrendamientosAdvanced extends BaseReportComponent
{
    public $arrendamientos;
    public $total;

    protected function setupData()
    {
        $this->titulo = 'Reporte de Arrendamientos';

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

        $this->arrendamientos = $query->orderBy('fecha', 'asc')
            ->orderBy('recibo', 'asc')
            ->get();

        $this->total = $this->arrendamientos->sum('monto');
    }

    protected function getViewName()
    {
        return 'livewire.tesoreria.arrendamientos.print-arrendamientos-advanced';
    }
}
