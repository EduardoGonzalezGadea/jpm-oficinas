<?php

namespace App\Http\Controllers;

use App\Models\ExternalDownloadLog;
use Illuminate\Support\Facades\Cache;

/**
 * Health check endpoints para monitoreo de salud de la aplicación.
 */
class HealthCheckController extends Controller
{
    /**
     * Retorna el estado de las descargas externas
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function externalDownloads()
    {
        $services = ['valor_ur', 'sincronizacion_hora', 'valores_soa'];
        $status = [];

        foreach ($services as $service) {
            // Buscar el último intento (últimos 7 días)
            $lastLog = ExternalDownloadLog::forService($service)
                ->recent(10080) // 7 días
                ->latest('created_at')
                ->first();

            if (!$lastLog) {
                $status[$service] = [
                    'status' => 'unknown',
                    'last_attempt' => null,
                    'last_status' => null,
                ];
            } else {
                $isStale = $lastLog->created_at < now()->subHours(24);

                $status[$service] = [
                    'status' => $lastLog->status === 'success' ? '✅' : '❌',
                    'last_attempt' => $lastLog->created_at->toIso8601String(),
                    'last_status' => $lastLog->status,
                    'stale' => $isStale,
                    'age_minutes' => $lastLog->created_at->diffInMinutes(now()),
                ];
            }
        }

        // Health general
        $allHealthy = collect($status)
            ->every(fn ($s) => $s['last_status'] === 'success' && !($s['stale'] ?? false));

        return response()->json([
            'healthy' => $allHealthy,
            'timestamp' => now()->toIso8601String(),
            'services' => $status,
        ]);
    }

    /**
     * Retorna estadísticas de descargas de los últimos días
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function externalDownloadsStats()
    {
        $stats = [];
        $services = ['valor_ur', 'sincronizacion_hora', 'valores_soa'];

        foreach ($services as $service) {
            $logs = ExternalDownloadLog::forService($service)->recent(1440); // últimas 24h

            $stats[$service] = [
                'total_attempts' => $logs->count(),
                'successful' => $logs->whereStatus('success')->count(),
                'failed' => $logs->whereStatus('failure')->count(),
                'success_rate' => $logs->count() > 0
                    ? round(($logs->whereStatus('success')->count() / $logs->count()) * 100, 2)
                    : null,
                'avg_duration_ms' => round($logs->whereNotNull('duration_ms')->avg('duration_ms') ?? 0, 2),
            ];
        }

        return response()->json([
            'period' => 'last_24h',
            'timestamp' => now()->toIso8601String(),
            'stats' => $stats,
        ]);
    }
}
