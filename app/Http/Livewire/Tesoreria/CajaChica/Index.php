<?php

namespace App\Http\Livewire\Tesoreria\CajaChica;

use Livewire\Component;
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

    public function mostrarAlertaSweet($data)
    {
        $this->dispatchBrowserEvent('swal', $data);
    }

    protected $listeners = [
        'cargarDependencias',
        'fondoCreado' => 'cargarDatos',
        'fondoActualizado' => 'cargarDatos',
        'pendienteCreado' => 'cargarDatos',
        'pagoCreado' => 'cargarDatos',
        'mostrarAlerta' => 'mostrarAlertaSweet',
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
        $this->cargarDatos();
        $this->dispatchBrowserEvent('show-modal', ['id' => 'modalDependencias']);
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
        $this->cargarDatos();
        $this->dispatchBrowserEvent('show-modal', ['id' => 'modalAcreedores']);
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
        $this->mesActual = strtolower($this->mesActual); // Asegurar minúsculas
        session()->forget(['caja_chica_mes', 'caja_chica_anio']);
        $this->cargarDatos();
    }
    public function updatedAnioActual()
    {
        session()->forget(['caja_chica_mes', 'caja_chica_anio']);
        $this->cargarDatos();
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
        $this->cargarTablaCajaChica();
        $this->cargarDependenciasSinPendientes();
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
        $this->cargarDatos();
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
        $this->cargarDatos();
        if ($this->cajaChicaSeleccionada) {
            $this->emitTo('tesoreria.caja-chica.modales.modal-nuevo-pendiente', 'mostrarModalNuevoPendiente', $this->cajaChicaSeleccionada->idCajaChica);
        } else {
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'No se ha determinado Fondo Permanente para el mes y año de trabajo actual.']);
        }
    }

    public function mostrarModalNuevoFondo()
    {
        $this->cargarDatos();
        $this->nuevoFondo['mes'] = $this->mesActual;
        $this->nuevoFondo['anio'] = $this->anioActual;
        $this->nuevoFondo['monto'] = '0';

        $this->emitTo('tesoreria.caja-chica.modales.modal-nuevo-fondo', 'mostrarModalNuevoFondo');
    }

    public function prepararModalNuevoPago()
    {
        $this->cargarDatos();
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

    public function exportarExcel()
    {
        $fechaHastaStr = $this->fechaHasta ? Carbon::parse($this->fechaHasta)->endOfDay()->toDateTimeString() : now()->endOfDay()->toDateTimeString();
        $service = app(\App\Services\Tesoreria\CajaChicaService::class);
        $pendientesSinFiltro = $service->obtenerPendientes($this->cajaChicaSeleccionada, $this->mesActual, $this->anioActual, $fechaHastaStr, '');
        $pagosSinFiltro = $service->obtenerPagos($this->cajaChicaSeleccionada, $this->mesActual, $this->anioActual, $fechaHastaStr, '');
        $totales = $service->calcularTotales($this->cajaChicaSeleccionada, collect($pendientesSinFiltro), collect($pagosSinFiltro));

        $fileName = 'TOTALES_CAJA_CHICA_' . strtoupper($this->mesActual) . '_' . $this->anioActual . '.xls';
        
        $xml = '<?xml version="1.0"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
        $xml .= ' <Styles>' . "\n";
        $xml .= '  <Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Bottom"/><Borders/><Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000"/><Interior/><NumberFormat/><Protection/></Style>' . "\n";
        $xml .= '  <Style ss:ID="s1"><Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#FFFFFF" ss:Bold="1"/><Interior ss:Color="#17a2b8" ss:Pattern="Solid"/></Style>' . "\n";
        $xml .= '  <Style ss:ID="s2"><NumberFormat ss:Format="#,##0.00"/></Style>' . "\n";
        $xml .= '  <Style ss:ID="s3"><Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000" ss:Bold="1"/><NumberFormat ss:Format="#,##0.00"/></Style>' . "\n";
        $xml .= ' </Styles>' . "\n";
        $xml .= ' <Worksheet ss:Name="Totales">' . "\n";
        $xml .= '  <Table>' . "\n";
        $xml .= '   <Column ss:Width="280"/>' . "\n";
        $xml .= '   <Column ss:Width="150"/>' . "\n";
        
        $xml .= '   <Row>' . "\n";
        $xml .= '    <Cell ss:StyleID="s1"><Data ss:Type="String">CONCEPTO</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="s1"><Data ss:Type="String">MONTO ($)</Data></Cell>' . "\n";
        $xml .= '   </Row>' . "\n";

        $datos = [
            ['Total Pendientes', $totales['Total Pendientes'] ?? 0, 's2'],
            ['Total Rendidos', $totales['Total Rendidos'] ?? 0, 's2'],
            ['Total Extras', $totales['Total Extras'] ?? 0, 's2'],
            ['Pagos Sin Egreso', $totales['Pagos Sin Egreso'] ?? 0, 's2'],
            ['Pent.+Pag. (Sin Rendir)', $totales['Pendientes y Pagos Sin Rendir'] ?? 0, 's2'],
            ['Total Pendientes + Pagos s/eg.', ($totales['Total Pendientes'] ?? 0) + ($totales['Pagos Sin Egreso'] ?? 0), 's3'],
            ['Saldo Pagos Directos', $totales['Saldo Pagos Directos'] ?? 0, 's2'],
            ['Recuperar (Rendidos + Extras + Pagos Dir.)', ($totales['Total Rendidos'] ?? 0) + ($totales['Total Extras'] ?? 0) + ($totales['Saldo Pagos Directos'] ?? 0), 's3'],
            ['Saldo Final', $totales['Saldo Total'] ?? 0, 's3'],
        ];

        foreach ($datos as $fila) {
            $xml .= '   <Row>' . "\n";
            $xml .= '    <Cell><Data ss:Type="String">' . htmlspecialchars($fila[0]) . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="' . $fila[2] . '"><Data ss:Type="Number">' . number_format($fila[1], 2, '.', '') . '</Data></Cell>' . "\n";
            $xml .= '   </Row>' . "\n";
        }

        $xml .= '  </Table>' . "\n";
        $xml .= ' </Worksheet>' . "\n";
        $xml .= '</Workbook>';

        return response()->streamDownload(function () use ($xml) {
            echo $xml;
        }, $fileName);
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

        if ($this->cajaChicaSeleccionada) {
            try {
                $tablaPendientesDetalle = $service->obtenerPendientes($this->cajaChicaSeleccionada, $this->mesActual, $this->anioActual, $fechaHastaStr, $this->searchPendientes);
                $tablaPagos = $service->obtenerPagos($this->cajaChicaSeleccionada, $this->mesActual, $this->anioActual, $fechaHastaStr, $this->searchPagos);

                // Calcular totales sin los filtros de búsqueda para evitar datos erróneos al buscar
                $pendientesSinFiltro = $service->obtenerPendientes($this->cajaChicaSeleccionada, $this->mesActual, $this->anioActual, $fechaHastaStr, '');
                $pagosSinFiltro = $service->obtenerPagos($this->cajaChicaSeleccionada, $this->mesActual, $this->anioActual, $fechaHastaStr, '');
                $tablaTotales = $service->calcularTotales($this->cajaChicaSeleccionada, $pendientesSinFiltro, $pagosSinFiltro);
            } catch (\Exception $e) {
                // Manejar error silenciosamente o despachar alerta
            }
        }

        return view('livewire.tesoreria.caja-chica.index', [
            'tablaCajaChica' => $tablaCajaChica,
            'tablaPendientesDetalle' => $tablaPendientesDetalle,
            'tablaPagos' => $tablaPagos,
            'tablaTotales' => $tablaTotales
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
