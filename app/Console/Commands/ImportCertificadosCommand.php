<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tesoreria\CertificadoResidencia;

class ImportCertificadosCommand extends Command
{
    protected $signature = 'import:certificados';
    protected $description = 'Importar certificados de residencia desde un archivo CSV';

    public function handle()
    {
        $csvPath = base_path('docs/CERTIFICADOS DE RESIDENCIA 2025 PLANILLA CONTROL .csv');

        if (!file_exists($csvPath)) {
            $this->error('El archivo CSV no se encuentra en la ruta: ' . $csvPath);
            return 1;
        }

        $file = fopen($csvPath, 'r');

        // Skip header lines
        for ($i = 0; $i < 5; $i++) {
            fgetcsv($file);
        }

        $this->info('Iniciando la importación de certificados...');

        while (($row = fgetcsv($file)) !== false) {
            if (empty(array_filter($row))) continue; // Skip empty rows

            // Data extraction and cleaning
            try {
                $fecha_recibido = !empty($row[0]) ? \DateTime::createFromFormat('d/m/Y', $row[0])->format('Y-m-d') : null;
                
                $titular_full_name = trim(preg_replace('/[^\w\s-]/u', '', $row[1]));
                $titular_parts = explode(' ', $titular_full_name, 2);
                $titular_nombre = $titular_parts[0];
                $titular_apellido = $titular_parts[1] ?? '';

                $titular_nro_documento = trim($row[2]);
                $titular_tipo_documento = 'Cédula';
                if (strtoupper($titular_nro_documento) === 'NO TIENE') {
                    $titular_tipo_documento = 'Otro';
                }

                $fecha_entregado = null;
                if (!empty($row[4]) && strtoupper(trim($row[4])) !== 'SE DEV') {
                    $fecha_entregado = \DateTime::createFromFormat('d/m/y', $row[4])->format('Y-m-d');
                }

                $retira_full_name = !empty($row[5]) ? trim(preg_replace('/[^\w\s-]/u', '', $row[5])) : null;
                $retira_nombre = null;
                $retira_apellido = null;
                if ($retira_full_name && strtoupper($retira_full_name) !== 'SE DEV') {
                    $retira_parts = explode(' ', $retira_full_name, 2);
                    $retira_nombre = $retira_parts[0];
                    $retira_apellido = $retira_parts[1] ?? '';
                }

                $fecha_devuelto = null;
                if (!empty($row[6])) {
                    $fecha_devuelto = \DateTime::createFromFormat('d/m/y', $row[6])->format('Y-m-d');
                } elseif (strtoupper(trim($row[4])) === 'SE DEV') {
                    $fecha_devuelto = \DateTime::createFromFormat('d/m/Y', $row[0])->format('Y-m-d');
                }

                // Determine state
                $estado = 'Recibido';
                if ($fecha_entregado) {
                    $estado = 'Entregado';
                } elseif ($fecha_devuelto) {
                    $estado = 'Devuelto';
                }

                // Default user IDs
                $receptor_id = 1;
                $entregador_id = $fecha_entregado ? 1 : null;
                $devolucion_user_id = $fecha_devuelto ? 1 : null;

                CertificadoResidencia::create([
                    'fecha_recibido' => $fecha_recibido,
                    'receptor_id' => $receptor_id,
                    'titular_nombre' => $titular_nombre,
                    'titular_apellido' => $titular_apellido,
                    'titular_tipo_documento' => $titular_tipo_documento,
                    'titular_nro_documento' => $titular_nro_documento,
                    'fecha_entregado' => $fecha_entregado,
                    'entregador_id' => $entregador_id,
                    'retira_nombre' => $retira_nombre,
                    'retira_apellido' => $retira_apellido,
                    'retira_tipo_documento' => $retira_nombre ? 'Cédula' : null,
                    'retira_nro_documento' => $retira_nombre ? '' : null,
                    'retira_telefono' => null,
                    'fecha_devuelto' => $fecha_devuelto,
                    'devolucion_user_id' => $devolucion_user_id,
                    'estado' => $estado,
                ]);

            } catch (\Exception $e) {
                $this->error("Error procesando la fila: " . implode(', ', $row) . " - Error: " . $e->getMessage());
            }
        }

        fclose($file);

        $this->info('¡Importación de certificados completada!');
        return 0;
    }
}