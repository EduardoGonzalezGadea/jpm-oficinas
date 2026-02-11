<?php

namespace App\Http\Livewire\Tesoreria\Prendas;

use App\Http\Livewire\Shared\BaseReportComponent;
use App\Models\Tesoreria\Prenda;

class PrintPrendasAdvanced extends BaseReportComponent
{
    public $registros;
    public $total;

    protected function setupData()
    {
        $this->titulo = 'Reporte de Prendas';

        $query = Prenda::query();

        // Aplicar Filtros
        $hasDateRange = !empty($this->filters['fecha_desde']) || !empty($this->filters['fecha_hasta']);

        if (!empty($this->filters['fecha_desde'])) {
            $query->whereDate('recibo_fecha', '>=', $this->filters['fecha_desde']);
        }
        if (!empty($this->filters['fecha_hasta'])) {
            $query->whereDate('recibo_fecha', '<=', $this->filters['fecha_hasta']);
        }

        if (!$hasDateRange) {
            if (!empty($this->filters['mes'])) {
                $query->whereMonth('recibo_fecha', $this->filters['mes']);
            }
            if (!empty($this->filters['year'])) {
                $query->whereYear('recibo_fecha', $this->filters['year']);
            }
        }

        if (!empty($this->filters['titular_nombre'])) {
            $query->where('titular_nombre', 'like', '%' . $this->filters['titular_nombre'] . '%');
        }
        if (!empty($this->filters['titular_cedula'])) {
            $query->where('titular_cedula', 'like', '%' . $this->filters['titular_cedula'] . '%');
        }
        if (!empty($this->filters['recibo_numero'])) {
            $query->where('recibo_numero', 'like', '%' . $this->filters['recibo_numero'] . '%');
        }
        if (!empty($this->filters['orden_cobro'])) {
            $query->where('orden_cobro', 'like', '%' . $this->filters['orden_cobro'] . '%');
        }

        $this->registros = $query->orderBy('recibo_fecha', 'asc')
            ->orderBy('recibo_numero', 'asc')
            ->get();

        $this->total = $this->registros->sum('monto');
    }

    protected function getViewName()
    {
        return 'livewire.tesoreria.prendas.print-prendas-advanced';
    }
}
