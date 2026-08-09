<div class="container-fluid px-0">
    @section('title', 'Indicadores - Recaudaciones')

    <div class="card">
        {{-- Header --}}
        <div class="card-header bg-info text-white card-header-gradient p-2">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="card-title px-1 m-0">
                    <strong><i class="fas fa-chart-pie mr-2"></i>Indicadores de Recaudaciones</strong>
                </h4>
                <div class="d-flex align-items-center">
                    <div class="btn-group mb-0 mr-2 position-relative" role="group" x-data="{ open: false }" @click.outside="open = false">
                        <button type="button" class="btn btn-light btn-sm dropdown-toggle" @click="open = !open" aria-haspopup="true" :aria-expanded="open">
                            <i class="fas fa-hand-holding-usd mr-1"></i> Resumen
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" :class="{ 'show': open }" style="display: block;" x-show="open" x-cloak>
                            <a class="dropdown-item" href="{{ route('tesoreria.gestion-cfe.recaudaciones') }}">
                                <i class="fas fa-list-alt mr-2"></i>Resumen Detallado
                            </a>
                            <a class="dropdown-item active" href="{{ route('tesoreria.gestion-cfe.dashboard') }}">
                                <i class="fas fa-chart-pie mr-2"></i>Indicadores
                            </a>
                        </div>
                    </div>
                    <button type="button" onclick="window.print()" class="btn btn-light btn-sm mr-2" title="Imprimir reporte">
                        <i class="fas fa-print mr-1"></i> Imprimir
                    </button>
                    <a href="{{ route('tesoreria.gestion-cfe.index') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Volver
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">

            {{-- Filtros de Período --}}
            <div class="d-flex mb-3 align-items-end flex-wrap d-print-none" style="gap: 8px;">
                <div>
                    <label class="small mb-1 d-block font-weight-bold">Período rápido</label>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button"
                                class="btn {{ $filtroSeleccionado === 'hoy' ? 'btn-info' : 'btn-outline-secondary' }}"
                                wire:click="filtrarHoy">
                            <i class="fas fa-calendar-day mr-1"></i>Hoy
                        </button>
                        <button type="button"
                                class="btn {{ $filtroSeleccionado === 'semana' ? 'btn-info' : 'btn-outline-secondary' }}"
                                wire:click="filtrarSemana">
                            <i class="fas fa-calendar-week mr-1"></i>Semana
                        </button>
                        <button type="button"
                                class="btn {{ $filtroSeleccionado === 'mes' ? 'btn-info' : 'btn-outline-secondary' }}"
                                wire:click="filtrarMes">
                            <i class="fas fa-calendar-alt mr-1"></i>Este mes
                        </button>
                        <button type="button"
                                class="btn {{ $filtroSeleccionado === 'mes_anterior' ? 'btn-info' : 'btn-outline-secondary' }}"
                                wire:click="filtrarMesAnterior">
                            <i class="fas fa-history mr-1"></i>Mes anterior
                        </button>
                        <button type="button"
                                class="btn {{ $filtroSeleccionado === '30dias' ? 'btn-info' : 'btn-outline-secondary' }}"
                                wire:click="filtrar30Dias">
                            <i class="fas fa-clock mr-1"></i>Últimos 30 días
                        </button>
                        <button type="button"
                                class="btn {{ $filtroSeleccionado === 'ano' ? 'btn-info' : 'btn-outline-secondary' }}"
                                wire:click="filtrarAno">
                            <i class="fas fa-calendar mr-1"></i>Este año
                        </button>
                    </div>
                </div>

                <div class="border-left pl-3">
                    <label class="small mb-1 d-block font-weight-bold">Rango personalizado</label>
                    <div class="d-flex align-items-center" style="gap: 6px;">
                        <input type="date" class="form-control form-control-sm" wire:model.live="fechaInicioCustom" style="width: 145px;">
                        <span class="small text-muted">al</span>
                        <input type="date" class="form-control form-control-sm" wire:model.live="fechaFinCustom" style="width: 145px;">
                        <button type="button" class="btn btn-sm btn-success" wire:click="aplicarFiltroPersonalizado" title="Aplicar">
                            <i class="fas fa-check"></i>
                        </button>
                        @if($filtroSeleccionado === 'personalizado')
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="limpiarFiltros" title="Restablecer">
                            <i class="fas fa-undo"></i>
                        </button>
                        @endif
                    </div>
                    @error('fechaInicioCustom') <span class="text-danger small">{{ $message }}</span> @enderror
                    @error('fechaFinCustom') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>

                <div class="ml-auto text-right">
                    <span class="badge badge-info badge-pill px-3 py-2">
                        <i class="fas fa-calendar-check mr-1"></i> {{ $periodoTexto }}
                    </span>
                    <div class="small text-muted mt-1">
                        {{ $carbonInicio->format('d/m/Y') }} al {{ $carbonFin->format('d/m/Y') }}
                        &nbsp;·&nbsp; {{ $kpis['total_recaudado']['dias_periodo'] ?? 1 }} día(s)
                        &nbsp;·&nbsp; Prom. $ {{ number_format($kpis['total_recaudado']['promedio_diario'] ?? 0, 2, ',', '.') }}/día
                    </div>
                </div>
            </div>

            {{-- KPI: Total Recaudado --}}
            <div class="card mb-3">
                <div class="card-header bg-info text-white py-1 px-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold">
                            <i class="fas fa-dollar-sign mr-1"></i>Total Recaudado en el Período
                        </span>
                        <span class="h5 mb-0 font-weight-bold">
                            $ {{ number_format($kpis['total_recaudado']['total_general'], 2, ',', '.') }}
                        </span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if(count(array_filter($kpis['total_recaudado']['desglose'], fn($m) => $m['monto'] > 0)) > 0)
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Medio de Pago</th>
                                <th class="text-right" style="width: 180px;">Monto</th>
                                <th class="text-right" style="width: 80px;">%</th>
                                <th style="width: 200px;" class="d-print-none">Distribución</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kpis['total_recaudado']['desglose'] as $medio)
                                @if($medio['monto'] > 0)
                                <tr>
                                    <td class="align-middle">
                                        @if($medio['medio'] === 'Efectivo')
                                            <i class="fas fa-money-bill-wave text-success mr-1"></i>
                                        @elseif($medio['medio'] === 'Cheque')
                                            <i class="fas fa-money-check-alt text-warning mr-1"></i>
                                        @elseif($medio['medio'] === 'Transferencia Bancaria')
                                            <i class="fas fa-university text-info mr-1"></i>
                                        @elseif($medio['medio'] === 'Tarjeta de Débito')
                                            <i class="fas fa-credit-card text-primary mr-1"></i>
                                        @else
                                            <i class="fas fa-coins text-secondary mr-1"></i>
                                        @endif
                                        {{ $medio['medio'] }}
                                    </td>
                                    <td class="align-middle text-right font-weight-bold text-nowrap">
                                        $ {{ number_format($medio['monto'], 2, ',', '.') }}
                                    </td>
                                    <td class="align-middle text-right text-nowrap">
                                        {{ $medio['porcentaje'] }}%
                                    </td>
                                    <td class="align-middle d-print-none">
                                        <div class="progress" style="height: 8px; border-radius: 4px;">
                                            <div class="progress-bar {{ $medio['medio'] === 'Efectivo' ? 'bg-success' : ($medio['medio'] === 'Cheque' ? 'bg-warning' : ($medio['medio'] === 'Transferencia Bancaria' ? 'bg-info' : 'bg-primary')) }}"
                                                 role="progressbar"
                                                 style="width: {{ $medio['porcentaje'] }}%">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                        <tfoot class="table-active">
                            <tr>
                                <td class="font-weight-bold">TOTAL GENERAL</td>
                                <td class="font-weight-bold text-right text-nowrap">
                                    $ {{ number_format($kpis['total_recaudado']['total_general'], 2, ',', '.') }}
                                </td>
                                <td class="font-weight-bold text-right">100%</td>
                                <td class="d-print-none"></td>
                            </tr>
                        </tfoot>
                    </table>
                    @else
                    <p class="text-muted text-center py-4">No hay recaudaciones en el período seleccionado.</p>
                    @endif
                </div>
            </div>

            {{-- KPI: Comparativa con Período Anterior --}}
            <div class="card mb-3">
                <div class="card-header bg-info text-white py-1 px-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold">
                            <i class="fas fa-exchange-alt mr-1"></i>Comparativa con Período Anterior
                        </span>
                        @if(($kpis['comparativa']['tendencia'] ?? 'up') === 'up')
                            <span class="badge badge-success px-2 py-1">
                                <i class="fas fa-arrow-up mr-1"></i> +{{ $kpis['comparativa']['porcentaje_display'] }}
                            </span>
                        @elseif($kpis['comparativa']['porcentaje_display'] === 'Nuevo')
                            <span class="badge badge-info px-2 py-1">
                                <i class="fas fa-star mr-1"></i> Nuevo
                            </span>
                        @else
                            <span class="badge badge-danger px-2 py-1">
                                <i class="fas fa-arrow-down mr-1"></i> {{ $kpis['comparativa']['porcentaje_display'] }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Período</th>
                                <th class="text-right">Monto</th>
                                <th class="text-right">Diferencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="align-middle font-weight-bold text-primary">
                                    <i class="fas fa-calendar-check mr-1"></i>Período Actual
                                </td>
                                <td class="align-middle text-right font-weight-bold text-nowrap">
                                    $ {{ number_format($kpis['comparativa']['actual'], 2, ',', '.') }}
                                </td>
                                <td class="align-middle text-right font-weight-bold text-nowrap {{ $kpis['comparativa']['tendencia'] === 'up' ? 'text-success' : 'text-danger' }}">
                                    @if($kpis['comparativa']['tendencia'] === 'up')
                                        <i class="fas fa-arrow-up mr-1"></i>
                                    @else
                                        <i class="fas fa-arrow-down mr-1"></i>
                                    @endif
                                    $ {{ number_format(abs($kpis['comparativa']['diferencia']), 2, ',', '.') }}
                                </td>
                            </tr>
                            <tr class="table-secondary">
                                <td class="align-middle text-muted">
                                    <i class="fas fa-history mr-1"></i>Período Anterior
                                </td>
                                <td class="align-middle text-right text-nowrap text-muted">
                                    $ {{ number_format($kpis['comparativa']['anterior'], 2, ',', '.') }}
                                </td>
                                <td class="align-middle text-right text-muted small">
                                    (base de comparación)
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="px-2 py-1 small text-muted border-top">
                        <i class="fas fa-info-circle mr-1"></i>
                        Compara el rango seleccionado contra la misma cantidad de días inmediatamente anteriores.
                    </div>
                </div>
            </div>

            {{-- KPI: Distribución por Tipo SIIF --}}
            @if(count($kpis['recaudacion_por_tipo_siif']) > 0)
            <div class="card mb-3">
                <div class="card-header bg-info text-white py-1 px-2">
                    <span class="font-weight-bold">
                        <i class="fas fa-sitemap mr-1"></i>Recaudación por Tipo de Distribución SIIF
                    </span>
                </div>
                <div class="card-body p-0">
                    @php $totalSiif = array_sum(array_column($kpis['recaudacion_por_tipo_siif'], 'total')); @endphp
                    <table class="table table-sm table-bordered table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Tipo de Distribución</th>
                                <th class="text-right" style="width: 180px;">Total</th>
                                <th class="text-right" style="width: 80px;">%</th>
                                <th style="width: 200px;" class="d-print-none">Distribución</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kpis['recaudacion_por_tipo_siif'] as $item)
                            @php $pct = $totalSiif > 0 ? round(($item['total'] / $totalSiif) * 100, 1) : 0; @endphp
                            <tr>
                                <td class="align-middle">
                                    <i class="fas fa-tag text-info mr-1"></i>{{ $item['tipo'] }}
                                </td>
                                <td class="align-middle text-right font-weight-bold text-nowrap">
                                    $ {{ number_format($item['total'], 2, ',', '.') }}
                                </td>
                                <td class="align-middle text-right text-nowrap">{{ $pct }}%</td>
                                <td class="align-middle d-print-none">
                                    <div class="progress" style="height: 8px; border-radius: 4px;">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ $pct }}%"></div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-active">
                            <tr>
                                <td class="font-weight-bold">TOTAL</td>
                                <td class="font-weight-bold text-right text-nowrap">
                                    $ {{ number_format($totalSiif, 2, ',', '.') }}
                                </td>
                                <td class="font-weight-bold text-right">100%</td>
                                <td class="d-print-none"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endif

            {{-- KPI: Top 10 Dependencias --}}
            @if(count($kpis['recaudacion_por_dependencia']) > 0)
            <div class="card mb-3">
                <div class="card-header bg-info text-white py-1 px-2">
                    <span class="font-weight-bold">
                        <i class="fas fa-building mr-1"></i>Top 10 Dependencias Recaudadoras
                    </span>
                </div>
                <div class="card-body p-0">
                    @php $totalDep = array_sum(array_column($kpis['recaudacion_por_dependencia'], 'total')); @endphp
                    <table class="table table-sm table-bordered table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 40px;" class="text-center">#</th>
                                <th>Dependencia</th>
                                <th class="text-right" style="width: 180px;">Total</th>
                                <th class="text-right" style="width: 80px;">%</th>
                                <th style="width: 200px;" class="d-print-none">Distribución</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kpis['recaudacion_por_dependencia'] as $index => $item)
                            @php $pct = $totalDep > 0 ? round(($item['total'] / $totalDep) * 100, 1) : 0; @endphp
                            <tr>
                                <td class="align-middle text-center font-weight-bold text-muted">{{ $index + 1 }}</td>
                                <td class="align-middle">{{ $item['dependencia'] }}</td>
                                <td class="align-middle text-right font-weight-bold text-nowrap">
                                    $ {{ number_format($item['total'], 2, ',', '.') }}
                                </td>
                                <td class="align-middle text-right text-nowrap">{{ $pct }}%</td>
                                <td class="align-middle d-print-none">
                                    <div class="progress" style="height: 8px; border-radius: 4px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $pct }}%"></div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- KPI: Ítems Sin Asignar --}}
            <div class="card mb-3">
                <div class="card-header {{ $kpis['items_sin_asignar']['alerta'] ? 'bg-danger' : 'bg-info' }} text-white py-1 px-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold">
                            <i class="fas fa-exclamation-circle mr-1"></i>Ítems Sin Asignar a Planilla (CUN)
                        </span>
                        <div>
                            <span class="badge badge-light text-dark mr-1">
                                <strong>{{ $kpis['items_sin_asignar']['count'] }}</strong> total
                            </span>
                            @if($kpis['items_sin_asignar']['alerta'])
                            <span class="badge badge-warning">
                                <i class="fas fa-bell mr-1"></i><strong>{{ $kpis['items_sin_asignar']['antiguos'] }}</strong> con &gt; 7 días
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($kpis['items_sin_asignar']['count'] > 0)
                    <div class="d-flex justify-content-between align-items-center px-2 py-2 border-bottom bg-light">
                        <span class="small font-weight-bold text-uppercase text-muted">Monto total sin asignar:</span>
                        <span class="h5 mb-0 font-weight-bold text-danger">
                            $ {{ number_format($kpis['items_sin_asignar']['monto_total'] ?? 0, 2, ',', '.') }}
                        </span>
                        <a href="{{ route('tesoreria.gestion-cfe.index') }}" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-external-link-alt mr-1"></i>Gestionar
                        </a>
                    </div>
                    @if(count($kpis['items_sin_asignar']['items_recientes']) > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover mb-0">
                            <thead class="thead-dark">
                                <tr>
                                    <th>CFE</th>
                                    <th>Descripción</th>
                                    <th class="text-right">Importe</th>
                                    <th class="text-center">Fecha</th>
                                    <th class="text-center">Antigüedad</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($kpis['items_sin_asignar']['items_recientes'] as $item)
                                <tr class="{{ $item['dias_antiguedad'] > 7 ? 'table-danger' : '' }}">
                                    <td class="align-middle font-weight-bold text-nowrap">
                                        @if($item['cfe_numero'])
                                        <a href="{{ route('tesoreria.gestion-cfe.index') }}?search={{ urlencode($item['cfe_numero_solo']) }}"
                                           class="text-primary font-weight-bold" title="Ver CFE {{ $item['cfe_numero'] }}">
                                            {{ $item['cfe_numero'] }}
                                            <i class="fas fa-external-link-alt ml-1" style="font-size: 0.75em;"></i>
                                        </a>
                                        @else
                                        <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td class="align-middle text-muted small">
                                        {{ Illuminate\Support\Str::limit($item['descripcion'], 60) }}
                                    </td>
                                    <td class="text-right align-middle font-weight-bold text-nowrap">
                                        ${{ number_format($item['importe'], 2, ',', '.') }}
                                    </td>
                                    <td class="text-center align-middle text-nowrap small">
                                        {{ $item['fecha'] }}
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-{{ $item['dias_antiguedad'] > 7 ? 'danger' : ($item['dias_antiguedad'] > 3 ? 'warning' : 'secondary') }}">
                                            {{ $item['dias_antiguedad'] }} día{{ $item['dias_antiguedad'] != 1 ? 's' : '' }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                    @else
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-check-circle text-success fa-2x mb-2 d-block"></i>
                        <span class="small">No hay ítems pendientes de asignación.</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- KPI: Planillas Pendientes --}}
            <div class="card mb-3">
                <div class="card-header {{ $kpis['planillas_pendientes']['count'] > 0 ? 'bg-warning text-dark' : 'bg-info text-white' }} py-1 px-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold">
                            <i class="fas fa-file-invoice mr-1"></i>Planillas Pendientes de Confirmación
                        </span>
                        <div class="d-flex align-items-center" style="gap: 6px;">
                            <span class="badge badge-{{ $kpis['planillas_pendientes']['count'] > 0 ? 'dark' : 'light text-dark' }} px-2">
                                <strong>{{ $kpis['planillas_pendientes']['count'] }}</strong> pendientes
                            </span>
                            @if($kpis['planillas_pendientes']['count'] > 0)
                            <a href="{{ route('tesoreria.gestion-cfe.estados-recaudacion') }}" class="btn btn-sm btn-dark">
                                Ir a Confirmar <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($kpis['planillas_pendientes']['count'] > 0)
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>N° Planilla</th>
                                <th>Tipo</th>
                                <th>Dependencia</th>
                                <th class="text-center">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kpis['planillas_pendientes']['planillas'] as $planilla)
                            <tr>
                                <td class="align-middle font-weight-bold">N° {{ $planilla['numero'] }}</td>
                                <td class="align-middle">
                                    <span class="badge badge-light border">{{ $planilla['tipo'] }}</span>
                                </td>
                                <td class="align-middle">{{ $planilla['dependencia'] }}</td>
                                <td class="align-middle text-center text-nowrap small">
                                    <i class="far fa-calendar-alt mr-1"></i>{{ $planilla['fecha'] }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-check-circle text-success fa-2x mb-2 d-block"></i>
                        <span class="small">Todas las planillas están confirmadas.</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Panel de Alertas --}}
            @if(count($kpis['alertas']) > 0)
            <div class="card mb-0">
                <div class="card-header bg-info text-white py-1 px-2">
                    <span class="font-weight-bold">
                        <i class="fas fa-bell mr-1"></i>Alertas del Sistema
                    </span>
                </div>
                <div class="card-body py-2 px-2">
                    <div class="row" style="row-gap: 8px;">
                        @foreach($kpis['alertas'] as $alerta)
                        <div class="col-md-6">
                            <div class="alert alert-{{ $alerta['tipo'] }} py-2 mb-0">
                                <strong><i class="fas fa-{{ $alerta['icono'] }} mr-1"></i>{{ $alerta['titulo'] }}</strong>
                                <div class="small mt-1">{{ $alerta['mensaje'] }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

        </div>{{-- /card-body --}}
    </div>{{-- /card --}}
</div>
