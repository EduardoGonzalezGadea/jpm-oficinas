<div class="container-fluid px-0">
    <style>
        .btn-action-fixed {
            width: 30px;
            padding-left: 0;
            padding-right: 0;
        }
        .modal-full-width {
            max-width: 95vw;
        }
    </style>
    @section('title', 'Planillas para Estados de Recaudación')

    <div class="card">
        <div class="card-header bg-info text-white card-header-gradient p-2">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="card-title px-1 m-0">
                    <strong><i class="fas fa-chart-bar mr-2"></i>Planillas para Estados de Recaudación</strong>
                </h4>
                <a href="{{ route('asesoria-contable.planillas-no-completadas') }}" class="btn btn-sm btn-warning">
                    <i class="fas fa-exclamation-triangle mr-1"></i>No Completadas
                </a>
            </div>
        </div>

        <div class="card-body px-2 pt-1">
            {{-- Barra de filtros --}}
            <div class="d-flex mb-2 align-items-center">
                <div class="flex-grow-1 mr-2" style="min-width: 200px;">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" wire:model.debounce.300ms="search" class="form-control"
                            placeholder="Buscar por planilla o N° documento CFE...">
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

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped table-hover">
                    <thead class="thead-dark align-middle">
                        <tr>
                            <th>Fecha</th>
                            <th>N°</th>
                            <th>Tipo</th>
                            <th>Dependencia</th>
                            <th>Turno</th>
                            <th class="text-right">Total</th>
                            <th class="text-center d-print-none">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        @forelse($grupos as $fechaKey => $grupo)
                            @foreach($grupo['planillas'] as $p)
                            <tr>
                                @if($loop->first)
                                <td class="align-middle font-weight-bold" rowspan="{{ count($grupo['planillas']) }}">{{ $grupo['fecha_display'] }}</td>
                                @endif
                                <td class="align-middle">{{ $p->numero }}</td>
                                <td class="align-middle">{{ $p->tipo->tipo ?? '—' }}</td>
                                <td class="align-middle">{{ $p->dependencia->dependencia ?? '—' }}</td>
                                <td class="align-middle">{{ $p->turno ?? '—' }}</td>
                                <td class="align-middle text-right text-nowrap">$ {{ number_format($totalesAjustados[$p->id] ?? 0, 2, ',', '.') }}</td>
                                <td class="align-middle text-center d-print-none text-nowrap">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button class="btn btn-info btn-action-fixed" title="Ver Detalles"
                                            wire:click="verDetalles({{ $p->id }})">
                                            <i class="fas fa-list"></i>
                                        </button>
                                        <button class="btn btn-secondary btn-action-fixed" title="Ver Planilla"
                                            wire:click="verPlanilla({{ $p->id }})">
                                            <i class="fas fa-file-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                            @if($grupo['mostrar_total'])
                            <tr class="bg-light font-weight-bold">
                                <td colspan="5" class="text-right align-middle">Total del {{ $grupo['fecha_display'] }}</td>
                                <td class="align-middle text-right text-nowrap">$ {{ number_format($grupo['total_dia'], 2, ',', '.') }}</td>
                                <td class="d-print-none"></td>
                            </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-3 text-muted">
                                    <i class="fas fa-info-circle mr-1"></i> No se encontraron planillas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-2">
                <small class="text-muted">
                    Mostrando {{ $planillas->firstItem() ?? 0 }} a {{ $planillas->lastItem() ?? 0 }}
                    de {{ $planillas->total() }} resultados
                </small>
                {{ $planillas->links() }}
            </div>
        </div>
    </div>

    {{-- Modal Detalles de Planilla --}}
    <div class="modal fade" id="modalDetallesPlanilla" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-full-width" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white p-2">
                    <h5 class="modal-title m-0">
                        <i class="fas fa-list mr-2"></i><strong>Detalles — Planilla {{ $planillaDetalles?->numero ?? '' }}</strong>
                    </h5>
                    <button type="button" class="close text-white" aria-label="Close"
                        wire:click="cerrarModalDetalles">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                @if($planillaDetalles)
                <div class="modal-body p-3">
                    <div class="row mb-3 small">
                        <div class="col-auto"><strong>Tipo:</strong> {{ $planillaDetalles->tipo->tipo ?? '—' }}</div>
                        <div class="col-auto"><strong>Dependencia:</strong> {{ $planillaDetalles->dependencia->dependencia ?? '—' }}</div>
                        <div class="col-auto"><strong>Turno:</strong> {{ $planillaDetalles->turno ?? '—' }}</div>
                        <div class="col-auto"><strong>ER N°:</strong> {{ $planillaDetalles->er_numero ?? '—' }}</div>
                        <div class="col-auto"><strong>Fecha:</strong> {{ $planillaDetalles->fecha->format('d/m/Y') }}</div>
                    </div>

                    @php
                        $itemsAgrupados = $planillaDetalles->items->groupBy(function($item) {
                            $cfe = $item->cfe;
                            $cfeLabel = $cfe ? "{$cfe->documento_tipo} {$cfe->documento_serie}-{$cfe->documento_numero}" : '—';
                            $distribucion = $item->siifDistribucion->concepto ?? 'Sin distribución';
                            return $cfeLabel . '|' . $distribucion;
                        });
                    @endphp
                    <div class="mb-2 d-flex justify-content-end">
                        <div class="input-group input-group-sm" style="width: 280px;">
                            <input type="text" class="form-control" placeholder="Buscar en detalles..." id="buscarDetalles" onkeyup="filtrarDetalles(this)">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" onclick="resetearBusquedaDetalles()" title="Limpiar búsqueda">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <table class="table table-sm table-bordered mb-0" id="tablaDetalles">
                        <thead class="thead-light">
                            <tr>
                                <th>CFE</th>
                                <th>Detalle</th>
                                <th>Distribución SIIF</th>
                                <th class="text-right">Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($itemsAgrupados as $grupoKey => $grupoItems)
                                @php
                                    $partes = explode('|', $grupoKey);
                                    $cfeLabel = $partes[0];
                                    $distribucionLabel = $partes[1] ?? '—';
                                    $totalGrupo = $grupoItems->sum('importe');
                                    $primerItem = $grupoItems->first();
                                @endphp
                                <tr class="table-info font-weight-bold">
                                    <td colspan="3" class="align-middle small">
                                        <i class="fas fa-folder-open mr-1"></i> {{ $cfeLabel }} — {{ $distribucionLabel }}
                                        @if($primerItem->cfe && $primerItem->cfe->receptor_nombre_denominacion)
                                            <br><small style="font-size: 0.8rem;" class="ml-3">{{ $primerItem->cfe->receptor_nombre_denominacion }}</small>
                                        @endif
                                    </td>
                                    <td class="align-middle small text-right text-nowrap">$ {{ number_format($totalGrupo, 2, ',', '.') }}</td>
                                </tr>
                                @foreach($grupoItems as $item)
                                    <tr>
                                        <td class="align-middle small pl-4">
                                            @if($item->cfe)
                                                {{ $item->cfe->documento_tipo }} {{ $item->cfe->documento_serie }}-{{ $item->cfe->documento_numero }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="align-middle small">{{ $item->detalle }}</td>
                                        <td class="align-middle small">{{ $item->siifDistribucion->concepto ?? '—' }}</td>
                                        <td class="align-middle small text-right text-nowrap">$ {{ number_format($item->importe, 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-2">No hay ítems asociados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="cerrarModalDetalles">
                        Cerrar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="imprimirDetalles()">
                        <i class="fas fa-print mr-1"></i> Imprimir
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal Ver Planilla --}}
    <div class="modal fade" id="modalPlanilla" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-full-width" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white p-2">
                    <h5 class="modal-title m-0">
                        <i class="fas fa-file-alt mr-2"></i><strong>Planilla {{ $planillaVer?->numero ?? '' }}</strong>
                    </h5>
                    <button type="button" class="close text-white" aria-label="Close"
                        wire:click="cerrarModalPlanilla">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                @if($planillaVer)
                <div class="modal-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <strong>JEFATURA DE POLICÍA DE MONTEVIDEO</strong><br>
                            <strong>DIRECCIÓN DE TESORERÍA</strong>
                        </div>
                        <div class="text-right">
                            <strong>PLANILLA: {{ $planillaVer->numero }}</strong><br>
                            <strong>FECHA: {{ $planillaVer->fecha->format('d/m/Y') }}</strong>
                        </div>
                    </div>
                    <div class="text-center mb-3">
                        <strong>PLANILLA PARA ESTADO DE RECAUDACIÓN</strong>
                    </div>

                    @php
                        $itemsAgrupados = $planillaVer->items->sortBy('siif_distribucion_id')->groupBy(function($item) {
                            return $item->siifDistribucion?->concepto ?? 'Sin distribución';
                        });
                        $totalGeneralAjustado = 0;
                    @endphp

                    @forelse($itemsAgrupados as $concepto => $items)
                        @php
                            $grupoTotal = $items->sum('importe');
                            $grupoTotalAjustado = $grupoTotal;
                        @endphp
                        <div class="card mb-3">
                            <div class="card-header py-1 px-2 border-bottom-0 d-flex align-items-center justify-content-center">
                                <strong>{{ $concepto }}</strong>
                            </div>
                            <div class="card-body p-0">
                                @if($concepto !== 'Sin distribución' && $items->first()->siifDistribucion)
                                    @php
                                        $primerItem = $items->first();
                                        $distribuciones = \App\Models\Tesoreria\SiifDistribucion::where('tipo_id', $primerItem->siifDistribucion->tipo_id)
                                            ->where('dependencia_id', $primerItem->siifDistribucion->dependencia_id)
                                            ->where('concepto', $concepto)
                                            ->whereNull('deleted_at')
                                            ->get();
                                    @endphp

                                    @if($distribuciones->isNotEmpty())
                                        <table class="table table-sm table-bordered mb-0 border-top-0">
                                            <thead>
                                                <tr>
                                                    <th>Recurso</th>
                                                    <th>Concepto</th>
                                                    <th class="text-right">%</th>
                                                    <th class="text-center">Financiación</th>
                                                    <th class="text-center">Inciso</th>
                                                    <th class="text-center">Unid.Ejec.</th>
                                                    <th class="text-right">Importe</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $distGrupos = $distribuciones->groupBy(function($d) {
                                                        return ($d->financiacion ?? '—') . '|' . ($d->inciso ?? '—') . '|' . ($d->unidad_ejecutora ?? '—');
                                                    })->map(function($grupo) use ($grupoTotal) {
                                                        $sumaPorc = $grupo->sum('porcentaje');
                                                        $primer = $grupo->first();
                                                        return (object) [
                                                            'recurso' => $primer->recurso,
                                                            'concepto' => $primer->concepto,
                                                            'porcentaje' => $sumaPorc,
                                                            'financiacion' => $primer->financiacion,
                                                            'inciso' => $primer->inciso,
                                                            'unidad_ejecutora' => $primer->unidad_ejecutora,
                                                            'importe_raw' => $grupoTotal * ($sumaPorc / 100),
                                                        ];
                                                    });

                                                    $sumaRedondeada = 0;
                                                    foreach ($distGrupos as $dg) {
                                                        $dg->importe = round($dg->importe_raw, 0);
                                                        $sumaRedondeada += $dg->importe;
                                                    }

                                                    $diferencia = round($grupoTotal - $sumaRedondeada, 0);

                                                    if ($diferencia != 0) {
                                                        $compensado = false;
                                                        foreach ($distGrupos as $dg) {
                                                            if ($dg->unidad_ejecutora == '4' && $dg->inciso == '1') {
                                                            $dg->importe = round($dg->importe + $diferencia, 0);
                                                            $compensado = true;
                                                                break;
                                                            }
                                                        }
                                                        if (!$compensado) {
                                                            $dg = $distGrupos->first();
                                                            if ($dg) {
                                                                $dg->importe = round($dg->importe + $diferencia, 0);
                                                            }
                                                        }
                                                    }

                                                    $grupoTotalAjustado = $distGrupos->sum('importe');
                                                @endphp
                                                @foreach($distGrupos as $dg)
                                                    <tr>
                                                        <td class="align-middle">{{ is_numeric($dg->recurso) ? number_format((int)$dg->recurso, 0, ',', '.') : ($dg->recurso ?? '—') }}</td>
                                                        <td class="align-middle">{{ $dg->concepto }}</td>
                                                        <td class="align-middle text-right">{{ rtrim(rtrim(number_format($dg->porcentaje, 3, ',', '.'), '0'), ',') }}%</td>
                                                        <td class="align-middle text-center">{{ $dg->financiacion ?? '—' }}</td>
                                                        <td class="align-middle text-center">{{ $dg->inciso ?? '—' }}</td>
                                                        <td class="align-middle text-center">{{ $dg->unidad_ejecutora ?? '—' }}</td>
                                                        <td class="align-middle text-right text-nowrap">$&nbsp;{{ number_format($dg->importe, 2, ',', '.') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                @endif

                                <div class="d-flex justify-content-end align-items-center py-2 px-3 border-top">
                                    <div>
                                        <strong>Total del grupo:</strong> $ {{ number_format($grupoTotalAjustado, 2, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        @php $totalGeneralAjustado += $grupoTotalAjustado; @endphp
                    @empty
                        <p class="text-center py-4">No hay ítems asociados a esta planilla.</p>
                    @endforelse

                    <div class="d-flex justify-content-end py-2 px-3 my-3 border rounded">
                        <strong>TOTAL GENERAL:</strong>&nbsp;$ {{ number_format($totalGeneralAjustado, 2, ',', '.') }}
                    </div>

                    <table class="table table-sm table-bordered mt-3 mb-0">
                        <tbody>
                            <tr>
                                <td class="align-middle small text-nowrap font-weight-bold" style="width: 1%; white-space: nowrap;">Estado de recaudación Nro.</td>
                                <td class="align-middle small">{{ $planillaVer->er_numero }}</td>
                            </tr>
                            <tr>
                                <td class="align-middle small text-nowrap font-weight-bold" style="width: 1%; white-space: nowrap;">Nros. Egresos (rubro 100.99/800.9)</td>
                                <td class="align-middle small">{{ $planillaVer->egresos_numero }}</td>
                            </tr>
                            <tr>
                                <td class="align-middle small text-nowrap font-weight-bold" style="width: 1%; white-space: nowrap;">Nros. Ingresos</td>
                                <td class="align-middle small">{{ $planillaVer->ingresos_numero }}</td>
                            </tr>
                            <tr>
                                <td class="align-middle small text-nowrap font-weight-bold" style="width: 1%; white-space: nowrap;">Fecha de transferencia</td>
                                <td class="align-middle small">{{ $planillaVer->transferencia_fecha?->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <td class="align-middle small text-nowrap font-weight-bold" style="width: 1%; white-space: nowrap;">Transf. Confirmación</td>
                                <td class="align-middle small">{{ $planillaVer->transferencia_confirmacion }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="cerrarModalPlanilla">
                        Cerrar
                    </button>
                    @if($planillaVer->confirmada)
                    <button type="button" class="btn btn-primary" onclick="imprimirPlanilla()">
                        <i class="fas fa-print mr-1"></i> Imprimir
                    </button>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function imprimirPlanilla() {
        var modalBody = document.getElementById('modalPlanilla').querySelector('.modal-body');
        var wrapper = document.createElement('div');
        wrapper.innerHTML = modalBody.innerHTML;
        wrapper.querySelectorAll('svg,i').forEach(function(el) {
            el.style.setProperty('width', '1em', 'important');
            el.style.setProperty('height', '1em', 'important');
            el.style.setProperty('font-size', 'inherit', 'important');
            el.style.setProperty('max-width', '1em', 'important');
            el.style.setProperty('max-height', '1em', 'important');
            el.style.setProperty('vertical-align', 'middle', 'important');
        });
        var ventana = window.open('', '_blank', 'width=800,height=600');
        ventana.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Planilla</title>');
        ventana.document.write('<style>');
        ventana.document.write('body{font-family:sans-serif;font-size:13px;padding:20px}');
        ventana.document.write('table{width:100%;border-collapse:collapse;font-size:13px}');
        ventana.document.write('td,th{border:1px solid #999;padding:3px 6px;vertical-align:middle}');
        ventana.document.write('thead{background:#e0e0e0;font-weight:bold}');
        ventana.document.write('.card{border:1px solid #bbb;margin-bottom:12px;page-break-inside:avoid}');
        ventana.document.write('.card-header{padding:6px 10px;background:#f0f0f0;font-weight:700;text-align:center;border-bottom:1px solid #bbb}');
        ventana.document.write('.card-body{padding:0}');
        ventana.document.write('.border-top{border-top:1px solid #bbb}');
        ventana.document.write('.border-bottom-0{border-bottom:0}');
        ventana.document.write('.border{border:1px solid #999!important}');
        ventana.document.write('.rounded{border-radius:4px}');
        ventana.document.write('.d-flex{display:flex}');
        ventana.document.write('.justify-content-between{justify-content:space-between}');
        ventana.document.write('.justify-content-center{justify-content:center}');
        ventana.document.write('.justify-content-end{justify-content:flex-end}');
        ventana.document.write('.align-items-start{align-items:flex-start}');
        ventana.document.write('.align-items-center{align-items:center}');
        ventana.document.write('.text-right{text-align:right}');
        ventana.document.write('.text-center{text-align:center}');
        ventana.document.write('.text-nowrap{white-space:nowrap}');
        ventana.document.write('.font-weight-bold{font-weight:700}');
        ventana.document.write('.small{font-size:11px}');
        ventana.document.write('.mb-0{margin-bottom:0}.mb-3{margin-bottom:12px}');
        ventana.document.write('.mt-3{margin-top:12px}');
        ventana.document.write('.my-3{margin-top:12px;margin-bottom:12px}');
        ventana.document.write('.py-1{padding-top:4px;padding-bottom:4px}');
        ventana.document.write('.py-2{padding-top:8px;padding-bottom:8px}');
        ventana.document.write('.py-3{padding-top:12px;padding-bottom:12px}');
        ventana.document.write('.px-2{padding-left:8px;padding-right:8px}');
        ventana.document.write('.px-3{padding-left:12px;padding-right:12px}');
        ventana.document.write('.d-print-none,.modal-footer,.close,.custom-control{display:none!important}');
        ventana.document.write('@media print{body{padding:0}}');
        ventana.document.write('</style></head><body>');
        ventana.document.write(wrapper.innerHTML);
        ventana.document.write('</body></html>');
        ventana.document.close();
        ventana.focus();
        setTimeout(function() { ventana.print(); ventana.close(); }, 500);
    }

    function imprimirDetalles() {
        var modalBody = document.getElementById('modalDetallesPlanilla').querySelector('.modal-body');
        var wrapper = document.createElement('div');
        wrapper.innerHTML = modalBody.innerHTML;
        wrapper.querySelectorAll('svg,i').forEach(function(el) {
            el.style.setProperty('width', '1em', 'important');
            el.style.setProperty('height', '1em', 'important');
            el.style.setProperty('font-size', 'inherit', 'important');
            el.style.setProperty('max-width', '1em', 'important');
            el.style.setProperty('max-height', '1em', 'important');
            el.style.setProperty('vertical-align', 'middle', 'important');
        });
        wrapper.querySelectorAll('tbody').forEach(function(tbody) {
            var rows = Array.from(tbody.querySelectorAll('tr'));
            if (rows.some(function(r) { return r.classList.contains('table-info'); })) {
                var group = document.createElement('tbody');
                rows.forEach(function(row) {
                    if (row.classList.contains('table-info') && group.querySelector('tr')) {
                        group.style.setProperty('page-break-inside', 'avoid', 'important');
                        tbody.parentNode.insertBefore(group, tbody);
                        group = document.createElement('tbody');
                    }
                    group.appendChild(row);
                });
                if (group.querySelector('tr')) {
                    group.style.setProperty('page-break-inside', 'avoid', 'important');
                    tbody.parentNode.insertBefore(group, tbody);
                }
                tbody.remove();
            }
        });
        var ventana = window.open('', '_blank', 'width=800,height=600');
        ventana.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Detalles - Planilla</title>');
        ventana.document.write('<style>');
        ventana.document.write('body{font-family:sans-serif;font-size:13px;padding:20px}');
        ventana.document.write('table{width:100%;border-collapse:collapse;font-size:13px}');
        ventana.document.write('td,th{border:1px solid #999;padding:3px 6px;vertical-align:middle}');
        ventana.document.write('thead{background:#e0e0e0;font-weight:bold}');
        ventana.document.write('.table-info{background:#e8f0fe}');
        ventana.document.write('.table-secondary{background:#f0f0f0}');
        ventana.document.write('.small{font-size:11px}');
        ventana.document.write('.font-weight-bold{font-weight:700}');
        ventana.document.write('.text-right{text-align:right}');
        ventana.document.write('.text-center{text-align:center}');
        ventana.document.write('.text-nowrap{white-space:nowrap}');
        ventana.document.write('tbody{page-break-inside:avoid}');
        ventana.document.write('.d-print-none,.modal-footer,.close{display:none!important}');
        ventana.document.write('@media print{body{padding:0}}');
        ventana.document.write('</style></head><body>');
        ventana.document.write(wrapper.innerHTML);
        ventana.document.write('</body></html>');
        ventana.document.close();
        ventana.focus();
        setTimeout(function() { ventana.print(); ventana.close(); }, 500);
    }

    document.addEventListener('livewire:load', function () {
        window.addEventListener('abrir-modal-detalles', () => {
            $('#modalDetallesPlanilla').modal('show');
        });
        window.addEventListener('cerrar-modal-detalles', () => {
            $('#modalDetallesPlanilla').modal('hide');
        });
        window.addEventListener('abrir-modal-planilla', () => {
            $('#modalPlanilla').modal('show');
        });
        window.addEventListener('cerrar-modal-planilla', () => {
            $('#modalPlanilla').modal('hide');
        });

        $('#modalDetallesPlanilla').on('hidden.bs.modal', function () {
            @this.call('cerrarModalDetalles');
        });
        $('#modalPlanilla').on('hidden.bs.modal', function () {
            @this.call('cerrarModalPlanilla');
        });
    });
</script>

<script>
    function resetearBusquedaDetalles() {
        var input = document.getElementById('buscarDetalles');
        if (input) { input.value = ''; filtrarDetalles(input); }
    }

    function filtrarDetalles(input) {
        var texto = input.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        var tabla = document.getElementById('tablaDetalles');
        if (!tabla) return;
        var filas = tabla.querySelectorAll('tbody tr');
        filas.forEach(function(fila) {
            if (fila.classList.contains('table-info')) {
                var dets = [];
                var hermano = fila.nextElementSibling;
                while (hermano && !hermano.classList.contains('table-info')) {
                    dets.push(hermano);
                    hermano = hermano.nextElementSibling;
                }
                var algunVisible = false;
                dets.forEach(function(d) {
                    var txt = d.textContent.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                    var coincide = txt.indexOf(texto) > -1;
                    d.style.display = coincide ? '' : 'none';
                    if (coincide) algunVisible = true;
                });
                var txtG = fila.textContent.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                if (txtG.indexOf(texto) > -1) algunVisible = true;
                fila.style.display = algunVisible || texto === '' ? '' : 'none';
                if (!algunVisible && texto !== '') {
                    dets.forEach(function(d) { d.style.display = 'none'; });
                }
            }
        });
    }
</script>
@endpush
