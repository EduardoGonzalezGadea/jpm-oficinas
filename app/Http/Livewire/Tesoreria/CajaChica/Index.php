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
        'fondoCreado' => 'cargarDatos',
        'fondoActualizado' => 'cargarDatos',
        'pendienteCreado' => 'cargarDatos',
        'pagoCreado' => 'cargarDatos',
        'mostrarAlerta' => 'mostrarAlertaSweet',
        'eliminarPendiente' => 'eliminarPendiente',
        'eliminarPago' => 'eliminarPago',
    ];

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
        $this->mesActual = strtolower(session('caja_chica_mes') ?: now()->locale('es')->translatedFormat('F'));
        $this->anioActual = session('caja_chica_anio') ?: now()->year;
        $this->fechaHasta = now()->format('Y-m-d');
        $this->tablaCajaChica = collect();
        $this->tablaPendientesDetalle = collect();
        $this->tablaPagos = collect();
        $this->dependencias = collect();
        $this->itemsParaRecuperar = [];
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
        $this->cargarTablaPendientesDetalle();
    }

    public function limpiarFiltroPendientes()
    {
        $this->searchPendientes = '';
        $this->cargarTablaPendientesDetalle();
    }

    public function updatedSearchPagos()
    {
        $this->cargarTablaPagos();
    }

    public function limpiarFiltroPagos()
    {
        $this->searchPagos = '';
        $this->cargarTablaPagos();
    }

    public function irAEditar($id)
    {
        session(['caja_chica_mes' => $this->mesActual, 'caja_chica_anio' => $this->anioActual]);
        return redirect()->route('tesoreria.caja-chica.pendientes.editar', $id);
    }

    public function cargarDatos()
    {
        $this->cargarTablaCajaChica();
        $this->cargarTablaPendientesDetalle();
        $this->cargarTablaPagos();
        $this->cargarTablaTotales();
        $this->cargarDependenciasSinPendientes();
    }

    public function cargarTablaCajaChica()
    {
        $this->tablaCajaChica = CajaChica::where(function ($query) {
            $query->where('mes', $this->mesActual)
                ->orWhere('mes', ucfirst($this->mesActual))
                ->orWhere('mes', strtolower($this->mesActual));
        })
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
        try {
            $fechaHastaStr = $this->fechaHasta ? Carbon::parse($this->fechaHasta)->endOfDay()->toDateTimeString() : now()->endOfDay()->toDateTimeString();

            // Determinar el primer día del mes seleccionado para separar "actual" de "anteriores"
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
            $mesNo = $meses[strtolower($this->mesActual)] ?? now()->month;
            $primerDiaMesActual = Carbon::create($this->anioActual, $mesNo, 1)->startOfMonth();

            // 1. Pendientes del MES SELECCIONADO (vinculados a la Caja Chica seleccionada)
            $pendientesActual = collect();
            if ($this->cajaChicaSeleccionada) {
                $pendientesActual = Pendiente::where('relCajaChica', $this->cajaChicaSeleccionada->idCajaChica)
                    ->where('fechaPendientes', '<=', $fechaHastaStr)
                    ->with('dependencia')
                    ->selectRaw(
                        'tes_cch_pendientes.*,
                        (SELECT SUM(rendido) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_rendido,
                        (SELECT SUM(reintegrado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_reintegrado,
                        (SELECT SUM(recuperado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_recuperado,
                        (SELECT COUNT(*) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL AND rendido > 0 AND (documentos IS NULL OR TRIM(documentos) = \'\')) as count_undoc,
                        (SELECT COUNT(*) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND deleted_at IS NULL) as cant_movimientos',
                        [$fechaHastaStr, $fechaHastaStr, $fechaHastaStr, $fechaHastaStr]
                    )
                    ->get();
            }

            $pendientesActual = $pendientesActual->map(function ($p) {
                $p->tot_rendido = $p->tot_rendido ?? 0;
                $p->tot_reintegrado = $p->tot_reintegrado ?? 0;
                $p->tot_recuperado = $p->tot_recuperado ?? 0;

                $totalMovs = $p->tot_rendido + $p->tot_reintegrado;

                // Cálculo de Extra
                $diferencia = $totalMovs > 0 ? $totalMovs - $p->montoPendientes : 0;
                $p->extra = $diferencia > 0 ? $diferencia : 0;

                // Lógica de Saldo Final (Validada con casos de prueba)
                // Lógica de Saldo Final (Validada con casos de prueba)
                // Usar round para evitar problemas de coma flotante
                if (round($totalMovs, 2) > round($p->montoPendientes, 2)) {
                    // Caso con Extra: Saldo es todo lo movido (Rendido + Reintegrado) menos lo recuperado
                    $p->saldo = $totalMovs - $p->tot_recuperado;
                } else {
                    // Caso Normal: Saldo es el Monto original menos lo ya devuelto (Reintegrado + Recuperado)
                    $p->saldo = $p->montoPendientes - $p->tot_reintegrado - $p->tot_recuperado;
                }
                $p->saldo = max(0, $p->saldo);

                if (($p->count_undoc ?? 0) > 0) {
                    $p->rendido_sin_docs_calc = ($p->tot_rendido - $p->tot_recuperado);
                } else {
                    $p->rendido_sin_docs_calc = 0;
                }

                // Calcular extra pendiente (no recuperado)
                if ($p->extra > 0) {
                    $saldo_gasto = $p->tot_rendido - $p->tot_recuperado;
                    $p->extra_pendiente = max(0, min($p->extra, $saldo_gasto));
                } else {
                    $p->extra_pendiente = 0;
                }

                $p->es_mes_anterior = false;
                return $p;
            });

            $inicioMesAnterior = (clone $primerDiaMesActual)->subMonth()->startOfMonth();

            // 2. Pendientes del MES ANTERIOR (fecha >= inicioMesAnterior y fecha < primer día mes actual)
            $pendientesAnteriores = Pendiente::where('fechaPendientes', '>=', $inicioMesAnterior->toDateTimeString())
                ->where('fechaPendientes', '<', $primerDiaMesActual->toDateTimeString())
                ->where('fechaPendientes', '<=', $fechaHastaStr)
                ->with('dependencia')
                ->selectRaw(
                    'tes_cch_pendientes.*,
                    (SELECT SUM(rendido) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_rendido,
                    (SELECT SUM(reintegrado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_reintegrado,
                    (SELECT SUM(recuperado) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL) as tot_recuperado,
                    (SELECT COUNT(*) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND fechaMovimientos <= ? AND deleted_at IS NULL AND rendido > 0 AND (documentos IS NULL OR TRIM(documentos) = \'\')) as count_undoc,
                    (SELECT COUNT(*) FROM tes_cch_movimientos WHERE tes_cch_movimientos.relPendiente = tes_cch_pendientes.idPendientes AND deleted_at IS NULL) as cant_movimientos',
                    [$fechaHastaStr, $fechaHastaStr, $fechaHastaStr, $fechaHastaStr]
                )
                ->get();

            $idActuales = $pendientesActual->pluck('idPendientes')->toArray();

            $pendientesAnteriores = $pendientesAnteriores->filter(function ($p) use ($idActuales) {
                return !in_array($p->idPendientes, $idActuales);
            })->map(function ($p) {
                $p->tot_rendido = $p->tot_rendido ?? 0;
                $p->tot_reintegrado = $p->tot_reintegrado ?? 0;
                $p->tot_recuperado = $p->tot_recuperado ?? 0;

                $totalMovs = $p->tot_rendido + $p->tot_reintegrado;

                // Cálculo de Extra
                $diferencia = $totalMovs > 0 ? $totalMovs - $p->montoPendientes : 0;
                $p->extra = $diferencia > 0 ? $diferencia : 0;

                // Lógica de Saldo Final (Validada con casos de prueba)
                // Lógica de Saldo Final (Validada con casos de prueba)
                if (round($totalMovs, 2) > round($p->montoPendientes, 2)) {
                    $p->saldo = $totalMovs - $p->tot_recuperado;
                } else {
                    $p->saldo = $p->montoPendientes - $p->tot_reintegrado - $p->tot_recuperado;
                }
                $p->saldo = max(0, $p->saldo);

                if (($p->count_undoc ?? 0) > 0) {
                    $p->rendido_sin_docs_calc = ($p->tot_rendido - $p->tot_recuperado);
                } else {
                    $p->rendido_sin_docs_calc = 0;
                }

                // Calcular extra pendiente (no recuperado)
                if ($p->extra > 0) {
                    $saldo_gasto = $p->tot_rendido - $p->tot_recuperado;
                    $p->extra_pendiente = max(0, min($p->extra, $saldo_gasto));
                } else {
                    $p->extra_pendiente = 0;
                }

                $p->es_mes_anterior = true;
                return $p;
            })->filter(function ($p) {
                return round($p->saldo, 2) > 0;
            });

            $allPendientes = $pendientesActual->concat($pendientesAnteriores)->sortBy('pendiente')->values();

            // Aplicar filtro de búsqueda si existe
            if (!empty($this->searchPendientes)) {
                $search = mb_strtolower($this->searchPendientes, 'UTF-8');
                $allPendientes = $allPendientes->filter(function ($p) use ($search) {
                    $numero = mb_strtolower((string)$p->pendiente, 'UTF-8');
                    $dependencia = mb_strtolower($p->dependencia->dependencia ?? '', 'UTF-8');
                    $monto = number_format($p->montoPendientes, 2, ',', '.');
                    return str_contains($numero, $search) || str_contains($dependencia, $search) || str_contains($monto, $search);
                })->values();
            }

            $this->tablaPendientesDetalle = $allPendientes->map(function ($item) {
                // Convertir a array para evitar problemas livewire con stdClass
                $arr = $item->toArray();

                // INYECTAR MANUALMENTE LAS PROPIEDADES CALCULADAS
                // toArray() de Eloquent ignora propiedades dinámicas no incluidas en attributes/appends,
                // o usa accessors que sobreescriben nuestros cálculos.
                $arr['saldo'] = $item->saldo;
                $arr['extra'] = $item->extra;
                $arr['extra_pendiente'] = $item->extra_pendiente;
                $arr['tot_rendido'] = $item->tot_rendido;
                $arr['tot_reintegrado'] = $item->tot_reintegrado;
                $arr['tot_recuperado'] = $item->tot_recuperado;
                $arr['es_mes_anterior'] = $item->es_mes_anterior;

                // Asegurar estructura de dependencia
                $arr['dependencia'] = $arr['dependencia'] ?? ['dependencia' => ''];
                if (is_object($arr['dependencia'])) { // Por si acaso toArray no fue profundo (Eloquent toArray lo es)
                    $arr['dependencia'] = (array) $arr['dependencia'];
                }

                // Agregar fecha formateada
                $arr['fecha_formateada'] = $item->fechaPendientes ? Carbon::parse($item->fechaPendientes)->format('d/m/Y') : '';

                return $arr;
            });
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Error al cargar pendientes: ' . $e->getMessage()]);
            $this->tablaPendientesDetalle = collect();
        }
    }

    public function cargarTablaPagos()
    {
        try {
            $fechaHastaStr = $this->fechaHasta ? Carbon::parse($this->fechaHasta)->endOfDay()->toDateTimeString() : now()->endOfDay()->toDateTimeString();

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
            $mesNo = $meses[strtolower($this->mesActual)] ?? now()->month;
            $primerDiaMesActual = Carbon::create($this->anioActual, $mesNo, 1)->startOfMonth();

            $pagosActual = collect();
            if ($this->cajaChicaSeleccionada) {
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
                    ->get();
            }

            $pagosActual = $pagosActual->map(function ($p) {
                $p->es_mes_anterior = false;
                return $p;
            });

            $inicioMesAnterior = (clone $primerDiaMesActual)->subMonth()->startOfMonth();

            // 2. Pagos de MES ANTERIOR (fecha >= inicioMesAnterior y fecha < primer día mes actual)
            $pagosAnteriores = Pago::where('fechaEgresoPagos', '>=', $inicioMesAnterior->toDateString())
                ->where('fechaEgresoPagos', '<', $primerDiaMesActual->toDateString())
                ->where('fechaEgresoPagos', '<=', $fechaHastaStr)
                ->with('acreedor')
                ->selectRaw(
                    'tes_cch_pagos.*,
                    (montoPagos - CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN recuperadoPagos ELSE 0 END) as saldo_pagos,
                    CASE WHEN fechaIngresoPagos IS NOT NULL AND fechaIngresoPagos <= ? THEN recuperadoPagos ELSE 0 END as recuperado_en_periodo',
                    [$fechaHastaStr, $fechaHastaStr]
                )
                ->get();

            $idActualesPagos = $pagosActual->pluck('idPagos')->toArray();

            $pagosAnteriores = $pagosAnteriores->filter(function ($p) use ($idActualesPagos) {
                return !in_array($p->idPagos, $idActualesPagos);
            })->map(function ($p) {
                $p->es_mes_anterior = true;
                return $p;
            })->filter(function ($p) {
                return round(($p->saldo_pagos ?? 0), 2) > 0;
            });

            $allPagos = $pagosActual->concat($pagosAnteriores)->sortBy('fechaEgresoPagos')->values();

            if (!empty($this->searchPagos)) {
                $search = mb_strtolower($this->searchPagos, 'UTF-8');
                $allPagos = $allPagos->filter(function ($p) use ($search) {
                    $egreso = mb_strtolower((string)($p->egresoPagos ?? ''), 'UTF-8');
                    $acreedor = mb_strtolower($p->acreedor->acreedor ?? '', 'UTF-8');
                    $concepto = mb_strtolower($p->conceptoPagos ?? '', 'UTF-8');
                    $monto = number_format($p->montoPagos, 2, ',', '.');
                    return str_contains($egreso, $search) || str_contains($acreedor, $search) || str_contains($concepto, $search) || str_contains($monto, $search);
                })->values();
            }

            $this->tablaPagos = $allPagos->map(function ($item) {
                // Convertir a array
                $arr = $item->toArray();

                // Asegurar adreedor
                $arr['acreedor'] = $arr['acreedor'] ?? ['acreedor' => ''];
                if (is_object($arr['acreedor'])) {
                    $arr['acreedor'] = (array) $arr['acreedor'];
                }

                // Agregar fecha formateada
                $arr['fecha_formateada'] = $item->fechaEgresoPagos ? Carbon::parse($item->fechaEgresoPagos)->format('d/m/Y') : '';

                return $arr;
            });
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Error al cargar pagos: ' . $e->getMessage()]);
            $this->tablaPagos = collect();
        }
    }

    public function cargarTablaTotales()
    {
        $this->tablaTotales = [];

        try {
            $montoCajaChica = $this->cajaChicaSeleccionada ? floatval($this->cajaChicaSeleccionada->montoCajaChica) : 0;

            $this->tablaTotales['Monto Caja Chica'] = $montoCajaChica;

            // Calcular totales de pendientes usando tablaPendientesDetalle
            $totalMontoPendientes = $this->tablaPendientesDetalle->sum('montoPendientes');
            $totalRendido = $this->tablaPendientesDetalle->sum('tot_rendido');
            $totalReintegrado = $this->tablaPendientesDetalle->sum('tot_reintegrado');
            $totalRecuperado = $this->tablaPendientesDetalle->sum('tot_recuperado');
            $stExtras = $this->tablaPendientesDetalle->sum('extra_pendiente'); // Usar el extra pendiente calculado

            $totalGastado = $totalRendido + $totalReintegrado;
            // $stPendientes es la suma de saldos pendientes de aquellos que NO se pasaron (normales)
            // Pero simplificando, el saldo total pendiente global se puede calcular diferente.
            // Usemos la suma directa de saldos, pero hay que distinguir tipos.

            // Revertimos a la lógica anterior pero ajustada:
            // Total Pendiente (Dinero en calle): Monto - Gastado (donde gastado <= monto)
            // El problema es que "extra" distorsiona.

            // Cálculo Alternativo más robusto basado en items:
            // Saldo "Normal" Pendiente = Sum(max(0, Monto - Reintegrado - Recuperado)) - pero si gastó más...
            // Mejor mantener la lógica simple de sumas si funcionaba excepto por el extra.

            $stPendientes = $totalMontoPendientes - $totalGastado;
            // Si gastamos extra globalmente, esto resta de más. Hay que sumar TotalExtraGenerado (sum('extra')) para compensar.
            $totalGeneratedExtra = $this->tablaPendientesDetalle->sum('extra');
            $stPendientes = ($totalMontoPendientes + $totalGeneratedExtra) - $totalGastado;
            // ($totalMontoPendientes + $totalExtra) es el Total "Debit" real. $totalGastado es el Credit.
            // Pero 'extra' es derivado de gastado.
            // Si gaste 1200 (1000 monto), tengo 200 extra.
            // 1000 + 200 - 1200 = 0 pendiente. Correcto.

            $stPendientes = $stPendientes > 0 ? $stPendientes : 0;

            $stRendidos = $totalRendido - $totalRecuperado;
            // Restar solo el extra que AUN falta recuperar.
            $stRendidos = max(0, $stRendidos - $stExtras);

            $this->tablaTotales['Total Pendientes'] = $stPendientes;
            $this->tablaTotales['Total Rendidos'] = $stRendidos;
            $this->tablaTotales['Total Extras'] = $stExtras;

            // Nuevo total solicitado: Suma de (Rendido - Recuperado) de pendientes con movimientos sin docs
            $totalRendidoSinDocs = $this->tablaPendientesDetalle->sum('rendido_sin_docs_calc');
            $this->tablaTotales['Total Rendido Sin Docs'] = $totalRendidoSinDocs;

            // Calcular totales de pagos usando tablaPagos
            // Nota: tablaPagos ahora es una colección de arrays, no objetos stdClass
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
                'detalle' => $detalleDependencia,
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
                    'detalle' => $detalleDependencia,
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

        DB::beginTransaction();
        try {
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

            DB::commit();
            $this->closeRecuperarModal();
            $this->cargarDatos();
            $this->dispatchBrowserEvent('swal:success', ['text' => 'Recuperación guardada exitosamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Error al guardar la recuperación: ' . $e->getMessage()]);
        }
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

        DB::beginTransaction();
        try {
            $pendiente = Pendiente::find($this->recuperarRendidoData['relPendiente']);

            if (!$pendiente) {
                $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Pendiente no encontrado para la validación.']);
                DB::rollBack();
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
                DB::rollBack();
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

            DB::commit();
            $this->cargarDatos();
            $this->dispatchBrowserEvent('swal:success', ['text' => 'Dinero rendido recuperado exitosamente.']);
            $this->closeRecuperarRendidoModal();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Error al guardar la recuperación del dinero rendido: ' . $e->getMessage()]);
        }
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

        DB::beginTransaction();
        try {
            $pago = Pago::find($this->recuperarPagoData['relPago']);

            if (!$pago) {
                $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Pago no encontrado para la validación.']);
                DB::rollBack();
                return;
            }

            // Verificar que el monto a recuperar no exceda el saldo disponible
            $saldoDisponible = $pago->montoPagos - $pago->recuperadoPagos;

            if ($this->recuperarPagoData['monto_recuperado'] > $saldoDisponible) {
                $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'El monto a recuperar (' . number_format($this->recuperarPagoData['monto_recuperado'], 2, ',', '.') . ') no puede ser mayor que el saldo disponible del pago (' . number_format($saldoDisponible, 2, ',', '.') . ').']);
                DB::rollBack();
                return;
            }

            // Actualizar el pago con la información de recuperación
            $pago->update([
                'recuperadoPagos' => $pago->recuperadoPagos + $this->recuperarPagoData['monto_recuperado'],
                'fechaIngresoPagos' => $this->recuperarPagoData['fecha'],
                'ingresoPagos' => $this->recuperarPagoData['numero_ingreso'],
                'ingresoPagosBSE' => $this->recuperarPagoData['numero_ingreso_bse'],
            ]);

            DB::commit();
            $this->cargarDatos();
            $this->dispatchBrowserEvent('swal:success', ['text' => 'Pago directo recuperado exitosamente.']);
            $this->closeRecuperarPagoModal();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Error al guardar la recuperación del pago directo: ' . $e->getMessage()]);
        }
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

        try {
            $fondo = CajaChica::findOrFail($this->editandoFondo['id']);
            $montoAnterior = $fondo->montoCajaChica;
            $montoNuevo = floatval($this->editandoFondo['monto']);

            if (abs($montoAnterior - $montoNuevo) < 0.01) {
                $this->cerrarModalEditFondo();
                $this->dispatchBrowserEvent('swal:success', ['text' => 'No se realizaron cambios en el monto del fondo.']);
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

            $this->dispatchBrowserEvent('swal:success', ['text' => $mensaje]);

            $this->dispatchBrowserEvent('fondo-actualizado', [
                'message' => 'Fondo actualizado exitosamente',
                'montoAnterior' => $montoAnterior,
                'montoNuevo' => $montoNuevo
            ]);
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Error al actualizar el fondo: ' . $e->getMessage()]);
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
    public function cargarDependenciasSinPendientes()
    {
        if (!$this->cajaChicaSeleccionada) {
            $this->dependenciasSinPendientes = [];
            $this->dependenciasEspecialesSinPendientes = [];
            return;
        }

        // Obtener conteo de pendientes por dependencia para el mes actual directamente de la BD
        // Esto evita errores cuando la tabla principal está filtrada por búsqueda
        $conteoPorDependencia = Pendiente::where('relCajaChica', $this->cajaChicaSeleccionada->idCajaChica)
            ->where('fechaPendientes', '<=', $this->fechaHasta ? Carbon::parse($this->fechaHasta)->endOfDay()->toDateTimeString() : now()->endOfDay()->toDateTimeString())
            ->selectRaw('relDependencia, count(*) as total')
            ->groupBy('relDependencia')
            ->pluck('total', 'relDependencia');

        // Obtener todas las dependencias excepto Tesorería
        $todasDependencias = Dependencia::where('dependencia', '<>', 'Dirección de Tesorería')
            ->orderBy('dependencia', 'ASC')
            ->get();

        $faltantes = collect();

        foreach ($todasDependencias as $dep) {
            $nombreNorm = $this->normalizarParaComparar($dep->dependencia);
            $esEspecial = str_contains($nombreNorm, '(especial)');

            // Determinar la meta de registros para esta dependencia
            $meta = 1;

            if (!$esEspecial) {
                // Verificar si es una de las dependencias quincenales
                // "Direccion de Administracion" y "Direccion de Logistica y Apoyo"
                if (
                    str_contains($nombreNorm, 'direccion de administracion') ||
                    str_contains($nombreNorm, 'direccion de logistica y apoyo')
                ) {
                    $meta = 2;
                }
            }

            $cantidadActual = $conteoPorDependencia->get($dep->idDependencias, 0);

            if ($cantidadActual < $meta) {
                $faltantes->push($dep);
            }
        }

        // Separar normales de especiales
        $this->dependenciasSinPendientes = $faltantes->filter(function ($dep) {
            return !str_contains(strtolower($dep->dependencia), '(especial)');
        });

        $this->dependenciasEspecialesSinPendientes = $faltantes->filter(function ($dep) {
            return str_contains(strtolower($dep->dependencia), '(especial)');
        });
    }

    private function normalizarParaComparar($string)
    {
        $string = mb_strtolower($string, 'UTF-8');
        $replacements = [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'à' => 'a',
            'è' => 'e',
            'ì' => 'i',
            'ò' => 'o',
            'ù' => 'u',
            'ä' => 'a',
            'ë' => 'e',
            'ï' => 'i',
            'ö' => 'o',
            'ü' => 'u',
            'â' => 'a',
            'ê' => 'e',
            'î' => 'i',
            'ô' => 'o',
            'û' => 'u',
            'ñ' => 'n',
            'ç' => 'c'
        ];
        return strtr($string, $replacements);
    }



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

        $mesActualLower = strtolower($this->mesActual);
        $mesNumero = $meses[$mesActualLower] ?? null;

        if (is_null($mesNumero)) {
            return ['mes' => '', 'anio' => ''];
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
        if (!auth()->user()->hasRole(['administrador', 'gerente_tesoreria', 'supervisor_tesoreria'])) {
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'No tiene permisos para eliminar pendientes.']);
            return;
        }

        $pendiente = Pendiente::find($id);
        if (!$pendiente) {
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Pendiente no encontrado.']);
            return;
        }

        if ($pendiente->movimientos()->count() > 0) {
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'No se puede eliminar el pendiente porque tiene movimientos asociados.']);
            return;
        }

        DB::beginTransaction();
        try {
            $pendiente->delete();

            DB::commit();
            $this->cargarDatos();
            $this->dispatchBrowserEvent('swal:success', ['text' => 'Pendiente eliminado correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
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
        if (!auth()->user()->hasRole(['administrador', 'gerente_tesoreria', 'supervisor_tesoreria'])) {
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'No tiene permisos para eliminar pagos.']);
            return;
        }

        $pago = Pago::find($id);
        if (!$pago) {
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Pago no encontrado.']);
            return;
        }

        if (!empty($pago->ingresoPagos) || ($pago->recuperadoPagos > 0)) {
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'No se puede eliminar el pago porque tiene ingresos cargados o recuperos parciales.']);
            return;
        }

        DB::beginTransaction();
        try {
            $pago->delete();

            DB::commit();
            $this->cargarDatos();
            $this->dispatchBrowserEvent('swal:success', ['text' => 'Pago eliminado correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('swal:toast-error', ['text' => 'Error al eliminar el pago: ' . $e->getMessage()]);
        }
    }
}
