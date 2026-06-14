<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para auditoría de descargas de datos desde URLs externas.
 *
 * Registra cada intento de descargar datos (UR, Hora, SOA) para debugging y monitoreo.
 */
class ExternalDownloadLog extends Model
{
    protected $table = 'external_download_logs';

    public $timestamps = false; // Manual control de timestamps

    protected $fillable = [
        'service_name',
        'url',
        'status',
        'http_status',
        'duration_ms',
        'content_length',
        'proxy_used',
        'cache_hit',
        'error_message',
        'created_at',
    ];

    protected $casts = [
        'cache_hit' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Scope: filtrar por servicio
     */
    public function scopeForService($query, string $serviceName)
    {
        return $query->where('service_name', $serviceName);
    }

    /**
     * Scope: filtrar por estado
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: filtrar por rango de fechas
     */
    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Registra un intento de descarga
     */
    public static function log(
        string $serviceName,
        string $url,
        string $status,
        array $details = []
    ): self {
        return static::create([
            'service_name' => $serviceName,
            'url' => $url,
            'status' => $status,
            'http_status' => $details['http_status'] ?? null,
            'duration_ms' => $details['duration_ms'] ?? null,
            'content_length' => $details['content_length'] ?? null,
            'proxy_used' => $details['proxy_used'] ?? 'none',
            'cache_hit' => $details['cache_hit'] ?? false,
            'error_message' => $details['error_message'] ?? null,
            'created_at' => now(),
        ]);
    }

    /**
     * Limpia logs antiguos
     */
    public static function cleanOldLogs(int $retentionDays = 30): int
    {
        return static::where('created_at', '<', now()->subDays($retentionDays))->delete();
    }
}
