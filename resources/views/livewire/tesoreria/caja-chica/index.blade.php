<div>
    @if (session()->has('message'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('message') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    @if (session()->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    {{-- Fila mes anterior ahora gestionada globalmente o mantenida aquí si es única --}}
    <style>
        .fila-mes-anterior {
            background-color: rgba(253, 126, 20, 0.08) !important; /* Ahora usa el color de acento (naranja) suave */
            border-left: 3px solid var(--color-accent) !important;
        }

        /* Mejoras de densidad y Sticky Headers */
        .table-container {
            /* Permitir que la tabla se expanda infinitamente */
        }
        .table-container thead th {
            position: sticky;
            top: 0;
            background-color: #f4f6f9; /* Color adminLTE/Bootstrap */
            z-index: 1;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        /* Ajuste para Dark Mode */
        @media (prefers-color-scheme: dark) {
            .table-container thead th {
                background-color: #343a40;
                color: #ffffff;
            }
        }
    </style>

    <!-- Cabecera de Caja Chica -->
    <div class="card mb-3 shadow-sm">
        <div class="card-header bg-info text-white card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <strong><i class="fas fa-coins mr-2"></i>Caja Chica</strong>
            </h4>
            <div class="d-flex align-items-center">
                <!-- Selectores de Mes y Año -->
                <div class="form-inline mr-3">
                    <select id="mesSelector" class="form-control form-control-sm mr-2" wire:model="mesActual">
                        <option value="enero">Enero</option>
                        <option value="febrero">Febrero</option>
                        <option value="marzo">Marzo</option>
                        <option value="abril">Abril</option>
                        <option value="mayo">Mayo</option>
                        <option value="junio">Junio</option>
                        <option value="julio">Julio</option>
                        <option value="agosto">Agosto</option>
                        <option value="septiembre">Septiembre</option>
                        <option value="octubre">Octubre</option>
                        <option value="noviembre">Noviembre</option>
                        <option value="diciembre">Diciembre</option>
                    </select>
                    <input type="number" id="anioSelector" class="form-control form-control-sm" style="width: 90px;" wire:model="anioActual">
                </div>
                <!-- Botones -->
                <div class="btn-group" role="group">
                    <button class="btn btn-warning btn-sm" wire:click="mostrarModalNuevoFondo">
                        <i class="fas fa-comment-dollar"></i>
                        Fondo Permanente
                    </button>
                    <button class="btn btn-danger btn-sm" wire:click="openRecuperarModal">
                        <i class="fas fa-money-check"></i>
                        Recuperar todo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla Caja Chica (Fondo Permanente) -->
    <h4 class="mt-1 mb-0">Fondo Permanente</h4>
    <div class="table-responsive" wire:loading.class="loading-overlay">
        <table class="table table-sm table-striped table-bordered table-hover table-compact" id="tablaCajaChica">
            <thead>
                <tr>
                    <th class="text-center">Mes</th>
                    <th class="text-center">Año</th>
                    <th class="text-center">Monto</th>
                    <th class="text-center d-print-none">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tablaCajaChica as $item)
                <tr wire:key="cajachica-{{ $item['idCajaChica'] }}">
                    <td class="text-center font-weight-bold">{{ mb_strtoupper($item['mes'], 'UTF-8') }}</td>
                    <td class="text-center font-weight-bold">{{ $item['anio'] }}</td>
                    <td class="text-center font-weight-bold classCajaChicaActual">
                        {{ number_format($item['montoCajaChica'], 2, ',', '.') }}
                    </td>
                    <td class="text-center d-print-none">
                        <button class="btn btn-sm btn-success"
                            wire:click="$emitTo('tesoreria.caja-chica.modales.modal-editar-fondo', 'abrirModalEditarFondo', {{ $item['idCajaChica'] }}, {{ $item['montoCajaChica'] }})">
                            <i class="fas fa-pencil-alt"></i> Editar
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center">No hay datos de Fondo Permanente para el mes y año
                        seleccionados.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <livewire:tesoreria.caja-chica.modales.modal-editar-fondo />

    <livewire:tesoreria.caja-chica.modales.modal-recuperar-saldos />

    <div class="d-flex justify-content-between align-items-center mt-1 mb-0">
        <h4 class="mb-0">Totales</h4>

        <div class="flex-grow-1 text-center">
            @if (isset($tablaTotales['Total Rendido Sin Docs']) && $tablaTotales['Total Rendido Sin Docs'] > 0)
            <span class="badge badge-danger text-white shadow-sm animated pulse infinite" style="font-size: 0.9rem;">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                Rendido sin Egr.: ${{ number_format($tablaTotales['Total Rendido Sin Docs'], 2, ',', '.') }} (restar al cierre)
            </span>
            @endif
        </div>

        <div class="form-inline d-print-none">
            <label for="fechaHastaInput" class="mr-2">Fecha Hasta:</label>
            <input type="date" id="fechaHastaInput" class="form-control mr-2" wire:model="fechaHasta">
            <button class="btn btn-secondary btn-sm mr-2" wire:click="establecerFechaHoy">
                <i class="fas fa-calendar-day"></i> Hoy
            </button>
            <button class="btn btn-success btn-sm" wire:click="exportarExcel" onclick="setTimeout(() => { document.getElementById('loader').style.display = 'none'; }, 2000)">
                <i class="fas fa-file-excel"></i> Excel
            </button>
        </div>
    </div>

    <div class="table-responsive" wire:loading.class="loading-overlay">
        <table class="table table-sm table-bordered mb-1 table-compact" id="tablaTotales">
            <thead>
                <tr>
                    <th class="text-center align-middle">Pendientes</th>
                    <th class="text-center align-middle">Rendidos</th>
                    <th class="text-center align-middle">Extras</th>
                    <th class="text-center align-middle">Pagos s/eg.</th>
                    <th class="text-center align-middle">Pent.+Pag.</th>
                    <th class="text-center align-middle">Pend.+Pag.s/eg.</th>
                    <th class="text-center align-middle">Pagos</th>
                    <th class="text-center align-middle">Recuperar</th>
                    <th class="text-center align-middle">Saldo en $</th>
                </tr>
            </thead>
            <tbody>
                @if (!empty($tablaTotales))
                <tr>
                    <td class="text-center align-middle font-weight-bold">
                        {{ isset($tablaTotales['Total Pendientes']) ? number_format($tablaTotales['Total Pendientes'], 2, ',', '.') : '0,00' }}
                    </td>
                    <td class="text-center align-middle font-weight-bold">
                        {{ isset($tablaTotales['Total Rendidos']) ? number_format($tablaTotales['Total Rendidos'], 2, ',', '.') : '0,00' }}
                    </td>
                    <td class="text-center align-middle font-weight-bold">
                        {{ isset($tablaTotales['Total Extras']) ? number_format($tablaTotales['Total Extras'], 2, ',', '.') : '0,00' }}
                    </td>
                    <td class="text-center align-middle font-weight-bold">
                        {{ isset($tablaTotales['Pagos Sin Egreso']) ? number_format($tablaTotales['Pagos Sin Egreso'], 2, ',', '.') : '0,00' }}
                    </td>
                    <td class="text-center align-middle font-weight-bold">
                        <h5 class="m-0 font-weight-bold">
                            {{ isset($tablaTotales['Pendientes y Pagos Sin Rendir']) ? number_format($tablaTotales['Pendientes y Pagos Sin Rendir'], 2, ',', '.') : '0,00' }}
                        </h5>
                    </td>
                    <td class="text-center align-middle font-weight-bold">
                        {{ number_format((isset($tablaTotales['Total Pendientes']) ? floatval($tablaTotales['Total Pendientes']) : 0) + (isset($tablaTotales['Pagos Sin Egreso']) ? floatval($tablaTotales['Pagos Sin Egreso']) : 0), 2, ',', '.') }}
                    </td>
                    <td class="text-center align-middle font-weight-bold">
                        {{ isset($tablaTotales['Saldo Pagos Directos']) ? number_format($tablaTotales['Saldo Pagos Directos'], 2, ',', '.') : '0,00' }}
                    </td>
                    <td class="text-center align-middle font-weight-bold">
                        {{ number_format(
                            (isset($tablaTotales['Total Rendidos']) ? floatval($tablaTotales['Total Rendidos']) : 0) +
                                (isset($tablaTotales['Total Extras']) ? floatval($tablaTotales['Total Extras']) : 0) +
                                (isset($tablaTotales['Saldo Pagos Directos']) ? floatval($tablaTotales['Saldo Pagos Directos']) : 0),
                            2,
                            ',',
                            '.',
                        ) }}
                    </td>
                    <td class="text-center align-middle font-weight-bold">
                        <h5 class="m-0 font-weight-bold">
                            {{ isset($tablaTotales['Saldo Total']) ? number_format($tablaTotales['Saldo Total'], 2, ',', '.') : '0,00' }}
                        </h5>
                    </td>
                </tr>
                @else
                <tr>
                    <td colspan="9" class="text-center align-middle">No hay datos de totales.</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- Tabla Pendientes Detalle -->
    <div class="d-flex align-items-center mt-4 d-print-none">
        <h4 class="mb-0 mr-3 flex-shrink-0">Pendientes</h4>
        <div class="input-group flex-grow-1 mr-3">
            <input type="text"
                wire:model.debounce.300ms="searchPendientes"
                class="form-control"
                placeholder="Buscar por número, dependencia o monto...">
            <div class="input-group-append">
                <button class="btn btn-outline-danger"
                    wire:click="limpiarFiltroPendientes"
                    type="button"
                    title="Limpiar filtro">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="btn-group flex-shrink-0">
            <button class="btn btn-primary" wire:click="openModalDependencias">
                <i class="fas fa-building"></i>
                Dependencias
            </button>
            <button class="btn btn-info" wire:click="prepararModalNuevoPendiente">
                <i class="fas fa-money-bill"></i>
                Nuevo Pendiente
            </button>
        </div>
    </div>

    <!-- Título solo para impresión -->
    <h4 class="mt-4 d-none d-print-block">Pendientes</h4>

    <div class="table-responsive table-container" wire:loading.class="loading-overlay">
        <table class="table table-sm table-striped table-bordered table-hover table-compact" id="tablaPendientesDetalle">
            <thead>
                <tr>
                    <th class="text-center align-middle">N&deg;</th>
                    <th class="text-center align-middle">FECHA</th>
                    <th class="text-center align-middle">DEPENDENCIA</th>
                    <th class="text-center align-middle">MONTO</th>
                    <th class="text-center align-middle">RENDIDO</th>
                    <th class="text-center align-middle">EXTRA</th>
                    <th class="text-center align-middle">REINTEG.</th>
                    <th class="text-center align-middle">RECUPER.</th>
                    <th class="text-center align-middle">SALDO</th>
                    <th class="text-center align-middle"><i class="fas fa-info-circle" title="Estado del pendiente"></i></th>
                    <th class="text-center align-middle d-print-none">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tablaPendientesDetalle as $item)
                <tr wire:key="pendiente-{{ $item['idPendientes'] }}"
                    class="{{ ($item['es_mes_anterior'] ?? false) ? 'table-warning fila-mes-anterior' : '' }}">
                    <td class="text-right align-middle font-weight-bold">{{ $item['pendiente'] }}</td>
                    <td class="text-center align-middle">
                        {{ $item['fecha_formateada'] }}
                    </td>
                    <td class="text-center align-middle">{{ $item['dependencia']['dependencia'] }}</td>
                    <td class="text-right align-middle">{{ number_format($item['montoPendientes'], 2, ',', '.') }}
                    </td>
                    <td class="text-right align-middle {{ ($item['rendido_sin_docs_calc'] ?? 0) > 0 ? 'table-danger font-weight-bold' : '' }}">
                        {{ number_format($item['tot_rendido'] ?? 0, 2, ',', '.') }}
                    </td>
                    <td class="text-right align-middle">{{ number_format($item['extra'] ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right align-middle">
                        {{ number_format($item['tot_reintegrado'] ?? 0, 2, ',', '.') }}
                    </td>
                    <td class="text-right align-middle">
                        {{ number_format($item['tot_recuperado'] ?? 0, 2, ',', '.') }}
                    </td>
                    <td
                        class="text-right align-middle {{ ($item['saldo'] ?? 0) > 0 ? 'text-danger font-weight-bold' : '' }}">
                        {{ number_format($item['saldo'] ?? 0, 2, ',', '.') }}
                    </td>
                    <td class="text-center align-middle">
                        @php
                        $hasMovements =
                        ($item['tot_rendido'] ?? 0) > 0 ||
                        ($item['extra'] ?? 0) > 0 ||
                        ($item['tot_reintegrado'] ?? 0) > 0 ||
                        ($item['tot_recuperado'] ?? 0) > 0;
                        $saldo = $item['saldo'] ?? 0;
                        @endphp
                        @if (!$hasMovements)
                        <i class="fas fa-check text-success" title="Sin movimientos"></i>
                        @elseif($hasMovements && $saldo > 0)
                        <i class="fas fa-dollar-sign text-warning" title="Con movimientos, saldo pendiente"></i>
                        @elseif($hasMovements && $saldo == 0)
                        <i class="fas fa-check-circle text-info" title="Finalizado"></i>
                        @endif
                    </td>
                    <td class="text-center align-middle d-print-none">
                        <input type='hidden' name='selIdPendientes' value='{{ $item['idPendientes'] }}'>
                        <div class='btn-group' role='group'>
                            <button type="button" wire:click="irAEditar({{ $item['idPendientes'] }})"
                                class="btn btn-sm btn-dark mr-1" title="Editar Pendiente">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                            @if (($item['tot_recuperado'] ?? 0) < ($item['tot_rendido'] ?? 0) && ($item['tot_rendido'] ?? 0)> 0)
                                <button type="button" class="btn btn-sm btn-info mr-1"
                                    title="Recuperar Dinero Rendido"
                                    wire:click="$emitTo('tesoreria.caja-chica.modales.modal-recuperar-rendido', 'abrirModalRecuperarRendido', {{ $item['idPendientes'] }}, '{{ $fechaHasta }}')">
                                    <i class="fas fa-hand-holding-usd"></i>
                                </button>
                                @endif
                                <a href="{{ route('tesoreria.caja-chica.imprimir.pendiente', $item['idPendientes']) }}"
                                    target="_blank" class="btn btn-sm btn-dark mr-1" title="Imprimir Pendiente">
                                    <i class="fas fa-print"></i>
                                </a>
                                </a>
                                @if(auth()->user()->hasRole(['administrador', 'gerente_tesoreria', 'supervisor_tesoreria']))
                                @if(($item['cant_movimientos'] ?? 0) == 0)
                                <button type="button" class="btn btn-sm btn-danger ml-1"
                                    title="Eliminar Pendiente"
                                    wire:click="confirmarEliminarPendiente({{ $item['idPendientes'] }})">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endif
                                @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center">No hay datos de Pendientes para el mes y año
                        seleccionados
                        hasta la fecha
                        {{ \Carbon\Carbon::createFromFormat('Y-m-d', $fechaHasta)->format('d/m/Y') }}.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Acordeón de Dependencias sin Pendientes -->
    @if(count($dependenciasSinPendientes) > 0 || count($dependenciasEspecialesSinPendientes) > 0)
    <div class="accordion my-4 shadow-sm d-print-none" id="dependenciasAccordion">
        <div class="card border-0">
            <div class="card-header p-0 bg-info" id="headingDependencias">
                <h2 class="mb-0">
                    <button class="btn btn-info btn-block text-left d-flex align-items-center justify-content-between text-decoration-none py-2 px-3 collapsed"
                        type="button" data-toggle="collapse" data-target="#collapseDependencias"
                        aria-expanded="false" aria-controls="collapseDependencias"
                        title="Ver listado de dependencias sin pendientes"
                        style="color: #FFFFFF !important; text-shadow: 1px 1px 2px rgba(0,0,0,0.7);">
                        <span class="font-weight-bold" style="font-size: 0.9rem;">
                            <i class="fas fa-building mr-2"></i>DEPENDENCIAS SIN PENDIENTES REGISTRADOS ({{ $mesActual }} {{ $anioActual }})
                            <span class="badge badge-primary badge-pill ml-2">Normal: {{ count($dependenciasSinPendientes) }}</span>
                            <span class="badge badge-danger badge-pill ml-1">Especial: {{ count($dependenciasEspecialesSinPendientes) }}</span>
                        </span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </h2>
            </div>

            <div id="collapseDependencias" class="collapse" aria-labelledby="headingDependencias" data-parent="#dependenciasAccordion">
                <div class="card-body p-3 bg-white text-dark border-left border-right border-bottom border-info">

                    {{-- Grupo 1: Normales --}}
                    @if(count($dependenciasSinPendientes) > 0)
                    <div class="mb-3">
                        <h6 class="text-primary font-weight-bold mb-2" style="font-size: 0.85rem;">
                            <i class="fas fa-clipboard-list mr-1"></i> Pendientes faltantes de las Dependencias
                        </h6>
                        <div class="row">
                            @foreach($dependenciasSinPendientes->chunk(ceil(count($dependenciasSinPendientes) / 3)) as $chunk)
                            <div class="col-md-4">
                                <ul class="list-unstyled mb-0">
                                    @foreach($chunk as $dep)
                                    @php $dep = (object) $dep; @endphp
                                    <li class="py-1" style="font-size: 0.8rem;">
                                        <i class="fas fa-circle text-muted mr-2" style="font-size: 0.4rem;"></i>
                                        {{ $dep->dependencia }}
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if(count($dependenciasSinPendientes) > 0 && count($dependenciasEspecialesSinPendientes) > 0)
                    <hr class="my-2">
                    @endif

                    {{-- Grupo 2: Especiales --}}
                    @if(count($dependenciasEspecialesSinPendientes) > 0)
                    <div>
                        <h6 class="text-danger font-weight-bold mb-2" style="font-size: 0.85rem;">
                            <i class="fas fa-star-of-life mr-1"></i> Pendientes Especiales faltantes de las Dependencias
                        </h6>
                        <div class="row">
                            @foreach($dependenciasEspecialesSinPendientes->chunk(ceil(count($dependenciasEspecialesSinPendientes) / 3)) as $chunk)
                            <div class="col-md-4">
                                <ul class="list-unstyled mb-0">
                                    @foreach($chunk as $dep)
                                    @php $dep = (object) $dep; @endphp
                                    <li class="py-1" style="font-size: 0.8rem;">
                                        <i class="fas fa-circle text-muted mr-2" style="font-size: 0.4rem;"></i>
                                        {{ str_replace(' (especial)', '', $dep->dependencia) }}
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Tabla Pagos -->
    <div class="d-flex align-items-center mt-4 d-print-none">
        <h4 class="mb-0 mr-3 flex-shrink-0">Pagos Directos</h4>
        <div class="input-group flex-grow-1 mr-3">
            <input type="text"
                wire:model.debounce.300ms="searchPagos"
                class="form-control"
                placeholder="Buscar por egreso, acreedor, concepto o monto...">
            <div class="input-group-append">
                <button class="btn btn-outline-danger"
                    wire:click="limpiarFiltroPagos"
                    type="button"
                    title="Limpiar filtro">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="btn-group flex-shrink-0">
            <button class="btn btn-primary" wire:click="openModalAcreedores">
                <i class="fas fa-users"></i>
                Acreedores
            </button>
            <button class="btn btn-warning" wire:click="prepararModalNuevoPago">
                <i class="far fa-handshake"></i>
                Nuevo Pago
            </button>
        </div>
    </div>

    <!-- Título solo para impresión -->
    <h4 class="mt-4 d-none d-print-block">Pagos Directos</h4>

    <div class="table-responsive table-container" wire:loading.class="loading-overlay">
        <table class="table table-sm table-striped table-bordered table-hover table-compact" id="tablaPagos">
            <thead>
                <tr>
                    <th class="text-center align-middle">FCH.EG.</th>
                    <th class="text-center align-middle">EGRESO</th>
                    <th class="text-center align-middle">ACREEDOR</th>
                    <th class="text-center align-middle">CONCEPTO</th>
                    <th class="text-center align-middle">MONTO</th>
                    <th class="text-center align-middle">RENDIDO</th>
                    <th class="text-center align-middle">EXTRA</th>
                    <th class="text-center align-middle">REINTEG.</th>
                    <th class="text-center align-middle">RECUPER.</th>
                    <th class="text-center align-middle">SALDO</th>
                    <th class="text-center align-middle d-print-none">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tablaPagos as $item)
                <tr wire:key="pago-{{ $item['idPagos'] }}"
                    class="{{ ($item['es_mes_anterior'] ?? false) ? 'table-warning fila-mes-anterior' : '' }}">
                    <td class="text-center align-middle">
                        {{ $item['fecha_formateada'] }}
                    </td>
                    <td class="text-center align-middle font-weight-bold">{{ $item['egresoPagos'] ?? 'Sin número' }}
                    </td>
                    <td class="text-center align-middle">{{ $item['acreedor']['acreedor'] }}</td>
                    <td>{{ $item['conceptoPagos'] }}</td>
                    <td class="text-right align-middle">{{ number_format($item['montoPagos'], 2, ',', '.') }}</td>
                    <td class="text-right align-middle">
                        {{ !is_null($item['rendido_en_periodo'] ?? null) ? number_format($item['rendido_en_periodo'], 2, ',', '.') : '-' }}
                    </td>
                    <td class="text-right align-middle {{ ($item['extra_pagos'] ?? 0) > 0 ? 'text-warning font-weight-bold' : '' }}">
                        {{ ($item['extra_pagos'] ?? 0) > 0 ? number_format($item['extra_pagos'], 2, ',', '.') : '-' }}
                    </td>
                    <td class="text-right align-middle">
                        {{ !is_null($item['reintegrado_en_periodo'] ?? null) ? number_format($item['reintegrado_en_periodo'], 2, ',', '.') : '-' }}
                    </td>
                    <td class="text-right align-middle">
                        {{ number_format($item['recuperado_en_periodo'] ?? 0, 2, ',', '.') }}
                    </td>
                    <td
                        class="text-right align-middle
                        {{ ($item['saldo_pagos'] ?? 0) > 0 ? 'text-danger font-weight-bold' : '' }}">
                        {{ number_format($item['saldo_pagos'] ?? 0, 2, ',', '.') }}
                        @if (($item['ingresoPagosBSE'] ?? null) == null && ($item['acreedor']['acreedor'] ?? '') == 'Banco de Seguros del Estado')
                        <i class="fas fa-exclamation-triangle text-danger ml-1"
                            title="Ingreso BSE no encontrado"></i>
                        @endif
                    </td>
                    <td class="text-center align-middle d-print-none">
                        <input type='hidden' name='selIdPagos' value='{{ $item['idPagos'] }}'>
                        <div class='btn-group' role='group'>
                            <button type="button" class="btn btn-sm btn-dark"
                                wire:click="$emit('mostrarModalEditarPago', {{ $item['idPagos'] }})" title="Editar">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                            @if (!($item['tiene_datos_rendicion'] ?? false))
                            <button type="button" class="btn btn-sm btn-success"
                                wire:click="$emitTo('tesoreria.caja-chica.modales.modal-rendir-pago', 'abrirModalRendirPago', {{ $item['idPagos'] }})" title="Rendir Pago">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </button>
                            @endif
                            @if ($item['puede_recuperar'] ?? false)
                                <button type="button" class="btn btn-sm btn-info"
                                    title="Recuperar Pago Directo"
                                    wire:click="$emitTo('tesoreria.caja-chica.modales.modal-recuperar-pago', 'abrirModalRecuperarPago', {{ $item['idPagos'] }})">
                                    <i class="fas fa-hand-holding-usd"></i>
                                </button>
                            @endif
                                <a href="{{ route('tesoreria.caja-chica.imprimir.pago', $item['idPagos']) }}"
                                    target="_blank" class="btn btn-sm btn-dark" title="Imprimir Pago Directo">
                                    <i class="fas fa-print"></i>
                                </a>
                                @if(auth()->user()->hasRole(['administrador', 'gerente_tesoreria', 'supervisor_tesoreria']))
                                @if(
                                    !($item['tiene_datos_rendicion'] ?? false) &&
                                    !($item['tiene_datos_recuperacion'] ?? false)
                                )
                                <button type="button" class="btn btn-sm btn-danger"
                                    title="Eliminar Pago"
                                    wire:click="confirmarEliminarPago({{ $item['idPagos'] }})">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endif
                                @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center">No hay datos de Pagos Directos para el mes y año
                        seleccionados hasta la fecha
                        {{ \Carbon\Carbon::createFromFormat('Y-m-d', $fechaHasta)->format('d/m/Y') }}.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Espacio inferior extra para no tapar contenido con botones flotantes -->
    <div style="height: 100px;"></div>

    <livewire:tesoreria.caja-chica.modales.modal-recuperar-rendido />

    <livewire:tesoreria.caja-chica.modales.modal-recuperar-pago />
    <livewire:tesoreria.caja-chica.modales.modal-rendir-pago />



    <!-- Incluir los componentes de modales -->
    <livewire:tesoreria.caja-chica.modales.modal-nuevo-fondo />
    <livewire:tesoreria.caja-chica.modales.modal-nuevo-pendiente />
    <livewire:tesoreria.caja-chica.modales.modal-nuevo-pago />
    <livewire:tesoreria.caja-chica.modales.modal-editar-pago />

    <!-- Modal Dependencias -->
    <div class="modal fade" id="modalDependencias" tabindex="-1" role="dialog"
        aria-labelledby="modalDependenciasLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="modalDependenciasLabel">
                        <i class="fas fa-building"></i> Gestión de Dependencias
                    </h6>
                    <button type="button" class="close" wire:click="closeModalDependencias">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @livewire('tesoreria.caja-chica.dependencias')
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Acreedores -->
    <div class="modal fade" id="modalAcreedores" tabindex="-1" role="dialog"
        aria-labelledby="modalAcreedoresLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="modalAcreedoresLabel">
                        <i class="fas fa-users"></i> Gestión de Acreedores
                    </h6>
                    <button type="button" class="close" wire:click="closeModalAcreedores">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @livewire('tesoreria.caja-chica.acreedores')
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:init', function() {
        // === Gestión de Modales ===

        // Prevenir que Livewire re-renderice innecesariamente
        Livewire.on('livewire:load', () => {
            console.log('Livewire cargado, manteniendo estado de modales');
        });

        // === Gestión de Estados de Carga ===
        const modales = {
            recuperar: {
                show: () => $('#modalRecuperar').modal('show'),
                hide: () => $('#modalRecuperar').modal('hide')
            },
            nuevoFondo: {
                show: () => {
                    if ($('#modalNuevoFondo').length) {
                        $('#modalNuevoFondo').modal('show');
                    } else {
                        console.error('Error: Elemento #modalNuevoFondo no encontrado en el DOM.');
                    }
                },
                hide: () => {
                    if ($('#modalNuevoFondo').length) {
                        $('#modalNuevoFondo').modal('hide');
                    }
                }
            }
        };

        // Eventos de modales
        Livewire.on('show-recuperar-modal', modales.recuperar.show);
        Livewire.on('hide-recuperar-modal', modales.recuperar.hide);
        Livewire.on('show-recuperar-rendido-modal', () => $('#modalRecuperarRendido').modal('show'));
        Livewire.on('hide-recuperar-rendido-modal', () => $('#modalRecuperarRendido').modal('hide'));
        Livewire.on('show-recuperar-pago-modal', () => $('#modalRecuperarPago').modal('show'));
        Livewire.on('hide-recuperar-pago-modal', () => $('#modalRecuperarPago').modal('hide'));
        Livewire.on('mostrar-modal-nuevo-fondo', modales.nuevoFondo.show);
        Livewire.on('cerrar-modal-nuevo-fondo', modales.nuevoFondo.hide);

        // Inicializar datepicker si es necesario
        Livewire.on('contentChanged', function() {
            if ($.fn.datepicker) {
                $('.datepicker').not('.hasDatepicker').datepicker({
                    dateFormat: 'dd/mm/yy',
                    changeMonth: true,
                    changeYear: true,
                });
            }
        });



        // --- Listener para mostrar el modal de nuevo fondo ---
        Livewire.on('mostrar-modal-nuevo-fondo', function() {
            if ($('#modalNuevoFondo').length) {
                $('#modalNuevoFondo').modal('show');
            } else {
                console.error('Error: Elemento #modalNuevoFondo no encontrado en el DOM.');
            }
        });

        Livewire.on('cerrar-modal-nuevo-fondo', function() {
            if ($('#modalNuevoFondo').length) {
                $('#modalNuevoFondo').modal('hide');
            }
        });

        // Listener para eventos de actualización
        Livewire.on('fondo-actualizado', function(data) {
            console.log('Fondo actualizado:', data);
            // Aquí puedes agregar notificaciones adicionales si necesitas
        });

        // Listeners para el modal de dependencias
        Livewire.on('dependenciaCreada', function() {
            console.log('Dependencia creada exitosamente');
        });

        Livewire.on('dependenciaActualizada', function() {
            console.log('Dependencia actualizada exitosamente');
        });

        Livewire.on('dependenciaEliminada', function() {
            console.log('Dependencia eliminada exitosamente');
        });



        // Listener para cuando se recargan los datos
        Livewire.on('datosRecargados', function() {
            console.log('Datos recargados correctamente');
            // Forzar la actualización de la interfaz si es necesario
            Livewire.find('{{ $this->id }}').call('$refresh');
        });

        // Listener para cuando se completa la recarga forzada
        Livewire.on('recargaCompletada', function() {
            console.log('Recarga completa finalizada');
            // Aquí se pueden agregar acciones adicionales si es necesario
        });

        // Listeners para gestión de modales de acreedores y dependencias
        Livewire.on('cerrarModalAcreedores', function() {
            $('#modalAcreedores').modal('hide');
        });

        Livewire.on('cerrarModalDependencias', function() {
            $('#modalDependencias').modal('hide');
        });

        // === Gestión de Estados de Carga ===
        const tablasAfectadas = ['tablaTotales', 'tablaPendientesDetalle', 'tablaPagos'];

        // Función para manejar el estado de carga de las tablas
        const manejarEstadoTablas = (estado) => {
            tablasAfectadas.forEach(tabla => {
                const elemento = document.querySelector(`[wire\\:loading\\.${tabla}]`);
                if (elemento) {
                    if (estado === 'loading') {
                        elemento.classList.add('loading');
                    } else {
                        elemento.classList.remove('loading');
                    }
                }
            });
        };

        // Escuchar eventos de carga
        Livewire.on('loading', () => manejarEstadoTablas('loading'));
        Livewire.on('loaded', () => manejarEstadoTablas('loaded'));

        // === Gestión de Errores ===
        Livewire.on('error', (error) => {
            console.error('Error en Livewire:', error);
            // Aquí puedes agregar manejo de errores global
        });

        // === Inicialización Adicional ===
        // Asegurar que los modales se inicialicen correctamente
        $(document).ready(function() {
            // Verificar que los modales estén disponibles
            console.log('Modales disponibles:', {
                nuevoFondo: $('#modalNuevoFondo').length > 0
            });
        });
    });
</script>
@endpush