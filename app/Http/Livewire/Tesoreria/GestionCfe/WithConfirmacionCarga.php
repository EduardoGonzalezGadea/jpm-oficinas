<?php

namespace App\Http\Livewire\Tesoreria\GestionCfe;

use App\DataTransferObjects\CfeData;
use App\Exceptions\Tesoreria\CfeDuplicateException;
use App\Exceptions\Tesoreria\CfeValidationException;
use App\Helpers\TextoHelper;
use App\Models\Tesoreria\CajaConcepto;
use App\Models\Tesoreria\TesCfe;
use App\Models\Tesoreria\TesCfeItem;
use App\Models\Tesoreria\SiifDistribucion;
use App\Services\Tesoreria\CfeUniversalParserService;
use Illuminate\Support\Facades\Log;

trait WithConfirmacionCarga
{
    public bool $mostrarModalConfirmacion = false;
    public array $datosExtraidos = [];
    public string $nombreArchivoOriginal = '';
    public string $rutaArchivoTemporal = '';

    public function updatedArchivoPdf(): void
    {
        $this->validate([
            'archivoPdf' => 'required|mimes:pdf|max:5120',
        ]);

        try {
            $parser = app(CfeUniversalParserService::class);
            $datos = $parser->parsePdf($this->archivoPdf->getRealPath());
            $nombreOriginal = $this->archivoPdf->getClientOriginalName();

            $path = $this->archivoPdf->storeAs('cfes_cargados', time() . '_' . $nombreOriginal, 'local');

            $this->datosExtraidos = $datos;
            $this->nombreArchivoOriginal = $nombreOriginal;
            $this->rutaArchivoTemporal = $path;

            $this->cajaConceptoSeleccionado = $this->detectarConceptoAutomatico($datos);

            $this->resetItemDistribuciones();

            if ($this->cajaConceptoSeleccionado && $this->siifDependenciaSeleccionado) {
                $this->autoAsignarDistribuciones();
            }

            $this->mostrarModalConfirmacion = true;
            $this->dispatchBrowserEvent('abrir-modal-confirmacion-cfe');

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'error',
                'title' => 'Error al procesar',
                'text' => 'Hubo un problema procesando el archivo: ' . $e->getMessage(),
            ]);
        }

        $this->reset('archivoPdf');
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

    public function confirmarCarga(bool $force = false): void
    {
        if (empty($this->cajaConceptoSeleccionado)) {
            $this->dispatchBrowserEvent('swal:toast-error', [
                'text' => 'Debe seleccionar un concepto de caja antes de confirmar.',
            ]);
            return;
        }

        $rules = [
            'cajaConceptoSeleccionado' => 'required|integer|min:1|exists:tes_caja_conceptos,id',
            'siifDependenciaSeleccionado' => 'nullable|integer|exists:siif_distribucion_dependencias,id',
        ];

        $cajaConcepto = CajaConcepto::find($this->cajaConceptoSeleccionado);
        $requiereDistribucion = $cajaConcepto ? $cajaConcepto->requiere_distribucion : false;

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
                $this->dispatchBrowserEvent('swal:toast-error', [
                    'text' => 'El concepto de caja seleccionado requiere asignar una distribución SIIF a cada uno de los ítems.',
                ]);
                return;
            }

            $rules['itemDistribuciones.*'] = 'required|integer|exists:siif_distribucions,id';
        } else {
            $rules['itemDistribuciones.*'] = 'nullable|integer|exists:siif_distribucions,id';
        }

        $this->validate($rules, [
            'cajaConceptoSeleccionado.required' => 'Debe seleccionar un concepto de caja antes de confirmar.',
            'cajaConceptoSeleccionado.min' => 'Debe seleccionar un concepto de caja válido.',
            'cajaConceptoSeleccionado.exists' => 'El concepto de caja seleccionado no existe.',
            'siifDependenciaSeleccionado.exists' => 'La dependencia de distribución SIIF seleccionada no existe.',
            'itemDistribuciones.*.required' => 'Debe seleccionar una distribución para todos los ítems.',
            'itemDistribuciones.*.exists' => 'La distribución SIIF seleccionada no existe.',
        ]);

        if (!$force) {
            $ordenCobro = $this->extraerOrdenCobro();
            if ($ordenCobro !== null) {
                $cfeOcExistente = $this->buscarOrdenCobroDuplicada($ordenCobro);
                if ($cfeOcExistente) {
                    $documentoIdentificador = "{$cfeOcExistente->documento_tipo} {$cfeOcExistente->documento_serie}-{$cfeOcExistente->documento_numero}";
                    $this->dispatchBrowserEvent('swal:confirmar-orden-cobro-duplicada', [
                        'ordenCobro' => $ordenCobro,
                        'documentoExistente' => $documentoIdentificador,
                    ]);
                    return;
                }
            }
        }

        $referencia = trim($this->datosExtraidos['referencias'] ?? '');
        if (!$force && $referencia !== '') {
            $refCompleta = '';
            $cfeExistente = null;

            if (preg_match(
                '/(e[- ]?(?:Factura|Ticket|Boleta)(?:[- ]Cobranza)?|Nota[- ]de[- ]Cr[ée]dito)\s*[-–\s]*([A-Z])?\s*[-–\s]*(\d+)\b/iu',
                $referencia,
                $m
            )) {
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

                    $cfeExistente = $cfe;
                    break;
                }

                if ($cfeExistente) {
                    $refCompleta = $refTipo . ($refSerie ? "-{$refSerie}" : "") . "-{$refNumero}";
                }
            }

            if ($cfeExistente) {
                $documentoIdentificador = "{$cfeExistente->documento_tipo} {$cfeExistente->documento_serie}-{$cfeExistente->documento_numero}";
                $this->dispatchBrowserEvent('swal:confirmar-guardar-referencia-duplicada', [
                    'documentoReferencia' => $refCompleta,
                    'documentoExistente' => $documentoIdentificador,
                ]);
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

            $this->cancelarCarga();

            $this->dispatchBrowserEvent('swal:toast-success', [
                'text' => "Archivo {$this->nombreArchivoOriginal} procesado y guardado correctamente.",
            ]);

        } catch (CfeDuplicateException | CfeValidationException | \InvalidArgumentException $e) {
            $this->dispatchBrowserEvent('swal:toast-error', [
                'text' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'error',
                'title' => 'Error al guardar',
                'text' => 'Hubo un problema guardando el CFE: ' . $e->getMessage(),
            ]);
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
        $this->dispatchBrowserEvent('cerrar-modal-confirmacion-cfe');
    }

    private function extraerOrdenCobro(): ?string
    {
        $marcadores = ['O/C', 'O.C.', 'Orden de Cobro', 'Orden Cobro'];

        $texto = '';
        foreach (['referencias', 'adenda'] as $c) {
            $texto .= ($this->datosExtraidos[$c] ?? '') . "\n";
        }
        foreach ($this->datosExtraidos['items'] ?? [] as $item) {
            $texto .= ($item['detalle'] ?? '') . "\n";
            $texto .= ($item['descripcion'] ?? '') . "\n";
        }

        foreach ($marcadores as $marcador) {
            if (preg_match('/' . preg_quote($marcador, '/') . '\s*(\d+)/iu', $texto, $m)) {
                return $m[1];
            }
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
