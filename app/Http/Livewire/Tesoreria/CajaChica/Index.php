<?php

namespace App\Http\Livewire\Tesoreria\CajaChica;

use Livewire\Component;
use App\Models\Tesoreria\CajaChica;
use App\Models\Tesoreria\Pendiente;
use App\Models\Tesoreria\Movimiento;
use App\Models\Tesoreria\Pago;
use App\Models\Tesoreria\Dependencia;
use App\Models\Tesoreria\Acreedor;
use Illuminate\Support\Collection;

class Index extends Component
{
    // --- Propiedades Públicas ---
    /** @var string $mesActual */
    public $mesActual;
    /** @var int $anioActual */
    public $anioActual;
    /** @var string $fechaHasta */
    public $fechaHasta;

    /** @var Collection|array $tablaCajaChica */
    public $tablaCajaChica;
    /** @var Collection|array $tablaPendientesDetalle */
    public $tablaPendientesDetalle;
    /** @var Collection|array $tablaPagos */
    public $tablaPagos;
    /** @var array $tablaTotales */
    public $tablaTotales = [];

    // Para formularios
    /** @var mixed $cajaChicaSeleccionada */
    public $cajaChicaSeleccionada = null;
    /** @var Collection|array $dependencias */
    public $dependencias;
    public $nuevoFondo = ['mes' => '', 'anio' => '', 'monto' => ''];
    public $nuevoPendiente = [
        'relCajaChica' => null,
        'pendiente' => '',
        'fechaPendientes' => '',
        'relDependencia' => '',
        'montoPendientes' => '',
    ];

    // --- Listeners ---
    protected $listeners = [
        'cargarDependencias',
        'fondoCreado' => 'cargarDatos',
        'pendienteCreado' => 'cargarDatos',
    ];

    // --- Ciclo de Vida del Componente ---

    public function mount()
    {
        $this->mesActual = now()->locale('es_ES')->isoFormat('MMMM');
        if (strtolower($this->mesActual) === 'septiembre') {
            $this->mesActual = 'setiembre';
        } else {
            $this->mesActual = strtolower($this->mesActual);
        }

        $this->anioActual = now()->year;
        $this->fechaHasta = now()->format('d/m/Y');
        $this->tablaCajaChica = collect();
        $this->tablaPendientesDetalle = collect();
        $this->tablaPagos = collect();
        $this->dependencias = collect();
        $this->cargarDatos();
    }

    // --- Métodos de Actualización Automática ---

    public function updatedMesActual()
    {
        $this->cargarDatos();
    }

    public function updatedAnioActual()
    {
        $this->cargarDatos();
    }

    public function updatedFechaHasta()
    {
        $this->cargarTablaTotales();
    }

    // --- Métodos de Carga de Datos ---

    public function cargarDatos()
    {
        $this->cargarTablaCajaChica();
        $this->cargarTablaPendientesDetalle();
        $this->cargarTablaPagos();
        $this->cargarTablaTotales();
    }

    public function cargarTablaCajaChica()
    {
        $this->tablaCajaChica = CajaChica::where('mes', $this->mesActual)
            ->where('anio', $this->anioActual)
            ->get();
        $this->cajaChicaSeleccionada = $this->tablaCajaChica->first();
        if ($this->cajaChicaSeleccionada) {
            $this->nuevoPendiente['relCajaChica'] = $this->cajaChicaSeleccionada->idCajaChica;
        } else {
            $this->nuevoPendiente['relCajaChica'] = null;
        }
    }

    public function cargarTablaPendientesDetalle()
    {
        if (!$this->cajaChicaSeleccionada) {
            $this->tablaPendientesDetalle = collect();
            return;
        }

        $this->tablaPendientesDetalle = Pendiente::where('relCajaChica', $this->cajaChicaSeleccionada->idCajaChica)
            ->with(['dependencia', 'movimientos'])
            ->orderBy('pendiente', 'ASC')
            ->get();
    }

    public function cargarTablaPagos()
    {
        if (!$this->cajaChicaSeleccionada) {
            $this->tablaPagos = collect();
            return;
        }

        $this->tablaPagos = Pago::where('relCajaChica_Pagos', $this->cajaChicaSeleccionada->idCajaChica)
            ->with('acreedor')
            ->orderBy('fechaEgresoPagos', 'ASC')
            ->get();
    }

    public function cargarTablaTotales()
    {
        $this->tablaTotales = [];

        if (!$this->cajaChicaSeleccionada) {
            return;
        }

        try {
            if (empty($this->fechaHasta)) {
                session()->flash('error', 'Fecha "Hasta" no proporcionada.');
                $this->tablaTotales = [];
                return;
            }

            $fechaHastaCarbon = \Carbon\Carbon::createFromFormat('d/m/Y', $this->fechaHasta)->endOfDay();
            $idCajaChica = $this->cajaChicaSeleccionada->idCajaChica;
            $stCajaChica = floatval($this->cajaChicaSeleccionada->montoCajaChica);
            $this->tablaTotales['Monto Caja Chica'] = $stCajaChica;

            // --- Cálculos para Pendientes ---
            $stPendientes = 0;
            $stRendidos = 0;
            $stExtras = 0;

            foreach ($this->tablaPendientesDetalle as $pendiente) {
                $montoPend = floatval($pendiente->montoPendientes);
                $totRendido = floatval($pendiente->tot_rendido);
                $totReintegrado = floatval($pendiente->tot_reintegrado);
                $totRecuperado = floatval($pendiente->tot_recuperado);
                $tExtra = floatval($pendiente->extra);

                // 2. Total Pendientes (stPendientes)
                if ($montoPend > ($totRendido + $totReintegrado)) {
                    $stPendientes += ($montoPend - ($totRendido + $totReintegrado));
                }

                // 3. Total Rendidos (stRendidos)
                if ($totRendido > $totRecuperado) {
                    $stRendidos += ($totRendido - $totRecuperado - $tExtra);
                }

                // 4. Total Extras (stExtras)
                if ($totRecuperado < $totRendido) {
                    $stExtras += $tExtra;
                }
            }

            $this->tablaTotales['Total Pendientes'] = $stPendientes;
            $this->tablaTotales['Total Rendidos'] = $stRendidos;
            $this->tablaTotales['Total Extras'] = $stExtras;

            // --- Cálculos para Pagos Directos ---
            $pagosFiltrados = $this->tablaPagos->filter(function ($pago) use ($fechaHastaCarbon) {
                return $pago->fechaEgresoPagos && \Carbon\Carbon::parse($pago->fechaEgresoPagos)->lte($fechaHastaCarbon);
            });

            // 6. Saldo Pagos Directos (stPagos)
            $stPagos = 0;
            foreach ($pagosFiltrados as $pago) {
                $saldoPagoIndividual = floatval($pago->montoPagos) - floatval($pago->recuperadoPagos);
                $stPagos += $saldoPagoIndividual;
            }
            $this->tablaTotales['Saldo Pagos Directos'] = $stPagos;

            // 5. Saldo Total (stSaldo)
            // Incluye el descuento de Saldo Pagos Directos
            $stSaldo = $stCajaChica - $stPendientes - $stRendidos - $stExtras - $stPagos;
            $this->tablaTotales['Saldo Total'] = $stSaldo;
        } catch (\Exception $e) {
            session()->flash('error', 'Error al calcular los totales. Por favor, revise los datos o contacte al administrador.');
            $this->tablaTotales = [];
        } catch (\Error $e) {
            session()->flash('error', 'Error fatal al calcular los totales. Por favor, contacte al administrador.');
            $this->tablaTotales = [];
        }
    }

    // --- Métodos de Acción ---

    public function editarFondo($idCajaChica, $montoActual)
    {
        session()->flash('message', "Editar fondo ID: $idCajaChica, Monto: " . number_format($montoActual, 2, ',', '.'));
    }

    public function prepararModalNuevoPendiente()
    {
        if ($this->cajaChicaSeleccionada) {
            $this->emitTo('tesoreria.caja-chica.modal-nuevo-pendiente', 'mostrarModalNuevoPendiente', $this->cajaChicaSeleccionada->idCajaChica);
        } else {
            session()->flash('error', 'No se ha determinado Fondo Permanente para el mes y año de trabajo actual.');
        }
    }

    /**
     * Prepara y solicita la apertura del modal de nuevo fondo.
     */
    public function mostrarModalNuevoFondo()
    {
        // Pre-cargar datos para el formulario del modal
        $this->nuevoFondo['mes'] = $this->mesActual;
        $this->nuevoFondo['anio'] = $this->anioActual;
        $this->nuevoFondo['monto'] = '350000'; // Valor por defecto

        // Emitir evento al navegador para que JS muestre el modal
        // O mejor aún, emitir al componente específico
        $this->emitTo('tesoreria.caja-chica.modal-nuevo-fondo', 'mostrarModalNuevoFondo');
    }
    
    public function exportarExcel()
    {
        $html = view('livewire.tesoreria.caja-chica.partials.excel-totales', [
            'datos' => $this->tablaTotales,
            'fechaHasta' => $this->fechaHasta,
            'mes' => $this->mesActual,
            'anio' => $this->anioActual
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="TOTALES_CAJA_CHICA.xls"',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    // --- Funciones auxiliares ---

    private function mesAnterior($mesActual)
    {
        $meses = [
            'enero' => 'diciembre',
            'febrero' => 'enero',
            'marzo' => 'febrero',
            'abril' => 'marzo',
            'mayo' => 'abril',
            'junio' => 'mayo',
            'julio' => 'junio',
            'agosto' => 'julio',
            'setiembre' => 'agosto',
            'octubre' => 'setiembre',
            'noviembre' => 'octubre',
            'diciembre' => 'noviembre'
        ];
        return $meses[strtolower($mesActual)] ?? 'diciembre';
    }

    // --- Listeners ---

    public function cargarDependencias()
    {
        $this->dependencias = Dependencia::orderBy('dependencia', 'ASC')->get();
    }

    // --- Renderizado ---

    public function render()
    {
        return view('livewire.tesoreria.caja-chica.index');
    }
}
