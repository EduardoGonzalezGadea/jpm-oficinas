<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Blade;
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
        // TODO Laravel 11: Verificar método correcto en Sanctum 4
        // Sanctum::withoutMigrations();
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

            // Livewire 4: setUpdateRoute() ha sido removido o cambiado.
            // La configuración de rutas ahora se maneja automáticamente.
            // TODO: Verificar si XAMPP requiere configuración especial para subdirectorios
            // if ($basePath !== '') {
            //     \Livewire\Livewire::setUpdateRoute(function ($handle) use ($basePath) {
            //         return \Illuminate\Support\Facades\Route::post(
            //             $basePath . '/livewire/update',
            //             $handle
            //         )->middleware('web');
            //     });
            // }
        }

        // Livewire v3 auto-descubre los componentes en app/Livewire (App\Livewire),
        // por lo que no es necesario registrarlos manualmente.

        // Blade directives para formateo Uruguay
        Blade::directive('money', function ($expression) {
            return "<?php echo \\App\\Helpers\\FormatHelper::moneyUyu({$expression}); ?>";
        });

        Blade::directive('urudate', function ($expression) {
            return "<?php echo \\App\\Helpers\\FormatHelper::dateUy({$expression}); ?>";
        });
    }
}