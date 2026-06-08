<?php

namespace App\Http\Controllers;

use App\Services\ValorUrService;
use App\Traits\WithHttpProxy;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;
use Smalot\PdfParser\Parser;

class UtilidadController extends Controller
{
    use WithHttpProxy;

    /**
     * Obtiene el valor de la Unidad Reajustable (UR) desde el sitio del BPS.
     */
    public function getValorUr(ValorUrService $valorUrService)
    {
        return response()->json($valorUrService->obtener());
    }

    /**
     * Obtiene la hora actual de Uruguay desde WorldTimeAPI.
     * Maneja proxy para entornos de producción usando el mismo patrón probado de ValorUrService.
     */
    public function getHoraUruguay()
    {
        $apiUrls = [
            'https://worldtimeapi.org/api/timezone/America/Montevideo',
            'https://timeapi.io/api/Time/current/zone?timeZone=America/Montevideo',
            'http://worldtimeapi.org/api/timezone/America/Montevideo'  // Intentar HTTP
        ];

        foreach ($apiUrls as $index => $apiUrl) {
            try {
                // Usar el mismo patrón probado de ValorUrService: retry + proxy array
                $response = $this->httpGetWithRetry($apiUrl, 15, 2, 10);

                if ($response->successful()) {
                    return $this->processTimeResponse($response, $index);
                }
            } catch (\Throwable $e) {
                Log::info('Hora Uruguay falló en ' . $apiUrl . ': ' . $e->getMessage());
                // Continuar con la siguiente URL
            }
        }

        // Fallback: usar hora del servidor
        Log::info('Usando hora local del servidor como fallback tras fallar todas las APIs');
        return response()->json([
            'success' => true,
            'datetime' => now()->toIso8601String(),
            'timezone' => 'America/Montevideo',
            'source' => 'server',
            'synced' => false
        ]);
    }

    /**
     * Procesa la respuesta de las APIs de tiempo.
     */
    private function processTimeResponse($response, $apiIndex)
    {
        $data = $response->json();

        if ($apiIndex === 0 || $apiIndex === 2) {
            // WorldTimeAPI response (HTTPS or HTTP)
            return response()->json([
                'success' => true,
                'datetime' => $data['datetime'] ?? now()->toIso8601String(),
                'timezone' => $data['timezone'] ?? 'America/Montevideo',
                'source' => 'worldtimeapi',
                'synced' => true
            ]);
        } else {
            // TimeAPI.io response
            $datetime = sprintf(
                '%04d-%02d-%02dT%02d:%02d:%02d',
                $data['year'],
                $data['month'],
                $data['day'],
                $data['hour'],
                $data['minute'],
                $data['seconds']
            );
            return response()->json([
                'success' => true,
                'datetime' => $datetime,
                'timezone' => 'America/Montevideo',
                'source' => 'timeapi',
                'synced' => true
            ]);
        }
    }

    /**
     * Actualiza los valores de las multas por carecer de SOA (Art. 184)
     * basándose en el PDF publicado por el BCU.
     */
    public function actualizarValoresSoa()
    {
        try {
            // 1. Obtener URL del PDF más reciente desde la web del BCU
            $bcuUrl = 'https://www.bcu.gub.uy/Servicios-Financieros-SSF/Paginas/ImpPromCostoDelSOA.aspx';

            // Intentar con retry y timeout aumentado
            $response = $this->httpGetWithRetry($bcuUrl, 60, 3);

            if (!$response->successful()) {
                throw new \Exception("No se pudo acceder a la web del BCU.");
            }

            $html = $response->body();
            $dom = new DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            $xpath = new DOMXPath($dom);

            // Buscar enlaces que apunten a PDF con el nombre esperado
            // Buscamos <a> que contenga 'SOA_Prima_Promedio' y termine en .pdf
            $pdfLinks = $xpath->query('//a[contains(@href, "SOA_Prima_Promedio") and contains(@href, ".pdf")]');

            if ($pdfLinks->length === 0) {
                // Intentar buscar en filas de tabla data-href
                $rows = $xpath->query('//tr[@data-href]');
                foreach ($rows as $row) {
                    $href = $row->getAttribute('data-href');
                    if (strpos($href, 'SOA_Prima_Promedio') !== false && strpos($href, '.pdf') !== false) {
                        $pdfUrl = $href;
                        break;
                    }
                }

                if (!isset($pdfUrl)) {
                    throw new \Exception("No se encontraron enlaces al PDF del SOA.");
                }
            } else {
                $pdfUrl = $pdfLinks->item(0)->getAttribute('href');
            }

            // Normalizar URL relativa
            if (strpos($pdfUrl, 'http') !== 0) {
                $pdfUrl = 'https://www.bcu.gub.uy' . $pdfUrl;
            }

            // 2. Descargar contenido del PDF
            $pdfResponse = $this->httpGetWithRetry($pdfUrl, 120, 2);
            $pdfContent = $pdfResponse->body();

            // 3. Parsear PDF
            $parser = new Parser();
            $pdf = $parser->parseContent($pdfContent);
            $text = $pdf->getText();

            // 4. Extraer valores y actualizar
            $categorias = [
                1 => 'Motos',
                2 => 'Autom.viles y Camionetas', // Automóviles
                3 => 'Camiones',
                4 => '.mnibus', // Ómnibus
                5 => 'Taxis',
                6 => 'Remises',
                7 => 'Veh.culos.*?alq', // Vehículos de alquiler (sin chofer) - Aparece como "Vehículos de alq sin chofer"
                8 => 'Ambulancias',
                9 => 'Coche Escuela',
                10 => 'Trailers',
                11 => 'Casa Rodante',
                12 => 'Tractores', // Tractores, semi remolques
                13 => 'Otros'
            ];

            $actualizados = 0;
            $detalles = [];

            foreach ($categorias as $apartado => $nombrePattern) {
                // Regex: Nombre Pattern + caracteres opcionales + Numero (X.XXX o XX.XXX)
                $pattern = '/' . $nombrePattern . '.*?(\d{1,3}(?:\.\d{3})*)/isu';

                if (preg_match($pattern, $text, $matches)) {
                    $valorTexto = str_replace('.', '', $matches[1]);
                    $valorNumerico = floatval($valorTexto);
                    $nuevoImporte = $valorNumerico * 2;

                    // Actualizar en BD
                    $afectados = \App\Models\Tesoreria\Multa::where('articulo', '184')
                        ->where('apartado', (string)$apartado)
                        ->update([
                            'importe_original' => $nuevoImporte,
                            'moneda' => 'UYU',
                            'updated_at' => now()
                        ]);

                    if ($afectados > 0) {
                        $actualizados += $afectados;
                        $detalles[] = "$nombrePattern (Ap. $apartado): $valorNumerico -> $nuevoImporte";
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Se actualizaron $actualizados registros.",
                'pdf_url' => $pdfUrl,
                'detalles' => $detalles
            ]);
        } catch (\Exception $e) {
            Log::error("Error actualizando SOA: " . $e->getMessage());

            // Mensaje más descriptivo para el usuario
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'timeout') !== false || strpos($errorMessage, 'Connection timeout') !== false) {
                $errorMessage = 'No se pudo conectar al sitio del BCU. Verifique la conexión a internet o contacte al administrador del sistema.';
            } elseif (strpos($errorMessage, 'No se pudo acceder') !== false) {
                $errorMessage = 'El sitio del BCU no está disponible temporalmente. Intente nuevamente más tarde.';
            }

            return response()->json([
                'success' => false,
                'error' => $errorMessage
            ], 500);
        }
    }

}
