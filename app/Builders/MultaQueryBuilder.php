<?php

namespace App\Builders;

use App\Models\Tesoreria\Multa as MultaModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query Builder optimizado para el modelo Multa
 *
 * Centraliza la lógica de consulta y optimiza el rendimiento
 * mediante la selección de campos específicos y búsqueda indexada.
 */
class MultaQueryBuilder
{
    /**
     * Campos básicos necesarios para el listado
     */
    public const CAMPOS_LISTADO = [
        'id',
        'articulo',
        'apartado',
        'articulo_completo',
        'descripcion',
        'moneda',
        'importe_original',
        'importe_unificado',
        'decreto'
    ];

    /**
     * Campos mínimos para búsqueda rápida
     */
    public const CAMPOS_BUSQUEDA_RAPIDA = [
        'id',
        'articulo',
        'apartado',
        'articulo_completo',
        'descripcion'
    ];

    /**
     * Crea un query builder optimizado para listado
     *
     * @param array $params Parámetros de búsqueda y ordenamiento
     * @return Builder
     */
    public static function forList(array $params = []): Builder
    {
        $query = MultaModel::select(self::CAMPOS_LISTADO);

        // Aplicar búsqueda si existe
        if (!empty($params['search'])) {
            self::applySearch($query, $params['search']);
        }

        // Aplicar ordenamiento
        $sortField = $params['sortField'] ?? 'articulo';
        $sortDirection = $params['sortDirection'] ?? 'asc';
        $query->orderBy($sortField, $sortDirection);

        return $query;
    }

    /**
     * Crea un query builder para búsqueda rápida (autocompletado)
     *
     * @param string $search Término de búsqueda
     * @param int $limit Límite de resultados
     * @return Builder
     */
    public static function forQuickSearch(string $search, int $limit = 20): Builder
    {
        return MultaModel::select(self::CAMPOS_BUSQUEDA_RAPIDA)
            ->where(function ($query) use ($search) {
                self::applySearch($query, $search);
            })
            ->limit($limit)
            ->orderBy('articulo', 'asc');
    }

    /**
     * Aplica búsqueda optimizada usando índices
     *
     * @param Builder $query
     * @param string $search
     * @return void
     */
    protected static function applySearch(Builder $query, string $search): void
    {
        $search = trim($search);

        if (empty($search)) {
            return;
        }

        $query->where(function ($q) use ($search) {
            // Búsqueda por artículo (usa índice)
            $q->where('articulo', 'like', $search . '%')

            // Búsqueda por artículo_completo (usa índice dedicado)
            // Formato: "103.2A" - mucho más rápido que CONCAT
            ->orWhere('articulo_completo', 'like', $search . '%')

            // Búsqueda por descripción (usa índice parcial)
            ->orWhere('descripcion', 'like', '%' . $search . '%');
        });
    }

    /**
     * Obtiene el total de multas activas (cached)
     *
     * @return int
     */
    public static function countActive(): int
    {
        return cache()->remember('multas.count_active', now()->addDay(), function () {
            return MultaModel::count();
        });
    }

    /**
     * Obtiene estadísticas básicas del sistema de multas
     *
     * @return array
     */
    public static function getStats(): array
    {
        return cache()->remember('multas.stats', now()->addHours(6), function () {
            return [
                'total' => MultaModel::count(),
                'con_apartado' => MultaModel::whereNotNull('apartado')->count(),
                'sin_apartado' => MultaModel::whereNull('apartado')->count(),
                'monedas' => MultaModel::selectRaw('moneda, count(*) as total')
                    ->groupBy('moneda')
                    ->pluck('total', 'moneda')
                    ->toArray(),
            ];
        });
    }

    /**
     * Invalida las cachés relacionadas con multas
     *
     * @return void
     */
    public static function clearCache(): void
    {
        cache()->forget('multas.count_active');
        cache()->forget('multas.stats');

        // Invalidar cachés de listado por patrón
        $driver = config('cache.default');

        if (in_array($driver, ['redis', 'memcached'])) {
            // Para Redis/Memcached, usar patrón
            $prefix = config('cache.prefix');
            // Nota: La implementación específica depende del driver
        }
    }
}
