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
    public $reporteTipo = 'completas'; // completas, en_uso

    // Propiedades para paginación
    public $perPage = 10;

    protected $queryString = [
        'reporteTipo' => ['except' => 'completas'],
        'filtroTipoLibreta' => ['except' => ''],
        'filtroEstado' => ['except' => ''],
        'filtroServicio' => ['except' => ''],
        'search' => ['except' => ''],
    ];

    public function mount()
    {
        // Inicialización si es necesaria
    }

    public function updating($property)
    {
        if (in_array($property, ['filtroTipoLibreta', 'filtroEstado', 'filtroServicio', 'search'])) {
            $this->resetPage();
        }
    }

    public function cambiarReporte($tipo)
    {
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
                    $libretasAgrupadas[] = $libreta;
                }
            }
        }

        // Ordenar las libretas agrupadas por fecha de recepción
        usort($libretasAgrupadas, function($a, $b) {
            return $a->fecha_recepcion->timestamp <=> $b->fecha_recepcion->timestamp;
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
        // Obtener la última entrega por cada servicio
        // Primero obtenemos los IDs de las entregas más recientes por servicio
        $ultimasEntregasIds = EntregaLibretaValor::selectRaw('MAX(id) as id')
            ->where('estado', 'activo')
            ->groupBy('servicio_id')
            ->pluck('id');

        $query = EntregaLibretaValor::with(['libretaValor.tipoLibreta', 'servicio'])
            ->whereIn('id', $ultimasEntregasIds);

        if ($this->filtroTipoLibreta) {
            $query->whereHas('libretaValor', function ($q) {
                $q->where('tipo_libreta_id', $this->filtroTipoLibreta);
            });
        }

        if ($this->filtroServicio) {
            $query->where('servicio_id', $this->filtroServicio);
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

        return $query->orderBy('fecha_entrega', 'desc')
                    ->paginate($this->perPage);
    }

    public function render()
    {
        $tiposLibreta = \App\Models\Tesoreria\TipoLibreta::orderBy('nombre')->get();
        $servicios = Servicio::orderBy('nombre')->get();

        $data = [
            'tiposLibreta' => $tiposLibreta,
            'servicios' => $servicios,
        ];

        if ($this->reporteTipo === 'completas') {
            $data['libretas'] = $this->libretasCompletas;
            $data['totalesCompletas'] = $this->totalesCompletas;
        } elseif ($this->reporteTipo === 'en_uso') {
            $data['entregas'] = $this->libretasEnUso;
        }

        return view('livewire.tesoreria.valores.reportes.index', $data)
            ->extends('layouts.app');
    }
}
