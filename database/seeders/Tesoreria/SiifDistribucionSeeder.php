<?php

namespace Database\Seeders\Tesoreria;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Tesoreria\SiifDistribucion;

class SiifDistribucionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        try {
            $externalRecords = DB::connection('mysql')
                ->table('jpm-tesoreria-2026.siif_distribucions')
                ->get();
        } catch (\Exception $e) {
            try {
                $pdo = DB::connection()->getPdo();
                $stmt = $pdo->query("SELECT * FROM `jpm-tesoreria-2026`.`siif_distribucions`");
                $externalRecords = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\Exception $e2) {
                $this->command->warn('Base de datos externa no disponible. Se saltan datos SIIF.');
                return;
            }
        }

        foreach ($externalRecords as $record) {
            $data = (array) $record;
            
            // Aseguramos de mapear y registrar en siif_distribucions usando updateOrCreate o insert
            SiifDistribucion::updateOrCreate(
                ['id' => $data['id']],
                [
                    'tipo_id'          => $data['tipo_id'],
                    'dependencia_id'   => $data['dependencia_id'],
                    'rubro'            => $data['rubro'],
                    'sub_rubro'        => $data['sub_rubro'],
                    'recurso'          => $data['recurso'],
                    'concepto'         => $data['concepto'],
                    'codigo_sir'       => $data['codigo_sir'],
                    'porcentaje'       => $data['porcentaje'],
                    'financiacion'     => $data['financiacion'],
                    'inciso'           => $data['inciso'],
                    'unidad_ejecutora' => $data['unidad_ejecutora'],
                    'created_at'       => $data['created_at'],
                    'updated_at'       => $data['updated_at'],
                    'deleted_at'       => $data['deleted_at'],
                    'created_by'       => $data['created_by'],
                    'updated_by'       => $data['updated_by'],
                    'deleted_by'       => $data['deleted_by'],
                ]
            );
        }
    }
}
