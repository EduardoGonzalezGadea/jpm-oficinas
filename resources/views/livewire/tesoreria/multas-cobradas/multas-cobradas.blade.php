<div>
    <style>
        [x-cloak] {
            display: none !important;
        }

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
                <div class="card-header bg-danger text-white card-header-gradient py-1 px-3 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><strong><i class="fas fa-receipt mr-2"></i>Multas Cobradas</strong></h4>
                    <div class="btn-group d-print-none">
                        <a href="{{ route('tesoreria.multas-cobradas.reportes') }}" class="btn btn-secondary">
                            <i class="fas fa-filter"></i> Filtrar
                        </a>
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
                <div class="card-body pt-1 px-2">
                    @if (session()->has('message'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-2"></i> {{ session('message') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    @endif

                    <!-- Selector de Mes/Año y Búsqueda -->
                    <div class="form-row mb-1">
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
                                    <button class="btn btn-danger" type="button" wire:click="$set('search', '')" title="Limpiar filtro">
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
                                <tr class="text-dark">
                                    <th class="text-center align-middle">Fecha</th>
                                    <th class="text-center align-middle">Recibo</th>
                                    <th class="text-center align-middle">Nombre / Cédula / Forma de Pago</th>
                                    <th class="text-center align-middle">Ítems</th>
                                    <th class="text-center align-middle">Monto Total</th>
                                    <th class="text-center align-middle d-print-none">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($registros as $registro)
                                <tr>
                                    <td class="text-center align-middle">{{ $registro->fecha->format('d/m/Y') }}</td>
                                    <td class="text-center align-middle font-weight-bold">{{ $registro->recibo }}</td>
                                    <td class="align-middle">
                                        <div class="font-weight-bold">{{ $registro->nombre }}</div>
                                        @if($registro->cedula || $registro->forma_pago)
                                        <div class="text-muted small">
                                            @if($registro->cedula)
                                            <span>CI: {{ $registro->cedula }}</span>
                                            @endif
                                            @if($registro->forma_pago)
                                            <span class="{{ $registro->cedula ? 'ml-2' : '' }}">FORMA DE PAGO: {{ $this->formatearFormaPagoUy($registro->forma_pago) }}</span>
                                            @endif
                                        </div>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        @php
                                        $itemsTooltip = $registro->items->map(function($item) {
                                        $detalle = $item->detalle ?: 'Sin detalle';
                                        // Eliminar "MULTAS DE TRÁNSITO" o "MULTAS DE TRANSITO" al principio
                                        $detalle = preg_replace('/^MULTAS DE TRANSITO\s*/i', '', $detalle);
                                        $detalle = preg_replace('/^MULTAS DE TRÁNSITO\s*/i', '', $detalle);
                                        return '<strong>' . trim($detalle) . '</strong>: $ ' . number_format($item->importe, 2, ',', '.');
                                        })->join('<br><br>');
                                        @endphp
                                        <span class="badge badge-info"
                                            data-toggle="tooltip"
                                            data-html="true"
                                            data-placement="auto"
                                            data-boundary="window"
                                            title="{!! $itemsTooltip !!}"
                                            style="cursor: help;">
                                            {{ $registro->items->count() }}
                                        </span>
                                    </td>
                                    <td class="text-right align-middle font-weight-bold text-nowrap">{{ $registro->monto_formateado }}</td>
                                    <td class="text-center align-middle text-nowrap d-print-none">
                                        <div class="btn-group">
                                            <button wire:click="showDetails({{ $registro->id }})" class="btn btn-sm btn-info py-0 px-2" title="Ver Detalle">
                                                <i class="fas fa-eye fa-sm"></i>
                                            </button>
                                            <button wire:click="edit({{ $registro->id }})" class="btn btn-sm btn-primary py-0 px-2" title="Editar">
                                                <i class="fas fa-edit fa-sm"></i>
                                            </button>
                                            <button wire:click="confirmDelete({{ $registro->id }})" class="btn btn-sm btn-danger py-0 px-2" title="Eliminar">
                                                <i class="fas fa-trash fa-sm"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted font-italic">
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
                                            <input type="text" class="form-control datepicker-uy" wire:model="resumenFechaDesde">
                                        </div>
                                        <div class="input-group input-group-sm" style="max-width: 180px;">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Hasta</span>
                                            </div>
                                            <input type="text" class="form-control datepicker-uy" wire:model="resumenFechaHasta">
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
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0 small text-reset">
                                            <thead class="text-center bg-light text-dark">
                                                <tr>
                                                    <th>Medio de Pago</th>
                                                    <th class="text-right">Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($subtotales as $totalMedio)
                                                <tr>
                                                    <td class="font-weight-bold pl-3">{{ $totalMedio->forma_pago ?: 'SIN DATOS' }}</td>
                                                    <td class="text-right font-weight-bold pr-3 text-info">$ {{ number_format($totalMedio->total, 2, ',', '.') }}</td>
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
                                    </div>
                                    @else
                                    <div class="p-3 text-center text-muted font-italic">
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
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0 small text-reset">
                                            <thead class="text-center bg-light text-dark">
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
                                                    <td class="text-right font-weight-bold pr-3 text-success">$ {{ number_format($totalMedio->total, 2, ',', '.') }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
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
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog" wire:ignore.self
        x-cloak
        x-data="{
        isClosing: false,
        mediosAgregados: [],
        nuevoMedio: '',
        nuevoMonto: '',
        totalItems: {{ $this->suma_items ?? 0 }},

        init() {
            this.parseMedios();
            this.$nextTick(() => {
                const el = document.getElementById('input-recibo');
                if (el) el.focus();
                // Ensure calculation runs after everything is ready
                this.calculateTotalItems();
            });
        },

        closeModal() {
            this.isClosing = true;
            setTimeout(() => {
                $wire.set('showModal', false);
            }, 200);
        },

        parseNumber(val) {
            if (!val) return 0;
            if (typeof val === 'number') return val;
            let s = val.toString().trim();
            if (s.includes(',')) {
                return parseFloat(s.replace(/\./g, '').replace(',', '.')) || 0;
            }
            if ((s.match(/\./g) || []).length > 1) {
                return parseFloat(s.replace(/\./g, '')) || 0;
            }
            return parseFloat(s) || 0;
        },

        formatMoney(value) {
            return '$ ' + value.toLocaleString('es-UY', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        },

        calculateTotalItems() {
            // Use querySelectorAll to find all current inputs in the DOM
            let inputs = document.querySelectorAll('.item-importe');
            this.totalItems = Array.from(inputs).reduce((sum, el) => {
                return sum + this.parseNumber(el.value);
            }, 0);
        },

        parseMedios() {
            let formaPago = $wire.forma_pago;
            if (!formaPago || formaPago === 'SIN DATOS') {
                this.mediosAgregados = [];
                return;
            }
            let partes = formaPago.split('/');
            this.mediosAgregados = partes.filter(p => p.trim() !== '').map(p => {
                let sub = p.split(':');
                return {
                    nombre: sub[0].trim(),
                    monto: sub[1] ? sub[1].trim() : ''
                };
            });
        },

        agregarMedio() {
            if (!this.nuevoMedio) return;

            let nombre = this.nuevoMedio.replace(':', '').trim();
            let totalActual = parseFloat($wire.monto || 0);

            if (!this.nuevoMonto || this.nuevoMonto == 0) {
                let saldo = totalActual - this.calcularTotalMedios();
                if (saldo > 0) this.nuevoMonto = saldo.toFixed(2);
            }

            this.mediosAgregados.push({
                nombre: nombre,
                monto: this.nuevoMonto
            });

            this.nuevoMedio = '';
            this.nuevoMonto = '';
            this.syncFormaPago();
        },

        calcularTotalMedios() {
            return this.mediosAgregados.reduce((acc, m) => acc + this.parseNumber(m.monto), 0);
        },

        removerMedio(index) {
            this.mediosAgregados.splice(index, 1);
            this.syncFormaPago();
        },

        syncFormaPago() {
            $wire.forma_pago = this.mediosAgregados.map(m => {
                let val = this.parseNumber(m.monto);
                return m.nombre + (val ? ':' + val.toFixed(2) : '');
            }).join('/');
        },

        confirmSave() {
            this.calculateTotalItems();
            
            let totalFactura = this.totalItems;
            let totalMedios = this.calcularTotalMedios();
            let diferencia = Math.abs(totalFactura - totalMedios);
            
            $wire.set('monto', totalFactura, true);

            if (diferencia > 0.01) {
                Swal.fire({
                    title: '¡Discrepancia de Montos!',
                    html: `El monto total de la multa (<b>$ ${totalFactura.toLocaleString('es-UY', {minimumFractionDigits: 2})}</b>) no coincide con la suma de los medios de pago (<b>$ ${totalMedios.toLocaleString('es-UY', {minimumFractionDigits: 2})}</b>).<br><br>¿Deseas continuar de todas formas?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'SÍ, GUARDAR ASÍ',
                    cancelButtonText: 'CANCELAR Y CORREGIR',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $wire.save(true);
                    }
                });
            } else {
                $wire.save();
            }
        }
    }">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-animate-in"
            :class="{'modal-animate-out': isClosing}"
            role="document" style="max-width: 95%;">
            <div class="modal-content shadow-lg border-0"
                @click="calculateTotalItems()"
                @keyup="calculateTotalItems()"
                x-on:update-total.window="totalItems = $event.detail.total">
                <div class="modal-header bg-primary text-white py-2 shadow-sm">
                    <h6 class="modal-title font-weight-bold mb-0">
                        <i class="fas {{ $editMode ? 'fa-edit' : 'fa-plus-circle' }} mr-2"></i>
                        {{ $editMode ? 'EDITAR REGISTRO DE COBRO' : 'REGISTRAR NUEVA MULTA COBRADA' }}
                    </h6>
                    <button type="button" class="close text-white outline-none" @click="closeModal()" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body px-4 py-3">
                    @if (session()->has('error'))
                    <div class="alert alert-danger shadow-sm mb-3">
                        <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
                    </div>
                    @endif

                    <div class="row no-gutters">
                        <!-- Fila 1: Identificación Superior (Compacta) -->
                        <div class="col-12 px-1 mb-2">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-2">
                                    <div class="form-row align-items-end">
                                        <div class="col-md-2 mb-1 mb-md-0">
                                            <label class="label-compact">Fecha de Cobro</label>
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend"><span class="input-group-text p-1 px-2 border-0 bg-transparent text-primary"><i class="fas fa-calendar-day"></i></span></div>
                                                <input type="text" class="form-control form-control-compact datepicker-uy @error('fecha') is-invalid @enderror" wire:model.defer="fecha">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-1 mb-md-0">
                                            <label class="label-compact">Nro. Recibo</label>
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend"><span class="input-group-text p-1 px-2 border-0 bg-transparent text-primary"><i class="fas fa-hashtag"></i></span></div>
                                                <input type="text" id="input-recibo" class="form-control form-control-compact @error('recibo') is-invalid @enderror" wire:model.defer="recibo" placeholder="A-XXXX">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-1 mb-md-0">
                                            <label class="label-compact">Cédula / RUT</label>
                                            <input type="text" class="form-control form-control-compact @error('cedula') is-invalid @enderror" wire:model.defer="cedula" placeholder="12345678">
                                        </div>
                                        <div class="col-md-3 mb-1 mb-md-0">
                                            <label class="label-compact">Nombre / Razón Social</label>
                                            <input type="text" class="form-control form-control-compact @error('nombre') is-invalid @enderror" wire:model.defer="nombre" placeholder="Nombre completo...">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="label-compact">Domicilio</label>
                                            <input type="text" class="form-control form-control-compact" wire:model.defer="domicilio" placeholder="Dirección...">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fila 2: Desglose de Ítems (Ancho Completo) -->
                        <div class="col-12 px-1 mb-2">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-dark text-white py-1 d-flex justify-content-between align-items-center border-bottom-0">
                                    <h6 class="text-primary font-weight-bold mb-0 small text-uppercase tracking-wider">
                                        <i class="fas fa-list-ul mr-1 small"></i> Detalle de Multas
                                    </h6>
                                    <button type="button" wire:click="addItem" class="btn btn-info btn-xs py-0 px-2 shadow-sm font-weight-bold" style="font-size: 0.7rem;">
                                        <i class="fas fa-plus mr-1"></i> AGREGAR FILA
                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-compact mb-0">
                                            <thead class="bg-primary text-white small">
                                                <tr>
                                                    <th width="60%" class="pl-3 font-weight-normal py-1">DETALLE</th>
                                                    <th width="20%" class="font-weight-normal text-center py-1">DESCRIPCIÓN</th>
                                                    <th width="17%" class="text-right pr-1 font-weight-normal py-1">IMPORTE</th>
                                                    <th width="3%" class="py-1"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($items_form as $index => $item)
                                                <tr class="item-row" wire:key="item-row-{{ $item['_uid'] ?? $index }}">
                                                    <td class="pl-3">
                                                        <input type="text" wire:model.defer="items_form.{{ $index }}.detalle"
                                                            wire:key="detalle-{{ $item['_uid'] }}"
                                                            class="form-control form-control-compact @error('items_form.'.$index.'.detalle') is-invalid @enderror"
                                                            placeholder="Concepto..." list="sugerencias-detalle" required>
                                                    </td>
                                                    <td>
                                                        <input type="text" wire:model.defer="items_form.{{ $index }}.descripcion"
                                                            wire:key="descripcion-{{ $item['_uid'] }}"
                                                            class="form-control form-control-compact"
                                                            placeholder="Opcional...">
                                                    </td>
                                                    <td class="pr-1">
                                                        <div class="input-group input-group-compact">
                                                            <div class="input-group-prepend"><span class="input-group-text bg-transparent border-0 text-muted">$</span></div>
                                                            <input type="number" step="1.00" wire:model.defer="items_form.{{ $index }}.importe"
                                                                wire:key="importe-{{ $item['_uid'] }}"
                                                                class="form-control form-control-compact text-right font-weight-bold item-importe"
                                                                value="{{ $item['importe'] }}"
                                                                @input="calculateTotalItems()"
                                                                required>
                                                        </div>
                                                    </td>
                                                    <td class="p-1 align-middle text-center">
                                                        @if(count($items_form) > 1)
                                                        <button type="button" wire:click="removeItem({{ $index }})"
                                                            wire:loading.attr="disabled"
                                                            wire:target="removeItem({{ $index }})"
                                                            class="btn btn-link text-danger p-0" title="Eliminar fila">
                                                            <span wire:loading.remove wire:target="removeItem({{ $index }})">
                                                                <i class="fas fa-times-circle"></i>
                                                            </span>
                                                            <span wire:loading wire:target="removeItem({{ $index }})">
                                                                <i class="fas fa-spinner fa-spin text-muted"></i>
                                                            </span>
                                                        </button>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="bg-dark text-white border-top shadow-sm">
                                                <tr>
                                                    <td colspan="2" class="text-right align-middle py-1 pr-4">
                                                        <span class="small mb-0 text-white font-weight-bold">MONTO TOTAL A PERCIBIR:</span>
                                                    </td>
                                                    <td class="py-1 pr-1">
                                                        <div class="h6 mb-0 text-right font-weight-bold text-white">
                                                            <span x-text="formatMoney(totalItems)"></span>
                                                        </div>
                                                    </td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fila 3: Pago y Otros Datos -->
                        <div class="col-lg-7 px-1">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-2">
                                    <label class="label-compact">
                                        <i class="fas fa-money-check-alt mr-1 text-success"></i> Medios de Pago (Múltiples)
                                    </label>

                                    <div class="input-group input-group-sm input-group-compact mb-1 shadow-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text p-1 px-2 border-right-0"><i class="fas fa-plus text-success" style="font-size: 0.7rem;"></i></span>
                                        </div>
                                        <input type="text" x-model="nuevoMedio" x-cloak class="form-control form-control-compact border-left-0"
                                            placeholder="Medio..." list="sugerencias-medios"
                                            @keydown.enter.prevent="agregarMedio()">
                                        <input type="number" x-model="nuevoMonto" x-cloak class="form-control form-control-compact"
                                            placeholder="Monto" style="max-width: 90px;"
                                            @keydown.enter.prevent="agregarMedio()">
                                        <div class="input-group-append">
                                            <button type="button" @click="agregarMedio()" class="btn btn-success" title="Agregar medio">
                                                <i class="fas fa-check small"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap align-items-center mb-0" style="min-height: 30px;" x-cloak>
                                        <template x-for="(m, index) in mediosAgregados" :key="index">
                                            <div class="badge badge-info mr-1 mb-1 p-1 px-2 d-flex align-items-center shadow-sm" style="font-size: 0.75rem;">
                                                <i class="fas fa-wallet mr-1 small opacity-75"></i>
                                                <span x-text="m.nombre"></span>:<span class="ml-1 font-weight-bold" x-text="parseNumber(m.monto || 0).toLocaleString('es-UY', {minimumFractionDigits: 2})"></span>
                                                <button type="button" @click="removerMedio(index)" class="close ml-1 text-white" style="text-shadow: none; opacity: 0.8; font-size: 0.9rem; line-height: 1;">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                        </template>
                                    </div>

                                    <input type="hidden" wire:model="forma_pago">
                                    @error('forma_pago') <span class="text-danger d-block small font-weight-bold">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5 px-1">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-2">
                                    <div class="form-group mb-1">
                                        <label class="label-compact">
                                            <i class="fas fa-sticky-note mr-1 text-info"></i> Notas
                                        </label>
                                        <input type="text" class="form-control form-control-compact" wire:model.defer="adenda" placeholder="MATRÍCULA XXX BOLETA...">
                                    </div>
                                    <div class="form-row">
                                        <div class="col-md-5">
                                            <div class="form-group mb-0">
                                                <label class="label-compact">Teléfono</label>
                                                <input type="text" class="form-control form-control-compact" wire:model.defer="temp_tel" placeholder="099...">
                                            </div>
                                        </div>
                                        <div class="col-md-7">
                                            <div class="form-group mb-0">
                                                <label class="label-compact">Período</label>
                                                <div class="d-flex">
                                                    <input type="text" class="form-control form-control-compact datepicker-uy mr-1" wire:model.defer="temp_periodo_desde" placeholder="Desde">
                                                    <input type="text" class="form-control form-control-compact datepicker-uy" wire:model.defer="temp_periodo_hasta" placeholder="Hasta">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-3 px-4 border-top d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        <small class="text-uppercase font-weight-bold"><i class="fas fa-info-circle mr-1"></i> Registro de auditoría activo</small>
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary btn-sm px-4 mr-2 font-weight-bold shadow-sm" @click="closeModal()">
                            <i class="fas fa-times mr-1"></i> DESCARTAR
                        </button>
                        <button type="button" class="btn btn-primary btn-sm px-5 shadow-sm font-weight-bold" @click="confirmSave()" wire:loading.attr="disabled">
                            <span wire:loading wire:target="save"><i class="fas fa-circle-notch fa-spin mr-2"></i>GUARDANDO...</span>
                            <span wire:loading.remove wire:target="save"><i class="fas fa-check-circle mr-2"></i>FINALIZAR Y GUARDAR</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show backdrop-animate-in"></div>
    @endif

    <datalist id="sugerencias-detalle">
        @foreach($sugerenciasDetalle as $sugerencia)
        <option value="{{ $sugerencia }}">
            @endforeach
    </datalist>

    <datalist id="sugerencias-medios">
        @foreach($mediosDisponibles as $medio)
        <option value="{{ $medio }}:">
            @endforeach
    </datalist>

    <!-- Modal Detalle Mejorado -->
    @if($showDetailModal)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog"
        x-data="{
            isClosing: false,
            closeDetail() {
                this.isClosing = true;
                setTimeout(() => {
                    $wire.set('showDetailModal', false);
                }, 200);
            },
            editFromDetail(id) {
                const wire = $wire;
                this.isClosing = true;
                setTimeout(() => {
                    wire.set('showDetailModal', false);
                    setTimeout(() => {
                        wire.edit(id);
                    }, 300);
                }, 250);
            }
        }">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-animate-in"
            :class="{'modal-animate-out': isClosing}"
            role="document">
            <div class="modal-content border-0 shadow" style="max-height: 90vh;">
                <!-- Header Compacto -->
                <div class="modal-header bg-dark text-white py-2 px-3">
                    <div class="d-flex align-items-center w-100 justify-content-between">
                        <div class="d-flex align-items-center">
                            <span class="badge badge-light mr-2">{{ $selectedRegistro->recibo }}</span>
                            <span class="text-light small">{{ $selectedRegistro->fecha->format('d/m/Y') }}</span>
                        </div>
                        <button type="button" class="close text-white" @click="closeDetail()" style="opacity: 0.9;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>

                <!-- Sección de Monto Total Minimalista -->
                <div class="border-bottom py-2 px-3 bg-light d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <span class="text-muted small mr-2">Total:</span>
                        <span class="h5 font-weight-bold text-success mb-0">{{ $selectedRegistro->monto_formateado }}</span>
                    </div>
                    <div class="d-flex flex-wrap">
                        @foreach(explode('/', $selectedRegistro->forma_pago) as $medio)
                        <span class="badge badge-secondary mr-1">{{ $medio }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="modal-body p-0" style="overflow-y: auto;">
                    @if($selectedRegistro)
                    <!-- Sección de Contribuyente -->
                    <div class="border-bottom py-2 px-3 bg-light">
                        <div class="row small">
                            <div class="col-md-6">
                                <span class="text-muted">Nombre:</span>
                                <strong>{{ $selectedRegistro->nombre }}</strong>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted">CI:</span>
                                <strong>{{ $selectedRegistro->cedula }}</strong>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted">Tel:</span>
                                <strong>{{ $selectedRegistro->temp_tel }}</strong>
                            </div>
                        </div>
                        <div class="row small mt-1">
                            <div class="col-12">
                                <span class="text-muted">Domicilio:</span>
                                <span>{{ $selectedRegistro->domicilio }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Conceptos -->
                    <div class="p-0">
                        <table class="table table-sm table-borderless mb-0 small">
                            <thead class="border-bottom">
                                <tr class="text-muted">
                                    <th class="border-0 py-1">CONCEPTO</th>
                                    <th class="border-0 py-1" style="width: 120px;">DESCRIPCIÓN</th>
                                    <th class="border-0 py-1 text-right" style="width: 100px;">IMPORTE</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedRegistro->items as $item)
                                <tr>
                                    <td class="py-1">{{ $item->detalle }}</td>
                                    <td class="py-1 text-muted">{{ $item->descripcion ?: '-' }}</td>
                                    <td class="py-1 text-right font-weight-bold">$ {{ number_format($item->importe, 2, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Sección de Observaciones -->
                    <div class="border-top py-2 px-3">
                        <div class="small text-muted text-uppercase mb-1">Observaciones</div>
                        <div class="small">{{ $selectedRegistro->adenda ?: 'Sin observaciones' }}</div>
                    </div>

                    @endif
                </div>

                <!-- Footer Minimalista -->
                <div class="modal-footer py-2 px-3 bg-light border-top">
                    <small class="text-muted mr-auto">
                        <i class="fas fa-user-edit"></i> {{ $selectedRegistro->creator->nombre }} · {{ $selectedRegistro->created_at->format('d/m/Y H:i') }}
                    </small>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-secondary" onclick="window.print()">
                            <i class="fas fa-print"></i>
                        </button>
                        <button class="btn btn-primary" @click="editFromDetail({{ $selectedRegistro->id ?? 0 }})">
                            <i class="fas fa-edit mr-1"></i> Editar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show backdrop-animate-in"></div>
    @endif

    <!-- Modal Borrar -->
    @if($showDeleteModal)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog"
        x-data="{
            isClosing: false,
            closeDelete() {
                this.isClosing = true;
                setTimeout(() => {
                    $wire.set('showDeleteModal', false);
                }, 200);
            }
        }">
        <div class="modal-dialog modal-dialog-scrollable modal-lg modal-animate-in"
            :class="{'modal-animate-out': isClosing}"
            role="document" style="max-height: 90vh; display: flex; align-items: center;">
            <div class="modal-content shadow-lg border-0" style="max-height: 90vh; display: flex; flex-direction: column;">
                <div class="modal-header bg-danger text-white flex-shrink-0">
                    <h5 class="modal-title font-weight-bold mb-0">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Confirmar Eliminación
                    </h5>
                    <button type="button" class="close text-white" @click="closeDelete()">
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
                    <button type="button" class="btn btn-secondary" @click="closeDelete()">
                        <i class="fas fa-times mr-1"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" wire:click="delete">
                        <i class="fas fa-trash mr-1"></i> Eliminar Definitivamente
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show backdrop-animate-in"></div>
    @endif

    <!-- Modal Selección de Fechas para Imprimir -->
    @if($showPrintModal)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog"
        x-data="{
            isClosing: false,
            closePrint() {
                this.isClosing = true;
                setTimeout(() => {
                    $wire.set('showPrintModal', false);
                }, 200);
            }
        }">
        <div class="modal-dialog modal-md modal-animate-in"
            :class="{'modal-animate-out': isClosing}"
            role="document">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-info text-white py-2">
                    <h5 class="modal-title font-weight-bold mb-0 text-white"><i class="fas fa-calendar-alt mr-2"></i> Seleccionar Rango de Fechas</h5>
                    <button type="button" class="close text-white" @click="closePrint()">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body py-3 px-3">
                    <p class="text-muted small mb-2">Seleccione el rango de fechas para generar los informes de multas cobradas.</p>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group mb-2">
                                <label class="font-weight-bold small text-muted mb-1 text-uppercase">Fecha Desde</label>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend"><span class="input-group-text p-1 px-2 border-0 bg-transparent text-primary"><i class="fas fa-calendar-alt"></i></span></div>
                                    <input type="text" class="form-control form-control-compact datepicker-uy @error('fechaDesde') is-invalid @enderror" wire:model.defer="fechaDesde">
                                </div>
                                @error('fechaDesde') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-2">
                                <label class="font-weight-bold small text-muted mb-1 text-uppercase">Fecha Hasta</label>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend"><span class="input-group-text p-1 px-2 border-0 bg-transparent text-primary"><i class="fas fa-calendar-check"></i></span></div>
                                    <input type="text" class="form-control form-control-compact datepicker-uy @error('fechaHasta') is-invalid @enderror" wire:model.defer="fechaHasta">
                                </div>
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
                                    <button type="button" class="btn btn-info btn-block btn-sm mb-2" wire:click="generarReporteResumen">
                                        <i class="fas fa-chart-pie mr-1"></i> Informe Resumen
                                    </button>
                                    <button type="button" class="btn btn-primary btn-block btn-sm" wire:click="generarReporte">
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
                                    <button type="button" class="btn btn-danger btn-block btn-sm mb-2" wire:click="generarPdfResumen">
                                        <i class="fas fa-file-pdf mr-1"></i> Descargar Resumen
                                    </button>
                                    <button type="button" class="btn btn-warning btn-block btn-sm" wire:click="generarPdfDetallado">
                                        <i class="fas fa-file-download mr-1"></i> Descargar Detallado
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light border-top">
                    <button type="button" class="btn btn-secondary btn-sm px-4 shadow-sm" @click="closePrint()">
                        <i class="fas fa-times mr-1"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show backdrop-animate-in"></div>
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
        document.addEventListener('DOMContentLoaded', function() {
            initTooltips();
        });

        document.addEventListener('livewire:load', function() {
            initTooltips();
        });

        document.addEventListener('livewire:update', function() {
            initTooltips();
        });

        function initTooltips() {
            $('[data-toggle="tooltip"]').tooltip('dispose').tooltip();
        }
    </script>

</div>
