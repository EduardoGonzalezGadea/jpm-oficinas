<?php

namespace App\Services;

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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

/**
 * Servicio refactorizado para procesamiento de CFE mediante patrón Strategy.
 * Delega la extracción de datos a extractores especializados por tipo de CFE.
 */
class CfeProcessorService
{
    /**
     * Extractores registrados, ordenados por prioridad de detección.
     *
     * @var CfeExtractorInterface[]
     */
    private array $extractors;

    public function __construct(
        private readonly CfePendienteRepository $repository
    ) {
        // Orden importa: tipos más específicos primero para evitar falsos positivos
        $this->extractors = [
            new CertificadoResidenciaExtractor(),
            new MultasExtractor(),
            new PrendasExtractor(),
            new ArrendamientosExtractor(),
            new ArmasExtractor(),
            new EventualesExtractor(), // Fallback genérico (e-Factura/e-Ticket)
        ];
    }

    /**
     * Procesa un archivo PDF y crea el registro CFE pendiente.
     *
     * @param  \Illuminate\Http\UploadedFile  $pdf
     * @param  string|null  $sourceUrl
     * @param  int|null  $userId
     * @return TesCfePendiente
     */
    public function procesarPdf($pdf, ?string $sourceUrl = null, ?int $userId = null): TesCfePendiente
    {
        $pdfPath = $pdf->store('cfe-pendientes');

        $texto = $this->parsearPdf(Storage::path($pdfPath));
        $tipoCfe = $this->detectarTipoCfe($texto);

        $extractor = $this->getExtractor($tipoCfe);
        $datosExtraidos = $extractor->extraer($texto);

        $validacion = $extractor->validar($datosExtraidos);
        if (!$validacion['valid']) {
            $this->logParsingWarning($tipoCfe, $texto, $datosExtraidos, $validacion['errors']);
        }

        return $this->repository->crear([
            'tipo_cfe'        => $tipoCfe,
            'serie'           => $datosExtraidos['serie'] ?? null,
            'numero'          => $datosExtraidos['numero'] ?? null,
            'fecha'           => $datosExtraidos['fecha'] ?? null,
            'monto'           => $this->normalizarMonto($datosExtraidos),
            'moneda'          => $datosExtraidos['moneda'] ?? 'UYU',
            'datos_extraidos' => $datosExtraidos,
            'pdf_path'        => $pdfPath,
            'source_url'      => $sourceUrl,
            'user_id'         => $userId,
            'estado'          => 'pendiente',
        ]);
    }

    /**
     * Detecta el tipo de CFE basado en palabras clave del texto.
     *
     * @param string $texto
     * @return string
     */
    public function detectarTipoCfe(string $texto): string
    {
        $textoLower = mb_strtolower($texto, 'UTF-8');
        $textoNorm  = $this->quitarAcentos($textoLower);

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

    /**
     * Parsea un archivo PDF y retorna el texto extraído.
     *
     * @param string $rutaAbsoluta Ruta absoluta al archivo PDF
     * @return string
     *
     * @throws \Exception
     */
    public function parsearPdf(string $rutaAbsoluta): string
    {
        $parser = new Parser();
        $pdf    = $parser->parseFile($rutaAbsoluta);
        return $pdf->getText();
    }

    /**
     * Retorna el extractor adecuado para el tipo de CFE dado.
     * Si no hay uno específico, devuelve el EventualesExtractor como fallback.
     *
     * @param string $tipoCfe
     * @return CfeExtractorInterface
     */
    public function getExtractor(string $tipoCfe): CfeExtractorInterface
    {
        foreach ($this->extractors as $extractor) {
            if ($extractor->soporta($tipoCfe)) {
                return $extractor;
            }
        }

        // Fallback: EventualesExtractor (genérico)
        return new EventualesExtractor();
    }

    /**
     * Quita acentos del texto para comparación insensible a caracteres especiales.
     *
     * @param string $texto
     * @return string
     */
    public function quitarAcentos(string $texto): string
    {
        $search  = ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'Ü'];
        $replace = ['a', 'e', 'i', 'o', 'u', 'n', 'u', 'a', 'e', 'i', 'o', 'u', 'n', 'u'];
        return str_replace($search, $replace, $texto);
    }

    /**
     * Normaliza el campo "monto" de los datos extraídos a float.
     * Distintos extractores usan diferentes claves (monto, monto_total).
     *
     * @param array $datos
     * @return float
     */
    private function normalizarMonto(array $datos): float
    {
        $valor = $datos['monto'] ?? $datos['monto_total'] ?? 0;

        if (is_numeric($valor)) {
            return (float) $valor;
        }

        return (float) str_replace(['.', ','], ['', '.'], (string) $valor);
    }

    /**
     * Analiza un PDF desde una ruta local y retorna los datos extraídos sin persistir.
     * Usado por la extensión del navegador para previsualizar antes de confirmar.
     *
     * @param string $filepath Ruta absoluta al PDF
     * @return array ['es_cfe' => bool, 'tipo_cfe' => string, 'tipo_cfe_codigo' => string, 'datos' => array, 'mensaje' => string]
     */
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

    /**
     * Prepara un registro a partir del análisis previo: guarda datos en caché
     * y retorna la URL de redirección al formulario del módulo correspondiente.
     *
     * @param string      $tipoCfe  Código de tipo (ej: 'multas_cobradas')
     * @param array       $datos    Datos extraídos del PDF
     * @param string|null $filepath Ruta original del PDF (para referencia)
     * @return array ['success' => bool, 'mensaje' => string, 'redirect_url' => string]
     */
    public function crearRegistroDesdeAnalisis(string $tipoCfe, array $datos, ?string $filepath = null): array
    {
        // Mapa tipo → URL del formulario de carga
        $rutas = [
            'multas_cobradas'        => 'tesoreria/multas-cobradas/cargar-cfe',
            'eventuales'             => 'tesoreria/eventuales/cargar-efactura',
            'porte_armas'            => 'tesoreria/armas/cargar-cfe',
            'tenencia_armas'         => 'tesoreria/armas/cargar-cfe',
            'certificado_residencia' => 'tesoreria/certificados-residencia/cargar-cfe',
            'arrendamientos'         => 'tesoreria/arrendamientos/cargar-cfe',
            'prendas'                => 'tesoreria/prendas/cargar-cfe',
        ];

        // Normalizar: aceptar tanto código como nombre legible del tipo
        $tipoCodigo = mb_strtolower(str_replace([' ', '-'], '_', $tipoCfe));
        $rutaRelativa = $rutas[$tipoCodigo] ?? ($rutas[$tipoCfe] ?? null);

        if (!$rutaRelativa) {
            return [
                'success' => false,
                'mensaje' => 'Tipo de CFE no soportado para creación automática: ' . $tipoCfe,
            ];
        }

        // Persistir datos en caché 15 minutos para que el formulario pueda leerlos
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

    /**
     * Registra automáticamente una multa a partir de los datos ya extraídos del CFE.
     * Valida consistencia, verifica duplicados y persiste cabecera + ítems en una transacción.
     *
     * @param array $datos   Datos extraídos por MultasExtractor
     * @param int   $userId  ID del usuario que registra
     * @return TesMultasCobradas
     * @throws \Exception Si hay inconsistencia, duplicado o error de BD
     */
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
                'fecha'      => $fecha->format('Y-m-d'),
                'recibo'     => $recibo,
                'monto'      => $montoTotal,
                'nombre'     => mb_strtoupper($datos['nombre'], 'UTF-8'),
                'cedula'     => $datos['cedula'],
                'adicional'  => $datos['adicional'] ?? null,
                'adenda'     => $datos['adenda'] ?? null,
                'referencias' => $datos['referencias'] ?? null,
                'forma_pago' => $datos['forma_pago'] ?? 'SIN DATOS',
                'created_by' => $userId,
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

    /**
     * Registra una advertencia cuando la extracción no es completamente válida.
     *
     * @param string $tipoCfe
     * @param string $texto
     * @param array  $datosExtraidos
     * @param array  $errores
     * @return void
     */
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
