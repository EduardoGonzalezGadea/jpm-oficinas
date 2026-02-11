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
                                <tr class="text-dark">
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
                                    <td class="text-center align-middle font-weight-bold">{{ $registro->recibo }}</td>
                                    <td class="align-middle">
                                        <div class="font-weight-bold">{{ $registro->nombre }}</div>
                                        @if($registro->cedula)
                                        <div class="text-muted small">{{ $registro->cedula }}</div>
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
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog" x-data="{ 
        isClosing: false,
        closeModal() {
            this.isClosing = true;
            setTimeout(() => {
                $wire.set('showModal', false);
            }, 200);
        },
        totalLocal: @entangle('monto'),
        formaPagoLocal: @entangle('forma_pago'),
        items: @entangle('items_form'),
        mediosAgregados: [],
        nuevoMedio: '',
        nuevoMonto: '',
        
        init() {
            this.parseMedios();
            this.calculateTotal();
            this.$nextTick(() => {
                const el = document.getElementById('input-recibo');
                if (el) el.focus();
            });
            // Observar cambios externos (como al abrir para edición)
            this.$watch('formaPagoLocal', (val) => {
                if (val && this.mediosAgregados.length === 0) this.parseMedios();
                if (!val) this.mediosAgregados = [];
            });
            this.$watch('items', () => this.calculateTotal());
        },

        parseNumber(val) {
            if (!val) return 0;
            if (typeof val === 'number') return val;
            let s = val.toString().trim();
            // Si tiene coma, es formato regional (miles con punto, decimal con coma)
            if (s.includes(',')) {
                return parseFloat(s.replace(/\./g, '').replace(',', '.')) || 0;
            }
            // Si tiene más de un punto, definitivamente son miles
            if ((s.match(/\./g) || []).length > 1) {
                return parseFloat(s.replace(/\./g, '')) || 0;
            }
            // Si tiene un solo punto y no tiene coma, lo tratamos como decimal estándar
            return parseFloat(s) || 0;
        },
        
        parseMedios() {
            if (!this.formaPagoLocal || this.formaPagoLocal === 'SIN DATOS') {
                this.mediosAgregados = [];
                return;
            }
            let partes = this.formaPagoLocal.split('/');
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
            
            // Normalizar nombre quitando : si viene del datalist
            let nombre = this.nuevoMedio.replace(':', '').trim();
            
            // Si el monto está vacío, sugerimos el saldo restante
            if (!this.nuevoMonto || this.nuevoMonto == 0) {
                let saldo = parseFloat(this.totalLocal || 0) - this.calcularTotalMedios();
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
            this.formaPagoLocal = this.mediosAgregados.map(m => m.nombre + (m.monto ? ':' + m.monto : '')).join('/');
        },
        
        calculateTotal() {
            let sum = this.items.reduce((acc, item) => acc + this.parseNumber(item.importe), 0);
            this.totalLocal = sum.toFixed(2);
        },

        confirmSave() {
            let totalFactura = parseFloat(this.totalLocal || 0);
            let totalMedios = this.calcularTotalMedios();
            let diferencia = Math.abs(totalFactura - totalMedios);

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
                        $wire.save();
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
            <div class="modal-content shadow-lg border-0">
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
                                                <tr class="item-row">
                                                    <td class="pl-3">
                                                        <input type="text" wire:model.defer="items_form.{{ $index }}.detalle"
                                                            class="form-control form-control-compact @error('items_form.'.$index.'.detalle') is-invalid @enderror"
                                                            placeholder="Concepto..." list="sugerencias-detalle" required>
                                                    </td>
                                                    <td>
                                                        <input type="text" wire:model.defer="items_form.{{ $index }}.descripcion"
                                                            class="form-control form-control-compact"
                                                            placeholder="Opcional...">
                                                    </td>
                                                    <td class="pr-1">
                                                        <div class="input-group input-group-compact">
                                                            <div class="input-group-prepend"><span class="input-group-text bg-transparent border-0 text-muted">$</span></div>
                                                            <input type="number" step="1.00" wire:model="items_form.{{ $index }}.importe"
                                                                class="form-control form-control-compact text-right font-weight-bold item-importe"
                                                                @input="calculateTotal()" required>
                                                        </div>
                                                    </td>
                                                    <td class="p-1 align-middle text-center">
                                                        @if(count($items_form) > 1)
                                                        <button type="button" wire:click="removeItem({{ $index }})"
                                                            class="btn btn-link text-danger p-0" title="Eliminar fila">
                                                            <i class="fas fa-times-circle"></i>
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
                                                        <div class="h6 mb-0 text-right font-weight-bold text-white" x-text="'$ ' + parseNumber(totalLocal).toLocaleString('es-UY', {minimumFractionDigits: 2})">
                                                            $ {{ number_format($this->sumaItems, 2, ',', '.') }}
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
                                        <input type="text" x-model="nuevoMedio" class="form-control form-control-compact border-left-0"
                                            placeholder="Medio..." list="sugerencias-medios"
                                            @keydown.enter.prevent="agregarMedio()">
                                        <input type="number" x-model="nuevoMonto" class="form-control form-control-compact"
                                            placeholder="Monto" style="max-width: 90px;"
                                            @keydown.enter.prevent="agregarMedio()">
                                        <div class="input-group-append">
                                            <button type="button" @click="agregarMedio()" class="btn btn-success" title="Agregar medio">
                                                <i class="fas fa-check small"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap align-items-center mb-0" style="min-height: 30px;">
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

                                    <input type="hidden" wire:model.defer="forma_pago">
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

    <!-- Modal Detalle -->
    @if($showDetailModal)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog"
        x-data="{ 
            isClosing: false,
            closeDetail() {
                this.isClosing = true;
                setTimeout(() => {
                    $wire.set('showDetailModal', false);
                }, 200);
            }
        }">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-animate-in"
            :class="{'modal-animate-out': isClosing}"
            role="document">
            <div class="modal-content border-0 shadow-lg" style="max-height: 95vh;">
                <div class="modal-header bg-info text-white py-2">
                    <h5 class="modal-title font-weight-bold mb-0 text-white">
                        <i class="fas fa-file-invoice mr-2"></i> Detalles del Cobro
                    </h5>
                    <button type="button" class="close text-white" @click="closeDetail()">
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
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="card bg-light border-0">
                                    <div class="card-body p-3">
                                        <h6 class="text-info font-weight-bold mb-3 border-bottom pb-2">
                                            <i class="fas fa-id-card mr-2"></i>DATOS DEL CONTRIBUYENTE
                                        </h6>
                                        <div class="row text-dark">
                                            <div class="col-md-8">
                                                <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.65rem;">Nombre / Razón Social</small>
                                                <span class="h5 font-weight-bold mb-0">{{ $selectedRegistro->nombre ?: 'SIN DATOS' }}</span>
                                            </div>
                                            <div class="col-md-4">
                                                <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.65rem;">Cédula / RUT</small>
                                                <span class="h5 font-weight-bold mb-0">{{ $selectedRegistro->cedula ?: 'SIN DATOS' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Desglose de Ítems -->
                        <h6 class="text-info font-weight-bold mb-2 mt-2"><i class="fas fa-list-ul mr-2"></i>DESGLOSE DE CONCEPTOS</h6>
                        <div class="table-responsive mb-4 rounded border shadow-sm">
                            <table class="table table-sm table-hover mb-0 text-dark">
                                <thead class="bg-primary text-white small">
                                    <tr>
                                        <th class="pl-3 py-2">DETALLE</th>
                                        <th class="py-2">DESCRIPCIÓN</th>
                                        <th class="text-right pr-3 py-2">IMPORTE</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($selectedRegistro->items as $item)
                                    <tr>
                                        <td class="pl-3 align-middle font-weight-bold">{{ $item->detalle }}</td>
                                        <td class="align-middle text-muted small">{{ $item->descripcion }}</td>
                                        <td class="text-right pr-3 align-middle font-weight-bold text-nowrap text-success">
                                            $ {{ number_format($item->importe, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="row">
                            <!-- Otros Datos y Domicilio -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6 class="text-info font-weight-bold mb-3"><i class="fas fa-map-marker-alt mr-2"></i>DOMICILIO Y PAGO</h6>
                                    <div class="card border-0 bg-light p-3">
                                        <div class="mb-3">
                                            <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.65rem;">Dirección / Domicilio</small>
                                            <span class="text-dark font-weight-bold">{{ $selectedRegistro->domicilio ?: 'NO REGISTRADO' }}</span>
                                        </div>
                                        <div class="mb-3">
                                            <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.65rem;">Medio de Pago</small>
                                            <span class="badge badge-info p-2 px-3 text-uppercase font-weight-bold" style="font-size: 0.75rem;">{{ $selectedRegistro->forma_pago ?: 'SIN DATOS' }}</span>
                                        </div>
                                        @if($selectedRegistro->adicional)
                                        <div>
                                            <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.65rem;">Otros Datos (Tel / Período)</small>
                                            <span class="text-dark small font-weight-bold">{{ $selectedRegistro->adicional }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Adenda -->
                            <div class="col-md-6">
                                <h6 class="text-info font-weight-bold mb-3"><i class="fas fa-sticky-note mr-2"></i>ADENDA / OBSERVACIONES</h6>
                                <div class="bg-white p-3 rounded border text-dark h-100 shadow-sm" style="min-height: 120px; white-space: pre-wrap; font-size: 0.9rem;">{{ $selectedRegistro->adenda ?: 'Sin observaciones registradas.' }}</div>
                            </div>
                        </div>

                        <!-- Metadata de Registro -->
                        <div class="mt-4 pt-3 border-top d-flex flex-column flex-sm-row justify-content-between text-muted small px-1 border-light">
                            <div class="mb-2 mb-sm-0">
                                <i class="fas fa-user-edit mr-1"></i> Registrado por: <span class="font-weight-bold">{{ $selectedRegistro->creator->nombre }} {{ $selectedRegistro->creator->apellido }}</span>
                            </div>
                            <div>
                                <i class="fas fa-clock mr-1 text-info"></i> Sistema: <span class="font-weight-bold">{{ $selectedRegistro->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm px-4 shadow-sm" @click="closeDetail()">
                        <i class="fas fa-times mr-1"></i> Cerrar
                    </button>
                    <button type="button" class="btn btn-info btn-sm px-4 shadow-sm d-print-none" onclick="window.print()">
                        <i class="fas fa-print mr-1"></i> Imprimir Página
                    </button>
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
</div>