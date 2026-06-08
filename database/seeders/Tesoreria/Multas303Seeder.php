<?php

namespace Database\Seeders\Tesoreria;

use Illuminate\Database\Seeder;
use App\Models\Tesoreria\Multa303;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class Multas303Seeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $jsonPath = database_path('data/multas_303.json');

        if (!File::exists($jsonPath)) {
            $this->command->error("El archivo JSON de multas 303 no existe en: {$jsonPath}");
            return;
        }

        $json = File::get($jsonPath);
        $multas = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error("Error al decodificar el archivo JSON: " . json_last_error_msg());
            return;
        }

        // Limpiar tabla antes de sembrar para evitar registros huérfanos con OCR corrupto
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Multa303::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $count = 0;
        foreach ($multas as $multa) {
            Multa303::create([
                'grupo' => trim($multa['grupo']),
                'codigo' => trim($multa['codigo']),
                'descripcion' => trim($multa['descripcion']),
                'valor_ur' => trim($multa['valor_ur']),
            ]);
            $count++;
        }

        $this->command->info("Se han cargado/actualizado {$count} registros de multas del Decreto 303/2023 (extraídos correctamente desde el documento DOCX).");
    }
}
