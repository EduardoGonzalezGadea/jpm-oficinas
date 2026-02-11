<?php

namespace App\Http\Livewire\Tesoreria\Eventuales;

use App\Http\Livewire\Shared\BaseReportComponent;
use App\Models\Tesoreria\Eventual;
use Illuminate\Support\Facades\DB;

class PrintEventualesAdvanced extends BaseReportComponent
{
    public $eventuales;
    public $total;

    protected function setupData()
    {
        $this->titulo = 'Reporte de Eventuales';

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

        $this->eventuales = $query->orderBy('fecha', 'asc')
            ->orderBy('recibo', 'asc')
            ->get();

        $this->total = $this->eventuales->sum('monto');
    }

    protected function getViewName()
    {
        return 'livewire.tesoreria.eventuales.print-eventuales-advanced';
    }
}
