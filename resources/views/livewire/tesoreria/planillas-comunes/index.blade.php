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
  @section('title', 'Planillas Comunes')

  <div class="card">
    <div class="card-header bg-info text-white card-header-gradient p-2">
      <div class="d-flex justify-content-between align-items-center">
        <h4 class="card-title px-1 m-0">
          <strong><i class="fas fa-folder-open mr-2"></i>Planillas Comunes de Recaudación</strong>
        </h4>
        <div>
          <a href="{{ route('tesoreria.gestion-cfe.index') }}" class="btn btn-light mb-0">
            <i class="fas fa-arrow-left mr-1"></i> Volver a Gestión de Recaudaciones
          </a>
        </div>
      </div>
    </div>
    <div class="card-body">
      <div class="d-flex mb-3 align-items-center flex-wrap">
        <div class="mr-2 mb-0" style="width: 250px;">
          <input type="text" class="form-control form-control-sm" wire:model.live.debounce.300ms="search" placeholder="Buscar por número o concepto...">
        </div>
        <div class="mr-2 mb-0" style="width: 150px;">
          <select class="form-control form-control-sm" wire:model.live="filtroEstado">
            <option value="">Todos los estados</option>
            <option value="confirmada">Confirmadas</option>
            <option value="pendiente">Pendientes</option>
            <option value="anulada">Anuladas</option>
          </select>
        </div>
        <div class="mr-2 mb-0" style="width: 200px;">
          <select class="form-control form-control-sm" wire:model.live="filtroConcepto">
            <option value="">— Todos los conceptos —</option>
            @foreach($cajaConceptos as $concepto)
              <option value="{{ $concepto->id }}">{{ $concepto->caja_concepto }}</option>
            @endforeach
          </select>
        </div>
        <div class="dropdown mr-2 mb-0" style="width: 180px;" id="dropdownMesesWrapper" wire:ignore.self>
          <button class="btn btn-white border form-control form-control-sm dropdown-toggle text-left d-flex justify-content-between align-items-center" type="button" id="dropdownMeses" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
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
                <input type="checkbox" id="mes_{{ $num }}" value="{{ $num }}" wire:model.live="filtroMeses" class="custom-control-input">
                <label for="mes_{{ $num }}" class="custom-control-label small cursor-pointer w-100">{{ $nombre }}</label>
              </div>
            @endforeach
          </div>
        </div>
        <div class="mr-2 mb-0" style="width: 110px;">
          <select class="form-control form-control-sm" wire:model.live="filtroAno">
            <option value="0">— Todos los años —</option>
            @foreach($anosRegistrados as $ano)
              <option value="{{ $ano }}">{{ $ano }}</option>
            @endforeach
          </select>
        </div>
        <div class="mb-0">
          <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="limpiarFiltros" title="Limpiar filtros">
            <i class="fas fa-undo"></i>
          </button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-sm table-bordered table-striped table-hover">
          <thead class="align-middle">
            <tr>
              <th>N°</th>
              <th>Concepto</th>
              <th>Total CFEs</th>
              <th class="text-right">Monto Total</th>
              <th class="text-center">Conf.</th>
              <th class="text-center d-print-none">Acciones</th>
            </tr>
          </thead>
          <tbody class="align-middle">
            @forelse($planillasPorFecha as $fecha => $planillasDelDia)
              @php
                $totalDia = $planillasDelDia->sum(fn($p) => $p->cfes->sum('total_a_pagar'));
              @endphp
              <tr class="table-info" wire:key="fecha-header-{{ $fecha }}">
                <td colspan="6" class="py-1 px-2 font-weight-bold">
                  <div class="d-flex justify-content-between align-items-center">
                    <span><i class="far fa-calendar-alt mr-1"></i> {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</span>
                    <span class="text-right">Total: $ {{ number_format($totalDia, 2, ',', '.') }}</span>
                  </div>
                </td>
              </tr>
              @foreach($planillasDelDia as $p)
                <tr wire:key="planilla-row-{{ $p->id }}">
                  <td class="align-middle">
                    @if($p->trashed())
                      {{ $p->numero }}<br><strong class="text-danger">(ANULADA)</strong>
                      <br><small class="text-muted">{{ $p->motivo_anulacion }}</small>
                    @else
                      {{ $p->numero }}
                    @endif
                  </td>
                  <td class="align-middle">
                    <strong>{{ $p->cajaConcepto->caja_concepto ?? '—' }}</strong>
                  </td>
                  <td class="align-middle text-center">
                    {{ $p->cfes->count() }}
                  </td>
                  <td class="align-middle text-right text-nowrap font-weight-bold">
                    $ {{ number_format($p->cfes->sum('total_a_pagar'), 2, ',', '.') }}
                  </td>
                  <td class="align-middle text-center">
                    @if($p->trashed())
                      <i class="fas fa-ban text-danger" title="Anulada"></i>
                    @elseif($p->confirmada)
                      <i class="fas fa-check-circle text-success" title="Confirmada"></i>
                    @else
                      <i class="fas fa-times-circle text-danger" title="No confirmada"></i>
                    @endif
                  </td>
                  <td class="align-middle text-center d-print-none text-nowrap">
                    <div class="btn-group btn-group-sm" role="group">
                      <button class="btn btn-primary btn-action-fixed" title="Ver Planilla"
                        wire:click="verPlanilla({{ $p->id }})">
                        <i class="fas fa-file-alt"></i>
                      </button>
                      @if(!$p->trashed() && !$p->confirmada)
                      <button class="btn btn-danger btn-action-fixed" title="Anular"
                        onclick="anularPlanilla({{ $p->id }})">
                        <i class="fas fa-ban"></i>
                      </button>
                      @endif
                    </div>
                  </td>
                </tr>
              @endforeach
            @empty
              <tr>
                <td colspan="6" class="text-center py-3">
                  No hay planillas en el período seleccionado.
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

  {{-- Modal Ver Planilla --}}
  <div class="modal fade" id="modalPlanilla" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-full-width" role="document">
      <div class="modal-content border-0 shadow">
        <div class="modal-header {{ $planillaVer?->trashed() ? 'bg-danger' : 'bg-info' }} text-white p-2">
          <h5 class="modal-title m-0">
            <i class="fas fa-folder-open mr-2"></i><strong>Planilla {{ $planillaVer?->numero ?? '' }}</strong>
            @if($planillaVer?->trashed())
              <span class="badge badge-light ml-2">ANULADA</span>
            @endif
          </h5>
          <button type="button" class="close text-white" aria-label="Close"
            wire:click="cerrarModalPlanilla">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        @if($planillaVer)
        <div class="modal-body p-3">
          @if($planillaVer->trashed())
          <div class="alert alert-danger py-2 mb-3">
            <strong><i class="fas fa-ban mr-1"></i>PLANILLA ANULADA</strong><br>
            <span>Motivo: {{ $planillaVer->motivo_anulacion }}</span>
          </div>
          @endif
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
            <strong>PLANILLA DE RECAUDACIÓN DE {{ strtoupper($planillaVer->cajaConcepto->caja_concepto ?? '') }}</strong>
          </div>

          <table class="table table-sm table-bordered">
            <thead class="thead-light">
              <tr>
                <th>CFE</th>
                <th>Fecha</th>
                <th>Receptor</th>
                <th>Doc. Receptor</th>
                @if($planillaVer->cajaConcepto && $planillaVer->cajaConcepto->requiere_institucion)
                  <th>Institución</th>
                @endif
                <th class="text-right">Monto</th>
              </tr>
            </thead>
            <tbody>
              @forelse($planillaVer->cfes as $cfe)
                <tr>
                  <td class="align-middle">
                    {{ $cfe->documento_tipo }} {{ $cfe->documento_serie }}-{{ $cfe->documento_numero }}
                  </td>
                  <td class="align-middle">
                    {{ $cfe->fecha ? $cfe->fecha->format('d/m/Y') : '—' }}
                  </td>
                  <td class="align-middle">
                    {{ $cfe->receptor_nombre_denominacion ?: '—' }}
                  </td>
                  <td class="align-middle">
                    {{ $cfe->receptor_documento_ruc ?: '—' }}
                  </td>
                  @if($planillaVer->cajaConcepto && $planillaVer->cajaConcepto->requiere_institucion)
                    <td class="align-middle">
                      {{ $cfe->institucion ? $cfe->institucion->descripcion : '—' }}
                    </td>
                  @endif
                  <td class="align-middle text-right text-nowrap">
                    $ {{ number_format($cfe->total_a_pagar, 2, ',', '.') }}
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="{{ $planillaVer->cajaConcepto && $planillaVer->cajaConcepto->requiere_institucion ? 6 : 5 }}" class="text-center py-2">No hay CFEs asociados.</td>
                </tr>
              @endforelse
            </tbody>
            <tfoot class="bg-light font-weight-bold">
              <tr>
                <td colspan="{{ $planillaVer->cajaConcepto && $planillaVer->cajaConcepto->requiere_institucion ? 5 : 4 }}" class="text-right">TOTAL GENERAL:</td>
                <td class="text-right text-success text-nowrap">
                  $ {{ number_format($planillaVer->cfes->sum('total_a_pagar'), 2, ',', '.') }}
                </td>
              </tr>
            </tfoot>
          </table>

          {{-- Totales por Institución --}}
          @if($planillaVer->cajaConcepto && $planillaVer->cajaConcepto->requiere_institucion && !empty($totalesPorInstitucion) && count($totalesPorInstitucion) > 0)
            <div class="mt-4 mb-3">
              <h6 class="mb-2 font-weight-bold text-uppercase">
                <i class="fas fa-university mr-2"></i>Totales por Institución
              </h6>
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
          @endif
        </div>
        <div class="modal-footer">
          @can('tesoreria.supervisar')
            @unless($planillaVer->trashed())
            <div class="custom-control custom-switch d-inline-block mr-auto">
              <input type="checkbox" class="custom-control-input" id="switchConfirmada"
                onclick="event.preventDefault()"
                wire:click="toggleConfirmada({{ $planillaVer->id }})"
                wire:key="switchConfirmada-{{ $planillaVer->id }}-{{ $planillaVer->confirmada ? 'on' : 'off' }}"
                {{ $planillaVer->confirmada ? 'checked' : '' }}>
              <label class="custom-control-label" for="switchConfirmada">Confirmada</label>
            </div>
            @endunless
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
        ventana.document.write('.text-right{text-align:right}');
        ventana.document.write('.text-center{text-align:center}');
        ventana.document.write('.text-nowrap{white-space:nowrap}');
        ventana.document.write('.font-weight-bold{font-weight:700}');
        ventana.document.write('.mb-3{margin-bottom:12px}');
        ventana.document.write('.mt-4{margin-top:1.5rem}');
        ventana.document.write('.d-flex{display:flex}');
        ventana.document.write('.justify-content-between{justify-content:space-between}');
        ventana.document.write('.justify-content-center{justify-content:center}');
        ventana.document.write('.align-items-start{align-items:flex-start}');
        ventana.document.write('.bg-light{background:#f0f0f0}');
        ventana.document.write('.text-success{color:#28a745}');
        ventana.document.write('.text-uppercase{text-transform:uppercase}');
        ventana.document.write('.table-striped tbody tr:nth-of-type(odd){background-color:rgba(0,0,0,.05)}');
        ventana.document.write('.d-print-none,.modal-footer,.close,.custom-control{display:none!important}');
        ventana.document.write('@media print{body{padding:0}}');
        ventana.document.write('<\/style><\/head><body>');
        ventana.document.write(wrapper.innerHTML);
        ventana.document.write('<\/body><\/html>');
        ventana.document.close();
        ventana.focus();
        setTimeout(function() { ventana.print(); ventana.close(); }, 500);
      }

      function anularPlanilla(id) {
        Swal.fire({
          title: '¿Anular planilla?',
          text: 'Los CFEs quedarán disponibles para futuras planillas. Se preservará una copia de la planilla actual.',
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
            Livewire.dispatch('anularPlanilla', { id: id, motivo: result.value });
          }
        });
      }

      function bindModalEvents() {
        const show = (selector) => $(selector).modal('show');
        const hide = (selector) => $(selector).modal('hide');

        window.addEventListener('abrir-modal-planilla', () => show('#modalPlanilla'));
        window.addEventListener('cerrar-modal-planilla', () => hide('#modalPlanilla'));

        if (typeof Livewire !== 'undefined') {
          Livewire.on('abrir-modal-planilla', () => show('#modalPlanilla'));
          Livewire.on('cerrar-modal-planilla', () => hide('#modalPlanilla'));
        }

        $('#modalPlanilla').on('hidden.bs.modal', function () {
          if (typeof Livewire !== 'undefined') Livewire.dispatch('cerrarModalPlanilla');
        });
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindModalEvents);
      } else {
        bindModalEvents();
      }
      document.addEventListener('livewire:init', bindModalEvents);
    </script>
@endpush
