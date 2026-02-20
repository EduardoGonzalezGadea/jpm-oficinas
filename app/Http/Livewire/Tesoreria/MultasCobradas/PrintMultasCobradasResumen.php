<?php

namespace App\Http\Livewire\Tesoreria\MultasCobradas;

use App\Services\Tesoreria\MultasNormalizationService;
use Livewire\Component;

class PrintMultasCobradasResumen extends Component
{
    public $fechaDesde;
    public $fechaHasta;
    public $itemsGrouped;
    public $itemsUnclassified;
    public $totalGeneral;
    public $totalCantidad;
    public $totalesPorMedio;
    public $isPdf = false;

    public function mount($fechaDesde, $fechaHasta, MultasNormalizationService $service)
    {
        $this->fechaDesde = $this->normalizarFechaInput($fechaDesde) ?? $fechaDesde;
        $this->fechaHasta = $this->normalizarFechaInput($fechaHasta) ?? $fechaHasta;
        $this->isPdf = request()->query('pdf') == 1;

        $data = $service->getResumenData($this->fechaDesde, $this->fechaHasta);

        $this->itemsGrouped = $data['grouped'];
        $this->itemsUnclassified = $data['unclassified'];

        $totalGrouped = $this->itemsGrouped->sum('importe_total');
        $totalUnclassified = $this->itemsUnclassified->sum('importe');

        $this->totalGeneral = $totalGrouped + $totalUnclassified;

        // Calculate total quantity for percentage
        $this->totalCantidad = $this->itemsGrouped->sum('cantidad') + $this->itemsUnclassified->count();

        $registros_pago = \App\Models\Tesoreria\TesMultasCobradas::whereDate('fecha', '>=', $this->fechaDesde)
            ->whereDate('fecha', '<=', $this->fechaHasta)
            ->select('forma_pago', 'monto')
            ->get();

        $medioPagoService = new \App\Services\Tesoreria\MedioPagoService();

        // Procesar medios de pago y crear subtotales
        $subtotales = [];
        $combinaciones = [];
        $subtotales_combinados = []; // Para guardar los subtotales de cada medio dentro de las combinaciones

        foreach ($registros_pago as $item) {
            $forma_pago = $item->forma_pago ?: 'SIN DATOS';
            $partes = $medioPagoService->parsearMedioPago($forma_pago);

            // Si solo hay un medio de pago
            if (count($partes) == 1) {
                $medio = $medioPagoService->obtenerNombreReal($partes[0]['nombre']);
                if (!isset($subtotales[$medio])) {
                    $subtotales[$medio] = 0;
                }
                $subtotales[$medio] += $item->monto;
            } else {
                // Si hay múltiples medios de pago combinados
                $medios_con_valores = $medioPagoService->calcularValoresMedios($forma_pago, $item->monto);

                $nombresReal = array_map(fn($m) => $medioPagoService->obtenerNombreReal($m['nombre']), $medios_con_valores);
                sort($nombresReal);
                $nombre_combinado = implode(' / ', $nombresReal);

                // Acumular en subtotales generales
                foreach ($medios_con_valores as $medio) {
                    $medioNombre = $medioPagoService->obtenerNombreReal($medio['nombre']);
                    $valorMedio = $medio['valor'];

                    if (!isset($subtotales[$medioNombre])) {
                        $subtotales[$medioNombre] = 0;
                    }
                    $subtotales[$medioNombre] += $valorMedio;

                    // Guardar también en subtotales de combinados
                    if (!isset($subtotales_combinados[$nombre_combinado])) {
                        $subtotales_combinados[$nombre_combinado] = [];
                    }
                    if (!isset($subtotales_combinados[$nombre_combinado][$medioNombre])) {
                        $subtotales_combinados[$nombre_combinado][$medioNombre] = 0;
                    }
                    $subtotales_combinados[$nombre_combinado][$medioNombre] += $valorMedio;
                }

                // Guardar el total de la combinación (suma de los valores específicos)
                if (!isset($combinaciones[$nombre_combinado])) {
                    $combinaciones[$nombre_combinado] = 0;
                }
                $combinaciones[$nombre_combinado] += array_sum(array_column($medios_con_valores, 'valor'));
            }
        }

        // Construir el resultado final
        $this->totalesPorMedio = collect();

        // Agregar subtotales individuales
        foreach ($subtotales as $medio => $total) {
            $this->totalesPorMedio->push((object)[
                'forma_pago' => $medio,
                'total' => $total,
                'es_subtotal' => true,
                'es_combinacion' => false,
                'es_subtotal_combinado' => false
            ]);
        }

        // Agregar subtotales de combinaciones y totales
        foreach ($combinaciones as $combinacion => $total) {
            // Primero agregar los subtotales de cada medio en la combinación
            if (isset($subtotales_combinados[$combinacion])) {
                foreach ($subtotales_combinados[$combinacion] as $medio => $subtotal) {
                    $this->totalesPorMedio->push((object)[
                        'forma_pago' => $medio,
                        'total' => $subtotal,
                        'es_subtotal' => false,
                        'es_combinacion' => false,
                        'es_subtotal_combinado' => true,
                        'combinacion_padre' => $combinacion
                    ]);
                }
            }

            // Luego agregar el total de la combinación
            $this->totalesPorMedio->push((object)[
                'forma_pago' => $combinacion,
                'total' => $total,
                'es_subtotal' => false,
                'es_combinacion' => true,
                'es_subtotal_combinado' => false
            ]);
        }
    }

    public function render()
    {
        return view('livewire.tesoreria.multas-cobradas.print-multas-cobradas-resumen')
            ->layout('layouts.print');
    }

    /**
     * Normaliza fechas de entrada (DD/MM/YYYY o YYYY-MM-DD) a YYYY-MM-DD.
     */
    private function normalizarFechaInput(?string $fecha): ?string
    {
        if (!$fecha) {
            return null;
        }

        $fecha = trim($fecha);

        try {
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fecha)) {
                return \Carbon\Carbon::createFromFormat('d/m/Y', $fecha)->format('Y-m-d');
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                return \Carbon\Carbon::createFromFormat('Y-m-d', $fecha)->format('Y-m-d');
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }
}
