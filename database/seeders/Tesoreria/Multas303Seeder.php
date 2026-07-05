<?php

namespace Database\Seeders\Tesoreria;

use Illuminate\Database\Seeder;
use App\Models\Tesoreria\Multa303;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class Multas303Seeder extends Seeder
{
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

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Multa303::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $count = 0;
        foreach ($multas as $multa) {
            $rawValor = trim($multa['valor_ur'] ?? '0');
            $numericUr = (float) preg_replace('/[^0-9.]/', '', $rawValor);

            Multa303::create([
                'codigo' => trim($multa['codigo']),
                'descripcion' => trim($multa['descripcion']),
                'grupo' => trim($multa['grupo'] ?? ''),
                'detalle' => trim($multa['descripcion']),
                'monto_ur' => $numericUr,
                'valor_ur' => $rawValor,
            ]);
            $count++;
        }

        $this->command->info("Se han cargado/actualizado {$count} registros de multas del Decreto 303/2023.");
    }
}
