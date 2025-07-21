<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Modulo;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Obtener módulos
        $tesoreria = Modulo::where('nombre', 'Tesorería')->first();
        $contabilidad = Modulo::where('nombre', 'Contabilidad')->first();

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
        $admin->assignRole('administrador');

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
        $gerente_tes->assignRole('gerente_tesoreria');

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
        $supervisor_tes->assignRole('supervisor_tesoreria');

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
        $usuario_tes->assignRole('usuario_tesoreria');

        // Gerente Contabilidad
        $gerente_cont = User::create([
            'nombre' => 'Graciela',
            'apellido' => 'Maidana',
            'email' => 'graciela.maidana@minterior.gub.uy',
            'cedula' => null,
            'telefono' => '2030 2099',
            'password' => Hash::make('123456'),
            'activo' => true,
            'modulo_id' => $contabilidad->id,
        ]);
        $gerente_cont->assignRole('gerente_contabilidad');

        // Supervisor Contabilidad
        $supervisor_cont = User::create([
            'nombre' => 'Cristina',
            'apellido' => 'Fernández',
            'email' => 'cristina.fernandez@minterior.gub.uy',
            'cedula' => null,
            'telefono' => '2030 2108',
            'password' => Hash::make('123456'),
            'activo' => true,
            'modulo_id' => $contabilidad->id,
        ]);
        $supervisor_cont->assignRole('supervisor_contabilidad');

        // Usuario Contabilidad
        $usuario_cont = User::create([
            'nombre' => 'Mónica',
            'apellido' => 'López',
            'email' => 'monica.lopez@minterior.gub.uy',
            'cedula' => null,
            'telefono' => '2030 2108',
            'password' => Hash::make('123456'),
            'activo' => true,
            'modulo_id' => $contabilidad->id,
        ]);
        $usuario_cont->assignRole('usuario_contabilidad');
    }
}
