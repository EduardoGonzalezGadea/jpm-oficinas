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
        $tesoreria = Modulo::where('clave', 'tesoreria')->first();
        $asesoriaContable = Modulo::where('clave', 'asesoria_contable')->first();

        $admin = User::create([
            'nombre' => 'Administrador',
            'apellido' => 'del Sistema',
            'email' => 'tesoreria.montevideo@minterior.gub.uy',
            'cedula' => null,
            'telefono' => '2030 2000',
            'password' => Hash::make('inciSo-04'),
            'activo' => true,
            'modulo_id' => null,
        ]);
        $adminRole = Role::findByName('administrador', 'web');
        $admin->assignRole($adminRole);

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
        $gerente_tes->assignRole(Role::findByName('tesoreria_gerente', 'web'));

        $gerente_ac = User::create([
            'nombre' => 'María Fiorella',
            'apellido' => 'Quiñones',
            'email' => 'maria.quinones@minterior.gub.uy',
            'cedula' => null,
            'telefono' => null,
            'password' => Hash::make('123456'),
            'activo' => true,
            'modulo_id' => $asesoriaContable->id,
        ]);
        $gerente_ac->assignRole(Role::findByName('asesoria_contable_gerente', 'web'));

        $supervisor_tes = User::create([
            'nombre' => 'Mónica',
            'apellido' => 'Pintos',
            'email' => 'monica.pintos@minterior.gub.uy',
            'cedula' => null,
            'telefono' => '099113188',
            'password' => Hash::make('123456'),
            'activo' => true,
            'modulo_id' => $tesoreria->id,
        ]);
        $supervisor_tes->assignRole(Role::findByName('tesoreria_supervisor', 'web'));

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
        $usuario_tes->assignRole(Role::findByName('tesoreria_operador', 'web'));

        $usuario_ac = User::create([
            'nombre' => 'Carlos',
            'apellido' => 'Camejo',
            'email' => 'carlos.camejo@minterior.gub.uy',
            'cedula' => null,
            'telefono' => null,
            'password' => Hash::make('123456'),
            'activo' => true,
            'modulo_id' => $asesoriaContable->id,
        ]);
        $usuario_ac->assignRole(Role::findByName('asesoria_contable_operador', 'web'));
    }
}
