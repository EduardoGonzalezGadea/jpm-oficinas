<div>
<div class="container-fluid px-0">
    <style>
        .btn-action-fixed {
            width: 30px;
            padding-left: 0;
            padding-right: 0;
        }
        .text-small-custom {
            font-size: 0.8rem;
        }
        .upload-loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            display: none !important;
            align-items: center;
            justify-content: center;
            z-index: 20;
            border-radius: 8px;
        }
        .item-distribucion-group {
            outline: 3px solid #1a73e8;
            outline-offset: -1px;
        }
        .item-distribucion-group + .item-distribucion-group {
            outline: 3px solid #1a73e8;
            outline-offset: -1px;
        }
        .modal-full-width {
            max-width: 95vw;
        }
    </style>
    @section('title', 'Gestión de CFEs')

    <div class="card">
        <div class="card-header bg-info text-white card-header-gradient p-2">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="card-title px-1 m-0">
                    <strong><i class="fas fa-file-invoice mr-2"></i>Gestión de CFEs</strong>
                </h4>
                <div class="d-flex align-items-center">
                    <div wire:loading wire:target="archivoPdf" class="mr-3 text-white font-weight-bold small">
                        <i class="fas fa-spinner fa-spin mr-1"></i> CARGANDO
                    </div>
                    <a href="{{ route('tesoreria.gestion-cfe.estados-recaudacion') }}" class="btn btn-warning mb-0 mr-2">
                        <i class="fas fa-chart-line mr-1"></i> Est. Recaudación
                    </a>
                    <a href="{{ route('tesoreria.gestion-cfe.recaudaciones') }}" class="btn btn-info mb-0 mr-2">
                        <i class="fas fa-hand-holding-usd mr-1"></i> Recaudaciones
                    </a>
                    <button type="button" class="btn btn-success mb-0 mr-2"
                        wire:click="nuevoCfe">
                        <i class="fas fa-plus-circle mr-1"></i> Nuevo
                    </button>
                    <label for="archivoPdfInput" class="btn btn-primary mb-0 cursor-pointer"
                        wire:loading.attr="disabled" wire:target="archivoPdf">
                        <i class="fas fa-file-upload mr-1"></i> Cargar CFE
                    </label>
                    <input type="file" id="archivoPdfInput" wire:model="archivoPdf" class="d-none"
                        accept="application/pdf">
                </div>
            </div>
        </div>

        <div class="card-body px-2 pt-1 position-relative">
            <div wire:loading.style="display: flex" wire:target="archivoPdf,confirmarCarga" class="upload-loading-overlay">
                <div class="text-white font-weight-bold h4 mb-0">
                    <i class="fas fa-spinner fa-spin mr-2"></i> CARGANDO
                </div>
            </div>
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
                        <option value="0">— Todos los años —</option>
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
                            <th class="align-middle">Nro. Doc.</th>
                            <th class="align-middle">Receptor</th>
                            <th class="align-middle">Doc. Receptor</th>
                            <th class="align-middle">Fecha</th>
                            <th class="align-middle">Total a Pagar</th>
                            <th class="align-middle">Concepto / ER</th>
                            <th class="align-middle text-center d-print-none">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        @forelse($cfes as $cfe)
                            @php $simbolo = $cfe->moneda === 'UYU' ? '$' : $cfe->moneda; @endphp
                            <tr>
                                <td class="align-middle">
                                    <strong>{{ $cfe->documento_serie }}-{{ $cfe->documento_numero }}</strong>
                                    <span class="text-muted d-block text-small-custom">{{ $cfe->documento_tipo }}</span>
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
                                        @php
                                            $erNumeros = $cfe->items
                                                ->pluck('planillaEr.numero')
                                                ->filter()
                                                ->unique()
                                                ->values();
                                        @endphp
                                        @if($erNumeros->isNotEmpty())
                                            <span class="text-muted d-block text-small-custom">E/R {{ $erNumeros->map(fn($n) => "N°{$n}")->implode(', ') }}</span>
                                        @endif
                                    @else
                                        <span class="badge badge-warning">Sin asignar</span>
                                    @endif
                                </td>
                                <td class="align-middle text-center d-print-none text-nowrap">
                                    <div class="btn-group btn-group-sm">
                                    <button class="btn btn-info btn-action-fixed" title="Ver Detalles" data-toggle="modal"
                                        data-target="#modalCfe{{ $cfe->id }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-warning btn-action-fixed {{ $cfe->items_en_planilla_count ? 'd-none' : '' }}"
                                        title="Editar"
                                        wire:click="editarCfe({{ $cfe->id }})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-action-fixed {{ $cfe->items_en_planilla_count ? 'd-none' : '' }}"
                                        title="Eliminar"
                                        onclick="confirmDeleteCfe({{ $cfe->id }})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    </div>
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
    @include('livewire.tesoreria.gestion-cfe._modal-confirmacion-carga')

    {{-- =================== MODALES DE DETALLE =================== --}}
    @foreach($cfes as $cfe)
        @include('livewire.tesoreria.gestion-cfe._modal-detalle')
    @endforeach

    {{-- =================== MODAL DE EDICIÓN =================== --}}
    @include('livewire.tesoreria.gestion-cfe._modal-editar')

    {{-- =================== MODAL DE NUEVO CFE =================== --}}
    @include('livewire.tesoreria.gestion-cfe._modal-nuevo')

</div>
</div>

@include('livewire.tesoreria.gestion-cfe._scripts')
