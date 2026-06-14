<?php

namespace App\Http\Livewire\Tesoreria\TarjetasCobroBrou;

use Livewire\Component;
use App\Models\Tesoreria\TarjetaCobroBrou;

class TarjetasReporte extends Component
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
            'fecha_desde' => '',
            'fecha_hasta' => '',
            'fecha_entregado_desde' => '',
            'fecha_entregado_hasta' => '',
            'fecha_devuelto_desde' => '',
            'fecha_devuelto_hasta' => '',
            'estado' => '',
            'titular_cedula' => '',
            'titular_nombre' => '',
            'titular_apellido' => '',
            'numero_tarjeta' => '',
        ];
        $this->resultados = null;
    }

    public function buscar()
    {
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
            $this->applyFlexibleSearch($query, 'titular_cedula', $this->filters['titular_cedula']);
        }
        if (!empty($this->filters['titular_nombre'])) {
            $this->applyFlexibleSearch($query, 'titular_nombre', $this->filters['titular_nombre']);
        }
        if (!empty($this->filters['titular_apellido'])) {
            $this->applyFlexibleSearch($query, 'titular_apellido', $this->filters['titular_apellido']);
        }
        if (!empty($this->filters['numero_tarjeta'])) {
            $query->where('numero_tarjeta', 'like', '%' . $this->filters['numero_tarjeta'] . '%');
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

        $url = route('tesoreria.tarjetas-cobro-brou.imprimir-avanzado', $activeFilters);
        $this->emit('openInNewTab', $url);
    }

    public function render()
    {
        return view('livewire.tesoreria.tarjetas-cobro-brou.tarjetas-reporte')
            ->extends('layouts.app')
            ->section('content');
    }
}