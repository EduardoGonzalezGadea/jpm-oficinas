<?php

namespace Tests\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Trait WithAuthentication
 * 
 * Proporciona helpers para manejar autenticación en tests,
 * incluyendo usuarios, roles y permisos con Spatie.
 */
trait WithAuthentication
{
    /**
     * Crea un usuario de prueba
     */
    protected function createUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Usuario Test',
            'email' => 'test_' . uniqid() . '@test.com',
            'password' => Hash::make('password'),
            'activo' => true,
        ], $attributes));
    }

    /**
     * Crea un usuario con un rol específico
     */
    protected function createUserWithRole(string $roleName, array $userAttributes = []): User
    {
        $user = $this->createUser($userAttributes);
        
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $user->assignRole($role);
        
        return $user;
    }

    /**
     * Crea un usuario administrador
     */
    protected function createAdminUser(array $attributes = []): User
    {
        return $this->createUserWithRole('Administrador', $attributes);
    }

    /**
     * Crea un usuario con permisos específicos
     */
    protected function createUserWithPermissions(array $permissions, array $userAttributes = []): User
    {
        $user = $this->createUser($userAttributes);
        
        foreach ($permissions as $permission) {
            $perm = Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $user->givePermissionTo($perm);
        }
        
        return $user;
    }

    /**
     * Actúa como un usuario específico (autenticado)
     */
    protected function actingAsUser(?User $user = null): User
    {
        if (!$user) {
            $user = $this->createUser();
        }
        
        $this->actingAs($user);
        
        return $user;
    }

    /**
     * Actúa como un usuario con un rol específico
     */
    protected function actingAsUserWithRole(string $roleName): User
    {
        $user = $this->createUserWithRole($roleName);
        $this->actingAs($user);
        
        return $user;
    }

    /**
     * Actúa como administrador
     */
    protected function actingAsAdmin(): User
    {
        return $this->actingAsUserWithRole('Administrador');
    }

    /**
     * Crea un rol de prueba
     */
    protected function createRole(string $name, array $permissions = []): Role
    {
        $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        
        foreach ($permissions as $permission) {
            $perm = Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $role->givePermissionTo($perm);
        }
        
        return $role;
    }

    /**
     * Crea un permiso de prueba
     */
    protected function createPermission(string $name): Permission
    {
        return Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    /**
     * Crea los roles básicos del sistema
     */
    protected function createBasicRoles(): array
    {
        return [
            'admin' => $this->createRole('Administrador', [
                'ver_tesoreria',
                'editar_tesoreria',
                'eliminar_tesoreria',
                'ver_reportes',
            ]),
            'tesorero' => $this->createRole('Tesorero', [
                'ver_tesoreria',
                'editar_tesoreria',
                'ver_reportes',
            ]),
            'usuario' => $this->createRole('Usuario', [
                'ver_tesoreria',
            ]),
        ];
    }

    /**
     * Verifica que un usuario tiene un permiso específico
     */
    protected function assertUserHasPermission(User $user, string $permission): void
    {
        $this->assertTrue(
            $user->hasPermissionTo($permission),
            "El usuario no tiene el permiso '{$permission}'"
        );
    }

    /**
     * Verifica que un usuario NO tiene un permiso específico
     */
    protected function assertUserDoesNotHavePermission(User $user, string $permission): void
    {
        $this->assertFalse(
            $user->hasPermissionTo($permission),
            "El usuario tiene el permiso '{$permission}' cuando no debería"
        );
    }

    /**
     * Verifica que un usuario tiene un rol específico
     */
    protected function assertUserHasRole(User $user, string $role): void
    {
        $this->assertTrue(
            $user->hasRole($role),
            "El usuario no tiene el rol '{$role}'"
        );
    }
}
