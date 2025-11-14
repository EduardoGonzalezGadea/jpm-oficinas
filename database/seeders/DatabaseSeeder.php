<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            ModuloSeeder::class,
            RoleAndPermissionSeeder::class,
            UserSeeder::class,
            Tesoreria\MedioDePagoSeeder::class,
            Tesoreria\MultaSeeder::class,
            Tesoreria\TesDenominacionMonedaSeeder::class,
            Tesoreria\TesTipoMonedaSeeder::class,
        ]);
    }
}
