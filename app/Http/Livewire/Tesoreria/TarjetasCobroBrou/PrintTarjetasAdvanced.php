<?php

namespace App\Http\Livewire\Tesoreria\TarjetasCobroBrou;

use App\Http\Livewire\Shared\BaseReportComponent;
use App\Models\Tesoreria\TarjetaCobroBrou;

class PrintTarjetasAdvanced extends BaseReportComponent
{
    public $registros;

    protected function setupData()
    {
        $this->titulo = 'Reporte de Tarjetas de Cobro BROU';

        $query = TarjetaCobroBrou::query();

        // Filtro por fecha de recibido
        if (!empty($this->filters['fecha_desde'])) {
            $query->whereDate('fecha_recibido', '>=', $this->filters['fecha_desde']);
        }
        if (!empty($this->filters['fecha_hasta'])) {
            $query->whereDate('fecha_recibido', '<=', $this->filters['fecha_hasta']);
        }

        // Filtro por fecha de entregado
        if (!empty($this->filters['fecha_entregado_desde'])) {
            $query->whereDate('fecha_entregado', '>=', $this->filters['fecha_entregado_desde']);
        }
        if (!empty($this->filters['fecha_entregado_hasta'])) {
            $query->whereDate('fecha_entregado', '<=', $this->filters['fecha_entregado_hasta']);
        }

        // Filtro por fecha de devuelto
        if (!empty($this->filters['fecha_devuelto_desde'])) {
            $query->whereDate('fecha_devuelto', '>=', $this->filters['fecha_devuelto_desde']);
        }
        if (!empty($this->filters['fecha_devuelto_hasta'])) {
            $query->whereDate('fecha_devuelto', '<=', $this->filters['fecha_devuelto_hasta']);
        }

        // Filtro por estado
        if (!empty($this->filters['estado'])) {
            $query->where('estado', $this->filters['estado']);
        }

        // Filtros de búsqueda por titular
        if (!empty($this->filters['titular_cedula'])) {
            $query->where('titular_cedula', 'like', '%' . $this->filters['titular_cedula'] . '%');
        }
        if (!empty($this->filters['titular_nombre'])) {
            $query->where('titular_nombre', 'like', '%' . $this->filters['titular_nombre'] . '%');
        }
        if (!empty($this->filters['titular_apellido'])) {
            $query->where('titular_apellido', 'like', '%' . $this->filters['titular_apellido'] . '%');
        }
        if (!empty($this->filters['numero_tarjeta'])) {
            $query->where('numero_tarjeta', 'like', '%' . $this->filters['numero_tarjeta'] . '%');
        }

        $this->registros = $query->orderBy('fecha_recibido', 'asc')->get();
    }

    protected function getViewName()
    {
        return 'livewire.tesoreria.tarjetas-cobro-brou.print-tarjetas-advanced';
    }
}