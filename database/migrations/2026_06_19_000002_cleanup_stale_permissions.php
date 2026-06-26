<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Eliminar permisos con guard_name = 'api' (solo usamos web)
        Permission::where('guard_name', 'api')->delete();
        Role::where('guard_name', 'api')->delete();
    }

    public function down()
    {
        // No hay vuelta atrás
    }
};
