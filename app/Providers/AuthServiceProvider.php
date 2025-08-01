<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;

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

        // Definir Gates automáticamente para todos los permisos
        $this->registerPermissionGates();

        Gate::before(function ($user, $ability) {
            // Log para depuración (opcional)
            \Illuminate\Support\Facades\Log::info('Gate::before - User: ' . ($user ? $user->id : 'Guest') . ', Ability: ' . $ability);

            // Si el usuario tiene el permiso específico, permitir acceso
            if ($user && $user->hasPermissionTo($ability)) {
                return true;
            }

            // Si no tiene el permiso, continuar con la verificación normal de Gates
            return null;
        });
    }

    /**
     * Registra automáticamente Gates para todos los permisos de Spatie
     */
    protected function registerPermissionGates()
    {
        try {
            // Obtener todos los permisos existentes
            $permissions = Permission::all();

            foreach ($permissions as $permission) {
                Gate::define($permission->name, function ($user) use ($permission) {
                    return $user->hasPermissionTo($permission->name);
                });
            }
        } catch (\Exception $e) {
            // En caso de que las tablas aún no existan (durante migraciones)
            // No hacer nada
        }
    }
}
