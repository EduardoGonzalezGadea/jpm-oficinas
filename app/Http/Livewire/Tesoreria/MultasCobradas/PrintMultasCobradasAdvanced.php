<?php

namespace App\Http\Livewire\Tesoreria\MultasCobradas;

use App\Http\Livewire\Shared\BaseReportComponent;
use App\Models\Tesoreria\TesMultasCobradas;
use Illuminate\Database\Eloquent\Builder;

class PrintMultasCobradasAdvanced extends BaseReportComponent
{
    public $multas;
    public $total;

    protected function setupData()
    {
        $this->titulo = 'Reporte de Multas Cobradas';

        $query = TesMultasCobradas::with('items');

        // Filtros
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
        if (!empty($this->filters['nombre'])) {
            $query->where('nombre', 'like', '%' . $this->filters['nombre'] . '%');
        }
        if (!empty($this->filters['cedula'])) {
            $query->where('cedula', 'like', '%' . $this->filters['cedula'] . '%');
        }
        if (!empty($this->filters['recibo'])) {
            $query->where('recibo', 'like', '%' . $this->filters['recibo'] . '%');
        }
        if (!empty($this->filters['monto_min'])) {
            $query->where('monto', '>=', $this->filters['monto_min']);
        }
        if (!empty($this->filters['monto_max'])) {
            $query->where('monto', '<=', $this->filters['monto_max']);
        }
        if (!empty($this->filters['forma_pago'])) {
            $query->where('forma_pago', 'like', '%' . $this->filters['forma_pago'] . '%');
        }

        // Búsqueda en items relacionados
        if (!empty($this->filters['detalle_item'])) {
            $term = $this->filters['detalle_item'];
            $query->whereHas('items', function (Builder $q) use ($term) {
                $q->where('detalle', 'like', '%' . $term . '%')
                    ->orWhere('descripcion', 'like', '%' . $term . '%');
            });
        }

        $this->multas = $query->orderBy('fecha', 'asc')
            ->orderBy('recibo', 'asc')
            ->get();

        $this->total = $this->multas->sum('monto');
    }

    protected function getViewName()
    {
        return 'livewire.tesoreria.multas-cobradas.print-multas-cobradas-advanced';
    }
}
