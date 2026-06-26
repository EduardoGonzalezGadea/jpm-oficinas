<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddBackupSystemPermission extends Migration
{
    public function up(): void
    {
        // Migración reemplazada por RoleAndPermissionSeeder v2.
        // El permiso 'sistema.backups' se crea en el seeder junto con toda la jerarquía.
        // Se mantiene solo por compatibilidad con migraciones existentes.
        Permission::firstOrCreate([
            'name' => 'sistema.backups',
            'guard_name' => 'web',
        ]);

        $adminRole = Role::where('name', 'administrador')
                         ->where('guard_name', 'web')
                         ->first();

        if ($adminRole) {
            $adminRole->givePermissionTo('sistema.backups');
        }
    }

    public function down(): void
    {
        Permission::where('name', 'sistema.backups')->delete();
    }
}
