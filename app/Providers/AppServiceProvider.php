<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Detectar automáticamente la URL base
        if (request()->getHost()) {
            $protocol = request()->isSecure() ? 'https://' : 'http://';
            $host = request()->getHost();
            $path = rtrim(dirname(request()->server('SCRIPT_NAME')), '/');

            if(strpos($host, 'localhost') !== false) {
                config(['livewire.asset_url' => $protocol . $host . '/oficinas/public']);
            }
            config(['app.url' => $protocol . $host . $path . '/oficinas/public']);
        }
    }
}
