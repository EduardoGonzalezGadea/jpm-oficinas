<?php

namespace App\Services;

use App\Services\Http\HttpClientService;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ValorUrService
{
    private const CACHE_KEY = 'valor_ur_completo';

    private const CACHE_KEY_ULTIMO_VALIDO = 'valor_ur_ultimo_valido';

    private const CACHE_TTL_MINUTES = 240;

    private const MESES_ES = [
        'enero' => 1,
        'febrero' => 2,
        'marzo' => 3,
        'abril' => 4,
        'mayo' => 5,
        'junio' => 6,
        'julio' => 7,
        'agosto' => 8,
        'septiembre' => 9,
        'octubre' => 10,
        'noviembre' => 11,
        'diciembre' => 12,
    ];

    private HttpClientService $httpClient;

    public function __construct(HttpClientService $httpClient = null)
    {
        $this->httpClient = $httpClient ?? app(HttpClientService::class);
    }

    /**
     * Obtiene el valor de la UR desde BPS con caché, reintentos y detección de vencimiento.
     */
    public function obtener(): array
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached) && empty($cached['vencido'])) {
            return $cached;
        }

        $desdeBps = $this->fetchFromBps();
        if ($desdeBps !== null) {
            $resultado = $this->buildResult($desdeBps['valorUr'], $desdeBps['mesUr'], 'bps');
            Cache::put(self::CACHE_KEY, $resultado, now()->addMinutes(self::CACHE_TTL_MINUTES));

            if (!$resultado['vencido']) {
                Cache::forever(self::CACHE_KEY_ULTIMO_VALIDO, $resultado);
            }

            return $resultado;
        }

        $ultimoValido = Cache::get(self::CACHE_KEY_ULTIMO_VALIDO);
        if (is_array($ultimoValido) && !empty($ultimoValido['valorUr'])) {
            return $this->buildResult(
                $ultimoValido['valorUr'],
                $ultimoValido['mesUr'] ?? null,
                'ultimo_valido'
            );
        }

        return $this->buildResult('$ 1.839,08', 'Noviembre', 'fallback');
    }

    /**
     * @return array{valorUr: string, mesUr: string|null}|null
     */
    public function fetchFromBps(): ?array
    {
        $config = config('external_downloads.valor_ur', []);
        $url = $config['url'] ?? 'https://www.bps.gub.uy/bps/valores.jsp?contentid=5478';
        $timeout = $config['timeout'] ?? 45;
        $maxRetries = $config['max_retries'] ?? 3;

        try {
            $response = $this->httpClient->getWithRetry(
                $url,
                ['timeout' => $timeout],
                $maxRetries,
                $config['retry_delay_ms'] ?? 1000,
                'valor_ur'
            );
        } catch (\Throwable $e) {
            Log::error('No se pudo obtener valor UR desde BPS: ' . $e->getMessage());

            return null;
        }

        if (!$response->successful()) {
            Log::error('Respuesta no exitosa al obtener UR desde BPS: ' . $response->status());

            return null;
        }

        $parsed = $this->parseBpsHtml($response->body());

        // Validar valor extraído
        if ($parsed && $this->isValidUrValue($parsed['valorUr'])) {
            return $parsed;
        }

        return null;
    }

    /**
     * @return array{valorUr: string, mesUr: string|null}|null
     */
    public function parseBpsHtml(string $html): ?array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $tables = $dom->getElementsByTagName('table');
        if ($tables->length === 0) {
            return $this->parseBpsHtmlRegex($html);
        }

        $table = $tables->item(0);
        $headers = $this->extractTableHeaders($xpath, $table);

        $rows = $xpath->query('.//tr', $table);
        foreach ($rows as $row) {
            $cells = $xpath->query('.//td', $row);
            if ($cells->length === 0) {
                continue;
            }

            $indicador = trim($cells->item(0)->nodeValue);
            if (stripos($indicador, 'Unidad Reajustable') === false) {
                continue;
            }

            for ($i = $cells->length - 1; $i >= 1; $i--) {
                $cellValue = $this->normalizeCellValue($cells->item($i)->nodeValue);

                if ($cellValue === '' || !preg_match('/[\d\.,]+/', $cellValue)) {
                    continue;
                }

                $valorUr = str_contains($cellValue, '$') ? $cellValue : '$ ' . $cellValue;
                $mesUr = $headers[$i] ?? (count($headers) > 0 ? end($headers) : null);

                Log::info("UR obtenida desde BPS: {$valorUr} - Mes: {$mesUr}");

                return [
                    'valorUr' => $valorUr,
                    'mesUr' => $mesUr ? trim($mesUr) : null,
                ];
            }
        }

        return $this->parseBpsHtmlRegex($html);
    }

    public function esMesVigente(?string $mesUr): bool
    {
        if ($mesUr === null || trim($mesUr) === '') {
            return false;
        }

        $mesNumero = $this->mesEspanolANumero($mesUr);
        if ($mesNumero === null) {
            return false;
        }

        return $mesNumero === Carbon::now('America/Montevideo')->month;
    }

    /**
     * @return array{valorUr: string, mesUr: string|null, vencido: bool, fuente: string}
     */
    private function buildResult(string $valorUr, ?string $mesUr, string $fuente): array
    {
        return [
            'valorUr' => $valorUr,
            'mesUr' => $mesUr,
            'vencido' => !$this->esMesVigente($mesUr),
            'fuente' => $fuente,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function extractTableHeaders(DOMXPath $xpath, \DOMNode $table): array
    {
        $headers = [];
        $thList = $xpath->query('.//thead//th | .//thead//td', $table);

        if ($thList->length === 0) {
            $thList = $xpath->query('.//tr[1]//td | .//tr[1]//th', $table);
        }

        foreach ($thList as $th) {
            $headers[] = trim($th->nodeValue);
        }

        return $headers;
    }

    private function normalizeCellValue(string $value): string
    {
        return trim(str_replace(["\xC2\xA0", '&nbsp;'], ' ', $value));
    }

    /**
     * @return array{valorUr: string, mesUr: null}|null
     */
    private function parseBpsHtmlRegex(string $html): ?array
    {
        if (preg_match('/Unidad Reajustable[^$]*\$[^$]*\$[^$]*\$[^>]*(\d+\.\d+,\d+)/i', $html, $matches)) {
            return [
                'valorUr' => '$ ' . $matches[1],
                'mesUr' => null,
            ];
        }

        return null;
    }

    private function mesEspanolANumero(string $mes): ?int
    {
        $mesNormalizado = mb_strtolower(trim($mes), 'UTF-8');

        return self::MESES_ES[$mesNormalizado] ?? null;
    }

    /**
     * Valida que el valor de UR sea razonable
     *
     * @param string $valorUr Valor en formato "$ 1.839,08"
     * @return bool
     */
    private function isValidUrValue(string $valorUr): bool
    {
        $config = config('external_downloads.valor_ur.validation', []);
        $minValue = $config['min_value'] ?? 100;
        $maxValue = $config['max_value'] ?? 10000;

        // Extraer número del valor
        if (!preg_match('/[\d\.]+[,.]?\d{2}/', $valorUr, $matches)) {
            return false;
        }

        $numericValue = (float) str_replace(['.', ','], ['' , '.'], $matches[0]);

        // Validar rango
        if ($numericValue < $minValue || $numericValue > $maxValue) {
            Log::warning("Valor UR fuera de rango: {$valorUr} (rango: {$minValue}-{$maxValue})");
            return false;
        }

        return true;
    }
}
