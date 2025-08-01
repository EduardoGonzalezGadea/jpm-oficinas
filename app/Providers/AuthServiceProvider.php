<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Depuración del Gate de Laravel
        Gate::before(function ($user, $ability) {
            // Para depuración, puedes habilitar esta línea para ver las comprobaciones de Gate
            \Illuminate\Support\Facades\Log::info('Gate::before - User: ' . ($user ? $user->id : 'Guest') . ', Ability: ' . $ability);
            // if ($user) {
            //     return true; // Si hay un usuario autenticado, siempre permitir el acceso para depuración
            // }
        });

        // -----------------------------------------------------------------
        // GATE PERSONALIZADO PARA "CUALQUIERA" (OR) de los permisos
        Gate::define('acceso_preferencial_modular', function ($user) {
            // El método hasAnyPermission es parte del trait HasRoles de Spatie
            return $user->hasAnyPermission(['acceso_gerente', 'acceso_supervisor']);
        });
    }
}
