<?php

namespace App\Http\Controllers\Tesoreria\Valores;

use App\Http\Controllers\Controller;
use App\Models\Tesoreria\Valores\Valor;
use App\Models\Tesoreria\Valores\ValorConcepto;
use App\Models\Tesoreria\Valores\ValorEntrada;
use App\Models\Tesoreria\Valores\ValorSalida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ValorController extends Controller
{
    public function __construct()
    {
        // Aplicar middlewares de autenticación y permisos si están configurados
        $this->middleware('auth');

        // Si estás usando Spatie Permissions, descomenta las siguientes líneas:
        // $this->middleware('permission:tesoreria.valores.index')->only('index');
        // $this->middleware('permission:tesoreria.valores.create')->only(['create', 'store']);
        // $this->middleware('permission:tesoreria.valores.edit')->only(['edit', 'update']);
        // $this->middleware('permission:tesoreria.valores.delete')->only('destroy');
    }

    /**
     * Mostrar la lista de valores
     */
    public function index()
    {
        // Solo permitir acceso a administradores mientras el módulo está en desarrollo
        if (!auth()->user()->hasRole('administrador')) {
            abort(403, 'Acceso denegado. Solo los administradores pueden acceder al módulo de Tesorería mientras está en desarrollo.');
        }

        return view('tesoreria.valores.index')->with([
            'component' => 'tesoreria.valores.stock',
        ]);
    }

    /**
     * Mostrar la gestión de conceptos
     */
    public function conceptos()
    {
        // Solo permitir acceso a administradores mientras el módulo está en desarrollo
        if (!auth()->user()->hasRole('administrador')) {
            abort(403, 'Acceso denegado. Solo los administradores pueden acceder al módulo de Tesorería mientras está en desarrollo.');
        }

        return view('tesoreria.valores.conceptos');
    }

    /**
     * Mostrar la gestión de entradas
     */
    public function entradas()
    {
        // Solo permitir acceso a administradores mientras el módulo está en desarrollo
        if (!auth()->user()->hasRole('administrador')) {
            abort(403, 'Acceso denegado. Solo los administradores pueden acceder al módulo de Tesorería mientras está en desarrollo.');
        }

        return view('tesoreria.valores.entradas');
    }

    /**
     * Mostrar la gestión de salidas
     */
    public function salidas()
    {
        // Solo permitir acceso a administradores mientras el módulo está en desarrollo
        if (!auth()->user()->hasRole('administrador')) {
            abort(403, 'Acceso denegado. Solo los administradores pueden acceder al módulo de Tesorería mientras está en desarrollo.');
        }

        return view('tesoreria.valores.salidas');
    }

    /**
     * Mostrar el resumen de stock
     */
    public function stock()
    {
        // Solo permitir acceso a administradores mientras el módulo está en desarrollo
        if (!auth()->user()->hasRole('administrador')) {
            abort(403, 'Acceso denegado. Solo los administradores pueden acceder al módulo de Tesorería mientras está en desarrollo.');
        }

        return view('tesoreria.valores.stock');
    }

    /**
     * API: Obtener resumen de stock para un valor específico
     */
    public function getStockResumen($valorId)
    {
        try {
            $valor = Valor::with(['conceptos.usosActivos'])->findOrFail($valorId);
            $resumen = $valor->getResumenStock();

            $conceptosDetalle = $valor->conceptosActivos->map(function ($concepto) {
                return [
                    'id' => $concepto->id,
                    'concepto' => $concepto->concepto,
                    'resumen' => $concepto->getResumenUso()
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'valor' => $valor,
                    'resumen' => $resumen,
                    'conceptos' => $conceptosDetalle
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el resumen de stock.'
            ], 500);
        }
    }

    /**
     * API: Validar disponibilidad de rango de recibos
     */
    public function validarRango(Request $request)
    {
        $request->validate([
            'valores_id' => 'required|exists:tes_valores,id',
            'desde' => 'required|integer|min:1',
            'hasta' => 'required|integer|min:1',
            'excluir_id' => 'nullable|integer'
        ]);

        $valorId = $request->valores_id;
        $desde = $request->desde;
        $hasta = $request->hasta;
        $excluirId = $request->excluir_id;

        if ($desde > $hasta) {
            return response()->json([
                'success' => false,
                'message' => 'El número final debe ser mayor o igual al número inicial.'
            ]);
        }

        // Verificar solapamiento en entradas
        $solapamientoEntradas = ValorEntrada::where('valores_id', $valorId)
            ->when($excluirId, function ($query) use ($excluirId) {
                $query->where('id', '!=', $excluirId);
            })
            ->where(function ($query) use ($desde, $hasta) {
                $query->whereBetween('desde', [$desde, $hasta])
                    ->orWhereBetween('hasta', [$desde, $hasta])
                    ->orWhere(function ($q) use ($desde, $hasta) {
                        $q->where('desde', '<=', $desde)
                            ->where('hasta', '>=', $hasta);
                    });
            })
            ->exists();

        if ($solapamientoEntradas) {
            return response()->json([
                'success' => false,
                'message' => 'El rango se solapa con una entrada existente.',
                'tipo' => 'entrada'
            ]);
        }

        // Verificar solapamiento en salidas
        $solapamientoSalidas = ValorSalida::where('valores_id', $valorId)
            ->when($excluirId, function ($query) use ($excluirId) {
                $query->where('id', '!=', $excluirId);
            })
            ->where(function ($query) use ($desde, $hasta) {
                $query->whereBetween('desde', [$desde, $hasta])
                    ->orWhereBetween('hasta', [$desde, $hasta])
                    ->orWhere(function ($q) use ($desde, $hasta) {
                        $q->where('desde', '<=', $desde)
                            ->where('hasta', '>=', $hasta);
                    });
            })
            ->exists();

        if ($solapamientoSalidas) {
            return response()->json([
                'success' => false,
                'message' => 'El rango se solapa con una salida existente.',
                'tipo' => 'salida'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rango disponible.'
        ]);
    }

    /**
     * API: Obtener estadísticas generales
     */
    public function estadisticas()
    {
        try {
            $estadisticas = [
                'total_valores' => Valor::activos()->count(),
                'total_conceptos' => ValorConcepto::activos()->count(),
                'total_entradas_mes' => ValorEntrada::whereMonth('fecha', now()->month)
                    ->whereYear('fecha', now()->year)
                    ->count(),
                'total_salidas_mes' => ValorSalida::whereMonth('fecha', now()->month)
                    ->whereYear('fecha', now()->year)
                    ->count(),
                'valores_con_stock_bajo' => $this->getValoresStockBajo(),
                'recibos_en_uso' => $this->getTotalRecibosEnUso(),
                'ultima_entrada' => ValorEntrada::with('valor')
                    ->orderBy('fecha', 'desc')
                    ->first(),
                'ultima_salida' => ValorSalida::with(['valor', 'concepto'])
                    ->orderBy('fecha', 'desc')
                    ->first()
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas.'
            ], 500);
        }
    }

    /**
     * API: Exportar datos de stock
     */
    public function exportarStock()
    {
        try {
            $valores = Valor::activos()
                ->with(['conceptos.usosActivos'])
                ->get()
                ->map(function ($valor) {
                    $resumen = $valor->getResumenStock();
                    return [
                        'nombre' => $valor->nombre,
                        'tipo_valor' => $valor->tipo_valor_texto,
                        'recibos_libreta' => $valor->recibos,
                        'valor_unitario' => $valor->valor ? '$' . number_format($valor->valor, 2) : 'N/A',
                        'stock_total' => $resumen['stock_total'],
                        'libretas_completas' => $resumen['libretas_completas'],
                        'recibos_en_uso' => $resumen['recibos_en_uso'],
                        'recibos_disponibles' => $resumen['recibos_disponibles'],
                        'conceptos_activos' => $valor->conceptosActivos->count()
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $valores,
                'fecha_exportacion' => now()->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar los datos.'
            ], 500);
        }
    }

    /**
     * Obtener valores con stock bajo (menos de 2 libretas completas)
     */
    private function getValoresStockBajo()
    {
        return Valor::activos()
            ->get()
            ->filter(function ($valor) {
                return $valor->getLibretasCompletas() < 2;
            })
            ->count();
    }

    /**
     * Obtener total de recibos en uso
     */
    private function getTotalRecibosEnUso()
    {
        return DB::table('tes_val_usos')
            ->where('activo', true)
            ->sum('recibos_disponibles');
    }
}
