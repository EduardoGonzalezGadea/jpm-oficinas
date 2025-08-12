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

    public $tablaCajaChica;
    public $tablaPendientesDetalle;
    public $tablaPagos;
    public $tablaTotales = [];

    public $cajaChicaSeleccionada = null;
    public $dependencias;
    public $nuevoFondo = ['mes' => '', 'anio' => '', 'monto' => ''];
    public $nuevoPendiente = [
        'relCajaChica' => null,
        'pendiente' => '',
        'fechaPendientes' => '',
        'relDependencia' => '',
        'montoPendientes' => '',
    ];

    // Modal Editar Fondo
    public $showEditFondoModal = false;
    public $editandoFondo = [
        'id' => null, 'mes' => '', 'anio' => '', 'monto' => '', 'montoOriginal' => ''
    ];

    // --- Propiedades para el Modal de Recuperación ---
    public $showRecuperarModal = false;
    public $recuperacion = [
        'fecha' => '',
        'numero_ingreso' => ''
    ];
    public $itemsParaRecuperar = [];
    public $itemsSeleccionados = [];
    public $totalARecuperar = 0.00;
    public $seleccionarTodos = false;

    protected $listeners = [
        'cargarDependencias',
        'fondoCreado' => 'cargarDatos',
        'fondoActualizado' => 'cargarDatos',
        'pendienteCreado' => 'cargarDatos',
        'pagoCreado' => 'cargarDatos',
        'mostrarAlerta' => 'mostrarAlertaSweet',
        'dependenciaCreada' => 'cargarDatos',
        'dependenciaActualizada' => 'cargarDatos',
        'dependenciaEliminada' => 'cargarDatos',
        'acreedorCreado' => 'cargarDatos',
        'acreedorActualizado' => 'cargarDatos',
        'acreedorEliminado' => 'cargarDatos',
    ];

    public $mostrarModalDependencias = false;
    public $mostrarModalAcreedores = false;

    protected $queryString = [
        'mostrarModalDependencias' => ['except' => false],
    ];

    public function mostrarAlertaSweet($data)
    {
        $this->dispatchBrowserEvent('swal', $data);
    }

    protected function rules()
    {
        $rules = [
            'editandoFondo.monto' => 'required|numeric|min:0|max:99999999.99',
        ];

        if ($this->showRecuperarModal) {
            $rules['recuperacion.fecha'] = 'required|date';
            $rules['recuperacion.numero_ingreso'] = 'required|string|max:50';
            $rules['itemsSeleccionados'] = 'required|array|min:1';
        }

        return $rules;
    }

    protected function messages()
    {
        return [
            'editandoFondo.monto.required' => 'El monto es obligatorio.',
            'editandoFondo.monto.numeric' => 'El monto debe ser un número válido.',
            'editandoFondo.monto.min' => 'El monto no puede ser negativo.',
            'editandoFondo.monto.max' => 'El monto no puede exceder 99,999,999.99.',
            'recuperacion.fecha.required' => 'La fecha de recuperación es obligatoria.',
            'recuperacion.numero_ingreso.required' => 'El número de ingreso es obligatorio.',
            'itemsSeleccionados.required' => 'Debe seleccionar al menos un ítem para recuperar.',
            'itemsSeleccionados.min' => 'Debe seleccionar al menos un ítem para recuperar.',
        ];
    }

    public function mount()
    {
        $this->mesActual = now()->locale('es')->translatedFormat('F');

        $this->anioActual = now()->year;
        $this->fechaHasta = now()->format('Y-m-d');
        $this->tablaCajaChica = collect();
        $this->tablaPendientesDetalle = collect();
        $this->tablaPagos = collect();
        $this->dependencias = collect();
        $this->itemsParaRecuperar = [];
        $this->cargarDatos();
    }

    public function updatedMesActual() { $this->cargarDatos(); }
    public function updatedAnioActual() { $this->cargarDatos(); }
    public function updatedFechaHasta() { $this->cargarDatos(); }

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

        try {
            $fechaHastaStr = Carbon::createFromFormat('Y-m-d', $this->fechaHasta)->endOfDay()->toDateTimeString();

            $pendientesActual = Pendiente::where('relCajaChica', $this->cajaChicaSeleccionada->idCajaChica)
                ->where('fechaPendientes', '<=', $fechaHastaStr)
                ->with('dependencia')
                ->selectRaw(
                    'tes_cch_pendientes.*,
                    (SELECT SUM(rendido) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND deleted_at IS NULL) as tot_rendido,
                    (SELECT SUM(reintegrado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND deleted_at IS NULL) as tot_reintegrado,
                    (SELECT SUM(recuperado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND deleted_at IS NULL) as tot_recuperado'
                )
                ->orderBy('pendiente', 'ASC')
                ->get();

            $pendientesActual = $pendientesActual->map(function ($pendiente) {
                $pendiente->tot_rendido = $pendiente->tot_rendido ?? 0;
                $pendiente->tot_reintegrado = $pendiente->tot_reintegrado ?? 0;
                $pendiente->tot_recuperado = $pendiente->tot_recuperado ?? 0;

                $totalGastado = $pendiente->tot_rendido + $pendiente->tot_reintegrado;
                $diferencia = $totalGastado > 0 ? $totalGastado - $pendiente->montoPendientes : 0;
                $pendiente->extra = $diferencia > 0 ? $diferencia : 0;

                $pendiente->saldo = $pendiente->montoPendientes - ($pendiente->tot_reintegrado + $pendiente->tot_recuperado);
                $pendiente->es_mes_anterior = false; // Flag for current month
                return $pendiente;
            });

            // Obtener pendientes del mes anterior con saldo > 0
            $mesAnioAnterior = $this->getMesAnioAnterior();
            $mesAnterior = $mesAnioAnterior['mes'];
            $anioAnterior = $mesAnioAnterior['anio'];

            $cajaChicaAnterior = CajaChica::where('mes', $mesAnterior)
                ->where('anio', $anioAnterior)
                ->first();

            $pendientesAnterior = collect();
            if ($cajaChicaAnterior) {
                $pendientesAnterior = Pendiente::where('relCajaChica', $cajaChicaAnterior->idCajaChica)
                    ->where('fechaPendientes', '<=', $fechaHastaStr)
                    ->with('dependencia')
                    ->selectRaw(
                        'tes_cch_pendientes.*,
                        (SELECT SUM(rendido) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND deleted_at IS NULL) as tot_rendido,
                        (SELECT SUM(reintegrado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND deleted_at IS NULL) as tot_reintegrado,
                        (SELECT SUM(recuperado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND deleted_at IS NULL) as tot_recuperado'
                    )
                    ->orderBy('pendiente', 'ASC')
                    ->get();

                $pendientesAnterior = $pendientesAnterior->map(function ($pendiente) {
                    $pendiente->tot_rendido = $pendiente->tot_rendido ?? 0;
                    $pendiente->tot_reintegrado = $pendiente->tot_reintegrado ?? 0;
                    $pendiente->tot_recuperado = $pendiente->tot_recuperado ?? 0;

                    $totalGastado = $pendiente->tot_rendido + $pendiente->tot_reintegrado;
                    $diferencia = $totalGastado > 0 ? $totalGastado - $pendiente->montoPendientes : 0;
                    $pendiente->extra = $diferencia > 0 ? $diferencia : 0;

                    $pendiente->saldo = $pendiente->montoPendientes - ($pendiente->tot_reintegrado + $pendiente->tot_recuperado);
                    $pendiente->es_mes_anterior = true; // Flag for previous month
                    return $pendiente;
                })->filter(function ($pendiente) {
                    return $pendiente->saldo > 0;
                });
            }

            $this->tablaPendientesDetalle = $pendientesActual->concat($pendientesAnterior)->sortBy('pendiente')->values();
        } catch (\Exception $e) {
            session()->flash('error', 'Error al cargar pendientes: ' . $e->getMessage());
            $this->tablaPendientesDetalle = collect();
        }
    }

    public function cargarTablaPagos()
    {
        if (!$this->cajaChicaSeleccionada) {
            $this->tablaPagos = collect();
            return;
        }

        try {
            $fechaHastaStr = Carbon::createFromFormat('Y-m-d', $this->fechaHasta)->endOfDay()->toDateTimeString();

            $pagosActual = Pago::where('relCajaChica_Pagos', $this->cajaChicaSeleccionada->idCajaChica)
                ->where('fechaEgresoPagos', '<=', $fechaHastaStr)
                ->with('acreedor')
                ->selectRaw(
                    'tes_cch_pagos.*,
                    (montoPagos - CASE WHEN fechaIngresoPagos IS NOT NULL THEN recuperadoPagos ELSE 0 END) as saldo_pagos'
                )
                ->orderBy('fechaEgresoPagos', 'ASC')
                ->get()
                ->map(function($pago) {
                    $pago->es_mes_anterior = false;
                    return $pago;
                });

            // Obtener pagos del mes anterior con saldo > 0
            $mesAnioAnterior = $this->getMesAnioAnterior();
            $mesAnterior = $mesAnioAnterior['mes'];
            $anioAnterior = $mesAnioAnterior['anio'];

            $cajaChicaAnterior = CajaChica::where('mes', $mesAnterior)
                ->where('anio', $anioAnterior)
                ->first();

            $pagosAnterior = collect();
            if ($cajaChicaAnterior) {
                $pagosAnterior = Pago::where('relCajaChica_Pagos', $cajaChicaAnterior->idCajaChica)
                    ->where('fechaEgresoPagos', '<=', $fechaHastaStr)
                    ->with('acreedor')
                    ->selectRaw(
                        'tes_cch_pagos.*,
                        (montoPagos - CASE WHEN fechaIngresoPagos IS NOT NULL THEN recuperadoPagos ELSE 0 END) as saldo_pagos'
                    )
                    ->orderBy('fechaEgresoPagos', 'ASC')
                    ->get()
                    ->map(function($pago) {
                        $pago->es_mes_anterior = true;
                        return $pago;
                    })
                    ->filter(function ($pago) {
                        return ($pago['saldo_pagos'] ?? 0) > 0;
                    });
            }

            $this->tablaPagos = $pagosActual->concat($pagosAnterior)->sortBy('fechaEgresoPagos')->values();
        } catch (\Exception $e) {
            session()->flash('error', 'Error al cargar pagos: ' . $e->getMessage());
            $this->tablaPagos = collect();
        }
    }

    public function cargarTablaTotales()
    {
        $this->tablaTotales = [];

        if (!$this->cajaChicaSeleccionada) {
            return;
        }

        try {
            $montoCajaChica = floatval($this->cajaChicaSeleccionada->montoCajaChica);

            $this->tablaTotales['Monto Caja Chica'] = $montoCajaChica;

            // Calcular totales de pendientes usando tablaPendientesDetalle
            $totalMontoPendientes = $this->tablaPendientesDetalle->sum('montoPendientes');
            $totalRendido = $this->tablaPendientesDetalle->sum('tot_rendido');
            $totalReintegrado = $this->tablaPendientesDetalle->sum('tot_reintegrado');
            $totalRecuperado = $this->tablaPendientesDetalle->sum('tot_recuperado');
            $stExtras = $this->tablaPendientesDetalle->sum('extra');

            $totalGastado = $totalRendido + $totalReintegrado;
            $stPendientes = $totalMontoPendientes - $totalGastado;
            $stPendientes = $stPendientes > 0 ? $stPendientes : 0;

            $stRendidos = $totalRendido - $totalRecuperado;
            $stRendidos = max(0, $stRendidos - $stExtras);

            $this->tablaTotales['Total Pendientes'] = $stPendientes;
            $this->tablaTotales['Total Rendidos'] = $stRendidos;
            $this->tablaTotales['Total Extras'] = $stExtras;

            // Calcular totales de pagos usando tablaPagos
            $totalMontoPagos = $this->tablaPagos->sum('montoPagos');
            $totalRecuperadoPagos = $this->tablaPagos->sum('recuperadoPagos');

            $stPagos = $totalMontoPagos - $totalRecuperadoPagos;
            $this->tablaTotales['Saldo Pagos Directos'] = $stPagos;

            $stSaldo = $montoCajaChica - $stPendientes - $stRendidos - $stExtras - $stPagos;
            $this->tablaTotales['Saldo Total'] = $stSaldo;
        } catch (\Exception $e) {
            session()->flash('error', 'Error al calcular los totales: ' . $e->getMessage());
            $this->tablaTotales = [];
        } catch (\Error $e) {
            session()->flash('error', 'Error fatal al calcular los totales. Por favor, contacte al administrador.');
            $this->tablaTotales = [];
        }
    }

    // --- Métodos para el Modal de Recuperación ---
    public function openRecuperarModal()
    {
        if (!$this->cajaChicaSeleccionada) {
            $this->dispatchBrowserEvent('swal:toast-error', [
                'text' => 'No hay una caja chica activa para este período.'
            ]);
            return;
        }

        $this->reset(['itemsParaRecuperar', 'itemsSeleccionados', 'totalARecuperar', 'seleccionarTodos']);
        $this->recuperacion['fecha'] = now()->format('Y-m-d');
        $this->recuperacion['numero_ingreso'] = '';

        $fechaRecuperacionActual = now()->endOfDay()->toDateTimeString();

        // --- Pendientes del mes actual ---
        $pendientesRecuperacion = Pendiente::where('relCajaChica', $this->cajaChicaSeleccionada->idCajaChica)
            ->where('fechaPendientes', '<=', $fechaRecuperacionActual)
            ->with('dependencia')
            ->selectRaw(
                'tes_cch_pendientes.*,
                (SELECT SUM(rendido) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_rendido,
                (SELECT SUM(reintegrado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_reintegrado,
                (SELECT SUM(recuperado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_recuperado',
                [$fechaRecuperacionActual, $fechaRecuperacionActual, $fechaRecuperacionActual]
            )
            ->orderBy('pendiente', 'ASC')
            ->get();

        $pendientes = $pendientesRecuperacion->filter(function ($p) {
            $saldoRendido = ($p['tot_rendido'] ?? 0) - ($p['tot_recuperado'] ?? 0);
            return $saldoRendido > 0;
        })->map(function ($p) {
            $detalleDependencia = $p['dependencia']['dependencia'] ?? 'Sin dato';
            return [
                'id' => 'pendiente_' . $p['idPendientes'],
                'tipo' => 'Pendiente',
                'detalle' => $detalleDependencia . ' (N° ' . $p['pendiente'] . ')',
                'saldo' => ($p['tot_rendido'] ?? 0) - ($p['tot_recuperado'] ?? 0),
                'origen_id' => $p['idPendientes'],
                'origen_type' => Pendiente::class,
            ];
        });

        // --- Pagos del mes actual ---
        $pagosRecuperacion = Pago::where('relCajaChica_Pagos', $this->cajaChicaSeleccionada->idCajaChica)
            ->where('fechaEgresoPagos', '<=', $fechaRecuperacionActual)
            ->with('acreedor')
            ->selectRaw(
                'tes_cch_pagos.*,
                (montoPagos - CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN recuperadoPagos ELSE 0 END) as saldo_pagos',
                [$fechaRecuperacionActual]
            )
            ->orderBy('fechaEgresoPagos', 'ASC')
            ->get();

        $pagos = $pagosRecuperacion->filter(function ($p) {
            return ($p['saldo_pagos'] ?? 0) > 0;
        })->map(function ($p) {
            $detalleAcreedor = $p['acreedor']['acreedor'] ?? 'Sin dato';
            return [
                'id' => 'pago_' . $p['idPagos'],
                'tipo' => 'Pago Directo',
                'detalle' => $detalleAcreedor . ' - ' . $p['conceptoPagos'],
                'saldo' => $p['saldo_pagos'] ?? 0,
                'origen_id' => $p['idPagos'],
                'origen_type' => Pago::class,
            ];
        });

        // --- Mes Anterior ---
        $mesAnioAnterior = $this->getMesAnioAnterior();
        $cajaChicaAnterior = CajaChica::where('mes', $mesAnioAnterior['mes'])
            ->where('anio', $mesAnioAnterior['anio'])
            ->first();

        $pendientesAnterior = collect();
        $pagosAnterior = collect();

        if ($cajaChicaAnterior) {
            // --- Pendientes del mes anterior ---
            $pendientesRecuperacionAnterior = Pendiente::where('relCajaChica', $cajaChicaAnterior->idCajaChica)
                ->where('fechaPendientes', '<=', $fechaRecuperacionActual)
                ->with('dependencia')
                ->selectRaw(
                    'tes_cch_pendientes.*,
                    (SELECT SUM(rendido) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_rendido,
                    (SELECT SUM(reintegrado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_reintegrado,
                    (SELECT SUM(recuperado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_recuperado',
                    [$fechaRecuperacionActual, $fechaRecuperacionActual, $fechaRecuperacionActual]
                )
                ->orderBy('pendiente', 'ASC')
                ->get();

            $pendientesAnterior = $pendientesRecuperacionAnterior->filter(function ($p) {
                $saldoRendido = ($p['tot_rendido'] ?? 0) - ($p['tot_recuperado'] ?? 0);
                return $saldoRendido > 0;
            })->map(function ($p) {
                $detalleDependencia = $p['dependencia']['dependencia'] ?? 'Sin dato';
                return [
                    'id' => 'pendiente_' . $p['idPendientes'],
                    'tipo' => 'Pendiente (Mes Ant.)',
                    'detalle' => $detalleDependencia . ' (N° ' . $p['pendiente'] . ')',
                    'saldo' => ($p['tot_rendido'] ?? 0) - ($p['tot_recuperado'] ?? 0),
                    'origen_id' => $p['idPendientes'],
                    'origen_type' => Pendiente::class,
                ];
            });

            // --- Pagos del mes anterior ---
            $pagosRecuperacionAnterior = Pago::where('relCajaChica_Pagos', $cajaChicaAnterior->idCajaChica)
                ->where('fechaEgresoPagos', '<=', $fechaRecuperacionActual)
                ->with('acreedor')
                ->selectRaw(
                    'tes_cch_pagos.*,
                    (montoPagos - CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN recuperadoPagos ELSE 0 END) as saldo_pagos',
                    [$fechaRecuperacionActual]
                )
                ->orderBy('fechaEgresoPagos', 'ASC')
                ->get();

            $pagosAnterior = $pagosRecuperacionAnterior->filter(function ($p) {
                return ($p['saldo_pagos'] ?? 0) > 0;
            })->map(function ($p) {
                $detalleAcreedor = $p['acreedor']['acreedor'] ?? 'Sin dato';
                return [
                    'id' => 'pago_' . $p['idPagos'],
                    'tipo' => 'Pago Directo (Mes Ant.)',
                    'detalle' => $detalleAcreedor . ' - ' . $p['conceptoPagos'],
                    'saldo' => $p['saldo_pagos'] ?? 0,
                    'origen_id' => $p['idPagos'],
                    'origen_type' => Pago::class,
                ];
            });
        }

        $items = $pendientes->concat($pendientesAnterior)->concat($pagos)->concat($pagosAnterior)->values();

        if ($items->isEmpty()) {
            session()->flash('message', 'No hay saldos pendientes de recuperar para el período y fecha seleccionados.');
            return;
        }
        $this->itemsParaRecuperar = $items->toArray();
        $this->showRecuperarModal = true;
        $this->dispatchBrowserEvent('show-recuperar-modal');
    }

    public function updatedSeleccionarTodos($value)
    {
        if ($value) {
            $this->itemsSeleccionados = collect($this->itemsParaRecuperar)->pluck('id')->toArray();
        } else {
            $this->itemsSeleccionados = [];
        }
        $this->recalcularTotal();
    }

    public function updatedItemsSeleccionados()
    {
        if (count($this->itemsSeleccionados) === count($this->itemsParaRecuperar)) {
            $this->seleccionarTodos = true;
        } else {
            $this->seleccionarTodos = false;
        }
        $this->recalcularTotal();
    }

    public function recalcularTotal()
    {
        $this->totalARecuperar = collect($this->itemsParaRecuperar)
            ->whereIn('id', $this->itemsSeleccionados)
            ->sum('saldo');
    }

    public function guardarRecuperacion()
    {
        $this->validate([
            'recuperacion.fecha' => 'required|date',
            'recuperacion.numero_ingreso' => 'required|string|max:50',
            'itemsSeleccionados' => 'required|array|min:1',
        ], [
            'recuperacion.fecha.required' => 'La fecha es obligatoria.',
            'recuperacion.numero_ingreso.required' => 'El número de ingreso es obligatorio.',
            'itemsSeleccionados.min' => 'Debe seleccionar al menos un ítem.',
        ]);

        DB::beginTransaction();
        try {
            $fechaRecuperacion = $this->recuperacion['fecha'];
            $nroIngreso = $this->recuperacion['numero_ingreso'];

            $itemsCollection = collect($this->itemsParaRecuperar);

            foreach ($this->itemsSeleccionados as $itemId) {
                $item = $itemsCollection->firstWhere('id', $itemId);

                if (!$item) continue;

                if ($item['origen_type'] === Pendiente::class) {
                    Movimiento::create([
                        'relPendiente' => $item['origen_id'],
                        'fechaMovimientos' => $fechaRecuperacion,
                        'recuperado' => $item['saldo'],
                        'ingresoNro' => $nroIngreso,
                        'rendido' => 0,
                        'reintegrado' => 0,
                        'saldo' => 0,
                    ]);
                } elseif ($item['origen_type'] === Pago::class) {
                    $pago = Pago::find($item['origen_id']);
                    if ($pago) {
                        $pago->recuperadoPagos = ($pago->recuperadoPagos ?? 0) + $item['saldo'];
                        $pago->fechaIngresoPagos = $fechaRecuperacion;
                        $pago->ingresoPagos = $nroIngreso;
                        $pago->save();
                    }
                }
            }

            DB::commit();
            $this->closeRecuperarModal();
            $this->cargarDatos();
            session()->flash('message', 'Recuperación guardada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error al guardar la recuperación: ' . $e->getMessage());
        }
    }

    public function closeRecuperarModal()
    {
        $this->showRecuperarModal = false;
        $this->reset(['recuperacion', 'itemsParaRecuperar', 'itemsSeleccionados', 'totalARecuperar', 'seleccionarTodos']);
        $this->resetErrorBag();
        $this->cargarDatos();
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

            if (abs($montoAnterior - $montoNuevo) < 0.01) {
                $this->cerrarModalEditFondo();
                session()->flash('message', 'No se realizaron cambios en el monto del fondo.');
                return;
            }

            $fondo->montoCajaChica = $montoNuevo;
            $fondo->save();

            $this->cargarDatos();
            $this->cerrarModalEditFondo();

            $mensaje = sprintf(
                'Fondo actualizado exitosamente. Monto anterior: $%s, Monto nuevo: $%s',
                number_format($montoAnterior, 2, ',', '.'),
                number_format($montoNuevo, 2, ',', '.')
            );

            session()->flash('message', $mensaje);

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
        $this->cargarDatos();
    }

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

    public function mostrarModalNuevoFondo()
    {
        $this->nuevoFondo['mes'] = $this->mesActual;
        $this->nuevoFondo['anio'] = $this->anioActual;
        $this->nuevoFondo['monto'] = '0';

        $this->emitTo('tesoreria.caja-chica.modal-nuevo-fondo', 'mostrarModalNuevoFondo');
    }

    public function prepararModalNuevoPago()
    {
        if ($this->cajaChicaSeleccionada) {
            $this->emitTo('tesoreria.caja-chica.modal-nuevo-pago', 'mostrarModalNuevoPago', $this->cajaChicaSeleccionada->idCajaChica);
        } else {
            session()->flash('error', 'No se ha determinado Fondo Permanente para el mes y año de trabajo actual.');
        }
    }

    public function establecerFechaHoy()
    {
        $this->fechaHasta = now()->format('Y-m-d');
        $this->cargarDatos();
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
    private function getMesAnioAnterior()
    {
        $meses = [
            'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
            'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
            'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
        ];

        // Asegurarse de que el nombre del mes esté en minúsculas para la búsqueda
        $mesActualLower = strtolower($this->mesActual);

        $mesNumero = $meses[$mesActualLower] ?? null;

        if (is_null($mesNumero)) {
            // Esto no debería ocurrir si translatedFormat('F') funciona correctamente,
            // pero es una salvaguarda.
            session()->flash('error', 'Error interno: Nombre de mes no reconocido: ' . $this->mesActual);
            return ['mes' => '', 'anio' => '']; // Retornar vacío para evitar más errores
        }

        $fechaActual = Carbon::create($this->anioActual, $mesNumero, 1);
        $fechaAnterior = $fechaActual->subMonth();

        return [
            'mes' => strtolower($fechaAnterior->locale('es_ES')->isoFormat('MMMM')),
            'anio' => $fechaAnterior->year
        ];
    }

    // --- Listeners ---

    public function cargarDependencias()
    {
        $this->dependencias = Dependencia::orderBy('dependencia', 'ASC')->get();
    }

    public function abrirModalDependencias()
    {
        $this->mostrarModalDependencias = true;
    }

    public function cerrarModalDependencias()
    {
        $this->mostrarModalDependencias = false;
        $this->cargarDatos();
    }

    public function abrirModalAcreedores()
    {
        $this->mostrarModalAcreedores = true;
    }

    public function cerrarModalAcreedores()
    {
        $this->mostrarModalAcreedores = false;
        $this->cargarDatos();
    }

    // --- Renderizado ---
    public function render()
    {
        return view('livewire.tesoreria.caja-chica.index');
    }
}
