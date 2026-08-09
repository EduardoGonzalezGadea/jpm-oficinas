<div class="container-fluid px-0">
  @section('title', 'Confirmar Planilla — E.R.')

  <div class="card border-primary shadow-sm">
    <div class="card-header bg-primary text-white p-2">
      <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
        <h4 class="card-title px-1 mb-2 mb-lg-0">
          <strong><i class="fas fa-check-double mr-2"></i>Confirmar Planilla {{ $planilla->numero }}</strong>
        </h4>
        <div class="d-flex flex-column flex-sm-row align-items-stretch">
          <a href="{{ route('tesoreria.gestion-cfe.estados-recaudacion') }}" class="btn btn-sm btn-outline-light mb-2 mb-sm-0 mr-sm-2">
            <i class="fas fa-list mr-1"></i>Estados de Recaudación
          </a>
          <a href="{{ route('tesoreria.gestion-cfe.estados-recaudacion.no-confirmadas') }}" class="btn btn-sm btn-light">
            <i class="fas fa-arrow-left mr-1"></i>Volver a No Confirmadas
          </a>
        </div>
      </div>
    </div>

    <div class="card-body p-2 p-md-3">

      {{-- ============================================================ --}}
      {{-- SECCIÓN SUPERIOR: Items por CFE con cambio de Distribución SIIF --}}
      {{-- ============================================================ --}}
      <div class="card mb-4 border-primary">
        <div class="card-header bg-primary text-white py-2">
          <div class="d-flex flex-row align-items-center w-100">
            {{-- Título a la izquierda --}}
            <div class="text-nowrap mr-3 flex-shrink-0">
              <strong><i class="fas fa-tags mr-1"></i>Distribución SIIF por Ítem</strong>
            </div>

            {{-- Barra de progreso en el medio (ocupa espacio disponible) --}}
            @if($estadisticas['total'] > 0)
            <div class="d-flex align-items-center flex-grow-1 mx-3" style="min-width: 80px;">
              <div class="progress w-100">
                <div class="progress-bar font-weight-bold"
                     role="progressbar"
                     style="width: {{ $estadisticas['porcentaje_completado'] }}%;"
                     aria-valuenow="{{ $estadisticas['porcentaje_completado'] }}"
                     aria-valuemin="0"
                     aria-valuemax="100">
                  {{ $estadisticas['confirmados'] }}/{{ $estadisticas['total'] }} ({{ $estadisticas['porcentaje_completado'] }}%)
                </div>
              </div>
            </div>
            @endif

            {{-- Badges a la derecha --}}
            <div class="d-flex align-items-center flex-nowrap ml-auto">
              @php
                $totalItems = $planilla->items->count();
                $itemsConfirmados = $planilla->items->where('confirmado', true)->count();
                $itemsSinDistribucion = $planilla->items->whereNull('siif_distribucion_id')->count();
                $itemsPendientes = $planilla->items->where('confirmado', false)->where('siif_distribucion_id', '!=', null)->count();
              @endphp
              <span class="badge badge-light mr-2" title="Total de ítems" data-toggle="tooltip">
                <i class="fas fa-list mr-1"></i>{{ $totalItems }}
              </span>
              <span class="badge badge-success mr-2" title="Ítems confirmados" data-toggle="tooltip">
                <i class="fas fa-check-circle mr-1"></i>{{ $itemsConfirmados }}
              </span>
              @if($itemsPendientes > 0)
              <span class="badge badge-warning mr-2" title="Pendientes de confirmación" data-toggle="tooltip">
                <i class="fas fa-exclamation-circle mr-1"></i>{{ $itemsPendientes }}
              </span>
              @endif
              @if($itemsSinDistribucion > 0)
              <span class="badge badge-danger mr-2" title="Sin distribución SIIF" data-toggle="tooltip">
                <i class="fas fa-exclamation-triangle mr-1"></i>{{ $itemsSinDistribucion }}
              </span>
              @endif
              <span class="badge badge-light" title="Total general" data-toggle="tooltip">
                $ {{ number_format($totalGeneral, 2, ',', '.') }}
              </span>
            </div>
          </div>
        </div>
        <div class="card-body p-2">
          <div class="card card-body py-2 px-2 mb-2 border border-secondary">
            <div class="form-row align-items-center">
              <div class="col-12 col-lg mb-2 mb-lg-0">
              <div class="input-group input-group-sm">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
                <input type="text" class="form-control" placeholder="Buscar por receptor o documento..."
                  wire:model.live.debounce.300ms="busqueda">
              </div>
              </div>
              <div class="col-12 col-md-6 col-lg-3 mb-2 mb-lg-0">
              <select class="form-control form-control-sm"
                wire:model.live="filtroDistribucion">
                <option value="">Todas las distribuciones</option>
                @foreach($distribucionesPlanilla as $distConcepto)
                  <option value="{{ $distConcepto }}">{{ $distConcepto }}</option>
                @endforeach
              </select>
              </div>
              <div class="col-12 col-md-6 col-lg-auto mb-2 mb-lg-0">
              <button class="btn btn-sm btn-block {{ $modoRevisionRapida ? 'btn-warning' : 'btn-outline-warning' }} text-nowrap"
                wire:click="toggleModoRevisionRapida"
                title="Mostrar solo ítems sin confirmar">
                <i class="fas fa-filter mr-1"></i>{{ $modoRevisionRapida ? 'Mostrando sin confirmar' : 'Revisión rápida' }}
              </button>
              </div>
              <div class="col-12 col-lg-auto">
              <button class="btn btn-sm btn-secondary btn-block text-nowrap" wire:click="limpiarFiltros">
                <i class="fas fa-times"></i> Limpiar
              </button>
              </div>
            </div>
          </div>

          {{-- Leyenda de estados --}}
          <div class="card card-body py-2 px-3 mb-2 border">
            <div class="d-flex align-items-center justify-content-center flex-wrap small">
              <span class="mr-3"><strong>Leyenda:</strong></span>
              <span class="mr-3">
                <i class="fas fa-check-circle text-success"></i> Configurado correctamente
              </span>
              <span class="mr-3">
                <i class="fas fa-exclamation-circle text-warning"></i> Pendiente de confirmación
              </span>
              <span class="mr-3">
                <i class="fas fa-exclamation-triangle text-danger"></i> Sin distribución SIIF
              </span>
            </div>
          </div>

          {{-- Alertas de coherencia en distribuciones --}}
          @if(!empty($alertasCoherencia))
          <div class="alert alert-warning py-2 mb-2" role="alert">
            <div class="d-flex align-items-start">
              <i class="fas fa-exclamation-triangle mr-2 mt-1"></i>
              <div class="flex-grow-1">
                <strong>Advertencias de Coherencia en Distribuciones:</strong>
                <ul class="mb-0 mt-1 small">
                  @foreach($alertasCoherencia as $alerta)
                  <li>
                    <strong>{{ $alerta['cfe']->documento_tipo }} {{ $alerta['cfe']->documento_serie }}-{{ $alerta['cfe']->documento_numero }}:</strong>
                    {{ $alerta['detalle'] }}
                  </li>
                  @endforeach
                </ul>
                <p class="small mb-0 mt-1">
                  <i class="fas fa-info-circle mr-1"></i>
                  Las diferencias menores (±$2) son normales por redondeos. Estas advertencias no impiden la confirmación.
                </p>
              </div>
            </div>
          </div>
          @endif

          @forelse($itemsPorCfe as $cfeLabel => $items)
            @php $primerCfe = $items->first()->cfe; @endphp
            <div class="card mb-2 border">
              <div class="card-header py-2 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                <strong><i class="fas fa-file-invoice mr-1"></i>{{ $cfeLabel }}</strong>
                <div class="d-flex align-items-center mt-2 mt-md-0">
                  @if($items->where('enPlanilla', true)->isNotEmpty())
                  <div class="custom-control custom-switch d-inline-block mr-3">
                    <input type="checkbox" class="custom-control-input" id="cfeMaster_{{ $primerCfe->id }}"
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
              <div class="px-3 py-2 small border-bottom d-flex flex-wrap">
                <span class="mr-3">
                  <strong>Receptor:</strong> {{ $primerCfe->receptor_nombre_denominacion ?? '—' }}
                  @if($primerCfe->receptor_documento_ruc)
                    <small class="text-muted">({{ $primerCfe->receptor_documento_ruc }})</small>
                  @endif
                </span>
                <span class="mr-3"><strong>Fecha:</strong> {{ $primerCfe->fecha?->format('d/m/Y') ?? '—' }}</span>
                <span><strong>Total a pagar:</strong> $ {{ number_format($primerCfe->total_a_pagar ?? 0, 2, ',', '.') }}</span>
              </div>
              @endif
              @php
                $referencias = $primerCfe->referencias ?? null;
                $adenda = $primerCfe->adenda ?? null;
              @endphp
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-sm table-bordered mb-0">
                    <thead class="thead-light">
                      <tr>
                        <th>Detalle</th>
                        <th class="text-right text-nowrap">Cantidad</th>
                        <th class="text-right text-nowrap">Precio</th>
                        <th class="text-right text-nowrap">Importe</th>
                        <th>Distribución SIIF</th>
                        <th class="text-center text-nowrap">Conf.</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($items as $item)
                        @php
                          $estado = $this->getEstadoItem($item);
                          $rowClass = $item->enPlanilla ? "border-left border-{$estado['color']}" : 'text-secondary';
                        @endphp
                        <tr class="{{ $rowClass }}">
                          <td class="align-middle small">
                            @if(!$item->enPlanilla)
                              <i class="fas fa-minus-circle mr-1 text-secondary" title="No integra esta planilla"></i>
                            @else
                              <i class="fas fa-{{ $estado['icono'] }} mr-1 text-{{ $estado['color'] }}"
                                 title="{{ $estado['mensaje'] }}"
                                 data-toggle="tooltip"
                                 data-placement="right"></i>
                            @endif
                            {{ $item->detalle }}
                            @if($item->enPlanilla && !empty($estado['problemas']))
                              <span class="badge badge-{{ $estado['color'] }} badge-pill ml-1"
                                    data-toggle="tooltip"
                                    title="{{ implode(', ', $estado['problemas']) }}">
                                <i class="fas fa-info-circle"></i>
                              </span>
                            @endif
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
                                  $key = ($cfe->cajaConcepto?->siif_distribucion_tipo_id ?? 'X') . '-' . ($cfe->siif_distribucion_dependencia_id ?? 'X');
                                  $opciones = $opcionesPorTipoDep[$key] ?? [];
                                @endphp
                                @foreach($opciones as $opt)
                                  <option value="{{ $opt->id }}"
                                          {{ $item->siif_distribucion_id == $opt->id ? 'selected' : '' }}
                                          data-toggle="tooltip"
                                          title="Recurso: {{ $opt->recurso ?? 'N/A' }} | Concepto: {{ $opt->concepto ?? $opt->distribucion }} | Financ: {{ $opt->financiacion ?? 'N/A' }} | Inciso: {{ $opt->inciso ?? 'N/A' }} | U.E.: {{ $opt->unidad_ejecutora ?? 'N/A' }}">
                                    {{ $opt->distribucion }}
                                  </option>
                                @endforeach
                              </select>
                            @else
                              <span class="small">{{ $item->siifDistribucion?->distribucion ?? '—' }}</span>
                            @endif
                          </td>
                          <td class="align-middle text-center">
                            @if($item->enPlanilla)
                              <div class="custom-control custom-switch d-inline-block">
                                <input type="checkbox" class="custom-control-input" id="itemConfirmado_{{ $item->id }}"
                                  wire:click="toggleItemConfirmado({{ $item->id }})"
                                  {{ $item->confirmado ? 'checked' : '' }}>
                                <label class="custom-control-label" for="itemConfirmado_{{ $item->id }}">
                                  <span class="small {{ $item->confirmado ? 'text-success font-weight-bold' : 'text-secondary' }}">
                                    {{ $item->confirmado ? 'Sí' : 'No' }}
                                  </span>
                                </label>
                              </div>
                            @else
                              <span class="small text-secondary">—</span>
                            @endif
                          </td>
                        </tr>
                        @if($item->descripcion)
                        <tr class="{{ $item->enPlanilla ? '' : 'text-secondary' }}">
                          <td class="pl-3 py-1 small border-top-0 text-nowrap"><strong>DESC.:</strong></td>
                          <td colspan="5" class="py-1 small border-top-0 font-italic">
                            {{ $item->descripcion }}
                          </td>
                        </tr>
                        @endif
                      @endforeach
                    </tbody>
                  </table>
                </div>
                  @if($referencias || $adenda)
                  <div class="px-3 py-2 border-top small">
                    @if($referencias)
                      <div class="mb-1">
                        <strong><i class="fas fa-hashtag mr-1 text-secondary"></i>Referencias:</strong>
                        <span>{{ $referencias }}</span>
                      </div>
                    @endif
                    @if($adenda)
                      <div>
                        <strong><i class="fas fa-paperclip mr-1 text-secondary"></i>Adenda:</strong>
                        <span>{{ $adenda }}</span>
                      </div>
                    @endif
                  </div>
                  @endif
                  @if(!$loop->last)
                  <hr class="my-2 border-secondary">
                  @endif
              </div>
            </div>
          @empty
            <p class="text-center py-3 mb-0">No hay ítems asociados a esta planilla.</p>
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
            <ul class="nav nav-tabs flex-column flex-sm-row" id="confirmarRecaudacionesTab" role="tablist">
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
            <div class="tab-content border border-top-0 p-2 p-md-3" id="confirmarRecaudacionesTabContent">
              @foreach($gruposRecaudacion as $key => $grupo)
                @if($grupo['total_efectivo'] + $grupo['total_cheque'] + $grupo['total_transferencia'] + $grupo['total_pos'] > 0)
                  <div class="tab-pane fade {{ $primerActivo ? 'show active' : '' }}"
                    id="ar-content-{{ \Illuminate\Support\Str::slug($key) }}" role="tabpanel"
                    aria-labelledby="ar-tab-{{ \Illuminate\Support\Str::slug($key) }}">
                    @php $primerActivo = false; @endphp

                    @foreach($grupo['distribuciones'] as $distKey => $distribucion)
                      @if(!empty($distribucion['items']))
                        <div class="card mb-3">
                          <div class="card-header py-1 px-2 text-center">
                            <strong>{{ $distribucion['distribucion'] }}</strong>
                            @php $docsStr = $this->formatRangoDocumentos($distribucion['items']); @endphp
                            @if($docsStr)
                              <br><small>{{ $docsStr }}</small>
                            @endif
                          </div>
                          <div class="card-body p-0">
                            <div class="table-responsive">
                                  <table class="table table-sm table-bordered mb-0 border-top-0">
                                    <thead class="thead-light">
                                      <tr>
                                        <th class="text-nowrap align-middle">Recibo</th>
                                        <th class="text-nowrap align-middle">Concepto</th>
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
                                          <td class="align-middle small">
                                            {{ $rowData['concepto'] }}
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
                                        <td colspan="2" class="text-right font-weight-bold small align-middle">Subtotal {{ $distribucion['distribucion'] }}:</td>
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
                    <div class="table-responsive py-2 px-3 border rounded">
                      <table class="table table-sm table-borderless mb-0 text-right w-auto ml-auto">
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
            <p class="text-center py-3 mb-0">No hay medios de pago registrados para esta planilla.</p>
          @endif
        </div>
      </div>

      {{-- ============================================================ --}}
      {{-- SECCIÓN INFERIOR: Cuerpo de la planilla con cálculos y switch --}}
      {{-- ============================================================ --}}
      <div class="card border-warning" id="seccion-planilla-cuerpo">
        <div class="card-header bg-warning py-1">
          <strong><i class="fas fa-file-alt mr-1"></i>Planilla {{ $planilla->numero }}</strong>
        </div>
        <div class="card-body p-3">

          @if($planilla->trashed())
          <div class="alert alert-danger py-2 mb-3">
            <strong><i class="fas fa-ban mr-1"></i>PLANILLA ANULADA</strong><br>
            <span>Motivo: {{ $planilla->motivo_anulacion }}</span>
          </div>
          @endif

          <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start mb-3">
            <div class="small mb-2 mb-sm-0">
              <strong>JEFATURA DE POLICÍA DE MONTEVIDEO</strong><br>
              <strong>DIRECCIÓN DE TESORERÍA</strong>
            </div>
            <div class="text-sm-right small">
              <strong>PLANILLA: {{ $planilla->numero }}</strong><br>
              <strong>FECHA: {{ $planilla->fecha?->format('d/m/Y') }}</strong>
            </div>
          </div>
          <div class="text-center mb-3">
            <strong>PLANILLA PARA ESTADO DE RECAUDACIÓN</strong>
          </div>

          @php
            $itemsPorDistribucion = $planilla->items->sortBy('siif_distribucion_id')->groupBy(function($item) {
              return $item->siifDistribucion?->distribucion ?? 'Sin distribución';
            });
            $totalGeneralAjustado = 0;
          @endphp

          @forelse($itemsPorDistribucion as $distribucion => $itemsDist)
            @php
              $grupoTotal = $itemsDist->sum('importe');
              $grupoTotalAjustado = $grupoTotal;
            @endphp
            <div class="card mb-3">
              <div class="card-header py-1 px-2 border-bottom-0 d-flex align-items-center justify-content-center">
                <strong>{{ $distribucion }}</strong>
              </div>
              <div class="card-body p-0">
                @if($distribucion !== 'Sin distribución' && $itemsDist->first()->siifDistribucion)
                  @php
                    $primerItem = $itemsDist->first();
                    $distribuciones = \App\Models\Tesoreria\SiifDistribucion::where('tipo_id', $primerItem->siifDistribucion->tipo_id)
                      ->where('dependencia_id', $primerItem->siifDistribucion->dependencia_id)
                      ->where('distribucion', $distribucion)
                      ->whereNull('deleted_at')
                      ->get();
                  @endphp

                  @if($distribuciones->isNotEmpty())
                    <div class="table-responsive">
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
                            return ($d->recurso ?? '—') . '|' . ($d->financiacion ?? '—') . '|' . ($d->inciso ?? '—') . '|' . ($d->unidad_ejecutora ?? '—') . '|' . ($d->porcentaje ?? '0');
                          })->map(function($grupo) use ($grupoTotal) {
                            $primer = $grupo->first();
                            return (object) [
                              'recurso' => $primer->recurso,
                              'distribucion' => $primer->distribucion,
                              'porcentaje' => $primer->porcentaje,
                              'financiacion' => $primer->financiacion,
                              'inciso' => $primer->inciso,
                              'unidad_ejecutora' => $primer->unidad_ejecutora,
                              'importe_raw' => $grupoTotal * ($primer->porcentaje / 100),
                            ];
                          });

                          $distFinal = $distGrupos->groupBy(function($dg) {
                            return ($dg->recurso ?? '—') . '|' . ($dg->financiacion ?? '—') . '|' . ($dg->inciso ?? '—') . '|' . ($dg->unidad_ejecutora ?? '—');
                          })->map(function($grupo) {
                            $sumaPorc = $grupo->sum('porcentaje');
                            $importeRaw = $grupo->sum('importe_raw');
                            $primer = $grupo->first();
                            return (object) [
                              'recurso' => $primer->recurso,
                              'distribucion' => $primer->distribucion,
                              'porcentaje' => $sumaPorc,
                              'financiacion' => $primer->financiacion,
                              'inciso' => $primer->inciso,
                              'unidad_ejecutora' => $primer->unidad_ejecutora,
                              'importe_raw' => $importeRaw,
                            ];
                          });

                          $sumaRedondeada = 0;
                          foreach ($distFinal as $dg) {
                            $dg->importe = round($dg->importe_raw, 0);
                            $sumaRedondeada += $dg->importe;
                          }

                          $diferencia = round($grupoTotal - $sumaRedondeada, 0);

                          if ($diferencia != 0) {
                            $compensado = false;
                            foreach ($distFinal as $dg) {
                              if ($dg->unidad_ejecutora == '4' && $dg->inciso == '1') {
                                $dg->importe = round($dg->importe + $diferencia, 0);
                                $compensado = true;
                                break;
                              }
                            }
                            if (!$compensado) {
                              $dg = $distFinal->first();
                              if ($dg) {
                                $dg->importe = round($dg->importe + $diferencia, 0);
                              }
                            }
                          }

                          $grupoTotalAjustado = $distFinal->sum('importe');
                        @endphp
                        @foreach($distFinal as $dg)
                          <tr>
                            <td class="align-middle">{{ is_numeric($dg->recurso) ? number_format((int)$dg->recurso, 0, ',', '.') : ($dg->recurso ?? '—') }}</td>
                            <td class="align-middle">{{ $dg->distribucion }}</td>
                            <td class="align-middle text-right">{{ rtrim(rtrim(number_format($dg->porcentaje, 3, ',', '.'), '0'), ',') }}%</td>
                            <td class="align-middle text-center">{{ $dg->financiacion ?? '—' }}</td>
                            <td class="align-middle text-center">{{ $dg->inciso ?? '—' }}</td>
                            <td class="align-middle text-center">{{ $dg->unidad_ejecutora ?? '—' }}</td>
                            <td class="align-middle text-right text-nowrap">$&nbsp;{{ number_format($dg->importe, 2, ',', '.') }}</td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                    </div>
                  @endif
                @endif

                <div class="d-flex justify-content-end align-items-center py-2 px-3 border-top">
                  <div>
                    <strong>Total {{ $distribucion }}:</strong> $ {{ number_format($grupoTotalAjustado, 2, ',', '.') }}
                  </div>
                </div>
                @php $totalGeneralAjustado += $grupoTotalAjustado; @endphp
              </div>
            </div>
          @empty
            <p class="text-center py-4">No hay ítems asociados a esta planilla.</p>
          @endforelse

          <div class="d-flex justify-content-end py-2 px-3 my-3 border rounded">
            <strong>TOTAL GENERAL:</strong>&nbsp;$ {{ number_format($totalGeneralAjustado, 2, ',', '.') }}
          </div>

          <div class="table-responsive">
          <table class="table table-sm table-bordered mt-3 mb-0">
            <tbody>
              <tr>
                <td class="align-middle small text-nowrap font-weight-bold w-25">Estado de recaudación Nro.</td>
                <td class="align-middle small">{{ $planilla->er_numero ?? '—' }}</td>
              </tr>
              <tr>
                <td class="align-middle small text-nowrap font-weight-bold w-25">Nros. Egresos (rubro 100.99/800.9)</td>
                <td class="align-middle small">{{ $planilla->egresos_numero ?? '—' }}</td>
              </tr>
              <tr>
                <td class="align-middle small text-nowrap font-weight-bold w-25">Nros. Ingresos</td>
                <td class="align-middle small">{{ $planilla->ingresos_numero ?? '—' }}</td>
              </tr>
              <tr>
                <td class="align-middle small text-nowrap font-weight-bold w-25">Fecha de transferencia</td>
                <td class="align-middle small">{{ $planilla->transferencia_fecha?->format('d/m/Y') ?? '—' }}</td>
              </tr>
              <tr>
                <td class="align-middle small text-nowrap font-weight-bold w-25">Transf. Confirmación</td>
                <td class="align-middle small">{{ $planilla->transferencia_confirmacion ?? '—' }}</td>
              </tr>
            </tbody>
          </table>
          </div>

          <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between border-top pt-3 mt-3">
            @can('tesoreria.supervisar')
              <div class="d-flex flex-wrap align-items-center mb-3 mb-md-0">
                <div class="custom-control custom-switch d-inline-block">
                  <input type="checkbox" class="custom-control-input" id="switchConfirmar"
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
              <button type="button" class="btn btn-primary btn-sm align-self-stretch align-self-md-auto" onclick="imprimirConfirmar()">
                <i class="fas fa-print mr-1"></i> Imprimir
              </button>
              @else
              <button type="button" class="btn btn-danger btn-sm align-self-stretch align-self-md-auto"
                onclick="anularPlanilla()">
                <i class="fas fa-ban mr-1"></i> Anular esta planilla para E.R.
              </button>
              @endif
            @else
              <p class="mb-0">
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
// Inicializar tooltips de Bootstrap
$(document).ready(function() {
  $('[data-toggle="tooltip"]').tooltip();
});

// Reinicializar tooltips después de cada actualización de Livewire v3
document.addEventListener('livewire:init', function () {
  Livewire.hook('commit', ({ succeed }) => {
    succeed(() => {
      queueMicrotask(() => $('[data-toggle="tooltip"]').tooltip());
    });
  });
});

function anularPlanilla() {
  Swal.fire({
    title: '¿Anular planilla?',
    text: 'Los ítems quedarán disponibles para futuras planillas. Se preservará una copia de la planilla actual.',
    icon: 'warning',
    input: 'textarea',
    inputLabel: 'Motivo de la anulación',
    inputPlaceholder: 'Ingrese el motivo...',
    inputValidator: (value) => {
      if (!value) return 'Debe ingresar un motivo';
    },
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sí, anular',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      @this.call('anularPlanilla', {{ $planilla->id }}, result.value);
    }
  });
}

window.addEventListener('swal:previsualizar-cambio', event => {
  const data = window.LiveEvent(event);
  Swal.fire({
    title: 'Previsualización de Cambio',
    html: `
      <div class="text-left">
        <p><strong>Ítem:</strong> ${data.itemDetalle}</p>
        <hr>
        <div class="mb-2">
          <strong>Distribución Actual:</strong><br>
          <span class="badge badge-secondary">${data.distribucionAnterior}</span>
        </div>
        <div class="text-center my-2">
          <i class="fas fa-arrow-down fa-2x text-primary"></i>
        </div>
        <div>
          <strong>Nueva Distribución:</strong><br>
          <span class="badge badge-primary">${data.distribucionNueva}</span>
        </div>
      </div>
    `,
    icon: 'info',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#6c757d',
    confirmButtonText: '<i class="fas fa-check mr-1"></i>Aplicar Cambio',
    cancelButtonText: '<i class="fas fa-times mr-1"></i>Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      @this.call('cambiarDistribucion', data.itemId, data.nuevoSiifDistribucionId);
    }
  });
});

window.addEventListener('swal:confirmar-cambio-planilla', event => {
  const data = window.LiveEvent(event);
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

window.addEventListener('swal:confirmar-con-advertencias', event => {
  const data = window.LiveEvent(event);
  Swal.fire({
    title: data.title,
    html: data.html,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ffc107',
    cancelButtonColor: '#6c757d',
    confirmButtonText: '<i class="fas fa-check mr-1"></i>Sí, confirmar de todos modos',
    cancelButtonText: '<i class="fas fa-times mr-1"></i>Cancelar',
    customClass: {
      confirmButton: 'btn btn-warning',
      cancelButton: 'btn btn-secondary'
    },
    buttonsStyling: false
  }).then((result) => {
    if (result.isConfirmed) {
      @this.call('confirmarConAdvertencias');
    }
  });
});

window.addEventListener('swal:error', event => {
  const data = window.LiveEvent(event);

  Swal.fire({
    title: data.title || 'Error',
    text: data.text || 'Ocurrió un error',
    icon: 'error',
    confirmButtonColor: '#d33',
    confirmButtonText: 'Entendido',
    width: '600px',
    allowOutsideClick: false
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
  ventana.document.write('<\/head><body>');
  ventana.document.write('<h4 style="margin-bottom:1rem">Recaudaciones</h4>');
  ventana.document.write(contenidoRec);
  ventana.document.write('<div class="page-break"></div>');
  ventana.document.write('<h4 style="margin-bottom:1rem">Planilla {{ $planilla->numero }}</h4>');
  ventana.document.write(contenidoPlan);
  ventana.document.write('<\/body><\/html>');
  ventana.document.close();
  ventana.focus();
  setTimeout(function() { ventana.print(); ventana.close(); }, 500);
}
</script>
@endpush
