<div class="container-fluid px-0">
    @section('title', 'Gestión de CFEs')

    <div class="card">
        <div class="card-header bg-info text-white card-header-gradient p-2">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="card-title px-1 m-0">
                    <strong><i class="fas fa-file-invoice mr-2"></i>Gestión de CFEs</strong>
                </h4>
                <div class="d-flex align-items-center">
                    <div wire:loading wire:target="archivoPdf" class="mr-3 text-white font-weight-bold small">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Procesando...
                    </div>
                    <label for="archivoPdfInput" class="btn btn-primary mb-0 cursor-pointer"
                        wire:loading.attr="disabled" wire:target="archivoPdf">
                        <i class="fas fa-file-upload mr-1"></i> Cargar CFE
                    </label>
                    <input type="file" id="archivoPdfInput" wire:model="archivoPdf" class="d-none"
                        accept="application/pdf">
                </div>
            </div>
        </div>

        <div class="card-body px-2 pt-1">
            {{-- Barra de filtros --}}
            <div class="d-flex mb-2 align-items-center">
                <div class="flex-grow-1 mr-2" style="max-width: 40%;">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" wire:model.debounce.300ms="search" class="form-control"
                            placeholder="Buscar por número, receptor o RUC...">
                    </div>
                </div>
                <div class="mr-2" style="width: 230px;">
                    <select wire:model="filtroConcepto" class="form-control">
                        <option value="">— Filtrar por concepto —</option>
                        @foreach($cajaConceptos as $concepto)
                            <option value="{{ $concepto->id }}">{{ $concepto->caja_concepto }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="dropdown mr-2" style="width: 200px;" id="dropdownMesesWrapper" wire:ignore.self>
                    <button class="btn btn-white border form-control dropdown-toggle text-left d-flex justify-content-between align-items-center" type="button" id="dropdownMeses" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="text-truncate">
                            @if(empty($filtroMeses))
                                — Todos los meses —
                            @else
                                {{ count($filtroMeses) }} {{ count($filtroMeses) === 1 ? 'mes' : 'meses' }}
                            @endif
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right p-3" aria-labelledby="dropdownMeses" style="min-width: 240px; max-height: 350px; overflow-y: auto;" onclick="event.stopPropagation()" wire:ignore.self>
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <span class="font-weight-bold small text-secondary">Meses del año</span>
                            <a href="#" wire:click.prevent="limpiarFiltroMeses" class="small font-weight-bold text-danger">
                                Limpiar
                            </a>
                        </div>
                        @php
                            $mesesNombres = [
                                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                            ];
                        @endphp
                        @foreach($mesesNombres as $num => $nombre)
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" id="mes_{{ $num }}" value="{{ $num }}" wire:model="filtroMeses" class="custom-control-input">
                                <label for="mes_{{ $num }}" class="custom-control-label small cursor-pointer w-100">{{ $nombre }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="mr-2" style="width: 170px;">
                    <select wire:model="filtroAno" class="form-control">
                        <option value="">— Todos los años —</option>
                        @foreach($anosRegistrados as $ano)
                            <option value="{{ $ano }}">{{ $ano }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="text-nowrap ml-auto">
                    <small class="font-weight-bold text-secondary">{{ $cfes->total() }} registros</small>
                </div>
            </div>

            {{-- Tabla principal --}}
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped table-hover">
                    <thead class="thead-dark align-middle">
                        <tr>
                            <th class="align-middle">Nro. Documento</th>
                            <th class="align-middle">Tipo</th>
                            <th class="align-middle">Receptor</th>
                            <th class="align-middle">Doc. Receptor</th>
                            <th class="align-middle">Fecha</th>
                            <th class="align-middle text-right">Total a Pagar</th>
                            <th class="align-middle">Concepto</th>
                            <th class="align-middle text-center d-print-none">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        @forelse($cfes as $cfe)
                            @php $simbolo = $cfe->moneda === 'UYU' ? '$' : $cfe->moneda; @endphp
                            <tr>
                                <td class="align-middle">
                                    <strong>{{ $cfe->documento_serie }}-{{ $cfe->documento_numero }}</strong>
                                </td>
                                <td class="align-middle">
                                    <span class="badge badge-info">{{ $cfe->documento_tipo }}</span>
                                </td>
                                <td class="align-middle">
                                    {{ $cfe->receptor_nombre_denominacion ?: '—' }}
                                </td>
                                <td class="align-middle">
                                    {{ $cfe->receptor_documento_ruc ?: '—' }}
                                </td>
                                <td class="align-middle">
                                    {{ $cfe->fecha ? $cfe->fecha->format('d/m/Y') : 'N/A' }}
                                </td>
                                <td class="align-middle text-right font-weight-bold text-nowrap">
                                    {{ $simbolo }} {{ number_format($cfe->total_a_pagar, 2, ',', '.') }}
                                </td>
                                <td class="align-middle">
                                    @if($cfe->cajaConcepto)
                                        <span class="badge badge-success">{{ $cfe->cajaConcepto->caja_concepto }}</span>
                                    @else
                                        <span class="badge badge-warning">Sin asignar</span>
                                    @endif
                                </td>
                                <td class="align-middle text-center d-print-none">
                                    <button class="btn btn-sm btn-secondary mr-1" title="Ver Detalles" data-toggle="modal"
                                        data-target="#modalCfe{{ $cfe->id }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                <button class="btn btn-sm btn-danger" title="Eliminar"
                                    onclick="confirmDeleteCfe({{ $cfe->id }})">
                                    <i class="fas fa-trash"></i>
                                </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-3">
                                    No hay CFEs registrados. Utilizá el botón <strong>Cargar CFE</strong> para cargar uno.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 d-flex justify-content-center d-print-none">
                {{ $cfes->links() }}
            </div>
        </div>
    </div>


    {{-- =================== MODAL DE CONFIRMACIÓN DE CARGA =================== --}}
    <div class="modal fade" id="modalConfirmacionCfe" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content border-0 shadow">

                <div class="modal-header bg-primary text-white p-2">
                    <h5 class="modal-title m-0">
                        <i class="fas fa-file-invoice mr-2"></i>
                        Confirmar carga de CFE
                        @if(!empty($datosExtraidos['documento_tipo']))
                            &mdash; <strong>{{ $datosExtraidos['documento_tipo'] }}</strong>
                        @endif
                        @if(!empty($datosExtraidos['documento_serie']) || !empty($datosExtraidos['documento_numero']))
                            Serie {{ $datosExtraidos['documento_serie'] ?? '' }} Nº
                            {{ $datosExtraidos['documento_numero'] ?? '' }}
                        @endif
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"
                        wire:click="cancelarCarga">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body p-3">

                    @if(!empty($datosExtraidos))

                        {{-- Fila de datos principales --}}
                        <div class="row mb-3">
                            {{-- Datos del Documento --}}
                            <div class="col-md-4">
                                <div class="card card-body py-2 px-3 h-100">
                                    <h6 class="text-uppercase small font-weight-bold mb-2">
                                        <i class="fas fa-file-alt mr-1 text-info"></i> Documento
                                    </h6>
                                    <p class="mb-1 small"><strong>Archivo:</strong> {{ $nombreArchivoOriginal }}</p>
                                    <p class="mb-1 small"><strong>Fecha:</strong>
                                        {{ !empty($datosExtraidos['fecha']) ? \Carbon\Carbon::parse($datosExtraidos['fecha'])->format('d/m/Y') : 'N/A' }}
                                    </p>
                                    <p class="mb-1 small"><strong>Moneda:</strong>
                                        {{ ($datosExtraidos['moneda'] ?? 'UYU') === 'UYU' ? '$' : ($datosExtraidos['moneda'] ?? 'UYU') }}
                                    </p>
                                    <p class="mb-1 small"><strong>Forma de Pago:</strong>
                                        {{ $datosExtraidos['forma_pago'] ?? 'N/A' }}</p>
                                    @if(!empty($datosExtraidos['periodo']))
                                        <p class="mb-0 small"><strong>Período:</strong> {{ $datosExtraidos['periodo'] }}</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Datos del Emisor --}}
                            <div class="col-md-4">
                                <div class="card card-body py-2 px-3 h-100">
                                    <h6 class="text-uppercase small font-weight-bold mb-2">
                                        <i class="fas fa-building mr-1 text-info"></i> Emisor
                                    </h6>
                                    <p class="mb-1 small"><strong>Nombre:</strong>
                                        {{ $datosExtraidos['emisor_nombre'] ?? '—' }}</p>
                                    <p class="mb-1 small"><strong>RUC:</strong> {{ $datosExtraidos['emisor_ruc'] ?? '—' }}
                                    </p>
                                    @if(!empty($datosExtraidos['emisor_direccion']))
                                        <p class="mb-0 small">
                                            {{ $datosExtraidos['emisor_direccion'] }}{{ !empty($datosExtraidos['emisor_localidad']) ? ', ' . $datosExtraidos['emisor_localidad'] : '' }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            {{-- Datos del Receptor --}}
                            <div class="col-md-4">
                                <div class="card card-body py-2 px-3 h-100">
                                    <h6 class="text-uppercase small font-weight-bold mb-2">
                                        <i class="fas fa-user mr-1 text-info"></i> Receptor
                                    </h6>
                                    <p class="mb-1 small"><strong>Nombre:</strong>
                                        {{ $datosExtraidos['receptor_nombre_denominacion'] ?? 'Consumidor Final' }}</p>
                                    <p class="mb-1 small"><strong>RUC/CI:</strong>
                                        {{ $datosExtraidos['receptor_documento_ruc'] ?? '—' }}</p>
                                    @if(!empty($datosExtraidos['receptor_domicilio_fiscal']))
                                        <p class="mb-0 small">{{ $datosExtraidos['receptor_domicilio_fiscal'] }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- ===== SELECTORES DE CONCEPTO Y SIIF ===== --}}
                        <div class="border-top pt-3 mt-2 mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-uppercase small font-weight-bold mb-2">
                                        <i class="fas fa-tag mr-1 text-primary"></i> Concepto de Caja
                                    </h6>
                                    <div class="form-group mb-0">
                                        <select wire:model="cajaConceptoSeleccionado" id="selectorCajaConcepto"
                                            class="form-control @error('cajaConceptoSeleccionado') is-invalid @enderror">
                                            <option value="">— Seleccione concepto —</option>
                                            @foreach($cajaConceptos as $concepto)
                                                <option value="{{ $concepto->id }}">{{ $concepto->caja_concepto }}</option>
                                            @endforeach
                                        </select>
                                        @error('cajaConceptoSeleccionado')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Concepto al que corresponde el CFE.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-uppercase small font-weight-bold mb-2">
                                        <i class="fas fa-sitemap mr-1 text-primary"></i> Dependencia
                                    </h6>
                                    <div class="form-group mb-0">
                                        <select wire:model="siifDependenciaSeleccionado" id="selectorSiifDependencia"
                                            class="form-control @error('siifDependenciaSeleccionado') is-invalid @enderror">
                                            <option value="">— Seleccione dep. SIIF —</option>
                                            @foreach($siifDependencias as $dep)
                                                <option value="{{ $dep->id }}">{{ $dep->abreviatura }} - {{ $dep->dependencia }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('siifDependenciaSeleccionado')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Dependencia asignada.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tabla de Ítems --}}
                        <h6 class="text-uppercase small font-weight-bold mb-1 border-bottom pb-1">
                            <i class="fas fa-list mr-1"></i> Ítems
                        </h6>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered table-striped">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Detalle</th>
                                        <th class="text-center" style="width:10%">Cant.</th>
                                        <th class="text-right" style="width:18%">Precio</th>
                                        <th class="text-right" style="width:18%">Importe</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($datosExtraidos['items'] ?? [] as $index => $item)
                                        <tr>
                                            <td>
                                                {{ $item['detalle'] ?? '' }}
                                                @if(!empty($item['descripcion']))
                                                    <br><small>{{ $item['descripcion'] }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center align-middle">
                                                {{ number_format($item['cantidad'] ?? 1, 2, ',', '.') }}</td>
                                            <td class="text-right align-middle">
                                                {{ number_format($item['precio'] ?? 0, 2, ',', '.') }}</td>
                                            <td class="text-right align-middle font-weight-bold">
                                                {{ number_format($item['importe'] ?? 0, 2, ',', '.') }}</td>
                                        </tr>
                                        <tr class="table-secondary">
                                            <td colspan="4" class="py-1 px-3">
                                                <div class="d-flex align-items-center">
                                                    <span class="mr-2 font-weight-bold small">
                                                        <i class="fas fa-sitemap mr-1 text-primary"></i> Distribución SIIF:
                                                    </span>
                                                    <div class="flex-grow-1" style="max-width: 400px;">
                                                        @if($cajaConceptoSeleccionado && $siifDependenciaSeleccionado)
                                                            <select wire:model="itemDistribuciones.{{ $index }}"
                                                                class="form-control form-control-sm @error('itemDistribuciones.'.$index) is-invalid @enderror">
                                                                <option value="">— Sin asignar —</option>
                                                                @foreach($distribuciones as $dist)
                                                                    <option value="{{ $dist->id }}">
                                                                        {{ $dist->concepto }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            @error('itemDistribuciones.'.$index)
                                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                                            @enderror
                                                        @else
                                                            <span class="small text-info">
                                                                <i class="fas fa-info-circle mr-1"></i> Seleccione concepto y dependencia en la cabecera
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Medios de Pago + Total --}}
                        <div class="row align-items-center mb-3">
                            <div class="col-md-7">
                                <h6 class="text-uppercase small font-weight-bold mb-1 border-bottom pb-1">
                                    <i class="fas fa-money-bill-wave mr-1"></i> Medios de Pago
                                </h6>
                                <ul class="list-unstyled mb-0 small">
                                    @forelse($datosExtraidos['medios_pago'] ?? [] as $mp)
                                        <li>
                                            <i class="fas fa-check-circle text-success mr-1"></i>
                                            {{ $mp['tipo'] ?: 'Medio de pago' }}:
                                            <strong>
                                                {{ ($datosExtraidos['moneda'] ?? 'UYU') === 'UYU' ? '$' : ($datosExtraidos['moneda'] ?? '') }}
                                                {{ number_format($mp['valor'] ?? 0, 2, ',', '.') }}
                                            </strong>
                                        </li>
                                    @empty
                                        <li>No se extrajeron medios de pago explícitos.</li>
                                    @endforelse
                                </ul>
                            </div>
                            <div class="col-md-5 text-right">
                                <p class="small text-uppercase font-weight-bold mb-0">Total a Pagar</p>
                                <h3 class="font-weight-bold mb-0 text-nowrap {{ ($datosExtraidos['total_a_pagar'] ?? 0) < 0 ? 'text-danger' : 'text-info' }}"
                                    style="letter-spacing:-1px">
                                    {{ ($datosExtraidos['moneda'] ?? 'UYU') === 'UYU' ? '$' : ($datosExtraidos['moneda'] ?? '') }}
                                    {{ number_format($datosExtraidos['total_a_pagar'] ?? 0, 2, ',', '.') }}
                                </h3>
                            </div>
                        </div>

                        {{-- Referencias y Adenda --}}
                        @if(!empty($datosExtraidos['referencias']) || !empty($datosExtraidos['adenda']))
                            <div class="row mb-3">
                                @if(!empty($datosExtraidos['referencias']))
                                    <div class="col-{{ !empty($datosExtraidos['adenda']) ? 'md-6' : '12' }}">
                                        <h6 class="text-uppercase small font-weight-bold mb-1 border-bottom pb-1">
                                            <i class="fas fa-link mr-1"></i> Referencias
                                        </h6>
                                        <p class="small bg-light p-2 rounded text-wrap text-break mb-0">
                                            {{ $datosExtraidos['referencias'] }}</p>
                                    </div>
                                @endif
                                @if(!empty($datosExtraidos['adenda']))
                                    <div class="col-{{ !empty($datosExtraidos['referencias']) ? 'md-6' : '12' }}">
                                        <h6 class="text-uppercase small font-weight-bold mb-1 border-bottom pb-1">
                                            <i class="fas fa-sticky-note mr-1"></i> Adenda
                                        </h6>
                                        <p class="small bg-light p-2 rounded text-wrap text-break mb-0">
                                            {{ $datosExtraidos['adenda'] }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endif {{-- /!empty($datosExtraidos) --}}

                </div>{{-- /modal-body --}}

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" wire:click="cancelarCarga">
                        <i class="fas fa-times mr-1"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="confirmarCarga"
                        wire:loading.attr="disabled" wire:target="confirmarCarga">
                        <span wire:loading.remove wire:target="confirmarCarga">
                            <i class="fas fa-save mr-1"></i> Confirmar y Guardar
                        </span>
                        <span wire:loading wire:target="confirmarCarga">
                            <i class="fas fa-spinner fa-spin mr-1"></i> Guardando...
                        </span>
                    </button>
                </div>

            </div>
        </div>
    </div>


    {{-- =================== MODALES DE DETALLE =================== --}}
    @foreach($cfes as $cfe)
        @php $simbolo = $cfe->moneda === 'UYU' ? '$' : $cfe->moneda; @endphp
        <div class="modal fade" id="modalCfe{{ $cfe->id }}" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content border-0 shadow">

                    {{-- Cabecera del modal --}}
                    <div class="modal-header bg-info text-white p-2">
                        <h5 class="modal-title m-0">
                            <i class="fas fa-file-invoice mr-2"></i>
                            <strong>{{ $cfe->documento_tipo }}</strong>
                            &mdash; Serie {{ $cfe->documento_serie }} Nº {{ $cfe->documento_numero }}
                            @if($cfe->comprobante_tipo)
                                <span class="badge badge-light ml-2">{{ $cfe->comprobante_tipo }}</span>
                            @endif
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body p-3">

                        {{-- Fila de datos principales --}}
                        <div class="row mb-3">
                            {{-- Datos del Documento --}}
                            <div class="col-md-4">
                                <div class="card card-body py-2 px-3 h-100">
                                    <h6 class="text-uppercase small font-weight-bold mb-2">
                                        <i class="fas fa-file-alt mr-1 text-info"></i> Documento
                                    </h6>
                                    <p class="mb-1 small"><strong>Fecha:</strong>
                                        {{ $cfe->fecha ? $cfe->fecha->format('d/m/Y') : 'N/A' }}</p>
                                    <p class="mb-1 small"><strong>Moneda:</strong> {{ $simbolo }}</p>
                                    <p class="mb-1 small"><strong>Forma de Pago:</strong> {{ $cfe->forma_pago ?: 'N/A' }}
                                    </p>
                                    @if($cfe->periodo)
                                        <p class="mb-0 small"><strong>Período:</strong> {{ $cfe->periodo }}</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Datos del Emisor --}}
                            <div class="col-md-4">
                                <div class="card card-body py-2 px-3 h-100">
                                    <h6 class="text-uppercase small font-weight-bold mb-2">
                                        <i class="fas fa-building mr-1 text-info"></i> Emisor
                                    </h6>
                                    <p class="mb-1 small"><strong>Nombre:</strong> {{ $cfe->emisor_nombre }}</p>
                                    <p class="mb-1 small"><strong>RUC:</strong> {{ $cfe->emisor_ruc }}</p>
                                    @if($cfe->emisor_direccion)
                                        <p class="mb-0 small">
                                            {{ $cfe->emisor_direccion }}{{ $cfe->emisor_localidad ? ', ' . $cfe->emisor_localidad : '' }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            {{-- Datos del Receptor --}}
                            <div class="col-md-4">
                                <div class="card card-body py-2 px-3 h-100">
                                    <h6 class="text-uppercase small font-weight-bold mb-2">
                                        <i class="fas fa-user mr-1 text-info"></i> Receptor
                                    </h6>
                                    <p class="mb-1 small"><strong>Nombre:</strong>
                                        {{ $cfe->receptor_nombre_denominacion ?: 'Consumidor Final' }}</p>
                                    <p class="mb-1 small"><strong>RUC/CI:</strong> {{ $cfe->receptor_documento_ruc ?: '—' }}
                                    </p>
                                    @if($cfe->receptor_domicilio_fiscal)
                                        <p class="mb-0 small">{{ $cfe->receptor_domicilio_fiscal }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Tabla de Ítems --}}
                        <h6 class="text-uppercase small font-weight-bold mb-1 border-bottom pb-1">
                            <i class="fas fa-list mr-1"></i> Ítems
                        </h6>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered table-striped">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Detalle</th>
                                        <th class="text-center" style="width:10%">Cant.</th>
                                        <th class="text-right" style="width:18%">Precio</th>
                                        <th class="text-right" style="width:18%">Importe</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cfe->items as $item)
                                        <tr>
                                            <td>
                                                {{ $item->detalle }}
                                                @if($item->descripcion)
                                                    <br><small>{{ $item->descripcion }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center align-middle">
                                                {{ number_format($item->cantidad, 2, ',', '.') }}</td>
                                            <td class="text-right align-middle">{{ number_format($item->precio, 2, ',', '.') }}
                                            </td>
                                            <td class="text-right align-middle font-weight-bold">
                                                {{ number_format($item->importe, 2, ',', '.') }}</td>
                                        </tr>
                                        <tr class="table-secondary">
                                            <td colspan="4" class="py-1 px-3">
                                                <span class="font-weight-bold small mr-2">
                                                    <i class="fas fa-sitemap mr-1 text-primary"></i> Distribución SIIF:
                                                </span>
                                                @if($item->siifDistribucion)
                                                    <span class="badge badge-info text-wrap">
                                                        {{ $item->siifDistribucion->concepto }}
                                                    </span>
                                                @else
                                                    <span class="small text-info">Sin asignar</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Medios de Pago + Total --}}
                        <div class="row align-items-center mb-3">
                            <div class="col-md-7">
                                <h6 class="text-uppercase small font-weight-bold mb-1 border-bottom pb-1">
                                    <i class="fas fa-money-bill-wave mr-1"></i> Medios de Pago
                                </h6>
                                <ul class="list-unstyled mb-0 small">
                                    @forelse($cfe->mediosPago as $mp)
                                        <li>
                                            <i class="fas fa-check-circle text-success mr-1"></i>
                                            {{ $mp->medio_pago_tipo ?: 'Medio de pago' }}:
                                            <strong>{{ $simbolo }}
                                                {{ number_format($mp->medio_pago_valor, 2, ',', '.') }}</strong>
                                        </li>
                                    @empty
                                        <li>No se extrajeron medios de pago explícitos.</li>
                                    @endforelse
                                </ul>
                            </div>
                            <div class="col-md-5 text-right">
                                <p class="small text-uppercase font-weight-bold mb-0">Total a Pagar</p>
                                <h3 class="font-weight-bold mb-0 text-nowrap {{ $cfe->total_a_pagar < 0 ? 'text-danger' : 'text-info' }}"
                                    style="letter-spacing:-1px">
                                    {{ $simbolo }} {{ number_format($cfe->total_a_pagar, 2, ',', '.') }}
                                </h3>
                            </div>
                        </div>

                        {{-- Referencias y Adenda --}}
                        @if($cfe->referencias || $cfe->adenda)
                            <div class="row mb-3">
                                @if($cfe->referencias)
                                    <div class="col-{{ $cfe->adenda ? 'md-6' : '12' }}">
                                        <h6 class="text-uppercase small font-weight-bold mb-1 border-bottom pb-1">
                                            <i class="fas fa-link mr-1"></i> Referencias
                                        </h6>
                                        <p class="small bg-light p-2 rounded text-wrap text-break mb-0">{{ $cfe->referencias }}</p>
                                    </div>
                                @endif
                                @if($cfe->adenda)
                                    <div class="col-{{ $cfe->referencias ? 'md-6' : '12' }}">
                                        <h6 class="text-uppercase small font-weight-bold mb-1 border-bottom pb-1">
                                            <i class="fas fa-sticky-note mr-1"></i> Adenda
                                        </h6>
                                        <p class="small bg-light p-2 rounded text-wrap text-break mb-0">{{ $cfe->adenda }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Concepto de Caja y Distribuciones SIIF --}}
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-uppercase small font-weight-bold mb-1 border-bottom pb-1">
                                    <i class="fas fa-tag mr-1"></i> Concepto de Caja
                                </h6>
                                @if($cfe->cajaConcepto)
                                    <span class="badge badge-success px-3 py-2" style="font-size:0.9rem">
                                        {{ $cfe->cajaConcepto->caja_concepto }}
                                    </span>
                                @else
                                    <span class="badge badge-warning px-3 py-2" style="font-size:0.9rem">Sin concepto
                                        asignado</span>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-uppercase small font-weight-bold mb-1 border-bottom pb-1">
                                    <i class="fas fa-sitemap mr-1"></i> Dependencia
                                </h6>
                                @if($cfe->siifDistribucionDependencia)
                                    <span class="badge badge-info px-3 py-2" style="font-size:0.9rem">
                                        {{ $cfe->siifDistribucionDependencia->abreviatura }} -
                                        {{ $cfe->siifDistribucionDependencia->dependencia }}
                                    </span>
                                @else
                                    <span class="badge badge-secondary px-3 py-2" style="font-size:0.9rem">Sin dep.
                                        asignada</span>
                                @endif
                            </div>
                        </div>

                    </div>{{-- /modal-body --}}

                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

</div>

@push('scripts')
    <script>
        function confirmDeleteCfe(id) {
            Swal.fire({
                title: '¿Está seguro?',
                text: 'Esta acción no se puede deshacer y eliminará el CFE seleccionado.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.emit('borrarCfe', id);
                }
            });
        }

        document.addEventListener('livewire:load', function () {
            // Evitar que el dropdown de meses se cierre al hacer click dentro del mismo
            $('#dropdownMesesWrapper').on('hide.bs.dropdown', function (e) {
                if (e.clickEvent && $(e.clickEvent.target).closest('.dropdown-menu').length) {
                    e.preventDefault();
                }
            });

            // Abrir modal de confirmación cuando Livewire lo indique
            window.addEventListener('abrir-modal-confirmacion-cfe', () => {
                $('#modalConfirmacionCfe').modal('show');
            });

            // Cerrar modal de confirmación cuando Livewire lo indique
            window.addEventListener('cerrar-modal-confirmacion-cfe', () => {
                $('#modalConfirmacionCfe').modal('hide');
            });

            // Cuando el modal se cierra manualmente (botón X o clic fuera), notificar a Livewire
            $('#modalConfirmacionCfe').on('hidden.bs.modal', function () {
                @this.call('cancelarCarga');
            });

            // Alerta si la referencia ya existe en otro documento
            window.addEventListener('swal:confirmar-guardar-referencia-duplicada', (event) => {
                const data = event.detail;
                Swal.fire({
                    title: 'Referencia Duplicada',
                    html: `La referencia al documento original <strong>${data.documentoReferencia}</strong> ya existe en el documento <strong>${data.documentoExistente}</strong>.<br><br>¿Desea grabar de todas formas o descartar la carga?`,
                    icon: 'warning',
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonColor: '#28a745',
                    denyButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Grabar de todas formas',
                    denyButtonText: 'Descartar carga',
                    cancelButtonText: 'Cancelar y revisar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        @this.call('confirmarCarga', true);
                    } else if (result.isDenied) {
                        @this.call('cancelarCarga');
                    }
                });
            });
        });
    </script>
@endpush