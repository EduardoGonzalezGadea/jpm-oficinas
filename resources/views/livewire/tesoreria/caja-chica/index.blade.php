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

    <!-- Selector de Fecha/Mes/Año -->
    <div class="form-row mb-3">
        <div class="col-md-5">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">Mes y Año</span>
                </div>
                <select id="mesSelector" class="form-control" wire:model.live="mesActual">
                    <option value="enero">Enero</option>
                    <option value="febrero">Febrero</option>
                    <option value="marzo">Marzo</option>
                    <option value="abril">Abril</option>
                    <option value="mayo">Mayo</option>
                    <option value="junio">Junio</option>
                    <option value="julio">Julio</option>
                    <option value="agosto">Agosto</option>
                    <option value="setiembre">Setiembre</option>
                    <option value="octubre">Octubre</option>
                    <option value="noviembre">Noviembre</option>
                    <option value="diciembre">Diciembre</option>
                </select>
                <input type="number" id="anioSelector" class="form-control" wire:model.live="anioActual">
            </div>
        </div>
        <div class="col-md-7 align-self-end d-print-none">
            <div class="btn-group d-flex" role="group">
                <button class="btn btn-warning flex-fill" wire:click="mostrarModalNuevoFondo">
                    <i class="fas fa-comment-dollar"></i>
                    Fondo Permanente
                </button>
                <button class="btn btn-primary flex-fill" wire:click="openRecuperarModal">
                    <i class="fas fa-money-check"></i>
                    Recuperar todo
                </button>
            </div>
        </div>
    </div>

    <!-- Tabla Caja Chica (Fondo Permanente) -->
    <h4 class="mt-4">Fondo Permanente</h4>
    <table class="table table-striped table-bordered" id="tablaCajaChica">
        <thead class="thead-dark">
            <tr>
                <th class="text-center">Mes</th>
                <th class="text-center">Año</th>
                <th class="text-center">Monto</th>
                <th class="text-center d-print-none">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tablaCajaChica as $item)
                <tr wire:key="cajachica-{{ $item->idCajaChica }}">
                    <td class="text-center font-weight-bold">{{ $item->mes }}</td>
                    <td class="text-center font-weight-bold">{{ $item->anio }}</td>
                    <td class="text-center font-weight-bold classCajaChicaActual">
                        {{ number_format($item->montoCajaChica, 2, ',', '.') }}
                    </td>
                    <td class="text-center d-print-none">
                        <button class="btn btn-sm btn-success"
                            wire:click="editarFondo({{ $item->idCajaChica }}, {{ $item->montoCajaChica }})">
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

    <!-- Modal de Edición de Fondo -->
    @if ($showEditFondoModal)
        <div class="modal fade show" id="modalEditarFondo" tabindex="-1" role="dialog"
            aria-labelledby="modalEditarFondoLabel" style="display: block;" aria-modal="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="modalEditarFondoLabel">
                            <i class="fas fa-pencil-alt mr-2"></i>Editar Fondo Permanente
                        </h5>
                        <button type="button" class="close" wire:click="cerrarModalEditFondo" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="actualizarFondo">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="editMes" class="form-label">Mes:</label>
                                    <input type="text" class="form-control" id="editMes"
                                        value="{{ $editandoFondo['mes'] }}" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="editAnio" class="form-label">Año:</label>
                                    <input type="text" class="form-control" id="editAnio"
                                        value="{{ $editandoFondo['anio'] }}" readonly>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <label for="editMonto" class="form-label">Monto: <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number"
                                            class="form-control @error('editandoFondo.monto') is-invalid @enderror"
                                            id="editMonto" wire:model.live="editandoFondo.monto" step="0.01"
                                            min="0" max="99999999.99" placeholder="Ingrese el nuevo monto">
                                    </div>
                                    @error('editandoFondo.monto')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Monto original:
                                        ${{ number_format($editandoFondo['montoOriginal'], 2, ',', '.') }}
                                    </small>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="cerrarModalEditFondo">
                            <i class="fas fa-times mr-1"></i>Cancelar
                        </button>
                        <button type="button" class="btn btn-success" wire:click="actualizarFondo">
                            <i class="fas fa-save mr-1"></i>Actualizar Fondo
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Modal de Recuperación -->
    @if ($showRecuperarModal)
        <div class="modal fade show" id="modalRecuperar" tabindex="-1" role="dialog" style="display: block;"
            aria-modal="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Recuperar Saldos Pendientes</h5>
                        <button type="button" class="close" wire:click="closeRecuperarModal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="recuperacion_fecha">Fecha de Recuperación *</label>
                                <input type="date" id="recuperacion_fecha"
                                    class="form-control @error('recuperacion.fecha') is-invalid @enderror"
                                    wire:model.defer="recuperacion.fecha">
                                @error('recuperacion.fecha')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="recuperacion_numero_ingreso">Número de Ingreso *</label>
                                <input type="text" id="recuperacion_numero_ingreso"
                                    class="form-control @error('recuperacion.numero_ingreso') is-invalid @enderror"
                                    wire:model.defer="recuperacion.numero_ingreso" placeholder="Ej: 12345">
                                @error('recuperacion.numero_ingreso')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="5%"><input type="checkbox" wire:model.live="seleccionarTodos">
                                        </th>
                                        <th>Tipo</th>
                                        <th>Detalle</th>
                                        <th class="text-right">Saldo a Recuperar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($itemsParaRecuperar as $item)
                                        <tr wire:key="rec-item-{{ $item['id'] }}">
                                            <td><input type="checkbox" wire:model.live="itemsSeleccionados"
                                                    value="{{ $item['id'] }}"></td>
                                            <td><span
                                                    class="badge badge-{{ $item['tipo'] == 'Pendiente' ? 'info' : 'warning' }}">{{ $item['tipo'] }}</span>
                                            </td>
                                            <td>{{ $item['detalle'] }}</td>
                                            <td class="text-right">
                                                {{ number_format($item['saldo'], 2, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center">No hay ítems para recuperar.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-right">Total a Recuperar:</th>
                                        <th class="text-right font-weight-bold">
                                            {{ number_format($totalARecuperar, 2, ',', '.') }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                            @error('itemsSeleccionados')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            wire:click="closeRecuperarModal">Cancelar</button>
                        <button type="button" class="btn btn-primary" wire:click="guardarRecuperacion"
                            wire:loading.attr="disabled">
                            <span wire:loading wire:target="guardarRecuperacion"
                                class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            Guardar Recuperación
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Tabla Totales -->
    <div class="d-flex justify-content-between align-items-center mt-4">
        <h4 class="mb-0">Totales</h4>
        <div class="form-inline d-print-none">
            <label for="fechaHastaInput" class="mr-2">Fecha Hasta:</label>
            <input type="date" id="fechaHastaInput" class="form-control mr-2" wire:model.live="fechaHasta">
            <button class="btn btn-secondary btn-sm mr-2" wire:click="establecerFechaHoy">
                <i class="fas fa-calendar-day"></i> Hoy
            </button>
        </div>
    </div>

    <table class="table table-bordered mb-1" id="tablaTotales">
        <thead class="thead-dark">
            <tr>
                <th class="text-center align-middle">Pendientes</th>
                <th class="text-center align-middle">Rendidos</th>
                <th class="text-center align-middle">Extras</th>
                <th class="text-center align-middle">Pagos s/eg.</th>
                <th class="text-center align-middle">Pagos</th>
                <th class="text-center align-middle">Recuperar</th>
                <th class="text-center align-middle">Saldo en $</th>
            </tr>
        </thead>
        <tbody>
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
            @if (empty($tablaTotales))
                <tr>
                    <td colspan="7" class="text-center align-middle">No hay datos de totales.</td>
                </tr>
            @endif
        </tbody>
    </table>
    <div class="mt-0 text-center small">
        <span class="font-weight-bold">Pendientes + Pagos sin egreso = $
            {{ number_format((isset($tablaTotales['Total Pendientes']) ? floatval($tablaTotales['Total Pendientes']) : 0) + (isset($tablaTotales['Pagos Sin Egreso']) ? floatval($tablaTotales['Pagos Sin Egreso']) : 0), 2, ',', '.') }}</span>
    </div>

    <!-- Tabla Pendientes Detalle -->
    <div class="row d-flex justify-content-between align-items-center">
        <div class="col-md-6 d-flex align-items-center">
            <h4 class="mt-4">Pendientes</h4>
        </div>
        <div class="col-md-6 text-right d-print-none">
            <div class="btn-group" role="group">
                <button class="btn btn-primary flex-fill" wire:click="openModalDependencias">
                    <i class="fas fa-building"></i>
                    Dependencias
                </button>
                <button class="btn btn-info" wire:click="prepararModalNuevoPendiente">
                    <i class="fas fa-money-bill"></i>
                    Nuevo Pendiente
                </button>
            </div>
        </div>
    </div>

    <table class="table table-striped table-bordered" id="tablaPendientesDetalle">
        <thead class="thead-light">
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
                <th class="text-center align-middle d-print-none">ACCIONES</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tablaPendientesDetalle as $item)
                <tr wire:key="pendiente-{{ $item->idPendientes }}"
                    class="{{ $item->es_mes_anterior ?? false ? 'table-warning' : '' }}">
                    <td class="text-right align-middle font-weight-bold">{{ $item->pendiente }}</td>
                    <td class="text-center align-middle">
                        {{ $item->fechaPendientes ? $item->fechaPendientes->format('d/m/Y') : '' }}
                    </td>
                    <td class="text-center align-middle">{{ $item->dependencia->dependencia ?? '' }}</td>
                    <td class="text-right align-middle">{{ number_format($item->montoPendientes, 2, ',', '.') }}
                    </td>
                    <td class="text-right align-middle">{{ number_format($item->tot_rendido ?? 0, 2, ',', '.') }}
                    </td>
                    <td class="text-right align-middle">{{ number_format($item->extra ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right align-middle">
                        {{ number_format($item->tot_reintegrado ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right align-middle">
                        {{ number_format($item->tot_recuperado ?? 0, 2, ',', '.') }}</td>
                    <td
                        class="text-right align-middle {{ ($item->saldo ?? 0) > 0 ? 'text-danger font-weight-bold' : '' }}">
                        {{ number_format($item->saldo ?? 0, 2, ',', '.') }}
                    </td>
                    <td class="text-center align-middle d-print-none">
                        <input type='hidden' name='selIdPendientes' value='{{ $item->idPendientes }}'>
                        <div class='btn-group' role='group'>
                            <a href="{{ route('tesoreria.caja-chica.pendientes.editar', $item->idPendientes) }}"
                                class="btn btn-sm btn-dark mr-1" title="Editar Pendiente">
                                <i class="fas fa-pencil-alt"></i>
                            </a>
                            @if (($item->tot_recuperado ?? 0) < ($item->tot_rendido ?? 0) && ($item->tot_rendido ?? 0) > 0)
                                <button type="button" class="btn btn-sm btn-info mr-1"
                                    title="Recuperar Dinero Rendido"
                                    wire:click="openRecuperarRendidoModal({{ $item->idPendientes }})">
                                    <i class="fas fa-hand-holding-usd"></i>
                                </button>
                            @endif
                            <a href="{{ route('tesoreria.caja-chica.imprimir.pendiente', $item->idPendientes) }}"
                                target="_blank" class="btn btn-sm btn-dark mr-1" title="Imprimir Pendiente">
                                <i class="fas fa-print"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">No hay datos de Pendientes para el mes y año
                        seleccionados
                        hasta la fecha
                        {{ \Carbon\Carbon::createFromFormat('Y-m-d', $fechaHasta)->format('d/m/Y') }}.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Tabla Pagos -->
    <div class="row d-flex justify-content-between align-items-center">
        <div class="col-md-6 d-flex align-items-center">
            <h4 class="mt-4">Pagos Directos</h4>
        </div>
        <div class="col-md-6 text-right d-print-none">
            <div class="btn-group" role="group">
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
    </div>

    <table class="table table-striped table-bordered" id="tablaPagos">
        <thead class="thead-light">
            <tr>
                <th class="text-center align-middle">FCH.EG.</th>
                <th class="text-center align-middle">EGRESO</th>
                <th class="text-center align-middle">ACREEDOR</th>
                <th class="text-center align-middle">CONCEPTO</th>
                <th class="text-center align-middle">MONTO</th>
                <th class="text-center align-middle">RECUPER.</th>
                <th class="text-center align-middle">SALDO</th>
                <th class="text-center align-middle d-print-none">ACCIONES</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tablaPagos as $item)
                <tr wire:key="pago-{{ $item->idPagos }}"
                    class="{{ $item->es_mes_anterior ?? false ? 'table-warning' : '' }}">
                    <td class="text-center align-middle">
                        {{ $item->fechaEgresoPagos ? $item->fechaEgresoPagos->format('d/m/Y') : '' }}</td>
                    <td class="text-center align-middle font-weight-bold">{{ $item->egresoPagos ?? 'Sin número' }}
                    </td>
                    <td class="text-center align-middle">{{ $item->acreedor->acreedor ?? '' }}</td>
                    <td>{{ $item->conceptoPagos }}</td>
                    <td class="text-right align-middle">{{ number_format($item->montoPagos, 2, ',', '.') }}</td>
                    <td class="text-right align-middle">{{ number_format($item->recuperadoPagos, 2, ',', '.') }}
                    </td>
                    <td
                        class="text-right align-middle
                    {{ ($item->saldo_pagos ?? 0) > 0 ? 'text-danger font-weight-bold' : '' }}">
                        {{ number_format($item->saldo_pagos ?? 0, 2, ',', '.') }}
                        @if (($item->ingresoPagosBSE ?? null) == null && ($item->acreedor->acreedor ?? '') == 'Banco de Seguros del Estado')
                            <i class="fas fa-exclamation-triangle text-danger ml-1"
                                title="Ingreso BSE no encontrado"></i>
                        @endif
                    </td>
                    <td class="text-center align-middle d-print-none">
                        <input type='hidden' name='selIdPagos' value='{{ $item->idPagos }}'>
                        <div class='btn-group' role='group'>
                            <button type="button" class="btn btn-sm btn-dark mr-1"
                                wire:click="$emit('mostrarModalEditarPago', {{ $item->idPagos }})" title="Editar">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                            @if (
                                ($item->recuperadoPagos ?? 0) < ($item->montoPagos ?? 0) &&
                                    ($item->montoPagos ?? 0) > 0 &&
                                    isset($item->egresoPagos) &&
                                    strlen(trim($item->egresoPagos)) > 0)
                                <button type="button" class="btn btn-sm btn-info mr-1"
                                    title="Recuperar Pago Directo"
                                    wire:click="openRecuperarPagoModal({{ $item->idPagos }})">
                                    <i class="fas fa-hand-holding-usd"></i>
                                </button>
                            @endif
                            <a href="{{ route('tesoreria.caja-chica.imprimir.pago', $item->idPagos) }}"
                                target="_blank" class="btn btn-sm btn-dark mr-1" title="Imprimir Pago Directo">
                                <i class="fas fa-print"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No hay datos de Pagos Directos para el mes y año
                        seleccionados hasta la fecha
                        {{ \Carbon\Carbon::createFromFormat('Y-m-d', $fechaHasta)->format('d/m/Y') }}.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Modal de Recuperación de Dinero Rendido de Pendiente -->
    @if ($showRecuperarRendidoModal)
        <div class="modal fade show" id="modalRecuperarRendido" tabindex="-1" role="dialog"
            aria-labelledby="modalRecuperarRendidoLabel" style="display: block;" aria-modal="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title" id="modalRecuperarRendidoLabel">
                            <i class="fas fa-hand-holding-usd mr-2"></i>Recuperar Dinero Rendido de Pendiente
                        </h5>
                        <button type="button" class="close" wire:click="closeRecuperarRendidoModal"
                            aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <form wire:submit.prevent="saveRecuperarRendido">
                            <div class="form-group">
                                <label for="recuperarRendidoFecha">Fecha:</label>
                                <input type="date" id="recuperarRendidoFecha"
                                    class="form-control @error('recuperarRendidoData.fecha') is-invalid @enderror"
                                    wire:model.defer="recuperarRendidoData.fecha">
                                @error('recuperarRendidoData.fecha')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="recuperarRendidoDocumentos">Documentos:</label>
                                <input type="text" id="recuperarRendidoDocumentos"
                                    class="form-control @error('recuperarRendidoData.documentos') is-invalid @enderror"
                                    wire:model.defer="recuperarRendidoData.documentos"
                                    placeholder="Ingrese documentos">
                                @error('recuperarRendidoData.documentos')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="recuperarRendidoMontoRecuperado">Monto Recuperado:</label>
                                <input type="number" id="recuperarRendidoMontoRecuperado"
                                    class="form-control @error('recuperarRendidoData.monto_recuperado') is-invalid @enderror"
                                    wire:model.defer="recuperarRendidoData.monto_recuperado" step="0.01">
                                @error('recuperarRendidoData.monto_recuperado')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeRecuperarRendidoModal">
                            <i class="fas fa-times mr-1"></i>Cancelar
                        </button>
                        <button type="button" class="btn btn-info" wire:click="saveRecuperarRendido">
                            <i class="fas fa-save mr-1"></i>Guardar Recuperación
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Modal de Recuperación de Pago Directo -->
    @if ($showRecuperarPagoModal)
        <div class="modal fade show" id="modalRecuperarPago" tabindex="-1" role="dialog"
            aria-labelledby="modalRecuperarPagoLabel" style="display: block;" aria-modal="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title" id="modalRecuperarPagoLabel">
                            <i class="fas fa-hand-holding-usd mr-2"></i>Recuperar Pago Directo
                        </h5>
                        <button type="button" class="close" wire:click="closeRecuperarPagoModal"
                            aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <form wire:submit.prevent="saveRecuperarPago">
                            <div class="form-group">
                                <label for="recuperarPagoFecha">Fecha de Recuperación:</label>
                                <input type="date" id="recuperarPagoFecha"
                                    class="form-control @error('recuperarPagoData.fecha') is-invalid @enderror"
                                    wire:model.defer="recuperarPagoData.fecha">
                                @error('recuperarPagoData.fecha')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="recuperarPagoNumeroIngreso">Número de Ingreso:</label>
                                <input type="text" id="recuperarPagoNumeroIngreso"
                                    class="form-control @error('recuperarPagoData.numero_ingreso') is-invalid @enderror"
                                    wire:model.defer="recuperarPagoData.numero_ingreso"
                                    placeholder="Ingrese número de ingreso">
                                @error('recuperarPagoData.numero_ingreso')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            @if ($recuperarPagoData['es_banco_bse'] ?? false)
                                <div class="form-group">
                                    <label for="recuperarPagoNumeroIngresoBSE">Número de Ingreso BSE:</label>
                                    <input type="text" id="recuperarPagoNumeroIngresoBSE"
                                        class="form-control @error('recuperarPagoData.numero_ingreso_bse') is-invalid @enderror"
                                        wire:model.defer="recuperarPagoData.numero_ingreso_bse"
                                        placeholder="Ingrese número de ingreso BSE">
                                    @error('recuperarPagoData.numero_ingreso_bse')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            @endif
                            <div class="form-group">
                                <label for="recuperarPagoMontoRecuperado">Monto Recuperado:</label>
                                <input type="number" id="recuperarPagoMontoRecuperado"
                                    class="form-control @error('recuperarPagoData.monto_recuperado') is-invalid @enderror"
                                    wire:model.defer="recuperarPagoData.monto_recuperado" step="0.01">
                                @error('recuperarPagoData.monto_recuperado')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeRecuperarPagoModal">
                            <i class="fas fa-times mr-1"></i>Cancelar
                        </button>
                        <button type="button" class="btn btn-info" wire:click="saveRecuperarPago">
                            <i class="fas fa-save mr-1"></i>Guardar Recuperación
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Alertas para recuperación de pagos -->
    @if ($modalRecuperarPagoError)
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $modalRecuperarPagoError }}
            <button type="button" class="close" wire:click="$set('modalRecuperarPagoError', null)">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if ($modalRecuperarPagoMessage)
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ $modalRecuperarPagoMessage }}
            <button type="button" class="close" wire:click="$set('modalRecuperarPagoMessage', null)">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Incluir los componentes de modales -->
    <livewire:tesoreria.caja-chica.modal-nuevo-fondo />
    <livewire:tesoreria.caja-chica.modal-nuevo-pendiente />
    <livewire:tesoreria.caja-chica.modal-nuevo-pago />
    <livewire:tesoreria.caja-chica.modal-editar-pago />

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

            // Focus automático en el campo monto cuando se abre el modal
            Livewire.on('modal-edit-fondo-opened', function() {
                setTimeout(() => {
                    document.getElementById('editMonto').focus();
                    document.getElementById('editMonto').select();
                }, 300);
            });

            // Prevenir cierre del modal con Escape o click fuera
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && document.getElementById('modalEditarFondo')) {
                    @this.cerrarModalEditFondo();
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

            Livewire.on('cerrarModalDependencias', function() {
                @this.cerrarModalDependencias();
            });

            Livewire.on('cerrarModalAcreedores', function() {
                @this.cerrarModalAcreedores();
            });

            // Listener para cuando se recargan los datos
            Livewire.on('datosRecargados', function() {
                console.log('Datos recargados correctamente');
                // Forzar la actualización de la interfaz si es necesario
                @this.call('$refresh');
            });

            // Listener para cuando se completa la recarga forzada
            Livewire.on('recargaCompletada', function() {
                console.log('Recarga completa finalizada');
                // Aquí se pueden agregar acciones adicionales si es necesario
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
