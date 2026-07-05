<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DistribucionERSeeder extends Seeder
{
    public function run()
    {
        // Este seeder requiere modelos que fueron refactorizados.
        // La lógica de distribución ER ahora se maneja a través del módulo SIIF.
        $this->command->info('DistribucionERSeeder: No requiere migración inicial.');
    }
}
