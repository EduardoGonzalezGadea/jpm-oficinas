<?php

namespace App\Services\Tesoreria;

use App\Models\Tesoreria\CajaChica;
use App\Models\Tesoreria\Pendiente;
use App\Models\Tesoreria\Movimiento;
use App\Models\Tesoreria\Pago;
use App\Models\Tesoreria\Dependencia;
use App\Services\Tesoreria\CajaChicaAsientosService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CajaChicaService
{
    /**
     * Obtiene la Caja Chica según mes y año.
     */
    public function obtenerCajaChica(string $mesActual, int $anioActual): ?CajaChica
    {
        return CajaChica::where(function ($query) use ($mesActual) {
            $query->where('mes', $mesActual)
                  ->orWhere('mes', ucfirst($mesActual))
                  ->orWhere('mes', strtolower($mesActual));
        })
        ->where('anio', $anioActual)
        ->first();
    }

    /**
     * Obtiene los Pendientes (Del mes actual y anteriores con saldo).
     */
    public function obtenerPendientes(?CajaChica $cajaChica, string $mesActual, int $anioActual, string $fechaHastaStr, string $searchPendientes = ''): Collection
    {
        if (!$cajaChica) {
            return collect();
        }

        $meses = [
            'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
            'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
            'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
        ];
        $mesNo = $meses[strtolower($mesActual)] ?? now()->month;
        $primerDiaMesActual = Carbon::create($anioActual, $mesNo, 1)->startOfMonth();

        // 1. Pendientes del MES SELECCIONADO
        $pendientesActual = Pendiente::where('relCajaChica', $cajaChica->idCajaChica)
            ->where('fechaPendientes', '<=', $fechaHastaStr)
            ->with('dependencia')
            ->selectRaw(
                'tes_cch_pendientes.*,
                (SELECT SUM(rendido) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_rendido,
                (SELECT SUM(reintegrado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_reintegrado,
                (SELECT SUM(recuperado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_recuperado,
                (SELECT COUNT(*) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL AND rendido > 0 AND (documentos IS NULL OR TRIM(documentos) = \'\')) as count_undoc,
                (SELECT COUNT(*) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND deleted_at IS NULL) as cant_movimientos',
                [$fechaHastaStr, $fechaHastaStr, $fechaHastaStr, $fechaHastaStr]
            )
            ->get();

        $pendientesActual = $pendientesActual->map(function ($p) {
            $p->tot_rendido = $p->tot_rendido ?? 0;
            $p->tot_reintegrado = $p->tot_reintegrado ?? 0;
            $p->tot_recuperado = $p->tot_recuperado ?? 0;
            $totalMovs = $p->tot_rendido + $p->tot_reintegrado;

            $diferencia = $totalMovs > 0 ? $totalMovs - $p->montoPendientes : 0;
            $p->extra = $diferencia > 0 ? $diferencia : 0;

            if (round($totalMovs, 2) > round($p->montoPendientes, 2)) {
                $p->saldo = $totalMovs - $p->tot_recuperado;
            } else {
                $p->saldo = $p->montoPendientes - $p->tot_reintegrado - $p->tot_recuperado;
            }
            $p->saldo = max(0, $p->saldo);
            $p->rendido_sin_docs_calc = (($p->count_undoc ?? 0) > 0) ? ($p->tot_rendido - $p->tot_recuperado) : 0;
            $p->extra_pendiente = ($p->extra > 0) ? max(0, min($p->extra, $p->tot_rendido - $p->tot_recuperado)) : 0;
            $p->es_mes_anterior = false;
            return $p;
        });

        // 2. Pendientes del MES ANTERIOR
        $inicioMesAnterior = (clone $primerDiaMesActual)->subMonth()->startOfMonth();
        $pendientesAnteriores = Pendiente::where('fechaPendientes', '>=', $inicioMesAnterior->toDateTimeString())
            ->where('fechaPendientes', '<', $primerDiaMesActual->toDateTimeString())
            ->where('fechaPendientes', '<=', $fechaHastaStr)
            ->with('dependencia')
            ->selectRaw(
                'tes_cch_pendientes.*,
                (SELECT SUM(rendido) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_rendido,
                (SELECT SUM(reintegrado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_reintegrado,
                (SELECT SUM(recuperado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_recuperado,
                (SELECT COUNT(*) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL AND rendido > 0 AND (documentos IS NULL OR TRIM(documentos) = \'\')) as count_undoc,
                (SELECT COUNT(*) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND deleted_at IS NULL) as cant_movimientos',
                [$fechaHastaStr, $fechaHastaStr, $fechaHastaStr, $fechaHastaStr]
            )
            ->get();

        $idActuales = $pendientesActual->pluck('idPendientes')->toArray();
        $pendientesAnteriores = $pendientesAnteriores->filter(function ($p) use ($idActuales) {
            return !in_array($p->idPendientes, $idActuales);
        })->map(function ($p) {
            $p->tot_rendido = $p->tot_rendido ?? 0;
            $p->tot_reintegrado = $p->tot_reintegrado ?? 0;
            $p->tot_recuperado = $p->tot_recuperado ?? 0;
            $totalMovs = $p->tot_rendido + $p->tot_reintegrado;

            $diferencia = $totalMovs > 0 ? $totalMovs - $p->montoPendientes : 0;
            $p->extra = $diferencia > 0 ? $diferencia : 0;

            if (round($totalMovs, 2) > round($p->montoPendientes, 2)) {
                $p->saldo = $totalMovs - $p->tot_recuperado;
            } else {
                $p->saldo = $p->montoPendientes - $p->tot_reintegrado - $p->tot_recuperado;
            }
            $p->saldo = max(0, $p->saldo);
            $p->rendido_sin_docs_calc = (($p->count_undoc ?? 0) > 0) ? ($p->tot_rendido - $p->tot_recuperado) : 0;
            $p->extra_pendiente = ($p->extra > 0) ? max(0, min($p->extra, $p->tot_rendido - $p->tot_recuperado)) : 0;
            $p->es_mes_anterior = true;
            return $p;
        })->filter(function ($p) {
            return round($p->saldo, 2) > 0;
        });

        $allPendientes = $pendientesActual->concat($pendientesAnteriores)->sortBy('pendiente')->values();

        if (!empty($searchPendientes)) {
            $search = mb_strtolower($searchPendientes, 'UTF-8');
            $allPendientes = $allPendientes->filter(function ($p) use ($search) {
                $numero = mb_strtolower((string)$p->pendiente, 'UTF-8');
                $dependencia = mb_strtolower($p->dependencia->dependencia ?? '', 'UTF-8');
                $monto = number_format($p->montoPendientes, 2, ',', '.');
                return str_contains($numero, $search) || str_contains($dependencia, $search) || str_contains($monto, $search);
            })->values();
        }

        return $allPendientes->map(function ($item) {
            $arr = $item->toArray();
            $arr['saldo'] = $item->saldo;
            $arr['extra'] = $item->extra;
            $arr['extra_pendiente'] = $item->extra_pendiente;
            $arr['tot_rendido'] = $item->tot_rendido;
            $arr['tot_reintegrado'] = $item->tot_reintegrado;
            $arr['tot_recuperado'] = $item->tot_recuperado;
            $arr['rendido_sin_docs_calc'] = $item->rendido_sin_docs_calc;
            $arr['es_mes_anterior'] = $item->es_mes_anterior;
            $arr['dependencia'] = is_object($arr['dependencia'] ?? null) ? (array) $arr['dependencia'] : ($arr['dependencia'] ?? ['dependencia' => '']);
            $arr['fecha_formateada'] = $item->fechaPendientes ? Carbon::parse($item->fechaPendientes)->format('d/m/Y') : '';
            return $arr;
        });
    }

    /**
     * Obtiene los Pagos Directos.
     */
    public function obtenerPagos(?CajaChica $cajaChica, string $mesActual, int $anioActual, string $fechaHastaStr, string $searchPagos = ''): Collection
    {
        if (!$cajaChica) {
            return collect();
        }

        $meses = [
            'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
            'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
            'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
        ];
        $mesNo = $meses[strtolower($mesActual)] ?? now()->month;
        $primerDiaMesActual = Carbon::create($anioActual, $mesNo, 1)->startOfMonth();

        $pagosActual = Pago::where('relCajaChica_Pagos', $cajaChica->idCajaChica)
            ->where('fechaEgresoPagos', '<=', $fechaHastaStr)
            ->with('acreedor')
            ->selectRaw(
                'tes_cch_pagos.*,
                CASE WHEN (CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN COALESCE(recuperadoPagos, 0) ELSE 0 END) >= montoPagos AND montoPagos > 0 THEN 0 ELSE
                (CASE 
                    WHEN (rendidoPagos IS NULL AND reintegradoPagos IS NULL) 
                         OR (fechaRendicionPagos IS NOT NULL AND fechaRendicionPagos > ?) 
                        THEN montoPagos 
                    ELSE COALESCE(rendidoPagos, 0) 
                END - CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN COALESCE(recuperadoPagos, 0) ELSE 0 END)
                END as saldo_pagos,
                CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN COALESCE(recuperadoPagos, 0) ELSE 0 END as recuperado_en_periodo,
                CASE 
                    WHEN (rendidoPagos IS NULL AND reintegradoPagos IS NULL) 
                         OR (fechaRendicionPagos IS NOT NULL AND fechaRendicionPagos > ?) 
                        THEN NULL 
                    ELSE rendidoPagos 
                END as rendido_en_periodo,
                CASE 
                    WHEN (rendidoPagos IS NULL AND reintegradoPagos IS NULL) 
                         OR (fechaRendicionPagos IS NOT NULL AND fechaRendicionPagos > ?) 
                        THEN NULL 
                    ELSE reintegradoPagos 
                END as reintegrado_en_periodo',
                [$fechaHastaStr, $fechaHastaStr, $fechaHastaStr, $fechaHastaStr, $fechaHastaStr, $fechaHastaStr]
            )
            ->orderBy('fechaEgresoPagos', 'ASC')
            ->get();

        $pagosActual = $pagosActual->map(function ($p) {
            $p->es_mes_anterior = false;
            return $p;
        });

        $inicioMesAnterior = (clone $primerDiaMesActual)->subMonth()->startOfMonth();
        $pagosAnteriores = Pago::where('fechaEgresoPagos', '>=', $inicioMesAnterior->toDateString())
            ->where('fechaEgresoPagos', '<', $primerDiaMesActual->toDateString())
            ->where('fechaEgresoPagos', '<=', $fechaHastaStr)
            ->with('acreedor')
            ->selectRaw(
                'tes_cch_pagos.*,
                CASE WHEN (CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN COALESCE(recuperadoPagos, 0) ELSE 0 END) >= montoPagos AND montoPagos > 0 THEN 0 ELSE
                (CASE 
                    WHEN (rendidoPagos IS NULL AND reintegradoPagos IS NULL) 
                         OR (fechaRendicionPagos IS NOT NULL AND fechaRendicionPagos > ?) 
                        THEN montoPagos 
                    ELSE COALESCE(rendidoPagos, 0) 
                END - CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN COALESCE(recuperadoPagos, 0) ELSE 0 END)
                END as saldo_pagos,
                CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN COALESCE(recuperadoPagos, 0) ELSE 0 END as recuperado_en_periodo,
                CASE 
                    WHEN (rendidoPagos IS NULL AND reintegradoPagos IS NULL) 
                         OR (fechaRendicionPagos IS NOT NULL AND fechaRendicionPagos > ?) 
                        THEN NULL 
                    ELSE rendidoPagos 
                END as rendido_en_periodo,
                CASE 
                    WHEN (rendidoPagos IS NULL AND reintegradoPagos IS NULL) 
                         OR (fechaRendicionPagos IS NOT NULL AND fechaRendicionPagos > ?) 
                        THEN NULL 
                    ELSE reintegradoPagos 
                END as reintegrado_en_periodo',
                [$fechaHastaStr, $fechaHastaStr, $fechaHastaStr, $fechaHastaStr, $fechaHastaStr, $fechaHastaStr]
            )
            ->get();

        $idActualesPagos = $pagosActual->pluck('idPagos')->toArray();
        $pagosAnteriores = $pagosAnteriores->filter(function ($p) use ($idActualesPagos) {
            return !in_array($p->idPagos, $idActualesPagos);
        })->map(function ($p) {
            $p->es_mes_anterior = true;
            return $p;
        })->filter(function ($p) {
            $saldo = $p->getAttributes()['saldo_pagos'] ?? $p->saldo_pagos ?? 0;
            return round($saldo, 2) > 0;
        });

        $allPagos = $pagosActual->concat($pagosAnteriores)->sortBy('fechaEgresoPagos')->values();

        if (!empty($searchPagos)) {
            $search = mb_strtolower($searchPagos, 'UTF-8');
            $allPagos = $allPagos->filter(function ($p) use ($search) {
                $egreso = mb_strtolower((string)($p->egresoPagos ?? ''), 'UTF-8');
                $acreedor = mb_strtolower($p->acreedor->acreedor ?? '', 'UTF-8');
                $concepto = mb_strtolower($p->conceptoPagos ?? '', 'UTF-8');
                $monto = number_format($p->montoPagos, 2, ',', '.');
                return str_contains($egreso, $search) || str_contains($acreedor, $search) || str_contains($concepto, $search) || str_contains($monto, $search);
            })->values();
        }

        return $allPagos->map(function ($item) {
            $arr = $item->toArray();
            $raw = $item->getAttributes();
            $arr['saldo_pagos'] = $raw['saldo_pagos'] ?? $item->saldo_pagos;
            $arr['recuperado_en_periodo'] = $raw['recuperado_en_periodo'] ?? 0;
            $arr['rendido_en_periodo'] = $raw['rendido_en_periodo'] ?? null;
            $arr['reintegrado_en_periodo'] = $raw['reintegrado_en_periodo'] ?? null;
            $arr['extra_pagos'] = (!is_null($arr['rendido_en_periodo']) && $arr['rendido_en_periodo'] > $item->montoPagos)
                ? round($arr['rendido_en_periodo'] - $item->montoPagos, 2)
                : 0;
            $arr['acreedor'] = is_object($arr['acreedor'] ?? null) ? (array) $arr['acreedor'] : ($arr['acreedor'] ?? ['acreedor' => '']);
            $arr['fecha_formateada'] = $item->fechaEgresoPagos ? Carbon::parse($item->fechaEgresoPagos)->format('d/m/Y') : '';
            $arr['es_mes_anterior'] = $item->es_mes_anterior;
            $arr['tiene_datos_rendicion'] = $item->tieneDatosRendicion();
            $arr['tiene_datos_recuperacion'] = $item->tieneDatosRecuperacion();
            $arr['puede_recuperar'] = $item->puedeRecuperar();
            return $arr;
        });
    }

    /**
     * Calcula los totales globales del mes para la vista.
     */
    public function calcularTotales(?CajaChica $cajaChica, Collection $pendientes, Collection $pagos): array
    {
        $montoCajaChica = $cajaChica ? floatval($cajaChica->montoCajaChica) : 0;
        $totales = ['Monto Caja Chica' => $montoCajaChica];

        $totalMontoPendientes = $pendientes->sum('montoPendientes');
        $totalRendido = $pendientes->sum('tot_rendido');
        $totalReintegrado = $pendientes->sum('tot_reintegrado');
        $totalRecuperado = $pendientes->sum('tot_recuperado');
        $stExtras = $pendientes->sum('extra_pendiente');
        
        $totalGastado = $totalRendido + $totalReintegrado;
        $totalGeneratedExtra = $pendientes->sum('extra');
        $stPendientes = ($totalMontoPendientes + $totalGeneratedExtra) - $totalGastado;
        $stPendientes = $stPendientes > 0 ? $stPendientes : 0;
        
        $stRendidos = max(0, ($totalRendido - $totalRecuperado) - $stExtras);

        $totales['Total Pendientes'] = $stPendientes;
        $totales['Total Rendidos'] = $stRendidos;
        $totales['Total Extras'] = $stExtras;
        // Pagos sin egreso que han sido rendidos (rendido > 0)
        $rendidoPagosSinEgreso = $pagos->filter(function($p) {
            return (!isset($p['egresoPagos']) || trim((string)$p['egresoPagos']) === '') && 
                   (floatval($p['rendido_en_periodo'] ?? 0) > 0);
        })->sum(function($p) {
            return floatval($p['rendido_en_periodo'] ?? 0) - floatval($p['recuperado_en_periodo'] ?? 0);
        });

        $totales['Total Rendido Sin Docs'] = $pendientes->sum('rendido_sin_docs_calc') + $rendidoPagosSinEgreso;

        $pagosConEgreso = $pagos->filter(fn($p) => isset($p['egresoPagos']) && trim((string)$p['egresoPagos']) !== '');
        $pagosSinEgreso = $pagos->filter(fn($p) => !isset($p['egresoPagos']) || trim((string)$p['egresoPagos']) === '');

        // Pagos con egreso (siempre se recuperan)
        $saldoPagosConEgreso = $pagosConEgreso->sum(fn($p) => $p['saldo_pagos'] ?? 0);
        
        // Pagos sin egreso que YA fueron rendidos (se mueven a recuperación según pedido del usuario)
        $saldoPagosSinEgresoRendidos = $rendidoPagosSinEgreso; 
        
        // Pagos sin egreso que NO han sido rendidos (quedan en "Pagos sin egreso")
        $saldoPagosSinEgresoPendientes = $pagosSinEgreso->filter(fn($p) => floatval($p['rendido_en_periodo'] ?? 0) <= 0)
                                                        ->sum(fn($p) => $p['saldo_pagos'] ?? 0);

        $totales['Saldo Pagos Directos'] = $saldoPagosConEgreso + $saldoPagosSinEgresoRendidos;
        $totales['Pagos Sin Egreso'] = $saldoPagosSinEgresoPendientes;

        // Nuevo cálculo: Pendientes y Pagos sin rendir con saldo positivo
        $pendientesSinRendir = $pendientes->filter(function($p) {
            return floatval($p['tot_rendido'] ?? 0) == 0 && floatval($p['saldo'] ?? 0) > 0;
        })->sum('saldo');

        $pagosSinRendir = $pagos->filter(function($p) {
            return is_null($p['rendido_en_periodo'] ?? null) && floatval($p['saldo_pagos'] ?? 0) > 0;
        })->sum('saldo_pagos');

        $totales['Pendientes y Pagos Sin Rendir'] = $pendientesSinRendir + $pagosSinRendir;

        $totales['Saldo Total'] = $montoCajaChica - $stPendientes - $stRendidos - $stExtras - $totales['Saldo Pagos Directos'] - $totales['Pagos Sin Egreso'];

        return $totales;
    }
    /**
     * Obtiene los elementos (Pendientes y Pagos Directos) con saldo para recuperar.
     */
    public function obtenerElementosParaRecuperar(?CajaChica $cajaChica, string $mesActual, int $anioActual, string $fechaRecuperacionActual): Collection
    {
        if (!$cajaChica) {
            return collect();
        }

        // --- Pendientes del mes actual ---
        $pendientesRecuperacion = Pendiente::where('relCajaChica', $cajaChica->idCajaChica)
            ->where('fechaPendientes', '<=', $fechaRecuperacionActual)
            ->with('dependencia')
            ->selectRaw(
                'tes_cch_pendientes.*,
                (SELECT SUM(rendido) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_rendido,
                (SELECT SUM(reintegrado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_reintegrado,
                (SELECT SUM(recuperado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_recuperado',
                [$fechaRecuperacionActual, $fechaRecuperacionActual, $fechaRecuperacionActual]
            )
            ->orderBy('pendiente', 'ASC')
            ->get();

        $pendientes = $pendientesRecuperacion->filter(function ($p) {
            $saldoRendido = ($p['tot_rendido'] ?? 0) - ($p['tot_recuperado'] ?? 0);
            return $saldoRendido > 0;
        })->map(function ($p) {
            $detalleDependencia = $p['dependencia']['dependencia'] ?? 'Sin dato';
            return [
                'id' => 'pendiente_' . $p['idPendientes'],
                'tipo' => 'Pendiente',
                'detalle' => $detalleDependencia,
                'saldo' => ($p['tot_rendido'] ?? 0) - ($p['tot_recuperado'] ?? 0),
                'origen_id' => $p['idPendientes'],
                'origen_type' => Pendiente::class,
            ];
        });

        // --- Pagos del mes actual ---
        $pagosRecuperacion = Pago::where('relCajaChica_Pagos', $cajaChica->idCajaChica)
            ->where('fechaEgresoPagos', '<=', $fechaRecuperacionActual)
            ->where(function($query) {
                $query->whereRaw("TRIM(egresoPagos) <> ''")
                      ->orWhere(function($q) {
                          $q->whereNotNull('rendidoPagos')
                            ->where('rendidoPagos', '>', 0);
                      });
            })
            ->with('acreedor')
            ->selectRaw(
                'tes_cch_pagos.*,
                CASE WHEN (CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN COALESCE(recuperadoPagos, 0) ELSE 0 END) >= montoPagos AND montoPagos > 0 THEN 0 ELSE
                (CASE 
                    WHEN (rendidoPagos IS NULL AND reintegradoPagos IS NULL) 
                         OR (fechaRendicionPagos IS NOT NULL AND fechaRendicionPagos > ?) 
                        THEN montoPagos 
                    ELSE COALESCE(rendidoPagos, 0) 
                END - CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN COALESCE(recuperadoPagos, 0) ELSE 0 END)
                END as saldo_pagos,
                CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN COALESCE(recuperadoPagos, 0) ELSE 0 END as recuperado_en_periodo',
                [$fechaRecuperacionActual, $fechaRecuperacionActual, $fechaRecuperacionActual, $fechaRecuperacionActual]
            )
            ->orderBy('fechaEgresoPagos', 'ASC')
            ->get();

        $pagos = $pagosRecuperacion->filter(function ($p) {
            return ($p['saldo_pagos'] ?? 0) > 0;
        })->map(function ($p) {
            $detalleAcreedor = $p['acreedor']['acreedor'] ?? 'Sin dato';
            return [
                'id' => 'pago_' . $p['idPagos'],
                'tipo' => 'Pago Directo',
                'detalle' => $detalleAcreedor . ' - ' . $p['conceptoPagos'],
                'saldo' => $p['saldo_pagos'] ?? 0,
                'origen_id' => $p['idPagos'],
                'origen_type' => Pago::class,
            ];
        });

        // --- Mes Anterior ---
        $meses = [
            'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
            'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
            'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
        ];
        $mesNo = $meses[strtolower($mesActual)] ?? now()->month;
        $fechaAnterior = Carbon::create($anioActual, $mesNo, 1)->subMonth();
        
        $cajaChicaAnterior = CajaChica::where('mes', strtolower($fechaAnterior->locale('es')->isoFormat('MMMM')))
            ->where('anio', $fechaAnterior->year)
            ->first();

        $pendientesAnterior = collect();
        $pagosAnterior = collect();

        if ($cajaChicaAnterior) {
            $pendientesRecuperacionAnterior = Pendiente::where('relCajaChica', $cajaChicaAnterior->idCajaChica)
                ->where('fechaPendientes', '<=', $fechaRecuperacionActual)
                ->with('dependencia')
                ->selectRaw(
                    'tes_cch_pendientes.*,
                    (SELECT SUM(rendido) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_rendido,
                    (SELECT SUM(reintegrado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_reintegrado,
                    (SELECT SUM(recuperado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_recuperado',
                    [$fechaRecuperacionActual, $fechaRecuperacionActual, $fechaRecuperacionActual]
                )
                ->orderBy('pendiente', 'ASC')
                ->get();

            $pendientesAnterior = $pendientesRecuperacionAnterior->filter(function ($p) {
                $saldoRendido = ($p['tot_rendido'] ?? 0) - ($p['tot_recuperado'] ?? 0);
                return $saldoRendido > 0;
            })->map(function ($p) {
                $detalleDependencia = $p['dependencia']['dependencia'] ?? 'Sin dato';
                return [
                    'id' => 'pendiente_' . $p['idPendientes'],
                    'tipo' => 'Pendiente (Mes Ant.)',
                    'detalle' => $detalleDependencia,
                    'saldo' => ($p['tot_rendido'] ?? 0) - ($p['tot_recuperado'] ?? 0),
                    'origen_id' => $p['idPendientes'],
                    'origen_type' => Pendiente::class,
                ];
            });

            $pagosRecuperacionAnterior = Pago::where('relCajaChica_Pagos', $cajaChicaAnterior->idCajaChica)
                ->where('fechaEgresoPagos', '<=', $fechaRecuperacionActual)
                ->where(function($query) {
                    $query->whereRaw("TRIM(egresoPagos) <> ''")
                          ->orWhere(function($q) {
                              $q->whereNotNull('rendidoPagos')
                                ->where('rendidoPagos', '>', 0);
                          });
                })
                ->with('acreedor')
                ->selectRaw(
                    'tes_cch_pagos.*,
                    CASE WHEN (CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN COALESCE(recuperadoPagos, 0) ELSE 0 END) >= montoPagos AND montoPagos > 0 THEN 0 ELSE
                    (CASE 
                        WHEN (rendidoPagos IS NULL AND reintegradoPagos IS NULL) 
                             OR (fechaRendicionPagos IS NOT NULL AND fechaRendicionPagos > ?) 
                            THEN montoPagos 
                        ELSE COALESCE(rendidoPagos, 0) 
                    END - CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN COALESCE(recuperadoPagos, 0) ELSE 0 END)
                    END as saldo_pagos,
                    CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN COALESCE(recuperadoPagos, 0) ELSE 0 END as recuperado_en_periodo',
                    [$fechaRecuperacionActual, $fechaRecuperacionActual, $fechaRecuperacionActual, $fechaRecuperacionActual]
                )
                ->orderBy('fechaEgresoPagos', 'ASC')
                ->get();

            $pagosAnterior = $pagosRecuperacionAnterior->filter(function ($p) {
                return ($p['saldo_pagos'] ?? 0) > 0;
            })->map(function ($p) {
                $detalleAcreedor = $p['acreedor']['acreedor'] ?? 'Sin dato';
                return [
                    'id' => 'pago_' . $p['idPagos'],
                    'tipo' => 'Pago Directo (Mes Ant.)',
                    'detalle' => $detalleAcreedor . ' - ' . $p['conceptoPagos'],
                    'saldo' => $p['saldo_pagos'] ?? 0,
                    'origen_id' => $p['idPagos'],
                    'origen_type' => Pago::class,
                ];
            });
        }

        return $pendientes->concat($pendientesAnterior)->concat($pagos)->concat($pagosAnterior)->values();
    }

    /**
     * Guarda la recuperación múltiple.
     * @throws \Exception
     */
    public function guardarRecuperacion(string $fechaRecuperacion, string $nroIngreso, array $itemsSeleccionados, array $itemsParaRecuperar): void
    {
        DB::beginTransaction();
        try {
            if (ctype_digit($nroIngreso)) {
                $nroIngreso = "INGRESO " . $nroIngreso;
            }

            $asientosService = app(CajaChicaAsientosService::class);
            $itemsCollection = collect($itemsParaRecuperar);

            foreach ($itemsSeleccionados as $itemId) {
                $item = $itemsCollection->firstWhere('id', $itemId);

                if (!$item) continue;

                if ($item['origen_type'] === Pendiente::class) {
                    $movimiento = Movimiento::create([
                        'relPendiente' => $item['origen_id'],
                        'fechaMovimientos' => $fechaRecuperacion,
                        'recuperado' => $item['saldo'],
                        'documentos' => $nroIngreso,
                        'rendido' => 0,
                        'reintegrado' => 0,
                    ]);
                    $asientosService->registrarAsientosRecuperacion($movimiento);
                } elseif ($item['origen_type'] === Pago::class) {
                    $pago = Pago::find($item['origen_id']);
                    if ($pago) {
                        $pago->recuperadoPagos = ($pago->recuperadoPagos ?? 0) + $item['saldo'];
                        $pago->fechaIngresoPagos = $fechaRecuperacion;
                        $pago->ingresoPagos = $nroIngreso;
                        $pago->save();
                        $asientosService->registrarAsientosRecuperacionPago($pago, (float) $item['saldo']);
                    }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calcula el monto total que se puede recuperar de un pendiente específico.
     */
    public function calcularMontoRecuperableRendido(int $pendienteId, string $fechaHastaStr): float
    {
        $tot_rendido = Movimiento::where('relPendiente', $pendienteId)
            ->where('fechaMovimientos', '<=', $fechaHastaStr)
            ->sum('rendido');

        $tot_recuperado = Movimiento::where('relPendiente', $pendienteId)
            ->where('fechaMovimientos', '<=', $fechaHastaStr)
            ->sum('recuperado');

        return max(0, $tot_rendido - $tot_recuperado);
    }

    /**
     * Guarda la recuperación parcial de un Rendido específico.
     * @throws \Exception
     */
    public function guardarRecuperacionRendido(array $data, string $fechaHastaStr): void
    {
        DB::beginTransaction();
        try {
            $pendiente = Pendiente::find($data['relPendiente']);
            if (!$pendiente) {
                throw new \Exception('Pendiente no encontrado.');
            }

            $montoRecuperableActual = $this->calcularMontoRecuperableRendido($pendiente->idPendientes, $fechaHastaStr);

            if ($data['monto_recuperado'] > $montoRecuperableActual) {
                throw new \Exception('El monto a recuperar no puede ser mayor que el saldo rendido actual del pendiente.');
            }

            $movimiento = Movimiento::create([
                'relPendiente' => $data['relPendiente'],
                'fechaMovimientos' => $data['fecha'],
                'documentos' => $data['documentos'],
                'rendido' => 0,
                'reintegrado' => 0,
                'recuperado' => $data['monto_recuperado'],
            ]);

            $asientosService = app(CajaChicaAsientosService::class);
            $asientosService->registrarAsientosRecuperacion($movimiento);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Guarda la recuperación parcial de un Pago Directo.
     * @throws \Exception
     */
    public function guardarRecuperacionPago(array $data): void
    {
        DB::beginTransaction();
        try {
            $pago = Pago::find($data['relPago']);
            if (!$pago) {
                throw new \Exception('Pago no encontrado.');
            }

            $rendido = $pago->rendidoPagos;
            $recuperado = $pago->recuperadoPagos ?: 0;
            $maxRecuperable = is_null($rendido)
                ? max(0, round($pago->montoPagos - $recuperado, 2))
                : max(0, round($rendido - $recuperado, 2));

            if ($data['monto_recuperado'] > $maxRecuperable) {
                throw new \Exception('El monto a recuperar no puede ser mayor que el saldo disponible del pago.');
            }

            $updateData = [
                'recuperadoPagos' => $pago->recuperadoPagos + $data['monto_recuperado'],
                'fechaIngresoPagos' => $data['fecha'],
                'ingresoPagos' => $data['numero_ingreso'],
                'ingresoPagosBSE' => $data['numero_ingreso_bse'] ?? null,
            ];

            if (!empty($data['fecha_ingreso_bse'])) {
                $updateData['fechaIngresoBSEPagos'] = $data['fecha_ingreso_bse'];
            }

            $pago->update($updateData);

            if (!$this->esPagoBSEConCamposBSE($pago, $data)) {
                $asientosService = app(CajaChicaAsientosService::class);
                $asientosService->registrarAsientosRecuperacionPago($pago, (float) $data['monto_recuperado']);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Determina si un pago del Banco de Seguros del Estado (BSE) fue
     * recuperado completando los campos específicos del BSE. En ese caso
     * no debe generarse asiento en el Libro Diario (la recuperación se
     * registra contra el propio BSE, sin movimiento de caja).
     *
     * @param Pago $pago  Pago directo (debe tener el acreedor cargado).
     * @param array $data Datos del formulario (numero_ingreso_bse,
     *                    fecha_ingreso_bse, ingresoPagosBSE, fechaIngresoBSEPagos).
     */
    private function esPagoBSEConCamposBSE(Pago $pago, array $data = []): bool
    {
        $esBancoDeSeguros = ($pago->acreedor->acreedor ?? '') === 'Banco de Seguros del Estado';

        if (!$esBancoDeSeguros) {
            return false;
        }

        // Campos BSE persistidos en el pago (recuperación ya registrada).
        $tieneCamposEnPago = !empty($pago->ingresoPagosBSE)
            || !empty($pago->fechaIngresoBSEPagos);

        // Campos BSE enviados por el formulario.
        $tieneCamposEnData = !empty($data['numero_ingreso_bse'])
            || !empty($data['fecha_ingreso_bse'])
            || !empty($data['ingresoPagosBSE'])
            || !empty($data['fechaIngresoBSEPagos']);

        return $tieneCamposEnPago || $tieneCamposEnData;
    }

    /**
     * Guarda la rendición de un Pago Directo.
     * @throws \Exception
     */
    public function guardarRendicionPago(array $data): void
    {
        DB::beginTransaction();
        try {
            $pago = Pago::find($data['relPago']);
            if (!$pago) {
                throw new \Exception('Pago no encontrado.');
            }

            $rendido = floatval($data['monto_rendido']);
            $otorgado = floatval($pago->montoPagos);
            $reintegrado = ($rendido <= $otorgado) ? round($otorgado - $rendido, 2) : 0;

            $pago->update([
                'rendidoPagos' => $rendido,
                'reintegradoPagos' => $reintegrado,
                'ingresoReintegroPagos' => $data['ingreso_reintegro'] ?? null,
                'fechaRendicionPagos' => $data['fecha_rendicion'] ?? now()->format('Y-m-d'),
            ]);

            if (($rendido > 0) || ($reintegrado > 0)) {
                $asientosService = app(CajaChicaAsientosService::class);
                $asientosService->registrarAsientosRendicionPago($pago);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Actualiza el monto de un fondo de caja chica.
     * Retorna un arreglo con montos anteriores y nuevos, o null si no hay cambios.
     */
    public function actualizarFondo(int $idCajaChica, float $montoNuevo): ?array
    {
        $fondo = CajaChica::findOrFail($idCajaChica);
        $montoAnterior = $fondo->montoCajaChica;

        if (abs($montoAnterior - $montoNuevo) < 0.01) {
            return null;
        }

        $fondo->montoCajaChica = $montoNuevo;
        $fondo->save();

        try {
            $asientosService = app(CajaChicaAsientosService::class);
            $asientosService->registrarAjusteFondoFijoPorSaldoLibroDiario(
                $fondo,
                $montoNuevo,
                now()->toDateString()
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('CajaChicaService: no se pudo registrar asiento de ajuste de fondo fijo', [
                'caja_chica_id' => $idCajaChica,
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'montoAnterior' => $montoAnterior,
            'montoNuevo' => $montoNuevo
        ];
    }

    /**
     * Obtiene las dependencias que aún no han rendido su pendiente correspondiente en el mes.
     */
    public function obtenerDependenciasSinPendientes(?CajaChica $cajaChica, string $fechaHastaStr): array
    {
        if (!$cajaChica) {
            return ['normales' => collect(), 'especiales' => collect()];
        }

        $conteoPorDependencia = Pendiente::where('relCajaChica', $cajaChica->idCajaChica)
            ->where('fechaPendientes', '<=', $fechaHastaStr)
            ->selectRaw('relDependencia, count(*) as total')
            ->groupBy('relDependencia')
            ->pluck('total', 'relDependencia');

        $todasDependencias = Dependencia::where('dependencia', '<>', 'Dirección de Tesorería')
            ->orderBy('dependencia', 'ASC')
            ->get();

        $faltantes = collect();

        foreach ($todasDependencias as $dep) {
            $nombreNorm = $this->normalizarParaComparar($dep->dependencia);
            $esEspecial = str_contains($nombreNorm, '(especial)');

            $meta = 1;
            if (!$esEspecial && (str_contains($nombreNorm, 'direccion de administracion') || str_contains($nombreNorm, 'direccion de logistica y apoyo'))) {
                $meta = 2;
            }

            $cantidadActual = $conteoPorDependencia->get($dep->idDependencias, 0);

            if ($cantidadActual < $meta) {
                $faltantes->push($dep);
            }
        }

        return [
            'normales' => $faltantes->filter(fn($dep) => !str_contains(strtolower($dep->dependencia), '(especial)')),
            'especiales' => $faltantes->filter(fn($dep) => str_contains(strtolower($dep->dependencia), '(especial)')),
        ];
    }

    private function normalizarParaComparar($string)
    {
        $string = mb_strtolower($string, 'UTF-8');
        $replacements = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
            'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c'
        ];
        return strtr($string, $replacements);
    }

    public function actualizarPendiente(int $id, array $data): Pendiente
    {
        $pendiente = Pendiente::findOrFail($id);
        $montoAnterior = (float) $pendiente->montoPendientes;

        $pendiente->pendiente = $data['pendiente'];
        $pendiente->fechaPendientes = $data['fechaPendientes'];
        $pendiente->relDependencia = $data['relDependencia'];
        $pendiente->montoPendientes = $data['montoPendientes'];

        DB::transaction(function () use ($pendiente, $montoAnterior) {
            $montoCambio = abs((float) $pendiente->montoPendientes - $montoAnterior) >= 0.01;

            if ($montoCambio) {
                $asientosService = app(CajaChicaAsientosService::class);
                $asientosService->reemplazarRedistribucionPendiente($pendiente, $montoAnterior);
            }

            $pendiente->save();
        });

        return $pendiente->fresh();
    }

    public function crearPendiente(array $data): Pendiente
    {
        return DB::transaction(function () use ($data) {
            $pendiente = Pendiente::create([
                'relCajaChica' => $data['relCajaChica'],
                'pendiente' => $data['pendiente'],
                'fechaPendientes' => $data['fechaPendientes'],
                'relDependencia' => $data['relDependencia'],
                'montoPendientes' => $data['montoPendientes'],
            ]);

            $asientosService = app(CajaChicaAsientosService::class);
            $asientosService->registrarRedistribucionPendiente($pendiente);

            return $pendiente;
        });
    }

    public function crearPago(array $data): Pago
    {
        return DB::transaction(function () use ($data) {
            $pago = Pago::create([
                'relCajaChica_Pagos' => $data['relCajaChica_Pagos'] ?? $data['relCajaChica'] ?? null,
                'fechaEgresoPagos' => $data['fechaEgresoPagos'],
                'egresoPagos' => $data['egresoPagos'] ?? null,
                'relAcreedores' => $data['relAcreedores'] ?? null,
                'conceptoPagos' => $data['conceptoPagos'],
                'montoPagos' => $data['montoPagos'],
            ]);

            $asientosService = app(CajaChicaAsientosService::class);
            $asientosService->registrarRedistribucionPago($pago);

            return $pago;
        });
    }

    public function crearFondo(array $data): CajaChica
    {
        return DB::transaction(function () use ($data) {
            $cajaChica = CajaChica::create([
                'mes' => $data['mes'],
                'anio' => $data['anio'],
                'montoCajaChica' => $data['montoCajaChica'],
            ]);

            try {
                $asientosService = app(CajaChicaAsientosService::class);
                $asientosService->registrarAjusteFondoFijoPorSaldoLibroDiario(
                    $cajaChica,
                    (float) $cajaChica->montoCajaChica,
                    now()->toDateString()
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('CajaChicaService: no se pudo registrar asiento de fondo fijo al crear fondo', [
                    'caja_chica_id' => $cajaChica->idCajaChica,
                    'error' => $e->getMessage(),
                ]);
            }

            return $cajaChica;
        });
    }

    public function crearMovimiento(array $data): Movimiento
    {
        return DB::transaction(function () use ($data) {
            $movimiento = Movimiento::create([
                'relPendiente' => $data['relPendiente'],
                'fechaMovimientos' => $data['fechaMovimientos'],
                'documentos' => $data['documentos'] ?? null,
                'rendido' => $data['rendido'] ?? 0,
                'reintegrado' => $data['reintegrado'] ?? 0,
                'recuperado' => $data['recuperado'] ?? 0,
            ]);

            $asientosService = app(CajaChicaAsientosService::class);
            $rendido = (float) ($movimiento->rendido ?? 0);
            $reintegrado = (float) ($movimiento->reintegrado ?? 0);
            $recuperado = (float) ($movimiento->recuperado ?? 0);

            if ($rendido > 0 || $reintegrado > 0) {
                $asientosService->registrarAsientosRendicionPendiente($movimiento);
            }
            if ($recuperado > 0) {
                $asientosService->registrarAsientosRecuperacion($movimiento);
            }

            return $movimiento;
        });
    }

    public function eliminarMovimiento(int $id): void
    {
        DB::transaction(function () use ($id) {
            $asientosService = app(CajaChicaAsientosService::class);
            $asientosService->eliminarAsientosPorOrigen('movimiento', $id);

            $movimiento = Movimiento::findOrFail($id);
            $movimiento->delete();
        });
    }

    public function actualizarMovimiento(int $id, array $data): Movimiento
    {
        $movimiento = Movimiento::findOrFail($id);

        DB::transaction(function () use ($movimiento, $data, $id) {
            $asientosService = app(CajaChicaAsientosService::class);
            $asientosService->eliminarAsientosPorOrigen('movimiento', $id);

            $movimiento->fechaMovimientos = $data['fechaMovimientos'];
            $movimiento->documentos = $data['documentos'] ?? null;
            $movimiento->rendido = $data['rendido'] ?? 0;
            $movimiento->reintegrado = $data['reintegrado'] ?? 0;
            $movimiento->recuperado = $data['recuperado'] ?? 0;
            $movimiento->save();

            $rendido = (float) ($movimiento->rendido ?? 0);
            $recuperado = (float) ($movimiento->recuperado ?? 0);

            if ($rendido > 0) {
                $asientosService->registrarAsientosRendicionPendiente($movimiento);
            }
            if ($recuperado > 0) {
                $asientosService->registrarAsientosRecuperacion($movimiento);
            }
        });

        return $movimiento->fresh();
    }

    public function actualizarPago(int $id, array $data): Pago
    {
        $pago = Pago::findOrFail($id);
        $montoAnterior = (float) $pago->montoPagos;
        $teniaRendicion = $pago->tieneDatosRendicion();

        // La recuperación del BSE (acreedor BSE + campos BSE) NO genera asientos
        // en el Libro Diario. Por lo tanto, al editar un pago BSE no debe tocarse
        // la redistribución ni la recuperación (evita el error "monto a
        // redistribuir supera el saldo disponible del flujo de origen").
        $esBSE = $this->esPagoBSEConCamposBSE($pago, $data);

        DB::transaction(function () use ($pago, $data, $montoAnterior, $teniaRendicion, $esBSE) {
            $asientosService = app(CajaChicaAsientosService::class);

            if (!$esBSE && $teniaRendicion) {
                $asientosService->eliminarAsientosPorOrigen('pago', $pago->idPagos);
            }

            $pago->fechaEgresoPagos = $data['fechaEgresoPagos'];
            $pago->fechaEgresoEfectivoPagos = $data['fechaEgresoEfectivoPagos'] ?? null;
            $pago->egresoPagos = $data['egresoPagos'] ?? null;
            $pago->relAcreedores = $data['relAcreedores'] ?? null;
            $pago->conceptoPagos = $data['conceptoPagos'];
            $pago->montoPagos = $data['montoPagos'];
            $pago->rendidoPagos = $data['rendidoPagos'] ?? null;
            $pago->reintegradoPagos = $data['reintegradoPagos'] ?? null;
            $pago->recuperadoPagos = $data['recuperadoPagos'] ?? 0;
            $pago->fechaIngresoPagos = $data['fechaIngresoPagos'] ?? null;
            $pago->ingresoPagos = $data['ingresoPagos'] ?? null;
            $pago->ingresoReintegroPagos = $data['ingresoReintegroPagos'] ?? null;
            $pago->ingresoPagosBSE = $data['ingresoPagosBSE'] ?? null;
            $pago->fechaIngresoBSEPagos = $data['fechaIngresoBSEPagos'] ?? null;
            $pago->fechaRendicionPagos = $data['fechaRendicionPagos'] ?? null;
            $pago->save();

            if ($esBSE) {
                return;
            }

            $montoCambio = abs((float) $pago->montoPagos - $montoAnterior) >= 0.01;
            $rendido = (float) ($pago->rendidoPagos ?? 0);
            $recuperado = (float) ($pago->recuperadoPagos ?? 0);

            if ($teniaRendicion) {
                $asientosService->registrarRedistribucionPago($pago);
            } elseif ($montoCambio) {
                $asientosService->reemplazarRedistribucionPago($pago, $montoAnterior);
            }

            if ($rendido > 0) {
                $asientosService->registrarAsientosRendicionPago($pago);
            }
            if ($recuperado > 0 && !$this->esPagoBSEConCamposBSE($pago, $data)) {
                $asientosService->registrarAsientosRecuperacionPago($pago, $recuperado);
            }
        });

        return $pago->fresh();
    }

    public function eliminarRendicionPago(int $id): void
    {
        $pago = Pago::findOrFail($id);

        if (($pago->recuperadoPagos ?? 0) > 0 || $pago->fechaIngresoPagos || $pago->ingresoPagos) {
            throw new \Exception('No se puede eliminar la rendición porque existen datos de recuperación.');
        }

        DB::transaction(function () use ($pago) {
            $asientosService = app(CajaChicaAsientosService::class);
            $asientosService->eliminarAsientosPorOrigen('pago', $pago->idPagos);

            $asientosService->registrarRedistribucionPago($pago);

            $pago->update([
                'rendidoPagos' => null,
                'reintegradoPagos' => null,
                'ingresoReintegroPagos' => null,
                'fechaRendicionPagos' => null,
            ]);
        });
    }

    public function eliminarRecuperacionPago(int $id): void
    {
        $pago = Pago::findOrFail($id);

        DB::transaction(function () use ($pago) {
            $asientosService = app(CajaChicaAsientosService::class);
            $asientosService->eliminarAsientosPorOrigen('pago', $pago->idPagos);

            $pago->update([
                'recuperadoPagos' => 0,
                'fechaIngresoPagos' => null,
                'ingresoPagos' => null,
            ]);

            $rendido = (float) ($pago->rendidoPagos ?? 0);

            $asientosService->registrarRedistribucionPago($pago);

            if ($rendido > 0) {
                $asientosService->registrarAsientosRendicionPago($pago);
            }
        });
    }

    public function eliminarBSEPago(int $id): void
    {
        $pago = Pago::findOrFail($id);

        $pago->update([
            'ingresoPagosBSE' => null,
            'fechaIngresoBSEPagos' => null,
        ]);
    }

    /**
     * Elimina un pendiente siempre que no tenga movimientos asociados.
     * @throws \Exception
     */
    public function eliminarPendiente(int $id): void
    {
        $pendiente = Pendiente::find($id);
        if (!$pendiente) {
            throw new \Exception('Pendiente no encontrado.');
        }

        if ($pendiente->movimientos()->count() > 0) {
            throw new \Exception('No se puede eliminar el pendiente porque tiene movimientos asociados.');
        }

        DB::transaction(function () use ($pendiente, $id) {
            $asientosService = app(CajaChicaAsientosService::class);
            $asientosService->eliminarAsientosPorOrigen('pendiente', $id);

            $pendiente->delete();
        });
    }

    /**
     * Elimina un pago directo si no tiene ingresos ni recuperos.
     * @throws \Exception
     */
    public function eliminarPago(int $id): void
    {
        $pago = Pago::find($id);
        if (!$pago) {
            throw new \Exception('Pago no encontrado.');
        }

        if ($pago->tieneDatosRendicion() || $pago->tieneDatosRecuperacion()) {
            throw new \Exception('No se puede eliminar el pago porque tiene datos de rendición o recuperación registrados.');
        }

        DB::transaction(function () use ($pago, $id) {
            $asientosService = app(CajaChicaAsientosService::class);
            $asientosService->eliminarAsientosPorOrigen('pago', $id);

            $pago->delete();
        });
    }
}
