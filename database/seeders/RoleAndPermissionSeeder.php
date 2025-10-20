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

            // Módulo Contabilidad
            'gestionar_contabilidad',
            'supervisar_contabilidad',
            'operador_contabilidad',

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

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'api']);
        }

        // Crear roles
        $administrador = Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'api']);

        $gerente_tesoreria = Role::firstOrCreate(['name' => 'gerente_tesoreria', 'guard_name' => 'api']);
        $supervisor_tesoreria = Role::firstOrCreate(['name' => 'supervisor_tesoreria', 'guard_name' => 'api']);
        $usuario_tesoreria = Role::firstOrCreate(['name' => 'usuario_tesoreria', 'guard_name' => 'api']);

        $gerente_contabilidad = Role::firstOrCreate(['name' => 'gerente_contabilidad', 'guard_name' => 'api']);
        $supervisor_contabilidad = Role::firstOrCreate(['name' => 'supervisor_contabilidad', 'guard_name' => 'api']);
        $usuario_contabilidad = Role::firstOrCreate(['name' => 'usuario_contabilidad', 'guard_name' => 'api']);

        // Asignar permisos a roles

        // Administrador: todos los permisos
        $administrador->givePermissionTo(Permission::all()->where('guard_name', 'api'));

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
        ]);

        $gerente_contabilidad->givePermissionTo([
            'operador_contabilidad',
            'acceso_gerente',
            'gestionar_usuarios',
            'crear_usuarios',
            'editar_usuarios',
            'eliminar_usuarios',
            'ver_usuarios',
            'gestionar_contabilidad',
            'cambiar_propia_contraseña',
            'editar_propio_perfil',
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
        ]);

        $supervisor_contabilidad->givePermissionTo([
            'operador_contabilidad',
            'acceso_supervisor',
            'gestionar_usuarios',
            'crear_usuarios',
            'editar_usuarios',
            'ver_usuarios',
            'supervisar_contabilidad',
            'cambiar_propia_contraseña',
            'editar_propio_perfil',
        ]);

        // Usuarios normales: solo su módulo
        $usuario_tesoreria->givePermissionTo([
            'operador_tesoreria',
            'cambiar_propia_contraseña',
            'editar_propio_perfil'
        ]);

        $usuario_contabilidad->givePermissionTo([
            'operador_contabilidad',
            'cambiar_propia_contraseña',
            'editar_propio_perfil'
        ]);
    }
}
