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
            Tesoreria\Multas303Seeder::class,
            Tesoreria\TesDenominacionMonedaSeeder::class,
            Tesoreria\TesTipoMonedaSeeder::class,
            Tesoreria\SiifDistribucionTipoSeeder::class,
            Tesoreria\CajaConceptoSeeder::class,
            Tesoreria\SiifDistribucionDependenciaSeeder::class,
            Tesoreria\SiifDistribucionSeeder::class,
        ]);
    }
}
