<?php

namespace App\Services\Tesoreria;

use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
use App\Models\Tesoreria\LibroDiario;
use App\Models\Tesoreria\MedioDePago;
use App\Models\Tesoreria\TesPlanillaEr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegistrarAsientosPorTransferenciaService
{
    private const DENOMINACION_TESORERIA = 'TESORERÍA DE LA JPM';

    public function __construct(
        private readonly LibroDiarioService $libroDiarioService,
    ) {}

    public function registrarAsientoPorTransferencia(TesPlanillaEr $planilla): void
    {
        try {
            DB::transaction(function () use ($planilla) {
                // Obtener el tipo de la planilla (Recaudación Artículo 222 o Recaudación Diaria)
                $tipoDistribucion = $planilla->tipo;
                if (!$tipoDistribucion) {
                    Log::warning('RegistrarAsientosPorTransferenciaService: no se encontró tipo de distribución de planilla', [
                        'planilla_id' => $planilla->id,
                    ]);
                    return;
                }

                // Determinar el concepto y detalle según el tipo de distribución
                $concepto = null;
                $detalle = null;

                if (stripos($tipoDistribucion->tipo, 'Artículo 222') !== false) {
                    $concepto = LbConcepto::recaudacion222();
                    $detalle = LbDetalle::recaudacionesVarias222();
                } elseif (stripos($tipoDistribucion->tipo, 'Recaudación Diaria') !== false || 
                          stripos($tipoDistribucion->tipo, 'Diaria') !== false) {
                    $concepto = LbConcepto::recaudacionDiaria();
                    $detalle = LbDetalle::otrasRecaudacionesVarias();
                } else {
                    Log::warning('RegistrarAsientosPorTransferenciaService: tipo de distribución no reconocido', [
                        'planilla_id' => $planilla->id,
                        'tipo' => $tipoDistribucion->tipo,
                    ]);
                    return;
                }

                // Calcular monto total de la planilla (suma de items)
                $montoTotal = $planilla->items()->sum('importe');

                if ($montoTotal <= 0) {
                    Log::warning('RegistrarAsientosPorTransferenciaService: monto total de planilla es cero o negativo', [
                        'planilla_id' => $planilla->id,
                        'monto' => $montoTotal,
                    ]);
                    return;
                }

                // Obtener fecha de transferencia
                $fechaTransferencia = $planilla->transferencia_fecha;

                if (!$fechaTransferencia) {
                    Log::warning('RegistrarAsientosPorTransferenciaService: transferencia_fecha es null', [
                        'planilla_id' => $planilla->id,
                    ]);
                    return;
                }

                // Obtener lista de CFEs que integran la planilla
                $cfeItems = $planilla->items()
                    ->with('cfe')
                    ->get();

                if ($cfeItems->isEmpty()) {
                    Log::warning('RegistrarAsientosPorTransferenciaService: planilla sin CFEs asociados', [
                        'planilla_id' => $planilla->id,
                    ]);
                    return;
                }

                // Redistribuir cada CFE desde su asiento original hacia el detalle destino
                // Solo aplica para planillas de tipo Recaudación Diaria
                if ($concepto?->nombre === LbConcepto::RECAUDACION_DIARIA) {
                    $this->redistribuirCfeItems(
                        $cfeItems,
                        $fechaTransferencia,
                        $concepto,
                        $detalle,
                        $planilla
                    );
                }

                // Obtener el medio de pago del primer CFE
                $primerCfeItem = $cfeItems->first();
                $primerCfe = $primerCfeItem->cfe;

                if (!$primerCfe) {
                    Log::warning('RegistrarAsientosPorTransferenciaService: primer CFE no encontrado', [
                        'planilla_id' => $planilla->id,
                    ]);
                    return;
                }

                // Obtener el medio de pago del primer CFE
                $medioDePagoCfe = $primerCfe->mediosPago->first();
                $medioId = null;
                $medioNombre = 'Transferencia';

                if ($medioDePagoCfe) {
                    $medioId = $medioDePagoCfe->medio_pago_id;
                    $medioNombre = $medioDePagoCfe->medioPago?->nombre
                        ?? $medioDePagoCfe->medio_pago_tipo;

                    if (!$medioId || !$medioDePagoCfe->medioPago) {
                        $medio = MedioDePago::where('nombre', 'like', "%{$medioNombre}%")
                            ->orWhere('nombre_corto', 'like', "%{$medioNombre}%")
                            ->where('activo', true)
                            ->first();

                        if ($medio) {
                            $medioId = $medio->id;
                        }
                    }
                }

                if (!$medioId) {
                    Log::info('RegistrarAsientosPorTransferenciaService: no se encontró medio de pago específico, usando Transferencia como fallback', [
                        'planilla_id' => $planilla->id,
                        'medio_cfe' => $medioNombre,
                    ]);
                    $medio = MedioDePago::where('nombre', 'like', '%Transferencia%')
                        ->orWhere('nombre_corto', 'like', '%Transferencia%')
                        ->where('activo', true)
                        ->first();

                    if (!$medio) {
                        Log::warning('RegistrarAsientosPorTransferenciaService: no se encontró medio de pago Transferencia', [
                            'planilla_id' => $planilla->id,
                        ]);
                        return;
                    }

                    $medioId = $medio->id;
                    $medioNombre = $medio->nombre;
                }

                // Construir lista de CFEs agrupados por tipo de documento sin repetir serie+número
                $cfesAgrupados = [];
                foreach ($cfeItems as $item) {
                    $cfe = $item->cfe;
                    if (!$cfe) continue;

                    $tipo = $cfe->documento_tipo;
                    $serie = $cfe->documento_serie;
                    $numero = $cfe->documento_numero;

                    // Crear clave única para evitar repetidos
                    $clave = "{$tipo}|" . ($serie ?? '') . "|" . $numero;

                    if (!isset($cfesAgrupados[$clave])) {
                        if (!isset($cfesAgrupados[$tipo])) {
                            $cfesAgrupados[$tipo] = [];
                        }
                        $cfesAgrupados[$tipo][] = [
                            'serie' => $serie,
                            'numero' => $numero,
                        ];
                    }
                }

                // Formatear la lista de CFES: tipo-documento: serie-numero, serie-numero, ... | tipo-documento: serie-numero
                $cfesListados = [];
                foreach ($cfesAgrupados as $tipo => $documentos) {
                    $docsFormateados = [];
                    foreach ($documentos as $doc) {
                        $serie = $doc['serie'] ? "{$doc['serie']}-" : '';
                        $docsFormateados[] = "{$serie}{$doc['numero']}";
                    }
                    $cfesListados[] = "{$tipo}: " . implode(', ', $docsFormateados);
                }

                if (empty($cfesListados)) {
                    Log::warning('RegistrarAsientosPorTransferenciaService: no se pudieron formatear los CFES', [
                        'planilla_id' => $planilla->id,
                    ]);
                    return;
                }

                // Construir descripción con los CFEs agrupados
                $descripcionBase = "SE REALIZÓ LA TRANSFERENCIA CORRESPONDIENTE A LA PLANILLA PARA ESTADO DE RECAUDACIÓN N° {$planilla->numero} --- CFEs: ";
                $cfesListadosStr = implode(' | ', $cfesListados);
                $descripcion = "{$descripcionBase}{$cfesListadosStr}";

                // Calcular saldo actual del flujo (concepto + detalle + medio de pago)
                $saldoActual = $this->libroDiarioService->saldoActualFlujo(
                    $medioId,
                    $concepto->id,
                    $detalle->id
                );

                // Crear asiento de salida con los datos especificados
                // El saldo se calculará automáticamente en registrarAsiento como: saldo_anterior - monto
                $asiento = $this->libroDiarioService->registrarAsiento([
                    'fecha' => $fechaTransferencia->format('Y-m-d'),
                    'tipo_id' => $this->getTipoSalidaId(),
                    'signo_efectivo' => -1,
                    'identidad' => null,
                    'denominacion' => self::DENOMINACION_TESORERIA,
                    'descripcion' => $descripcion,
                    'concepto_id' => $concepto->id,
                    'detalle_id' => $detalle->id,
                    'medio_id' => $medioId,
                    'monto' => $montoTotal,
                ]);

                Log::info('RegistrarAsientosPorTransferenciaService: asiento creado por transferencia de planilla', [
                    'planilla_id' => $planilla->id,
                    'planilla_numero' => $planilla->numero,
                    'asiento_id' => $asiento?->id,
                    'concepto' => $concepto->nombre,
                    'detalle' => $detalle->nombre,
                    'medio' => $medioNombre,
                    'monto' => $montoTotal,
                    'cfes' => $cfesListadosStr,
                    'saldo_anterior' => $saldoActual,
                    'saldo_nuevo' => $saldoActual - $montoTotal,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('RegistrarAsientosPorTransferenciaService: error al crear asiento por transferencia', [
                'planilla_id' => $planilla->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    private function redistribuirCfeItems(
        \Illuminate\Support\Collection $cfeItems,
        \Carbon\Carbon $fechaTransferencia,
        LbConcepto $concepto,
        LbDetalle $detalle,
        TesPlanillaEr $planilla
    ): void
    {
        $itemsPorCfe = $cfeItems->groupBy('tes_cfe_id');

        foreach ($itemsPorCfe as $cfeId => $itemsDelCfe) {
            $montoCfeEnPlanilla = (float) $itemsDelCfe->sum('importe');

            if ($montoCfeEnPlanilla <= 0) {
                continue;
            }

            $asientosOriginales = LibroDiario::where('cfe_id', $cfeId)
                ->where('signo_efectivo', 1)
                ->whereNull('deleted_at')
                ->get();

            if ($asientosOriginales->isEmpty()) {
                Log::warning('RegistrarAsientosPorTransferenciaService: CFE sin asiento original de entrada en Libro Diario', [
                    'cfe_id' => $cfeId,
                    'planilla_id' => $planilla->id,
                ]);
                continue;
            }

            $montoTotalCfe = (float) $asientosOriginales->sum('monto');

            foreach ($asientosOriginales as $asientoOriginal) {
                // Si el detalle original ya es el destino, no hace falta redistribuir
                if ($asientoOriginal->detalle_id === $detalle->id) {
                    continue;
                }

                $proporcion = $montoTotalCfe > 0 ? $asientoOriginal->monto / $montoTotalCfe : 0;
                $montoARedistribuir = round($montoCfeEnPlanilla * $proporcion, 2);

                if ($montoARedistribuir <= 0) {
                    continue;
                }

                try {
                    $this->libroDiarioService->registrarRedistribucion(
                        [
                            'fecha' => $fechaTransferencia->format('Y-m-d'),
                            'concepto_id' => $asientoOriginal->concepto_id,
                            'detalle_id' => $asientoOriginal->detalle_id,
                            'medio_id' => $asientoOriginal->medio_id,
                            'monto' => $montoARedistribuir,
                            'identidad' => $asientoOriginal->identidad,
                            'denominacion' => $asientoOriginal->denominacion,
                        ],
                        [
                            'fecha' => $fechaTransferencia->format('Y-m-d'),
                            'concepto_id' => $concepto->id,
                            'detalle_id' => $detalle->id,
                            'medio_id' => $asientoOriginal->medio_id,
                            'monto' => $montoARedistribuir,
                            'identidad' => $asientoOriginal->identidad,
                            'denominacion' => $asientoOriginal->denominacion,
                        ]
                    );

                    Log::info('RegistrarAsientosPorTransferenciaService: redistribución de CFE completada', [
                        'planilla_id' => $planilla->id,
                        'cfe_id' => $cfeId,
                        'asiento_original_id' => $asientoOriginal->id,
                        'monto' => $montoARedistribuir,
                        'concepto_origen' => $asientoOriginal->concepto_id,
                        'detalle_origen' => $asientoOriginal->detalle_id,
                        'concepto_destino' => $concepto->id,
                        'detalle_destino' => $detalle->id,
                        'medio_id' => $asientoOriginal->medio_id,
                    ]);
                } catch (\DomainException $e) {
                    Log::warning('RegistrarAsientosPorTransferenciaService: saldo insuficiente para redistribuir CFE', [
                        'cfe_id' => $cfeId,
                        'planilla_id' => $planilla->id,
                        'asiento_original_id' => $asientoOriginal->id,
                        'monto_intentado' => $montoARedistribuir,
                        'error' => $e->getMessage(),
                    ]);
                } catch (\Throwable $e) {
                    Log::error('RegistrarAsientosPorTransferenciaService: error al redistribuir CFE', [
                        'cfe_id' => $cfeId,
                        'planilla_id' => $planilla->id,
                        'asiento_original_id' => $asientoOriginal->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    private function getTipoSalidaId(): int
    {
        static $tipoSalidaId = null;

        if ($tipoSalidaId === null) {
            $tipoSalidaId = \App\Models\Tesoreria\LbTipo::where('nombre', 'Salida')->first()->id;
        }

        return $tipoSalidaId;
    }
}
