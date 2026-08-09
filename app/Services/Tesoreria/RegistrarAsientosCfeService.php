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
use App\Models\Tesoreria\Cajas\CajaApertura;
use App\Models\Tesoreria\Cajas\CajaMovimiento;
use App\Services\Tesoreria\MedioPagoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class RegistrarAsientosCfeService
{
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
                $asiento = $this->crearUnAsiento($cfe, $tipoEntrada->id, +1, $concepto, $detalle, $this->getMedioFallback(), $totalItems, $descripcionBase, false);
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
                $medioId = $mp['medio_pago_id'] ?? $this->resolverMedioPago($mp['tipo'] ?? '')?->id ?? $this->getMedioFallback();
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

    public function registrarAsientosPorCfeActualizado(TesCfe $cfe): void
    {
        try {
            // 1. Verificar que el CFE no esté en planilla
            $this->assertCfeNotInPlanilla($cfe);

            // 2. Obtener los asientos originales (no contra-asientos)
            $asientosOriginales = LibroDiario::where('cfe_id', $cfe->id)
                ->where('es_contra_asiento', false)
                ->get();

            // Si no hay asientos previos, no hay nada que actualizar
            if ($asientosOriginales->isEmpty()) {
                Log::info('RegistrarAsientosCfeService: CFE actualizado sin asientos previos, no se procesa', [
                    'cfe_id' => $cfe->id,
                ]);
                return;
            }

            // 3. Crear contra-asientos para anular los asientos antiguos
            $tipoSalida = $this->getTipoSalida();
            $serie = $cfe->documento_serie ? "-{$cfe->documento_serie}" : '';
            $docLabel = "{$cfe->documento_tipo}{$serie}-{$cfe->documento_numero}";
            $fechaHoy = now()->format('Y-m-d');

            DB::transaction(function () use ($cfe, $asientosOriginales, $tipoSalida, $docLabel, $fechaHoy) {
                // Crear contra-asientos
                foreach ($asientosOriginales as $original) {
                    $fechaOrig = $original->fecha?->format('d/m/Y') ?? 'desconocida';

                    LibroDiario::create([
                        'fecha' => $fechaHoy,
                        'tipo_id' => $tipoSalida->id,
                        'numero' => LibroDiario::generarNumero(now()->year, -1),
                        'signo_efectivo' => -1,
                        'identidad' => $original->identidad,
                        'denominacion' => "CONTRA-ASIENTO: {$docLabel} (ACTUALIZACIÓN)",
                        'descripcion' => "Contra-asiento por actualización de CFE {$docLabel}. Asiento original Nro {$original->numero} del {$fechaOrig}.",
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

                // 4. Cargar items y medios de pago actualizados del CFE
                $cfeActualizado = TesCfe::with(['items', 'mediosPago'])->find($cfe->id);
                
                $items = $cfeActualizado->items->map(fn($item) => [
                    'detalle' => $item->detalle,
                    'descripcion' => $item->descripcion,
                    'cantidad' => $item->cantidad,
                    'importe' => $item->importe,
                ])->toArray();

                $mediosPago = $cfeActualizado->mediosPago->map(fn($mp) => [
                    'tipo' => $mp->medio_pago_tipo,
                    'valor' => $mp->medio_pago_valor,
                    'medio_pago_id' => $mp->medio_pago_id,
                ])->toArray();

                // 5. Registrar nuevos asientos con los datos actualizados
                $this->registrarAsientosPorCfeCreado($cfeActualizado, $items, $mediosPago);
            });

            Log::info('RegistrarAsientosCfeService: asientos actualizados por cambio en CFE', [
                'cfe_id' => $cfe->id,
                'contra_asientos_creados' => $asientosOriginales->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('RegistrarAsientosCfeService: error al actualizar asientos por CFE actualizado', [
                'cfe_id' => $cfe->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function resolverConceptoDetalle(int $siifTipoId, string $cajaConceptoNombre): array
    {
        // Artículo 222
        $tipoArticulo222 = SiifDistribucionTipo::where('tipo', 'Recaudación Artículo 222')->value('id');
        if ($siifTipoId === $tipoArticulo222) {
            try {
                $concepto = LbConcepto::recaudacion222();
                $detalle = LbDetalle::recaudacionesVarias222();
                return [$concepto, $detalle];
            } catch (\Throwable $e) {
                Log::warning('RegistrarAsientosCfeService: no se pudo obtener concepto/detalle para Artículo 222', [
                    'error' => $e->getMessage(),
                ]);
                return [null, null];
            }
        }

        // Recaudación Diaria
        $tipoRecaudacionDiaria = SiifDistribucionTipo::where('tipo', 'Recaudación Diaria')->value('id');
        if ($siifTipoId === $tipoRecaudacionDiaria) {
            try {
                $concepto = LbConcepto::recaudacionDiaria();
            } catch (\Throwable $e) {
                Log::warning('RegistrarAsientosCfeService: no se pudo obtener concepto para Recaudación Diaria', [
                    'error' => $e->getMessage(),
                ]);
                return [null, null];
            }

            $cajaNorm = TextoHelper::normalizarTexto($cajaConceptoNombre);

            // Buscar detalle por nombre normalizado
            $detalle = LbDetalle::where('concepto_id', $concepto->id)
                ->get()
                ->first(fn(LbDetalle $d) => TextoHelper::normalizarTexto($d->nombre) === $cajaNorm);

            // Si no se encuentra, usar el detalle fallback
            if (!$detalle) {
                try {
                    $detalle = LbDetalle::otrasRecaudacionesVarias();
                } catch (\Throwable $e) {
                    Log::warning('RegistrarAsientosCfeService: no se pudo obtener detalle fallback para Recaudación Diaria', [
                        'error' => $e->getMessage(),
                    ]);
                    return [null, null];
                }
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

        $asiento = $this->libroDiarioService->registrarAsiento([
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

        // Sincronización: Si el medio es Efectivo y hay una caja abierta para el cajero
        // en la fecha del CFE, crear movimiento de caja vinculado al asiento.
        if ($medioId && !$esContraAsiento) {
            $medioEfectivoId = MedioDePago::where('nombre', MedioDePago::EFECTIVO)->value('id');
            if ((int)$medioId === (int)$medioEfectivoId) {
                $cajeroId = $cfe->created_by ?? (Auth::check() ? Auth::id() : null);
                if ($cajeroId) {
                    $cajaAbierta = CajaApertura::abiertas()
                        ->porCajero($cajeroId)
                        ->whereDate('fecha_apertura', $cfe->fecha ?? now()->format('Y-m-d'))
                        ->first();

                    if ($cajaAbierta) {
                        CajaMovimiento::create([
                            'caja_apertura_id' => $cajaAbierta->id,
                            'tipo_movimiento' => $signo === 1 ? 'INGRESO' : 'EGRESO',
                            'monto' => $monto,
                            'medio_pago_id' => $medioId,
                            'libro_diario_id' => $asiento->id,
                            'cfe_id' => $cfe->id,
                            'concepto' => 'Recaudación: ' . ($cfe->documento_tipo ?? 'CFE') . ' ' . ($cfe->documento_numero ?? ''),
                            'descripcion' => $descripcion,
                            'created_by' => $cajeroId,
                        ]);
                    }
                }
            }
        }

        return $asiento;
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

    private function getMedioFallback(): int
    {
        try {
            return MedioDePago::transferencia()->id;
        } catch (\Throwable $e) {
            Log::warning('RegistrarAsientosCfeService: no se pudo obtener medio de pago Transferencia, usando ID 3 como fallback', [
                'error' => $e->getMessage(),
            ]);
            return 3; // Fallback en caso de error
        }
    }

    private function assertCfeNotInPlanilla(TesCfe $cfe): void
    {
        $cfeConItems = TesCfe::withCount([
            'items as items_en_planilla_count' => fn($q) => $q->whereNotNull('planilla_er_id')
        ])->find($cfe->id);

        if ($cfeConItems && $cfeConItems->items_en_planilla_count > 0) {
            throw new \RuntimeException(
                'No se pueden modificar los asientos del CFE porque uno o más de sus ítems ya integran una Planilla para Estado de Recaudación.'
            );
        }
    }
}
