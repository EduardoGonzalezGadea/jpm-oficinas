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

        return $query->orderBy('fecha_recepcion', 'desc')
                    ->paginate($this->perPage);
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
        } elseif ($this->reporteTipo === 'en_uso') {
            $data['entregas'] = $this->libretasEnUso;
        }

        return view('livewire.tesoreria.valores.reportes.index', $data)
            ->extends('layouts.app');
    }
}
