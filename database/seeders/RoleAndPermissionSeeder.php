<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos
        $permisos = [
            'gestionar_usuarios',
            'crear_usuarios',
            'editar_usuarios',
            'eliminar_usuarios',
            'ver_usuarios',
            'gestionar_tesoreria',
            'gestionar_contabilidad',
            'cambiar_propia_contraseña',
            'editar_propio_perfil',
        ];

        foreach ($permisos as $permiso) {
            Permission::create(['name' => $permiso]);
        }

        // Crear roles
        $administrador = Role::create(['name' => 'administrador']);
        $supervisor_tesoreria = Role::create(['name' => 'supervisor_tesoreria']);
        $supervisor_contabilidad = Role::create(['name' => 'supervisor_contabilidad']);
        $usuario_tesoreria = Role::create(['name' => 'usuario_tesoreria']);
        $usuario_contabilidad = Role::create(['name' => 'usuario_contabilidad']);

        // Asignar permisos a roles

        // Administrador: todos los permisos
        $administrador->givePermissionTo(Permission::all());

        // Supervisores: pueden gestionar usuarios de su módulo
        $supervisor_tesoreria->givePermissionTo([
            'crear_usuarios',
            'editar_usuarios',
            'ver_usuarios',
            'gestionar_tesoreria',
            'cambiar_propia_contraseña',
            'editar_propio_perfil'
        ]);

        $supervisor_contabilidad->givePermissionTo([
            'crear_usuarios',
            'editar_usuarios',
            'ver_usuarios',
            'gestionar_contabilidad',
            'cambiar_propia_contraseña',
            'editar_propio_perfil'
        ]);

        // Usuarios normales: solo su módulo
        $usuario_tesoreria->givePermissionTo([
            'gestionar_tesoreria',
            'cambiar_propia_contraseña',
            'editar_propio_perfil'
        ]);

        $usuario_contabilidad->givePermissionTo([
            'gestionar_contabilidad',
            'cambiar_propia_contraseña',
            'editar_propio_perfil'
        ]);
    }
}
