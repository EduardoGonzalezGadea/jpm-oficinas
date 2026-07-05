<?php

namespace App\Services;

use App\DTOs\CfeExtraccionDto;
use App\Events\Tesoreria\CfeProcesado;
use App\Exceptions\CfeExtraccionInvalidaException;
use App\Helpers\TextoHelper;
use App\Jobs\ConfirmarCfeJob;
use App\Models\TesCfePendiente;
use App\Models\Tesoreria\TesMultasCobradas;
use App\Repositories\CfePendienteRepository;
use App\Services\CfeExtractor\ArmasExtractor;
use App\Services\CfeExtractor\ArrendamientosExtractor;
use App\Services\CfeExtractor\CertificadoResidenciaExtractor;
use App\Services\CfeExtractor\CfeExtractorInterface;
use App\Services\CfeExtractor\EventualesExtractor;
use App\Services\CfeExtractor\MultasExtractor;
use App\Services\CfeExtractor\PrendasExtractor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

class CfeProcessorService
{
    private array $extractors;

    public function __construct(
        private readonly CfePendienteRepository $repository
    ) {
        $this->extractors = [
            new CertificadoResidenciaExtractor(),
            new MultasExtractor(),
            new PrendasExtractor(),
            new ArrendamientosExtractor(),
            new ArmasExtractor(),
            new EventualesExtractor(),
        ];
    }

    public function procesarPdf($pdf, ?string $sourceUrl = null, ?int $userId = null): TesCfePendiente
    {
        $inicio = microtime(true);

        $pdfPath = $pdf->store('cfe-pendientes');
        $absolutePath = Storage::path($pdfPath);
        $pdfHash = hash_file('sha256', $absolutePath);

        $existente = $this->repository->buscarPorPdfHash($pdfHash);
        if ($existente) {
            Storage::delete($pdfPath);
            return $existente;
        }

        $result = $this->ejecutarExtraccion($absolutePath, $pdfHash);

        return $this->crearPendienteDesdeExtraccion(
            $result, $pdfPath, $sourceUrl, $pdfHash, $userId, $inicio
        );
    }

    protected function ejecutarExtraccion(string $absolutePath, string $pdfHash): array
    {
        $cacheKey = 'cfe_pdf_' . $pdfHash;

        if (Config::get('cfe.cache.enabled', true)) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return [
                    'dto'              => CfeExtraccionDto::fromArray($cached['datos'])
                        ->withExtractorVersion($cached['extractor_version']),
                    'tipo_cfe'         => $cached['tipo_cfe'],
                    'extractor_version' => $cached['extractor_version'],
                    'fallback_usado'   => $cached['fallback_usado'] ?? false,
                    'desde_cache'      => true,
                ];
            }
        }

        $texto = $this->parsearPdf($absolutePath);
        $tipoCfe = $this->detectarTipoCfe($texto);

        $extractor = $this->getExtractor($tipoCfe);
        $fallbackUsado = false;

        try {
            $dto = $extractor->extraer($texto);
        } catch (CfeExtraccionInvalidaException $e) {
            Log::channel('cfe_errors')->warning('Extractor principal falló, usando fallback', [
                'tipo_cfe'          => $tipoCfe,
                'extractor'         => get_class($extractor),
                'extractor_version' => $extractor->getExtractorVersion(),
                'errores'           => $e->errores,
                'pdf_hash'          => $pdfHash,
            ]);

            $extractor = new EventualesExtractor();
            $fallbackUsado = true;
            $dto = $extractor->extraer($texto);
        }

        $extractorVersion = $extractor->getExtractorVersion();

        if (Config::get('cfe.cache.enabled', true)) {
            Cache::put(
                $cacheKey,
                [
                    'tipo_cfe'          => $tipoCfe,
                    'datos'             => $dto->withExtractorVersion($extractorVersion)->toArray(),
                    'extractor_version' => $extractorVersion,
                    'fallback_usado'    => $fallbackUsado,
                    'procesado_at'      => now()->toIso8601String(),
                ],
                now()->addDays((int) Config::get('cfe.cache.ttl_dias', 7))
            );
        }

        return [
            'dto'              => $dto,
            'tipo_cfe'         => $tipoCfe,
            'extractor_version' => $extractorVersion,
            'fallback_usado'   => $fallbackUsado,
            'desde_cache'      => false,
        ];
    }

    private function crearPendienteDesdeExtraccion(
        array $result,
        string $pdfPath,
        ?string $sourceUrl,
        string $pdfHash,
        ?int $userId,
        float $inicio
    ): TesCfePendiente {
        $dto = $result['dto'];
        $tipoCfe = $result['tipo_cfe'];
        $extractorVersion = $result['extractor_version'];
        $fallbackUsado = $result['fallback_usado'];

        $pendiente = $this->repository->crear([
            'tipo_cfe'          => $tipoCfe,
            'serie'             => $dto->serie,
            'numero'            => $dto->numero,
            'fecha'             => $dto->fecha,
            'monto'             => $dto->monto,
            'moneda'            => $dto->moneda,
            'datos_extraidos'   => $dto->toArray(),
            'pdf_path'          => $pdfPath,
            'source_url'        => $sourceUrl,
            'pdf_hash'          => $pdfHash,
            'extractor_version' => $extractorVersion,
            'user_id'           => $userId,
            'estado'            => 'pendiente',
        ]);

        $duracionMs = (int) ((microtime(true) - $inicio) * 1000);
        event(new CfeProcesado($pendiente, $tipoCfe, $dto->toArray(), $duracionMs));

        if ($fallbackUsado) {
            $datosExtra = $pendiente->datos_extraidos;
            $datosExtra['fallback_usado'] = true;
            $pendiente->datos_extraidos = $datosExtra;
            $pendiente->save();
        }

        if (Config::get("cfe.auto_confirm_types.{$tipoCfe}", false)) {
            ConfirmarCfeJob::dispatch($pendiente->id)->onQueue(
                Config::get('cfe.jobs.confirm.queue', 'cfe-confirmation')
            );
        }

        return $pendiente;
    }

    public function procesarPendienteExistente(TesCfePendiente $pendiente): TesCfePendiente
    {
        $absolutePath = Storage::path($pendiente->pdf_path);

        if (!$absolutePath || !file_exists($absolutePath)) {
            throw new \RuntimeException(
                "Archivo PDF no encontrado para pendiente {$pendiente->id}: {$pendiente->pdf_path}"
            );
        }

        $pdfHash = $pendiente->pdf_hash ?? hash_file('sha256', $absolutePath);
        $result = $this->ejecutarExtraccion($absolutePath, $pdfHash);

        $dto = $result['dto'];
        $tipoCfe = $result['tipo_cfe'];
        $extractorVersion = $result['extractor_version'];
        $fallbackUsado = $result['fallback_usado'];

        $pendiente->update([
            'tipo_cfe'          => $tipoCfe,
            'serie'             => $dto->serie,
            'numero'            => $dto->numero,
            'fecha'             => $dto->fecha,
            'monto'             => $dto->monto,
            'moneda'            => $dto->moneda,
            'datos_extraidos'   => $dto->toArray(),
            'extractor_version' => $extractorVersion,
            'pdf_hash'          => $pdfHash,
            'estado'            => 'pendiente',
        ]);

        event(new CfeProcesado($pendiente, $tipoCfe, $dto->toArray(), 0));

        if ($fallbackUsado) {
            $datosExtra = $pendiente->datos_extraidos;
            $datosExtra['fallback_usado'] = true;
            $pendiente->datos_extraidos = $datosExtra;
            $pendiente->save();
        }

        if (Config::get("cfe.auto_confirm_types.{$tipoCfe}", false)) {
            ConfirmarCfeJob::dispatch($pendiente->id)->onQueue(
                Config::get('cfe.jobs.confirm.queue', 'cfe-confirmation')
            );
        }

        return $pendiente->fresh();
    }

    public function detectarTipoCfe(string $texto): string
    {
        $textoLower = mb_strtolower($texto, 'UTF-8');
        $textoNorm  = TextoHelper::quitarAcentos($textoLower);

        if (Str::contains($textoNorm, ['certificado de residencia', 'certificado residencia'])) {
            return 'certificado_residencia';
        }

        if (Str::contains($textoNorm, ['multa', 'infraccion', 'transito'])) {
            return 'multas_cobradas';
        }

        if (Str::contains($textoNorm, ['prenda', 'prendas'])) {
            return 'prendas';
        }

        if (Str::contains($textoNorm, ['arrendamiento', 'arrendamientos'])) {
            return 'arrendamientos';
        }

        if (Str::contains($textoLower, ['aguinaldo', 'policias eventuales', 'eventuales'])) {
            return 'eventuales';
        }

        if (Str::contains($textoNorm, ['tenencia', 'tahta'])) {
            return 'tenencia_armas';
        }

        if (Str::contains($textoNorm, 'porte')) {
            return 'porte_armas';
        }

        if (Str::contains($textoNorm, 'arma')) {
            return 'tenencia_armas';
        }

        if (Str::contains($textoLower, ['e-factura', 'e-ticket', 'e-boleta'])) {
            return 'generico';
        }

        return 'desconocido';
    }

    public function parsearPdf(string $rutaAbsoluta): string
    {
        $parser  = new Parser();
        $content = file_get_contents($rutaAbsoluta);

        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        $trimmedContent = rtrim($content);
        if (str_ends_with($trimmedContent, '%%E')) {
            $content = $trimmedContent . 'OF';
        } elseif (str_ends_with($trimmedContent, '%%EO')) {
            $content = $trimmedContent . 'F';
        }

        $pdf = $parser->parseContent($content);
        return $pdf->getText();
    }

    public function getExtractor(string $tipoCfe): CfeExtractorInterface
    {
        foreach ($this->extractors as $extractor) {
            if ($extractor->soporta($tipoCfe)) {
                return $extractor;
            }
        }

        return new EventualesExtractor();
    }


    private function normalizarMonto(array $datos): float
    {
        $valor = $datos['monto'] ?? $datos['monto_total'] ?? 0;

        if (is_numeric($valor)) {
            return (float) $valor;
        }

        return (float) str_replace(['.', ','], ['', '.'], (string) $valor);
    }

    public function analizarPdf(string $filepath): array
    {
        if (!file_exists($filepath)) {
            return [
                'es_cfe'  => false,
                'mensaje' => 'El archivo no existe en la ruta: ' . $filepath,
                'datos'   => [],
            ];
        }

        $texto   = $this->parsearPdf($filepath);
        $tipoCfe = $this->detectarTipoCfe($texto);

        if (in_array($tipoCfe, ['desconocido', 'generico'])) {
            return [
                'es_cfe'  => false,
                'mensaje' => 'Este PDF no contiene un CFE reconocido por el sistema.',
                'datos'   => [],
            ];
        }

        $extractor      = $this->getExtractor($tipoCfe);
        $datosExtraidos = $extractor->extraer($texto);
        $nombreLegible  = $extractor->getNombreLegible();

        return [
            'es_cfe'         => true,
            'tipo_cfe'       => $nombreLegible,
            'tipo_cfe_codigo' => $tipoCfe,
            'datos'          => $datosExtraidos,
            'mensaje'        => 'CFE detectado: ' . $nombreLegible,
        ];
    }

    public function crearRegistroDesdeAnalisis(string $tipoCfe, array $datos, ?string $filepath = null): array
    {
        $rutas = [
            'multas_cobradas'        => 'tesoreria/multas-cobradas/cargar-cfe',
            'eventuales'             => 'tesoreria/eventuales/cargar-efactura',
            'porte_armas'            => 'tesoreria/armas/cargar-cfe',
            'tenencia_armas'         => 'tesoreria/armas/cargar-cfe',
            'certificado_residencia' => 'tesoreria/certificados-residencia/cargar-cfe',
            'arrendamientos'         => 'tesoreria/arrendamientos/cargar-cfe',
            'prendas'                => 'tesoreria/prendas/cargar-cfe',
        ];

        $tipoCodigo = mb_strtolower(str_replace([' ', '-'], '_', $tipoCfe));
        $rutaRelativa = $rutas[$tipoCodigo] ?? ($rutas[$tipoCfe] ?? null);

        if (!$rutaRelativa) {
            return [
                'success' => false,
                'mensaje' => 'Tipo de CFE no soportado para creación automática: ' . $tipoCfe,
            ];
        }

        $prefillId = Str::random(40);
        Cache::put('cfe_prefill_' . $prefillId, [
            'datos'    => $datos,
            'tipo'     => $tipoCfe,
            'filepath' => $filepath,
        ], now()->addMinutes(15));

        $redirectUrl = url($rutaRelativa) . '?prefill_id=' . $prefillId;

        $extractor     = $this->getExtractor($tipoCodigo);
        $nombreLegible = $extractor->getNombreLegible();

        return [
            'success'      => true,
            'mensaje'      => 'Registro preparado. Por favor confirme los datos en el módulo de ' . $nombreLegible . '.',
            'redirect_url' => $redirectUrl,
            'tipo_cfe'     => $tipoCfe,
        ];
    }

    public function registrarMultaAuto(array $datos, int $userId): TesMultasCobradas
    {
        if (empty($datos['items'])) {
            throw new \Exception('No se detectaron ítems válidos para guardar.');
        }

        $montoTotal = is_string($datos['monto_total'])
            ? (float) str_replace(['.', ','], ['', '.'], $datos['monto_total'])
            : (float) $datos['monto_total'];

        $sumaItems = collect($datos['items'])->sum('importe');
        if (abs($montoTotal - $sumaItems) > 0.1) {
            throw new \Exception(sprintf(
                'ERROR DE CONSISTENCIA: El total ($ %s) NO coincide con la suma de los ítems ($ %s).',
                number_format($montoTotal, 2, ',', '.'),
                number_format($sumaItems, 2, ',', '.')
            ));
        }

        try {
            $fecha = Carbon::createFromFormat('d/m/Y', $datos['fecha']);
        } catch (\Exception) {
            $fecha = now();
        }

        $recibo = $datos['serie'] . '-' . $datos['numero'];

        $existe = TesMultasCobradas::where('recibo', $recibo)
            ->whereDate('fecha', $fecha->format('Y-m-d'))
            ->exists();

        if ($existe) {
            throw new \Exception("El recibo {$recibo} ya fue cargado el día {$datos['fecha']}.");
        }

        return DB::transaction(function () use ($datos, $fecha, $recibo, $montoTotal, $userId) {
            $cobro = TesMultasCobradas::create([
                'fecha'       => $fecha->format('Y-m-d'),
                'recibo'      => $recibo,
                'monto'       => $montoTotal,
                'nombre'      => mb_strtoupper($datos['nombre'], 'UTF-8'),
                'cedula'      => $datos['cedula'],
                'adicional'   => $datos['adicional'] ?? null,
                'adenda'      => $datos['adenda'] ?? null,
                'referencias' => $datos['referencias'] ?? null,
                'forma_pago'  => $datos['forma_pago'] ?? 'SIN DATOS',
                'created_by'  => $userId,
            ]);

            foreach ($datos['items'] as $itemData) {
                $cobro->items()->create([
                    'detalle'     => mb_strtoupper($itemData['detalle'], 'UTF-8'),
                    'descripcion' => mb_strtoupper($itemData['descripcion'] ?? '', 'UTF-8'),
                    'importe'     => $itemData['importe'],
                    'created_by'  => $userId,
                ]);
            }

            Cache::flush();

            return $cobro;
        });
    }

    private function logParsingWarning(
        string $tipoCfe,
        string $texto,
        array $datosExtraidos,
        array $errores
    ): void {
        Log::channel('cfe_errors')->warning('Advertencia de extracción CFE', [
            'tipo'            => $tipoCfe,
            'errores'         => $errores,
            'datos_extraidos' => $datosExtraidos,
            'texto_preview'   => substr($texto, 0, 500),
            'timestamp'       => now()->toIso8601String(),
        ]);
    }
}
