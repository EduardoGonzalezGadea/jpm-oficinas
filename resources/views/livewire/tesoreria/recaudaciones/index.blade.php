<div class="container-fluid px-0">
  <style>
    .nav-tabs { border-bottom: 2px solid #dee2e6; }
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
    html.dark-theme .nav-tabs { border-bottom-color: rgba(255,255,255,.15); }
    html.dark-theme .nav-tabs .nav-link.active {
      border-left-color: rgba(255,255,255,.2);
      border-right-color: rgba(255,255,255,.2);
      border-top-color: #5bc0de;
      border-bottom-color: transparent;
    }
  </style>
  @section('title', 'Recaudaciones')

  <div class="card">
    <div class="card-header card-header-section card-header-gradient py-2 px-3">
      <div class="d-flex justify-content-between align-items-center w-100">
        <h4 class="mb-0 text-premium-header">
          <i class="fas fa-hand-holding-usd mr-2"></i>Recaudaciones
        </h4>
        <div class="d-flex align-items-center">
          <div class="btn-group mr-2 position-relative" x-data="{ open: false }" @click.outside="open = false">
            <button type="button" class="btn btn-sm btn-light dropdown-toggle" @click="open = !open" :aria-expanded="open">
              <i class="fas fa-hand-holding-usd mr-1"></i> Resumen
            </button>
            <div class="dropdown-menu dropdown-menu-right" :class="{ 'show': open }" style="display: block;" x-show="open" x-cloak>
              <a class="dropdown-item active" href="{{ route('tesoreria.gestion-cfe.recaudaciones') }}">
                <i class="fas fa-list-alt mr-2"></i>Resumen Detallado
              </a>
              <a class="dropdown-item" href="{{ route('tesoreria.gestion-cfe.dashboard') }}">
                <i class="fas fa-chart-pie mr-2"></i>Indicadores
              </a>
            </div>
          </div>
          <button type="button" class="btn btn-light btn-sm mr-2" onclick="imprimirRecaudaciones()" title="Imprimir">
            <i class="fas fa-print mr-1"></i> Imprimir
          </button>
          <a href="{{ route('tesoreria.gestion-cfe.index') }}" class="btn btn-sm btn-light">
            <i class="fas fa-arrow-left mr-1"></i> Volver
          </a>
        </div>
      </div>
    </div>
    <div class="card-body">
      <div class="form-row align-items-end mb-3">
        @if($fecha)
          <div class="col-auto mb-2 mb-lg-0">
            <label class="small mb-1">Fecha</label>
            <input type="date" class="form-control form-control-sm" wire:model.live="fecha" wire:change="$refresh">
          </div>
          <div class="col-auto mb-2 mb-lg-0 d-flex align-items-end">
            <button class="btn btn-outline-secondary btn-sm" wire:click="$set('fecha', null)" title="Cambiar a filtro por mes/año">
              <i class="fas fa-times"></i>
            </button>
          </div>
        @else
          <div class="col-6 col-md-3 col-lg-auto mb-2 mb-lg-0">
            <label class="small mb-1">Mes</label>
            <select class="form-control form-control-sm" wire:model.live="filtroMes" wire:change="$refresh">
              @php $meses = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre']; @endphp
              @foreach($meses as $num => $nombre)
                <option value="{{ $num }}">{{ $nombre }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-md-2 col-lg-auto mb-2 mb-lg-0">
            <label class="small mb-1">Año</label>
            <select class="form-control form-control-sm" wire:model.live="filtroAno" wire:change="$refresh">
              @foreach($anosRegistrados as $ano)
                <option value="{{ $ano }}">{{ $ano }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-auto mb-2 mb-lg-0 d-flex align-items-end">
            <button class="btn btn-outline-secondary btn-sm" wire:click="$set('fecha', '{{ date('Y-m-d') }}')" title="Cambiar a filtro por fecha específica">
              <i class="fas fa-calendar-day"></i>
            </button>
          </div>
        @endif
        <div class="col-6 col-md-4 col-lg-2 mb-2 mb-lg-0">
          <label class="small mb-1">Dependencia</label>
          <select class="form-control form-control-sm" wire:model.live="dependencia_id" wire:change="$refresh">
            <option value="">Todas</option>
            @foreach($this->opcionesDependencias as $dep)
              <option value="{{ $dep->id }}">{{ $dep->abreviatura ?? $dep->dependencia }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-4 col-lg-2 mb-2 mb-lg-0">
          <label class="small mb-1">Tipo</label>
          <select class="form-control form-control-sm" wire:model.live="tipo_id" wire:change="$refresh">
            <option value="">Todos</option>
            @foreach($this->opcionesTipos as $tipo)
              <option value="{{ $tipo->id }}">{{ $tipo->tipo }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-4 col-lg-2 mb-2 mb-lg-0">
          <label class="small mb-1">Concepto de caja</label>
          <select class="form-control form-control-sm" wire:model.live="concepto_id" wire:change="$refresh">
            <option value="">Todos</option>
            @foreach($this->opcionesConceptos as $concepto)
              <option value="{{ $concepto->id }}">{{ $concepto->caja_concepto }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-3 col-lg-1 mb-2 mb-lg-0">
          <label class="small mb-1">Monto desde</label>
          <input type="number" step="0.01" class="form-control form-control-sm" wire:model.live="monto_desde" wire:change="$refresh" placeholder="0.00">
        </div>
        <div class="col-6 col-md-3 col-lg-1 mb-2 mb-lg-0">
          <label class="small mb-1">Monto hasta</label>
          <input type="number" step="0.01" class="form-control form-control-sm" wire:model.live="monto_hasta" wire:change="$refresh" placeholder="0.00">
        </div>
        <div class="col-12 col-md-6 col-lg mb-2 mb-lg-0">
          <label class="small mb-1">Buscar</label>
          <div class="input-group input-group-sm">
            <input type="text" class="form-control" wire:model.live="search" placeholder="Documento, adenda, descripción o monto...">
            <div class="input-group-append">
              <button class="btn btn-outline-secondary" wire:click="$set('search', '')" title="Limpiar búsqueda">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
        </div>

      </div>

      @php
        $tabsConDatos = collect($grupos)->filter(fn($g) => $g['total_efectivo'] + $g['total_cheque'] + $g['total_transferencia'] + $g['total_pos'] > 0);
      @endphp

      @if($tabsConDatos->isNotEmpty())
        @php $primerActivo = true; @endphp
        <ul class="nav nav-tabs mb-0" id="recaudacionesTab" role="tablist">
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
        <div class="tab-content p-3" id="recaudacionesTabContent">
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
                                  <td class="text-right font-weight-bold small align-middle text-break" colspan="2">Subtotal {{ $distribucion['distribucion'] }}:</td>
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
        <p class="text-center py-4">No hay recaudaciones para la fecha seleccionada.</p>
      @endif

      {{-- Totales por Institución --}}
      @if($mostrarTotalesInstitucion && !empty($totalesPorInstitucion) && count($totalesPorInstitucion) > 0)
        <div class="mt-4 mb-3">
          <h6 class="mb-2 font-weight-bold text-uppercase">
            <i class="fas fa-university mr-2"></i>Totales por Institución
          </h6>
          <div class="table-responsive">
            <table class="table table-sm table-bordered table-striped">
              <thead class="bg-light">
                <tr>
                  <th class="align-middle">Institución</th>
                  <th class="align-middle text-right" style="width: 1%; white-space: nowrap;">Monto Total</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($totalesPorInstitucion as $total)
                  <tr>
                    <td class="align-middle">
                      {{ $total->institucion ? $total->institucion->descripcion : 'SIN INSTITUCIÓN' }}
                    </td>
                    <td class="align-middle text-right font-weight-bold text-nowrap">
                      $ {{ number_format((float) $total->total_monto, 2, ',', '.') }}
                    </td>
                  </tr>
                @endforeach
              </tbody>
              <tfoot class="bg-light">
                <tr>
                  <td class="align-middle text-right font-weight-bold">Total General:</td>
                  <td class="align-middle text-right font-weight-bold text-success text-nowrap">
                    $ {{ number_format($totalesPorInstitucion->sum('total_monto'), 2, ',', '.') }}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      @endif
    </div>
  </div>
</div>

@push('scripts')
  <script>
    document.addEventListener('livewire:init', function () {
      window.addEventListener('swal:toast-success', (event) => {
        const data = window.LiveEvent(event);
        Swal.fire({
          icon: 'success',
          title: 'Éxito',
          text: data.text || 'Operación completada.',
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
        });
      });

      window.addEventListener('swal:toast-error', (event) => {
        const data = window.LiveEvent(event);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: data.text || 'Ocurrió un error inesperado.',
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 5000,
          timerProgressBar: true,
        });
      });
    });

    function imprimirRecaudaciones() {
      var tabActivo = document.querySelector('#recaudacionesTab .nav-link.active');
      if (!tabActivo) return;

      var targetId = tabActivo.getAttribute('href');
      var contenido = document.querySelector(targetId).innerHTML;
      
      // Incluir la tabla de totales por institución si existe
      var totalesInstitucion = document.querySelector('.mt-4.mb-3 h6');
      var totalesInstitucionHtml = '';
      if (totalesInstitucion && totalesInstitucion.textContent.includes('Totales por Institución')) {
        var divTotales = totalesInstitucion.closest('.mt-4.mb-3');
        if (divTotales) {
          totalesInstitucionHtml = divTotales.outerHTML;
        }
      }

      var ventana = window.open('', '_blank', 'width=800,height=600');
      ventana.document.write('<!DOCTYPE html><html><head><title>Recaudaciones</title>');
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
      ventana.document.write('.mt-4{margin-top:1.5rem!important}.mb-3{margin-bottom:1rem!important}');
      ventana.document.write('.text-uppercase{text-transform:uppercase}');
      ventana.document.write('.table-striped tbody tr:nth-of-type(odd){background-color:rgba(0,0,0,.05)}');
      ventana.document.write('.text-success{color:#28a745!important}');
      ventana.document.write('</style>');
      ventana.document.write('<\/head><body>');
      ventana.document.write('<h4 style="margin-bottom:1rem">Recaudaciones</h4>');
      ventana.document.write(contenido);
      if (totalesInstitucionHtml) {
        ventana.document.write(totalesInstitucionHtml);
      }
      ventana.document.write('<\/body><\/html>');
      ventana.document.close();
      ventana.focus();
      setTimeout(function() { ventana.print(); ventana.close(); }, 500);
    }
  </script>
@endpush
