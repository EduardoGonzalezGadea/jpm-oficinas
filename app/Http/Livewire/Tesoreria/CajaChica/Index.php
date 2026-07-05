<?php

namespace App\Http\Livewire\Tesoreria\CajaChica;

use Livewire\Component;
use Livewire\Livewire;
use App\Models\Tesoreria\CajaChica;
use App\Models\Tesoreria\Pendiente;
use App\Models\Tesoreria\Movimiento;
use App\Models\Tesoreria\Pago;
use App\Models\Tesoreria\Dependencia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;



class Index extends Component
{

    // --- Propiedades Públicas ---
    public $mesActual;
    public $anioActual;
    public $fechaHasta;

    // Propiedad usada para forzar re-render en Livewire 2
    public $refreshKey = 0;

    public $searchPendientes = '';
    public $searchPagos = '';

    public $cajaChicaSeleccionada = null;
    public $dependencias;
    public $dependenciasSinPendientes = [];
    public $dependenciasEspecialesSinPendientes = [];
    public $nuevoFondo = ['mes' => '', 'anio' => '', 'monto' => ''];
    public $nuevoPendiente = [
        'relCajaChica' => null,
        'pendiente' => '',
        'fechaPendientes' => '',
        'relDependencia' => '',
        'montoPendientes' => '',
    ];



    protected $queryString = [
        // Removido mostrarModalDependencias para evitar re-renderizado
    ];

    protected $listeners = [
        'cargarDependencias',
        'fondoCreado' => 'cargarDatos',
        'pendienteCreado' => 'cargarDatos',
        'pagoCreado' => 'recargarPorEvento',
        'datosRecargados' => 'recargarPorEvento',
        'eliminarPendiente' => 'eliminarPendiente',
        'eliminarPago' => 'eliminarPago',
    ];


    public function mount()
    {
        $this->mesActual = strtolower(session('caja_chica_mes') ?: now()->locale('es')->translatedFormat('F'));
        $this->anioActual = session('caja_chica_anio') ?: now()->year;
        $this->fechaHasta = now()->format('Y-m-d');
        $this->dependencias = collect();

        $this->dependenciasSinPendientes = [];
        $this->dependenciasEspecialesSinPendientes = [];
        $this->cargarDatos();
    }



    // Métodos para abrir/cerrar modales
    public function openModalDependencias()
    {
        try {
            $this->dispatchBrowserEvent('show-modal', ['id' => 'modalDependencias']);
        } catch (\Throwable $e) {
            \Log::error('Error openModalDependencias: ' . $e->getMessage());
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Error al cargar dependencias: ' . $e->getMessage()]);
        }
    }

    public function closeModalDependencias()
    {
        $this->dispatchBrowserEvent('hide-modal', ['id' => 'modalDependencias']);
        // Solo recargar datos al cerrar
        $this->cargarDatos();
        $this->emit('datosRecargados');
        $this->resetErrorBag();
    }

    public function openModalAcreedores()
    {
        try {
            $this->dispatchBrowserEvent('show-modal', ['id' => 'modalAcreedores']);
        } catch (\Throwable $e) {
            \Log::error('Error openModalAcreedores: ' . $e->getMessage());
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Error al cargar acreedores: ' . $e->getMessage()]);
        }
    }

    public function closeModalAcreedores()
    {
        $this->dispatchBrowserEvent('hide-modal', ['id' => 'modalAcreedores']);
        // Solo recargar datos al cerrar
        $this->cargarDatos();
        $this->emit('datosRecargados');
        $this->resetErrorBag();
    }



    public function updatedMesActual()
    {
        $this->mesActual = strtolower($this->mesActual);
        session(['caja_chica_mes' => $this->mesActual, 'caja_chica_anio' => $this->anioActual]);
        $this->cargarDatos();
        if (!$this->cajaChicaSeleccionada) {
            $this->dispatchBrowserEvent('swal:toast-warning', [
                'text' => 'No hay datos de Caja Chica para ' . ucfirst($this->mesActual) . ' de ' . $this->anioActual . '.',
            ]);
        }
    }
    public function updatedAnioActual()
    {
        session(['caja_chica_mes' => $this->mesActual, 'caja_chica_anio' => $this->anioActual]);
        $this->cargarDatos();
        if (!$this->cajaChicaSeleccionada) {
            $this->dispatchBrowserEvent('swal:toast-warning', [
                'text' => 'No hay datos de Caja Chica para ' . ucfirst($this->mesActual) . ' de ' . $this->anioActual . '.',
            ]);
        }
    }
    public function updatedFechaHasta()
    {
        $this->cargarDatos();
    }

    public function updatedSearchPendientes()
    {
        // El re-render automático de Livewire llamará a render() y aplicará la búsqueda
    }

    public function limpiarFiltroPendientes()
    {
        $this->searchPendientes = '';
    }

    public function updatedSearchPagos()
    {
        // El re-render automático de Livewire llamará a render() y aplicará la búsqueda
    }

    public function limpiarFiltroPagos()
    {
        $this->searchPagos = '';
    }

    public function irAEditar($id)
    {
        session(['caja_chica_mes' => $this->mesActual, 'caja_chica_anio' => $this->anioActual]);
        return redirect()->route('tesoreria.caja-chica.pendientes.editar', $id);
    }

    public function cargarDatos()
    {
        try {
            $this->cargarTablaCajaChica();
            $this->cargarDependenciasSinPendientes();
        } catch (\Throwable $e) {
            \Log::error('Error en cargarDatos (CajaChica): ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Recarga los datos y fuerza un re-render limpio del componente.
     * Usado por los listeners de eventos de modales hijos.
     * Se resetea cajaChicaSeleccionada para forzar una consulta completamente
     * fresca a la BD, evitando cualquier caché a nivel de objeto Eloquent.
     */
    public function recargarPorEvento()
    {
        $this->refreshKey++;
        $this->cajaChicaSeleccionada = null;
        $this->cargarDatos();
    }

    public function cargarTablaCajaChica()
    {
        $service = app(\App\Services\Tesoreria\CajaChicaService::class);
        $this->cajaChicaSeleccionada = $service->obtenerCajaChica($this->mesActual, $this->anioActual);
        if ($this->cajaChicaSeleccionada) {
            $this->nuevoPendiente['relCajaChica'] = $this->cajaChicaSeleccionada->idCajaChica;
        } else {
            $this->nuevoPendiente['relCajaChica'] = null;
        }
    }

    // Funciones cargarTablaPendientesDetalle, cargarTablaPagos y cargarTablaTotales se movieron al método render para no guardar en el estado del componente.

    // --- Métodos para el Modal de Recuperación ---
    public function openRecuperarModal()
    {
        if (!$this->cajaChicaSeleccionada) {
            $this->dispatchBrowserEvent('swal:toast-error', [
                'text' => 'No hay una caja chica activa para este período.'
            ]);
            return;
        }
        $this->emitTo('tesoreria.caja-chica.modales.modal-recuperar-saldos', 'abrirModalRecuperar', $this->cajaChicaSeleccionada->idCajaChica, $this->mesActual, $this->anioActual);
    }


    // --- Métodos para Modal de Acreedores ---


    // --- Métodos de Acción Existentes ---

    public function prepararModalNuevoPendiente()
    {
        if ($this->cajaChicaSeleccionada) {
            $this->emitTo('tesoreria.caja-chica.modales.modal-nuevo-pendiente', 'mostrarModalNuevoPendiente', $this->cajaChicaSeleccionada->idCajaChica);
        } else {
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'No se ha determinado Fondo Permanente para el mes y año de trabajo actual.']);
        }
    }

    public function mostrarModalNuevoFondo()
    {
        $this->nuevoFondo['mes'] = $this->mesActual;
        $this->nuevoFondo['anio'] = $this->anioActual;
        $this->nuevoFondo['monto'] = '0';

        $this->emitTo('tesoreria.caja-chica.modales.modal-nuevo-fondo', 'mostrarModalNuevoFondo');
    }

    public function prepararModalNuevoPago()
    {
        if ($this->cajaChicaSeleccionada) {
            $this->emitTo('tesoreria.caja-chica.modales.modal-nuevo-pago', 'mostrarModalNuevoPago', $this->cajaChicaSeleccionada->idCajaChica);
        } else {
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'No se ha determinado Fondo Permanente para el mes y año de trabajo actual.']);
        }
    }

    public function cerrarModalNuevoPendiente()
    {
        $this->emitTo('tesoreria.caja-chica.modales.modal-nuevo-pendiente', 'cerrarModalNuevoPendiente');
        $this->cargarDatos();
    }

    public function cerrarModalNuevoPago()
    {
        $this->emitTo('tesoreria.caja-chica.modales.modal-nuevo-pago', 'cerrarModalNuevoPago');
        $this->cargarDatos();
    }

    public function establecerFechaHoy()
    {
        $this->fechaHasta = now()->format('Y-m-d');
        $this->cargarDatos();
    }

    // --- Funciones auxiliares ---
    public function cargarDependenciasSinPendientes()
    {
        if (!$this->cajaChicaSeleccionada) {
            $this->dependenciasSinPendientes = collect();
            $this->dependenciasEspecialesSinPendientes = collect();
            return;
        }

        $fechaHastaStr = $this->fechaHasta ? Carbon::parse($this->fechaHasta)->endOfDay()->toDateTimeString() : now()->endOfDay()->toDateTimeString();
        $service = app(\App\Services\Tesoreria\CajaChicaService::class);
        
        $dependencias = $service->obtenerDependenciasSinPendientes($this->cajaChicaSeleccionada, $fechaHastaStr);
        $this->dependenciasSinPendientes = collect($dependencias['normales'])->values();
        $this->dependenciasEspecialesSinPendientes = collect($dependencias['especiales'])->values();
    }

    // --- Listeners ---

    public function cargarDependencias()
    {
        $this->dependencias = Dependencia::orderBy('dependencia', 'ASC')->get();
    }

    // --- Renderizado ---
    public function render()
    {
        $fechaHastaStr = $this->fechaHasta ? Carbon::parse($this->fechaHasta)->endOfDay()->toDateTimeString() : now()->endOfDay()->toDateTimeString();
        $service = app(\App\Services\Tesoreria\CajaChicaService::class);
        
        $tablaCajaChica = $this->cajaChicaSeleccionada ? collect([$this->cajaChicaSeleccionada->toArray()]) : collect();
        
        $tablaPendientesDetalle = collect();
        $tablaPagos = collect();
        $tablaTotales = [];
        $pendientesSinFiltro = collect();
        $pagosSinFiltro = collect();

        if ($this->cajaChicaSeleccionada) {
            try {
                $tablaPendientesDetalle = $service->obtenerPendientes($this->cajaChicaSeleccionada, $this->mesActual, $this->anioActual, $fechaHastaStr, $this->searchPendientes);
                $tablaPagos = $service->obtenerPagos($this->cajaChicaSeleccionada, $this->mesActual, $this->anioActual, $fechaHastaStr, $this->searchPagos);

                // Calcular totales sin los filtros de búsqueda para evitar datos erróneos al buscar
                $pendientesSinFiltro = $service->obtenerPendientes($this->cajaChicaSeleccionada, $this->mesActual, $this->anioActual, $fechaHastaStr, '');
                $pagosSinFiltro = $service->obtenerPagos($this->cajaChicaSeleccionada, $this->mesActual, $this->anioActual, $fechaHastaStr, '');
                $tablaTotales = $service->calcularTotales($this->cajaChicaSeleccionada, $pendientesSinFiltro, $pagosSinFiltro);
            } catch (\Throwable $e) {
                \Log::error('Error en render (CajaChica): ' . $e->getMessage());
            }
        }

        $totalPendientesEntregados = $pendientesSinFiltro->sum('montoPendientes');
        $totalPagosDirectosOtorgados = $pagosSinFiltro->sum('montoPagos');
        $sumaPendientesMasPagos = $totalPendientesEntregados + $totalPagosDirectosOtorgados;

        return view('livewire.tesoreria.caja-chica.index', [
            'tablaCajaChica' => $tablaCajaChica,
            'tablaPendientesDetalle' => $tablaPendientesDetalle,
            'tablaPagos' => $tablaPagos,
            'tablaTotales' => $tablaTotales,
            'totalPendientesEntregados' => $totalPendientesEntregados,
            'totalPagosDirectosOtorgados' => $totalPagosDirectosOtorgados,
            'sumaPendientesMasPagos' => $sumaPendientesMasPagos,
        ]);
    }

    public function confirmarEliminarPendiente($id)
    {
        $this->dispatchBrowserEvent('swal:confirm', [
            'title' => '¿Está seguro?',
            'text' => '¿Desea eliminar este pendiente? Esta acción no se puede deshacer.',
            'method' => 'eliminarPendiente',
            'id' => $id,
        ]);
    }

    public function eliminarPendiente($id)
    {
        if (!auth()->user()->can('tesoreria.supervisar')) {
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'No tiene permisos para eliminar pendientes.']);
            return;
        }

        try {
            $service = app(\App\Services\Tesoreria\CajaChicaService::class);
            $service->eliminarPendiente($id);
            $this->cargarDatos();
            $this->dispatchBrowserEvent('swal:success', ['text' => 'Pendiente eliminado correctamente.']);
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Error al eliminar el pendiente: ' . $e->getMessage()]);
        }
    }

    public function confirmarEliminarPago($id)
    {
        $this->dispatchBrowserEvent('swal:confirm', [
            'title' => '¿Está seguro?',
            'text' => '¿Desea eliminar este pago directo? Esta acción no se puede deshacer.',
            'method' => 'eliminarPago',
            'id' => $id,
        ]);
    }

    public function eliminarPago($id)
    {
        if (!auth()->user()->can('tesoreria.supervisar')) {
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'No tiene permisos para eliminar pagos.']);
            return;
        }

        try {
            $service = app(\App\Services\Tesoreria\CajaChicaService::class);
            $service->eliminarPago($id);
            $this->cargarDatos();
            $this->dispatchBrowserEvent('swal:success', ['text' => 'Pago eliminado correctamente.']);
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Error al eliminar el pago: ' . $e->getMessage()]);
        }
    }
}
