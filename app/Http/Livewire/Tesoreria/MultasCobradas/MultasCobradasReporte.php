<?php

namespace App\Http\Livewire\Tesoreria\MultasCobradas;

use Livewire\Component;
use App\Models\Tesoreria\TesMultasCobradas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class MultasCobradasReporte extends Component
{
    public $filters = [];
    public $resultados = null;

    public function mount()
    {
        $this->filters = [
            'mes' => '',
            'year' => date('Y'),
            'nombre' => '',
            'cedula' => '',
            'recibo' => '',
            'monto_min' => '',
            'monto_max' => '',
            'forma_pago' => '',
            'detalle_item' => '',
            'fecha_desde' => date('Y-m-d'),
            'fecha_hasta' => date('Y-m-d'),
            'adenda' => '',
        ];
    }

    public function buscar()
    {
        $useCache = $this->cacheAvailable();
        $cacheKey = $this->getCacheKeyReporteAvanzado($this->filters);

        if ($useCache) {
            try {
                $this->resultados = Cache::remember(
                    $cacheKey,
                    now()->addMinutes(30),
                    function () {
                        return $this->fetchReporteResults();
                    }
                );
                return;
            } catch (\Exception $e) {
                // Si falla el caché, continuar con la consulta directa
            }
        }

        $this->resultados = $this->fetchReporteResults();
    }

    /**
     * Obtiene los resultados del reporte de manera directa (sin caché)
     */
    protected function fetchReporteResults()
    {
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
            $this->applyFlexibleSearch($query, 'nombre', $this->filters['nombre']);
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
            $this->applyFlexibleSearch($query, 'forma_pago', $this->filters['forma_pago']);
        }

        // Búsqueda en items relacionados
        if (!empty($this->filters['detalle_item'])) {
            $term = $this->filters['detalle_item'];
            $normalizedTerm = '%' . $this->normalizeForSearch($term) . '%';

            $query->whereHas('items', function (Builder $q) use ($normalizedTerm) {
                $q->whereRaw("LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(detalle, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u')) LIKE ?", [$normalizedTerm])
                    ->orWhereRaw("LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(descripcion, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u')) LIKE ?", [$normalizedTerm]);
            });
        }

        // Búsqueda en adenda
        if (!empty($this->filters['adenda'])) {
            $this->applyFlexibleSearch($query, 'adenda', $this->filters['adenda']);
        }

        return $query->orderBy('fecha', 'asc')
            ->orderBy('recibo', 'asc')
            ->limit(500)
            ->get();
    }

    /**
     * Genera clave de caché para reportes avanzados
     */
    protected function getCacheKeyReporteAvanzado(array $filters): string
    {
        return 'multas_cobradas.reporte.' . md5(serialize($filters));
    }

    /**
     * Verifica si el caché está disponible y configurado correctamente
     */
    protected function cacheAvailable(): bool
    {
        try {
            // Verificar que el driver no sea 'null'
            if (config('cache.default') === 'null') {
                return false;
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Invalida la caché del reporte avanzado
     */
    protected function invalidateCache(): void
    {
        try {
            $cacheDriver = config('cache.default');

            // Para drivers que soportan patrones (Redis, Memcached)
            if (in_array($cacheDriver, ['redis', 'memcached'])) {
                $this->invalidateCacheByPattern(Cache::getPrefix() . 'multas_cobradas.reporte.*');
            } elseif ($cacheDriver === 'file') {
                // Para driver file, no podemos invalidar por patrón fácilmente
                // Simplemente limpiamos el caché de la aplicación
                Cache::flush();
            }
        } catch (\Exception $e) {
            // Silenciar errores de caché - no debe interrumpir la operación principal
        }
    }

    /**
     * Invalida claves de caché por patrón (solo para drivers que lo soportan)
     */
    protected function invalidateCacheByPattern(string $pattern): void
    {
        try {
            $store = Cache::getStore();

            if ($store instanceof \Illuminate\Cache\RedisStore) {
                $redis = $store->connection();
                $keys = $redis->keys($pattern);

                if (!empty($keys)) {
                    $redis->del($keys);
                }
            } elseif ($store instanceof \Illuminate\Cache\MemcachedStore) {
                // Memcached no soporta búsqueda por patrones directamente
                // Se debe implementar una estrategia diferente (ej: almacenar claves en una lista)
            }
        } catch (\Exception $e) {
            // Silenciar errores - no es crítico
        }
    }

    public function resetFilters()
    {
        $this->filters = [
            'mes' => '',
            'year' => date('Y'),
            'nombre' => '',
            'cedula' => '',
            'recibo' => '',
            'monto_min' => '',
            'monto_max' => '',
            'forma_pago' => '',
            'detalle_item' => '',
            'fecha_desde' => date('Y-m-d'),
            'fecha_hasta' => date('Y-m-d'),
            'adenda' => '',
        ];
        $this->resultados = null;
        $this->invalidateCache();
    }

    public function imprimir()
    {
        if (empty($this->resultados) || $this->resultados->isEmpty()) {
            return;
        }

        $activeFilters = array_filter($this->filters, function ($value) {
            return $value !== '' && $value !== null;
        });

        // Forzar generación de PDF
        $activeFilters['pdf'] = 1;

        $url = route('tesoreria.multas-cobradas.imprimir-avanzado', $activeFilters);
        $this->emit('openInNewTab', $url);
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

    public function render()
    {
        return view('livewire.tesoreria.multas-cobradas.multas-cobradas-reporte')
            ->extends('layouts.app')
            ->section('content');
    }
}
