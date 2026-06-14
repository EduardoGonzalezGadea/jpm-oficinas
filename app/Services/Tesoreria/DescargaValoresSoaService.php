<?php

namespace App\Services\Tesoreria;

use App\Models\Tesoreria\Multa;
use App\Services\Http\HttpClientService;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

/**
 * Servicio para descargar y procesar valores SOA (Seguro Obligatorio de Autos) desde BCU.
 *
 * Actualiza los valores de las multas por Art. 184 basándose en el PDF publicado por el BCU.
 */
class DescargaValoresSoaService
{
    private const CACHE_KEY = 'valores_soa_completo';

    private HttpClientService $httpClient;

    public function __construct(HttpClientService $httpClient = null)
    {
        $this->httpClient = $httpClient ?? app(HttpClientService::class);
    }

    /**
     * Descarga y actualiza valores SOA
     *
     * @return array{
     *     success: bool,
     *     message: string,
     *     pdf_url: string|null,
     *     updated_count: int,
     *     details: array,
     *     errors: array
     * }
     */
    public function descargarYActualizar(): array
    {
        $config = config('external_downloads.valores_soa', []);

        if (!($config['enabled'] ?? true)) {
            return [
                'success' => false,
                'message' => 'Servicio SOA deshabilitado',
                'pdf_url' => null,
                'updated_count' => 0,
                'details' => [],
                'errors' => [],
            ];
        }

        try {
            // Paso 1: Obtener la URL del PDF desde la web del BCU
            $urlFuente = $config['url_source'] ?? 'https://www.bcu.gub.uy/Servicios-Financieros-SSF/Paginas/ImpPromCostoDelSOA.aspx';
            $pdfUrl = $this->obtenerUrlPdf($urlFuente, $config);

            if (!$pdfUrl) {
                throw new \Exception("No se encontró la URL del PDF del SOA");
            }

            // Paso 2: Descargar PDF
            $pdfContent = $this->descargarPdf($pdfUrl, $config);

            if (!$pdfContent) {
                throw new \Exception("No se pudo descargar el PDF del SOA");
            }

            // Paso 3: Parsear PDF
            $textoPdf = $this->parsearPdf($pdfContent);

            if (!$textoPdf) {
                throw new \Exception("No se pudo extraer texto del PDF del SOA");
            }

            // Paso 4: Extraer valores y actualizar BD
            $resultado = $this->extraerYActualizar($textoPdf, $config);
            $resultado['pdf_url'] = $pdfUrl;

            // Cachear éxito
            Cache::put(self::CACHE_KEY, $resultado, now()->addMinutes($config['cache_ttl_minutes'] ?? 10080));

            return $resultado;

        } catch (\Exception $e) {
            Log::error("Error descargando SOA: " . $e->getMessage(), ['exception' => $e]);

            return [
                'success' => false,
                'message' => $this->getUsuarioFriendlyErrorMessage($e->getMessage()),
                'pdf_url' => null,
                'updated_count' => 0,
                'details' => [],
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Obtiene la URL del PDF desde la web del BCU
     */
    protected function obtenerUrlPdf(string $urlFuente, array $config): ?string
    {
        try {
            $response = $this->httpClient->getWithRetry(
                $urlFuente,
                ['timeout' => 60],
                $config['max_retries'] ?? 1,
                $config['retry_delay_ms'] ?? 2000,
                'valores_soa'
            );

            if (!$response->successful()) {
                throw new \Exception("No se pudo acceder a la web del BCU (HTTP {$response->status()})");
            }

            $html = $response->body();
            $dom = new DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            $xpath = new DOMXPath($dom);

            // Buscar enlaces al PDF
            $pdfPattern = $config['pdf_pattern'] ?? '/SOA_Prima_Promedio.*?\.pdf/i';

            // Estrategia 1: Buscar en atributos href de enlaces
            $links = $xpath->query('//a[@href]');
            if ($links && $links->length > 0) {
                foreach ($links as $link) {
                    $href = $link->attributes ? $link->attributes->getNamedItem('href')?->nodeValue : null;
                    if ($href && preg_match($pdfPattern, $href)) {
                        return $this->normalizarUrlPdf($href);
                    }
                }
            }

            // Estrategia 2: Buscar en data-href de filas
            $rows = $xpath->query('//tr[@data-href]');
            if ($rows && $rows->length > 0) {
                foreach ($rows as $row) {
                    $href = $row->attributes ? $row->attributes->getNamedItem('data-href')?->nodeValue : null;
                    if ($href && preg_match($pdfPattern, $href)) {
                        return $this->normalizarUrlPdf($href);
                    }
                }
            }

            // Estrategia 3: Buscar en toda el HTML con regex
            if (preg_match('/href=["\']([^"\']*SOA_Prima_Promedio[^"\']*\.pdf)/i', $html, $matches)) {
                return $this->normalizarUrlPdf($matches[1]);
            }

            throw new \Exception("No se encontraron enlaces al PDF del SOA en el HTML del BCU");

        } catch (\Exception $e) {
            Log::warning("Error obteniendo URL del PDF: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Normaliza URL relativa del PDF
     */
    protected function normalizarUrlPdf(string $url): string
    {
        if (strpos($url, 'http') === 0) {
            return $url;
        }

        // URL relativa, agregar dominio del BCU
        $url = ltrim($url, '/');
        return 'https://www.bcu.gub.uy/' . $url;
    }

    /**
     * Descarga el contenido del PDF
     */
    protected function descargarPdf(string $url, array $config): ?string
    {
        try {
            $response = $this->httpClient->getWithRetry(
                $url,
                ['timeout' => $config['timeout'] ?? 120],
                $config['max_retries'] ?? 1,
                $config['retry_delay_ms'] ?? 2000,
                'valores_soa'
            );

            if (!$response->successful()) {
                throw new \Exception("No se pudo descargar PDF (HTTP {$response->status()})");
            }

            return $response->body();

        } catch (\Exception $e) {
            Log::warning("Error descargando PDF: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Parsea el contenido del PDF y extrae texto
     */
    protected function parsearPdf(string $pdfContent): ?string
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseContent($pdfContent);
            return $pdf->getText();
        } catch (\Exception $e) {
            Log::warning("Error parseando PDF: " . $e->getMessage());
            throw new \Exception("No se pudo parsear el PDF del SOA: " . $e->getMessage());
        }
    }

    /**
     * Extrae valores del texto del PDF y actualiza BD
     */
    protected function extraerYActualizar(string $texto, array $config): array
    {
        $categorias = [
            1 => 'Motos',
            2 => 'Autom.viles y Camionetas',
            3 => 'Camiones',
            4 => '.mnibus',
            5 => 'Taxis',
            6 => 'Remises',
            7 => 'Veh.culos.*?alq',
            8 => 'Ambulancias',
            9 => 'Coche Escuela',
            10 => 'Trailers',
            11 => 'Casa Rodante',
            12 => 'Tractores',
            13 => 'Otros'
        ];

        $actualizados = 0;
        $detalles = [];
        $errores = [];

        $multiplier = $config['validation']['multiplier'] ?? 2;
        $minValue = $config['validation']['min_value'] ?? 0.01;
        $maxValue = $config['validation']['max_value'] ?? 1000000;

        // Usar transacción para garantizar atomicidad
        DB::beginTransaction();

        try {
            foreach ($categorias as $apartado => $nombrePattern) {
                try {
                    // Regex: nombre + números
                    $pattern = '/' . $nombrePattern . '.*?(\d{1,3}(?:\.\d{3})*(?:,\d{2})?)/isu';

                    if (preg_match($pattern, $texto, $matches)) {
                        $valorTexto = str_replace(['.', ','], '', $matches[1]);
                        $valorNumerico = (float) $valorTexto;

                        // Validar rango
                        if ($valorNumerico < $minValue || $valorNumerico > $maxValue) {
                            $errores[] = "Apartado $apartado: valor fuera de rango ({$valorNumerico})";
                            continue;
                        }

                        $nuevoImporte = $valorNumerico * $multiplier;

                        // Actualizar en BD
                        $afectados = Multa::where('articulo', '184')
                            ->where('apartado', (string) $apartado)
                            ->update([
                                'importe_original' => $nuevoImporte,
                                'moneda' => 'UYU',
                                'updated_at' => now()
                            ]);

                        if ($afectados > 0) {
                            $actualizados += $afectados;
                            $detalles[] = "Apartado $apartado ($nombrePattern): $valorNumerico → $nuevoImporte UYU";
                        } else {
                            $errores[] = "Apartado $apartado: no se encontraron registros en BD";
                        }

                    } else {
                        $errores[] = "Apartado $apartado ($nombrePattern): no se encontró valor en el PDF";
                    }

                } catch (\Exception $e) {
                    $errores[] = "Apartado $apartado: " . $e->getMessage();
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Error durante actualización en BD: " . $e->getMessage());
        }

        return [
            'success' => true,
            'message' => "Se actualizaron $actualizados registros",
            'pdf_url' => null, // Se asigna en descargarYActualizar()
            'updated_count' => $actualizados,
            'details' => $detalles,
            'errors' => $errores,
        ];
    }

    /**
     * Convierte mensajes técnicos a mensajes amigables para el usuario
     */
    protected function getUsuarioFriendlyErrorMessage(string $errorTecnico): string
    {
        if (stripos($errorTecnico, 'timeout') !== false ||
            stripos($errorTecnico, 'connection') !== false) {
            return 'No se pudo conectar al sitio del BCU. Verifique la conexión a internet o intente nuevamente más tarde.';
        }

        if (stripos($errorTecnico, 'No se pudo acceder') !== false) {
            return 'El sitio del BCU no está disponible temporalmente. Intente nuevamente más tarde.';
        }

        if (stripos($errorTecnico, 'No se encontró') !== false) {
            return 'No se pudo encontrar el PDF del SOA. Es posible que el BCU haya cambiado la estructura de su sitio.';
        }

        return 'Error al descargar y procesar valores SOA. Por favor, contacte al administrador del sistema.';
    }
}
