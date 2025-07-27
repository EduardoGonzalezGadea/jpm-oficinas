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

    // Variables para el modal de edición de fondo
    public $showEditFondoModal = false;
    public $editandoFondo = [
        'id' => null,
        'mes' => '',
        'anio' => '',
        'monto' => '',
        'montoOriginal' => ''
    ];

    // --- Listeners ---
    protected $listeners = [
        'cargarDependencias',
        'fondoCreado' => 'cargarDatos',
        'fondoActualizado' => 'cargarDatos',
        'pendienteCreado' => 'cargarDatos',
        'pagoCreado' => 'cargarDatos',
    ];

    // Reglas de validación para editar fondo
    protected function rules()
    {
        return [
            'editandoFondo.monto' => 'required|numeric|min:0|max:99999999.99',
        ];
    }

    protected function messages()
    {
        return [
            'editandoFondo.monto.required' => 'El monto es obligatorio.',
            'editandoFondo.monto.numeric' => 'El monto debe ser un número válido.',
            'editandoFondo.monto.min' => 'El monto no puede ser negativo.',
            'editandoFondo.monto.max' => 'El monto no puede exceder 99,999,999.99.',
        ];
    }

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

    // --- Métodos de Acción para Editar Fondo ---

    public function editarFondo($idCajaChica, $montoActual)
    {
        try {
            $fondo = CajaChica::findOrFail($idCajaChica);

            $this->editandoFondo = [
                'id' => $idCajaChica,
                'mes' => ucfirst($fondo->mes),
                'anio' => $fondo->anio,
                'monto' => number_format($montoActual, 2, '.', ''),
                'montoOriginal' => number_format($montoActual, 2, '.', '')
            ];

            $this->showEditFondoModal = true;
            $this->resetErrorBag();

            // Emitir evento para focus en el campo monto
            $this->dispatchBrowserEvent('modal-edit-fondo-opened');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al cargar los datos del fondo: ' . $e->getMessage());
        }
    }

    public function actualizarFondo()
    {
        $this->validate();

        try {
            $fondo = CajaChica::findOrFail($this->editandoFondo['id']);
            $montoAnterior = $fondo->montoCajaChica;
            $montoNuevo = floatval($this->editandoFondo['monto']);

            // Verificar si realmente hay cambios
            if (abs($montoAnterior - $montoNuevo) < 0.01) {
                $this->cerrarModalEditFondo();
                session()->flash('message', 'No se realizaron cambios en el monto del fondo.');
                return;
            }

            $fondo->montoCajaChica = $montoNuevo;
            $fondo->save();

            // Recargar datos
            $this->cargarDatos();
            $this->cerrarModalEditFondo();

            $mensaje = sprintf(
                'Fondo actualizado exitosamente. Monto anterior: $%s, Monto nuevo: $%s',
                number_format($montoAnterior, 2, ',', '.'),
                number_format($montoNuevo, 2, ',', '.')
            );

            session()->flash('message', $mensaje);

            // Emitir evento para notificación
            $this->dispatchBrowserEvent('fondo-actualizado', [
                'message' => 'Fondo actualizado exitosamente',
                'montoAnterior' => $montoAnterior,
                'montoNuevo' => $montoNuevo
            ]);
        } catch (\Exception $e) {
            session()->flash('error', 'Error al actualizar el fondo: ' . $e->getMessage());
        }
    }

    public function cerrarModalEditFondo()
    {
        $this->showEditFondoModal = false;
        $this->editandoFondo = [
            'id' => null,
            'mes' => '',
            'anio' => '',
            'monto' => '',
            'montoOriginal' => ''
        ];
        $this->resetErrorBag();
    }

    // Validación en tiempo real del monto
    public function updatedEditandoFondoMonto()
    {
        $this->validateOnly('editandoFondo.monto');
    }

    // --- Métodos de Acción Existentes ---

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

    /**
     * Prepara y solicita la apertura del modal de nuevo pago directo.
     */
    public function prepararModalNuevoPago()
    {
        if ($this->cajaChicaSeleccionada) {
            $this->emitTo('tesoreria.caja-chica.modal-nuevo-pago', 'mostrarModalNuevoPago', $this->cajaChicaSeleccionada->idCajaChica);
        } else {
            session()->flash('error', 'No se ha determinado Fondo Permanente para el mes y año de trabajo actual.');
        }
    }

    /**
     * Exporta los totales actuales a un archivo Excel.
     *
     * Envia una respuesta HTTP con el contenido HTML del archivo Excel.
     * El archivo se llama "TOTALES_CAJA_CHICA.xls" y se puede abrir con Microsoft Excel o LibreOffice Calc.
     *
     * @return \Illuminate\Http\Response
     */
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
