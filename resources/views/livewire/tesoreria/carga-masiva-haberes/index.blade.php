<div class="container-fluid px-0">
    @section('title', 'Carga masiva de Haberes')

    <style>
      .row-selected {
        background-color: #e8f4fd !important;
      }
      /* Alineación vertical centrada en toda la tabla */
      .table-detalle td,
      .table-detalle th {
        vertical-align: middle !important;
      }
    </style>

    <div class="card">

      <div class="card-header bg-info text-white card-header-gradient p-2">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="card-title px-1 m-0">
            <strong><i class="fas fa-upload mr-2"></i>Carga masiva de Haberes</strong>
          </h4>
          <div>
            <a href="{{ route('tesoreria.libro-diario.index') }}" class="btn btn-light mb-0">
              <i class="fas fa-arrow-left mr-1"></i> Volver a Libro Diario
            </a>
          </div>
        </div>
      </div>
      <div class="card-body px-2 pt-2" style="position: relative;">

        {{-- Folder selector with autocomplete --}}
        <div class="form-row align-items-end mb-3" x-data="{ open: @entangle('mostrarSugerencias').live }" @click.away="open = false; $wire.ocultarSugerencias()">
          <div class="col-md-8">
            <label class="small mb-0">Carpeta con archivos Excel</label>
            <div style="position: relative;">
              <div class="input-group input-group-sm">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-folder-open"></i></span>
                </div>
                <input type="text" class="form-control" wire:model.live.debounce.400ms="ruta"
                       placeholder="Escriba la ruta de la carpeta…"
                       id="rutaInput"
                       autocomplete="off"
                       @focus="$wire.autocompletarRuta()"
                       @keydown.escape="open = false; $wire.ocultarSugerencias()">
              </div>

              {{-- Sugerencias dropdown --}}
              @if ($mostrarSugerencias && count($sugerencias) > 0)
                <div class="list-group shadow-sm"
                     style="position: absolute; top: 100%; left: 0; right: 0; z-index: 1050; max-height: 260px; overflow-y: auto; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 4px 4px;">
                  @foreach ($sugerencias as $sug)
                    <button type="button"
                            class="list-group-item list-group-item-action py-1 px-2 small d-flex align-items-center"
                            wire:click="seleccionarSugerencia('{{ addslashes($sug) }}')"
                            style="cursor: pointer;">
                      <i class="fas fa-folder text-warning mr-2" style="font-size: 0.85rem;"></i>
                      <span class="text-truncate">{{ $sug }}</span>
                    </button>
                  @endforeach
                </div>
              @endif
            </div>
          </div>
          <div class="col-md-2">
            <label class="small mb-0">Fecha asiento</label>
            <input type="date" class="form-control form-control-sm" wire:model.live="fechaAsiento">
          </div>
          <div class="col-md-2">
            <button type="button" class="btn btn-primary btn-sm btn-block" wire:click="procesar"
                    wire:loading.attr="disabled">
              <i class="fas fa-search mr-1"></i> Procesar
            </button>
          </div>
        </div>

        {{-- Results --}}
        @if ($procesado)

          {{-- Detail with filters --}}
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-header bg-secondary text-white py-1 d-flex justify-content-between align-items-center" style="position: relative;">
                  <strong><i class="fas fa-list mr-1"></i> Detalle de pagos</strong>
                  <strong style="position: absolute; left: 50%; transform: translateX(-50%);">
                    <i class="fas fa-money-bill-wave mr-1"></i>TOTAL VENTANILLA: $ {{ number_format($this->totalVentanillaExcel, 0, ',', '.') }}
                  </strong>
                  @if ($this->cantidadSeleccionados > 0)
                    <span class="badge badge-light">
                      {{ $this->cantidadSeleccionados }} seleccionados — $ {{ number_format($this->totalSeleccionado, 0, ',', '.') }}
                    </span>
                  @endif
                </div>
                @if ($this->puedeAsignarDetalleMasivo)
                  <div class="bg-secondary px-2 pb-1 text-center" style="border-top: 1px solid rgba(255,255,255,0.15);">
                    <div class="d-inline-flex align-items-center" style="gap: 0.5rem;">
                      <span class="text-white" style="font-size: 0.8rem; font-weight: 500;">Asignar detalle a seleccionados:</span>
                      <select class="form-control form-control-sm" style="width: auto; min-width: 180px; font-size: 0.8rem;" wire:change="asignarDetalleMasivo($event.target.value)">
                        <option value="">— Seleccionar —</option>
                        @foreach ($opcionesDetalle as $opt)
                          <option value="{{ $opt['id'] }}">{{ $opt['nombre'] }}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                @endif
                <div class="card-body p-0">
                  {{-- Filters --}}
                  <div class="form-row align-items-end p-2 bg-light border-bottom">
                    <div class="col-md-2">
                      <label class="small mb-0">Mes</label>
                      <select class="form-control form-control-sm" wire:model="filtro_mes">
                        <option value="">Todos</option>
                        @foreach ($mesesDisponibles as $m)
                          <option value="{{ $m }}">{{ $m }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-2">
                      <label class="small mb-0">Tipo</label>
                      <select class="form-control form-control-sm" wire:model="filtro_tipo">
                        <option value="">Todos</option>
                        @foreach ($tiposDisponibles as $t)
                          <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-2">
                      <label class="small mb-0">Pago</label>
                      <select class="form-control form-control-sm" wire:model="filtro_ventanilla">
                        <option value="">Todos</option>
                        <option value="1">Ventanilla</option>
                        <option value="0">Otros medios</option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="small mb-0">Buscar</label>
                      <input type="text" class="form-control form-control-sm" wire:model.live="buscar" placeholder="C.I. o nombre">
                    </div>
                    <div class="col-md-3">
                      <label class="small mb-0">&nbsp;</label>
                      <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted small">{{ count($detalleFiltrado) }} registros</span>
                        <div class="btn-group btn-group-sm">
                          <button type="button" class="btn btn-outline-primary btn-sm" wire:click="seleccionarTodos(true)" title="Seleccionar todos los visibles">
                            <i class="fas fa-check-double"></i>
                          </button>
                          <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="seleccionarTodos(false)" title="Deseleccionar todos">
                            <i class="fas fa-times"></i>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>

                  {{-- Detail table --}}
                  <div style="overflow: visible;">
                    <table class="table table-sm table-striped table-hover mb-0 table-detalle w-100">
                      <thead class="bg-light">
                        <tr>
                          <th style="width: 35px;" class="text-center">
                            <input type="checkbox" title="Seleccionar/deseleccionar todos"
                                   wire:click="seleccionarTodos($event.target.checked)"
                                   {{ collect($detalleFiltrado)->every(fn($d) => $seleccionados[$d['_idx']] ?? false) && count($detalleFiltrado) > 0 ? 'checked' : '' }}>
                          </th>
                          <th>C.I.</th>
                          <th>Nombre</th>
                          <th class="text-right">Monto</th>
                          <th>Tipo</th>
                          <th>Archivo</th>
                          <th>Pago</th>
                          <th>Descripción</th>
                          <th>Detalle Libro Diario</th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse ($detalleFiltrado as $d)
                          @php $idx = $d['_idx']; @endphp
                          <tr class="{{ ($seleccionados[$idx] ?? false) ? 'row-selected' : '' }} {{ $d['es_ventanilla'] ? '' : 'text-muted' }}">
                            <td class="text-center">
                              <input type="checkbox" wire:model.live="seleccionados.{{ $idx }}">
                            </td>
                            <td>{{ $d['ci'] }}</td>
                            <td>{{ $d['nombre'] }}</td>
                            <td class="text-right font-weight-bold">$ {{ number_format($d['monto'], 0, ',', '.') }}</td>
                            <td><span class="badge badge-info">{{ $d['tipo'] }}</span></td>
                            <td class="small">{{ $d['archivo'] }}</td>
                            <td>
                              @if ($d['es_ventanilla'])
                                <span class="badge badge-success">Ventanilla</span>
                              @else
                                <span class="badge badge-secondary">Otro medio</span>
                              @endif
                            </td>
                            <td>
                              <input type="text" class="form-control form-control-sm"
                                     wire:model.blur="descripcionItem.{{ $idx }}"
                                     placeholder="Descripción…">
                            </td>
                            <td>
                              <select class="form-control form-control-sm"
                                      wire:model.live="detalleAsignado.{{ $idx }}">
                                <option value="">— Seleccionar —</option>
                                @foreach ($opcionesDetalle as $opt)
                                  <option value="{{ $opt['id'] }}">{{ $opt['nombre'] }}</option>
                                @endforeach
                              </select>
                            </td>
                          </tr>
                        @empty
                          <tr>
                            <td colspan="9" class="text-center text-muted py-3">
                              <i class="fas fa-info-circle mr-1"></i> No se encontraron registros con los filtros actuales.
                            </td>
                          </tr>
                        @endforelse
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {{-- Errors --}}
          @if (count($errores) > 0)
            <div class="row mt-3">
              <div class="col-12">
                <div class="card border-warning">
                  <div class="card-header bg-warning text-white py-1">
                    <strong><i class="fas fa-exclamation-triangle mr-1"></i> Errores al procesar ({{ count($errores) }})</strong>
                  </div>
                  <div class="card-body p-0">
                    <div class="table-responsive">
                      <table class="table table-sm table-striped mb-0">
                        <thead>
                          <tr>
                            <th>Archivo</th>
                            <th>Error</th>
                          </tr>
                        </thead>
                        <tbody>
                          @foreach ($errores as $e)
                            <tr>
                              <td class="small">{{ $e['archivo'] }}</td>
                              <td class="text-danger small">{{ $e['error'] }}</td>
                            </tr>
                          @endforeach
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          @endif

          {{-- Action buttons --}}
          <div class="d-flex align-items-center mt-3" style="flex-wrap:nowrap">
            <div class="d-flex align-items-end flex-wrap mr-3" style="flex:1;min-width:0">
              <div class="form-check mr-4 mb-2 mb-sm-0">
                <input type="checkbox" class="form-check-input"
                       wire:model.live="entrada_confirmada" id="confirmarEntrada">
                <label class="form-check-label" for="confirmarEntrada">
                  <i class="fas fa-check-circle text-success mr-1"></i>
                  Confirmar ingreso a caja
                </label>
                <small class="text-muted d-block ml-4">Si no se confirma, el dinero no se contabiliza en caja hasta su confirmación posterior.</small>
              </div>
              <div class="mb-0 mr-4" style="min-width:220px;max-width:280px;flex:1">
                <label class="small mb-0" for="documento_referencia">Documento de referencia (opcional)</label>
                <input type="text" class="form-control form-control-sm"
                       wire:model.live="documento_referencia" id="documento_referencia"
                       placeholder="Nro. documento, recibo, etc.">
              </div>
            </div>
            <div class="d-flex" style="flex-shrink:0">
              <a href="{{ route('tesoreria.libro-diario.index') }}" class="btn btn-secondary mr-2">
                <i class="fas fa-times mr-1"></i> Cancelar
              </a>
              <button type="button" class="btn btn-success"
                      wire:click="generarAsientos"
                      wire:loading.attr="disabled"
                      {{ $this->cantidadSeleccionados === 0 ? 'disabled' : '' }}>
                <i class="fas fa-save mr-1"></i> Generar asientos
                @if ($this->cantidadSeleccionados > 0)
                  ({{ $this->cantidadSeleccionados }})
                @endif
              </button>
            </div>
          </div>

        @elseif (!$cargando && !$error)
          <div class="text-center text-muted py-5">
            <i class="fas fa-folder-open fa-3x mb-3"></i>
            <p class="mb-1">Ingrese la ruta de la carpeta que contiene los archivos Excel y haga clic en <strong>Procesar</strong>.</p>
            <p class="small mb-0">Mientras escribe, aparecerán sugerencias de subcarpetas disponibles.</p>
          </div>
        @endif

      </div>
    </div>
</div>

@push('scripts')
<script>
  window.addEventListener('swal:sin-documento-referencia', event => {
    const data = window.LiveEvent ? window.LiveEvent(event) : (event.detail || {});
    Swal.fire({
      title: '¿Generar sin Documento de Referencia?',
      text: `Ha seleccionado ${data.cantidad || 'varios'} ítems sin ingresar un documento de referencia. ¿Desea continuar generando los asientos sin este dato?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, generar sin referencia',
      cancelButtonText: 'Cancelar y revisar',
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#6c757d',
    }).then(result => {
      if (result.isConfirmed) {
        Livewire.dispatch('confirmar-generacion-sin-documento');
      }
    });
  });

  window.addEventListener('swal:confirmar-duplicados', event => {
    const data = window.LiveEvent ? window.LiveEvent(event) : (event.detail || {});
    Swal.fire({
      title: 'Registros duplicados',
      html: `Se encontraron <strong>${data.cantidad}</strong> registro(s) que ya existen en el Libro Diario con los mismos datos (identificador, denominación, concepto, detalle y monto).`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Descartar duplicados',
      cancelButtonText: 'Continuar de todas formas',
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      reverseButtons: true,
    }).then(result => {
      if (result.isConfirmed) {
        Livewire.dispatch('procesar-generacion', { descartarDuplicados: true });
      } else {
        Livewire.dispatch('procesar-generacion', { descartarDuplicados: false });
      }
    });
  });

  window.addEventListener('swal:items-sin-detalle', event => {
    const data = window.LiveEvent ? window.LiveEvent(event) : (event.detail || {});
    Swal.fire({
      title: 'Ítems sin detalle asignado',
      html: data.html,
      icon: 'warning',
      showCancelButton: true,
      showDenyButton: true,
      confirmButtonColor: '#28a745',
      denyButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: `<i class="fas fa-check mr-1"></i>Continuar con ${data.cantidadConDetalle} ítems`,
      denyButtonText: '<i class="fas fa-times mr-1"></i>Cancelar',
      cancelButtonText: '<i class="fas fa-arrow-left mr-1"></i>Volver',
      reverseButtons: true,
    }).then(result => {
      if (result.isConfirmed) {
        Livewire.dispatch('procesar-generacion-sin-detalle');
      }
    });
  });
</script>
@endpush
