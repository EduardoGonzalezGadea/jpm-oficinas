<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Detector de intentos de fuerza bruta.
 * Rastrea intentos fallidos de login por IP y genera alertas.
 */
class BruteForceDetector
{
    /**
     * Clave del cache para intentos fallidos.
     */
    private const CACHE_PREFIX = 'login_failed_';

    /**
     * Número máximo de intentos fallidos antes de alertar.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Ventana de tiempo en minutos para contar intentos.
     */
    private const WINDOW_MINUTES = 15;

    /**
     * Tiempo de bloqueo en minutos después de exceder el límite.
     */
    private const LOCKOUT_MINUTES = 30;

    /**
     * Registrar un intento fallido de login.
     *
     * @return array{blocked: bool, attempts: int, message: string}
     */
    public function registrarIntentoFallido(string $ip, ?string $email = null): array
    {
        $key = self::CACHE_PREFIX . $ip;
        $attempts = Cache::get($key, 0);
        $attempts++;

        Cache::put($key, $attempts, now()->addMinutes(self::WINDOW_MINUTES));

        if ($attempts >= self::MAX_ATTEMPTS) {
            $this->registrarAlertaBruteForce($ip, $email, $attempts);

            return [
                'blocked' => true,
                'attempts' => $attempts,
                'message' => "Bloqueado: {$attempts} intentos fallidos desde IP {$ip}",
            ];
        }

        return [
            'blocked' => false,
            'attempts' => $attempts,
            'message' => "Intento fallido #{$attempts} desde IP {$ip}",
        ];
    }

    /**
     * Verificar si una IP está bloqueada.
     */
    public function estaBloqueada(string $ip): bool
    {
        $key = self::CACHE_PREFIX . $ip;
        $attempts = Cache::get($key, 0);

        return $attempts >= self::MAX_ATTEMPTS;
    }

    /**
     * Limpiar intentos fallidos (llamar después de login exitoso).
     */
    public function limpiarIntentos(string $ip): void
    {
        Cache::forget(self::CACHE_PREFIX . $ip);
    }

    /**
     * Obtener número de intentos actuales para una IP.
     */
    public function obtenerIntentos(string $ip): int
    {
        return Cache::get(self::CACHE_PREFIX . $ip, 0);
    }

    /**
     * Registrar alerta de brute force en log de seguridad.
     */
    private function registrarAlertaBruteForce(string $ip, ?string $email, int $attempts): void
    {
        Log::channel('security')->warning('ALERTA BRUTE FORCE DETECTADA', [
            'ip' => $ip,
            'email_intentado' => $email ?? 'desconocido',
            'intentos_fallidos' => $attempts,
            'umbral' => self::MAX_ATTEMPTS,
            'ventana_minutos' => self::WINDOW_MINUTES,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
