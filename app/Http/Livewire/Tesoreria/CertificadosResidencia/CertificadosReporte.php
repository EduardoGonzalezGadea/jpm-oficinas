<?php

namespace App\Http\Livewire\Tesoreria\CertificadosResidencia;

use Livewire\Component;
use App\Models\Tesoreria\CertificadoResidencia;

class CertificadosReporte extends Component
{
    public $filters = [];
    public $resultados = null;

    public function mount()
    {
        $this->resetFilters();
    }

    public function resetFilters()
    {
        $this->filters = [
            'mes' => '',
            'year' => date('Y'),
            'titular_nombre' => '',
            'titular_apellido' => '',
            'titular_nro_documento' => '',
            'numero_recibo' => '',
            'fecha_desde' => '',
            'fecha_hasta' => '',
            // Nuevos filtros
            'retira_nombre' => '',
            'retira_apellido' => '',
            'retira_nro_documento' => '',
            'estado' => '',
            'fecha_entregado_desde' => '',
            'fecha_entregado_hasta' => '',
            'fecha_devuelto_desde' => '',
            'fecha_devuelto_hasta' => '',
        ];
        $this->resultados = null;
    }

    public function buscar()
    {
        $query = CertificadoResidencia::query();

        // Aplicar Filtros de Fecha Recibido
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

        // Búsqueda Flexible para Titular
        if (!empty($this->filters['titular_nombre'])) {
            $this->applyFlexibleSearch($query, 'titular_nombre', $this->filters['titular_nombre']);
        }
        if (!empty($this->filters['titular_apellido'])) {
            $this->applyFlexibleSearch($query, 'titular_apellido', $this->filters['titular_apellido']);
        }
        if (!empty($this->filters['titular_nro_documento'])) {
            $query->where('titular_nro_documento', 'like', '%' . $this->filters['titular_nro_documento'] . '%');
        }

        // Búsqueda Flexible para Quien Retira
        if (!empty($this->filters['retira_nombre'])) {
            $this->applyFlexibleSearch($query, 'retira_nombre', $this->filters['retira_nombre']);
        }
        if (!empty($this->filters['retira_apellido'])) {
            $this->applyFlexibleSearch($query, 'retira_apellido', $this->filters['retira_apellido']);
        }
        if (!empty($this->filters['retira_nro_documento'])) {
            $query->where('retira_nro_documento', 'like', '%' . $this->filters['retira_nro_documento'] . '%');
        }

        // Otros Filtros
        if (!empty($this->filters['numero_recibo'])) {
            $query->where('numero_recibo', 'like', '%' . $this->filters['numero_recibo'] . '%');
        }
        if (!empty($this->filters['estado'])) {
            $query->where('estado', $this->filters['estado']);
        }


        // Otros Rangos de Fecha
        if (!empty($this->filters['fecha_entregado_desde'])) {
            $query->whereDate('fecha_entregado', '>=', $this->filters['fecha_entregado_desde']);
        }
        if (!empty($this->filters['fecha_entregado_hasta'])) {
            $query->whereDate('fecha_entregado', '<=', $this->filters['fecha_entregado_hasta']);
        }
        if (!empty($this->filters['fecha_devuelto_desde'])) {
            $query->whereDate('fecha_devuelto', '>=', $this->filters['fecha_devuelto_desde']);
        }
        if (!empty($this->filters['fecha_devuelto_hasta'])) {
            $query->whereDate('fecha_devuelto', '<=', $this->filters['fecha_devuelto_hasta']);
        }

        $this->resultados = $query->orderBy('fecha_recibido', 'asc')
            ->limit(500)
            ->get();
    }

    /**
     * Aplica una búsqueda que ignora mayúsculas, minúsculas y tildes
     */
    protected function applyFlexibleSearch($query, $column, $value)
    {
        $normalized = '%' . $this->normalizeForSearch($value) . '%';
        $query->whereRaw("LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$column}, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u')) LIKE ?", [$normalized]);
    }

    /**
     * Normaliza un texto para búsqueda (minúsculas y sin tildes)
     */
    protected function normalizeForSearch($text)
    {
        $accents = ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú'];
        $normals = ['a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u'];
        return strtolower(str_replace($accents, $normals, $text));
    }

    public function imprimir()
    {
        if (empty($this->resultados) || $this->resultados->isEmpty()) {
            return;
        }

        $activeFilters = array_filter($this->filters, function ($value) {
            return $value !== '' && $value !== null;
        });

        $url = route('tesoreria.certificados-residencia.imprimir-avanzado', $activeFilters);
        $this->emit('openInNewTab', $url);
    }

    public function render()
    {
        return view('livewire.tesoreria.certificados-residencia.certificados-reporte')
            ->extends('layouts.app')
            ->section('content');
    }
}
