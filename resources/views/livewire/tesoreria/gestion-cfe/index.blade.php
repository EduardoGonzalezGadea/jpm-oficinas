<div>
<div class="container-fluid px-0">
  <style>
    .btn-action-fixed { width: 30px; padding-left: 0; padding-right: 0; }
    .text-small-custom { font-size: 0.8rem; }
    .upload-loading-overlay {
      position: absolute; top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.3); display: none !important;
      align-items: center; justify-content: center; z-index: 20; border-radius: 8px;
    }
    .skeleton-row td { height: 48px; }
    .skeleton-box {
      display: inline-block; height: 14px; border-radius: 4px;
      background: linear-gradient(90deg, #e0e0e0 25%, #f0f0f0 50%, #e0e0e0 75%);
      background-size: 200% 100%; animation: skeleton-shimmer 1.5s infinite;
    }
    @keyframes skeleton-shimmer {
      0% { background-position: 200% 0; }
      100% { background-position: -200% 0; }
    }
    .item-distribucion-group { outline: 3px solid #1a73e8; outline-offset: -1px; }
    .item-distribucion-group + .item-distribucion-group { outline: 3px solid #1a73e8; outline-offset: -1px; }
    .modal-full-width { max-width: 95vw; }
  </style>
  @section('title', 'Gestión de Recaudaciones')

  <div class="card">
    <div class="card-header bg-info text-white card-header-gradient p-2">
      <div class="d-flex justify-content-between align-items-center">
        <h4 class="card-title px-1 m-0">
          <strong><i class="fas fa-file-invoice mr-2"></i>Gestión de Recaudaciones</strong>
        </h4>
        <div class="d-flex align-items-center">
          <div wire:loading wire:target="archivoPdf" class="mr-3 text-white font-weight-bold small">
            <i class="fas fa-spinner fa-spin mr-1"></i> CARGANDO
          </div>
          <a href="{{ route('tesoreria.libro-diario.index') }}" class="btn btn-secondary mb-0">
            <i class="fas fa-book mr-1"></i> Libro Diario
          </a>
          <a href="{{ route('tesoreria.gestion-cfe.estados-recaudacion') }}" class="btn btn-warning mb-0">
            <i class="fas fa-chart-line mr-1"></i> Est. Rec.
          </a>
          <div class="btn-group mb-0 position-relative" role="group" x-data="{ open: false }" @click.outside="open = false">
            <button type="button" class="btn btn-info dropdown-toggle" @click="open = !open" aria-haspopup="true" :aria-expanded="open">
              <i class="fas fa-hand-holding-usd mr-1"></i> Reportes
            </button>
            <div class="dropdown-menu" :class="{ 'show': open }" style="display: block;" x-show="open" x-cloak>
              <a class="dropdown-item" href="{{ route('tesoreria.gestion-cfe.recaudaciones') }}">
                <i class="fas fa-list-alt mr-2"></i>Resumen Detallado
              </a>
              <a class="dropdown-item" href="{{ route('tesoreria.gestion-cfe.dashboard') }}">
                <i class="fas fa-chart-pie mr-2"></i>Indicadores
              </a>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item" href="{{ route('tesoreria.gestion-cfe.planillas-comunes') }}">
                <i class="fas fa-folder mr-2"></i>Planillas Comunes
              </a>
            </div>
          </div>
          <button type="button" class="btn btn-success mb-0"
            wire:click="nuevoCfe">
            <i class="fas fa-plus-circle mr-1"></i> Nuevo
          </button>
          <label for="archivoPdfInput" class="btn btn-primary mb-0 cursor-pointer"
            wire:loading.attr="disabled" wire:target="archivoPdf">
            <i class="fas fa-file-upload mr-1"></i> Cargar
          </label>
          <input type="file" id="archivoPdfInput" wire:model.live="archivoPdf" class="d-none"
            accept="application/pdf">
        </div>
      </div>
    </div>

    <div class="card-body px-2 pt-1 position-relative" wire:poll.visible.300s>
      {{-- Auto-refresh cada 5 minutos solo cuando la vista está activa --}}
      
      <div wire:loading.style="display: flex" wire:target="archivoPdf,confirmarCarga,editarCfe,nuevoCfe" class="upload-loading-overlay">
        <div class="text-white font-weight-bold h4 mb-0">
          <i class="fas fa-spinner fa-spin mr-2"></i> CARGANDO
        </div>
      </div>
      
      {{-- Indicador de auto-refresh --}}
      <div wire:loading wire:target="$refresh" class="position-absolute" style="top: 10px; right: 10px; z-index: 1000;">
        <span class="badge badge-info">
          <i class="fas fa-sync-alt fa-spin"></i> Actualizando datos...
        </span>
      </div>
      
      {{-- Barra de filtros --}}
      <div class="d-flex mb-2 align-items-center">
        <div class="flex-grow-1 mr-2" style="max-width: 40%;">
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text"><i class="fas fa-search"></i></span>
            </div>
            <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
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
                <input type="checkbox" id="mes_{{ $num }}" value="{{ $num }}" wire:model.live="filtroMeses" class="custom-control-input">
                <label for="mes_{{ $num }}" class="custom-control-label small cursor-pointer w-100">{{ $nombre }}</label>
              </div>
            @endforeach
          </div>
        </div>
        <div class="mr-2" style="width: 110px;">
          <select wire:model="filtroAno" class="form-control">
            <option value="0">— Todos los años —</option>
            @foreach($anosRegistrados as $ano)
              <option value="{{ $ano }}">{{ $ano }}</option>
            @endforeach
          </select>
        </div>
        <div class="mr-2">
          <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="limpiarFiltros" title="Limpiar filtros">
            <i class="fas fa-undo"></i>
          </button>
        </div>
        @if($mostrarSelectorPlanillas && !empty($cfesSeleccionados))
          <div class="mr-2">
            <button type="button" class="btn btn-sm btn-primary" wire:click="crearPlanillaComun">
              <i class="fas fa-folder-plus mr-1"></i> Crear Planilla ({{ count($cfesSeleccionados) }})
            </button>
          </div>
        @endif
      <div class="text-nowrap ml-auto">
        <small class="font-weight-bold text-secondary">{{ $cfes->total() }} registros</small>
      </div>

      </div>

      {{-- Tabla principal --}}
        <table class="table table-sm table-bordered table-striped table-hover w-100">
          <thead class="align-middle">
            <tr>
              @if($mostrarSelectorPlanillas)
                <th class="align-middle text-center" style="width: 1%; white-space: nowrap;">
                  <i class="fas fa-check-square"></i>
                </th>
              @endif
              <th class="align-middle" style="width: 1%; white-space: nowrap;">Nro. Doc.</th>
              <th class="align-middle">Receptor</th>
              <th class="align-middle" style="width: 1%; white-space: nowrap;">Doc. Receptor</th>
              <th class="align-middle" style="width: 1%; white-space: nowrap;">Fecha</th>
              <th class="align-middle text-right" style="width: 1%; white-space: nowrap;">Total a Pagar</th>
              <th class="align-middle">Concepto / ER</th>
              @if($mostrarColumnaConf)
                <th class="align-middle text-center" style="width: 1%; white-space: nowrap;">CONF.</th>
              @endif
              <th class="align-middle text-center" style="width: 1%; white-space: nowrap;">Acciones</th>
            </tr>
          </thead>
          <tbody class="align-middle">
            {{-- Skeleton loader durante carga inicial o filtros --}}
            <tr wire:loading.block wire:target="search,filtroConcepto,filtroMeses,filtroAno,limpiarFiltroMeses" class="d-none">
              <td colspan="{{ $mostrarSelectorPlanillas ? ($mostrarColumnaConf ? 9 : 8) : ($mostrarColumnaConf ? 8 : 7) }}" class="py-3">
                <div class="w-100">
                  @for($i = 0; $i < 5; $i++)
                    <div class="skeleton-row d-flex align-items-center border-bottom px-2 py-2">
                      <div class="flex-grow-1 mr-2"><div class="skeleton-box" style="width:15%"></div></div>
                      <div class="flex-grow-1 mr-2"><div class="skeleton-box" style="width:30%"></div></div>
                      <div class="flex-grow-1 mr-2"><div class="skeleton-box" style="width:20%"></div></div>
                      <div class="flex-grow-1 mr-2"><div class="skeleton-box" style="width:12%"></div></div>
                      <div class="flex-grow-1 mr-2"><div class="skeleton-box" style="width:15%"></div></div>
                      <div class="flex-grow-1 mr-2"><div class="skeleton-box" style="width:18%"></div></div>
                      <div style="width:100px"><div class="skeleton-box" style="width:60%"></div></div>
                    </div>
                  @endfor
                </div>
              </td>
            </tr>
            @forelse($cfes as $cfe)
              @php 
                $simbolo = $cfe->moneda === 'UYU' ? '$' : $cfe->moneda;
                $cfeYaTienePlanilla = $cfe->planilla_comun_id !== null;
                $cfeConfirmado = $cfe->items->every(fn($i) => $i->confirmado);
                
                // Si el concepto requiere confirmación y el CFE NO está confirmado, no mostrar checkbox
                $mostrarCheckbox = $mostrarSelectorPlanillas && 
                                   (!($conceptoPermitePlanilla && $conceptoPermitePlanilla->requiere_confirmacion) || $cfeConfirmado);
                
                // Checkbox deshabilitado si ya tiene planilla o si está confirmado (y concepto requiere confirmación)
                $checkboxDeshabilitado = $cfeYaTienePlanilla || 
                                         ($conceptoPermitePlanilla && $conceptoPermitePlanilla->requiere_confirmacion && $cfeConfirmado);
              @endphp
              <tr wire:loading.remove wire:target="search,filtroConcepto,filtroMeses,filtroAno">
                @if($mostrarSelectorPlanillas)
                  <td class="align-middle text-center">
                    @if($mostrarCheckbox)
                      <input type="checkbox" 
                        wire:click="toggleCfeSeleccionado({{ $cfe->id }})"
                        {{ in_array($cfe->id, $cfesSeleccionados) ? 'checked' : '' }}
                        {{ $checkboxDeshabilitado ? 'disabled' : '' }}
                        style="{{ $checkboxDeshabilitado ? 'opacity: 0.3; cursor: not-allowed;' : '' }}">
                      @if($cfeYaTienePlanilla)
                        <small class="d-block text-muted" style="font-size: 0.7rem;">En planilla</small>
                      @elseif($conceptoPermitePlanilla && $conceptoPermitePlanilla->requiere_confirmacion && $cfeConfirmado)
                        <small class="d-block text-success" style="font-size: 0.7rem;">Confirmado</small>
                      @endif
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                @endif
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
                <td class="align-middle text-nowrap">
                  {{ $cfe->fecha ? $cfe->fecha->format('d/m/Y') : 'N/A' }}
                </td>
                <td class="align-middle text-right font-weight-bold text-nowrap">
                  {{ $simbolo }} {{ number_format($cfe->total_a_pagar, 2, ',', '.') }}
                </td>
                <td class="align-middle">
                  @if($cfe->cajaConcepto)
                    <span class="font-weight-bold text-success">{{ $cfe->cajaConcepto->caja_concepto }}</span>
                    @php
                      $erNumeros = $cfe->items
                        ->pluck('planillaEr.numero')
                        ->filter()
                        ->unique()
                        ->values();
                    @endphp
                    @if($erNumeros->isNotEmpty())
                      <span class="text-muted d-block text-small-custom">E/R {{ $erNumeros->map(fn($n) =>"N°{$n}")->implode(', ') }}</span>
                    @endif
                  @else
                    <span class="badge badge-warning">Sin asignar</span>
                  @endif
                </td>
                @if($mostrarColumnaConf)
                  <td class="align-middle text-center d-print-none{{ !$cfeConfirmado ? ' table-warning' : '' }}">
                    @if($cfe->cajaConcepto && $cfe->cajaConcepto->requiere_confirmacion)
                      @php
                        $cfeConfirmado = $cfe->items->every(fn($i) => $i->confirmado);
                      @endphp
                      <div class="custom-control custom-switch d-inline-block">
                        <input type="checkbox" class="custom-control-input" id="confirmado-gc-{{ $cfe->id }}"
                          wire:click="toggleConfirmado({{ $cfe->id }})" {{ $cfeConfirmado ? 'checked' : '' }}>
                        <label class="custom-control-label" for="confirmado-gc-{{ $cfe->id }}"></label>
                      </div>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                @endif
                <td class="align-middle text-center d-print-none">
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
                <td colspan="{{ $mostrarSelectorPlanillas ? ($mostrarColumnaConf ? 9 : 8) : ($mostrarColumnaConf ? 8 : 7) }}" class="text-center py-5">
                  <div class="my-4">
                    <i class="fas fa-file-invoice fa-4x text-muted mb-3 d-block"></i>
                    <p class="text-muted mb-1 font-weight-bold" style="font-size:1.1rem">No hay CFEs registrados</p>
                    <p class="text-muted mb-3 small">Comience cargando un CFE desde un PDF o créelo manualmente.</p>
                    <div class="d-flex justify-content-center gap-2">
                      <label for="archivoPdfInputEmpty" class="btn btn-primary btn-sm mb-0 cursor-pointer mr-2">
                        <i class="fas fa-file-upload mr-1"></i> Cargar
                      </label>
                      <button type="button" class="btn btn-success btn-sm mb-0"
                        wire:click="nuevoCfe">
                        <i class="fas fa-plus-circle mr-1"></i> Nuevo
                      </button>
                    </div>
                    <input type="file" id="archivoPdfInputEmpty" wire:model.live="archivoPdf" class="d-none" accept="application/pdf">
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>

      @if($mostrarTotalesInstitucion && !empty($totalesPorInstitucion) && count($totalesPorInstitucion) > 0)
        <div class="mt-3 mb-3">
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

      <div class="mt-3 d-flex justify-content-center d-print-none">
        {{ $cfes->links() }}
      </div>
      
      <div class="mt-2 text-center">
        <small class="text-muted">
          <i class="fas fa-info-circle"></i>
          Los datos se actualizan automáticamente cada 5 minutos mientras la vista está activa
        </small>
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
