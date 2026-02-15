<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

/**
 * Trait para manejo optimizado de caché de multas
 *
 * Implementa caché selectivo que solo invalida las claves relacionadas
 * con multas, evitando limpiar toda la caché del sistema.
 */
trait CacheMultaTrait
{
    /**
     * Prefijo para todas las claves de caché de multas
     */
    protected static string $cachePrefix = 'multas';

    /**
     * Genera una clave de caché única para listados
     *
     * @param array $params Parámetros de búsqueda y paginación
     * @return string Clave de caché
     */
    protected function getMultasCacheKey(array $params = []): string
    {
        $defaultParams = [
            'search' => $this->search ?? '',
            'perPage' => $this->perPage ?? 50,
            'sortField' => $this->sortField ?? 'articulo',
            'sortDirection' => $this->sortDirection ?? 'asc',
            'page' => $this->page ?? 1,
        ];

        $mergedParams = array_merge($defaultParams, $params);

        return sprintf(
            '%s.list.%s',
            self::$cachePrefix,
            md5(serialize($mergedParams))
        );
    }

    /**
     * Genera una clave de caché para contadores
     *
     * @return string Clave de caché
     */
    protected function getMultasCountCacheKey(): string
    {
        return sprintf(
            '%s.count.%s',
            self::$cachePrefix,
            md5($this->search ?? '')
        );
    }

    /**
     * Invalida solo las claves de caché relacionadas con multas
     *
     * @return void
     */
    protected function invalidateMultasCache(): void
    {
        $driver = Cache::getDefaultDriver();

        try {
            if (in_array($driver, ['redis', 'memcached', 'dynamodb'])) {
                $this->invalidateCacheByPattern(self::$cachePrefix . '.*');
            } else {
                // Para file driver, invalidar claves específicas conocidas
                $this->invalidateFileCache();
            }
        } catch (\Exception $e) {
            // Si falla la invalidación selectiva, loggear pero no romper
            \Log::warning('Error invalidando caché de multas: ' . $e->getMessage());
        }
    }

    /**
     * Invalida caché por patrón (para Redis/Memcached)
     *
     * @param string $pattern Patrón de claves a eliminar
     * @return void
     */
    protected function invalidateCacheByPattern(string $pattern): void
    {
        $prefix = Cache::getPrefix();
        $fullPattern = $prefix . $pattern;

        $store = Cache::getStore();

        if ($store instanceof \Illuminate\Cache\RedisStore) {
            $redis = $store->connection();
            $keys = $redis->keys($fullPattern);

            if (!empty($keys)) {
                $redis->del($keys);
            }
        }
    }

    /**
     * Invalida caché para file driver
     *
     * @return void
     */
    protected function invalidateFileCache(): void
    {
        // Invalidar claves comunes
        $commonKeys = [
            $this->getMultasCacheKey(),
            $this->getMultasCountCacheKey(),
        ];

        foreach ($commonKeys as $key) {
            Cache::forget($key);
        }

        // Invalidar claves de páginas comunes
        for ($page = 1; $page <= 10; $page++) {
            Cache::forget($this->getMultasCacheKey(['page' => $page]));
        }
    }

    /**
     * Obtiene el TTL dinámico basado en el tipo de consulta
     *
     * @return \DateTimeInterface
     */
    protected function getMultasCacheTTL(): \DateTimeInterface
    {
        // Sin búsqueda: caché más largo (datos estáticos)
        // Con búsqueda: caché más corto (datos variables)
        return empty($this->search)
            ? now()->addDays(7)
            : now()->addHours(6);
    }

    /**
     * Limpia toda la caché de multas (usar con cuidado)
     *
     * @return void
     */
    public static function clearAllMultasCache(): void
    {
        $driver = Cache::getDefaultDriver();
        $store = Cache::getStore();

        if ($driver === 'redis' && $store instanceof \Illuminate\Cache\RedisStore) {
            $prefix = Cache::getPrefix();
            $redis = $store->connection();
            $keys = $redis->keys($prefix . self::$cachePrefix . '.*');

            if (!empty($keys)) {
                $redis->del($keys);
            }
        } else {
            // Para file driver, no hay forma eficiente de limpiar por patrón
            // Se recomienda usar Cache::flush() solo si es necesario
        }
    }
}
