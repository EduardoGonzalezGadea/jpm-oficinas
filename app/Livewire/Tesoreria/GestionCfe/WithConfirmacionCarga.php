<?php

namespace App\Livewire\Tesoreria\GestionCfe;

use App\DataTransferObjects\CfeData;
use App\Exceptions\Tesoreria\CfeDuplicateException;
use App\Exceptions\Tesoreria\CfeValidationException;
use Livewire\Attributes\On;
use App\Helpers\TextoHelper;
use App\Models\Tesoreria\CajaConcepto;
use App\Models\Tesoreria\TesCfe;
use App\Models\Tesoreria\TesCfeItem;
use App\Models\Tesoreria\SiifDistribucion;
use App\Services\Tesoreria\CfeUniversalParserService;
use Illuminate\Support\Facades\Log;

trait WithConfirmacionCarga
{
    private const CONCEPTOS_CON_VALIDACION_DE_MONTO = [
        'TÍTULO DE HABILITACIÓN Y TENENCIA DE ARMAS (THATA)',
        'PORTE DE ARMAS',
        'CERTIFICADO DE RESIDENCIA',
    ];

    public bool $mostrarModalConfirmacion = false;
    public array $datosExtraidos = [];
    public string $nombreArchivoOriginal = '';
    public string $rutaArchivoTemporal = '';
    public $confirmacionInstitucionSeleccionada = null;
    public bool $confirmacionConceptoRequiereInstitucion = false;

    public function updatedArchivoPdf(): void
    {
        if (!$this->archivoPdf) {
            Log::warning('updatedArchivoPdf: No se recibió archivo');
            return;
        }

        Log::info('updatedArchivoPdf: Iniciando carga de archivo', [
            'archivo_nombre' => $this->archivoPdf->getClientOriginalName(),
            'archivo_size' => $this->archivoPdf->getSize(),
            'archivo_mime' => $this->archivoPdf->getMimeType(),
        ]);

        try {
            $this->validate([
                'archivoPdf' => 'required|mimes:pdf|max:5120',
            ], [
                'archivoPdf.required' => 'Debe seleccionar un archivo.',
                'archivoPdf.mimes' => 'El archivo debe ser un PDF.',
                'archivoPdf.max' => 'El archivo no debe superar 5MB.',
            ]);

            Log::info('updatedArchivoPdf: Validación exitosa');

            $parser = app(CfeUniversalParserService::class);
            $datos = $parser->parsePdf($this->archivoPdf->getRealPath());
            $nombreOriginal = $this->archivoPdf->getClientOriginalName();

            Log::info('updatedArchivoPdf: PDF parseado exitosamente', [
                'items_encontrados' => count($datos['items'] ?? []),
                'medios_pago_encontrados' => count($datos['medios_pago'] ?? []),
            ]);

            $path = $this->archivoPdf->storeAs('cfes_cargados', time() . '_' . $nombreOriginal, 'local');

            Log::info('updatedArchivoPdf: Archivo almacenado', ['path' => $path]);

            $this->datosExtraidos = $datos;
            $this->nombreArchivoOriginal = $nombreOriginal;
            $this->rutaArchivoTemporal = $path;

            $this->cajaConceptoSeleccionado = $this->detectarConceptoAutomatico($datos);

            Log::info('updatedArchivoPdf: Concepto detectado', ['concepto_id' => $this->cajaConceptoSeleccionado]);

            // Establecer si el concepto requiere institución
            if ($this->cajaConceptoSeleccionado) {
                $concepto = CajaConcepto::find($this->cajaConceptoSeleccionado);
                $this->confirmacionConceptoRequiereInstitucion = $concepto ? $concepto->requiere_institucion : false;
            } else {
                $this->confirmacionConceptoRequiereInstitucion = false;
            }

            $this->resetItemDistribuciones();

            if ($this->cajaConceptoSeleccionado && $this->siifDependenciaSeleccionado) {
                $this->autoAsignarDistribuciones();
            }

            Log::info('updatedArchivoPdf: Abriendo modal de confirmación');

            $this->mostrarModalConfirmacion = true;
            $this->dispatch('abrir-modal-confirmacion-cfe');
            
            // Forzar re-render del componente
            $this->dispatch('$refresh');

            Log::info('updatedArchivoPdf: Proceso completado exitosamente');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('updatedArchivoPdf: Error de validación', [
                'errors' => $e->errors(),
            ]);

            $errores = collect($e->errors())->flatten()->implode(' ');
            $this->dispatch('swal:toast-error', text: "Error de validación: {$errores}");

        } catch (\Throwable $e) {
            Log::error('updatedArchivoPdf: Error general', [
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->dispatch('swal:toast-error', text: 'Error al procesar el archivo: ' . $e->getMessage());
        } finally {
            $this->reset('archivoPdf');
        }
    }

    private function detectarConceptoAutomatico(array $datos): ?int
    {
        $primerDetalle = trim($datos['items'][0]['detalle'] ?? '');

        if (empty($primerDetalle)) {
            return null;
        }

        $conceptos = CajaConcepto::whereNull('deleted_at')->get();
        $detalleNorm = TextoHelper::normalizarTexto($primerDetalle);

        foreach ($conceptos as $concepto) {
            $conceptoNorm = TextoHelper::normalizarTexto($concepto->caja_concepto);

            if ($detalleNorm === $conceptoNorm || str_contains($detalleNorm, $conceptoNorm)) {
                return $concepto->id;
            }
        }

        $ultimosItems = TesCfeItem::with('cfe')
            ->where('detalle', $primerDetalle)
            ->whereHas('cfe', fn($q) => $q->whereNotNull('tes_caja_concepto_id')->whereNull('deleted_at'))
            ->whereNull('deleted_at')
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        if ($ultimosItems->isEmpty()) {
            return null;
        }

        $frecuencias = $ultimosItems->groupBy(fn($item) => $item->cfe->tes_caja_concepto_id)
            ->map->count()
            ->sortDesc();

        return $frecuencias->keys()->first();
    }

    public function updatedCajaConceptoSeleccionado($value): void
    {
        $concepto = CajaConcepto::find($value);
        $this->confirmacionConceptoRequiereInstitucion = $concepto ? $concepto->requiere_institucion : false;

        if (!$this->confirmacionConceptoRequiereInstitucion) {
            $this->confirmacionInstitucionSeleccionada = null;
        }

        $this->resetItemDistribuciones();

        if (!empty($value) && !empty($this->siifDependenciaSeleccionado)) {
            $this->autoAsignarDistribuciones();
        }
    }

    public function updatedSiifDependenciaSeleccionado($value): void
    {
        $this->resetItemDistribuciones();

        if (!empty($value) && !empty($this->cajaConceptoSeleccionado)) {
            $this->autoAsignarDistribuciones();
        }
    }

    private function resetItemDistribuciones(): void
    {
        $this->itemDistribuciones = [];
        if (!empty($this->datosExtraidos['items'])) {
            foreach ($this->datosExtraidos['items'] as $index => $item) {
                $this->itemDistribuciones[$index] = '';
            }
        }
    }

    private function autoAsignarDistribuciones(): void
    {
        if (empty($this->datosExtraidos['items'])) {
            return;
        }

        $this->itemDistribuciones = $this->cfeCreator->autoAsignarDistribuciones(
            $this->cajaConceptoSeleccionado,
            $this->siifDependenciaSeleccionado,
            $this->datosExtraidos['items']
        );
    }

    #[On('confirmar-carga-forzado')]
    public function confirmarCargaForzado(): void
    {
        $this->confirmarCarga(true);
    }

    #[On('confirmar-carga-ignorar-duplicados')]
    public function confirmarCargaIgnorarDuplicados(): void
    {
        $this->confirmarCarga(false, true, false);
    }

    #[On('confirmar-carga-ignorar-concepto')]
    public function confirmarCargaIgnorarConcepto(): void
    {
        $this->confirmarCarga(false, false, true);
    }

    public function confirmarCarga($ignorarAdvertencias = false, bool $ignorarDuplicados = false, bool $ignorarConcepto = false): void
    {
        Log::info('confirmarCarga: llamado', [
            'ignorarAdvertencias' => $ignorarAdvertencias,
            'ignorarDuplicados' => $ignorarDuplicados,
            'ignorarConcepto' => $ignorarConcepto,
            'cajaConceptoSeleccionado' => $this->cajaConceptoSeleccionado,
            'mostrarModal' => $this->mostrarModalConfirmacion,
            'datosExtraidos_keys' => array_keys($this->datosExtraidos ?? []),
        ]);

        $force = (bool) $ignorarAdvertencias;

        if (empty($this->cajaConceptoSeleccionado)) {
            $this->dispatch('swal:toast-error', text: 'Debe seleccionar un concepto de caja antes de confirmar.');
            return;
        }

        $rules = [
            'cajaConceptoSeleccionado' => 'required|integer|min:1|exists:tes_caja_conceptos,id',
            'siifDependenciaSeleccionado' => 'nullable|integer|exists:siif_distribucion_dependencias,id',
        ];

        $cajaConcepto = CajaConcepto::find($this->cajaConceptoSeleccionado);
        $requiereDistribucion = $cajaConcepto ? $cajaConcepto->requiere_distribucion : false;
        $requiereInstitucion = $cajaConcepto ? $cajaConcepto->requiere_institucion : false;

        if ($requiereInstitucion && empty($this->confirmacionInstitucionSeleccionada)) {
            $this->dispatch('swal:toast-error', text: 'El concepto de caja seleccionado requiere seleccionar una institución.');
            return;
        }

        if ($requiereInstitucion) {
            $rules['confirmacionInstitucionSeleccionada'] = 'required|integer|exists:tes_eventuales_instituciones,id';
        } else {
            $rules['confirmacionInstitucionSeleccionada'] = 'nullable|integer|exists:tes_eventuales_instituciones,id';
        }

        if ($requiereDistribucion) {
            $hasMissingDistribution = false;
            if (!empty($this->datosExtraidos['items'])) {
                foreach ($this->datosExtraidos['items'] as $index => $item) {
                    if (empty($this->itemDistribuciones[$index])) {
                        $hasMissingDistribution = true;
                        break;
                    }
                }
            }

            if ($hasMissingDistribution) {
                $this->dispatch('swal:toast-error', text: 'El concepto de caja seleccionado requiere asignar una distribución SIIF a cada uno de los ítems.');
                return;
            }

            $rules['itemDistribuciones.*'] = 'required|integer|exists:siif_distribucions,id';
        } else {
            $rules['itemDistribuciones.*'] = 'nullable|integer|exists:siif_distribucions,id';
        }

        try {
            $this->validate($rules, [
                'cajaConceptoSeleccionado.required' => 'Debe seleccionar un concepto de caja antes de confirmar.',
                'cajaConceptoSeleccionado.min' => 'Debe seleccionar un concepto de caja válido.',
                'cajaConceptoSeleccionado.exists' => 'El concepto de caja seleccionado no existe.',
                'siifDependenciaSeleccionado.exists' => 'La dependencia de distribución SIIF seleccionada no existe.',
                'confirmacionInstitucionSeleccionada.required' => 'Debe seleccionar una institución.',
                'confirmacionInstitucionSeleccionada.exists' => 'La institución seleccionada no existe.',
                'itemDistribuciones.*.required' => 'Debe seleccionar una distribución para todos los ítems.',
                'itemDistribuciones.*.exists' => 'La distribución SIIF seleccionada no existe.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errores = collect($e->errors())->flatten()->first();
            Log::warning('confirmarCarga: validación fallida', $e->errors());
            $this->dispatch('swal:toast-error', text: $errores ?: 'Error de validación al confirmar la carga.');
            return;
        }

        if (!$force) {
            $advertencia = $this->detectarAdvertenciasPrevias(
                (int) $this->cajaConceptoSeleccionado,
                $this->datosExtraidos['items'] ?? [],
                $this->datosExtraidos['medios_pago'] ?? [],
                $this->datosExtraidos['referencias'] ?? '',
                $this->datosExtraidos['adenda'] ?? '',
                $this->itemDistribuciones,
                '',
                $ignorarDuplicados,
                $ignorarConcepto
            );

            if ($advertencia) {
                $this->dispatch($advertencia['evento'], ...$advertencia['parametros']);
                return;
            }
        }

        try {
            $archivoPath = storage_path('app/' . $this->rutaArchivoTemporal);

            $data = new CfeData(
                documento_tipo: $this->datosExtraidos['documento_tipo'] ?? '',
                documento_serie: $this->datosExtraidos['documento_serie'] ?? null,
                documento_numero: $this->datosExtraidos['documento_numero'] ?? '',
                fecha: $this->datosExtraidos['fecha'] ?? null,
                receptor_nombre_denominacion: $this->datosExtraidos['receptor_nombre_denominacion'] ?? null,
                receptor_documento_ruc: $this->datosExtraidos['receptor_documento_ruc'] ?? null,
                tes_caja_concepto_id: $this->cajaConceptoSeleccionado,
                siif_distribucion_dependencia_id: $this->siifDependenciaSeleccionado,
                institucion_id: $this->confirmacionInstitucionSeleccionada,
                items: $this->datosExtraidos['items'] ?? [],
                medios_pago: $this->datosExtraidos['medios_pago'] ?? [],
                item_distribuciones: $this->itemDistribuciones,
                force: $force,
                emisor_nombre: $this->datosExtraidos['emisor_nombre'] ?? null,
                emisor_direccion: $this->datosExtraidos['emisor_direccion'] ?? null,
                emisor_localidad: $this->datosExtraidos['emisor_localidad'] ?? null,
                emisor_telefono: $this->datosExtraidos['emisor_telefono'] ?? null,
                emisor_correo: $this->datosExtraidos['emisor_correo'] ?? null,
                emisor_ruc: $this->datosExtraidos['emisor_ruc'] ?? null,
                forma_pago: $this->datosExtraidos['forma_pago'] ?? null,
                vencimiento: $this->datosExtraidos['vencimiento'] ?? null,
                comprobante_tipo: $this->datosExtraidos['comprobante_tipo'] ?? null,
                receptor_domicilio_fiscal: $this->datosExtraidos['receptor_domicilio_fiscal'] ?? null,
                periodo: $this->datosExtraidos['periodo'] ?? null,
                nro_compra: $this->datosExtraidos['nro_compra'] ?? null,
                moneda: $this->datosExtraidos['moneda'] ?? 'UYU',
                monto_no_facturable: $this->datosExtraidos['monto_no_facturable'] ?? 0,
                monto_total: $this->datosExtraidos['monto_total'] ?? 0,
                referencias: $this->datosExtraidos['referencias'] ?? null,
                adenda: $this->datosExtraidos['adenda'] ?? null,
            );

            $this->cfeCreator->createFromPdf($data, $archivoPath);

            $nombreArchivo = $this->nombreArchivoOriginal;
            $this->cancelarCarga();

            $this->dispatch('swal:toast-success', text: "Archivo {$nombreArchivo} procesado y guardado correctamente.");

        } catch (CfeDuplicateException | CfeValidationException | \InvalidArgumentException $e) {
            $this->dispatch('swal:toast-error', text: $e->getMessage());
        } catch (\Throwable $e) {
            $this->dispatch('swal:modal', type: 'error', title: 'Error al guardar', text: 'Hubo un problema guardando el CFE: ' . $e->getMessage());
        }
    }

    public function cancelarCarga(): void
    {
        $this->mostrarModalConfirmacion = false;
        $this->datosExtraidos = [];
        $this->nombreArchivoOriginal = '';
        $this->rutaArchivoTemporal = '';
        $this->cajaConceptoSeleccionado = null;
        $this->siifDependenciaSeleccionado = 1;
        $this->itemDistribuciones = [];
        $this->confirmacionInstitucionSeleccionada = null;
        $this->confirmacionConceptoRequiereInstitucion = false;
        $this->dispatch('cerrar-modal-confirmacion-cfe');
    }

    private function detectarAdvertenciasPrevias(
        int $cajaConceptoId,
        array $items,
        array $mediosPago,
        string $referencias,
        string $adenda,
        array $itemDistribuciones = [],
        string $sufijoEvento = '',
        bool $ignorarDuplicados = false,
        bool $ignorarConcepto = false
    ): ?array {
        if (!$ignorarDuplicados) {
            $advertenciaDuplicados = $this->detectarAdvertenciaDuplicados($referencias, $adenda, $items, $sufijoEvento);
            if ($advertenciaDuplicados) {
                return $advertenciaDuplicados;
            }
        }

        if (!$ignorarConcepto) {
            $totalAPagar = $this->calcularTotalAPagar($items, $mediosPago);
            $advertenciaConcepto = $this->detectarAdvertenciaConceptoDeCaja($cajaConceptoId, $totalAPagar, $itemDistribuciones, $sufijoEvento);
            if ($advertenciaConcepto) {
                return $advertenciaConcepto;
            }
        }

        return null;
    }

    private function detectarAdvertenciaConceptoDeCaja(int $cajaConceptoId, float $totalAPagar, array $itemDistribuciones = [], string $sufijoEvento = ''): ?array
    {
        if ($totalAPagar <= 0) {
            return null;
        }

        $concepto = CajaConcepto::find($cajaConceptoId);
        $nombreConcepto = $concepto?->caja_concepto ?? '';
        $totalFormateado = '$ ' . number_format($totalAPagar, 2, ',', '.');
        $esSeleccionadoEspecial = $this->esConceptoConValidacionDeMonto($cajaConceptoId, $itemDistribuciones);

        // a) ¿El Total a Pagar ya existe en los últimos 50 registros?
        $ultimosCincuenta = TesCfe::whereNull('deleted_at')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $existeValor = $ultimosCincuenta->contains(fn ($c) => abs((float) $c->total_a_pagar - $totalAPagar) < 0.01);

        if (!$existeValor) {
            // Valor nuevo: sólo se pide confirmación si el concepto seleccionado es uno de los especiales
            if (!$esSeleccionadoEspecial) {
                return null;
            }

            return [
                'evento' => 'swal:confirmar-concepto-nuevo' . $sufijoEvento,
                'parametros' => ['totalAPagar' => $totalFormateado, 'concepto' => $nombreConcepto],
            ];
        }

        // b) En las últimas 50 ocurrencias del valor, ¿a qué concepto se asocia con mayor frecuencia?
        $ocurrenciasValor = TesCfe::whereNull('deleted_at')
            ->where('total_a_pagar', '>=', $totalAPagar - 0.01)
            ->where('total_a_pagar', '<=', $totalAPagar + 0.01)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $frecuenciasConcepto = $ocurrenciasValor
            ->filter(fn ($c) => $c->tes_caja_concepto_id !== null)
            ->groupBy('tes_caja_concepto_id')
            ->map->count()
            ->sortDesc();

        if ($frecuenciasConcepto->isEmpty()) {
            return null;
        }

        $maxOcurrencias = $frecuenciasConcepto->first();
        $ocurrenciasDelSeleccionado = $frecuenciasConcepto->get($cajaConceptoId, 0);

        if ($ocurrenciasDelSeleccionado === $maxOcurrencias) {
            return null;
        }

        $conceptoMasFrecuenteId = $frecuenciasConcepto->keys()->first();
        $conceptoMasFrecuente = CajaConcepto::find($conceptoMasFrecuenteId);
        $esFrecuenteEspecial = $conceptoMasFrecuente ? $this->esConceptoConValidacionDeMonto($conceptoMasFrecuente->id) : false;

        // Advertir sólo si alguno de los dos conceptos (el seleccionado o el más frecuente) es especial
        if (!$esSeleccionadoEspecial && !$esFrecuenteEspecial) {
            return null;
        }

        return [
            'evento' => 'swal:confirmar-concepto-diferente' . $sufijoEvento,
            'parametros' => [
                'totalAPagar' => $totalFormateado,
                'concepto' => $nombreConcepto,
                'conceptoFrecuente' => $conceptoMasFrecuente?->caja_concepto ?? 'Desconocido',
                'cantidad' => $maxOcurrencias,
            ],
        ];
    }

    private function calcularTotalAPagar(array $items, array $mediosPago): float
    {
        $itemsRedondeados = $this->cfeCreator->redondearYCompensarItems($items, $mediosPago);

        if (!empty($mediosPago)) {
            return (float) collect($mediosPago)->sum(fn ($mp) => (float) ($mp['valor'] ?? 0));
        }

        return (float) collect($itemsRedondeados)->sum(fn ($i) => (float) ($i['importe'] ?? 0));
    }

    private function esConceptoConValidacionDeMonto(?int $cajaConceptoId, array $itemDistribuciones = []): bool
    {
        if (!$cajaConceptoId) {
            return false;
        }

        $concepto = CajaConcepto::find($cajaConceptoId);
        if (!$concepto) {
            return false;
        }

        $nombreNorm = TextoHelper::normalizarConcepto($concepto->caja_concepto);

        if ($nombreNorm === TextoHelper::normalizarConcepto('MULTAS DE TRÁNSITO')) {
            return $this->tieneDistribucionSoa($itemDistribuciones);
        }

        foreach (self::CONCEPTOS_CON_VALIDACION_DE_MONTO as $nombre) {
            if ($nombreNorm === TextoHelper::normalizarConcepto($nombre)) {
                return true;
            }
        }

        return false;
    }

    private function tieneDistribucionSoa(array $itemDistribuciones): bool
    {
        $ids = collect($itemDistribuciones)
            ->filter(fn ($id) => !empty($id))
            ->unique();

        if ($ids->isEmpty()) {
            return false;
        }

        $soaNombreNorm = TextoHelper::normalizarConcepto('Multa por circular sin seguro obligatorio automotor (SOA)');

        return SiifDistribucion::whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->get()
            ->contains(function ($d) use ($soaNombreNorm) {
                $conceptoNorm = TextoHelper::normalizarConcepto($d->concepto ?? '');
                $distribucionNorm = TextoHelper::normalizarConcepto($d->distribucion ?? '');

                return $conceptoNorm === $soaNombreNorm || $distribucionNorm === $soaNombreNorm;
            });
    }

    private function detectarAdvertenciaDuplicados(string $referencias, string $adenda, array $items, string $sufijoEvento = ''): ?array
    {
        $texto = $referencias . "\n" . $adenda . "\n";
        foreach ($items as $item) {
            $texto .= ($item['detalle'] ?? '') . "\n";
            $texto .= ($item['descripcion'] ?? '') . "\n";
        }

        $ordenCobro = $this->extraerOrdenCobro($texto);
        if ($ordenCobro !== null) {
            $cfeOcExistente = $this->buscarOrdenCobroDuplicada($ordenCobro);
            if ($cfeOcExistente) {
                $documentoIdentificador = "{$cfeOcExistente->documento_tipo} {$cfeOcExistente->documento_serie}-{$cfeOcExistente->documento_numero}";
                return [
                    'evento' => 'swal:confirmar-orden-cobro-duplicada' . $sufijoEvento,
                    'parametros' => ['ordenCobro' => $ordenCobro, 'documentoExistente' => $documentoIdentificador],
                ];
            }
        }

        $referencia = trim($referencias);
        if ($referencia !== '') {
            $resultado = $this->buscarReferenciaDuplicada($referencia);
            if ($resultado) {
                $documentoIdentificador = "{$resultado['cfe']->documento_tipo} {$resultado['cfe']->documento_serie}-{$resultado['cfe']->documento_numero}";
                return [
                    'evento' => 'swal:confirmar-guardar-referencia-duplicada' . $sufijoEvento,
                    'parametros' => ['documentoReferencia' => $resultado['referencia'], 'documentoExistente' => $documentoIdentificador],
                ];
            }
        }

        return null;
    }

    private function extraerOrdenCobro(string $texto): ?string
    {
        $marcadores = ['O/C', 'O.C.', 'Orden de Cobro', 'Orden Cobro'];

        foreach ($marcadores as $marcador) {
            if (preg_match('/' . preg_quote($marcador, '/') . '\s*(\d+)/iu', $texto, $m)) {
                return $m[1];
            }
        }

        return null;
    }

    private function buscarReferenciaDuplicada(string $referencia): ?array
    {
        if (!preg_match(
            '/(e[- ]?(?:Factura|Ticket|Boleta)(?:[- ]Cobranza)?|Nota[- ]de[- ]Cr[ée]dito)\s*[-–\s]*([A-Z])?\s*[-–\s]*(\d+)\b/iu',
            $referencia,
            $m
        )) {
            return null;
        }

        $refTipo = $m[1];
        $refSerie = !empty($m[2]) ? mb_strtoupper($m[2], 'UTF-8') : null;
        $refNumero = $m[3];
        $tipoNorm = $this->normalizarTipoDoc($refTipo);

        // Buscar por numero en el campo referencias de TesCfe
        $candidatos = TesCfe::where('referencias', 'like', '%' . $refNumero . '%')
            ->whereNull('deleted_at')
            ->get();

        foreach ($candidatos as $cfe) {
            $refCfe = $cfe->referencias ?? '';
            $docTipoNorm = $this->normalizarTipoDoc($cfe->documento_tipo ?? '');

            // Verificar serie: buscar patron "A1167", "A-1167" o "A 1167"
            if ($refSerie && !preg_match('/' . preg_quote($refSerie, '/') . '\s*-?\s*' . $refNumero . '/u', $refCfe)) {
                continue;
            }

            // Verificar tipo: coincidencia flexible (ej. "efactura" ≈ "efacturacobranza")
            if (!str_contains($docTipoNorm, $tipoNorm) && !str_contains($tipoNorm, $docTipoNorm)) {
                continue;
            }

            $refCompleta = $refTipo . ($refSerie ? "-{$refSerie}" : "") . "-{$refNumero}";

            return ['cfe' => $cfe, 'referencia' => $refCompleta];
        }

        return null;
    }

    private function buscarOrdenCobroDuplicada(string $numero): ?TesCfe
    {
        $marcadores = ['O/C', 'O.C.', 'Orden de Cobro', 'Orden Cobro'];
        $busquedas = [];
        foreach ($marcadores as $m) {
            $busquedas[] = "%{$m} {$numero}%";
            $busquedas[] = "%{$m}{$numero}%";
        }

        $itemMatch = TesCfeItem::where(function ($q) use ($busquedas) {
                foreach ($busquedas as $b) {
                    $q->orWhere('detalle', 'like', $b);
                    $q->orWhere('descripcion', 'like', $b);
                }
            })
            ->whereHas('cfe', fn($q) => $q->whereNull('deleted_at'))
            ->first();

        if ($itemMatch) {
            return $itemMatch->cfe;
        }

        return TesCfe::whereNull('deleted_at')
            ->where(function ($q) use ($busquedas) {
                foreach ($busquedas as $b) {
                    $q->orWhere('referencias', 'like', $b);
                    $q->orWhere('adenda', 'like', $b);
                }
            })
            ->first();
    }

    private function normalizarTipoDoc(string $tipo): string
    {
        return preg_replace('/[\s\-]+/', '', TextoHelper::normalizarTexto($tipo));
    }
}
