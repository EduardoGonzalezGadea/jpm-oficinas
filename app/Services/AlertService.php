<?php

namespace App\Services;


use App\Models\Tesoreria\CertificadoResidencia;
use App\Models\Tesoreria\Cheque;
use App\Models\Tesoreria\CuentaBancaria;
use App\Models\Tesoreria\LibretaValor;
use App\Models\Tesoreria\Pago;
use App\Models\Tesoreria\Pendiente;
use App\Models\Tesoreria\TipoLibreta;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class AlertService
{
    /**
     * Obtiene todas las alertas organizadas por categorías
     * Cache TTL: 5 minutos (300 segundos)
     */
    public function getAllAlerts(): array
    {
        return Cache::remember('dashboard_alerts', 300, function () {
            $criticas = $this->getAlertasCriticas();
            $colapsables = $this->getAlertasColapsables();
            
            return [
                'criticas' => $criticas,
                'colapsables' => $colapsables,
                'total_colapsables' => collect($colapsables)->sum('contador'),
                'last_updated' => Carbon::now()->format('d/m/Y H:i'),
            ];
        });
    }

    /**
     * Obtiene alertas críticas - SIEMPRE visibles
     */
    private function getAlertasCriticas(): array
    {
        $alertas = [];
        
        return [
            'alertas' => $alertas,
            'contador' => count($alertas),
            'prioridad_maxima' => !empty($alertas) ? 1 : null,
        ];
    }

    /**
     * Obtiene alertas colapsables organizadas por categorías
     */
    private function getAlertasColapsables(): array
    {
        $alertas = [];
        
        // Stock de Valores
        $alertas['stock_valores'] = $this->getAlertasStockValores();
        
        // Stock de Cheques
        $alertas['stock_cheques'] = $this->getAlertasStockCheques();
        
        // Certificados
        $alertas['certificados'] = $this->getAlertasCertificados();
        
        // Caja Chica
        $alertas['caja_chica'] = $this->getAlertasCajaChica();
        
        return $alertas;
    }

    /**
     * Alertas de Stock de Valores
     */
    private function getAlertasStockValores(): array
    {
        $todosTipos = TipoLibreta::all();
        $libretasEnStock = LibretaValor::with('tipoLibreta')
            ->where('estado', 'en_stock')
            ->get();
        
        $stockPorTipo = [];
        foreach ($todosTipos as $tipo) {
            $stockPorTipo[$tipo->id] = [
                'tipo' => $tipo,
                'stock_total' => 0,
            ];
        }
        
        foreach ($libretasEnStock as $libreta) {
            if ($libreta->tipoLibreta) {
                $tipoId = $libreta->tipoLibreta->id;
                $proximoRecibo = $libreta->proximo_recibo_disponible ?? $libreta->numero_inicial;
                if ($proximoRecibo == 0) {
                    $proximoRecibo = $libreta->numero_inicial;
                }
                $stockLibreta = $libreta->numero_final - $proximoRecibo + 1;
                if (isset($stockPorTipo[$tipoId])) {
                    $stockPorTipo[$tipoId]['stock_total'] += max(0, $stockLibreta);
                }
            }
        }
        
        $alertas = [];
        foreach ($stockPorTipo as $info) {
            $stockTotal = $info['stock_total'];
            $stockMinimo = $info['tipo']->stock_minimo_recibos ?? 0;
            $nombreTipo = $info['tipo']->nombre;
            
            if ($stockTotal <= 0) {
                $alertas[] = [
                    'id' => 'stock_valor_' . $info['tipo']->id,
                    'tipo' => 'danger',
                    'categoria' => 'stock',
                    'prioridad' => 1,
                    'icono' => 'fa-barcode',
                    'titulo' => 'Sin Stock',
                    'mensaje' => "Las libretas de {$nombreTipo} se han quedado sin stock.",
                    'accion' => [
                        'route' => 'tesoreria.valores.index',
                        'label' => 'Ver',
                    ],
                ];
            } elseif ($stockMinimo > 0 && $stockTotal <= $stockMinimo) {
                $alertas[] = [
                    'id' => 'stock_valor_' . $info['tipo']->id,
                    'tipo' => 'warning',
                    'categoria' => 'stock',
                    'prioridad' => 2,
                    'icono' => 'fa-barcode',
                    'titulo' => 'Stock Bajo',
                    'mensaje' => "Las libretas de {$nombreTipo} tienen stock bajo ({$stockTotal} recibos disponibles). Mínimo: {$stockMinimo}.",
                    'accion' => [
                        'route' => 'tesoreria.valores.index',
                        'label' => 'Ver',
                    ],
                ];
            }
        }
        
        return [
            'alertas' => $alertas,
            'contador' => count($alertas),
            'prioridad_maxima' => !empty($alertas) ? min(array_column($alertas, 'prioridad')) : null,
        ];
    }

    /**
     * Alertas de Stock de Cheques
     */
    private function getAlertasStockCheques(): array
    {
        $cuentasBancarias = CuentaBancaria::where('activa', true)
            ->with('banco')
            ->get();
        
        $alertas = [];
        
        foreach ($cuentasBancarias as $cuenta) {
            $stockCheques = Cheque::where('cuenta_bancaria_id', $cuenta->id)
                ->where('estado', 'disponible')
                ->whereNull('deleted_at')
                ->count();
            
            $nombreCuenta = $cuenta->banco ? $cuenta->banco->nombre . ' - ' . $cuenta->numero_cuenta : 'Cuenta ' . $cuenta->numero_cuenta;
            
            if ($stockCheques <= 0) {
                $alertas[] = [
                    'id' => 'cheque_' . $cuenta->id,
                    'tipo' => 'danger',
                    'categoria' => 'cheques',
                    'prioridad' => 1,
                    'icono' => 'fa-money-check',
                    'titulo' => 'Sin Cheques',
                    'mensaje' => "La cuenta bancaria {$nombreCuenta} no tiene cheques disponibles.",
                    'accion' => [
                        'route' => 'tesoreria.cheques.index',
                        'label' => 'Ver',
                    ],
                ];
            } elseif ($stockCheques < 50) {
                $alertas[] = [
                    'id' => 'cheque_' . $cuenta->id,
                    'tipo' => 'warning',
                    'categoria' => 'cheques',
                    'prioridad' => 2,
                    'icono' => 'fa-money-check',
                    'titulo' => 'Stock Bajo',
                    'mensaje' => "La cuenta bancaria {$nombreCuenta} tiene stock bajo de cheques ({$stockCheques} disponibles). Mínimo recomendado: 50.",
                    'accion' => [
                        'route' => 'tesoreria.cheques.index',
                        'label' => 'Ver',
                    ],
                ];
            }
        }
        
        return [
            'alertas' => $alertas,
            'contador' => count($alertas),
            'prioridad_maxima' => !empty($alertas) ? min(array_column($alertas, 'prioridad')) : null,
        ];
    }

    /**
     * Alertas de Certificados de Residencia
     */
    private function getAlertasCertificados(): array
    {
        $fechaLimite = Carbon::now()->subDays(45);
        
        $certificadosVencidos = CertificadoResidencia::where('estado', 'emitido')
            ->where('fecha_recibido', '<', $fechaLimite)
            ->count();
        
        $alertas = [];
        
        if ($certificadosVencidos > 0) {
            $alertas[] = [
                'id' => 'certificado_vencido',
                'tipo' => 'danger',
                'categoria' => 'certificados',
                'prioridad' => 1,
                'icono' => 'fa-file-alt',
                'titulo' => 'Certificados Vencidos',
                'mensaje' => "Hay {$certificadosVencidos} certificados de residencia vencidos que requieren atención.",
                'accion' => [
                    'route' => 'tesoreria.certificados.index',
                    'label' => 'Ver',
                ],
            ];
        }
        
        return [
            'alertas' => $alertas,
            'contador' => count($alertas),
            'prioridad_maxima' => !empty($alertas) ? min(array_column($alertas, 'prioridad')) : null,
        ];
    }

    /**
     * Alertas de Caja Chica
     */
    private function getAlertasCajaChica(): array
    {
        $alertas = [];
        
        // Pendientes sin pagar
        // Adaptado al modelo real tes_cch_pendientes
        $pendientesSinPagar = Pendiente::whereNull('relDependencia')->count(); // Ajustar lógica según modelo real
        
        if ($pendientesSinPagar > 0) {
            $alertas[] = [
                'id' => 'pendiente_caja_chica',
                'tipo' => 'warning',
                'categoria' => 'caja_chica',
                'prioridad' => 2,
                'icono' => 'fa-exclamation-triangle',
                'titulo' => 'Pendientes sin Pagar',
                'mensaje' => "Hay {$pendientesSinPagar} pendientes de caja chica a revisar.",
                'accion' => [
                    'route' => 'tesoreria.caja-chica.index',
                    'label' => 'Ver',
                ],
            ];
        }
        
        // Pagos sin comprobante
        // Adaptado al modelo real tes_cch_pagos
        $pagosSinComprobante = Pago::whereNull('fechaIngresoPagos')->count();
        
        if ($pagosSinComprobante > 0) {
            $alertas[] = [
                'id' => 'pago_sin_comprobante',
                'tipo' => 'warning',
                'categoria' => 'caja_chica',
                'prioridad' => 2,
                'icono' => 'fa-receipt',
                'titulo' => 'Pagos sin Comprobante',
                'mensaje' => "Hay {$pagosSinComprobante} pagos de caja chica sin comprobante cargado.",
                'accion' => [
                    'route' => 'tesoreria.caja-chica.index',
                    'label' => 'Ver',
                ],
            ];
        }
        
        return [
            'alertas' => $alertas,
            'contador' => count($alertas),
            'prioridad_maxima' => !empty($alertas) ? min(array_column($alertas, 'prioridad')) : null,
        ];
    }



    /**
     * Invalida el cache de alertas
     */
    public function invalidateCache(): void
    {
        Cache::forget('dashboard_alerts');
    }

    /**
     * Obtiene alertas críticas específicas
     */
    public function getCriticalAlerts(): array
    {
        return $this->getAlertasCriticas();
    }

    /**
     * Obtiene alertas colapsables específicas por categoría
     */
    public function getCollapsibleAlerts(string $categoria): array
    {
        $colapsables = $this->getAlertasColapsables();
        return $colapsables[$categoria] ?? ['alertas' => [], 'contador' => 0, 'prioridad_maxima' => null];
    }

    /**
     * Obtiene el conteo total de alertas
     */
    public function getTotalAlertsCount(): int
    {
        $alerts = $this->getAllAlerts();
        return $alerts['criticas']['contador'] + $alerts['total_colapsables'];
    }

    /**
     * Obtiene alertas por tipo de icono
     */
    public function getAlertsByIcon(string $icono): array
    {
        $alerts = $this->getAllAlerts();
        $allAlerts = array_merge(
            $alerts['criticas']['alertas'],
            ...array_column($alerts['colapsables'], 'alertas')
        );
        
        return array_filter($allAlerts, function ($alerta) use ($icono) {
            return $alerta['icono'] === $icono;
        });
    }

    /**
     * Obtiene alertas por prioridad
     */
    public function getAlertsByPriority(int $prioridad): array
    {
        $alerts = $this->getAllAlerts();
        $allAlerts = array_merge(
            $alerts['criticas']['alertas'],
            ...array_column($alerts['colapsables'], 'alertas')
        );
        
        return array_filter($allAlerts, function ($alerta) use ($prioridad) {
            return $alerta['prioridad'] === $prioridad;
        });
    }
}