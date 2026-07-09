<div class="container-fluid px-0">
    @section('title', 'Confirmar Planilla — E.R.')

    <div class="card">
        <div class="card-header bg-primary text-white card-header-gradient p-2">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="card-title px-1 m-0">
                    <strong><i class="fas fa-check-double mr-2"></i>Confirmar Planilla {{ $planilla->numero }} para E.R.</strong>
                </h4>
                <div>
                    <a href="{{ route('tesoreria.gestion-cfe.estados-recaudacion') }}" class="btn btn-sm btn-outline-light mr-2">
                        <i class="fas fa-list mr-1"></i>Estados de Recaudación
                    </a>
                    <a href="{{ route('tesoreria.gestion-cfe.estados-recaudacion.no-confirmadas') }}" class="btn btn-sm btn-light">
                        <i class="fas fa-arrow-left mr-1"></i>Volver a No Confirmadas
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body p-3">

            {{-- ============================================================ --}}
            {{-- SECCIÓN SUPERIOR: Items por CFE con cambio de Distribución SIIF --}}
            {{-- ============================================================ --}}
            <div class="card mb-4 border-primary">
                <div class="card-header bg-primary text-white py-1">
                    <strong><i class="fas fa-tags mr-1"></i>Distribución SIIF por Ítem</strong>
                    <span class="badge badge-light float-right mt-1">$ {{ number_format($totalGeneral, 2, ',', '.') }}</span>
                </div>
                <div class="card-body p-2">
                    @forelse($itemsPorCfe as $cfeLabel => $items)
                        @php $primerCfe = $items->first()->cfe; @endphp
                        <div class="card mb-2 border">
                            <div class="card-header bg-light py-1 d-flex align-items-center justify-content-between">
                                <strong><i class="fas fa-file-invoice mr-1"></i>{{ $cfeLabel }}</strong>
                                <div class="d-flex align-items-center">
                                    @if($items->where('enPlanilla', true)->isNotEmpty())
                                    <div class="custom-control custom-switch d-inline-block mr-3">
                                        <input type="checkbox" class="custom-control-input" id="cfeMaster_{{ $primerCfe->id }}"
                                            onclick="event.preventDefault()"
                                            wire:click="toggleConfirmadoCfe({{ $primerCfe->id }})"
                                            {{ $items->where('enPlanilla', true)->every(fn($i) => $i->confirmado) ? 'checked' : '' }}>
                                        <label class="custom-control-label small" for="cfeMaster_{{ $primerCfe->id }}">
                                            Conf. todos
                                        </label>
                                    </div>
                                    @endif
                                    <span class="badge badge-info">$ {{ number_format($items->where('enPlanilla', true)->sum('importe'), 2, ',', '.') }}</span>
                                </div>
                            </div>
                            @if($primerCfe)
                            <div class="px-3 py-1 small bg-white border-bottom d-flex flex-wrap">
                                <span class="mr-3"><strong>Receptor:</strong> {{ $primerCfe->receptor_nombre_denominacion ?? '—' }}</span>
                                <span class="mr-3"><strong>Fecha:</strong> {{ $primerCfe->fecha?->format('d/m/Y') ?? '—' }}</span>
                                <span><strong>Total a pagar:</strong> $ {{ number_format($primerCfe->total_a_pagar ?? 0, 2, ',', '.') }}</span>
                            </div>
                            @endif
                            <div class="card-body p-0">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Detalle</th>
                                                <th class="text-right" style="width: 80px;">Cantidad</th>
                                                <th class="text-right" style="width: 100px;">Precios</th>
                                                <th class="text-right" style="width: 100px;">Importe</th>
                                                <th>Distribución SIIF</th>
                                                <th class="text-center" style="width: 100px;">Conf.</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($items as $item)
                                                <tr class="{{ $item->enPlanilla ? '' : 'text-muted' }}" style="{{ $item->enPlanilla ? '' : 'opacity: 0.6;' }}">
                                                    <td class="align-middle small">
                                                        @if(!$item->enPlanilla)
                                                            <i class="fas fa-minus-circle mr-1 text-secondary" title="No integra esta planilla"></i>
                                                        @else
                                                            <i class="fas fa-check-circle mr-1 text-success" title="Integra esta planilla"></i>
                                                        @endif
                                                        {{ $item->detalle }}
                                                    </td>
                                                    <td class="align-middle small text-right text-nowrap">{{ $item->cantidad ? number_format($item->cantidad, 2, ',', '.') : '—' }}</td>
                                                    <td class="align-middle small text-right text-nowrap">$ {{ number_format($item->precio, 2, ',', '.') }}</td>
                                                    <td class="align-middle small text-right text-nowrap">$ {{ number_format($item->importe, 2, ',', '.') }}</td>
                                                    <td class="align-middle">
                                                        @if($item->enPlanilla)
                                                            <select class="form-control form-control-sm"
                                                                wire:change="cambiarDistribucion({{ $item->id }}, $event.target.value)">
                                                                <option value="">— Sin distribución —</option>
                                                                @php
                                                                    $cfe = $item->cfe;
                                                                    $key = ($cfe->siif_distribucion_tipo_id ?? 'X') . '-' . ($cfe->siif_distribucion_dependencia_id ?? 'X');
                                                                    $opciones = $opcionesPorTipoDep[$key] ?? [];
                                                                @endphp
                                                                @foreach($opciones as $opt)
                                                                    <option value="{{ $opt->id }}" {{ $item->siif_distribucion_id == $opt->id ? 'selected' : '' }}>
                                                                        {{ $opt->concepto }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        @else
                                                            <span class="small">{{ $item->siifDistribucion?->concepto ?? '—' }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="align-middle text-center">
                                                        @if($item->enPlanilla)
                                                            <div class="custom-control custom-switch d-inline-block">
                                                                <input type="checkbox" class="custom-control-input" id="itemConfirmado_{{ $item->id }}"
                                                                    onclick="event.preventDefault()"
                                                                    wire:click="toggleItemConfirmado({{ $item->id }})"
                                                                    {{ $item->confirmado ? 'checked' : '' }}>
                                                                <label class="custom-control-label" for="itemConfirmado_{{ $item->id }}">
                                                                    <span class="small {{ $item->confirmado ? 'text-success font-weight-bold' : 'text-muted' }}">
                                                                        {{ $item->confirmado ? 'Sí' : 'No' }}
                                                                    </span>
                                                                </label>
                                                            </div>
                                                        @else
                                                            <span class="small text-muted">—</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center py-3 mb-0">No hay ítems asociados a esta planilla.</p>
                    @endforelse
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- SECCIÓN MEDIA: Desglose por Medios de Pago (estilo Recaudaciones) --}}
            {{-- ============================================================ --}}
            <div class="card mb-4 border-success" id="seccion-recaudaciones">
                <div class="card-header bg-success text-white py-1">
                    <strong><i class="fas fa-hand-holding-usd mr-1"></i>Desglose por Medios de Pago</strong>
                </div>
                <div class="card-body p-2">
                    @php
                        $tabsConDatos = collect($gruposRecaudacion)->filter(fn($g) => $g['total_efectivo'] + $g['total_cheque'] + $g['total_transferencia'] + $g['total_pos'] > 0);
                    @endphp

                    @if($tabsConDatos->isNotEmpty())
                        @php $primerActivo = true; @endphp
                        <ul class="nav nav-tabs" id="confirmarRecaudacionesTab" role="tablist">
                            @foreach($gruposRecaudacion as $key => $grupo)
                                @if($grupo['total_efectivo'] + $grupo['total_cheque'] + $grupo['total_transferencia'] + $grupo['total_pos'] > 0)
                                    <li class="nav-item">
                                        <a class="nav-link small {{ $primerActivo ? 'active' : '' }}"
                                            id="ar-tab-{{ \Illuminate\Support\Str::slug($key) }}" data-toggle="tab"
                                            href="#ar-content-{{ \Illuminate\Support\Str::slug($key) }}"
                                            role="tab" aria-controls="ar-content-{{ \Illuminate\Support\Str::slug($key) }}"
                                            aria-selected="{{ $primerActivo ? 'true' : 'false' }}">
                                            {{ $grupo['label'] }}
                                        </a>
                                    </li>
                                    @php $primerActivo = false; @endphp
                                @endif
                            @endforeach
                        </ul>
                        @php $primerActivo = true; @endphp
                        <div class="tab-content border border-top-0 p-3" id="confirmarRecaudacionesTabContent">
                            @foreach($gruposRecaudacion as $key => $grupo)
                                @if($grupo['total_efectivo'] + $grupo['total_cheque'] + $grupo['total_transferencia'] + $grupo['total_pos'] > 0)
                                    <div class="tab-pane fade {{ $primerActivo ? 'show active' : '' }}"
                                        id="ar-content-{{ \Illuminate\Support\Str::slug($key) }}" role="tabpanel"
                                        aria-labelledby="ar-tab-{{ \Illuminate\Support\Str::slug($key) }}">
                                        @php $primerActivo = false; @endphp

                                        @foreach($grupo['distribuciones'] as $distKey => $distribucion)
                                            @if(!empty($distribucion['items']))
                                                <div class="card mb-3">
                                                    <div class="card-header py-1 px-2 bg-light text-center">
                                                        <strong>{{ $distribucion['concepto'] }}</strong>
                                                        @php $docsStr = $this->formatRangoDocumentos($distribucion['items']); @endphp
                                                        @if($docsStr)
                                                            <br><small class="text-muted">{{ $docsStr }}</small>
                                                        @endif
                                                    </div>
                                                    <div class="card-body p-0">
                                                        <div class="table-responsive">
                                                            <table class="table table-sm table-bordered mb-0 border-top-0">
                                                                <thead class="thead-light">
                                                                    <tr>
                                                                        <th class="text-nowrap align-middle">Recibo</th>
                                                                        <th class="text-right text-nowrap align-middle">Efectivo</th>
                                                                        <th class="text-right text-nowrap align-middle">Cheque</th>
                                                                        <th class="text-right text-nowrap align-middle">Transferencia</th>
                                                                        <th class="text-right text-nowrap align-middle">POS</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($distribucion['items'] as $rowData)
                                                                        <tr>
                                                                            <td class="align-middle small text-nowrap">
                                                                                {{ $rowData['cfe']->documento_tipo }} {{ $rowData['cfe']->documento_serie }}-{{ $rowData['cfe']->documento_numero }}
                                                                            </td>
                                                                            <td class="align-middle small text-right text-nowrap">
                                                                                $ {{ number_format($rowData['efectivo'], 2, ',', '.') }}
                                                                            </td>
                                                                            <td class="align-middle small text-right text-nowrap">
                                                                                $ {{ number_format($rowData['cheque'], 2, ',', '.') }}
                                                                            </td>
                                                                            <td class="align-middle small text-right text-nowrap">
                                                                                $ {{ number_format($rowData['transferencia'], 2, ',', '.') }}
                                                                            </td>
                                                                            <td class="align-middle small text-right text-nowrap">
                                                                                $ {{ number_format($rowData['pos'], 2, ',', '.') }}
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                                <tfoot class="table-active">
                                                                    <tr>
                                                                        <td class="text-right font-weight-bold small align-middle">Subtotal {{ $distribucion['concepto'] }}:</td>
                                                                        <td class="text-right font-weight-bold small text-nowrap align-middle">$ {{ number_format($distribucion['total_efectivo'], 2, ',', '.') }}</td>
                                                                        <td class="text-right font-weight-bold small text-nowrap align-middle">$ {{ number_format($distribucion['total_cheque'], 2, ',', '.') }}</td>
                                                                        <td class="text-right font-weight-bold small text-nowrap align-middle">$ {{ number_format($distribucion['total_transferencia'], 2, ',', '.') }}</td>
                                                                        <td class="text-right font-weight-bold small text-nowrap align-middle">$ {{ number_format($distribucion['total_pos'], 2, ',', '.') }}</td>
                                                                    </tr>
                                                                </tfoot>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach

                                        @php
                                            $totalGrupo = $grupo['total_efectivo'] + $grupo['total_cheque'] + $grupo['total_transferencia'] + $grupo['total_pos'];
                                        @endphp
                                        <div class="d-flex justify-content-end py-2 px-3 bg-light border rounded">
                                            <table class="table table-sm table-borderless mb-0 text-right" style="width: auto;">
                                                <thead>
                                                    <tr>
                                                        <th class="text-center align-middle small font-weight-bold">TOTALES</th>
                                                        <th class="small font-weight-bold text-nowrap px-2">Efectivo</th>
                                                        <th class="small font-weight-bold text-nowrap px-2">Cheque</th>
                                                        <th class="small font-weight-bold text-nowrap px-2">Transferencia</th>
                                                        <th class="small font-weight-bold text-nowrap px-2">POS</th>
                                                        <th class="small font-weight-bold text-nowrap pl-3 border-left">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td class="font-weight-bold align-middle pr-3">TOTAL {{ mb_strtoupper($grupo['label']) }}:</td>
                                                        <td class="font-weight-bold small text-nowrap align-middle px-2">$ {{ number_format($grupo['total_efectivo'], 2, ',', '.') }}</td>
                                                        <td class="font-weight-bold small text-nowrap align-middle px-2">$ {{ number_format($grupo['total_cheque'], 2, ',', '.') }}</td>
                                                        <td class="font-weight-bold small text-nowrap align-middle px-2">$ {{ number_format($grupo['total_transferencia'], 2, ',', '.') }}</td>
                                                        <td class="font-weight-bold small text-nowrap align-middle px-2">$ {{ number_format($grupo['total_pos'], 2, ',', '.') }}</td>
                                                        <td class="font-weight-bold small text-nowrap align-middle pl-3 border-left">$ {{ number_format($totalGrupo, 2, ',', '.') }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted text-center py-3 mb-0">No hay medios de pago registrados para esta planilla.</p>
                    @endif
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- SECCIÓN INFERIOR: Cuerpo de la planilla con cálculos y switch  --}}
            {{-- ============================================================ --}}
            <div class="card border-warning" id="seccion-planilla-cuerpo">
                <div class="card-header bg-warning py-1">
                    <strong><i class="fas fa-file-alt mr-1"></i>Planilla {{ $planilla->numero }}</strong>
                </div>
                <div class="card-body p-3">

                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="small">
                            <strong>JEFATURA DE POLICÍA DE MONTEVIDEO</strong><br>
                            <strong>DIRECCIÓN DE TESORERÍA</strong>
                        </div>
                        <div class="text-right small">
                            <strong>PLANILLA: {{ $planilla->numero }}</strong><br>
                            <strong>FECHA: {{ $planilla->fecha?->format('d/m/Y') }}</strong>
                        </div>
                    </div>
                    <div class="text-center mb-3">
                        <strong>PLANILLA PARA ESTADO DE RECAUDACIÓN</strong>
                    </div>

                    @php
                        $itemsAgrupados = $planilla->items->sortBy('siif_distribucion_id')->groupBy(function($item) {
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
                                <td class="align-middle small">{{ $planilla->er_numero ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="align-middle small text-nowrap font-weight-bold" style="width: 1%; white-space: nowrap;">Nros. Egresos (rubro 100.99/800.9)</td>
                                <td class="align-middle small">{{ $planilla->egresos_numero ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="align-middle small text-nowrap font-weight-bold" style="width: 1%; white-space: nowrap;">Nros. Ingresos</td>
                                <td class="align-middle small">{{ $planilla->ingresos_numero ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="align-middle small text-nowrap font-weight-bold" style="width: 1%; white-space: nowrap;">Fecha de transferencia</td>
                                <td class="align-middle small">{{ $planilla->transferencia_fecha?->format('d/m/Y') ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="align-middle small text-nowrap font-weight-bold" style="width: 1%; white-space: nowrap;">Transf. Confirmación</td>
                                <td class="align-middle small">{{ $planilla->transferencia_confirmacion ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="d-flex align-items-center justify-content-between border-top pt-3 mt-3">
                        @can('tesoreria.supervisar')
                            <div class="d-flex align-items-center">
                                <div class="custom-control custom-switch d-inline-block">
                                    <input type="checkbox" class="custom-control-input" id="switchConfirmar"
                                        onclick="event.preventDefault()"
                                        wire:click="toggleConfirmada"
                                        {{ $planilla->confirmada ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold" for="switchConfirmar">
                                        {{ $planilla->confirmada ? 'Planilla Confirmada' : 'Marcar como Confirmada' }}
                                    </label>
                                </div>
                                @if($planilla->confirmada)
                                    <span class="badge badge-success ml-3 p-2 d-print-none">
                                        <i class="fas fa-check-circle mr-1"></i> Confirmada
                                    </span>
                                @else
                                    <span class="badge badge-secondary ml-3 p-2 d-print-none">
                                        <i class="fas fa-clock mr-1"></i> Pendiente
                                    </span>
                                @endif
                            </div>
                            @if($planilla->confirmada)
                            <button type="button" class="btn btn-primary btn-sm" onclick="imprimirConfirmar()">
                                <i class="fas fa-print mr-1"></i> Imprimir
                            </button>
                            @else
                            <button type="button" class="btn btn-danger btn-sm"
                                onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('swal:confirm', { detail: { title: '¿Eliminar planilla?', text: 'Esta acción no se puede deshacer. Los ítems quedarán sin asignar.', method: 'eliminarPlanilla', id: {{ $planilla->id }}, confirmButtonText: 'Sí, eliminar' } }))">
                                <i class="fas fa-trash mr-1"></i> Eliminar esta planilla para E.R.
                            </button>
                            @endif
                        @else
                            <p class="text-muted mb-0">
                                <i class="fas fa-info-circle mr-1"></i> No tiene permisos para confirmar planillas.
                            </p>
                        @endcan
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
window.addEventListener('swal:confirmar-cambio-planilla', event => {
    const data = event.detail;
    const showDeny = data.otrosItemsCount > 0;
    Swal.fire({
        title: data.title,
        html: data.html,
        icon: 'warning',
        showCancelButton: true,
        showDenyButton: showDeny,
        confirmButtonColor: '#3085d6',
        denyButtonColor: '#28a745',
        cancelButtonColor: '#d33',
        confirmButtonText: showDeny ? 'Solo este ítem' : 'Sí, mover ítem',
        denyButtonText: showDeny ? 'Incluir los ' + data.otrosItemsCount + ' ítem(s)' : null,
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            @this.call('confirmarCambioPlanilla', data.itemId, data.distribucionId, data.targetPlanillaId, data.action, false);
        } else if (result.isDenied) {
            @this.call('confirmarCambioPlanilla', data.itemId, data.distribucionId, data.targetPlanillaId, data.action, true);
        } else {
            @this.call('cancelarCambioPlanilla');
        }
    });
});

function imprimirConfirmar() {
    var recaudaciones = document.getElementById('seccion-recaudaciones');
    var planillaCuerpo = document.getElementById('seccion-planilla-cuerpo');

    var contenidoRec = '';
    if (recaudaciones) {
        var tabs = recaudaciones.querySelectorAll('.tab-pane');
        if (tabs.length > 0) {
            tabs.forEach(function(tab) { contenidoRec += tab.innerHTML; });
        } else {
            contenidoRec = recaudaciones.querySelector('.card-body').innerHTML;
        }
    }

    var contenidoPlan = planillaCuerpo ? '<div class="print-planilla">' + planillaCuerpo.querySelector('.card-body').innerHTML + '</div>' : '';

    var ventana = window.open('', '_blank', 'width=800,height=600');
    ventana.document.write('<!DOCTYPE html><html><head><title>Planilla {{ $planilla->numero }}</title>');
    ventana.document.write('<link rel="stylesheet" href="{{ asset('css/app.css') }}">');
    ventana.document.write('<style>');
    ventana.document.write('body{padding:20px;font-family:inherit}table{width:100%}');
    ventana.document.write('.d-print-none,.nav-tabs,.tab-pane.fade{display:none!important}');
    ventana.document.write('.tab-pane.fade.show.active,.tab-pane.fade{display:block!important}');
    ventana.document.write('.card{margin-bottom:1rem;border:1px solid #dee2e6}');
    ventana.document.write('.print-planilla .card{page-break-inside:avoid}');
    ventana.document.write('.card-header{padding:.5rem;background:#f8f9fa;font-weight:700}');
    ventana.document.write('.table{width:100%;border-collapse:collapse}');
    ventana.document.write('.table td,.table th{border:1px solid #dee2e6;padding:.25rem}');
    ventana.document.write('.text-right{text-align:right}.text-center{text-align:center}.text-nowrap{white-space:nowrap}');
    ventana.document.write('.d-flex{display:flex}.justify-content-end{justify-content:flex-end}');
    ventana.document.write('.justify-content-between{justify-content:space-between}');
    ventana.document.write('.align-items-center{align-items:center}.align-middle{vertical-align:middle}');
    ventana.document.write('.font-weight-bold{font-weight:700}.bg-light{background:#f8f9fa}');
    ventana.document.write('.table-active td{background:#f8f9fa}');
    ventana.document.write('.form-control,.close,.btn,.custom-control{display:none}');
    ventana.document.write('.my-3{margin-top:1rem;margin-bottom:1rem}');
    ventana.document.write('.page-break{page-break-before:always}');
    ventana.document.write('@media print{body{padding:0}}');
    ventana.document.write('</style>');
    ventana.document.write('</head><body>');
    ventana.document.write('<h4 style="margin-bottom:1rem">Recaudaciones</h4>');
    ventana.document.write(contenidoRec);
    ventana.document.write('<div class="page-break"></div>');
    ventana.document.write('<h4 style="margin-bottom:1rem">Planilla {{ $planilla->numero }}</h4>');
    ventana.document.write(contenidoPlan);
    ventana.document.write('</body></html>');
    ventana.document.close();
    ventana.focus();
    setTimeout(function() { ventana.print(); ventana.close(); }, 500);
}
</script>
@endpush
