<?php

namespace App\Http\Controllers;

use App\Models\ExternalDownloadLog;
use App\Models\TesCfePendiente;
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
     * Retorna estado del pipeline CFE: procesamiento, pendientes, tasas de éxito.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function cfeHealth()
    {
        $now = now();
        $todayStart = $now->copy()->startOfDay();

        // Últimas 24h
        $ultimas24h = TesCfePendiente::where('created_at', '>=', $now->copy()->subHours(24));
        $recibidos24h = $ultimas24h->count();
        $confirmados24h = (clone $ultimas24h)->where('estado', 'confirmado')->count();
        $fallidos24h = (clone $ultimas24h)->where('estado', 'rechazado')->count();
        $pendientes24h = (clone $ultimas24h)->whereIn('estado', ['pendiente', 'en_revision'])->count();

        $tasaExito = $recibidos24h > 0 ? round(($confirmados24h / $recibidos24h) * 100, 1) : null;

        // Estado actual de pendientes
        $pendientesAhora = TesCfePendiente::whereIn('estado', ['pendiente', 'en_revision'])->count();
        $pendientesViejos = TesCfePendiente::whereIn('estado', ['pendiente', 'en_revision'])
            ->where('created_at', '<', $now->copy()->subDays(3))
            ->count();

        // Totales acumulados
        $totalRecibidos = TesCfePendiente::count();
        $totalConfirmados = TesCfePendiente::where('estado', 'confirmado')->count();
        $totalTasaExito = $totalRecibidos > 0 ? round(($totalConfirmados / $totalRecibidos) * 100, 1) : null;

        return response()->json([
            'healthy' => $tasaExito === null || $tasaExito >= 80,
            'timestamp' => $now->toIso8601String(),
            'ultimas_24h' => [
                'recibidos' => $recibidos24h,
                'confirmados' => $confirmados24h,
                'fallidos' => $fallidos24h,
                'pendientes' => $pendientes24h,
                'tasa_exito_pct' => $tasaExito,
            ],
            'pendientes' => [
                'ahora' => $pendientesAhora,
                'mas_de_3_dias' => $pendientesViejos,
            ],
            'historial' => [
                'total_recibidos' => $totalRecibidos,
                'total_confirmados' => $totalConfirmados,
                'tasa_exito_total_pct' => $totalTasaExito,
            ],
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
