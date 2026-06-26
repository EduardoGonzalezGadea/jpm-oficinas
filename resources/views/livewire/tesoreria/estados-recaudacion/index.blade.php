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
    @section('title', 'Estados de Recaudación')

    <div class="card">
        <div class="card-header bg-info text-white card-header-gradient p-2">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="card-title px-1 m-0">
                    <strong><i class="fas fa-chart-line mr-2"></i>Estados de Recaudación</strong>
                </h4>
                <div>
                    @can('tesoreria.supervisar')
                    <a href="{{ route('tesoreria.gestion-cfe.estados-recaudacion.no-confirmadas') }}" class="btn btn-warning mb-0 mr-2">
                        <i class="fas fa-clock mr-1"></i> No Confirmadas
                    </a>
                    @endcan
                    <a href="{{ route('tesoreria.gestion-cfe.index') }}" class="btn btn-light mb-0">
                        <i class="fas fa-arrow-left mr-1"></i> Volver a Gestión de CFEs
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3 align-items-end justify-content-between">
                <div class="col-auto">
                    <label class="form-label small mb-1">Fecha</label>
                    <input type="date" class="form-control form-control-sm" wire:model="fecha">
                </div>
                <div class="col-auto">
                    <h5 class="mb-0 font-weight-bold text-dark">PLANILLAS PARA ESTADOS DE RECAUDACIÓN</h5>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary btn-sm" wire:click="abrirModalNueva">
                        <i class="fas fa-plus mr-1"></i> Nueva Planilla
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped table-hover">
                    <thead class="thead-dark align-middle">
                        <tr>
                            <th>N°</th>
                            <th>Tipo</th>
                            <th>Dependencia</th>
                            <th>Turno</th>
                            <th>Total</th>
                            <th class="text-center">Conf.</th>
                            <th class="text-center d-print-none">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        @forelse($planillas as $p)
                            <tr>
                                <td class="align-middle">{{ $p->numero }}</td>
                                <td class="align-middle">{{ $p->tipo->tipo ?? '—' }}</td>
                                <td class="align-middle">{{ $p->dependencia->dependencia ?? '—' }}</td>
                                <td class="align-middle">{{ $p->turno ?? '—' }}</td>
                                <td class="align-middle text-right text-nowrap">$ {{ number_format($p->items->sum('importe'), 2, ',', '.') }}</td>
                                <td class="align-middle text-center">
                                    @if($p->confirmada)
                                        <i class="fas fa-check-circle text-success" title="Confirmada"></i>
                                    @else
                                        <i class="fas fa-times-circle text-danger" title="No confirmada"></i>
                                    @endif
                                </td>
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
                                        <button class="btn btn-warning btn-action-fixed {{ $p->confirmada ? '' : 'd-none' }}"
                                            title="Editar"
                                            wire:click="editarPlanilla({{ $p->id }})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-action-fixed" title="Eliminar"
                                            onclick="confirmDeletePlanilla({{ $p->id }})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="table-secondary">
                                <td colspan="7" class="py-1 px-2">
                                    <div class="d-flex flex-wrap align-items-center small">
                                        <span class="mr-3"><strong>ER N°:</strong> {{ $p->er_numero ?? '—' }}</span>
                                        <span class="mr-3"><strong>Egresos N°:</strong> {{ $p->egresos_numero ?? '—' }}</span>
                                        <span class="mr-3"><strong>Ingresos N°:</strong> {{ $p->ingresos_numero ?? '—' }}</span>
                                        <span class="mr-3"><strong>Transf. Fecha:</strong> {{ $p->transferencia_fecha ? $p->transferencia_fecha->format('d/m/Y') : '—' }}</span>
                                        <span><strong>Conf. Transf.:</strong> {{ $p->transferencia_confirmacion ?? '—' }}</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-3">
                                    No hay planillas para esta fecha.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 d-flex justify-content-center d-print-none">
                {{ $planillas->links() }}
            </div>
        </div>
    </div>

    {{-- Modal Nueva Planilla --}}
    <div class="modal fade" id="modalNuevaPlanilla" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-full-width" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white p-2">
                    <h5 class="modal-title m-0">
                        <i class="fas fa-plus mr-2"></i><strong>Nueva Planilla de Recaudación</strong>
                    </h5>
                    <button type="button" class="close text-white" aria-label="Close"
                        wire:click="cerrarModalNueva">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-3">
                    <div class="row mb-3">
                        <div class="col-auto">
                            <label class="form-label small mb-1">Fecha de la Planilla</label>
                            <input type="date" class="form-control form-control-sm" wire:model="fechaPlanilla">
                        </div>
                    </div>

                    @forelse($grupos as $grupoKey => $grupo)
                        <div class="card mb-3" wire:key="grupo-{{ $grupoKey }}">
                            <div class="card-header py-1 px-2 d-flex justify-content-between align-items-center
                                {{ $grupoActivo === $grupoKey ? 'bg-primary text-white' : 'bg-light' }}">
                                <span class="font-weight-bold small {{ $grupoActivo && $grupoActivo !== $grupoKey ? 'text-muted' : '' }}">
                                    {{ $grupo['tipo_nombre'] }} &mdash; {{ $grupo['dependencia_nombre'] }}
                                    @if($grupo['turno'])
                                        <span class="badge badge-dark ml-2">{{ $grupo['turno'] }}</span>
                                    @endif
                                </span>
                                <button class="btn btn-sm btn-success"
                                    wire:click="crearPlanilla('{{ $grupoKey }}')"
                                    {{ empty($seleccionados[$grupoKey] ?? []) ? 'disabled' : '' }}>
                                    <i class="fas fa-save mr-1"></i> Crear Planilla
                                </button>
                            </div>
                            <div class="card-body p-0">
                                @php
                                    $itemsPorFecha = collect($grupo['items'])->sortBy('fecha_cfe_raw')->groupBy('fecha_cfe');
                                @endphp
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="text-center" style="width: 40px;">
                                                <input type="checkbox"
                                                    wire:click="toggleGrupo('{{ $grupoKey }}')"
                                                    {{ isset($seleccionados[$grupoKey]) && count($seleccionados[$grupoKey]) === count($grupo['items']) ? 'checked' : '' }}
                                                    {{ $grupoActivo && $grupoActivo !== $grupoKey ? 'disabled' : '' }}>
                                            </th>
                                            <th>Fecha CFE</th>
                                            <th>CFE</th>
                                            <th>Detalle</th>
                                            <th>Distribución SIIF</th>
                                            <th class="text-right">Importe</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($itemsPorFecha as $fecha => $itemsFecha)
                                            @php
                                                $fechaItemIds = $itemsFecha->pluck('id')->toArray();
                                                $fechaAllSelected = !empty($fechaItemIds) && empty(array_diff($fechaItemIds, $seleccionados[$grupoKey] ?? []));
                                            @endphp
                                            <tr class="table-secondary font-weight-bold">
                                                <td class="align-middle small py-1 text-center" style="width: 40px;">
                                                    <input type="checkbox"
                                                        wire:click="toggleFecha('{{ $grupoKey }}', '{{ $fecha }}')"
                                                        {{ $fechaAllSelected ? 'checked' : '' }}
                                                        {{ $grupoActivo && $grupoActivo !== $grupoKey ? 'disabled' : '' }}>
                                                </td>
                                                <td colspan="4" class="align-middle small py-1">
                                                    <i class="far fa-calendar-alt mr-1"></i> {{ $fecha ?? '—' }}
                                                </td>
                                                <td class="align-middle small text-right text-nowrap py-1">$ {{ number_format($itemsFecha->sum('importe'), 2, ',', '.') }}</td>
                                            </tr>
                                            @foreach($itemsFecha as $item)
                                                <tr>
                                                    <td class="align-middle text-center">
                                                        <input type="checkbox"
                                                            wire:click="toggleItem('{{ $grupoKey }}', {{ $item['id'] }})"
                                                            {{ in_array($item['id'], $seleccionados[$grupoKey] ?? []) ? 'checked' : '' }}
                                                            {{ $grupoActivo && $grupoActivo !== $grupoKey ? 'disabled' : '' }}>
                                                    </td>
                                                    <td class="align-middle small text-nowrap">{{ $item['fecha_cfe'] ?? '—' }}</td>
                                                    <td class="align-middle small">
                                                        @if($item['cfe'])
                                                            {{ $item['cfe']['documento_tipo'] }} {{ $item['cfe']['documento_serie'] }}-{{ $item['cfe']['documento_numero'] }}
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                    <td class="align-middle small">{{ $item['detalle'] }}</td>
                                                    <td class="align-middle small">{{ $item['siif_distribucion']['concepto'] ?? '—' }}</td>
                                                    <td class="align-middle small text-right text-nowrap">$ {{ number_format($item['importe'], 2, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center py-4">No hay ítems disponibles para crear planillas.</p>
                    @endforelse
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="cerrarModalNueva">
                        Cerrar
                    </button>
                </div>
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
                                @endphp
                                <tr class="table-info font-weight-bold">
                                    <td colspan="3" class="align-middle small">
                                        <i class="fas fa-folder-open mr-1"></i> {{ $cfeLabel }} — {{ $distribucionLabel }}
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
                    @can('tesoreria.supervisar')
                        <div class="custom-control custom-switch d-inline-block mr-auto">
                            <input type="checkbox" class="custom-control-input" id="switchConfirmada"
                                wire:click="toggleConfirmada({{ $planillaVer->id }})"
                                {{ $planillaVer->confirmada ? 'checked' : '' }}>
                            <label class="custom-control-label" for="switchConfirmada">Confirmada</label>
                        </div>
                    @endcan
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

    {{-- Modal Editar Planilla --}}
    <div class="modal fade" id="modalEditarPlanilla" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-full-width" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark p-2">
                    <h5 class="modal-title m-0">
                        <i class="fas fa-edit mr-2"></i><strong>Editar Planilla {{ $planillaEditar?->numero ?? '' }}</strong>
                    </h5>
                    <button type="button" class="close" aria-label="Close"
                        wire:click="cerrarModalEditar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form wire:submit.prevent="guardarPlanilla">
                    <div class="modal-body p-3">
                        <div class="small text-muted mb-3">
                            <strong>N°:</strong> {{ $planillaEditar?->numero ?? '—' }} &mdash;
                            <strong>Tipo:</strong> {{ $planillaEditar?->tipo?->tipo ?? '—' }} &mdash;
                            <strong>Dependencia:</strong> {{ $planillaEditar?->dependencia?->dependencia ?? '—' }} &mdash;
                            <strong>Turno:</strong> {{ $planillaEditar?->turno ?? '—' }} &mdash;
                            <strong>Fecha:</strong> {{ $planillaEditar?->fecha?->format('d/m/Y') ?? '—' }}
                        </div>

                        <div class="form-group">
                            <label class="small mb-1">Estado de Recaudación N°</label>
                            <input type="text" class="form-control form-control-sm" wire:model="edit_er_numero">
                        </div>
                        <div class="form-group">
                            <label class="small mb-1">Egresos N°</label>
                            <input type="text" class="form-control form-control-sm" wire:model="edit_egresos_numero">
                        </div>
                        <div class="form-group">
                            <label class="small mb-1">Ingresos N°</label>
                            <input type="text" class="form-control form-control-sm" wire:model="edit_ingresos_numero">
                        </div>
                        <div class="form-group">
                            <label class="small mb-1">Transferencia Fecha</label>
                            <input type="date" class="form-control form-control-sm" wire:model="edit_transferencia_fecha">
                        </div>
                        <div class="form-group">
                            <label class="small mb-1">Transf. Confirmación</label>
                            <input type="text" class="form-control form-control-sm" wire:model="edit_transferencia_confirmacion">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="cerrarModalEditar">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save mr-1"></i> Guardar
                        </button>
                    </div>
                </form>
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
                ventana.document.write('<!DOCTYPE html><html><head><meta charset=\"utf-8\"><title>Planilla</title>');
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
                ventana.document.write('.border{border:1px solid #999!important}');
                ventana.document.write('.rounded{border-radius:4px}');
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
                ventana.document.write('<!DOCTYPE html><html><head><meta charset=\"utf-8\"><title>Detalles - Planilla</title>');
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

            function confirmDeletePlanilla(id) {
                Swal.fire({
                    title: '¿Está seguro?',
                    text: 'Esta acción no se puede deshacer. La planilla se marcará como eliminada y los ítems de CFE asociados quedarán disponibles nuevamente.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Livewire.emit('borrarPlanilla', id);
                    }
                });
            }

            document.addEventListener('livewire:load', function () {
                window.addEventListener('abrir-modal-nueva-planilla', () => {
                    $('#modalNuevaPlanilla').modal('show');
                });
                window.addEventListener('abrir-modal-detalles', () => {
                    $('#modalDetallesPlanilla').modal('show');
                });
                window.addEventListener('abrir-modal-planilla', () => {
                    $('#modalPlanilla').modal('show');
                });
                window.addEventListener('abrir-modal-editar', () => {
                    $('#modalEditarPlanilla').modal('show');
                });
                window.addEventListener('cerrar-modal-editar', () => {
                    $('#modalEditarPlanilla').modal('hide');
                });
                window.addEventListener('cerrar-modal-planilla', () => {
                    $('#modalPlanilla').modal('hide');
                });
                window.addEventListener('cerrar-modal-nueva', () => {
                    $('#modalNuevaPlanilla').modal('hide');
                });
                window.addEventListener('cerrar-modal-detalles', () => {
                    $('#modalDetallesPlanilla').modal('hide');
                });

                // Si el modal se cierra manualmente (backdrop, ESC), sincronizar estado Livewire
                $('#modalNuevaPlanilla').on('hidden.bs.modal', function () {
                    @this.call('cerrarModalNueva');
                });
                $('#modalDetallesPlanilla').on('hidden.bs.modal', function () {
                    @this.call('cerrarModalDetalles');
                });
                $('#modalEditarPlanilla').on('hidden.bs.modal', function () {
                    @this.call('cerrarModalEditar');
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
                var grupoVisible = true;
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
                    } else if (!fila.classList.contains('table-info') && fila.style.display !== 'none') {
                    }
                });
            }
        </script>
    @endpush
</div>
