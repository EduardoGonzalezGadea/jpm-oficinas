<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Services\Tesoreria\CfeUniversalParserService;
use App\Models\Tesoreria\TesCfe;
use App\Models\Tesoreria\TesCfeItem;
use App\Models\Tesoreria\TesCfeMedioPago;

class ImportarCfesDePruebaCommand extends Command
{
    protected $signature = 'cfe:importar-prueba {directorio=C:\DESARROLLO\CFEs}';
    protected $description = 'Importar y parsear todos los PDFs de CFEs de un directorio de prueba';

    public function handle(CfeUniversalParserService $parser)
    {
        $directorio = $this->argument('directorio');
        
        if (!File::isDirectory($directorio)) {
            $this->error("El directorio no existe: {$directorio}");
            return 1;
        }

        $archivos = File::files($directorio);
        $pdfs = array_filter($archivos, fn($a) => strtolower($a->getExtension()) === 'pdf');

        $this->info("Encontrados " . count($pdfs) . " archivos PDF.");
        
        $procesados = 0;
        $errores = 0;

        foreach ($pdfs as $pdf) {
            try {
                $this->line("Procesando: {$pdf->getFilename()}...");
                
                $datos = $parser->parsePdf($pdf->getPathname());
                
                $cfe = TesCfe::create([
                    'emisor_nombre' => $datos['emisor_nombre'] ?? null,
                    'emisor_direccion' => $datos['emisor_direccion'] ?? null,
                    'emisor_localidad' => $datos['emisor_localidad'] ?? null,
                    'emisor_telefono' => $datos['emisor_telefono'] ?? null,
                    'emisor_correo' => $datos['emisor_correo'] ?? null,
                    'emisor_ruc' => $datos['emisor_ruc'] ?? null,
                    'documento_tipo' => $datos['documento_tipo'] ?? null,
                    'documento_serie' => $datos['documento_serie'] ?? null,
                    'documento_numero' => $datos['documento_numero'] ?? null,
                    'forma_pago' => $datos['forma_pago'] ?? null,
                    'vencimiento' => $datos['vencimiento'] ?? null,
                    'comprobante_tipo' => $datos['comprobante_tipo'] ?? null,
                    'receptor_documento_ruc' => $datos['receptor_documento_ruc'] ?? null,
                    'receptor_nombre_denominacion' => $datos['receptor_nombre_denominacion'] ?? null,
                    'receptor_domicilio_fiscal' => $datos['receptor_domicilio_fiscal'] ?? null,
                    'periodo' => $datos['periodo'] ?? null,
                    'nro_compra' => $datos['nro_compra'] ?? null,
                    'fecha' => $datos['fecha'] ?? null,
                    'moneda' => $datos['moneda'] ?? 'UYU',
                    'monto_no_facturable' => $datos['monto_no_facturable'] ?? 0,
                    'monto_total' => $datos['monto_total'] ?? 0,
                    'total_a_pagar' => $datos['total_a_pagar'] ?? 0,
                    'referencias' => $datos['referencias'] ?? null,
                    'adenda' => $datos['adenda'] ?? null,
                    'archivo_pdf_path' => $pdf->getPathname(),
                ]);

                if (!empty($datos['items'])) {
                    foreach ($datos['items'] as $item) {
                        TesCfeItem::create([
                            'tes_cfe_id' => $cfe->id,
                            'detalle' => $item['detalle'] ?? '',
                            'descripcion' => $item['descripcion'] ?? null,
                            'cantidad' => $item['cantidad'] ?? 1,
                            'precio' => $item['precio'] ?? 0,
                            'descuento' => $item['descuento'] ?? 0,
                            'recargo' => $item['recargo'] ?? 0,
                            'importe' => $item['importe'] ?? 0,
                        ]);
                    }
                }

                if (!empty($datos['medios_pago'])) {
                    foreach ($datos['medios_pago'] as $mp) {
                        TesCfeMedioPago::create([
                            'tes_cfe_id' => $cfe->id,
                            'medio_pago_tipo' => $mp['tipo'] ?? 'Desconocido',
                            'medio_pago_valor' => $mp['valor'] ?? 0,
                        ]);
                    }
                }

                $procesados++;
            } catch (\Exception $e) {
                $this->error("Error al procesar {$pdf->getFilename()}: " . $e->getMessage());
                $errores++;
            }
        }

        $this->info("Procesamiento finalizado.");
        $this->info("Éxitos: {$procesados}");
        $this->info("Errores: {$errores}");

        return 0;
    }
}
