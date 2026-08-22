<?php

namespace App\Listeners;

use App\Services\Security\BruteForceDetector;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

class AuthEventListener
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var BruteForceDetector
     */
    protected $bruteForceDetector;

    /**
     * Create the event listener.
     *
     * @param  Request  $request
     * @param  BruteForceDetector  $bruteForceDetector
     * @return void
     */
    public function __construct(Request $request, BruteForceDetector $bruteForceDetector)
    {
        $this->request = $request;
        $this->bruteForceDetector = $bruteForceDetector;
    }

    /**
     * Handle user login events.
     */
    public function onUserLogin(Login $event)
    {
        $ip = $this->request->ip();

        // Limpiar intentos fallidos al login exitoso
        $this->bruteForceDetector->limpiarIntentos($ip);

        activity('autenticacion')
            ->event('login')
            ->performedOn($event->user)
            ->causedBy($event->user)
            ->withProperties([
                'ip' => $ip,
                'user_agent' => $this->request->userAgent(),
            ])
            ->log("El usuario inició sesión");

        Log::channel('auth')->info('Login exitoso', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'ip' => $ip,
        ]);
    }

    /**
     * Handle user logout events.
     */
    public function onUserLogout(Logout $event)
    {
        if ($event->user) {
            activity('autenticacion')
                ->event('logout')
                ->performedOn($event->user)
                ->causedBy($event->user)
                ->withProperties([
                    'ip' => $this->request->ip(),
                ])
                ->log("El usuario cerró sesión");
        }
    }

    /**
     * Handle failed login attempts.
     * Registra en activity log, canal auth, y verifica brute force.
     */
    public function onLoginFailed(Failed $event)
    {
        $ip = $this->request->ip();
        $email = $event->credentials['email'] ?? 'desconocido';

        // Registrar en activity log (Spatie)
        activity('autenticacion')
            ->event('failed')
            ->withProperties([
                'credentials' => [
                    'email' => $email,
                ],
                'ip' => $ip,
                'user_agent' => $this->request->userAgent(),
            ])
            ->log("Intento de inicio de sesión fallido para: " . $email);

        // Registrar en canal auth
        Log::channel('auth')->warning('Login fallido', [
            'email' => $email,
            'ip' => $ip,
            'user_agent' => $this->request->userAgent(),
        ]);

        // Verificar brute force
        $resultado = $this->bruteForceDetector->registrarIntentoFallido($ip, $email);

        if ($resultado['blocked']) {
            Log::channel('security')->alert('LOGIN BLOQUEADO POR BRUTE FORCE', [
                'ip' => $ip,
                'email' => $email,
                'intentos' => $resultado['attempts'],
            ]);
        }
    }

    /**
     * Handle password reset events.
     */
    public function onPasswordReset(PasswordReset $event)
    {
        activity('autenticacion')
            ->event('password_reset')
            ->performedOn($event->user)
            ->causedBy($event->user)
            ->withProperties([
                'ip' => $this->request->ip(),
            ])
            ->log("El usuario restableció su contraseña");

        Log::channel('auth')->info('Password reset', [
            'user_id' => $event->user->id,
            'ip' => $this->request->ip(),
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     * @return void
     */
    public function subscribe($events)
    {
        $events->listen(
            Login::class,
            [AuthEventListener::class, 'onUserLogin']
        );

        $events->listen(
            Logout::class,
            [AuthEventListener::class, 'onUserLogout']
        );

        $events->listen(
            Failed::class,
            [AuthEventListener::class, 'onLoginFailed']
        );

        $events->listen(
            PasswordReset::class,
            [AuthEventListener::class, 'onPasswordReset']
        );
    }
}
