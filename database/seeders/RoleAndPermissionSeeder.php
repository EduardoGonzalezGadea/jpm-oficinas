<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Modules\ModuleRegistry;

class RoleAndPermissionSeeder extends Seeder
{
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ========================================
        // 1. Permisos globales del sistema
        // ========================================
        $globales = [
            'sistema.acceso.total',
            'sistema.acceso.administrador',
            'usuarios.gestionar',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.eliminar',
            'usuarios.ver',
            'usuarios.cambiar_contrasena',
            'usuarios.editar_perfil',
            'usuarios.asignar_roles',
            'roles.gestionar',
            'permisos.gestionar',
            'sistema.backups',
            'sistema.auditoria',
        ];

        foreach ($globales as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        // ========================================
        // 2. Permisos por módulo y recurso
        // ========================================
        foreach (ModuleRegistry::claves() as $claveModulo) {
            foreach (ModuleRegistry::recursos($claveModulo) as $recurso) {
                $acciones = ['ver', 'crear', 'editar'];
                // Supervisores pueden además aprobar/revertir
                $accionesSupervisor = array_merge($acciones, ['aprobar', 'revertir']);
                // Gerentes pueden todo + eliminar y configurar
                $accionesGerente = array_merge($accionesSupervisor, ['eliminar', 'configurar']);

                foreach ($accionesGerente as $accion) {
                    Permission::firstOrCreate([
                        'name' => ModuleRegistry::permiso($claveModulo, $recurso, $accion),
                        'guard_name' => 'web',
                    ]);
                }
            }
        }

        // ========================================
        // 3. Permisos de nivel de módulo
        // ========================================
        $moduloNivelPermisos = ['acceso', 'supervisar', 'gestionar'];
        foreach (ModuleRegistry::claves() as $claveModulo) {
            foreach ($moduloNivelPermisos as $permiso) {
                Permission::firstOrCreate([
                    'name' => "{$claveModulo}.{$permiso}",
                    'guard_name' => 'web',
                ]);
            }
        }

        // ========================================
        // 4. Rol Administrador (todo)
        // ========================================
        $admin = Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::all());

        // ========================================
        // 5. Roles por módulo con jerarquía
        // ========================================
        foreach (ModuleRegistry::claves() as $claveModulo) {
            $recursos = ModuleRegistry::recursos($claveModulo);

            // --- OPERADOR ---
            $rolOperador = ModuleRegistry::rolName($claveModulo, 'operador');
            $operador = Role::firstOrCreate(['name' => $rolOperador, 'guard_name' => 'web']);
            $permisosOperador = [
                "{$claveModulo}.acceso",
                'usuarios.cambiar_contrasena',
                'usuarios.editar_perfil',
            ];
            foreach ($recursos as $recurso) {
                $permisosOperador[] = ModuleRegistry::permiso($claveModulo, $recurso, 'ver');
                $permisosOperador[] = ModuleRegistry::permiso($claveModulo, $recurso, 'crear');
                $permisosOperador[] = ModuleRegistry::permiso($claveModulo, $recurso, 'editar');
            }
            $operador->givePermissionTo($permisosOperador);

            // --- SUPERVISOR (hereda operador +) ---
            $rolSupervisor = ModuleRegistry::rolName($claveModulo, 'supervisor');
            $supervisor = Role::firstOrCreate(['name' => $rolSupervisor, 'guard_name' => 'web']);
            $supervisor->givePermissionTo($operador->permissions);
            $extraSupervisor = [
                "{$claveModulo}.supervisar",
                'sistema.auditoria',
                'usuarios.ver',
                'usuarios.crear',
                'usuarios.editar',
            ];
            foreach ($recursos as $recurso) {
                $extraSupervisor[] = ModuleRegistry::permiso($claveModulo, $recurso, 'aprobar');
                $extraSupervisor[] = ModuleRegistry::permiso($claveModulo, $recurso, 'revertir');
            }
            $supervisor->givePermissionTo($extraSupervisor);

            // --- GERENTE (hereda supervisor +) ---
            $rolGerente = ModuleRegistry::rolName($claveModulo, 'gerente');
            $gerente = Role::firstOrCreate(['name' => $rolGerente, 'guard_name' => 'web']);
            $gerente->givePermissionTo($supervisor->permissions);
            $gerente->givePermissionTo([
                "{$claveModulo}.gestionar",
                'usuarios.gestionar',
                'usuarios.eliminar',
                'usuarios.asignar_roles',
            ]);
            foreach ($recursos as $recurso) {
                $gerente->givePermissionTo(ModuleRegistry::permiso($claveModulo, $recurso, 'eliminar'));
                $gerente->givePermissionTo(ModuleRegistry::permiso($claveModulo, $recurso, 'configurar'));
            }
        }
    }
}
