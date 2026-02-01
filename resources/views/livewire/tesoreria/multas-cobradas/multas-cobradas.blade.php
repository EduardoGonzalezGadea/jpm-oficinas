<div>
    <style>
        .text-nowrap-custom {
            white-space: nowrap;
        }
        /* Estados de carga */
        .loading-overlay {
            position: relative;
            pointer-events: none;
        }
        .loading-overlay::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.7);
            z-index: 10;
        }
        .loading-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 20;
        }
    </style>
    <div class="container-fluid p-0 m-0">
        <div>
            <div class="card">
                <div class="card-header bg-danger text-white card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><strong><i class="fas fa-receipt mr-2"></i>Multas Cobradas</strong></h4>
                    <div class="btn-group d-print-none">
                        <button wire:click="openPrintModal" class="btn btn-info">
                            <i class="fas fa-print"></i> Informes
                        </button>
                        <a href="{{ route('tesoreria.multas-cobradas.cargar-cfe') }}" class="btn btn-warning">
                            <i class="fas fa-file-upload"></i> Cargar CFE
                        </a>
                        <button type="button" class="btn btn-primary" wire:click.prevent="create">
                            <i class="fas fa-plus"></i> Crear Multa
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if (session()->has('message'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-2"></i> {{ session('message') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    @endif

                    <!-- Selector de Mes/Año y Búsqueda -->
                    <div class="form-row mb-2">
                        <div class="col-md-5">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Mes/Año</span>
                                </div>
                                <select id="mesSelector" class="form-control" wire:model="mes">
                                    <option value="1">Enero</option>
                                    <option value="2">Febrero</option>
                                    <option value="3">Marzo</option>
                                    <option value="4">Abril</option>
                                    <option value="5">Mayo</option>
                                    <option value="6">Junio</option>
                                    <option value="7">Julio</option>
                                    <option value="8">Agosto</option>
                                    <option value="9">Septiembre</option>
                                    <option value="10">Octubre</option>
                                    <option value="11">Noviembre</option>
                                    <option value="12">Diciembre</option>
                                </select>
                                <input type="number" id="anioSelector" class="form-control" style="max-width: 100px;" wire:model="anio">
                            </div>
                        </div>
                        <div class="col-md-7 d-print-none">
                            <div class="input-group input-group-sm">
                                <input type="text" wire:model="search" id="search"
                                    class="form-control"
                                    placeholder="Buscar por nombre, recibo, cédula, concepto...">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-danger" type="button" wire:click="$set('search', '')" title="Limpiar filtro">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive {{ $isLoading ? 'loading-overlay' : '' }}">
                        @if($isLoading)
                        <div class="loading-spinner">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        </div>
                        @endif
                        <table class="table table-bordered table-striped table-hover table-sm small mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th class="text-center align-middle">Fecha</th>
                                    <th class="text-center align-middle">Recibo</th>
                                    <th class="text-center align-middle">Nombre / Cédula</th>
                                    <th class="text-center align-middle">Ítems</th>
                                    <th class="text-center align-middle">Monto Total</th>
                                    <th class="text-center align-middle d-print-none">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($registros as $registro)
                                <tr>
                                    <td class="text-center align-middle">{{ $registro->fecha->format('d/m/Y') }}</td>
                                    <td class="text-center align-middle"><strong>{{ $registro->recibo }}</strong></td>
                                    <td class="align-middle">
                                        <span class="font-weight-bold">{{ $registro->nombre }}</span>
                                        @if($registro->cedula)
                                        <span class="text-muted small ml-1">({{ $registro->cedula }})</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-info">{{ $registro->items->count() }}</span>
                                    </td>
                                    <td class="text-right align-middle font-weight-bold text-nowrap">{{ $registro->monto_formateado }}</td>
                                    <td class="text-center align-middle text-nowrap d-print-none">
                                        <div class="btn-group">
                                            <button wire:click="showDetails({{ $registro->id }})" class="btn btn-sm btn-outline-info py-0 px-2" title="Ver Detalle">
                                                <i class="fas fa-eye fa-sm"></i>
                                            </button>
                                            <button wire:click="edit({{ $registro->id }})" class="btn btn-sm btn-outline-primary py-0 px-2" title="Editar">
                                                <i class="fas fa-edit fa-sm"></i>
                                            </button>
                                            <button wire:click="confirmDelete({{ $registro->id }})" class="btn btn-sm btn-outline-danger py-0 px-2" title="Eliminar">
                                                <i class="fas fa-trash fa-sm"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="fas fa-info-circle mr-2"></i> No se encontraron registros.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 d-flex justify-content-center">
                        {{ $registros->links() }}
                    </div>

                    <!-- Resumen por Medios de Pago -->
                    <div class="row mt-3">
                        <div class="col-md-6 mx-auto">
                            <!-- Cuadro de Subtotales Individuales -->
                            <div class="card shadow-sm border-info">
                                <div class="card-header bg-info text-white py-1 px-3 small font-weight-bold d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-calculator mr-1"></i> Resumen de Ingresos por Medio de Pago</span>
                                </div>

                                <!-- Filtro de Fechas para el Resumen -->
                                <div class="card-body border-bottom p-2">
                                    <div class="d-flex justify-content-center align-items-center">
                                        <div class="input-group input-group-sm mr-2" style="max-width: 180px;">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Desde</span>
                                            </div>
                                            <input type="date" class="form-control" wire:model="resumenFechaDesde">
                                        </div>
                                        <div class="input-group input-group-sm" style="max-width: 180px;">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Hasta</span>
                                            </div>
                                            <input type="date" class="form-control" wire:model="resumenFechaHasta">
                                        </div>
                                    </div>
                                    <div class="text-center mt-1">
                                        <small class="font-italic text-body">
                                            <span>Filtrando ingresos del </span>
                                            <span class="font-weight-bold">{{ \Carbon\Carbon::parse($resumenFechaDesde)->format('d/m/Y') }}</span>
                                            <span> al </span>
                                            <span class="font-weight-bold">{{ \Carbon\Carbon::parse($resumenFechaHasta)->format('d/m/Y') }}</span>
                                        </small>
                                    </div>
                                </div>

                                <div class="card-body p-0">
                                    @php
                                    $subtotales = $totalesPorMedio->filter(fn($item) => $item->es_subtotal);
                                    $sumaGeneral = $subtotales->sum('total');
                                    @endphp

                                    @if($subtotales->count() > 0)
                                    <table class="table table-sm table-bordered mb-0 small text-body">
                                        <thead class="text-center">
                                            <tr>
                                                <th>Medio de Pago</th>
                                                <th class="text-right">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($subtotales as $totalMedio)
                                            <tr>
                                                <td class="font-weight-bold pl-3">{{ $totalMedio->forma_pago ?: 'SIN DATOS' }}</td>
                                                <td class="text-right font-weight-bold pr-3">$ {{ number_format($totalMedio->total, 2, ',', '.') }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-dark text-white">
                                            <tr>
                                                <th class="text-right pr-2">TOTAL GENERAL:</th>
                                                <th class="text-right pr-3">$ {{ number_format($sumaGeneral, 2, ',', '.') }}</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                    @else
                                    <div class="p-3 text-center text-muted">
                                        <i class="fas fa-info-circle mr-1"></i> No hay movimientos para el rango seleccionado.
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Cuadro de Totales Combinados -->
                            @php
                            $combinados = $totalesPorMedio->filter(fn($item) => $item->es_combinacion || $item->es_subtotal_combinado);
                            @endphp

                            @if($combinados->count() > 0)
                            <div class="card shadow-sm border-success mt-3">
                                <div class="card-header bg-success text-white py-1 px-3 small font-weight-bold">
                                    <i class="fas fa-equals mr-1"></i> Totales de Medios Combinados
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-bordered mb-0 small text-body">
                                        <thead class="text-center">
                                            <tr>
                                                <th>Combinación</th>
                                                <th class="text-right">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($combinados as $totalMedio)
                                            <tr>
                                                <td class="font-weight-bold {{ $totalMedio->es_subtotal_combinado ? 'pl-4' : 'pl-3' }}">
                                                    @if($totalMedio->es_subtotal_combinado)
                                                    <i class="fas fa-minus mr-1 text-muted small"></i>
                                                    @endif
                                                    @if($totalMedio->es_combinacion)
                                                    <i class="fas fa-equals mr-1"></i>
                                                    @endif
                                                    {{ $totalMedio->forma_pago }}
                                                </td>
                                                <td class="text-right font-weight-bold pr-3">$ {{ number_format($totalMedio->total, 2, ',', '.') }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Formulario -->
    @if($showModal)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document" style="max-width: 98%; margin-left: auto; margin-right: auto;">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-primary text-white py-2">
                    <h5 class="modal-title font-weight-bold mb-0 text-white">
                        <i class="fas fa-file-invoice mr-2"></i> {{ $editMode ? 'Editar' : 'Nueva' }} Multa Cobrada
                    </h5>
                    <button type="button" class="close text-white" wire:click="$set('showModal', false)">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body bg-light p-2">
                    <!-- Fila 1: Datos Principales -->
                    <div class="card mb-2 shadow-sm">
                        <div class="card-header bg-white py-1 small font-weight-bold text-primary"><i class="fas fa-id-card mr-1"></i> DATOS GENERALES DEL COBRO</div>
                        <div class="card-body p-2">
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group mb-1">
                                        <label class="small font-weight-bold mb-0">FECHA *</label>
                                        <input type="date" class="form-control form-control-sm" wire:model="fecha">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-1">
                                        <label class="small font-weight-bold mb-0">N° RECIBO *</label>
                                        <input type="text" class="form-control form-control-sm" wire:model="recibo">
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group mb-1">
                                        <label class="small font-weight-bold mb-0">NOMBRE / RAZÓN SOCIAL</label>
                                        <input type="text" class="form-control form-control-sm" wire:model="nombre" placeholder="Nombre completo...">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mb-1">
                                        <label class="small font-weight-bold mb-0">CÉDULA / RUT</label>
                                        <input type="text" class="form-control form-control-sm" wire:model="cedula" placeholder="Documento...">
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-md-12">
                                    <div class="form-group mb-0">
                                        <label class="small font-weight-bold mb-0">DOMICILIO</label>
                                        <input type="text" class="form-control form-control-sm" wire:model="domicilio" placeholder="Dirección completa...">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fila 2: Items (Ancho Completo) -->
                    <div class="card border-info mb-2 shadow-sm">
                        <div class="card-header bg-info text-white py-1 d-flex justify-content-between align-items-center">
                            <span class="small font-weight-bold text-white"><i class="fas fa-list mr-1"></i> DESGLOSE DE CONCEPTOS E IMPORTES</span>
                            <button type="button" wire:click="addItem" class="btn btn-xs btn-light font-weight-bold"><i class="fas fa-plus mr-1"></i>Añadir Línea</button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="bg-light small">
                                        <tr class="text-center">
                                            <th width="35%">DETALLE / CONCEPTO</th>
                                            <th width="45%">DESCRIPCIÓN ADICIONAL</th>
                                            <th width="15%">IMPORTE</th>
                                            <th width="5%"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items_form as $index => $item)
                                        <tr>
                                            <td class="p-1">
                                                <input type="text" wire:model="items_form.{{ $index }}.detalle" class="form-control form-control-sm" placeholder="Ej: Multa de Tránsito Art...">
                                            </td>
                                            <td class="p-1">
                                                <input type="text" wire:model="items_form.{{ $index }}.descripcion" class="form-control form-control-sm" placeholder="Opcional: aclaraciones...">
                                            </td>
                                            <td class="p-1">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend"><span class="input-group-text bg-transparent">$</span></div>
                                                    <input type="number" step="0.01" wire:model="items_form.{{ $index }}.importe" class="form-control form-control-sm text-right font-weight-bold">
                                                </div>
                                            </td>
                                            <td class="p-1 align-middle text-center">
                                                @if(count($items_form) > 1)
                                                <button type="button" wire:click="removeItem({{ $index }})" class="btn btn-xs btn-outline-danger" title="Quitar item"><i class="fas fa-trash-alt"></i></button>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-white">
                                        <tr>
                                            <th colspan="2" class="text-right align-middle py-2 pr-3">
                                                <span class="text-success font-weight-bold"><i class="fas fa-calculator mr-1"></i> MONTO TOTAL COBRADO:</span>
                                            </th>
                                            <th class="p-1">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend"><span class="input-group-text bg-success text-white border-success">$</span></div>
                                                    <input type="number" step="0.01" class="form-control form-control-sm font-weight-bold text-right border-success text-success bg-white" wire:model="monto" readonly>
                                                </div>
                                            </th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Fila 3: Otros Datos y Observaciones -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card h-100 shadow-sm border-primary">
                                <div class="card-header bg-primary text-white py-1 small font-weight-bold text-white"><i class="fas fa-wallet mr-1"></i> MEDIO DE PAGO</div>
                                <div class="card-body p-2 text-dark">
                                    <textarea class="form-control form-control-sm" wire:model="forma_pago" rows="2" placeholder="Detalle cómo se pagó..."></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100 shadow-sm border-dark">
                                <div class="card-header bg-dark text-white py-1 small font-weight-bold text-white"><i class="fas fa-address-book mr-1"></i> DATOS CONTACTO</div>
                                <div class="card-body p-2 text-dark">
                                    <textarea class="form-control form-control-sm" wire:model="adicional" rows="2" placeholder="Teléfono, email, etc..."></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100 shadow-sm border-dark">
                                <div class="card-header bg-dark text-white py-1 small font-weight-bold text-white"><i class="fas fa-link mr-1"></i> REFERENCIAS</div>
                                <div class="card-body p-2 text-dark">
                                    <textarea class="form-control form-control-sm" wire:model="referencias" rows="2" placeholder="Expedientes, internos, etc..."></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100 shadow-sm border-dark">
                                <div class="card-header bg-dark text-white py-1 small font-weight-bold text-white"><i class="fas fa-sticky-note mr-1"></i> ADENDA / NOTAS</div>
                                <div class="card-body p-2 text-dark">
                                    <textarea class="form-control form-control-sm" wire:model="adenda" rows="2" placeholder="Observaciones generales..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm px-3" wire:click="$set('showModal', false)"><i class="fas fa-times mr-1"></i> Cancelar</button>
                    <button type="button" class="btn btn-primary btn-sm px-4 shadow-sm" wire:click="save" wire:loading.attr="disabled">
                        <span wire:loading wire:target="save"><i class="fas fa-spinner fa-spin mr-1"></i> Guardando...</span>
                        <span wire:loading.remove wire:target="save"><i class="fas fa-save mr-1"></i> GUARDAR COBRO</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Modal Detalle -->
    @if($showDetailModal)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content border-0 shadow-lg" style="max-height: 95vh;">
                <div class="modal-header bg-info text-white py-2">
                    <h5 class="modal-title font-weight-bold mb-0 text-white">
                        <i class="fas fa-file-invoice mr-2"></i> Detalles del Cobro
                    </h5>
                    <button type="button" class="close text-white" wire:click="$set('showDetailModal', false)">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0">
                    @if($selectedRegistro)
                    <div class="p-4 border-bottom bg-light d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase small font-weight-bold text-muted d-block mb-1">CANTIDAD TOTAL</span>
                            <h2 class="font-weight-bold mb-0 text-success">{{ $selectedRegistro->monto_formateado }}</h2>
                        </div>
                        <div class="text-right d-none d-sm-block">
                            <span class="badge badge-info p-2 px-3">
                                <i class="fas fa-hashtag mr-1"></i> RECIBO: {{ $selectedRegistro->recibo }}
                            </span>
                            <span class="badge badge-secondary p-2 px-3 ml-2">
                                <i class="fas fa-calendar-alt mr-1"></i> {{ $selectedRegistro->fecha->format('d/m/Y') }}
                            </span>
                        </div>
                    </div>

                    <div class="px-4 py-3">
                        <!-- Sección de Identificación -->
                        <div class="row mb-4">
                            <div class="col-md-7 border-right">
                                <h6 class="text-info font-weight-bold mb-3"><i class="fas fa-user-circle mr-2"></i>IDENTIFICACIÓN</h6>
                                <div class="mb-2">
                                    <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.65rem;">Nombre / Razón Social</small>
                                    <span class="h6 font-weight-bold text-body">{{ $selectedRegistro->nombre ?: 'SIN DATOS' }}</span>
                                </div>
                                <div>
                                    <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.65rem;">Cédula / RUT</small>
                                    <span class="text-body">{{ $selectedRegistro->cedula ?: 'SIN DATOS' }}</span>
                                </div>
                            </div>
                            <div class="col-md-5 pl-md-4 mt-3 mt-md-0">
                                <h6 class="text-info font-weight-bold mb-3"><i class="fas fa-map-marker-alt mr-2"></i>DOMICILIO</h6>
                                <div>
                                    <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.65rem;">Dirección Registrada</small>
                                    <span class="text-body d-block" style="line-height: 1.2;">{{ $selectedRegistro->domicilio ?: 'SIN DATOS' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Desglose de Ítems -->
                        <h6 class="text-info font-weight-bold mb-2"><i class="fas fa-list-ul mr-2"></i>DESGLOSE DE CONCEPTOS</h6>
                        <div class="table-responsive mb-4 rounded border">
                            <table class="table table-sm table-hover mb-0 text-body">
                                <thead class="bg-info text-white small">
                                    <tr>
                                        <th class="pl-3 py-2 text-white">CONCEPTO / DETALLE</th>
                                        <th class="py-2 text-white">DESCRIPCIÓN</th>
                                        <th class="text-right pr-3 py-2 text-white">IMPORTE</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($selectedRegistro->items as $item)
                                    <tr>
                                        <td class="pl-3 align-middle font-weight-bold">{{ $item->detalle }}</td>
                                        <td class="align-middle text-muted">{{ $item->descripcion }}</td>
                                        <td class="text-right pr-3 align-middle font-weight-bold text-nowrap">$ {{ number_format($item->importe, 2, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="row">
                            <!-- Información Adicional -->
                            <div class="col-md-6">
                                <h6 class="text-info font-weight-bold mb-3"><i class="fas fa-info-circle mr-2"></i>INFORMACIÓN ADICIONAL</h6>
                                <div class="card bg-transparent border-0 mb-3">
                                    <div class="mb-3">
                                        <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.65rem;">Medio de Pago</small>
                                        <span class="badge badge-pill badge-primary py-1 px-2 text-uppercase font-weight-bold" style="font-size: 0.75rem;">{{ $selectedRegistro->forma_pago ?: 'SIN DATOS' }}</span>
                                    </div>
                                    @if($selectedRegistro->adicional)
                                    <div class="mb-3">
                                        <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.65rem;">Otros Datos</small>
                                        <span class="text-body small">{{ $selectedRegistro->adicional }}</span>
                                    </div>
                                    @endif
                                    @if($selectedRegistro->referencias)
                                    <div>
                                        <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.65rem;">Referencias</small>
                                        <span class="text-body small">{{ $selectedRegistro->referencias }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Adenda -->
                            <div class="col-md-6">
                                <h6 class="text-info font-weight-bold mb-3"><i class="fas fa-sticky-note mr-2"></i>ADENDA / OBSERVACIONES</h6>
                                <div class="bg-light p-3 rounded border small text-body" style="min-height: 80px; white-space: pre-wrap;">{{ $selectedRegistro->adenda ?: 'Sin observaciones registradas.' }}</div>
                            </div>
                        </div>

                        <!-- Metadata de Registro -->
                        <div class="mt-4 pt-3 border-top d-flex flex-column flex-sm-row justify-content-between text-muted small px-1">
                            <div class="mb-2 mb-sm-0">
                                <i class="fas fa-user-edit mr-1"></i> Registrado por: <span class="font-weight-bold">{{ $selectedRegistro->creator->nombre }} {{ $selectedRegistro->creator->apellido }}</span>
                            </div>
                            <div>
                                <i class="fas fa-clock mr-1"></i> Fecha/Hora Sistema: <span class="font-weight-bold">{{ $selectedRegistro->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm px-4 shadow-sm" wire:click="$set('showDetailModal', false)">
                        <i class="fas fa-times mr-1"></i> Cerrar
                    </button>
                    <button type="button" class="btn btn-info btn-sm px-4 shadow-sm d-print-none" onclick="window.print()">
                        <i class="fas fa-print mr-1"></i> Imprimir Página
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Modal Borrar -->
    @if($showDeleteModal)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-scrollable modal-lg" role="document" style="max-height: 90vh; display: flex; align-items: center;">
            <div class="modal-content" style="max-height: 90vh; display: flex; flex-direction: column;">
                <div class="modal-header bg-danger text-white flex-shrink-0">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Confirmar Eliminación
                    </h5>
                    <button type="button" class="close text-white" wire:click="$set('showDeleteModal', false)">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="overflow-y: auto; max-height: calc(90vh - 130px);">
                    @if($registroAEliminar)
                    <div class="alert alert-warning">
                        <strong>¿Estás seguro de eliminar este registro?</strong>
                        <p class="mb-0">Esta acción no se puede deshacer.</p>
                    </div>

                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="font-weight-bold mb-3">Detalles del Registro:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Recibo:</strong> {{ $registroAEliminar->recibo }}</p>
                                    <p class="mb-1"><strong>Fecha:</strong> {{ $registroAEliminar->fecha->format('d/m/Y') }}</p>
                                    <p class="mb-1"><strong>Nombre:</strong> {{ $registroAEliminar->nombre }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Cédula:</strong> {{ $registroAEliminar->cedula }}</p>
                                    <p class="mb-1"><strong>Monto:</strong> {{ $registroAEliminar->monto_formateado }}</p>
                                    <p class="mb-1"><strong>Ítems:</strong> {{ $registroAEliminar->items->count() }}</p>
                                </div>
                            </div>

                            @if($registroAEliminar->items->count() > 0)
                            <hr class="my-3">
                            <h6 class="font-weight-bold mb-2">Ítems a eliminar:</h6>
                            <ul class="list-unstyled mb-0">
                                @foreach($registroAEliminar->items as $item)
                                <li class="mb-1">
                                    <i class="fas fa-times text-danger mr-1"></i>
                                    {{ $item->detalle }} - <span class="text-nowrap">$ {{ number_format($item->importe, 2, ',', '.') }}</span>
                                </li>
                                @endforeach
                            </ul>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer flex-shrink-0">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showDeleteModal', false)">
                        <i class="fas fa-times mr-1"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" wire:click="delete">
                        <i class="fas fa-trash mr-1"></i> Eliminar Definitivamente
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Modal Selección de Fechas para Imprimir -->
    @if($showPrintModal)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white py-2">
                    <h5 class="modal-title font-weight-bold mb-0"><i class="fas fa-calendar-alt mr-2"></i> Seleccionar Rango de Fechas</h5>
                    <button type="button" class="close text-white" wire:click="$set('showPrintModal', false)">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body py-3 px-3">
                    <p class="text-muted small mb-2">Seleccione el rango de fechas para generar los informes de multas cobradas.</p>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group mb-2">
                                <label class="font-weight-bold small text-muted mb-1">FECHA DESDE</label>
                                <input type="date" class="form-control form-control-sm" wire:model="fechaDesde">
                                @error('fechaDesde') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-2">
                                <label class="font-weight-bold small text-muted mb-1">FECHA HASTA</label>
                                <input type="date" class="form-control form-control-sm" wire:model="fechaHasta">
                                @error('fechaHasta') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-2">

                    <div class="row mt-2">
                        <!-- Bloque de Impresión -->
                        <div class="col-md-6 mb-2">
                            <div class="card h-100 shadow-sm border-info">
                                <div class="card-header bg-info text-white py-1 px-2 small font-weight-bold text-center">
                                    <i class="fas fa-print mr-1"></i> IMPRESIÓN
                                </div>
                                <div class="card-body p-2">
                                    <button type="button" class="btn btn-outline-info btn-block btn-sm mb-2" wire:click="generarReporteResumen">
                                        <i class="fas fa-chart-pie mr-1"></i> Informe Resumen
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-block btn-sm" wire:click="generarReporte">
                                        <i class="fas fa-list-alt mr-1"></i> Informe Detallado
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Bloque de PDF -->
                        <div class="col-md-6 mb-2">
                            <div class="card h-100 shadow-sm border-danger">
                                <div class="card-header bg-danger text-white py-1 px-2 small font-weight-bold text-center">
                                    <i class="fas fa-file-pdf mr-1"></i> DESCARGAR EN PDF
                                </div>
                                <div class="card-body p-2">
                                    <button type="button" class="btn btn-outline-danger btn-block btn-sm mb-2" wire:click="generarPdfResumen">
                                        <i class="fas fa-file-pdf mr-1"></i> Descargar Resumen
                                    </button>
                                    <button type="button" class="btn btn-outline-warning btn-block btn-sm" wire:click="generarPdfDetallado">
                                        <i class="fas fa-file-download mr-1"></i> Descargar Detallado
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-4" wire:click="$set('showPrintModal', false)">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif




    <style>
        .btn-xs {
            padding: 0.1rem 0.3rem;
            font-size: 0.75rem;
        }

        .text-upper {
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        pre {
            font-family: inherit;
        }
    </style>

    <script>
        document.addEventListener('livewire:load', function() {
            window.livewire.on('openInNewTab', (url) => {
                window.open(url, '_blank');
            });
        });
    </script>
</div>
