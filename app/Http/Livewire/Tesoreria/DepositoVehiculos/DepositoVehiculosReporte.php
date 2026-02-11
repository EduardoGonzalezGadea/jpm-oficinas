<?php

namespace App\Http\Livewire\Tesoreria\DepositoVehiculos;

use Livewire\Component;
use App\Models\Tesoreria\DepositoVehiculo;
use App\Models\Tesoreria\MedioDePago;

class DepositoVehiculosReporte extends Component
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
            'titular' => '',
            'cedula' => '',
            'recibo_serie' => '',
            'recibo_numero' => '',
            'orden_cobro' => '',
            'fecha_desde' => '',
            'fecha_hasta' => '',
            'medio_pago_id' => '',
            'monto_min' => '',
            'monto_max' => '',
        ];
        $this->resultados = null;
    }

    public function buscar()
    {
        $query = DepositoVehiculo::query()->with('medioPago');

        // Filtros de Fecha (Recibo)
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

        // Búsqueda Flexible para Titular
        if (!empty($this->filters['titular'])) {
            $this->applyFlexibleSearch($query, 'titular', $this->filters['titular']);
        }

        if (!empty($this->filters['cedula'])) {
            $query->where('cedula', 'like', '%' . $this->filters['cedula'] . '%');
        }

        if (!empty($this->filters['recibo_serie'])) {
            $query->where('recibo_serie', 'like', '%' . $this->filters['recibo_serie'] . '%');
        }

        if (!empty($this->filters['recibo_numero'])) {
            $query->where('recibo_numero', 'like', '%' . $this->filters['recibo_numero'] . '%');
        }

        if (!empty($this->filters['orden_cobro'])) {
            $query->where('orden_cobro', 'like', '%' . $this->filters['orden_cobro'] . '%');
        }

        if (!empty($this->filters['medio_pago_id'])) {
            $query->where('medio_pago_id', $this->filters['medio_pago_id']);
        }

        // Filtros de Monto
        if (!empty($this->filters['monto_min'])) {
            $query->where('monto', '>=', $this->filters['monto_min']);
        }
        if (!empty($this->filters['monto_max'])) {
            $query->where('monto', '<=', $this->filters['monto_max']);
        }

        $this->resultados = $query->orderBy('recibo_fecha', 'desc')
            ->limit(500)
            ->get();
    }

    protected function applyFlexibleSearch($query, $column, $value)
    {
        $normalized = '%' . $this->normalizeForSearch($value) . '%';
        $query->whereRaw("LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$column}, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u')) LIKE ?", [$normalized]);
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

        $url = route('tesoreria.deposito-vehiculos.imprimir-avanzado', $activeFilters);
        $this->emit('openInNewTab', $url);
    }

    public function render()
    {
        return view('livewire.tesoreria.deposito-vehiculos.deposito-vehiculos-reporte', [
            'mediosPago' => MedioDePago::activos()->ordenado()->get()
        ])
            ->extends('layouts.app')
            ->section('content');
    }
}
