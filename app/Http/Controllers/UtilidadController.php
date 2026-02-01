<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

class UtilidadController extends Controller
{
    /**
     * Obtiene el valor de la Unidad Reajustable (UR) desde el sitio del BPS.
     * Almacena el valor en caché durante 4 horas para evitar múltiples solicitudes.
     */
    public function getValorUr()
    {
        $resultado = Cache::remember('valor_ur_completo', 60 * 4, function () {
            // Intentar primero sin proxy
            Log::info('Intentando obtener valor UR sin proxy');
            $headers = [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ];
            try {
                $response = Http::withOptions([
                    'proxy' => false,
                    'verify' => false,
                    'timeout' => 30,
                    'headers' => $headers
                ])->get('https://www.bps.gub.uy/bps/valores.jsp?contentid=5478');
            } catch (\Exception $e) {
                Log::info('Conexión sin proxy falló: ' . $e->getMessage() . '. Intentando con proxy...');
                try {
                    $options = ['timeout' => 30, 'verify' => false, 'headers' => $headers];
                    if (env('HTTP_PROXY')) {
                        $options['proxy'] = env('HTTP_PROXY');
                    }
                    $response = Http::withOptions($options)->get('https://www.bps.gub.uy/bps/valores.jsp?contentid=5478');
                } catch (\Exception $e2) {
                    Log::error('Error al obtener valor UR con proxy: ' . $e2->getMessage());
                    return null;
                }
            }

            if ($response->successful()) {
                $html = $response->body();

                // Usar DOMDocument para parsear el HTML
                $dom = new DOMDocument();
                @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
                $xpath = new DOMXPath($dom);

                // Buscar la tabla de valores
                $tables = $dom->getElementsByTagName('table');
                if ($tables->length > 0) {
                    $table = $tables->item(0);

                    // Obtener encabezados (meses)
                    $headers = [];
                    $thList = $xpath->query('.//thead//th | .//thead//td', $table);
                    if ($thList->length === 0) {
                        // Si no hay thead, buscar en la primera fila del tbody
                        $thList = $xpath->query('.//tr[1]//td', $table);
                    }

                    foreach ($thList as $th) {
                        $headers[] = trim($th->nodeValue);
                    }

                    // headers logic kept same...

                    // Buscar la fila de "Unidad Reajustable"
                    // Nota: BPS a veces no usa tbody, buscar tr directamente
                    $rows = $xpath->query('.//tr', $table);
                    foreach ($rows as $row) {
                        $cells = $xpath->query('.//td', $row);
                        if ($cells->length > 0) {
                            $indicador = trim($cells->item(0)->nodeValue);
                            if (stripos($indicador, 'Unidad Reajustable') !== false) {
                                // Seleccionar directamente la última columna (suponiendo que es la del mes actual)
                                // y verificar si tiene valor. Si no, retroceder una.
                                for ($i = $cells->length - 1; $i >= 1; $i--) {
                                    // Limpiar caracteres invisibles (NBSP, etc)
                                    $valorUr = null;
                                    $mesUr = null;
                                    $cellValue = trim(str_replace(["\xC2\xA0", "&nbsp;"], ' ', $cells->item($i)->nodeValue));

                                    // Log para depuración
                                    Log::info("Revisando celda UR índice $i: '$cellValue'");

                                    if (!empty($cellValue) && preg_match('/[\d\.,]+/', $cellValue) && preg_match('/\d/', $cellValue)) {
                                        $valorUr = (strpos($cellValue, '$') === false) ? '$ ' . $cellValue : $cellValue;

                                        // Mapeo header
                                        if (isset($headers[$i])) {
                                            $mesUr = $headers[$i];
                                        } elseif ($i == $cells->length - 1 && count($headers) > 0) {
                                            // Fallback: si es la última celda, asumir último header
                                            $mesUr = end($headers);
                                        } else {
                                            $mesUr = null;
                                        }
                                        break;
                                    }
                                }

                                if ($valorUr) {
                                    Log::info("UR encontrada vía DOM: $valorUr - Mes: $mesUr");
                                    return ['valorUr' => $valorUr, 'mesUr' => $mesUr];
                                }
                            }
                        }
                    }
                }

                // Fallback a regex si falla DOM
                if (preg_match('/Unidad Reajustable[^$]*\$[^$]*\$[^$]*\$[^>]*(\d+\.\d+,\d+)/i', $html, $matches)) {
                    return ['valorUr' => '$ ' . $matches[1], 'mesUr' => null];
                }
            }
            return null;
        });

        if ($resultado) {
            return response()->json($resultado);
        }

        // Fallback final
        return response()->json([
            'valorUr' => '$ 1.839,08',
            'mesUr' => 'Noviembre'
        ]);
    }

    /**
     * Obtiene la hora actual de Uruguay desde WorldTimeAPI.
     * Maneja proxy para entornos de producción.
     */
    public function getHoraUruguay()
    {
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ];

        $apiUrls = [
            'https://worldtimeapi.org/api/timezone/America/Montevideo',
            'https://timeapi.io/api/Time/current/zone?timeZone=America/Montevideo',
            'http://worldtimeapi.org/api/timezone/America/Montevideo'  // Intentar HTTP
        ];

        foreach ($apiUrls as $index => $apiUrl) {
            // Intentar primero sin proxy
            try {
                $response = Http::withOptions([
                    'proxy' => false,
                    'verify' => false,
                    'timeout' => 5,
                    'headers' => $headers
                ])->get($apiUrl);

                if ($response->successful()) {
                    return $this->processTimeResponse($response, $index);
                }
            } catch (\Exception $e) {
                Log::info('Hora Uruguay sin proxy falló en ' . $apiUrl . ': ' . $e->getMessage());

                // Intentar con proxy configurado
                try {
                    $options = ['timeout' => 10, 'verify' => false, 'headers' => $headers];
                    if (env('HTTP_PROXY') || env('HTTPS_PROXY')) {
                        $options['proxy'] = [
                            'http' => env('HTTP_PROXY'),
                            'https' => env('HTTPS_PROXY', env('HTTP_PROXY')),
                        ];
                        if (env('NO_PROXY')) {
                            $options['proxy']['no'] = array_map('trim', explode(',', env('NO_PROXY')));
                        }
                    }

                    $response = Http::withOptions($options)->get($apiUrl);

                    if ($response->successful()) {
                        return $this->processTimeResponse($response, $index);
                    }
                } catch (\Exception $e2) {
                    Log::warning('Hora Uruguay con proxy falló en ' . $apiUrl . ': ' . $e2->getMessage());
                }
            }
        }

        // Fallback: usar hora del servidor
        Log::info('Usando hora local del servidor como fallback');
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

    /**
     * Realiza una petición HTTP GET con retry, backoff exponencial y manejo de proxy.
     * Similar a la lógica usada en getValorUr para BPS.
     */
    private function httpGetWithRetry($url, $timeout = 60, $maxRetries = 3)
    {
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ];

        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            try {
                // Intentar primero sin proxy
                Log::info("Intento {$attempt} para {$url} sin proxy");
                $response = Http::withOptions([
                    'proxy' => false,
                    'verify' => false,
                    'timeout' => $timeout,
                    'connect_timeout' => 10,
                    'headers' => $headers
                ])->get($url);

                if ($response->successful()) {
                    return $response;
                } else {
                    throw new \Exception("Respuesta no exitosa: " . $response->status());
                }
            } catch (\Exception $e) {
                Log::info("Conexión sin proxy falló para {$url}: " . $e->getMessage() . ". Intentando con proxy...");

                try {
                    // Intentar con proxy configurado
                    $options = [
                        'timeout' => $timeout,
                        'connect_timeout' => 10,
                        'verify' => false,
                        'headers' => $headers
                    ];

                    if (env('HTTP_PROXY')) {
                        $options['proxy'] = env('HTTP_PROXY');
                    }

                    $response = Http::withOptions($options)->get($url);

                    if ($response->successful()) {
                        return $response;
                    } else {
                        throw new \Exception("Respuesta no exitosa con proxy: " . $response->status());
                    }
                } catch (\Exception $e2) {
                    $lastException = $e2;
                    Log::warning("Conexión con proxy también falló para {$url}: " . $e2->getMessage());
                }
            }

            $attempt++;

            if ($attempt < $maxRetries) {
                $waitTime = pow(2, $attempt) * 1000000; // Microsegundos: 2^1 = 2s, 2^2 = 4s, etc.
                Log::info("Todos los intentos fallaron para {$url}. Reintentando en " . ($waitTime / 1000000) . " segundos...");
                usleep($waitTime);
            }
        }

        throw new \Exception("Fallaron todos los intentos para {$url}. Último error: " . $lastException->getMessage());
    }
}
