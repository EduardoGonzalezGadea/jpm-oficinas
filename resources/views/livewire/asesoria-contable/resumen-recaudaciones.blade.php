<div class="container-fluid px-0">
    <style>
        .btn-action-fixed {
            width: 30px;
            padding-left: 0;
            padding-right: 0;
        }

        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
        }

        .nav-tabs .nav-link {
            border: 2px solid transparent;
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
            margin-bottom: -2px;
            font-weight: 500;
            transition: all 0.2s ease;
            color: #495057;
        }

        .nav-tabs .nav-link:hover {
            border-color: #e9ecef #e9ecef #dee2e6;
            background-color: #f8f9fa;
        }

        .nav-tabs .nav-link.active {
            border-left: 2px solid #adb5bd;
            border-right: 2px solid #adb5bd;
            border-top: 3px solid #17a2b8;
            border-bottom-color: #fff;
            background-color: #fff;
            font-weight: 600;
            color: #495057;
        }

        html.dark-theme .nav-tabs {
            border-bottom-color: rgba(255, 255, 255, 0.15);
        }

        html.dark-theme .nav-tabs .nav-link {
            color: rgba(255, 255, 255, 0.65);
        }

        html.dark-theme .nav-tabs .nav-link:hover {
            border-color: rgba(255, 255, 255, 0.1) rgba(255, 255, 255, 0.1) rgba(255, 255, 255, 0.15);
            background-color: rgba(255, 255, 255, 0.05);
            color: #fff;
        }

        html.dark-theme .nav-tabs .nav-link.active {
            border-left-color: rgba(255, 255, 255, 0.2);
            border-right-color: rgba(255, 255, 255, 0.2);
            border-top-color: #5bc0de;
            border-bottom-color: transparent;
            background-color: transparent;
            color: #fff;
        }

        html.dark-theme tfoot tr {
            background: rgba(255, 255, 255, 0.08) !important;
            color: #fff;
        }

    </style>
    @section('title', 'Resumen de Recaudaciones')

    <div class="card">
        <div class="card-header bg-info text-white card-header-gradient p-2">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="card-title px-1 m-0">
                    <strong><i class="fas fa-chart-pie mr-2"></i>Resumen de Recaudaciones</strong>
                </h4>
            </div>
        </div>

        <div class="card-body px-2 pt-1">
            {{-- Pestañas por tipo de distribución SIIF --}}
            <ul class="nav nav-tabs mb-0" id="siifTabs" role="tablist">
                @forelse($tiposDistribucion as $tipo)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $tabActivo == $tipo->id ? 'active' : '' }}"
                           href="#"
                           wire:click.prevent="cambiarTab({{ $tipo->id }})"
                           role="tab">
                            {{ $tipo->tipo }}
                        </a>
                    </li>
                @empty
                    <li class="nav-item">
                        <span class="nav-link text-muted">No hay tipos de distribución SIIF</span>
                    </li>
                @endforelse
            </ul>
            <hr class="mt-0 mb-3" style="border-top: 1px solid #adb5bd; margin-top: 0 !important;">

            @if($tabActivo)
                {{-- Barra de filtros --}}
                <div class="d-flex mb-2 align-items-center">
                    <div class="flex-grow-1 mr-2" style="min-width: 200px;">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" wire:model.debounce.300ms="search" class="form-control"
                                placeholder="Buscar por N° documento o receptor...">
                            <div class="input-group-append">
                                <button class="btn btn-outline-dark" wire:click="resetearBusqueda" title="Resetear búsqueda">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </div>
                        </div>
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
                </div>

                {{-- Tabla de CFEs agrupados por fecha --}}
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-striped table-hover mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th class="align-middle">Fecha</th>
                                <th class="align-middle">Documento</th>
                                <th class="align-middle">Receptor</th>
                                <th class="align-middle">Dependencia</th>
                                <th class="align-middle text-right">Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($grupos as $grupo)
                                @php
                                    $tooltipGrupo = $grupo->mediosPago->map(fn($val, $tipo) => strtoupper($tipo) . ': $ ' . number_format($val, 2, ',', '.'))->implode(' | ');
                                @endphp
                                {{-- Fila de subtotal por día --}}
                                <tr class="table-info font-weight-bold">
                                    <td class="align-middle" colspan="4">
                                        <i class="fas fa-calendar-day mr-1"></i>{{ \Carbon\Carbon::parse($grupo->fecha)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}
                                    </td>
                                    <td class="align-middle text-right text-nowrap">
                                        $ {{ number_format($grupo->subtotal, 2, ',', '.') }}
                                        @if($grupo->mediosPago->isNotEmpty())
                                            <i class="fas fa-info-circle text-info ml-1" title="{{ $tooltipGrupo }}"></i>
                                        @endif
                                    </td>
                                </tr>
                                {{-- Sub-grupos por concepto de caja --}}
                                @foreach($grupo->conceptos as $concepto)
                                    @php
                                        $tooltipConcepto = $concepto->mediosPago->map(fn($val, $tipo) => strtoupper($tipo) . ': $ ' . number_format($val, 2, ',', '.'))->implode(' | ');
                                    @endphp
                                    <tr class="bg-light font-italic">
                                        <td class="small align-middle" colspan="4">
                                            <i class="fas fa-tag mr-1 text-secondary"></i>{{ $concepto->concepto }}
                                        </td>
                                        <td class="small align-middle text-right text-nowrap">
                                            $ {{ number_format($concepto->subtotal, 2, ',', '.') }}
                                            @if($concepto->mediosPago->isNotEmpty())
                                                <i class="fas fa-info-circle text-info ml-1" title="{{ $tooltipConcepto }}"></i>
                                            @endif
                                        </td>
                                    </tr>
                                    {{-- Items del concepto --}}
                                    @foreach($concepto->items as $cfe)
                                        @php
                                            $tooltipCfe = $cfe->mediosPago->map(fn($mp) => strtoupper($mp->medio_pago_tipo) . ': $ ' . number_format($mp->medio_pago_valor, 2, ',', '.'))->implode(' | ');
                                        @endphp
                                        <tr>
                                            <td class="small align-middle text-nowrap">{{ $cfe->fecha?->format('d/m/Y') ?? '—' }}</td>
                                            <td class="small align-middle">
                                                {{ $cfe->documento_tipo }} {{ $cfe->documento_serie }}-{{ $cfe->documento_numero }}
                                            </td>
                                            <td class="small align-middle">{{ $cfe->receptor_nombre_denominacion ?? '—' }}</td>
                                            <td class="small align-middle">{{ $cfe->siifDistribucionDependencia?->dependencia ?? '—' }}</td>
                                            <td class="small align-middle text-right text-nowrap">
                                                $ {{ number_format($cfe->total_a_pagar, 2, ',', '.') }}
                                                @if($cfe->mediosPago->isNotEmpty())
                                                    <i class="fas fa-info-circle text-info ml-1" title="{{ $tooltipCfe }}"></i>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted">
                                        <i class="fas fa-info-circle mr-1"></i> No se encontraron CFE.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            @php
                                $tooltipTotal = $totalGeneralPorMedioPago->map(fn($val, $tipo) => strtoupper($tipo) . ': $ ' . number_format($val, 2, ',', '.'))->implode(' | ');
                            @endphp
                            <tr class="font-weight-bold bg-light">
                                <td colspan="4" class="text-right align-middle">
                                    <i class="fas fa-chart-line mr-1"></i>TOTAL GENERAL
                                </td>
                                <td class="text-right text-nowrap align-middle">
                                    $ {{ number_format($totalGeneral, 2, ',', '.') }}
                                    @if($totalGeneralPorMedioPago->isNotEmpty())
                                        <i class="fas fa-info-circle text-info ml-1" title="{{ $tooltipTotal }}"></i>
                                    @endif
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <p class="text-muted">Seleccione un tipo de distribución SIIF para visualizar los CFE.</p>
            @endif
        </div>
    </div>
</div>

