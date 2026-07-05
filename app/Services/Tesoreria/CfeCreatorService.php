<?php

namespace App\Services\Tesoreria;

use App\DataTransferObjects\CfeData;
use App\Events\Tesoreria\CfeActualizado;
use App\Events\Tesoreria\CfeCreado;
use App\Events\Tesoreria\CfeEliminado;
use App\Exceptions\Tesoreria\CfeDuplicateException;
use App\Exceptions\Tesoreria\CfeNotFoundException;
use App\Exceptions\Tesoreria\CfeValidationException;
use App\Helpers\TextoHelper;
use App\Models\Tesoreria\CajaConcepto;
use App\Models\Tesoreria\SiifDistribucion;
use App\Models\Tesoreria\TesCfe;
use App\Models\Tesoreria\TesCfeItem;
use App\Models\Tesoreria\TesCfeMedioPago;
use App\Models\TesCfePendiente;
use Illuminate\Support\Facades\DB;

class CfeCreatorService
{
    public function createManual(CfeData $data): TesCfe
    {
        $itemsRedondeados = $this->redondearYCompensarItems($data->items, $data->medios_pago);
        $this->validateTotalsMatch($itemsRedondeados, $data->medios_pago, $data->force ?? false);
        $this->checkDocumentoDuplicado($data->documento_tipo, $data->documento_numero, $data->documento_serie);

        return DB::transaction(function () use ($data, $itemsRedondeados) {
            $totalAPagar = $this->calcularTotal($itemsRedondeados, $data->medios_pago);

            $cfe = TesCfe::create([
                'emisor_nombre' => 'Jefatura de Policía de Montevideo',
                'emisor_ruc' => '214988770019',
                'documento_tipo' => $data->documento_tipo,
                'documento_serie' => $data->documento_serie ?: null,
                'documento_numero' => $data->documento_numero,
                'fecha' => $data->fecha,
                'receptor_nombre_denominacion' => $data->receptor_nombre_denominacion,
                'receptor_documento_ruc' => $data->receptor_documento_ruc ?: null,
                'moneda' => $data->moneda ?? 'UYU',
                'total_a_pagar' => $totalAPagar,
                'referencias' => $data->referencias ?: null,
                'adenda' => $data->adenda ?: null,
                'tes_caja_concepto_id' => $data->tes_caja_concepto_id,
                'siif_distribucion_tipo_id' => $this->getSiifTipoId($data->tes_caja_concepto_id),
                'siif_distribucion_dependencia_id' => $data->siif_distribucion_dependencia_id,
            ]);

            $this->createItems($cfe, $itemsRedondeados, $data->item_distribuciones);
            $this->createMediosPago($cfe, $data->medios_pago);

            CfeCreado::dispatch($cfe, $itemsRedondeados, $data->medios_pago);

            return $cfe;
        });
    }

    public function createFromPdf(CfeData $data, string $rutaArchivoTemporal, ?int $excludePendienteId = null): TesCfe
    {
        $itemsRedondeados = $this->redondearYCompensarItems($data->items, $data->medios_pago);
        $this->validateTotalsMatch($itemsRedondeados, $data->medios_pago, $data->force ?? false);
        $this->checkDocumentoDuplicado($data->documento_tipo, $data->documento_numero, $data->documento_serie, $excludePendienteId);

        return DB::transaction(function () use ($data, $rutaArchivoTemporal, $itemsRedondeados) {
            $cajaConcepto = CajaConcepto::find($data->tes_caja_concepto_id);
            $siifTipoId = $cajaConcepto ? $cajaConcepto->siif_distribucion_tipo_id : null;

            $hayMediosPago = !empty($data->medios_pago);
            $totalAPagar = $hayMediosPago
                ? collect($data->medios_pago)->sum(fn($mp) => (float)($mp['valor'] ?? 0))
                : collect($itemsRedondeados)->sum(fn($i) => (float)($i['importe'] ?? 0));

            $cfe = TesCfe::create([
                'emisor_nombre' => $data->emisor_nombre ?? null,
                'emisor_direccion' => $data->emisor_direccion ?? null,
                'emisor_localidad' => $data->emisor_localidad ?? null,
                'emisor_telefono' => $data->emisor_telefono ?? null,
                'emisor_correo' => $data->emisor_correo ?? null,
                'emisor_ruc' => $data->emisor_ruc ?? null,
                'documento_tipo' => $data->documento_tipo,
                'documento_serie' => $data->documento_serie ?: null,
                'documento_numero' => $data->documento_numero,
                'forma_pago' => $data->forma_pago ?? null,
                'vencimiento' => $data->vencimiento ?? null,
                'comprobante_tipo' => $data->comprobante_tipo ?? null,
                'receptor_documento_ruc' => $data->receptor_documento_ruc ?? null,
                'receptor_nombre_denominacion' => $data->receptor_nombre_denominacion ?? null,
                'receptor_domicilio_fiscal' => $data->receptor_domicilio_fiscal ?? null,
                'periodo' => $data->periodo ?? null,
                'nro_compra' => $data->nro_compra ?? null,
                'fecha' => $data->fecha,
                'moneda' => $data->moneda ?? 'UYU',
                'monto_no_facturable' => $data->monto_no_facturable ?? 0,
                'monto_total' => $data->monto_total ?? 0,
                'total_a_pagar' => $totalAPagar,
                'referencias' => $data->referencias ?? null,
                'adenda' => $data->adenda ?? null,
                'archivo_pdf_path' => $rutaArchivoTemporal,
                'tes_caja_concepto_id' => $data->tes_caja_concepto_id,
                'siif_distribucion_tipo_id' => $siifTipoId,
                'siif_distribucion_dependencia_id' => $data->siif_distribucion_dependencia_id,
            ]);

            $this->appendDetalleFromDescripcionArticulo222($itemsRedondeados, $cajaConcepto);

            $this->createItems($cfe, $itemsRedondeados, $data->item_distribuciones);
            $this->createMediosPago($cfe, $data->medios_pago);

            CfeCreado::dispatch($cfe, $itemsRedondeados, $data->medios_pago);

            return $cfe;
        });
    }

    public function updateCfe(int $cfeId, CfeData $data): TesCfe
    {
        $cfe = TesCfe::with('items')->find($cfeId);

        if (!$cfe) {
            throw CfeNotFoundException::fromId($cfeId);
        }

        $this->assertItemsNotInPlanilla($cfeId);

        return DB::transaction(function () use ($cfe, $data) {
            $cajaConcepto = CajaConcepto::find($data->tes_caja_concepto_id);
            $siifTipoId = $cajaConcepto ? $cajaConcepto->siif_distribucion_tipo_id : null;

            $cfe->update([
                'fecha' => $data->fecha ?: null,
                'tes_caja_concepto_id' => $data->tes_caja_concepto_id,
                'siif_distribucion_tipo_id' => $siifTipoId,
                'siif_distribucion_dependencia_id' => $data->siif_distribucion_dependencia_id,
            ]);

            foreach ($data->items as $index => $item) {
                TesCfeItem::where('id', $item['id'])->update([
                    'siif_distribucion_id' => !empty($data->item_distribuciones[$index]) ? (int) $data->item_distribuciones[$index] : null,
                ]);
            }

            $changes = $cfe->getChanges();
            CfeActualizado::dispatch($cfe->fresh(), $changes);

            return $cfe;
        });
    }

    public function deleteCfe(int $cfeId): void
    {
        $cfe = TesCfe::with('items')->find($cfeId);

        if (!$cfe) {
            throw CfeNotFoundException::fromId($cfeId);
        }

        $this->assertItemsNotInPlanilla($cfeId);

        DB::transaction(function () use ($cfe) {
            $cfe->delete();
            CfeEliminado::dispatch($cfe);
        });
    }

    public function redondearYCompensarItems(array $items, array $mediosPago): array
    {
        $itemsRedondeados = $items;
        $itemsConDecimales = [];

        foreach ($itemsRedondeados as $idx => $item) {
            $valorOriginal = (float)($item['importe'] ?? 0);
            $valorRedondeado = (int) round($valorOriginal);
            $itemsRedondeados[$idx]['importe'] = $valorRedondeado;

            if (fmod($valorOriginal, 1.0) != 0) {
                $itemsConDecimales[] = [
                    'idx' => $idx,
                    'valor_original' => $valorOriginal,
                    'valor_redondeado' => $valorRedondeado,
                ];
            }
        }

        if (!empty($mediosPago) && !empty($itemsConDecimales)) {
            $sumaMedios = collect($mediosPago)->sum(fn($mp) => (float)($mp['valor'] ?? 0));
            $sumaRedondeada = collect($itemsRedondeados)->sum(fn($i) => (float)($i['importe'] ?? 0));
            $diferencia = (int) round($sumaMedios - $sumaRedondeada);

            if ($diferencia != 0) {
                usort($itemsConDecimales, fn($a, $b) => $b['valor_original'] <=> $a['valor_original']);
                $target = $itemsConDecimales[0];
                $itemsRedondeados[$target['idx']]['importe'] = $target['valor_redondeado'] + $diferencia;
            }
        }

        return $itemsRedondeados;
    }

    public function validateTotalsMatch(array $items, array $mediosPago, bool $force = false): void
    {
        if ($force) return;
        if (empty($mediosPago)) return;

        $sumaItems = collect($items)->sum(fn($i) => (float)($i['importe'] ?? 0));
        $sumaMedios = collect($mediosPago)->sum(fn($mp) => (float)($mp['valor'] ?? 0));

        if (abs($sumaItems - $sumaMedios) > 0.01) {
            throw new CfeValidationException(
                "La suma de los ítems (\${$sumaItems}) no coincide con la suma de los medios de pago (\${$sumaMedios})."
            );
        }
    }

    public function assertItemsNotInPlanilla(int $cfeId): void
    {
        $cfe = TesCfe::with('items')->find($cfeId);

        if (!$cfe) {
            throw CfeNotFoundException::fromId($cfeId);
        }

        if ($cfe->items->contains(fn($i) => $i->planilla_er_id !== null)) {
            throw new CfeValidationException('No se puede procesar este CFE porque uno o más de sus ítems ya integran una planilla.');
        }
    }

    public function checkDocumentoDuplicado(string $tipo, string $numero, ?string $serie = null, ?int $excludePendienteId = null): void
    {
        if (empty($tipo) || empty($numero)) return;

        $refCompleta = $tipo . ($serie ? "-{$serie}" : "") . "-{$numero}";

        $existente = TesCfe::where('documento_tipo', $tipo)
            ->where('documento_numero', $numero)
            ->where(function ($q) use ($serie) {
                if ($serie !== null) {
                    $q->where('documento_serie', $serie);
                } else {
                    $q->whereNull('documento_serie');
                }
            })
            ->whereNull('deleted_at')
            ->first();

        if ($existente) {
            throw CfeDuplicateException::fromDocumento($tipo, $numero, $serie);
        }

        $pendiente = TesCfePendiente::where('numero', $numero)
            ->where(function ($q) use ($serie) {
                if ($serie !== null) {
                    $q->where('serie', $serie);
                } else {
                    $q->whereNull('serie');
                }
            })
            ->whereIn('estado', ['pendiente', 'en_revision'])
            ->whereNull('deleted_at')
            ->when($excludePendienteId !== null, fn($q) => $q->where('id', '!=', $excludePendienteId))
            ->first();

        if ($pendiente) {
            throw CfeDuplicateException::fromPendiente($pendiente->numero, $pendiente->serie, $pendiente->estado);
        }
    }

    private function calcularTotal(array $items, array $mediosPago): float
    {
        if (!empty($mediosPago)) {
            return collect($mediosPago)->sum(fn($mp) => (float)($mp['valor'] ?? 0));
        }
        return collect($items)->sum(fn($i) => (float)($i['importe'] ?? 0));
    }

    public function autoAsignarDistribuciones(int $cajaConceptoId, int $dependenciaId, array $items): array
    {
        $concepto = CajaConcepto::find($cajaConceptoId);
        if (!$concepto || !$concepto->siif_distribucion_tipo_id) {
            return [];
        }

        $conceptoNorm = TextoHelper::normalizarTexto($concepto->caja_concepto);

        $distribucionPorConcepto = SiifDistribucion::where('tipo_id', $concepto->siif_distribucion_tipo_id)
            ->where('dependencia_id', $dependenciaId)
            ->whereNull('deleted_at')
            ->get()
            ->first(fn($d) => TextoHelper::normalizarTexto($d->concepto ?? '') === $conceptoNorm);

        $distribuciones = [];

        foreach ($items as $index => $item) {
            $detalle = trim($item['detalle'] ?? '');
            if (empty($detalle)) {
                continue;
            }

            $ultimosItems = TesCfeItem::where('detalle', $detalle)
                ->whereNotNull('siif_distribucion_id')
                ->whereNull('deleted_at')
                ->orderBy('id', 'desc')
                ->take(10)
                ->get();

            if ($ultimosItems->isNotEmpty()) {
                $frecuencias = $ultimosItems->groupBy('siif_distribucion_id')
                    ->map->count()
                    ->sortDesc();

                $distribucionId = $frecuencias->keys()->first();

                $existe = SiifDistribucion::where('id', $distribucionId)
                    ->where('tipo_id', $concepto->siif_distribucion_tipo_id)
                    ->where('dependencia_id', $dependenciaId)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($existe) {
                    $distribuciones[$index] = (string) $distribucionId;
                    continue;
                }
            }

            if ($distribucionPorConcepto) {
                $distribuciones[$index] = (string) $distribucionPorConcepto->id;
            }
        }

        return $distribuciones;
    }

    private function getSiifTipoId(?int $cajaConceptoId): ?int
    {
        if (!$cajaConceptoId) return null;
        $concepto = CajaConcepto::find($cajaConceptoId);
        return $concepto ? $concepto->siif_distribucion_tipo_id : null;
    }

    private function createItems(TesCfe $cfe, array $items, array $distribuciones): void
    {
        foreach ($items as $index => $item) {
            TesCfeItem::create([
                'tes_cfe_id' => $cfe->id,
                'detalle' => $item['detalle'] ?? '',
                'descripcion' => $item['descripcion'] ?? null,
                'cantidad' => $item['cantidad'] ?? 1,
                'precio' => $item['precio'] ?? 0,
                'descuento' => $item['descuento'] ?? 0,
                'recargo' => $item['recargo'] ?? 0,
                'importe' => $item['importe'] ?? 0,
                'siif_distribucion_id' => !empty($distribuciones[$index]) ? (int) $distribuciones[$index] : null,
            ]);
        }
    }

    private function createMediosPago(TesCfe $cfe, array $mediosPago): void
    {
        foreach ($mediosPago as $mp) {
            TesCfeMedioPago::create([
                'tes_cfe_id' => $cfe->id,
                'medio_pago_tipo' => $mp['tipo'] ?: 'Desconocido',
                'medio_pago_valor' => (float)($mp['valor'] ?? 0),
            ]);
        }
    }

    private function appendDetalleFromDescripcionArticulo222(array &$items, ?CajaConcepto $cajaConcepto): void
    {
        if (!$cajaConcepto) return;

        if (TextoHelper::normalizarTexto($cajaConcepto->caja_concepto) === TextoHelper::normalizarTexto('ARTÍCULO 222')) {
            foreach ($items as $idx => $item) {
                $detalle = trim($item['detalle'] ?? '');
                $descripcion = trim($item['descripcion'] ?? '');
                if ($descripcion !== '' && $detalle !== '') {
                    $items[$idx]['detalle'] = $detalle . ' ' . $descripcion;
                    $items[$idx]['descripcion'] = '';
                }
            }
        }
    }
}
