<?php

namespace App\Http\Livewire\Tesoreria\CajaChica;

use Livewire\Component;
use App\Models\Tesoreria\CajaChica;
use App\Models\Tesoreria\Pendiente;
use App\Models\Tesoreria\Movimiento;
use App\Models\Tesoreria\Pago;
use App\Models\Tesoreria\Dependencia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
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
        'id' => null,
        'mes' => '',
        'anio' => '',
        'monto' => '',
        'montoOriginal' => ''
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

    // --- Propiedades para el Modal de Recuperación de Rendido ---
    public $showRecuperarRendidoModal = false;
    public $recuperarRendidoData = [
        'relPendiente' => null,
        'fecha' => '',
        'documentos' => '',
        'monto_rendido' => 0,
        'monto_reintegrado' => 0,
        'monto_recuperado' => 0,
    ];
    public $selectedPendienteId;
    public $modalRecuperarRendidoError = null;
    public $modalRecuperarRendidoMessage = null;

    // Propiedades para recuperación de pagos directos
    public $showRecuperarPagoModal = false;
    public $recuperarPagoData = [
        'relPago' => null,
        'fecha' => '',
        'numero_ingreso' => '',
        'numero_ingreso_bse' => '',
        'monto_recuperado' => 0,
    ];
    public $selectedPagoId;
    public $modalRecuperarPagoError = null;
    public $modalRecuperarPagoMessage = null;



    protected $queryString = [
        // Removido mostrarModalDependencias para evitar re-renderizado
    ];

    public function mostrarAlertaSweet($data)
    {
        $this->dispatchBrowserEvent('swal', $data);
    }

    protected $listeners = [
        'cargarDependencias',
        'fondoCreado' => 'flushCacheAndReload',
        'fondoActualizado' => 'flushCacheAndReload',
        'pendienteCreado' => 'flushCacheAndReload',
        'pagoCreado' => 'flushCacheAndReload',
        'mostrarAlerta' => 'mostrarAlertaSweet',
    ];

    public function flushCacheAndReload()
    {
        Cache::flush();
        $this->cargarDatos();
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

        if ($this->showRecuperarRendidoModal) {
            $rules['recuperarRendidoData.fecha'] = 'required|date';
            $rules['recuperarRendidoData.documentos'] = 'nullable|string|max:255';
            $rules['recuperarRendidoData.monto_recuperado'] = 'required|numeric|min:0.01|max:99999999.99';
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
            'recuperarRendidoData.fecha.required' => 'La fecha es obligatoria.',
            'recuperarRendidoData.documentos.max' => 'Los documentos no pueden exceder los 255 caracteres.',
            'recuperarRendidoData.monto_recuperado.required' => 'El monto recuperado es obligatorio.',
            'recuperarRendidoData.monto_recuperado.numeric' => 'El monto recuperado debe ser un número válido.',
            'recuperarRendidoData.monto_recuperado.min' => 'El monto recuperado debe ser al menos 0.01.',
            'recuperarRendidoData.monto_recuperado.max' => 'El monto recuperado no puede exceder 99,999,999.99.',
        ];
    }

    public function mount()
    {
        $this->mesActual = session('caja_chica_mes') ?: now()->locale('es')->translatedFormat('F');
        $this->anioActual = session('caja_chica_anio') ?: now()->year;
        $this->fechaHasta = now()->format('Y-m-d');
        $this->tablaCajaChica = collect();
        $this->tablaPendientesDetalle = collect();
        $this->tablaPagos = collect();
        $this->dependencias = collect();
        $this->itemsParaRecuperar = [];
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

    public function irAEditar($id)
    {
        session(['caja_chica_mes' => $this->mesActual, 'caja_chica_anio' => $this->anioActual]);
        return redirect()->route('tesoreria.caja-chica.pendientes.editar', $id);
    }

    public function cargarDatos()
    {
        $cacheKey = 'caja_chica_report_' . $this->mesActual . '_' . $this->anioActual . '_' . $this->fechaHasta;

        $data = Cache::remember($cacheKey, now()->addHours(1), function () {
            $this->cargarTablaCajaChica();
            $this->cargarTablaPendientesDetalle();
            $this->cargarTablaPagos();
            return [
                'tablaCajaChica' => $this->tablaCajaChica,
                'tablaPendientesDetalle' => $this->tablaPendientesDetalle,
                'tablaPagos' => $this->tablaPagos,
                'cajaChicaSeleccionada' => $this->cajaChicaSeleccionada,
            ];
        });

        $this->tablaCajaChica = $data['tablaCajaChica'];
        $this->tablaPendientesDetalle = $data['tablaPendientesDetalle'];
        $this->tablaPagos = $data['tablaPagos'];
        $this->cajaChicaSeleccionada = $data['cajaChicaSeleccionada'];

        if ($this->cajaChicaSeleccionada) {
            $this->nuevoPendiente['relCajaChica'] = $this->cajaChicaSeleccionada->idCajaChica;
        } else {
            $this->nuevoPendiente['relCajaChica'] = null;
        }

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
                    (SELECT SUM(rendido) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_rendido,
                    (SELECT SUM(reintegrado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_reintegrado,
                    (SELECT SUM(recuperado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_recuperado',
                    [$fechaHastaStr, $fechaHastaStr, $fechaHastaStr]
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
                        (SELECT SUM(rendido) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_rendido,
                        (SELECT SUM(reintegrado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_reintegrado,
                        (SELECT SUM(recuperado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_recuperado',
                        [$fechaHastaStr, $fechaHastaStr, $fechaHastaStr]
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
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Error al cargar pendientes: ' . $e->getMessage()]);
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
                    (montoPagos - CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN recuperadoPagos ELSE 0 END) as saldo_pagos,
                    CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN recuperadoPagos ELSE 0 END as recuperado_en_periodo',
                    [$fechaHastaStr, $fechaHastaStr]
                )
                ->orderBy('fechaEgresoPagos', 'ASC')
                ->get()
                ->map(function ($pago) {
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
                        (montoPagos - CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN recuperadoPagos ELSE 0 END) as saldo_pagos,
                        CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN recuperadoPagos ELSE 0 END as recuperado_en_periodo',
                        [$fechaHastaStr, $fechaHastaStr]
                    )
                    ->orderBy('fechaEgresoPagos', 'ASC')
                    ->get()
                    ->map(function ($pago) {
                        $pago->es_mes_anterior = true;
                        return $pago;
                    })
                    ->filter(function ($pago) {
                        return ($pago['saldo_pagos'] ?? 0) > 0;
                    });
            }

            $this->tablaPagos = $pagosActual->concat($pagosAnterior)->sortBy('fechaEgresoPagos')->values();
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Error al cargar pagos: ' . $e->getMessage()]);
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
            $pagosConEgreso = $this->tablaPagos->filter(function ($p) {
                $egreso = $p['egresoPagos'] ?? null;
                return !is_null($egreso) && trim((string)$egreso) !== '';
            });
            $pagosSinEgreso = $this->tablaPagos->filter(function ($p) {
                $egreso = $p['egresoPagos'] ?? null;
                return is_null($egreso) || trim((string)$egreso) === '';
            });

            $saldoPagosConEgreso = $pagosConEgreso->sum(function ($p) {
                return ($p['montoPagos'] ?? 0) - ($p['recuperado_en_periodo'] ?? 0);
            });
            $saldoPagosSinEgreso = $pagosSinEgreso->sum(function ($p) {
                return ($p['montoPagos'] ?? 0) - ($p['recuperado_en_periodo'] ?? 0);
            });

            // Saldo de pagos directos con egreso (para totales y recuperar)
            $this->tablaTotales['Saldo Pagos Directos'] = $saldoPagosConEgreso;
            // Saldo de pagos directos sin egreso (visible en totales pero excluido de 'Recuperar')
            $this->tablaTotales['Pagos Sin Egreso'] = $saldoPagosSinEgreso;

            // Saldo total considera ambos tipos de pagos
            $stSaldo = $montoCajaChica - $stPendientes - $stRendidos - $stExtras - $saldoPagosConEgreso - $saldoPagosSinEgreso;
            $this->tablaTotales['Saldo Total'] = $stSaldo;
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Error al calcular los totales: ' . $e->getMessage()]);
            $this->tablaTotales = [];
        } catch (\Error $e) {
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Error fatal al calcular los totales. Por favor, contacte al administrador.']);
            $this->tablaTotales = [];
        }
    }

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
        // Nota: Excluir pagos sin número de egreso del proceso de recuperación
        $pagosRecuperacion = Pago::where('relCajaChica_Pagos', $this->cajaChicaSeleccionada->idCajaChica)
            ->where('fechaEgresoPagos', '<=', $fechaRecuperacionActual)
            ->whereRaw("TRIM(egresoPagos) <> ''")
            ->with('acreedor')
            ->selectRaw(
                'tes_cch_pagos.*,
                (montoPagos - CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN recuperadoPagos ELSE 0 END) as saldo_pagos,
                CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN recuperadoPagos ELSE 0 END as recuperado_en_periodo',
                [$fechaRecuperacionActual, $fechaRecuperacionActual]
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
            // Nota: Excluir pagos sin número de egreso del proceso de recuperación
            $pagosRecuperacionAnterior = Pago::where('relCajaChica_Pagos', $cajaChicaAnterior->idCajaChica)
                ->where('fechaEgresoPagos', '<=', $fechaRecuperacionActual)
                ->whereRaw("TRIM(egresoPagos) <> ''")
                ->with('acreedor')
                ->selectRaw(
                    'tes_cch_pagos.*,
                    (montoPagos - CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN recuperadoPagos ELSE 0 END) as saldo_pagos,
                    CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN recuperadoPagos ELSE 0 END as recuperado_en_periodo',
                    [$fechaRecuperacionActual, $fechaRecuperacionActual]
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
            $this->dispatchBrowserEvent('swal:success', ['text' => 'No hay saldos pendientes de recuperar para el período y fecha seleccionados.']);
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

        DB::transaction(function () {
            $fechaRecuperacion = $this->recuperacion['fecha'];
            $nroIngreso = $this->recuperacion['numero_ingreso'];

            // Procesar el número de ingreso
            if (ctype_digit($nroIngreso)) {
                $nroIngreso = "INGRESO " . $nroIngreso;
            }

            $itemsCollection = collect($this->itemsParaRecuperar);

            foreach ($this->itemsSeleccionados as $itemId) {
                $item = $itemsCollection->firstWhere('id', $itemId);

                if (!$item) continue;

                if ($item['origen_type'] === Pendiente::class) {
                    Movimiento::create([
                        'relPendiente' => $item['origen_id'],
                        'fechaMovimientos' => $fechaRecuperacion,
                        'recuperado' => $item['saldo'],
                        'documentos' => $nroIngreso,
                        'rendido' => 0,
                        'reintegrado' => 0,
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
            Cache::flush();
        });

        $this->closeRecuperarModal();
        $this->cargarDatos();
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Recuperación guardada exitosamente.']);
    }

    public function closeRecuperarModal()
    {
        $this->showRecuperarModal = false;
        $this->reset(['recuperacion', 'itemsParaRecuperar', 'itemsSeleccionados', 'totalARecuperar', 'seleccionarTodos']);
        $this->resetErrorBag();
        $this->cargarDatos();
    }

    // --- Métodos para el Modal de Recuperación de Rendido ---
    public function openRecuperarRendidoModal($pendienteId)
    {
        $this->cargarDatos();
        $this->resetErrorBag();
        $this->selectedPendienteId = $pendienteId;
        $pendiente = Pendiente::find($pendienteId);

        if (!$pendiente) {
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Pendiente no encontrado.']);
            return;
        }

        // Recalcular tot_rendido para este pendiente específico considerando fechaHasta
        $fechaHastaStr = Carbon::createFromFormat('Y-m-d', $this->fechaHasta)->endOfDay()->toDateTimeString();
        $tot_rendido = Movimiento::where('relPendiente', $pendienteId)
            ->where('fechaMovimientos', '<=', $fechaHastaStr)
            ->sum('rendido');
        $tot_recuperado_existente = Movimiento::where('relPendiente', $pendienteId)
            ->where('fechaMovimientos', '<=', $fechaHastaStr)
            ->sum('recuperado');

        $montoRecuperable = $tot_rendido - $tot_recuperado_existente;

        $this->recuperarRendidoData = [
            'relPendiente' => $pendienteId,
            'fecha' => now()->format('Y-m-d'),
            'documentos' => '',
            'monto_rendido' => 0,
            'monto_reintegrado' => 0,
            'monto_recuperado' => max(0, $montoRecuperable), // Asegura que no sea negativo
        ];

        $this->showRecuperarRendidoModal = true;
        $this->dispatchBrowserEvent('show-recuperar-rendido-modal');
    }

    public function saveRecuperarRendido()
    {
        $this->reset(['modalRecuperarRendidoError', 'modalRecuperarRendidoMessage']);
        $this->validate([
            'recuperarRendidoData.fecha' => 'required|date',
            'recuperarRendidoData.documentos' => 'nullable|string|max:255',
            'recuperarRendidoData.monto_recuperado' => 'required|numeric|min:0.01|max:99999999.99',
        ], [
            'recuperarRendidoData.fecha.required' => 'La fecha es obligatoria.',
            'recuperarRendidoData.documentos.max' => 'Los documentos no pueden exceder los 255 caracteres.',
            'recuperarRendidoData.monto_recuperado.required' => 'El monto recuperado es obligatorio.',
            'recuperarRendidoData.monto_recuperado.numeric' => 'El monto recuperado debe ser un número válido.',
            'recuperarRendidoData.monto_recuperado.min' => 'El monto recuperado debe ser al menos 0.01.',
            'recuperarRendidoData.monto_recuperado.max' => 'El monto recuperado no puede exceder 99,999,999.99.',
        ]);

        DB::transaction(function () {
            $pendiente = Pendiente::find($this->recuperarRendidoData['relPendiente']);

            if (!$pendiente) {
                $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Pendiente no encontrado para la validación.']);
                return;
            }

            // Recalcular tot_rendido y tot_recuperado para este pendiente específico con datos frescos considerando fechaHasta
            $fechaHastaStr = Carbon::createFromFormat('Y-m-d', $this->fechaHasta)->endOfDay()->toDateTimeString();
            $tot_rendido_actual = Movimiento::where('relPendiente', $pendiente->idPendientes)
                ->where('fechaMovimientos', '<=', $fechaHastaStr)
                ->sum('rendido');
            $tot_recuperado_existente_actual = Movimiento::where('relPendiente', $pendiente->idPendientes)
                ->where('fechaMovimientos', '<=', $fechaHastaStr)
                ->sum('recuperado');

            $montoRecuperableActual = $tot_rendido_actual - $tot_recuperado_existente_actual;

            if ($this->recuperarRendidoData['monto_recuperado'] > $montoRecuperableActual) {
                $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'El monto a recuperar ( ' . number_format($this->recuperarRendidoData['monto_recuperado'], 2, ',', '.') . ' ) no puede ser mayor que el saldo rendido actual del pendiente ( ' . number_format($montoRecuperableActual, 2, ',', '.') . ' ).']);
                return;
            }

            Movimiento::create([
                'relPendiente' => $this->recuperarRendidoData['relPendiente'],
                'fechaMovimientos' => $this->recuperarRendidoData['fecha'],
                'documentoMovimiento' => $this->recuperarRendidoData['documentos'],
                'rendido' => 0, // Siempre 0 para este tipo de movimiento
                'reintegrado' => 0, // Siempre 0 para este tipo de movimiento
                'recuperado' => $this->recuperarRendidoData['monto_recuperado'],
                'saldo' => 0, // El saldo se recalcula en el modelo o vista
            ]);
            Cache::flush();
        });

        $this->cargarDatos();
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Dinero rendido recuperado exitosamente.']);
        $this->closeRecuperarRendidoModal();
    }

    public function closeRecuperarRendidoModal()
    {
        $this->showRecuperarRendidoModal = false;
        $this->reset(['recuperarRendidoData', 'selectedPendienteId', 'modalRecuperarRendidoError', 'modalRecuperarRendidoMessage']);
        $this->resetErrorBag();
        $this->cargarDatos();
    }

    // --- Métodos para Recuperación de Pagos Directos ---

    public function openRecuperarPagoModal($pagoId)
    {
        $this->cargarDatos();
        $this->selectedPagoId = $pagoId;
        $pago = Pago::with('acreedor')->findOrFail($pagoId);

        $this->recuperarPagoData = [
            'relPago' => $pagoId,
            'fecha' => now()->format('Y-m-d'),
            'numero_ingreso' => '',
            'numero_ingreso_bse' => '',
            'monto_recuperado' => $pago->montoPagos - $pago->recuperadoPagos,
            'es_banco_bse' => ($pago->acreedor->acreedor ?? '') === 'Banco de Seguros del Estado',
        ];

        $this->showRecuperarPagoModal = true;
        $this->resetErrorBag();
    }

    public function closeRecuperarPagoModal()
    {
        $this->showRecuperarPagoModal = false;
        $this->reset(['recuperarPagoData', 'selectedPagoId', 'modalRecuperarPagoError', 'modalRecuperarPagoMessage']);
        $this->resetErrorBag();
        $this->cargarDatos();
    }

    public function saveRecuperarPago()
    {
        $this->reset(['modalRecuperarPagoError', 'modalRecuperarPagoMessage']);

        // Reglas de validación base
        $rules = [
            'recuperarPagoData.fecha' => 'required|date',
            'recuperarPagoData.numero_ingreso' => 'required|string|max:255',
            'recuperarPagoData.monto_recuperado' => 'required|numeric|min:0.01|max:99999999.99',
        ];

        // Si es Banco de Seguros del Estado, hacer obligatorio el campo BSE
        if ($this->recuperarPagoData['es_banco_bse']) {
            $rules['recuperarPagoData.numero_ingreso_bse'] = 'required|string|max:255';
        } else {
            $rules['recuperarPagoData.numero_ingreso_bse'] = 'nullable|string|max:255';
        }

        $this->validate($rules, [
            'recuperarPagoData.fecha.required' => 'La fecha es obligatoria.',
            'recuperarPagoData.numero_ingreso.required' => 'El número de ingreso es obligatorio.',
            'recuperarPagoData.numero_ingreso.max' => 'El número de ingreso no puede exceder los 255 caracteres.',
            'recuperarPagoData.numero_ingreso_bse.required' => 'El número de ingreso BSE es obligatorio para Banco de Seguros del Estado.',
            'recuperarPagoData.numero_ingreso_bse.max' => 'El número de ingreso BSE no puede exceder los 255 caracteres.',
            'recuperarPagoData.monto_recuperado.required' => 'El monto recuperado es obligatorio.',
            'recuperarPagoData.monto_recuperado.numeric' => 'El monto recuperado debe ser un número válido.',
            'recuperarPagoData.monto_recuperado.min' => 'El monto recuperado debe ser al menos 0.01.',
            'recuperarPagoData.monto_recuperado.max' => 'El monto recuperado no puede exceder 99,999,999.99.',
        ]);

        DB::transaction(function () {
            $pago = Pago::find($this->recuperarPagoData['relPago']);

            if (!$pago) {
                $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Pago no encontrado para la validación.']);
                return;
            }

            // Verificar que el monto a recuperar no exceda el saldo disponible
            $saldoDisponible = $pago->montoPagos - $pago->recuperadoPagos;

            if ($this->recuperarPagoData['monto_recuperado'] > $saldoDisponible) {
                $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'El monto a recuperar (' . number_format($this->recuperarPagoData['monto_recuperado'], 2, ',', '.') . ') no puede ser mayor que el saldo disponible del pago (' . number_format($saldoDisponible, 2, ',', '.') . ').']);
                return;
            }

            // Actualizar el pago con la información de recuperación
            $pago->update([
                'recuperadoPagos' => $pago->recuperadoPagos + $this->recuperarPagoData['monto_recuperado'],
                'fechaIngresoPagos' => $this->recuperarPagoData['fecha'],
                'ingresoPagos' => $this->recuperarPagoData['numero_ingreso'],
                'ingresoPagosBSE' => $this->recuperarPagoData['numero_ingreso_bse'],
            ]);
            Cache::flush();
        });

        $this->cargarDatos();
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Pago directo recuperado exitosamente.']);
        $this->closeRecuperarPagoModal();
    }

    // --- Métodos para Modal de Acreedores ---

    // --- Métodos de Acción para Editar Fondo ---

    public function editarFondo($idCajaChica, $montoActual)
    {
        $this->cargarDatos();
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
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Error al cargar los datos del fondo: ' . $e->getMessage()]);
        }
    }

    public function actualizarFondo()
    {
        $this->validate();

        DB::transaction(function () {
            $fondo = CajaChica::findOrFail($this->editandoFondo['id']);
            $montoAnterior = $fondo->montoCajaChica;
            $montoNuevo = floatval($this->editandoFondo['monto']);

            if (abs($montoAnterior - $montoNuevo) < 0.01) {
                return; // No hay cambios, no hacer nada
            }

            $fondo->montoCajaChica = $montoNuevo;
            $fondo->save();

            Cache::flush();
        });

        // Mover la lógica de UI fuera de la transacción
        $fondo = CajaChica::find($this->editandoFondo['id']);
        $montoAnterior = floatval($this->editandoFondo['montoOriginal']);
        $montoNuevo = $fondo->montoCajaChica;

        if (abs($montoAnterior - $montoNuevo) < 0.01) {
            $this->cerrarModalEditFondo();
            $this->dispatchBrowserEvent('swal:success', ['text' => 'No se realizaron cambios en el monto del fondo.']);
            return;
        }

        $this->cargarDatos();
        $this->cerrarModalEditFondo();

        $mensaje = sprintf(
            'Fondo actualizado exitosamente. Monto anterior: $%s, Monto nuevo: $%s',
            number_format($montoAnterior, 2, ',', '.'),
            number_format($montoNuevo, 2, ',', '.')
        );

        $this->dispatchBrowserEvent('swal:success', ['text' => $mensaje]);

        $this->dispatchBrowserEvent('fondo-actualizado', [
            'message' => 'Fondo actualizado exitosamente',
            'montoAnterior' => $montoAnterior,
            'montoNuevo' => $montoNuevo
        ]);
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
        $this->cargarDatos();
        if ($this->cajaChicaSeleccionada) {
            $this->emitTo('tesoreria.caja-chica.modal-nuevo-pendiente', 'mostrarModalNuevoPendiente', $this->cajaChicaSeleccionada->idCajaChica);
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

        $this->emitTo('tesoreria.caja-chica.modal-nuevo-fondo', 'mostrarModalNuevoFondo');
    }

    public function prepararModalNuevoPago()
    {
        $this->cargarDatos();
        if ($this->cajaChicaSeleccionada) {
            $this->emitTo('tesoreria.caja-chica.modal-nuevo-pago', 'mostrarModalNuevoPago', $this->cajaChicaSeleccionada->idCajaChica);
        } else {
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'No se ha determinado Fondo Permanente para el mes y año de trabajo actual.']);
        }
    }

    public function cerrarModalNuevoPendiente()
    {
        $this->emitTo('tesoreria.caja-chica.modal-nuevo-pendiente', 'cerrarModalNuevoPendiente');
        $this->cargarDatos();
    }

    public function cerrarModalNuevoPago()
    {
        $this->emitTo('tesoreria.caja-chica.modal-nuevo-pago', 'cerrarModalNuevoPago');
        $this->cargarDatos();
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
            'enero' => 1,
            'febrero' => 2,
            'marzo' => 3,
            'abril' => 4,
            'mayo' => 5,
            'junio' => 6,
            'julio' => 7,
            'agosto' => 8,
            'septiembre' => 9,
            'octubre' => 10,
            'noviembre' => 11,
            'diciembre' => 12
        ];

        // Asegurarse de que el nombre del mes esté en minúsculas para la búsqueda
        $mesActualLower = strtolower($this->mesActual);

        $mesNumero = $meses[$mesActualLower] ?? null;

        if (is_null($mesNumero)) {
            // Esto no debería ocurrir si translatedFormat('F') funciona correctamente,
            // pero es una salvaguarda.
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Error interno: Nombre de mes no reconocido: ' . $this->mesActual]);
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

    // --- Renderizado ---
    public function render()
    {
        return view('livewire.tesoreria.caja-chica.index');
    }
}
