<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

use Illuminate\Pagination\Paginator;

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
        Paginator::useBootstrap();

        // Detectar automáticamente la URL base
        if (request()->getHost()) {
            $protocol = request()->isSecure() ? 'https://' : 'http://';
            $host = request()->getHost();
            $path = rtrim(dirname(request()->server('SCRIPT_NAME')), '/');

            config(['livewire.asset_url' => $protocol . $host . '/oficinas/public']);

            config(['app.url' => $protocol . $host . $path . '/oficinas/public']);
        }

        // Registrar componentes Livewire manualmente

        // Blade directives para formateo Uruguay
        Blade::directive('money', function ($expression) {
            return "<?php echo \\App\\Helpers\\FormatHelper::moneyUyu({$expression}); ?>";
        });

        Blade::directive('urudate', function ($expression) {
            return "<?php echo \\App\\Helpers\\FormatHelper::dateUy({$expression}); ?>";
        });
    }
}
