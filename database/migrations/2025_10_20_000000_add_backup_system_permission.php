<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddBackupSystemPermission extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear el permiso para ambos guards
        Permission::firstOrCreate([
            'name' => 'administrar_sistema',
            'guard_name' => 'web'
        ]);

        Permission::firstOrCreate([
            'name' => 'administrar_sistema',
            'guard_name' => 'api'
        ]);

        // Asignar el permiso al rol de administrador para ambos guards
        $adminRoleWeb = Role::where('name', 'administrador')
                           ->where('guard_name', 'web')
                           ->first();

        $adminRoleApi = Role::where('name', 'administrador')
                           ->where('guard_name', 'api')
                           ->first();
        if ($adminRoleWeb) {
            $adminRoleWeb->givePermissionTo(['administrar_sistema']);
        }

        if ($adminRoleApi) {
            $adminRoleApi->givePermissionTo(['administrar_sistema']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar el permiso de ambos guards
        Permission::where('name', 'administrar_sistema')->delete();
    }
}
