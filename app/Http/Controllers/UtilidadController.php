<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use DOMDocument;
use DOMXPath;

class UtilidadController extends Controller
{
    /**
     * Obtiene el valor de la Unidad Indexada (UI) desde el sitio del BPS.
     * Almacena el valor en caché durante 4 horas para evitar múltiples solicitudes.
     */
    public function getValorUr()
    {
        $valor = Cache::remember('valor_ur', 60 * 4, function () {
            // Intentar primero con proxy configurado (para intranet)
            $options = [];
            if (env('HTTP_PROXY') || env('HTTPS_PROXY')) {
                $options['proxy'] = [
                    'http' => env('HTTP_PROXY'),
                    'https' => env('HTTPS_PROXY', env('HTTP_PROXY')),
                ];
                if (env('NO_PROXY')) {
                    $options['proxy']['no'] = explode(',', env('NO_PROXY'));
                }
            }

            try {
                $response = Http::withOptions($options)->get('https://www.bps.gub.uy/bps/valores.jsp?contentid=5478');
                \Log::info('Intento con proxy - Respuesta HTTP: ' . $response->status() . ' - Body length: ' . strlen($response->body()));
            } catch (\Exception $e) {
                \Log::info('Conexión con proxy falló: ' . $e->getMessage() . '. Intentando sin proxy...');
                try {
                    // Forzar explícitamente sin proxy
                    $response = Http::withOptions([
                        'proxy' => false
                    ])->get('https://www.bps.gub.uy/bps/valores.jsp?contentid=5478');
                    \Log::info('Intento sin proxy - Respuesta HTTP: ' . $response->status() . ' - Body length: ' . strlen($response->body()));
                } catch (\Exception $e2) {
                    \Log::error('Error al obtener valor UR sin proxy: ' . $e2->getMessage());
                    return null;
                }
            }

            if ($response->successful()) {
                $html = $response->body();
                \Log::info('HTML obtenido, longitud: ' . strlen($html));

                $dom = new DOMDocument();
                // Suprimir errores de HTML mal formado
                @$dom->loadHTML($html);
                $xpath = new DOMXPath($dom);

                // Buscar la fila que contiene "Unidad Indexada (UI)" y obtener el valor de la tercera columna
                $query = "//table//tr[td[1][contains(., 'Unidad Indexada (UI)')]]/td[3]";
                $nodos = $xpath->query($query);

                \Log::info('Nodos encontrados: ' . $nodos->length);

                if ($nodos->length > 0) {
                    $valorEncontrado = trim($nodos->item(0)->nodeValue);
                    \Log::info('Valor UR encontrado: ' . $valorEncontrado);
                    return $valorEncontrado;
                } else {
                    \Log::warning('No se encontraron nodos para la consulta XPath');
                }
            } else {
                \Log::warning('Respuesta no exitosa: ' . $response->status());
            }
            return null;
        });

        if ($valor) {
            return response()->json(['valorUr' => $valor]);
        }

        return response()->json(['valorUr' => 'No disponible'], 404);
    }
}
