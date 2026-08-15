<div class="container-fluid px-0">
  @section('title', 'Resumen de Recaudaciones')

  <style>
    .nav-tabs {
      border-bottom: 2px solid #dee2e6;
    }
    .nav-tabs .nav-link {
      border: 2px solid transparent;
      border-top-left-radius: 6px;
      border-top-right-radius: 6px;
      margin-bottom: -2px;
      font-weight: 500;
    }
    .nav-tabs .nav-link.active {
      border-left: 2px solid #adb5bd;
      border-right: 2px solid #adb5bd;
      border-top: 3px solid #17a2b8;
      border-bottom-color: transparent;
      font-weight: 600;
    }
    html.dark-theme .nav-tabs {
      border-bottom-color: rgba(255,255,255,.15);
    }
    html.dark-theme .nav-tabs .nav-link.active {
      border-left-color: rgba(255,255,255,.2);
      border-right-color: rgba(255,255,255,.2);
      border-top-color: #5bc0de;
      border-bottom-color: transparent;
    }
  </style>

  <div class="card">
    <div class="card-header card-header-section card-header-gradient py-2 px-3">
      <div class="d-flex justify-content-between align-items-center w-100">
        <h4 class="mb-0 text-premium-header">
          <i class="fas fa-chart-pie mr-2"></i>Resumen de Recaudaciones
        </h4>
        <div class="d-flex align-items-center">
          <button type="button" class="btn btn-light btn-sm" onclick="imprimirResumenRecaudaciones()" title="Imprimir">
            <i class="fas fa-print mr-1"></i> Imprimir
          </button>
        </div>
      </div>
    </div>
    <div class="card-body">
      <div class="form-row align-items-end mb-3">
        <div class="col-12 col-md-6 col-lg mb-2 mb-lg-0">
          <div class="input-group input-group-sm">
            <div class="input-group-prepend">
              <span class="input-group-text"><i class="fas fa-search"></i></span>
            </div>
            <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
              placeholder="Buscar doc, receptor, adenda...">
            <div class="input-group-append">
              <button class="btn btn-outline-secondary" wire:click="resetearBusqueda" title="Resetear búsqueda">
                <i class="fas fa-undo"></i>
              </button>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2 mb-2 mb-lg-0">
          <select wire:model.live="dependencia_id" wire:change="$refresh" class="form-control form-control-sm">
            <option value="">Todas las dependencias</option>
            @foreach($this->opcionesDependencias as $dep)
              <option value="{{ $dep->id }}">{{ $dep->abreviatura ?? $dep->dependencia }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-4 col-lg-2 mb-2 mb-lg-0">
          <select wire:model.live="tipo_id" wire:change="$refresh" class="form-control form-control-sm">
            <option value="">Todos los tipos</option>
            @foreach($this->opcionesTipos as $tipo)
              <option value="{{ $tipo->id }}">{{ $tipo->tipo }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-4 col-lg-1 mb-2 mb-lg-0">
          <input type="number" step="0.01" wire:model.live="monto_desde" wire:change="$refresh" class="form-control form-control-sm" placeholder="Monto desde">
        </div>
        <div class="col-6 col-md-4 col-lg-1 mb-2 mb-lg-0">
          <input type="number" step="0.01" wire:model.live="monto_hasta" wire:change="$refresh" class="form-control form-control-sm" placeholder="Monto hasta">
        </div>
        <div class="col-6 col-md-4 col-lg-auto mb-2 mb-lg-0">
          <div class="dropdown" id="dropdownMesesWrapper" wire:ignore.self>
            <button class="btn btn-sm btn-outline-secondary border dropdown-toggle text-left d-flex justify-content-between align-items-center w-100" type="button" id="dropdownMeses" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <span class="text-truncate small mr-1">
                @if(empty($filtroMeses))
                  Todos los meses
                @else
                  {{ count($filtroMeses) }} {{ count($filtroMeses) === 1 ? 'mes' : 'meses' }}
                @endif
              </span>
            </button>
            <div class="dropdown-menu dropdown-menu-right p-3" aria-labelledby="dropdownMeses" style="min-width: 240px; max-height: 350px; overflow-y: auto;" onclick="event.stopPropagation()" wire:ignore.self>
              <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                <span class="font-weight-bold small">Meses del año</span>
                <a href="#" wire:click.prevent="limpiarFiltroMeses" class="small font-weight-bold text-danger">Limpiar</a>
              </div>
              @php
                $mesesNombres = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
              @endphp
              @foreach($mesesNombres as $num => $nombre)
                <div class="custom-control custom-checkbox mb-2">
                  <input type="checkbox" id="mes_{{ $num }}" value="{{ $num }}" wire:model.live="filtroMeses" class="custom-control-input">
                  <label for="mes_{{ $num }}" class="custom-control-label small w-100">{{ $nombre }}</label>
                </div>
              @endforeach
            </div>
          </div>
        </div>
        <div class="col-6 col-md-2 col-lg-1 mb-2 mb-lg-0">
          <select wire:model.live="filtroAno" class="form-control form-control-sm">
            <option value="0">— Todos —</option>
            @foreach($anosRegistrados as $ano)
              <option value="{{ $ano }}">{{ $ano }}</option>
            @endforeach
          </select>
        </div>

      </div>

      @php
        $tabsConDatos = collect($grupos)->filter(fn($g) => $g['total_efectivo'] + $g['total_cheque'] + $g['total_transferencia'] + $g['total_pos'] > 0);
      @endphp

      @if($tabsConDatos->isNotEmpty())
        @php $primerActivo = true; @endphp
        <ul class="nav nav-tabs mb-0" id="resumenRecaudacionesTab" role="tablist">
          @foreach($grupos as $key => $grupo)
            @if($grupo['total_efectivo'] + $grupo['total_cheque'] + $grupo['total_transferencia'] + $grupo['total_pos'] > 0)
              <li class="nav-item">
                <a class="nav-link small {{ $primerActivo ? 'active' : '' }}"
                  id="tab-{{ \Illuminate\Support\Str::slug($key) }}" data-toggle="tab"
                  href="#content-{{ \Illuminate\Support\Str::slug($key) }}"
                  role="tab" aria-controls="content-{{ \Illuminate\Support\Str::slug($key) }}"
                  aria-selected="{{ $primerActivo ? 'true' : 'false' }}">
                  {{ $grupo['label'] }}
                </a>
              </li>
              @php $primerActivo = false; @endphp
            @endif
          @endforeach
        </ul>
        <hr class="mt-0 mb-3 border-secondary">
        @php $primerActivo = true; @endphp
        <div class="tab-content p-3" id="resumenRecaudacionesTabContent">
          @foreach($grupos as $key => $grupo)
            @if($grupo['total_efectivo'] + $grupo['total_cheque'] + $grupo['total_transferencia'] + $grupo['total_pos'] > 0)
              <div class="tab-pane fade {{ $primerActivo ? 'show active' : '' }}"
                id="content-{{ \Illuminate\Support\Str::slug($key) }}" role="tabpanel"
                aria-labelledby="tab-{{ \Illuminate\Support\Str::slug($key) }}">
                @php $primerActivo = false; @endphp

                @foreach($grupo['fechas'] as $fechaKey => $fecha)
                  <div class="card mb-3">
                    <div class="card-header bg-info text-white py-1 px-2">
                      <div class="d-flex justify-content-between align-items-center">
                        <span><i class="far fa-calendar-alt mr-1"></i> {{ $fechaKey !== 'sin-fecha' ? \Carbon\Carbon::parse($fechaKey)->format('d/m/Y') : 'Sin fecha' }}</span>
                        <span>Total del día: $ {{ number_format($fecha['total_efectivo'] + $fecha['total_cheque'] + $fecha['total_transferencia'] + $fecha['total_pos'], 2, ',', '.') }}</span>
                      </div>
                    </div>
                    <div class="card-body p-2">
                      @foreach($fecha['distribuciones'] as $distKey => $distribucion)
                        @if(!empty($distribucion['items']))
                          <div class="table-responsive">
                           <table class="table table-sm table-bordered mt-3 mb-2">
                            <thead>
                               <tr>
                                 <th colspan="6" class="text-center py-1 font-weight-bold text-break">
                                   {{ $distribucion['distribucion'] }}
                                 </th>
                               </tr>
                              <tr class="thead-light">
                                <th class="align-middle">Recibo</th>
                                <th class="align-middle">Receptor</th>
                                <th class="text-right align-middle">Efectivo</th>
                                <th class="text-right align-middle">Cheque</th>
                                <th class="text-right align-middle">Transferencia</th>
                                <th class="text-right align-middle">POS</th>
                              </tr>
                            </thead>
                            <tbody>
                              @foreach($distribucion['items'] as $rowData)
                                <tr>
                                  <td class="align-middle small">
                                    {{ $rowData['cfe']->documento_tipo }} {{ $rowData['cfe']->documento_serie }}-{{ $rowData['cfe']->documento_numero }}
                                  </td>
                                  <td class="align-middle small">
                                    {{ $rowData['cfe']->receptor_nombre_denominacion ?? '—' }}
                                    @if(!empty($rowData['cfe']->receptor_documento_ruc))
                                      <small class="d-block text-muted">{{ $rowData['cfe']->receptor_documento_ruc }}</small>
                                    @endif
                                  </td>
                                  <td class="align-middle small text-right">
                                    $ {{ number_format($rowData['efectivo'], 2, ',', '.') }}
                                  </td>
                                  <td class="align-middle small text-right">
                                    $ {{ number_format($rowData['cheque'], 2, ',', '.') }}
                                  </td>
                                  <td class="align-middle small text-right">
                                    $ {{ number_format($rowData['transferencia'], 2, ',', '.') }}
                                  </td>
                                  <td class="align-middle small text-right">
                                    $ {{ number_format($rowData['pos'], 2, ',', '.') }}
                                  </td>
                                </tr>
                              @endforeach
                            </tbody>
                            <tfoot class="table-active">
                              <tr>
                                <td colspan="2" class="text-right font-weight-bold small align-middle text-break">Subtotal {{ $distribucion['distribucion'] }}:</td>
                                <td class="text-right font-weight-bold small align-middle">$ {{ number_format($distribucion['total_efectivo'], 2, ',', '.') }}</td>
                                <td class="text-right font-weight-bold small align-middle">$ {{ number_format($distribucion['total_cheque'], 2, ',', '.') }}</td>
                                <td class="text-right font-weight-bold small align-middle">$ {{ number_format($distribucion['total_transferencia'], 2, ',', '.') }}</td>
                                <td class="text-right font-weight-bold small align-middle">$ {{ number_format($distribucion['total_pos'], 2, ',', '.') }}</td>
                              </tr>
                            </tfoot>
                           </table>
                          </div>
                        @endif
                      @endforeach
                    </div>
                  </div>
                @endforeach

                @php
                  $totalGrupo = $grupo['total_efectivo'] + $grupo['total_cheque'] + $grupo['total_transferencia'] + $grupo['total_pos'];
                @endphp
                <div class="d-flex justify-content-end py-2 px-3">
                  <div class="table-responsive">
                  <table class="table table-sm table-borderless mb-0 text-right w-auto ml-auto">
                    <thead>
                      <tr>
                        <th class="text-center align-middle small font-weight-bold">TOTALES GENERALES</th>
                        <th class="small font-weight-bold text-nowrap px-2">Efectivo</th>
                        <th class="small font-weight-bold text-nowrap px-2">Cheque</th>
                        <th class="small font-weight-bold text-nowrap px-2">Transferencia</th>
                        <th class="small font-weight-bold text-nowrap px-2">POS</th>
                        <th class="small font-weight-bold text-nowrap pl-3">Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td class="font-weight-bold align-middle pr-3">TOTAL {{ mb_strtoupper($grupo['label']) }}:</td>
                        <td class="font-weight-bold small text-nowrap align-middle px-2">$ {{ number_format($grupo['total_efectivo'], 2, ',', '.') }}</td>
                        <td class="font-weight-bold small text-nowrap align-middle px-2">$ {{ number_format($grupo['total_cheque'], 2, ',', '.') }}</td>
                        <td class="font-weight-bold small text-nowrap align-middle px-2">$ {{ number_format($grupo['total_transferencia'], 2, ',', '.') }}</td>
                        <td class="font-weight-bold small text-nowrap align-middle px-2">$ {{ number_format($grupo['total_pos'], 2, ',', '.') }}</td>
                        <td class="font-weight-bold small text-nowrap align-middle pl-3">$ {{ number_format($totalGrupo, 2, ',', '.') }}</td>
                      </tr>
                    </tbody>
                  </table>
                  </div>
                </div>
              </div>
            @endif
          @endforeach
        </div>
      @else
        <p class="text-center py-4">No hay recaudaciones para los filtros seleccionados.</p>
      @endif
    </div>
  </div>
</div>

@push('scripts')
  <script>
    function imprimirResumenRecaudaciones() {
      var tabActivo = document.querySelector('#resumenRecaudacionesTab .nav-link.active');
      if (!tabActivo) return;

      var targetId = tabActivo.getAttribute('href');
      var contenido = document.querySelector(targetId).innerHTML;

      var ventana = window.open('', '_blank', 'width=800,height=600');
      ventana.document.write('<!DOCTYPE html><html><head><title>Resumen de Recaudaciones</title>');
      ventana.document.write('<link rel="stylesheet" href="{{ asset('css/app.css') }}">');
      ventana.document.write('<style>');
      ventana.document.write('body{padding:20px;font-family:inherit}table{width:100%}');
      ventana.document.write('.d-print-none,.nav-tabs,.tab-pane.fade{display:none!important}');
      ventana.document.write('.tab-pane.fade.show.active{display:block!important}');
      ventana.document.write('.card{margin-bottom:1rem;border:1px solid #dee2e6}');
      ventana.document.write('.card-header,.table tr{page-break-inside:avoid}');
      ventana.document.write('.card-header{padding:.5rem;background:#f8f9fa;font-weight:700}');
      ventana.document.write('.card-header svg[data-icon="calendar-alt"]{width:1em!important;height:1em!important;vertical-align:middle!important}');
      ventana.document.write('.table{width:100%;border-collapse:collapse}');
      ventana.document.write('.table td,.table th{border:1px solid #dee2e6;padding:.25rem}');
      ventana.document.write('.text-right{text-align:right}.text-nowrap{white-space:nowrap}');
      ventana.document.write('.d-flex{display:flex}.justify-content-end{justify-content:flex-end}');
      ventana.document.write('.align-middle{vertical-align:middle}');
      ventana.document.write('.font-weight-bold{font-weight:700}');
      ventana.document.write('.bg-light{background:#f8f9fa}');
      ventana.document.write('.table-active td{background:#f8f9fa}');
      ventana.document.write('.card-body,.card-body *{color:#000!important}');
      ventana.document.write('.form-control{display:none}');
      ventana.document.write('</style>');
      ventana.document.write('<\/head><body>');
      ventana.document.write('<h4 style="margin-bottom:1rem">Resumen de Recaudaciones</h4>');
      ventana.document.write(contenido);
      ventana.document.write('<\/body><\/html>');
      ventana.document.close();
      ventana.focus();
      setTimeout(function() { ventana.print(); ventana.close(); }, 500);
    }
  </script>
@endpush
