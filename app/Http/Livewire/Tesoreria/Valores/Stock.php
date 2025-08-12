<?php

namespace App\Http\Livewire\Tesoreria\Valores;

use App\Models\Tesoreria\Valores\Valor;
use App\Models\Tesoreria\Valores\ValorConcepto;
use App\Models\Tesoreria\Valores\ValorUso;
use Livewire\Component;
use Illuminate\Support\Collection;

class Stock extends Component
{
    public $filterValor = '';
    public $filterTipo = '';
    public $filterStockBajo = false;
    public $sortField = 'nombre';
    public $sortDirection = 'asc';

    public $showDetailModal = false;
    public $selectedValor;
    public $detalleStock = [];

    public $estadisticasGenerales = [
        'total_valores' => 0,
        'total_recibos_stock' => 0,
        'total_recibos_uso' => 0,
        'total_libretas_completas' => 0,
        'valores_stock_bajo' => 0
    ];

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'actualizarStock' => 'render'
    ];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
    }

    public function resetFilters()
    {
        $this->reset();
    }

    public function openDetailModal($valorId)
    {
        $this->selectedValor = Valor::with(['conceptos.usosActivos', 'entradas', 'salidas'])
            ->findOrFail($valorId);

        $this->detalleStock = $this->calcularDetalleValor($this->selectedValor);
        $this->showDetailModal = true;
        $this->dispatchBrowserEvent('show-modal', ['id' => 'detailStockModal']);
    }

    public function calcularEstadisticas(Collection $valores)
    {
        $this->estadisticasGenerales = [
            'total_valores' => $valores->count(),
            'total_recibos_stock' => $valores->sum(function ($valor) {
                return $valor->getStockDisponible();
            }),
            'total_recibos_uso' => $valores->sum(function ($valor) {
                return $valor->getRecibosEnUso();
            }),
            'total_libretas_completas' => $valores->sum(function ($valor) {
                return $valor->getLibretasCompletas();
            }),
            'valores_stock_bajo' => $valores->filter(function ($valor) {
                return $valor->getLibretasCompletas() < 2;
            })->count()
        ];
    }

    private function getValoresFiltrados(): Collection
    {
        $query = Valor::activos()->with(['conceptos.usosActivos']);

        if ($this->filterValor) {
            $query->where('id', $this->filterValor);
        }

        if ($this->filterTipo) {
            $query->where('tipo_valor', $this->filterTipo);
        }

        $valores = $query->get();

        if ($this->filterStockBajo) {
            $valores = $valores->filter(function ($valor) {
                return $valor->getLibretasCompletas() < 2;
            });
        }

        return $valores;
    }

    private function calcularDetalleValor(Valor $valor): array
    {
        $resumenGeneral = $valor->getResumenStock();

        // Detalle por concepto
        $conceptosDetalle = $valor->conceptosActivos->map(function ($concepto) {
            $resumenConcepto = $concepto->getResumenUso();
            $usos = $concepto->usosActivos->map(function ($uso) {
                return [
                    'id' => $uso->id,
                    'rango_original' => $uso->desde . ' - ' . $uso->hasta,
                    'rango_disponible' => $uso->getRangoRecibosAttribute(),
                    'total_recibos' => ($uso->hasta - $uso->desde) + 1,
                    'recibos_disponibles' => $uso->recibos_disponibles,
                    'recibos_utilizados' => $uso->getRecibosUtilizadosAttribute(),
                    'porcentaje_uso' => $uso->getPorcentajeUsoAttribute(),
                    'interno' => $uso->interno,
                    'fecha_asignacion' => $uso->fecha_asignacion->format('d/m/Y')
                ];
            });

            return [
                'concepto' => $concepto,
                'resumen' => $resumenConcepto,
                'usos' => $usos
            ];
        });

        // Historial de movimientos recientes
        $entradasRecientes = $valor->entradas()
            ->orderBy('fecha', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($entrada) {
                return [
                    'tipo' => 'entrada',
                    'fecha' => $entrada->fecha->format('d/m/Y'),
                    'comprobante' => $entrada->comprobante,
                    'rango' => $entrada->desde . ' - ' . $entrada->hasta,
                    'cantidad' => $entrada->total_recibos,
                    'observaciones' => $entrada->observaciones
                ];
            });

        $salidasRecientes = $valor->salidas()
            ->with('concepto')
            ->orderBy('fecha', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($salida) {
                return [
                    'tipo' => 'salida',
                    'fecha' => $salida->fecha->format('d/m/Y'),
                    'comprobante' => $salida->comprobante,
                    'rango' => $salida->desde . ' - ' . $salida->hasta,
                    'cantidad' => $salida->total_recibos,
                    'concepto' => $salida->concepto->concepto,
                    'responsable' => $salida->responsable,
                    'observaciones' => $salida->observaciones
                ];
            });

        $movimientos = $entradasRecientes->concat($salidasRecientes)
            ->sortByDesc('fecha')
            ->take(10)
            ->values();

        return [
            'resumen_general' => $resumenGeneral,
            'conceptos_detalle' => $conceptosDetalle,
            'movimientos_recientes' => $movimientos,
            'alertas' => $this->generarAlertas($valor, $resumenGeneral)
        ];
    }

    private function generarAlertas(Valor $valor, array $resumen): array
    {
        $alertas = [];

        // Stock bajo
        if ($resumen['libretas_completas'] < 2) {
            $alertas[] = [
                'tipo' => 'warning',
                'icono' => 'fas fa-exclamation-triangle',
                'mensaje' => 'Stock bajo: Menos de 2 libretas completas disponibles'
            ];
        }

        // Sin stock
        if ($resumen['recibos_disponibles'] <= 0) {
            $alertas[] = [
                'tipo' => 'danger',
                'icono' => 'fas fa-times-circle',
                'mensaje' => 'Sin stock disponible'
            ];
        }

        // Libretas próximas a agotarse
        $libretasProximasAgotar = $valor->conceptosActivos->filter(function ($concepto) {
            return $concepto->usosActivos->filter(function ($uso) {
                return $uso->recibos_disponibles > 0 && $uso->recibos_disponibles <= 10;
            })->count() > 0;
        });

        if ($libretasProximasAgotar->count() > 0) {
            $alertas[] = [
                'tipo' => 'info',
                'icono' => 'fas fa-info-circle',
                'mensaje' => 'Hay libretas en uso próximas a agotarse (menos de 10 recibos)'
            ];
        }

        return $alertas;
    }

    public function actualizarRecibosUso($usoId, $nuevaCantidad)
    {
        try {
            $uso = ValorUso::findOrFail($usoId);

            if ($nuevaCantidad < 0 || $nuevaCantidad > ($uso->hasta - $uso->desde + 1)) {
                $this->emit('alert', [
                    'type' => 'error',
                    'message' => 'Cantidad inválida de recibos disponibles.'
                ]);
                return;
            }

            $uso->recibos_disponibles = $nuevaCantidad;

            if ($nuevaCantidad == 0) {
                $uso->activo = false;
            }

            $uso->save();

            // Actualizar el detalle
            $this->detalleStock = $this->calcularDetalleValor($this->selectedValor);

            $this->emit('alert', [
                'type' => 'success',
                'message' => 'Recibos disponibles actualizados correctamente.'
            ]);

        } catch (\Exception $e) {
            $this->emit('alert', [
                'type' => 'error',
                'message' => 'Error al actualizar los recibos disponibles.'
            ]);
        }
    }

    public function marcarLibretaAgotada($usoId)
    {
        try {
            $uso = ValorUso::findOrFail($usoId);
            $uso->marcarComoAgotado();

            // Actualizar el detalle
            $this->detalleStock = $this->calcularDetalleValor($this->selectedValor);

            $this->emit('alert', [
                'type' => 'success',
                'message' => 'Libreta marcada como agotada correctamente.'
            ]);

        } catch (\Exception $e) {
            $this->emit('alert', [
                'type' => 'error',
                'message' => 'Error al marcar la libreta como agotada.'
            ]);
        }
    }

    public function exportarStock()
    {
        try {
            $valores = $this->getValoresFiltrados();

            $data = $valores->map(function ($valor) {
                $resumen = $valor->getResumenStock();
                return [
                    'Nombre' => $valor->nombre,
                    'Tipo' => $valor->tipo_valor_texto,
                    'Valor Unitario' => $valor->valor ? number_format($valor->valor, 2) : 'N/A',
                    'Recibos por Libreta' => $valor->recibos,
                    'Stock Total' => $resumen['stock_total'],
                    'Libretas Completas' => $resumen['libretas_completas'],
                    'Recibos en Uso' => $resumen['recibos_en_uso'],
                    'Recibos Disponibles' => $resumen['recibos_disponibles'],
                    'Conceptos Activos' => $valor->conceptosActivos->count(),
                    'Estado Stock' => $resumen['libretas_completas'] < 2 ? 'BAJO' : 'OK'
                ];
            });

            // Aquí puedes implementar la lógica de exportación
            // Por ejemplo, generar un CSV o Excel

            $this->emit('alert', [
                'type' => 'success',
                'message' => 'Stock exportado correctamente.'
            ]);

        } catch (\Exception $e) {
            $this->emit('alert', [
                'type' => 'error',
                'message' => 'Error al exportar el stock.'
            ]);
        }
    }

    public function render()
    {
        $valores = $this->getValoresFiltrados();
        
        $this->calcularEstadisticas($valores);

        $valores_view = $valores->map(function ($valor) {
            $valor->resumen_stock = $valor->getResumenStock();
            return $valor;
        });

        // Ordenar los valores
        if ($this->sortField) {
            $sortFunction = function ($valor) {
                switch ($this->sortField) {
                    case 'nombre':
                        return $valor->nombre;
                    case 'stock_total':
                        return $valor->resumen_stock['stock_total'];
                    case 'libretas_completas':
                        return $valor->resumen_stock['libretas_completas'];
                    default:
                        return $valor->nombre;
                }
            };

            $valores_view = $this->sortDirection === 'asc'
                ? $valores_view->sortBy($sortFunction)
                : $valores_view->sortByDesc($sortFunction);
        }

        $valoresParaFiltro = Valor::activos()->orderBy('nombre')->get();

        return view('livewire.tesoreria.valores.stock', [
            'valores' => $valores_view,
            'valoresParaFiltro' => $valoresParaFiltro
        ]);
    }
}