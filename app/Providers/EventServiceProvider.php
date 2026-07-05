<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\Tesoreria\CfeProcesado;
use App\Events\Tesoreria\CfeConfirmado;
use App\Events\Tesoreria\CfeRechazado;
use App\Events\Tesoreria\CfeCreado;
use App\Events\Tesoreria\CfeActualizado;
use App\Events\Tesoreria\CfeEliminado;
use App\Listeners\Tesoreria\CfeProcesadoListener;
use App\Listeners\Tesoreria\CfeConfirmadoListener;
use App\Listeners\Tesoreria\CfeRechazadoListener;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        CfeProcesado::class => [
            CfeProcesadoListener::class,
        ],
        CfeConfirmado::class => [
            CfeConfirmadoListener::class,
        ],
        CfeRechazado::class => [
            CfeRechazadoListener::class,
        ],
    ];

    protected $subscribe = [
        \App\Listeners\AuthEventListener::class,
        \App\Listeners\Tesoreria\LogCfeActivity::class,
    ];

    public function boot()
    {
        //
    }
}
