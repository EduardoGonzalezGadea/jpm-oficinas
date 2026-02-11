<?php

namespace App\Http\Livewire\Tesoreria\Armas;

use App\Http\Livewire\Shared\BaseReportComponent;
use App\Models\Tesoreria\TesTenenciaArmas;

class PrintTenenciaArmasAdvanced extends BaseReportComponent
{
    public $registros;
    public $total;

    protected function setupData()
    {
        $this->titulo = 'Reporte de Tenencia de Armas';

        $query = TesTenenciaArmas::query();

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
        if (!empty($this->filters['recibo'])) {
            $query->where('recibo', 'like', '%' . $this->filters['recibo'] . '%');
        }

        $this->registros = $query->orderBy('fecha', 'asc')->get();
        $this->total = $this->registros->sum('monto');
    }

    protected function getViewName()
    {
        return 'livewire.tesoreria.armas.print-tenencia-armas-advanced';
    }
}
