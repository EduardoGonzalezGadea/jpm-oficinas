<?php

namespace App\Services\Tesoreria;

use App\Helpers\TextoHelper;
use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
use App\Models\Tesoreria\LbTipo;
use App\Models\Tesoreria\LibroDiario;
use App\Models\Tesoreria\MedioDePago;
use App\Models\Tesoreria\SiifDistribucionTipo;
use App\Models\Tesoreria\TesCfe;
use App\Services\Tesoreria\MedioPagoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegistrarAsientosCfeService
{
    private const CONCEPTO_ARTICULO_222 = 'Recaudación Artículo 222';
    private const CONCEPTO_RECAUDACION_DIARIA = 'Recaudación Diaria';
    private const DETALLE_ARTICULO_222 = 'Recaudaciones varias de Artículo 222';
    private const DETALLE_FALLBACK_RECAUDACION_DIARIA = 'Otras recaudaciones varias';
    private const MEDIO_FALLBACK_ID = 3; // Transferencia bancaria

    private ?LbTipo $tipoEntrada = null;
    private ?LbTipo $tipoSalida = null;

    public function __construct(
        private readonly LibroDiarioService $libroDiarioService,
    ) {}

    public function registrarAsientosPorCfeCreado(TesCfe $cfe, array $items, array $mediosPago): void
    {
        try {
            $cajaConcepto = $cfe->cajaConcepto;
            if (!$cajaConcepto || !$cajaConcepto->siif_distribucion_tipo_id) {
                Log::info('RegistrarAsientosCfeService: CFE sin cajaConcepto o sin tipo SIIF, se omite asiento', [
                    'cfe_id' => $cfe->id,
                ]);
                return;
            }

            [$concepto, $detalle] = $this->resolverConceptoDetalle(
                $cajaConcepto->siif_distribucion_tipo_id,
                $cajaConcepto->caja_concepto
            );

            if (!$concepto || !$detalle) {
                Log::warning('RegistrarAsientosCfeService: no se pudo resolver concepto/detalle', [
                    'cfe_id' => $cfe->id,
                    'caja_concepto' => $cajaConcepto->caja_concepto,
                    'tipo_id' => $cajaConcepto->siif_distribucion_tipo_id,
                ]);
                return;
            }

            $tipoEntrada = $this->getTipoEntrada();
            $totalItems = collect($items)->sum(fn($i) => (float)($i['importe'] ?? 0));
            $descripcionBase = $this->formatearDescripcion($cfe, $items, $mediosPago);

            if (empty($mediosPago)) {
                $asiento = $this->crearUnAsiento($cfe, $tipoEntrada->id, +1, $concepto, $detalle, self::MEDIO_FALLBACK_ID, $totalItems, $descripcionBase, false);
                Log::info('RegistrarAsientosCfeService: asiento creado (sin medios, fallback efectivo)', [
                    'cfe_id' => $cfe->id,
                    'asiento_id' => $asiento?->id,
                    'concepto' => $concepto->nombre,
                    'detalle' => $detalle->nombre,
                    'monto' => $totalItems,
                ]);
                return;
            }

            foreach ($mediosPago as $mp) {
                $medio = $this->resolverMedioPago($mp['tipo'] ?? '');
                $medioId = $medio?->id ?? self::MEDIO_FALLBACK_ID;
                $monto = (float)($mp['valor'] ?? 0);
                $asiento = $this->crearUnAsiento($cfe, $tipoEntrada->id, +1, $concepto, $detalle, $medioId, $monto, $descripcionBase, false);
                Log::info('RegistrarAsientosCfeService: asiento creado', [
                    'cfe_id' => $cfe->id,
                    'asiento_id' => $asiento?->id,
                    'concepto' => $concepto->nombre,
                    'detalle' => $detalle->nombre,
                    'medio' => $medio?->nombre ?? 'Efectivo (fallback)',
                    'monto' => $monto,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('RegistrarAsientosCfeService: error inesperado al crear asientos', [
                'cfe_id' => $cfe->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function registrarContraAsientosPorCfeEliminado(TesCfe $cfe): void
    {
        try {
            $asientosOriginales = LibroDiario::where('cfe_id', $cfe->id)
                ->where('es_contra_asiento', false)
                ->get();

            if ($asientosOriginales->isEmpty()) {
                return;
            }

            $tipoSalida = $this->getTipoSalida();
            $serie = $cfe->documento_serie ? "-{$cfe->documento_serie}" : '';
            $docLabel = "{$cfe->documento_tipo}{$serie}-{$cfe->documento_numero}";
            $fechaHoy = now()->format('Y-m-d');

            DB::transaction(function () use ($asientosOriginales, $tipoSalida, $cfe, $docLabel, $fechaHoy) {
                foreach ($asientosOriginales as $original) {
                    $fechaOrig = $original->fecha?->format('d/m/Y') ?? 'desconocida';

                    LibroDiario::create([
                        'fecha' => $fechaHoy,
                        'tipo_id' => $tipoSalida->id,
                        'numero' => LibroDiario::generarNumero(now()->year, -1),
                        'signo_efectivo' => -1,
                        'identidad' => $original->identidad,
                        'denominacion' => "CONTRA-ASIENTO: {$docLabel}",
                        'descripcion' => "Contra-asiento por anulacion de CFE {$docLabel}. Asiento original Nro {$original->numero} del {$fechaOrig}.",
                        'concepto_id' => $original->concepto_id,
                        'detalle_id' => $original->detalle_id,
                        'medio_id' => $original->medio_id,
                        'monto' => $original->monto,
                        'saldo' => 0,
                        'asociar' => $original->id,
                        'cfe_id' => $cfe->id,
                        'es_contra_asiento' => true,
                    ]);

                    $this->libroDiarioService->recalcularSaldosSubcuenta(
                        $original->medio_id,
                        $original->concepto_id,
                        $original->detalle_id
                    );
                }
            });

            Log::info('RegistrarAsientosCfeService: contra-asientos creados', [
                'cfe_id' => $cfe->id,
                'cantidad' => $asientosOriginales->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('RegistrarAsientosCfeService: error al crear contra-asientos', [
                'cfe_id' => $cfe->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function resolverConceptoDetalle(int $siifTipoId, string $cajaConceptoNombre): array
    {
        if ($siifTipoId === SiifDistribucionTipo::where('tipo', self::CONCEPTO_ARTICULO_222)->value('id')) {
            $concepto = LbConcepto::where('nombre', self::CONCEPTO_ARTICULO_222)->first();
            $detalle = LbDetalle::where('concepto_id', $concepto?->id)
                ->where('nombre', self::DETALLE_ARTICULO_222)
                ->first();
            return [$concepto, $detalle];
        }

        if ($siifTipoId === SiifDistribucionTipo::where('tipo', self::CONCEPTO_RECAUDACION_DIARIA)->value('id')) {
            $concepto = LbConcepto::where('nombre', self::CONCEPTO_RECAUDACION_DIARIA)->first();
            if (!$concepto) return [null, null];

            $cajaNorm = TextoHelper::normalizarTexto($cajaConceptoNombre);

            $detalle = LbDetalle::where('concepto_id', $concepto->id)
                ->get()
                ->first(fn(LbDetalle $d) => TextoHelper::normalizarTexto($d->nombre) === $cajaNorm);

            if (!$detalle) {
                $detalle = LbDetalle::where('concepto_id', $concepto->id)
                    ->where('nombre', self::DETALLE_FALLBACK_RECAUDACION_DIARIA)
                    ->first();
            }

            return [$concepto, $detalle];
        }

        return [null, null];
    }

    private function resolverMedioPago(string $tipo): ?MedioDePago
    {
        return app(MedioPagoService::class)->resolverPorTexto($tipo);
    }

    private function crearUnAsiento(
        TesCfe $cfe,
        int $tipoId,
        int $signo,
        LbConcepto $concepto,
        LbDetalle $detalle,
        ?int $medioId,
        float $monto,
        string $descripcion,
        bool $esContraAsiento,
    ): ?LibroDiario {
        $fecha = $cfe->fecha?->format('Y-m-d') ?? now()->format('Y-m-d');
        $serie = $cfe->documento_serie ? "-{$cfe->documento_serie}" : '';

        return $this->libroDiarioService->registrarAsiento([
            'fecha' => $fecha,
            'tipo_id' => $tipoId,
            'signo_efectivo' => $signo,
            'identidad' => $cfe->receptor_documento_ruc ?? '',
            'denominacion' => $cfe->receptor_nombre_denominacion ?? 'CONSUMIDOR FINAL',
            'descripcion' => $descripcion,
            'concepto_id' => $concepto->id,
            'detalle_id' => $detalle->id,
            'medio_id' => $medioId,
            'monto' => $monto,
            'cfe_id' => $cfe->id,
            'es_contra_asiento' => $esContraAsiento,
        ]);
    }

    private function formatearDescripcion(TesCfe $cfe, array $items, array $mediosPago): string
    {
        $serie = $cfe->documento_serie ? "-{$cfe->documento_serie}" : '';
        $docLabel = "{$cfe->documento_tipo}{$serie}-{$cfe->documento_numero}";
        $lineas = [];
        $lineas[] = "Documento: {$docLabel}";
        $lineas[] = "Receptor: " . ($cfe->receptor_nombre_denominacion ?? 'CONSUMIDOR FINAL');

        if (!empty($cfe->referencias)) {
            $lineas[] = "Referencias: {$cfe->referencias}";
        }
        if (!empty($cfe->adenda)) {
            $lineas[] = "Adenda: {$cfe->adenda}";
        }

        $lineas[] = 'Items:';
        foreach ($items as $item) {
            $detalle = $item['detalle'] ?? '';
            $descripcion = $item['descripcion'] ?? '';
            $cantidad = $item['cantidad'] ?? 1;
            $importe = $item['importe'] ?? 0;

            $texto = "  - {$detalle}";
            if (!empty($descripcion)) {
                $texto .= " ({$descripcion})";
            }
            $texto .= " | Cant: {$cantidad} | $ {$importe}";
            $lineas[] = $texto;
        }

        return implode("\n", $lineas);
    }

    private function getTipoEntrada(): LbTipo
    {
        if (!$this->tipoEntrada) {
            $this->tipoEntrada = LbTipo::where('nombre', 'Entrada')->firstOrFail();
        }
        return $this->tipoEntrada;
    }

    private function getTipoSalida(): LbTipo
    {
        if (!$this->tipoSalida) {
            $this->tipoSalida = LbTipo::where('nombre', 'Salida')->firstOrFail();
        }
        return $this->tipoSalida;
    }
}
