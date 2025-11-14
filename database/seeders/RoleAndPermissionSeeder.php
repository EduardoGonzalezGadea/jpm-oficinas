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

        $guards = ['web', 'api'];

        // Crear permisos
        $permisos = [
            // Accesos por categoría
            'acceso_administrador',
            'acceso_gerente',
            'acceso_supervisor',

            // Módulo Usuarios
            'gestionar_usuarios',
            'crear_usuarios',
            'editar_usuarios',
            'eliminar_usuarios',
            'ver_usuarios',
            'cambiar_propia_contraseña',
            'editar_propio_perfil',

            // Módulo Tesorería
            'gestionar_tesoreria',
            'supervisar_tesoreria',
            'operador_tesoreria',
            'gestionar_pagos',
            'crear_pagos',
            'editar_pagos',
            'eliminar_pagos',
            'ver_pagos',
            'gestionar_conceptos_pago',

            // Módulo Sistema
            'administrar_sistema',

            // Módulo Roles
            'roles.index',
            'roles.create',
            'roles.edit',
            'roles.destroy',
            'roles.show',
            'roles.assign',

            // Módulo Permisos
            'permissions.index',
            'permissions.create',
            'permissions.edit',
            'permissions.destroy',
            'permissions.show',
        ];

        foreach ($guards as $guard) {
            foreach ($permisos as $permiso) {
                Permission::firstOrCreate(['name' => $permiso, 'guard_name' => $guard]);
            }

            // Crear roles
            $administrador = Role::firstOrCreate(['name' => 'administrador', 'guard_name' => $guard]);

            $gerente_tesoreria = Role::firstOrCreate(['name' => 'gerente_tesoreria', 'guard_name' => $guard]);
            $supervisor_tesoreria = Role::firstOrCreate(['name' => 'supervisor_tesoreria', 'guard_name' => $guard]);
            $usuario_tesoreria = Role::firstOrCreate(['name' => 'usuario_tesoreria', 'guard_name' => $guard]);

            // Asignar permisos a roles

            // Administrador: todos los permisos
            $administrador->givePermissionTo(Permission::all()->where('guard_name', $guard));

            // Gerentes: pueden gestionar usuarios de su módulo
            $gerente_tesoreria->givePermissionTo([
                'operador_tesoreria',
                'acceso_gerente',
                'gestionar_usuarios',
                'crear_usuarios',
                'editar_usuarios',
                'eliminar_usuarios',
                'ver_usuarios',
                'gestionar_tesoreria',
                'cambiar_propia_contraseña',
                'editar_propio_perfil',
                'gestionar_pagos',
                'crear_pagos',
                'editar_pagos',
                'eliminar_pagos',
                'ver_pagos',
                'gestionar_conceptos_pago',
                'administrar_sistema',
            ]);

            // Supervisores: pueden gestionar usuarios de su módulo
            $supervisor_tesoreria->givePermissionTo([
                'operador_tesoreria',
                'acceso_supervisor',
                'gestionar_usuarios',
                'crear_usuarios',
                'editar_usuarios',
                'ver_usuarios',
                'supervisar_tesoreria',
                'cambiar_propia_contraseña',
                'editar_propio_perfil',
                'administrar_sistema',
            ]);

            // Usuarios normales: solo su módulo
            $usuario_tesoreria->givePermissionTo([
                'operador_tesoreria',
                'cambiar_propia_contraseña',
                'editar_propio_perfil'
            ]);

        }
    }
}