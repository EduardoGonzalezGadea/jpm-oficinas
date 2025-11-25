<?php

namespace App\Http\Livewire\Tesoreria\Valores\Reportes;

use App\Models\Tesoreria\LibretaValor;
use App\Models\Tesoreria\EntregaLibretaValor;
use App\Models\Tesoreria\Servicio;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Propiedades para filtros
    public $filtroTipoLibreta = '';
    public $filtroEstado = '';
    public $filtroServicio = '';
    public $search = '';

    // Propiedad para tipo de reporte
    public $reporteTipo = 'stock'; // stock, completas, en_uso
    
    // Historial de reportes de stock
    public $historialStock = [];
    public $mostrarHistorial = false;
    
    // Propiedades para paginación
    public $perPage = 10;
    
    protected $listeners = [
        'stockGenerado' => 'cargarHistorial',
        'refreshComponent' => '$refresh',
        'proximoReciboActualizado' => '$refresh'
    ];

    protected $queryString = [
        'reporteTipo' => ['except' => 'stock'],
        'filtroTipoLibreta' => ['except' => ''],
        'filtroEstado' => ['except' => ''],
        'filtroServicio' => ['except' => ''],
        'search' => ['except' => ''],
    ];
    
    public function mount()
    {
        $this->cargarHistorial();
    }
    
    public function toggleHistorial()
    {
        \Illuminate\Support\Facades\Log::info('Toggle historial clickeado. Estado anterior: ' . ($this->mostrarHistorial ? 'Abierto' : 'Cerrado'));
        $this->mostrarHistorial = !$this->mostrarHistorial;
        if ($this->mostrarHistorial) {
            $this->cargarHistorial();
        }
    }
    
    public function cargarHistorial()
    {
        $path = public_path('.docs/stock-valores');
        \Illuminate\Support\Facades\Log::info('Cargando historial desde: ' . $path);
        
        if (!\Illuminate\Support\Facades\File::exists($path)) {
            \Illuminate\Support\Facades\Log::info('El directorio no existe.');
            $this->historialStock = [];
            return;
        }
        
        $files = \Illuminate\Support\Facades\File::files($path);
        \Illuminate\Support\Facades\Log::info('Archivos encontrados: ' . count($files));
        
        // Ordenar por fecha de modificación descendente
        usort($files, function($a, $b) {
            return $b->getMTime() - $a->getMTime();
        });
        
        $this->historialStock = collect($files)->map(function($file) {
            return [
                'filename' => $file->getFilename(),
                'date' => $file->getMTime(),
                'size' => round($file->getSize() / 1024, 2)
            ];
        })->values()->all();
        
        \Illuminate\Support\Facades\Log::info('Historial actualizado en propiedad: ' . count($this->historialStock));
    }

    public function eliminarReporte($filename)
    {
        $path = public_path('.docs/stock-valores/' . $filename);
        
        if (\Illuminate\Support\Facades\File::exists($path)) {
            \Illuminate\Support\Facades\File::delete($path);
            $this->cargarHistorial();
            $this->dispatchBrowserEvent('swal', [
                'icon' => 'success',
                'title' => 'Eliminado',
                'text' => 'El reporte ha sido eliminado correctamente.',
                'toast' => true,
                'position' => 'top-end',
                'timer' => 3000
            ]);
        } else {
            $this->dispatchBrowserEvent('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'El archivo no existe.',
                'toast' => true,
                'position' => 'top-end',
                'timer' => 3000
            ]);
        }
    }    

    public function updating($property)
    {
        if (in_array($property, ['filtroTipoLibreta', 'filtroEstado', 'filtroServicio', 'search'])) {
            $this->resetPage();
        }
    }

    public function cambiarReporte($tipo)
    {
        \Illuminate\Support\Facades\Log::info('Cambiando reporte a: ' . $tipo);
        $this->reporteTipo = $tipo;
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->filtroTipoLibreta = '';
        $this->filtroEstado = '';
        $this->filtroServicio = '';
        $this->search = '';
        $this->resetPage();
    }
    
    /**
     * Actualizar el próximo recibo en la base de datos
     */
    public function actualizarProximoRecibo($libretaId, $valor)
    {
        \Log::info('actualizarProximoRecibo llamado', ['libretaId' => $libretaId, 'valor' => $valor]);
        
        try {
            $libreta = LibretaValor::findOrFail($libretaId);
            
            // Validar que el valor sea numérico (permitir 0)
            if (!is_numeric($valor)) {
                $this->dispatchBrowserEvent('swal', [
                    'icon' => 'error',
                    'title' => 'Error de Validación',
                    'text' => 'El próximo recibo debe ser un número válido.'
                ]);
                return;
            }
            
            $valorInt = (int) $valor;
            
            // Caso especial: 0 significa agotada
            if ($valorInt === 0) {
                $libreta->proximo_recibo_disponible = 0; // O mantener el último? Mejor 0 para indicar estado especial si se requiere, o simplemente cambiar estado.
                // El requerimiento dice "si próximo recibo vale cero... la libreta está agotada"
                
                $libreta->estado = 'agotada';
                
                // Actualizar también la entrega activa si existe
                $entregaActiva = $libreta->entregaActiva;
                if ($entregaActiva) {
                    $entregaActiva->estado = 'agotada';
                    $entregaActiva->save();
                }
                
                $libreta->save();
                
                $this->dispatchBrowserEvent('swal', [
                    'icon' => 'success',
                    'title' => 'Libreta Agotada',
                    'text' => 'La libreta ha sido marcada como agotada y finalizada.',
                    'toast' => true,
                    'position' => 'top-end',
                    'timer' => 3000,
                    'showConfirmButton' => false
                ]);
                
                // Limpiar cache
                unset($this->libretasEnUso);
                unset($this->stockData);
                return;
            }
            
            // Validar rango estricto [inicial, final]
            if ($valorInt < $libreta->numero_inicial || $valorInt > $libreta->numero_final) {
                $this->dispatchBrowserEvent('swal', [
                    'icon' => 'error',
                    'title' => 'Fuera de Rango',
                    'text' => 'El próximo recibo debe estar entre ' . $libreta->numero_inicial . ' y ' . $libreta->numero_final . ' (o 0 para agotar).'
                ]);
                return;
            }
            
            // Actualizar próximo recibo (rango válido)
            $libreta->proximo_recibo_disponible = $valorInt;
            
            // Si el usuario ingresa el último número, la libreta NO se finaliza automáticamente todavía, 
            // solo se finaliza si ingresa 0 explícitamente según el nuevo requerimiento.
            // "permitir que tome el valor 0 (cero). Si próximo recibo vale cero, se entiende que la libreta está agotada"
            
            $this->dispatchBrowserEvent('swal', [
                'icon' => 'success',
                'title' => 'Actualizado',
                'text' => 'Próximo recibo actualizado correctamente.',
                'toast' => true,
                'position' => 'top-end',
                'timer' => 3000,
                'showConfirmButton' => false
            ]);
            
            $libreta->save();
            \Log::info('Próximo recibo guardado exitosamente', ['libretaId' => $libretaId, 'valor' => $valorInt]);
            
            // Limpiar cache de propiedades computadas para asegurar que la vista se actualice con los nuevos datos
            unset($this->libretasEnUso);
            unset($this->stockData);
            
        } catch (\Exception $e) {
            \Log::error('Error al actualizar próximo recibo: ' . $e->getMessage());
            $this->dispatchBrowserEvent('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'Ocurrió un error al actualizar el próximo recibo.'
            ]);
        }
    }

    public function getStockDataProperty()
    {
        \Log::info('Calculando getStockDataProperty');
        // Obtener todas las libretas completas sin filtros
        $libretas = LibretaValor::with(['tipoLibreta.servicios' => function($query) {
                $query->orderBy('nombre')->limit(1);
            }])
            ->where('estado', 'en_stock')
            ->orderBy('tipo_libreta_id')
            ->orderBy('serie')
            ->orderBy('fecha_recepcion')
            ->orderBy('numero_inicial')
            ->get();

        // Agrupar libretas por tipo, serie y fecha de recepción
        $grupos = [];
        foreach ($libretas as $libreta) {
            $key = $libreta->tipo_libreta_id . '|' . ($libreta->serie ?: '') . '|' . $libreta->fecha_recepcion->format('Y-m-d');

            if (!isset($grupos[$key])) {
                $grupos[$key] = [];
            }

            $grupos[$key][] = $libreta;
        }

        // Consolidar grupos
        $libretasCompletas = collect();
        foreach ($grupos as $grupo) {
            $primera = $grupo[0];
            $ultima = end($grupo);
            
            $totalRecibos = 0;
            foreach ($grupo as $lib) {
                $totalRecibos += ($lib->numero_final - $lib->numero_inicial + 1);
            }

            // Obtener el valor del primer servicio asociado al tipo de libreta
            $primerServicio = $primera->tipoLibreta->servicios->first();
            $valor = $primerServicio && $primerServicio->valor_ui 
                ? number_format($primerServicio->valor_ui, 2, ',', '.') . ' U.I.' 
                : 'S.V.E.';

            $libretasCompletas->push([
                'concepto' => $primera->tipoLibreta->nombre,
                'serie' => $primera->serie ?? '-',
                'valor' => $valor,
                'del_numero' => $primera->numero_inicial,
                'al_numero' => $ultima->numero_final,
                'cantidad' => $totalRecibos,
            ]);
        }

        // Obtener todas las libretas entregadas con próximo recibo distinto de cero
        $libretasEnUso = EntregaLibretaValor::with(['libretaValor.tipoLibreta', 'servicio'])
            ->where('estado', 'activo')
            ->whereHas('libretaValor', function($q) {
                $q->where('proximo_recibo_disponible', '!=', 0);
            })
            ->get()
            ->map(function ($entrega) {
                $proximoRecibo = $entrega->libretaValor->proximo_recibo_disponible;
                $numeroFinal = $entrega->libretaValor->numero_final;
                $cantidad = max(0, $numeroFinal - $proximoRecibo + 1);
                
                \Log::info('Stock Data - Libreta ID: ' . $entrega->libretaValor->id . ' - Próximo Recibo: ' . $proximoRecibo);
                
                // Obtener el valor del servicio asociado
                $valor = $entrega->servicio && $entrega->servicio->valor_ui
                    ? number_format($entrega->servicio->valor_ui, 2, ',', '.') . ' U.I.'
                    : 'S.V.E.';
                
                // Si el nombre del tipo de libreta coincide con el nombre del servicio, solo mostrar el tipo
                $nombreTipoLibreta = $entrega->libretaValor->tipoLibreta->nombre;
                $nombreServicio = $entrega->servicio->nombre;
                $concepto = $nombreTipoLibreta === $nombreServicio 
                    ? $nombreTipoLibreta 
                    : $nombreTipoLibreta . ' (' . $nombreServicio . ')';
                
                return [
                    'concepto' => $concepto,
                    'serie' => $entrega->libretaValor->serie ?? '-',
                    'valor' => $valor,
                    'del_numero' => $proximoRecibo,
                    'al_numero' => $numeroFinal,
                    'cantidad' => $cantidad,
                ];
            });

        $totalRecibos = $libretasCompletas->sum('cantidad') + $libretasEnUso->sum('cantidad');

        return [
            'completas' => $libretasCompletas->sortBy('concepto')->values(),
            'en_uso' => $libretasEnUso->sortBy('concepto')->values(),
            'total_recibos' => $totalRecibos,
        ];
    }

    public function getLibretasCompletasProperty()
    {
        $query = LibretaValor::with('tipoLibreta')
            ->where('estado', 'en_stock');

        if ($this->filtroTipoLibreta) {
            $query->where('tipo_libreta_id', $this->filtroTipoLibreta);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('serie', 'like', '%' . $this->search . '%')
                  ->orWhere('numero_inicial', 'like', '%' . $this->search . '%')
                  ->orWhere('numero_final', 'like', '%' . $this->search . '%')
                  ->orWhereHas('tipoLibreta', function ($q2) {
                      $q2->where('nombre', 'like', '%' . $this->search . '%');
                  });
            });
        }

        $libretas = $query->orderBy('tipo_libreta_id')
                        ->orderBy('serie')
                        ->orderBy('fecha_recepcion')
                        ->orderBy('numero_inicial')
                        ->get();

        // Agrupar libretas por tipo, serie y fecha de recepción
        $grupos = [];
        foreach ($libretas as $libreta) {
            $key = $libreta->tipo_libreta_id . '|' . ($libreta->serie ?: '') . '|' . $libreta->fecha_recepcion->format('Y-m-d');

            if (!isset($grupos[$key])) {
                $grupos[$key] = [];
            }

            $grupos[$key][] = $libreta;
        }

        // Procesar cada grupo para verificar numeración consecutiva
        $libretasAgrupadas = [];
        foreach ($grupos as $key => $libretasGrupo) {
            $tipoLibreta = $libretasGrupo[0]->tipoLibreta;

            // Ordenar por número inicial
            usort($libretasGrupo, function($a, $b) {
                return $a->numero_inicial <=> $b->numero_inicial;
            });

            $gruposConsecutivos = [];
            $grupoActual = [$libretasGrupo[0]];

            for ($i = 1; $i < count($libretasGrupo); $i++) {
                $libretaAnterior = $libretasGrupo[$i - 1];
                $libretaActual = $libretasGrupo[$i];

                // Verificar si la numeración es consecutiva
                if ($libretaAnterior->numero_final + 1 === $libretaActual->numero_inicial) {
                    $grupoActual[] = $libretaActual;
                } else {
                    // Guardar el grupo actual y empezar uno nuevo
                    $gruposConsecutivos[] = $grupoActual;
                    $grupoActual = [$libretaActual];
                }
            }

            // Agregar el último grupo
            $gruposConsecutivos[] = $grupoActual;

            // Crear entradas agrupadas
            foreach ($gruposConsecutivos as $grupo) {
                if (count($grupo) > 1) {
                    // Crear entrada agrupada
                    $primeraLibreta = $grupo[0];
                    $ultimaLibreta = end($grupo);

                    $libretaAgrupada = new \stdClass();
                    $libretaAgrupada->tipoLibreta = $tipoLibreta;
                    $libretaAgrupada->serie = $primeraLibreta->serie;
                    $libretaAgrupada->numero_inicial = $primeraLibreta->numero_inicial;
                    $libretaAgrupada->numero_final = $ultimaLibreta->numero_final;
                    $libretaAgrupada->proximo_recibo_disponible = $ultimaLibreta->proximo_recibo_disponible;
                    $libretaAgrupada->fecha_recepcion = $primeraLibreta->fecha_recepcion;
                    $libretaAgrupada->agrupada = true;
                    $libretaAgrupada->cantidad_libretas = count($grupo);
                    $libretaAgrupada->fecha_recepcion_inicial = $primeraLibreta->fecha_recepcion->format('d/m/Y');
                    $libretaAgrupada->fecha_recepcion_final = end($grupo)->fecha_recepcion->format('d/m/Y');
                    $libretaAgrupada->total_recibos = (count($grupo) * $tipoLibreta->cantidad_recibos);

                    $libretasAgrupadas[] = $libretaAgrupada;
                } else {
                    // Mantener la libreta individual
                    $libreta = $grupo[0];
                    $libreta->agrupada = false;
                    $libreta->cantidad_libretas = 1;
                    $libreta->total_recibos = $libreta->tipoLibreta->cantidad_recibos;
                    $libreta->fecha_recepcion_inicial = $libreta->fecha_recepcion->format('d/m/Y');
                    $libretasAgrupadas[] = $libreta;
                }
            }
        }


        // Ordenar las libretas agrupadas por nombre del tipo de libreta, serie y número inicial
        usort($libretasAgrupadas, function($a, $b) {
            // Primero por nombre del tipo de libreta
            $nombreComparison = strcmp($a->tipoLibreta->nombre, $b->tipoLibreta->nombre);
            if ($nombreComparison !== 0) {
                return $nombreComparison;
            }
            
            // Luego por serie (considerando valores nulos)
            $serieA = $a->serie ?? '';
            $serieB = $b->serie ?? '';
            $serieComparison = strcmp($serieA, $serieB);
            if ($serieComparison !== 0) {
                return $serieComparison;
            }
            
            // Finalmente por número inicial
            return $a->numero_inicial <=> $b->numero_inicial;
        });

        // Aplicar paginación manual
        $total = count($libretasAgrupadas);
        $currentPage = request()->get('page', 1);
        $offset = ($currentPage - 1) * $this->perPage;
        $libretasPaginadas = array_slice($libretasAgrupadas, $offset, $this->perPage);

        // Crear paginador personalizado
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $libretasPaginadas,
            $total,
            $this->perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public function getTotalesCompletasProperty()
    {
        // Obtener todas las libretas sin paginación para calcular totales
        $query = LibretaValor::with('tipoLibreta')
            ->where('estado', 'en_stock');

        if ($this->filtroTipoLibreta) {
            $query->where('tipo_libreta_id', $this->filtroTipoLibreta);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('serie', 'like', '%' . $this->search . '%')
                  ->orWhere('numero_inicial', 'like', '%' . $this->search . '%')
                  ->orWhere('numero_final', 'like', '%' . $this->search . '%')
                  ->orWhereHas('tipoLibreta', function ($q2) {
                      $q2->where('nombre', 'like', '%' . $this->search . '%');
                  });
            });
        }

        $libretas = $query->get();

        $totalRecibos = 0;
        $totalLibretas = $libretas->count();

        foreach ($libretas as $libreta) {
            $totalRecibos += $libreta->tipoLibreta->cantidad_recibos;
        }

        return [
            'total_recibos' => $totalRecibos,
            'total_libretas' => $totalLibretas,
        ];
    }

    public function getLibretasEnUsoProperty()
    {
        \Log::info('Calculando getLibretasEnUsoProperty');
        // Obtener todas las entregas activas con próximo recibo distinto de cero
        $query = EntregaLibretaValor::with(['libretaValor.tipoLibreta', 'servicio'])
            ->join('tes_servicios', 'tes_entregas_libretas_valores.servicio_id', '=', 'tes_servicios.id')
            ->where('tes_entregas_libretas_valores.estado', 'activo')
            ->whereHas('libretaValor', function($q) {
                $q->where('proximo_recibo_disponible', '!=', 0);
            });

        if ($this->filtroTipoLibreta) {
            $query->whereHas('libretaValor', function ($q) {
                $q->where('tipo_libreta_id', $this->filtroTipoLibreta);
            });
        }

        if ($this->filtroServicio) {
            $query->where('tes_entregas_libretas_valores.servicio_id', $this->filtroServicio);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('libretaValor', function ($q2) {
                    $q2->where('serie', 'like', '%' . $this->search . '%')
                       ->orWhere('numero_inicial', 'like', '%' . $this->search . '%')
                       ->orWhere('numero_final', 'like', '%' . $this->search . '%')
                       ->orWhereHas('tipoLibreta', function ($q3) {
                           $q3->where('nombre', 'like', '%' . $this->search . '%');
                       });
                })
                ->orWhereHas('servicio', function ($q2) {
                    $q2->where('nombre', 'like', '%' . $this->search . '%');
                });
            });
        }

        return $query->join('tes_libretas_valores', 'tes_entregas_libretas_valores.libreta_valor_id', '=', 'tes_libretas_valores.id')
                    ->join('tes_tipos_libretas', 'tes_libretas_valores.tipo_libreta_id', '=', 'tes_tipos_libretas.id')
                    ->orderBy('tes_tipos_libretas.nombre', 'asc')
                    ->orderBy('tes_servicios.nombre', 'asc')
                    ->orderByRaw('COALESCE(tes_libretas_valores.serie, "") ASC')
                    ->orderBy('tes_libretas_valores.proximo_recibo_disponible', 'asc')
                    ->select('tes_entregas_libretas_valores.*')
                    ->paginate($this->perPage)
                    ->through(function ($entrega) {
                        \Log::info('En Uso Data - Entrega ID: ' . $entrega->id . ' - Próximo Recibo: ' . $entrega->libretaValor->proximo_recibo_disponible);
                        return $entrega;
                    });
    }

    public function render()
    {
        $tiposLibreta = \App\Models\Tesoreria\TipoLibreta::orderBy('nombre')->get();
        $servicios = Servicio::orderBy('nombre')->get();

        // Con pestañas Bootstrap nativas, todas se renderizan al mismo tiempo
        // por lo que necesitamos pasar todas las variables siempre
        $data = [
            'tiposLibreta' => $tiposLibreta,
            'servicios' => $servicios,
            'stockData' => $this->stockData,
            'libretasCompletas' => $this->libretasCompletas,
            'entregas' => $this->libretasEnUso,
        ];

        return view('livewire.tesoreria.valores.reportes.index', $data)
            ->extends('layouts.app');
    }
}
