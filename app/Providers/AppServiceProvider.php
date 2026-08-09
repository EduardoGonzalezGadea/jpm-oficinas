<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Illuminate\Support\Facades\URL;

use Illuminate\Pagination\Paginator;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // La tabla personal_access_tokens se crea con las migraciones consolidadas.
        Sanctum::ignoreMigrations();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // URL::forceScheme('https');

        Paginator::useBootstrap();

        // Detectar automáticamente la URL base
        if (request()->getHost()) {
            $protocol = request()->isSecure() ? 'https://' : 'http://';
            $host = request()->getHost();
            $port = request()->getPort();
            $portSuffix = ($port !== 80 && $port !== 443) ? ":$port" : '';

            // Obtener el path base desde SCRIPT_NAME
            // Bajo Apache (XAMPP):  /oficinas/public/index.php  → /oficinas/public
            // Bajo php artisan serve: /index.php                → '' (vacío, la app está en la raíz)
            $scriptName = request()->server('SCRIPT_NAME');
            $basePath = rtrim(dirname($scriptName), '/');
            $basePath = ($basePath === '/' || $basePath === '\\') ? '' : $basePath;

            $baseUrl = $protocol . $host . $portSuffix . $basePath;

            config(['app.url' => $baseUrl]);

            // Livewire v3 genera data-update-uri usando app('url')->toRoute($route, [], false),
            // que devuelve el path relativo del route sin el subdirectorio.
            // En XAMPP, el browser resuelve /livewire/update desde la raíz de Apache (404).
            // La solución: registrar el route con el prefijo del subdirectorio para que
            // toRoute() devuelva /oficinas/public/livewire/update.
            if ($basePath !== '') {
                \Livewire\Livewire::setUpdateRoute(function ($handle) use ($basePath) {
                    return \Illuminate\Support\Facades\Route::post(
                        $basePath . '/livewire/update',
                        $handle
                    )->middleware('web');
                });
            }
        }

        // Registrar componentes Livewire manualmente.
        // Nota: estos componentes viven en app/Livewire (App\Livewire\...), fuera del
        // class_namespace configurado (App\Http\Livewire). Sin registro explicito, el
        // render inicial funciona pero los requests posteriores (wire:model, wire:click)
        // fallan con ComponentNotFoundException, porque auto-discovery no los encuentra.
        Livewire::component('tesoreria.caja-diaria.index', \App\Livewire\Tesoreria\CajaDiaria\Index::class);
        Livewire::component('tesoreria.caja-diaria.apertura-cierre', \App\Livewire\Tesoreria\CajaDiaria\AperturaCierre::class);
        Livewire::component('tesoreria.caja-diaria.arqueo', \App\Livewire\Tesoreria\CajaDiaria\Arqueo::class);
        Livewire::component('tesoreria.caja-diaria.movimientos', \App\Livewire\Tesoreria\CajaDiaria\Movimientos::class);

        // Blade directives para formateo Uruguay
        Blade::directive('money', function ($expression) {
            return "<?php echo \\App\\Helpers\\FormatHelper::moneyUyu({$expression}); ?>";
        });

        Blade::directive('urudate', function ($expression) {
            return "<?php echo \\App\\Helpers\\FormatHelper::dateUy({$expression}); ?>";
        });
    }
}