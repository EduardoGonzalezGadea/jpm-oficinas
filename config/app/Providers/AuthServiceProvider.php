<?php

namespace App\Providers;

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

        // -----------------------------------------------------------------
        // GATE PERSONALIZADO PARA "CUALQUIERA" (OR) de los permisos
        Gate::define('acceso_preferencial_modular', function ($user) {
            // El método hasAnyPermission es parte del trait HasRoles de Spatie
            return $user->hasAnyPermission(['acceso_gerente', 'acceso_supervisor']);
        });
    }
}
