<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Modulo;
use App\Models\Tesoreria\LibretaValor;
use App\Models\Tesoreria\TipoLibreta;
use App\Models\Tesoreria\Pendiente;
use App\Models\Tesoreria\Pago;
use App\Models\Tesoreria\CuentaBancaria;
use App\Models\Tesoreria\Cheque;
use Carbon\Carbon;

class PanelController extends Controller
{
    public function index()
    {
        $usuario = auth()->user();

        // Estadísticas para el panel
        $estadisticas = [
            'total_usuarios' => User::activos()->count(),
            'total_modulos' => Modulo::activos()->count(),
            'usuarios_tesoreria' => User::activos()->whereHas('modulo', function ($q) {
                $q->where('nombre', 'Tesorería');
            })->count(),
        ];

        // --- ALERTAS ---

        // 1. Alertas de Stock (Valores)
        // Obtener TODOS los tipos de libreta configurados (SoftDeletes automáticamente excluye borrados)
        $todosTipos = TipoLibreta::all();
        
        // Obtener libretas que están "en_stock" (disponibles para ser entregadas)
        $libretasEnStock = LibretaValor::with('tipoLibreta')
            ->where('estado', 'en_stock')
            ->get();

        // Agrupar por tipo y calcular stock total disponible
        $stockPorTipo = [];
        
        // Inicializar con todos los tipos
        foreach ($todosTipos as $tipo) {
            $stockPorTipo[$tipo->id] = [
                'tipo' => $tipo,
                'stock_total' => 0,
            ];
        }
        
        // Calcular stock de las libretas en_stock
        foreach ($libretasEnStock as $libreta) {
            if ($libreta->tipoLibreta) {
                $tipoId = $libreta->tipoLibreta->id;
                
                // Calcular stock disponible de esta libreta
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

        $alertasStock = collect();

        // Generar alertas para cada tipo
        foreach ($stockPorTipo as $info) {
            $stockTotal = $info['stock_total'];
            $stockMinimo = $info['tipo']->stock_minimo_recibos ?? 0;
            $nombreTipo = $info['tipo']->nombre;

            if ($stockTotal <= 0) {
                $alertasStock->push([
                    'tipo' => 'danger',
                    'mensaje' => "Las libretas de <strong>{$nombreTipo}</strong> se han quedado sin stock.",
                ]);
            } elseif ($stockMinimo > 0 && $stockTotal <= $stockMinimo) {
                $alertasStock->push([
                    'tipo' => 'warning',
                    'mensaje' => "Las libretas de <strong>{$nombreTipo}</strong> tienen stock bajo ({$stockTotal} recibos disponibles). Mínimo: {$stockMinimo}.",
                ]);
            }
        }

        // 2. Alertas de Stock (Cheques)
        $cuentasBancarias = CuentaBancaria::where('activa', true)
            ->with('banco')
            ->get();
        
        $alertasCheques = collect();
        
        foreach ($cuentasBancarias as $cuenta) {
            // Contar cheques disponibles para esta cuenta
            $stockCheques = Cheque::where('cuenta_bancaria_id', $cuenta->id)
                ->where('estado', 'disponible')
                ->whereNull('deleted_at')
                ->count();
            
            $nombreCuenta = $cuenta->banco ? $cuenta->banco->nombre . ' - ' . $cuenta->numero_cuenta : 'Cuenta ' . $cuenta->numero_cuenta;
            
            if ($stockCheques <= 0) {
                $alertasCheques->push([
                    'tipo' => 'danger',
                    'mensaje' => "La cuenta bancaria <strong>{$nombreCuenta}</strong> no tiene cheques disponibles.",
                ]);
            } elseif ($stockCheques < 50) {
                $alertasCheques->push([
                    'tipo' => 'warning',
                    'mensaje' => "La cuenta bancaria <strong>{$nombreCuenta}</strong> tiene stock bajo de cheques ({$stockCheques} disponibles). Mínimo recomendado: 50.",
                ]);
            }
        }

        // 3. Alertas de Caja Chica (Mes Anterior)
        $fechaAnterior = Carbon::now()->subMonth();
        $mesAnterior = $fechaAnterior->month;
        $anioAnterior = $fechaAnterior->year;

        // Pendientes del mes anterior con saldo > 0
        $pendientesAnteriores = Pendiente::whereMonth('fechaPendientes', $mesAnterior)
            ->whereYear('fechaPendientes', $anioAnterior)
            ->with('movimientos')
            ->get()
            ->filter(function ($pendiente) {
                // Lógica de cálculo de saldo (replicada de Index.php)
                $tot_rendido = $pendiente->movimientos->sum('rendido');
                $tot_reintegrado = $pendiente->movimientos->sum('reintegrado');
                $tot_recuperado = $pendiente->movimientos->sum('recuperado');

                $tot_rendido_reintegrado = $tot_rendido + $tot_reintegrado;
                $saldo = 0;

                if ($tot_rendido_reintegrado > $pendiente->montoPendientes) {
                    $saldo = $tot_rendido - $tot_recuperado;
                } else {
                    $saldo = $pendiente->montoPendientes - $tot_reintegrado - $tot_recuperado;
                }

                return $saldo > 0; // Solo si queda saldo pendiente
            });

        // Pagos del mes anterior con saldo > 0
        $pagosAnteriores = Pago::whereMonth('fechaEgresoPagos', $mesAnterior)
            ->whereYear('fechaEgresoPagos', $anioAnterior)
            ->get()
            ->filter(function ($pago) {
                return $pago->saldo_pagos > 0;
            });

        return view('panel.index', compact('usuario', 'estadisticas', 'alertasStock', 'alertasCheques', 'pendientesAnteriores', 'pagosAnteriores'));
    }
}