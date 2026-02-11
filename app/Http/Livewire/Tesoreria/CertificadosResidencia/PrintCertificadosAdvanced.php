<?php

namespace App\Http\Livewire\Tesoreria\CertificadosResidencia;

use App\Http\Livewire\Shared\BaseReportComponent;
use App\Models\Tesoreria\CertificadoResidencia;

class PrintCertificadosAdvanced extends BaseReportComponent
{
    public $registros;

    protected function setupData()
    {
        $this->titulo = 'Reporte de Certificados de Residencia';

        $query = CertificadoResidencia::query();

        // Aplicar Filtros
        $hasDateRange = !empty($this->filters['fecha_desde']) || !empty($this->filters['fecha_hasta']);

        if (!empty($this->filters['fecha_desde'])) {
            $query->whereDate('fecha_recibido', '>=', $this->filters['fecha_desde']);
        }
        if (!empty($this->filters['fecha_hasta'])) {
            $query->whereDate('fecha_recibido', '<=', $this->filters['fecha_hasta']);
        }

        if (!$hasDateRange) {
            if (!empty($this->filters['mes'])) {
                $query->whereMonth('fecha_recibido', $this->filters['mes']);
            }
            if (!empty($this->filters['year'])) {
                $query->whereYear('fecha_recibido', $this->filters['year']);
            }
        }

        if (!empty($this->filters['titular_nombre'])) {
            $query->where('titular_nombre', 'like', '%' . $this->filters['titular_nombre'] . '%');
        }
        if (!empty($this->filters['titular_apellido'])) {
            $query->where('titular_apellido', 'like', '%' . $this->filters['titular_apellido'] . '%');
        }
        if (!empty($this->filters['titular_nro_documento'])) {
            $query->where('titular_nro_documento', 'like', '%' . $this->filters['titular_nro_documento'] . '%');
        }
        if (!empty($this->filters['numero_recibo'])) {
            $query->where('numero_recibo', 'like', '%' . $this->filters['numero_recibo'] . '%');
        }

        $this->registros = $query->orderBy('fecha_recibido', 'asc')->get();
    }

    protected function getViewName()
    {
        return 'livewire.tesoreria.certificados-residencia.print-certificados-advanced';
    }
}
