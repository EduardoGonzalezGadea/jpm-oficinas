<?php

namespace App\Services\Tesoreria;

use App\Models\Tesoreria\TesCfe;
use App\Models\Tesoreria\TesCfeItem;
use App\Models\Tesoreria\TesPlanillaEr;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Servicio para cálculo de KPIs del Dashboard de Recaudaciones
 * 
 * Implementa los 8 KPIs principales:
 * 1. Total Recaudado con desglose por medio de pago
 * 2. Planillas Pendientes de Confirmación
 * 3. Ítems Sin Asignar (alerta si > 7 días)
 * 4. Recaudación por Tipo de Distribución SIIF
 * 5. Recaudación por Dependencia (top 10)
 * 6. Recaudación por Medio de Pago (con %)
 * 7. Comparativa con Período Anterior
 * 8. Panel de Alertas
 */
class DashboardService
{
    /**
     * Calcula el total recaudado con desglose por medio de pago
     * Aplica lógica de prorrateo según distribución de items
     * 
     * @param Carbon $fechaInicio
     * @param Carbon $fechaFin
     * @return array
     */
    public function getTotalRecaudadoPorMedioPago(Carbon $fechaInicio, Carbon $fechaFin): array
    {
        // Obtener todos los items del período con sus CFEs y medios de pago
        $items = TesCfeItem::select('tes_cfe_items.*')
            ->join('tes_cfes', 'tes_cfe_items.tes_cfe_id', '=', 'tes_cfes.id')
            ->with([
                'cfe.mediosPago.medioPago',
                'cfe.items',
            ])
            ->whereNull('tes_cfe_items.deleted_at')
            ->whereNull('tes_cfes.deleted_at')
            ->whereBetween('tes_cfes.fecha', [$fechaInicio, $fechaFin])
            ->get();

        // Inicializar totales
        $totales = [
            'Efectivo' => 0,
            'Cheque' => 0,
            'Transferencia Bancaria' => 0,
            'Tarjeta de Débito' => 0,
            'Otros' => 0,
        ];

        $agregados = [];

        // Agrupar items por CFE para evitar procesar duplicados
        foreach ($items as $item) {
            $cfe = $item->cfe;
            $cfeKey = $item->tes_cfe_id;
            $itemKey = "{$cfeKey}|{$item->id}";

            if (!isset($agregados[$itemKey])) {
                $agregados[$itemKey] = [
                    'cfe' => $cfe,
                    'importe' => $item->importe,
                ];
            }
        }

        // Aplicar prorrateo por cada item/CFE
        foreach ($agregados as $aggr) {
            $cfe = $aggr['cfe'];
            $sumImporte = $aggr['importe'];

            // Calcular proporción del item respecto al total del CFE
            $cfeTotalItems = $cfe->items->sum('importe');
            $proporcion = $cfeTotalItems > 0 ? $sumImporte / $cfeTotalItems : 0;

            // Prorratear cada medio de pago
            foreach ($cfe->mediosPago as $mp) {
                $valorProrated = round($mp->medio_pago_valor * $proporcion, 2);
                $nombreMedio = $mp->medioPago?->nombre ?? 'Otros';

                if (!isset($totales[$nombreMedio])) {
                    $totales['Otros'] += $valorProrated;
                } else {
                    $totales[$nombreMedio] += $valorProrated;
                }
            }
        }

        // Calcular total general
        $totalGeneral = array_sum($totales);

        // Calcular promedio diario
        $diasPeriodo = max(1, $fechaInicio->diffInDays($fechaFin) + 1);
        $promedioDiario = $totalGeneral / $diasPeriodo;

        // Calcular porcentajes
        $totalesConPorcentaje = [];
        foreach ($totales as $medio => $monto) {
            $totalesConPorcentaje[] = [
                'medio' => $medio,
                'monto' => round($monto, 2),
                'porcentaje' => $totalGeneral > 0 ? round(($monto / $totalGeneral) * 100, 2) : 0,
            ];
        }

        return [
            'total_general' => round($totalGeneral, 2),
            'promedio_diario' => round($promedioDiario, 2),
            'dias_periodo' => $diasPeriodo,
            'desglose' => $totalesConPorcentaje,
        ];
    }

    /**
     * Obtiene planillas pendientes de confirmación
     * 
     * @return array
     */
    public function getPlanillasPendientes(): array
    {
        $planillas = TesPlanillaEr::with(['tipo', 'dependencia'])
            ->where('confirmada', false)
            ->whereNull('deleted_at')
            ->orderBy('fecha', 'desc')
            ->get();

        return [
            'count' => $planillas->count(),
            'planillas' => $planillas->map(function ($p) {
                return [
                    'id' => $p->id,
                    'numero' => $p->numero,
                    'fecha' => $p->fecha?->format('d/m/Y'),
                    'tipo' => $p->tipo?->tipo ?? 'N/A',
                    'dependencia' => $p->dependencia?->abreviatura ?? 'N/A',
                ];
            })->take(10), // Limitar a 10 para el panel
        ];
    }

    /**
     * Obtiene ítems sin asignar a planilla
     * Genera alerta si tienen más de 7 días
     * 
     * IMPORTANTE: Solo incluye ítems que VAN a la CUN (tienen distribución SIIF).
     * Los arrendamientos y otros que NO van a CUN nunca necesitan planilla,
     * por lo tanto no deben aparecer como "sin asignar".
     * 
     * @return array
     */
    public function getItemsSinAsignar(): array
    {
        $hoy = Carbon::now();
        $umbralAlerta = Carbon::now()->subDays(7);

        $items = TesCfeItem::select('tes_cfe_items.*')
            ->join('tes_cfes', 'tes_cfe_items.tes_cfe_id', '=', 'tes_cfes.id')
            ->with(['cfe'])
            ->whereNull('tes_cfe_items.planilla_er_id')
            ->whereNull('tes_cfe_items.deleted_at')
            ->whereNull('tes_cfes.deleted_at')
            // Solo ítems que tienen distribución SIIF asignada a nivel de ÍTEM
            // Este es el campo que se ve en "Ver detalles" como "Distribución SIIF"
            ->whereNotNull('tes_cfe_items.siif_distribucion_id')
            ->orderBy('tes_cfes.fecha', 'asc')
            ->get();

        $itemsAntiguos = $items->filter(function ($item) use ($umbralAlerta) {
            return $item->cfe?->fecha && $item->cfe->fecha->lte($umbralAlerta);
        });

        return [
            'count' => $items->count(),
            'monto_total' => round($items->sum('importe'), 2),
            'antiguos' => $itemsAntiguos->count(),
            'alerta' => $itemsAntiguos->count() > 0,
            'items_recientes' => $items->take(10)->map(function ($item) {
                $cfe = $item->cfe;
                $numeroCompleto = '';
                $numeroSolo = '';
                
                if ($cfe) {
                    $numeroCompleto = trim($cfe->documento_tipo . ' ' . $cfe->documento_serie . '-' . $cfe->documento_numero);
                    $numeroSolo = $cfe->documento_numero; // Solo el número para búsqueda
                }
                
                return [
                    'id' => $item->id,
                    'cfe_numero' => $numeroCompleto,
                    'cfe_numero_solo' => $numeroSolo,
                    'descripcion' => trim(
                        ($item->detalle ?? '') . 
                        (($item->detalle && $item->descripcion) ? ' ' : '') . 
                        ($item->descripcion ?? '')
                    ) ?: 'Sin descripción',
                    'importe' => $item->importe,
                    'fecha' => $item->cfe?->fecha?->format('d/m/Y') ?? 'N/A',
                    'dias_antiguedad' => $item->cfe?->fecha 
                        ? Carbon::now()->diffInDays($item->cfe->fecha) 
                        : 0,
                ];
            }),
        ];
    }

    /**
     * Calcula recaudación por Tipo de Distribución SIIF
     * 
     * @param Carbon $fechaInicio
     * @param Carbon $fechaFin
     * @return array
     */
    public function getRecaudacionPorTipoSiif(Carbon $fechaInicio, Carbon $fechaFin): array
    {
        $items = TesCfeItem::select(
                'siif_distribucion_tipos.tipo',
                DB::raw('SUM(tes_cfe_items.importe) as total')
            )
            ->join('tes_cfes', 'tes_cfe_items.tes_cfe_id', '=', 'tes_cfes.id')
            ->join('tes_caja_conceptos', 'tes_cfes.tes_caja_concepto_id', '=', 'tes_caja_conceptos.id')
            ->join('siif_distribucion_tipos', 'tes_caja_conceptos.siif_distribucion_tipo_id', '=', 'siif_distribucion_tipos.id')
            ->whereNull('tes_cfe_items.deleted_at')
            ->whereNull('tes_cfes.deleted_at')
            ->whereNull('tes_caja_conceptos.deleted_at')
            ->whereNull('siif_distribucion_tipos.deleted_at')
            ->whereBetween('tes_cfes.fecha', [$fechaInicio, $fechaFin])
            ->groupBy('siif_distribucion_tipos.id', 'siif_distribucion_tipos.tipo')
            ->orderBy('total', 'desc')
            ->get();

        return $items->map(function ($item) {
            return [
                'tipo' => $item->tipo,
                'total' => round($item->total, 2),
            ];
        })->toArray();
    }

    /**
     * Calcula recaudación por Dependencia (top 10)
     * 
     * @param Carbon $fechaInicio
     * @param Carbon $fechaFin
     * @return array
     */
    public function getRecaudacionPorDependencia(Carbon $fechaInicio, Carbon $fechaFin): array
    {
        $items = TesCfeItem::select(
                'siif_distribucion_dependencias.abreviatura',
                'siif_distribucion_dependencias.dependencia',
                DB::raw('SUM(tes_cfe_items.importe) as total')
            )
            ->join('tes_cfes', 'tes_cfe_items.tes_cfe_id', '=', 'tes_cfes.id')
            ->join('siif_distribucion_dependencias', 'tes_cfes.siif_distribucion_dependencia_id', '=', 'siif_distribucion_dependencias.id')
            ->whereNull('tes_cfe_items.deleted_at')
            ->whereNull('tes_cfes.deleted_at')
            ->whereNull('siif_distribucion_dependencias.deleted_at')
            ->whereBetween('tes_cfes.fecha', [$fechaInicio, $fechaFin])
            ->groupBy('siif_distribucion_dependencias.id', 'siif_distribucion_dependencias.abreviatura', 'siif_distribucion_dependencias.dependencia')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        return $items->map(function ($item) {
            return [
                'dependencia' => $item->abreviatura ?? $item->dependencia,
                'total' => round($item->total, 2),
            ];
        })->toArray();
    }

    /**
     * Genera comparativa con período anterior
     * 
     * @param Carbon $fechaInicio
     * @param Carbon $fechaFin
     * @return array
     */
    public function getComparativaPeriodoAnterior(Carbon $fechaInicio, Carbon $fechaFin): array
    {
        // Calcular duración del período actual
        $duracion = $fechaInicio->diffInDays($fechaFin);
        
        // Calcular fechas del período anterior
        $fechaInicioAnterior = $fechaInicio->copy()->subDays($duracion + 1);
        $fechaFinAnterior = $fechaInicio->copy()->subDay();

        // Total período actual
        $totalActual = TesCfeItem::join('tes_cfes', 'tes_cfe_items.tes_cfe_id', '=', 'tes_cfes.id')
            ->whereNull('tes_cfe_items.deleted_at')
            ->whereNull('tes_cfes.deleted_at')
            ->whereBetween('tes_cfes.fecha', [$fechaInicio, $fechaFin])
            ->sum('tes_cfe_items.importe');

        // Total período anterior
        $totalAnterior = TesCfeItem::join('tes_cfes', 'tes_cfe_items.tes_cfe_id', '=', 'tes_cfes.id')
            ->whereNull('tes_cfe_items.deleted_at')
            ->whereNull('tes_cfes.deleted_at')
            ->whereBetween('tes_cfes.fecha', [$fechaInicioAnterior, $fechaFinAnterior])
            ->sum('tes_cfe_items.importe');

        // Calcular diferencia y porcentaje
        $diferencia = $totalActual - $totalAnterior;
        
        // Calcular porcentaje con casos especiales
        if ($totalAnterior == 0 && $totalActual == 0) {
            // Caso: Ambos períodos en $0
            $porcentaje = 0;
            $porcentajeDisplay = '0%';
        } elseif ($totalAnterior == 0 && $totalActual > 0) {
            // Caso: Período anterior $0, actual con recaudación
            // No se puede calcular porcentaje (división por 0)
            // Se muestra como "Nuevo" o un indicador especial
            $porcentaje = null; // Null indica caso especial
            $porcentajeDisplay = 'Nuevo';
        } elseif ($totalActual == 0 && $totalAnterior > 0) {
            // Caso: Período anterior con recaudación, actual $0
            // Representa una caída del 100%
            $porcentaje = -100;
            $porcentajeDisplay = '-100%';
        } else {
            // Caso normal: ambos períodos tienen valores
            $porcentaje = round((($totalActual - $totalAnterior) / $totalAnterior) * 100, 2);
            $porcentajeDisplay = $porcentaje . '%';
        }

        return [
            'actual' => round($totalActual, 2),
            'anterior' => round($totalAnterior, 2),
            'diferencia' => round($diferencia, 2),
            'porcentaje' => $porcentaje,
            'porcentaje_display' => $porcentajeDisplay,
            'tendencia' => $diferencia >= 0 ? 'up' : 'down',
        ];
    }

    /**
     * Genera panel de alertas
     * 
     * @return array
     */
    public function getAlertas(): array
    {
        $alertas = [];

        // Alerta: Ítems sin asignar antiguos
        $itemsSinAsignar = $this->getItemsSinAsignar();
        if ($itemsSinAsignar['alerta']) {
            $alertas[] = [
                'tipo' => 'warning',
                'titulo' => 'Ítems sin asignar antiguos',
                'mensaje' => "Hay {$itemsSinAsignar['antiguos']} ítems sin asignar con más de 7 días de antigüedad.",
                'icono' => 'exclamation-triangle',
            ];
        }

        // Alerta: Planillas pendientes
        $planillasPendientes = $this->getPlanillasPendientes();
        if ($planillasPendientes['count'] > 10) {
            $alertas[] = [
                'tipo' => 'info',
                'titulo' => 'Muchas planillas pendientes',
                'mensaje' => "Hay {$planillasPendientes['count']} planillas pendientes de confirmación.",
                'icono' => 'info-circle',
            ];
        }

        // Alerta: CFEs sin distribución
        $cfesSinDistribucion = TesCfe::whereNull('siif_distribucion_dependencia_id')
            ->orWhereNull('tes_caja_concepto_id')
            ->whereNull('deleted_at')
            ->count();

        if ($cfesSinDistribucion > 0) {
            $alertas[] = [
                'tipo' => 'danger',
                'titulo' => 'CFEs sin distribución asignada',
                'mensaje' => "Hay {$cfesSinDistribucion} CFEs sin distribución SIIF o concepto de caja.",
                'icono' => 'exclamation-circle',
            ];
        }

        // Si no hay alertas
        if (empty($alertas)) {
            $alertas[] = [
                'tipo' => 'success',
                'titulo' => 'Todo en orden',
                'mensaje' => 'No hay alertas críticas en este momento.',
                'icono' => 'check-circle',
            ];
        }

        return $alertas;
    }

    /**
     * Obtiene todos los KPIs consolidados para un período
     * 
     * @param Carbon $fechaInicio
     * @param Carbon $fechaFin
     * @return array
     */
    public function getAllKPIs(Carbon $fechaInicio, Carbon $fechaFin): array
    {
        return [
            'total_recaudado' => $this->getTotalRecaudadoPorMedioPago($fechaInicio, $fechaFin),
            'planillas_pendientes' => $this->getPlanillasPendientes(),
            'items_sin_asignar' => $this->getItemsSinAsignar(),
            'recaudacion_por_tipo_siif' => $this->getRecaudacionPorTipoSiif($fechaInicio, $fechaFin),
            'recaudacion_por_dependencia' => $this->getRecaudacionPorDependencia($fechaInicio, $fechaFin),
            'comparativa' => $this->getComparativaPeriodoAnterior($fechaInicio, $fechaFin),
            'alertas' => $this->getAlertas(),
        ];
    }
}
