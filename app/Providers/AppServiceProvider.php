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

            // IMPORTANTE: php artisan serve siempre sirve desde raíz,
            // incluso si el proyecto está en un subdirectorio del filesystem.
            // Solo configurar basePath si NO estamos en el puerto 8000 (artisan serve default)
            // o si explícitamente detectamos que estamos bajo Apache/servidor web real
            $isArtisanServe = ($port === 8000 && $host === '127.0.0.1');
            
            if ($isArtisanServe) {
                $basePath = ''; // Forzar vacío para artisan serve
            }

            $baseUrl = $protocol . $host . $portSuffix . $basePath;

            config(['app.url' => $baseUrl]);
            
            // Forzar la URL base ANTES de que otros servicios la usen
            URL::forceRootUrl($baseUrl);

            // Livewire 4: Solo configurar app_url, NO asset_url
            // asset_url causa problemas cuando se usa con subdirectorios en XAMPP
            // porque Livewire lo interpreta de manera incorrecta
            if ($basePath !== '') {
                config(['livewire.app_url' => $baseUrl]);
            }
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