<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Modulo;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Obtener módulos
        $tesoreria = Modulo::where('nombre', 'Tesorería')->first();

        // Usuario Administrador
        $admin = User::create([
            'nombre' => 'Administrador',
            'apellido' => 'del Sistema',
            'email' => 'jefatura.montevideo@minterior.gub.uy',
            'cedula' => null,
            'telefono' => '2030 2000',
            'password' => Hash::make('inciSo-04'),
            'activo' => true,
            'modulo_id' => null,
        ]);
        $adminRole = Role::findByName('administrador', 'api');
        $admin->assignRole($adminRole);

        // Gerente Tesorería
        $gerente_tes = User::create([
            'nombre' => 'Alicia',
            'apellido' => 'Roldán',
            'email' => 'alicia.roldan@minterior.gub.uy',
            'cedula' => null,
            'telefono' => '099657524',
            'password' => Hash::make('123456'),
            'activo' => true,
            'modulo_id' => $tesoreria->id,
        ]);
        $gerente_tesoreriaRole = Role::findByName('gerente_tesoreria', 'api');
        $gerente_tes->assignRole($gerente_tesoreriaRole);

        // Supervisor Tesorería
        $supervisor_tes = User::create([
            'nombre' => 'Claudia',
            'apellido' => 'Vázquez',
            'email' => 'claudia.vazquez@minterior.gub.uy',
            'cedula' => null,
            'telefono' => '094267508',
            'password' => Hash::make('123456'),
            'activo' => true,
            'modulo_id' => $tesoreria->id,
        ]);
        $supervisor_tesoreriaRole = Role::findByName('supervisor_tesoreria', 'api');
        $supervisor_tes->assignRole($supervisor_tesoreriaRole);

        // Usuario Tesorería
        $usuario_tes = User::create([
            'nombre' => 'Eduardo',
            'apellido' => 'González',
            'email' => 'eduardo.gonzalez.gadea@minterior.gub.uy',
            'cedula' => '38898988',
            'telefono' => '093880521',
            'password' => Hash::make('123456'),
            'activo' => true,
            'modulo_id' => $tesoreria->id,
        ]);
        $usuario_tesoreriaRole = Role::findByName('usuario_tesoreria', 'api');
        $usuario_tes->assignRole($usuario_tesoreriaRole);
    }
}
