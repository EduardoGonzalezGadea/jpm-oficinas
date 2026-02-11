<?php

namespace App\Http\Livewire\Tesoreria\Prendas;

use Livewire\Component;
use App\Models\Tesoreria\Prenda;

/**
 * Componente Livewire: Reportes de Prendas
 *
 * Permite generar reportes avanzados de prendas con múltiples filtros.
 */
class PrendasReporte extends Component
{
    public $filters = [];
    public $resultados = null;

    public function mount()
    {
        $this->filters = [
            'mes' => '',
            'year' => date('Y'),
            'titular_nombre' => '',
            'titular_cedula' => '',
            'recibo_numero' => '',
            'orden_cobro' => '',
            'fecha_desde' => '',
            'fecha_hasta' => '',
            'concepto' => '',
            'medio_pago_id' => '',
        ];
    }

    public function resetFilters()
    {
        $this->filters = [
            'mes' => '',
            'year' => date('Y'),
            'titular_nombre' => '',
            'titular_cedula' => '',
            'recibo_numero' => '',
            'orden_cobro' => '',
            'fecha_desde' => '',
            'fecha_hasta' => '',
            'concepto' => '',
            'medio_pago_id' => '',
        ];
        $this->resultados = null;
    }

    public function buscar()
    {
        $query = Prenda::query();

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
            $this->applyFlexibleSearch($query, 'titular_nombre', $this->filters['titular_nombre']);
        }

        if (!empty($this->filters['titular_cedula'])) {
            $query->where('titular_cedula', 'like', '%' . $this->filters['titular_cedula'] . '%');
        }

        if (!empty($this->filters['recibo_numero'])) {
            $query->where('recibo_numero', 'like', '%' . $this->filters['recibo_numero'] . '%');
        }

        if (!empty($this->filters['concepto'])) {
            $this->applyFlexibleSearch($query, 'concepto', $this->filters['concepto']);
        }

        if (!empty($this->filters['orden_cobro'])) {
            $query->where('orden_cobro', 'like', '%' . $this->filters['orden_cobro'] . '%');
        }

        if (!empty($this->filters['medio_pago_id'])) {
            $query->where('medio_pago_id', $this->filters['medio_pago_id']);
        }

        $this->resultados = $query->with('medioPago')
            ->orderBy('recibo_fecha', 'desc')
            ->limit(500)
            ->get();
    }

    protected function applyFlexibleSearch($query, $column, $value)
    {
        $normalized = '%' . $this->normalizeForSearch($value) . '%';
        $query->whereRaw(
            "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$column}, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u')) LIKE ?",
            [$normalized]
        );
    }

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

        $url = route('tesoreria.prendas.imprimir-avanzado', $activeFilters);
        $this->emit('openInNewTab', $url);
    }

    public function render()
    {
        return view('livewire.tesoreria.prendas.prendas-reporte', [
            'mediosPago' => \App\Models\Tesoreria\MedioDePago::activos()->ordenado()->get()
        ])
            ->extends('layouts.app')
            ->section('content');
    }
}
