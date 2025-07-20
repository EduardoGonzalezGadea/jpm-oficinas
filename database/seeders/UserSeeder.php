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
            'apellido' => 'Sistema',
            'email' => 'admin@oficinas.uy',
            'cedula' => '12345678',
            'telefono' => '+598 99 123 456',
            'password' => Hash::make('admin123'),
            'activo' => true,
            'modulo_id' => null, // Administrador no pertenece a un módulo específico
        ]);
        $admin->assignRole('administrador');

        // Supervisor Tesorería
        $supervisor_tes = User::create([
            'nombre' => 'María',
            'apellido' => 'González',
            'email' => 'supervisor.tesoreria@oficinas.uy',
            'cedula' => '23456789',
            'telefono' => '+598 99 234 567',
            'password' => Hash::make('supervisor123'),
            'activo' => true,
            'modulo_id' => $tesoreria->id,
        ]);
        $supervisor_tes->assignRole('supervisor_tesoreria');

        // Supervisor Contabilidad
        $supervisor_cont = User::create([
            'nombre' => 'Carlos',
            'apellido' => 'Rodríguez',
            'email' => 'supervisor.contabilidad@oficinas.uy',
            'cedula' => '34567890',
            'telefono' => '+598 99 345 678',
            'password' => Hash::make('supervisor123'),
            'activo' => true,
            'modulo_id' => $contabilidad->id,
        ]);
        $supervisor_cont->assignRole('supervisor_contabilidad');

        // Usuario Tesorería
        $usuario_tes = User::create([
            'nombre' => 'Ana',
            'apellido' => 'Martínez',
            'email' => 'ana.martinez@oficinas.uy',
            'cedula' => '45678901',
            'telefono' => '+598 99 456 789',
            'password' => Hash::make('usuario123'),
            'activo' => true,
            'modulo_id' => $tesoreria->id,
        ]);
        $usuario_tes->assignRole('usuario_tesoreria');

        // Usuario Contabilidad
        $usuario_cont = User::create([
            'nombre' => 'Pedro',
            'apellido' => 'López',
            'email' => 'pedro.lopez@oficinas.uy',
            'cedula' => '56789012',
            'telefono' => '+598 99 567 890',
            'password' => Hash::make('usuario123'),
            'activo' => true,
            'modulo_id' => $contabilidad->id,
        ]);
        $usuario_cont->assignRole('usuario_contabilidad');
    }
}
