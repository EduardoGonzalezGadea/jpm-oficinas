<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AuthEventListener
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * Create the event listener.
     *
     * @param  Request  $request
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Handle user login events.
     */
    public function onUserLogin(Login $event)
    {
        activity('autenticacion')
            ->event('login')
            ->performedOn($event->user)
            ->causedBy($event->user)
            ->withProperties([
                'ip' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
            ])
            ->log("El usuario inició sesión");
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
     */
    public function onLoginFailed(Failed $event)
    {
        activity('autenticacion')
            ->event('failed')
            ->withProperties([
                'credentials' => [
                    'email' => $event->credentials['email'] ?? 'N/A',
                ],
                'ip' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
            ])
            ->log("Intento de inicio de sesión fallido para: " . ($event->credentials['email'] ?? 'desconocido'));
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
