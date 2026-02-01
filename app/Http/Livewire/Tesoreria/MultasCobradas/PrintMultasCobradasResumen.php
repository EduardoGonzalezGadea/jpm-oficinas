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
        $this->fechaDesde = $fechaDesde;
        $this->fechaHasta = $fechaHasta;
        $this->isPdf = request()->query('pdf') == 1;

        $data = $service->getResumenData($fechaDesde, $fechaHasta);

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

        // Procesar medios de pago y crear subtotales
        $subtotales = [];
        $combinaciones = [];
        $subtotales_combinados = []; // Para guardar los subtotales de cada medio dentro de las combinaciones

        foreach ($registros_pago as $item) {
            $forma_pago = $item->forma_pago ?: 'SIN DATOS';
            $partes = explode('/', $forma_pago);

            // Si solo hay un medio de pago
            if (count($partes) == 1) {
                $medio = trim(explode(':', $partes[0])[0]);
                if (!isset($subtotales[$medio])) {
                    $subtotales[$medio] = 0;
                }
                $subtotales[$medio] += $item->monto;
            } else {
                // Si hay múltiples medios de pago combinados
                $medios_con_valores = [];
                $nombre_medios = [];

                foreach ($partes as $parte) {
                    $datos = explode(':', trim($parte));
                    $nombre_medio = trim($datos[0]);
                    $nombre_medios[] = $nombre_medio;

                    // Extraer el valor específico si existe
                    if (isset($datos[1])) {
                        $valor_str = trim($datos[1]);
                        // Limpiar formato uruguayo: eliminar puntos (miles) y reemplazar coma (decimal) por punto
                        $valor_limpio = str_replace('.', '', $valor_str); // Eliminar separador de miles
                        $valor_limpio = str_replace(',', '.', $valor_limpio); // Reemplazar decimal

                        if (is_numeric($valor_limpio)) {
                            $valor = floatval($valor_limpio);
                        } else {
                            // Si aún no es numérico, dividir equitativamente como fallback
                            $valor = $item->monto / count($partes);
                        }
                    } else {
                        // Sin valor, dividir equitativamente
                        $valor = $item->monto / count($partes);
                    }

                    $medios_con_valores[$nombre_medio] = $valor;
                }

                $nombre_combinado = implode(' / ', $nombre_medios);

                // Acumular en subtotales generales
                foreach ($medios_con_valores as $medio => $valor) {
                    if (!isset($subtotales[$medio])) {
                        $subtotales[$medio] = 0;
                    }
                    $subtotales[$medio] += $valor;

                    // Guardar también en subtotales de combinados
                    if (!isset($subtotales_combinados[$nombre_combinado])) {
                        $subtotales_combinados[$nombre_combinado] = [];
                    }
                    if (!isset($subtotales_combinados[$nombre_combinado][$medio])) {
                        $subtotales_combinados[$nombre_combinado][$medio] = 0;
                    }
                    $subtotales_combinados[$nombre_combinado][$medio] += $valor;
                }

                // Guardar el total de la combinación (suma de los valores específicos)
                if (!isset($combinaciones[$nombre_combinado])) {
                    $combinaciones[$nombre_combinado] = 0;
                }
                $combinaciones[$nombre_combinado] += array_sum($medios_con_valores);
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
}
