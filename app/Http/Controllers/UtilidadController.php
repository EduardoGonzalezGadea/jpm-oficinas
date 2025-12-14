<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Str;

class UtilidadController extends Controller
{
    /**
     * Obtiene el valor de la Unidad Reajustable (UR) desde el sitio del BPS.
     * Almacena el valor en caché durante 4 horas para evitar múltiples solicitudes.
     */
    public function getValorUr()
    {
        $valor = Cache::remember('valor_ur', 60 * 4, function () {
            // Intentar primero sin proxy, ya que funciona en desarrollo y debería en producción
            Log::info('Intentando obtener valor UR sin proxy');
            $headers = [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ];
            try {
                $response = Http::withOptions([
                    'proxy' => false,
                    'verify' => false,  // Deshabilitar verificación SSL
                    'timeout' => 30,
                    'headers' => $headers
                ])->get('https://www.bps.gub.uy/bps/valores.jsp?contentid=5478');
                Log::info('Intento sin proxy - Respuesta HTTP: ' . $response->status() . ' - Body length: ' . strlen($response->body()));
            } catch (\Exception $e) {
                Log::info('Conexión sin proxy falló: ' . $e->getMessage() . '. Intentando con proxy configurado...');
                try {
                    // Intentar con proxy configurado
                    $options = ['timeout' => 30, 'verify' => false, 'headers' => $headers];
                    if (env('HTTP_PROXY') || env('HTTPS_PROXY')) {
                        $options['proxy'] = [
                            'http' => env('HTTP_PROXY'),
                            'https' => env('HTTPS_PROXY', env('HTTP_PROXY')),
                        ];
                        if (env('NO_PROXY')) {
                            $options['proxy']['no'] = array_map('trim', explode(',', env('NO_PROXY')));
                        }
                    }
                    Log::info('Proxy options: ' . json_encode($options));
                    $response = Http::withOptions($options)->get('https://www.bps.gub.uy/bps/valores.jsp?contentid=5478');
                    Log::info('Intento con proxy - Respuesta HTTP: ' . $response->status() . ' - Body length: ' . strlen($response->body()));
                } catch (\Exception $e2) {
                    Log::error('Error al obtener valor UR con proxy: ' . $e2->getMessage());
                    return null;
                }
            }

            if ($response->successful()) {
                $html = $response->body();
                Log::info('HTML obtenido, longitud: ' . strlen($html));

                // Guardar HTML completo en archivo para depuración
                $filename = storage_path('app/debug_bps_' . time() . '.html');
                file_put_contents($filename, $html);
                Log::info('HTML guardado en: ' . $filename);

                // Log parte del HTML para verificar estructura
                $preview = substr($html, 0, 500);
                Log::info('Preview HTML: ' . $preview);

                // Usar regex para encontrar el valor UR específico
                // Buscar en la fila de Unidad Reajustable, el tercer valor (columna 3)
                $patterns = [
                    '/Unidad Reajustable[^$]*\$[^$]*\$[^$]*\$[^>]*(\d+\.\d+,\d+)/i',  // Tercer $ con formato completo
                    '/Unidad Reajustable[^$]*\$[^$]*\$[^>]*(\d+\.\d+,\d+)/i',        // Segundo $ con formato completo (fallback)
                    '/Unidad Reajustable.*?(\d+\.\d+,\d+)/i',                        // Formato completo general
                    '/Unidad Reajustable[^$]*\$[^$]*\$[^$]*\$[^>]*(\d+,\d+)/i',      // Fallback sin punto
                ];

                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $html, $matches)) {
                        $valorEncontrado = '$ ' . trim($matches[1]);
                        Log::info('Valor UR encontrado con patrón "' . $pattern . '": ' . $valorEncontrado);
                        return $valorEncontrado;
                    }
                }

                Log::warning('No se encontró ningún patrón regex para UR.');
                // Buscar todos los números con coma para depuración
                if (preg_match_all('/(\d+,\d+)/', $html, $matches2)) {
                    Log::info('Todos los valores numéricos encontrados: ' . implode(', ', $matches2[1]));
                }
                Log::warning('HTML completo guardado en archivo para revisión.');
            } else {
                Log::warning('Respuesta no exitosa: ' . $response->status() . ' - Body: ' . substr($response->body(), 0, 200));
            }
            return null;
        });

        if ($valor) {
            return response()->json(['valorUr' => $valor]);
        }

        // Fallback: usar un valor por defecto conocido (último valor conocido)
        $valorDefault = '$ 1.839,08'; // Valor actual de UR
        Log::warning('Usando valor UR por defecto: ' . $valorDefault);
        return response()->json(['valorUr' => $valorDefault]);
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
}
