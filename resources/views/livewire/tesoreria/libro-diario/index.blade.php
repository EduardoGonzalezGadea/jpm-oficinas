<div class="container-fluid px-0">
  @section('title', 'Libro Diario')

  <div class="card">
    <div class="card-header bg-info text-white card-header-gradient p-2">
      <div class="d-flex justify-content-between align-items-center">
        <h4 class="card-title px-1 m-0">
          <strong><i class="fas fa-book mr-2"></i>Libro Diario</strong>
        </h4>
        <div class="d-flex d-print-none">
          <div class="btn-group mr-2">
            <button type="button" class="btn btn-light btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="fas fa-file-alt"></i> Reportes
            </button>
            <div class="dropdown-menu dropdown-menu-right">
              <a class="dropdown-item" href="#" wire:click.prevent="openLibroDiarioReport">
                <i class="fas fa-book mr-2"></i>Libro Diario
              </a>
              <a class="dropdown-item" href="#" wire:click.prevent="openPersonalPolicialReport">
                <i class="fas fa-user-shield mr-2"></i>Personal Policial
              </a>
            </div>
          </div>
          <div class="btn-group mr-2">
            <button type="button" class="btn btn-light btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="fas fa-cog"></i> Opciones
            </button>
            <div class="dropdown-menu dropdown-menu-right">
              @if(auth()->user()->esAdministrador() || in_array(auth()->user()->nivelActual(), ['supervisor', 'gerente']))
                <a class="dropdown-item" href="{{ route('tesoreria.libro-diario.lb-tipos.index') }}">
                  <i class="fas fa-tag mr-2"></i>Tipos de Asiento
                </a>
              @endif
              <a class="dropdown-item" href="{{ route('tesoreria.libro-diario.lb-conceptos.index') }}">
                <i class="fas fa-folder-open mr-2"></i>Conceptos
              </a>
              <a class="dropdown-item" href="{{ route('tesoreria.libro-diario.lb-detalle.index') }}">
                <i class="fas fa-list mr-2"></i>Detalles
              </a>
              <a class="dropdown-item" href="{{ route('tesoreria.libro-diario.lb-medios.index') }}">
                <i class="fas fa-credit-card mr-2"></i>Medios de Pago
              </a>
            </div>
          </div>
          <div class="btn-group">
            <button type="button" class="btn btn-light btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="fas fa-plus"></i> Nuevo
            </button>
            <div class="dropdown-menu dropdown-menu-right">
              <a class="dropdown-item" href="#" wire:click.prevent="openCreateModal">
                <i class="fas fa-file-invoice mr-2"></i>Asiento (Entrada / Salida)
              </a>
              <a class="dropdown-item" href="#" wire:click.prevent="openRedistribucionModal">
                <i class="fas fa-exchange-alt mr-2"></i>Redistribución
              </a>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item" href="{{ route('tesoreria.gestion-cfe.index') }}">
                <i class="fas fa-file-invoice mr-2"></i>Recaudación
              </a>
              <a class="dropdown-item" href="{{ route('tesoreria.libro-diario.carga-masiva-haberes') }}">
                <i class="fas fa-upload mr-2"></i>Carga masiva de Haberes
              </a>
            </div>
          </div>
        </div>
      </div>

    </div>
    <div class="card-body px-2 pt-2">
      <form class="form-row align-items-end mb-2 d-print-none">
        <div class="col-md-auto" style="min-width:130px">
          <label class="small mb-0">Desde</label>
          <input type="date" class="form-control form-control-sm" wire:model="fecha_desde">
        </div>
        <div class="col-md-auto" style="min-width:130px">
          <label class="small mb-0">Hasta</label>
          <input type="date" class="form-control form-control-sm" wire:model="fecha_hasta">
        </div>
        <div class="col-auto">
          <label class="small mb-0">&nbsp;</label>
          <button type="button" class="btn btn-sm btn-info" wire:click="setHoy" title="Seleccionar día de hoy">
            <i class="fas fa-calendar-day"></i> Hoy
          </button>
        </div>
        <div class="col">
          <label class="small mb-0">Tipo</label>
          <select class="form-control form-control-sm" wire:model="filtro_tipo_id">
            <option value="">Todos</option>
            @foreach ($tipos as $tipo)
              <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
            @endforeach
          </select>
        </div>
        <div class="col">
          <label class="small mb-0">Detalle</label>
          <select class="form-control form-control-sm" wire:model="filtro_detalle_id">
            <option value="">Todos</option>
            @foreach ($detallesAgrupados as $conceptoNombre => $grupoDetalles)
              <optgroup label="{{ $conceptoNombre }}">
                <option value="concepto-{{ $grupoDetalles->first()->concepto_id }}">Todos los detalles</option>
                @foreach ($grupoDetalles as $detalle)
                  <option value="{{ $detalle->id }}">{{ $detalle->nombre }}</option>
                @endforeach
              </optgroup>
            @endforeach
          </select>
        </div>
        <div class="col">
          <label class="small mb-0">Buscar</label>
          <input type="text" class="form-control form-control-sm" wire:model="search" placeholder="Identidad / Denominación">
        </div>
        <div class="col-auto">
          <label class="small mb-0">&nbsp;</label>
          <button class="btn btn-sm btn-outline-secondary" wire:click="limpiarFiltros" title="Limpiar filtros">
            <i class="fas fa-undo"></i>
          </button>
        </div>
      </form>

      <div class="row mb-3">
        <div class="col-12">
          <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
              <thead class="thead-light">
                <tr>
                  <th class="text-left align-middle">TOTALES POR MEDIO DE PAGO</th>
                  @foreach ($mediosEnTabla as $medio)
                    <th class="text-center align-middle">{{ $medio->nombre_corto ?: $medio->nombre }}</th>
                  @endforeach
                </tr>
              </thead>
              <tbody>
                @if($fechaAnterior)
                  <tr>
                    <td class="text-left align-middle font-weight-bold">SALDO ANTERIOR ({{ $fechaAnterior }})</td>
                    @foreach ($mediosEnTabla as $medio)
                      @php
                        $saldo = $saldosAnterioresPorMedio->firstWhere('medio_id', $medio->id)->saldo_actual ?? 0;
                      @endphp
                      <td class="text-center align-middle font-weight-bold {{ $saldo < 0 ? 'text-danger' : '' }}">
                        $ {{ number_format($saldo, 2, ',', '.') }}
                      </td>
                    @endforeach
                  </tr>
                  <tr>
                    <td class="text-left align-middle font-weight-bold">MOVIMIENTOS EN EL PERÍODO</td>
                    @foreach ($mediosEnTabla as $medio)
                      @php
                        $item = $saldosPeriodoPorMedio->firstWhere('medio_id', $medio->id);
                        $saldoPeriodo = $item->saldo_actual ?? 0;
                        $entradas = $item->total_entradas ?? 0;
                        $salidas = $item->total_salidas ?? 0;
                      @endphp
                      <td class="text-center align-middle">
                        <div class="font-weight-bold {{ $saldoPeriodo < 0 ? 'text-danger' : '' }}">$ {{ number_format($saldoPeriodo, 2, ',', '.') }}</div>
                        <small class="font-weight-normal" style="font-size:65%;line-height:1">ENT. = $ {{ number_format($entradas, 2, ',', '.') }} - SAL. = $ {{ number_format($salidas, 2, ',', '.') }}</small>
                      </td>
                    @endforeach
                  </tr>
                @endif
                <tr>
                  <td class="text-left align-middle font-weight-bold">SALDO ACTUAL</td>
                  @foreach ($mediosEnTabla as $medio)
                    @php
                      $saldo = $saldosPorMedio->firstWhere('medio_id', $medio->id)->saldo_actual ?? 0;
                    @endphp
                    <td class="text-center align-middle font-weight-bold {{ $saldo < 0 ? 'text-danger' : '' }}">
                      $ {{ number_format($saldo, 2, ',', '.') }}
                    </td>
                  @endforeach
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div style="overflow-x:auto">
        <table class="table table-bordered table-sm table-hover mb-0" style="width:100%">
          <thead>
            <tr>
              <th class="text-center align-middle" style="width:75px">Fecha</th>
              <th class="text-center align-middle" style="width:40px">N°</th>
              <th class="text-center align-middle" style="width:65px">Tipo</th>
              <th class="text-center align-middle">Concepto / Detalle</th>
              <th class="text-center align-middle">Identidad</th>
              <th class="text-center align-middle" style="width:105px">Monto</th>
              <th class="text-center align-middle d-print-none" style="width:90px">Saldo</th>
              <th class="text-center align-middle d-print-none" style="width:80px">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($items as $item)
              <tr>
                <td class="text-center align-middle">{{ $item->fecha->format('d/m/Y') }}</td>
                <td class="text-center align-middle">{{ $item->numero }}</td>
                <td class="text-center align-middle {{ $item->signo_efectivo === -1 ? 'table-danger' : ($item->signo_efectivo === 1 ? 'table-success' : '') }}">
                  {{ $item->tipo->nombre ?? '—' }}
                  @if($item->grupo_redistribucion_id)
                    <i class="fas fa-exchange-alt text-muted ml-1" title="Parte de una redistribución"></i>
                  @endif
                </td>
                <td class="text-left align-middle">
                  <div>{{ $item->concepto->nombre ?? '—' }}</div>
                  @if($item->detalle)
                    <div class="text-muted" style="font-size:65%">{{ $item->detalle->nombre }}</div>
                  @endif
                </td>
                <td class="text-left align-middle">
                  @if($item->identidad || $item->denominacion)
                    {{ $item->identidad }}@if($item->identidad && $item->denominacion) - @endif{{ $item->denominacion }}
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="text-right align-middle font-weight-bold" style="white-space:nowrap">
                  <span class="{{ $item->signo_efectivo === -1 ? 'text-danger' : '' }}">
                    {{ $item->signo_efectivo === -1 ? '-' : '+' }} $ {{ number_format($item->monto, 2, ',', '.') }}
                  </span>
                  <span class="text-muted d-block" style="font-size:65%;line-height:1">{{ $item->medio->nombre_corto ?? $item->medio->nombre ?? '—' }}</span>
                </td>
                <td class="text-right align-middle d-print-none" style="white-space:nowrap">$ {{ number_format($item->saldo, 2, ',', '.') }}</td>
                <td class="text-center align-middle d-print-none" style="white-space:nowrap">
                  <div class="btn-group btn-group-sm" role="group">
                    <button wire:click="showDetails({{ $item->id }})"
                      class="btn btn-info" title="Ver" style="padding:0.15rem 0.3rem;font-size:inherit"><i class="fas fa-eye"></i></button>
                    <button wire:click="openEditModal({{ $item->id }})"
                      class="btn btn-primary" title="Editar" style="padding:0.15rem 0.3rem;font-size:inherit"><i class="fas fa-edit"></i></button>
                    <button
                      onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('swal:confirm', { detail: { title: '¿Estás seguro?', text: '¡No podrás revertir esto! Se recalcularán los saldos.', method: 'destroy', id: {{ $item->id }}, confirmButtonText: 'Sí, elimínalo' } }))"
                      class="btn btn-danger" title="Eliminar" style="padding:0.15rem 0.3rem;font-size:inherit"><i class="fas fa-trash-alt"></i></button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center py-3">No hay asientos registrados en el período seleccionado.</td>
              </tr>
            @endforelse
          </tbody>
          <tfoot>
            <tr class="font-weight-bold bg-light">
              <td colspan="5" class="text-right align-middle">MONTO TOTAL</td>
              <td class="text-right align-middle" style="white-space:nowrap">
                $ {{ number_format($totales['entradas'] - $totales['salidas'], 2, ',', '.') }}
              </td>
              <td colspan="1"></td>
            </tr>
          </tfoot>
        </table>
      </div>

    </div>
  </div>

  {{-- Create Modal --}}
  <div wire:ignore.self class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-file-invoice mr-2"></i>Nuevo Asiento</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" wire:click="resetCreateForm">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form>
            <div class="form-row">
              <div class="form-group col-md-4">
                <label for="fecha">Fecha *</label>
                <input type="date" class="form-control @error('fecha') is-invalid @enderror"
                  wire:model.defer="fecha" id="fecha">
                @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="form-group col-md-4">
                <label for="tipo_id">Tipo *</label>
                <select class="form-control @error('tipo_id') is-invalid @enderror"
                  wire:model="tipo_id" id="tipo_id">
                  <option value="">Seleccione...</option>
                  @foreach ($tipos as $tipo)
                    <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                  @endforeach
                </select>
                @error('tipo_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="form-group col-md-4">
                <label for="medio_id">Medio *</label>
                <select class="form-control @error('medio_id') is-invalid @enderror"
                  wire:model.defer="medio_id" id="medio_id" {{ $asiento_base_id ? 'disabled' : '' }}>
                  <option value="">Seleccione...</option>
                  @foreach ($medios as $medio)
                    <option value="{{ $medio->id }}">{{ $medio->nombre }}</option>
                  @endforeach
                </select>
                @error('medio_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="concepto_id">Concepto *</label>
                <select class="form-control @error('concepto_id') is-invalid @enderror"
                  wire:model="concepto_id" id="concepto_id">
                  <option value="">Seleccione...</option>
                  @foreach ($conceptos as $concepto)
                    <option value="{{ $concepto->id }}">{{ $concepto->nombre }}</option>
                  @endforeach
                </select>
                @error('concepto_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="form-group col-md-6">
                <label for="detalle_id">Detalle *</label>
                <select class="form-control @error('detalle_id') is-invalid @enderror"
                  wire:model="detalle_id" id="detalle_id" {{ !$concepto_id ? 'disabled' : '' }}>
                  <option value="">Seleccione un concepto primero...</option>
                  @foreach ($detalles as $detalle)
                    <option value="{{ $detalle->id }}">{{ $detalle->nombre }}</option>
                  @endforeach
                </select>
                @error('detalle_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>
            @if (count($asientos_base))
              <div class="border rounded bg-light px-3 py-2 mb-3">
                <div class="d-flex align-items-center mb-2">
                  <i class="fas fa-landmark text-primary mr-2"></i>
                  <div>
                    <div class="font-weight-bold small">Usar saldo existente</div>
                    <div class="small text-muted">Opcional: elija un asiento con saldo disponible. Podrá ajustar el monto antes de registrar.</div>
                  </div>
                </div>
                <select class="form-control form-control-sm @error('asiento_base_id') is-invalid @enderror"
                  wire:model="asiento_base_id" id="asiento_base_id">
                  <option value="">Agregar importe e identificación manualmente...</option>
                  @foreach ($asientos_base as $asientoBase)
                    <option value="{{ data_get($asientoBase, 'id') }}">
                      #{{ data_get($asientoBase, 'numero') }} - {{ \Carbon\Carbon::parse(data_get($asientoBase, 'fecha'))->format('d/m/Y') }}
                      - {{ data_get($asientoBase, 'medio.nombre', 'Sin medio') }}
                      @if(data_get($asientoBase, 'identidad') || data_get($asientoBase, 'denominacion'))
                        - {{ data_get($asientoBase, 'identidad') }} {{ data_get($asientoBase, 'denominacion') }}
                      @endif
                      - Disponible: $ {{ number_format(abs(data_get($asientoBase, 'saldo_actual')), 2, ',', '.') }}
                    </option>
                  @endforeach
                </select>
                @error('asiento_base_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
              </div>
            @endif
            <div class="form-row">
              <div class="form-group col-md-4">
                <label for="monto">Monto *</label>
                <input type="number" step="0.01" min="0.01" class="form-control @error('monto') is-invalid @enderror"
                  wire:model.defer="monto" id="monto" placeholder="0.00">
                @error('monto') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="form-group col-md-8">
                <label for="identidad">Identidad</label>
                <input type="text" class="form-control @error('identidad') is-invalid @enderror"
                  wire:model.defer="identidad" id="identidad" placeholder="Cédula / RUT">
                @error('identidad') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="denominacion">Denominación</label>
                <input type="text" class="form-control @error('denominacion') is-invalid @enderror"
                  wire:model.defer="denominacion" id="denominacion" placeholder="Nombre / Razón social">
                @error('denominacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="form-group col-md-6">
                <label for="descripcion">Descripción</label>
                <textarea class="form-control @error('descripcion') is-invalid @enderror"
                  wire:model.defer="descripcion" id="descripcion" rows="1" placeholder="Descripción del asiento"></textarea>
                @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal" wire:click="resetCreateForm">Cancelar</button>
          <button type="button" class="btn btn-primary" wire:click="store" wire:loading.attr="disabled">
            <i class="fas fa-save mr-1"></i> Registrar
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- Redistribución Modal --}}
  <div wire:ignore.self class="modal fade" id="redistribucionModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content border-0 shadow">
        <div class="modal-header border-0 text-white" style="background:linear-gradient(135deg,#173f35,#266a55)">
          <div>
            <div class="text-uppercase small font-weight-bold" style="letter-spacing:1.2px;opacity:.72">Libro diario</div>
            <h5 class="modal-title mb-0"><i class="fas fa-exchange-alt mr-2"></i>Redistribución de fondos</h5>
          </div>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" wire:click="resetRedistribucionForm">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body p-4" style="background:#f5f7f6">
          <form>
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 px-3 py-2 bg-white rounded border">
              <div class="small text-muted"><i class="fas fa-info-circle mr-1 text-primary"></i>Traslade un importe entre dos subcuentas sin alterar el total del Libro Diario.</div>
              <div class="form-group mb-0 mt-2 mt-md-0">
                <label for="rd_fecha" class="small text-uppercase font-weight-bold text-muted mb-1">Fecha *</label>
                <input type="date" class="form-control @error('rd_fecha') is-invalid @enderror"
                  wire:model.defer="rd_fecha" id="rd_fecha">
                @error('rd_fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>
            <div class="row no-gutters align-items-stretch">
              <div class="col-lg-5 pr-lg-3 mb-3 mb-lg-0">
                <div class="h-100 bg-white rounded border-top border-danger shadow-sm p-3" style="border-top-width:4px!important">
                  <div class="d-flex justify-content-between align-items-start mb-3">
                    <div><span class="badge badge-danger px-2 py-1">SALE</span><h6 class="mb-0 mt-2 font-weight-bold">Cuenta de origen</h6></div>
                    <i class="fas fa-arrow-up text-danger fa-lg"></i>
                  </div>
                  <p class="small text-muted">Filtre la subcuenta y elija el asiento que tiene saldo disponible.</p>
                  <div class="form-group">
                <label for="rd_origen_concepto_id">Concepto origen *</label>
                <select class="form-control @error('rd_origen_concepto_id') is-invalid @enderror"
                  wire:model="rd_origen_concepto_id" id="rd_origen_concepto_id">
                  <option value="">Seleccione...</option>
                  @foreach ($rd_origen_conceptos as $concepto)
                    <option value="{{ $concepto->id }}">{{ $concepto->nombre }}</option>
                  @endforeach
                </select>
                @error('rd_origen_concepto_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
                  <div class="form-group">
                <label for="rd_origen_detalle_id">Detalle origen *</label>
                <select class="form-control @error('rd_origen_detalle_id') is-invalid @enderror"
                  wire:model="rd_origen_detalle_id" id="rd_origen_detalle_id" {{ !$rd_origen_concepto_id ? 'disabled' : '' }}>
                  <option value="">Seleccione un concepto primero...</option>
                  @foreach ($rd_origen_detalles as $detalle)
                    <option value="{{ $detalle->id }}">{{ $detalle->nombre }}</option>
                  @endforeach
                </select>
                @error('rd_origen_detalle_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
                  <div class="form-group mb-0">
                <label for="rd_asiento_id">Asiento *</label>
                <select class="form-control @error('rd_asiento_id') is-invalid @enderror"
                  wire:model="rd_asiento_id" id="rd_asiento_id" {{ !$rd_origen_detalle_id ? 'disabled' : '' }}>
                  <option value="">Seleccione concepto y detalle primero...</option>
                  @foreach ($rd_asientos as $asiento)
                    <option value="{{ $asiento->id }}">
                      #{{ $asiento->numero }} — {{ $asiento->fecha->format('d/m/Y') }}
                      @if($asiento->identidad) — {{ $asiento->identidad }} @endif
                      — $ {{ number_format($asiento->saldo_actual, 2, ',', '.') }}
                    </option>
                  @endforeach
                </select>
                @error('rd_asiento_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                  </div>
                </div>
              </div>
              <div class="col-lg-2 d-flex align-items-center justify-content-center py-2">
                <div class="text-center">
                  <div class="rounded-circle bg-white border shadow-sm d-inline-flex align-items-center justify-content-center" style="width:58px;height:58px"><i class="fas fa-long-arrow-alt-right fa-2x text-primary"></i></div>
                  <div class="small text-muted mt-2 font-weight-bold">REDISTRIBUYE</div>
                </div>
              </div>
              <div class="col-lg-5 pl-lg-3">
                <div class="h-100 bg-white rounded border-top border-success shadow-sm p-3" style="border-top-width:4px!important">
                  <div class="d-flex justify-content-between align-items-start mb-3">
                    <div><span class="badge badge-success px-2 py-1">ENTRA</span><h6 class="mb-0 mt-2 font-weight-bold">Cuenta de destino</h6></div>
                    <i class="fas fa-arrow-down text-success fa-lg"></i>
                  </div>
                  <p class="small text-muted">Defina la subcuenta que recibirá el importe.</p>
                  <div class="form-group">
                    <label for="rd_concepto_id">Concepto destino *</label>
                    <select class="form-control @error('rd_concepto_id') is-invalid @enderror" wire:model="rd_concepto_id" id="rd_concepto_id">
                      <option value="">Seleccione...</option>
                      @foreach ($conceptos as $concepto)<option value="{{ $concepto->id }}">{{ $concepto->nombre }}</option>@endforeach
                    </select>
                    @error('rd_concepto_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                  </div>
                  <div class="form-group">
                    <label for="rd_detalle_id">Detalle destino *</label>
                    <select class="form-control @error('rd_detalle_id') is-invalid @enderror" wire:model="rd_detalle_id" id="rd_detalle_id" {{ !$rd_concepto_id ? 'disabled' : '' }}>
                      <option value="">Seleccione un concepto primero...</option>
                      @foreach ($rd_detalles as $detalle)<option value="{{ $detalle->id }}">{{ $detalle->nombre }}</option>@endforeach
                    </select>
                    @error('rd_detalle_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                  </div>
                  <div class="form-group mb-0">
                    <label for="rd_medio_id">Medio de pago *</label>
                    <select class="form-control @error('rd_medio_id') is-invalid @enderror" wire:model.defer="rd_medio_id" id="rd_medio_id">
                      <option value="">Seleccione...</option>
                      @foreach ($medios as $medio)<option value="{{ $medio->id }}">{{ $medio->nombre }}</option>@endforeach
                    </select>
                    @error('rd_medio_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                  </div>
                </div>
              </div>
            </div>
            <div class="row mt-3">
              <div class="col-lg-5">
                <div class="bg-white border rounded shadow-sm p-3 h-100">
                  <label for="rd_monto" class="small text-uppercase font-weight-bold text-muted">Importe a redistribuir *</label>
                <input type="number" step="0.01" min="0.01" class="form-control @error('rd_monto') is-invalid @enderror"
                  wire:model.defer="rd_monto" id="rd_monto" placeholder="0.00" {{ !$rd_asiento_id ? 'readonly' : '' }}>
                @error('rd_monto') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
              </div>
              <div class="col-lg-7">
                <div class="bg-white border rounded shadow-sm p-3">
                  <div class="small text-uppercase font-weight-bold text-muted mb-2"><i class="fas fa-receipt mr-1"></i>Resumen de operación</div>
                  <div class="d-flex justify-content-between align-items-center"><span class="text-danger"><i class="fas fa-minus-circle mr-1"></i>Origen</span><span class="small">{{ $rd_origen_concepto_id ? ($rd_origen_conceptos->firstWhere('id', $rd_origen_concepto_id)->nombre ?? 'Pendiente') : 'Pendiente' }}</span></div>
                  <div class="d-flex justify-content-between align-items-center mt-1"><span class="text-success"><i class="fas fa-plus-circle mr-1"></i>Destino</span><span class="small">{{ $rd_concepto_id ? ($conceptos->firstWhere('id', $rd_concepto_id)->nombre ?? 'Pendiente') : 'Pendiente' }}</span></div>
                  <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top"><strong>Total trasladado</strong><strong class="text-primary">$ {{ number_format((float) ($rd_monto ?: 0), 2, ',', '.') }}</strong></div>
                </div>
              </div>
            </div>
            <div class="bg-white border rounded shadow-sm p-3 mt-3">
              <div class="small text-uppercase font-weight-bold text-muted mb-2"><i class="fas fa-user-tag mr-1"></i>Identificación del destino <span class="font-weight-normal">(opcional)</span></div>
              <div class="form-row mb-0">
              <div class="form-group col-md-6 mb-md-0">
                <label for="rd_identidad">Identidad</label>
                <input type="text" class="form-control @error('rd_identidad') is-invalid @enderror"
                  wire:model.defer="rd_identidad" id="rd_identidad" placeholder="Cédula / RUT">
                @error('rd_identidad') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="form-group col-md-6 mb-0">
                <label for="rd_denominacion">Denominación</label>
                <input type="text" class="form-control @error('rd_denominacion') is-invalid @enderror"
                  wire:model.defer="rd_denominacion" id="rd_denominacion" placeholder="Nombre / Razón social">
                @error('rd_denominacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal" wire:click="resetRedistribucionForm">Cancelar</button>
          <button type="button" class="btn btn-primary" wire:click="storeRedistribucion" wire:loading.attr="disabled">
            <i class="fas fa-save mr-1"></i> Registrar Redistribución
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- Edit Modal --}}
  <div wire:ignore.self class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Editar Asiento #{{ $edit_id }}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" wire:click="resetEditForm">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p class="small text-muted">Solo se pueden modificar campos no financieros.</p>
          <form>
            <div class="form-group">
              <label for="edit_identidad">Identidad</label>
              <input type="text" class="form-control @error('edit_identidad') is-invalid @enderror"
                wire:model.defer="edit_identidad" id="edit_identidad">
              @error('edit_identidad') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
              <label for="edit_denominacion">Denominación</label>
              <input type="text" class="form-control @error('edit_denominacion') is-invalid @enderror"
                wire:model.defer="edit_denominacion" id="edit_denominacion">
              @error('edit_denominacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
              <label for="edit_descripcion">Descripción</label>
              <textarea class="form-control @error('edit_descripcion') is-invalid @enderror"
                wire:model.defer="edit_descripcion" id="edit_descripcion" rows="2"></textarea>
              @error('edit_descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal" wire:click="resetEditForm">Cancelar</button>
          <button type="button" class="btn btn-primary" wire:click="update" wire:loading.attr="disabled">
            <i class="fas fa-save mr-1"></i> Actualizar
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- Details Modal --}}
  <div wire:ignore.self class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Detalles del Asiento #{{ $selectedItem->numero ?? '' }}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          @if ($selectedItem)
            <div class="row">
              <div class="col-6"><strong>Fecha:</strong> {{ $selectedItem->fecha?->format('d/m/Y') }}</div>
              <div class="col-6"><strong>Número:</strong> {{ $selectedItem->numero }}</div>
            </div>
            <div class="row mt-2">
              <div class="col-6"><strong>Tipo:</strong> {{ $selectedItem->tipo->nombre ?? '—' }}</div>
              <div class="col-6"><strong>Signo:</strong> {{ $selectedItem->signo_efectivo === 1 ? 'Entrada (+)' : 'Salida (-)' }}</div>
            </div>
            <div class="row mt-2">
              <div class="col-6"><strong>Concepto:</strong> {{ $selectedItem->concepto->nombre ?? '—' }}</div>
              <div class="col-6"><strong>Detalle:</strong> {{ $selectedItem->detalle->nombre ?? '—' }}</div>
            </div>
            <div class="row mt-2">
              <div class="col-6"><strong>Medio:</strong> {{ $selectedItem->medio->nombre ?? '—' }}</div>
              <div class="col-6"><strong>Monto:</strong> $ {{ number_format($selectedItem->monto, 2, ',', '.') }}</div>
            </div>
            <div class="row mt-2">
              <div class="col-6"><strong>Saldo:</strong> $ {{ number_format($selectedItem->saldo, 2, ',', '.') }}</div>
              <div class="col-6">
                <strong>Identidad:</strong> {{ $selectedItem->identidad ?? '—' }}
              </div>
            </div>
            <div class="row mt-2">
              <div class="col-12"><strong>Denominación:</strong> {{ $selectedItem->denominacion ?? '—' }}</div>
            </div>
            @if($selectedItem->descripcion)
              <div class="row mt-2">
                <div class="col-12"><strong>Descripción:</strong> {{ $selectedItem->descripcion }}</div>
              </div>
            @endif
            @if($selectedItem->parent)
              <div class="row mt-2">
                <div class="col-12">
                  <strong>Asociado a:</strong> Asiento #{{ $selectedItem->parent->numero }} del {{ $selectedItem->parent->fecha?->format('d/m/Y') }}
                  ({{ $selectedItem->parent->concepto->nombre ?? '—' }} / {{ $selectedItem->parent->detalle->nombre ?? '—' }})
                </div>
              </div>
            @endif
            @if($selectedItem->grupo_redistribucion_id)
              <div class="row mt-2">
                <div class="col-12">
                  <strong>Par de redistribución:</strong>
                  @foreach($selectedItem->parRedistribucion as $par)
                    @if($par->id !== $selectedItem->id)
                      Asiento #{{ $par->numero }} — {{ $par->concepto->nombre ?? '—' }} / {{ $par->detalle->nombre ?? '—' }}
                    @endif
                  @endforeach
                </div>
              </div>
            @endif
            @if($selectedItem->children->count() > 0)
              <div class="row mt-2">
                <div class="col-12">
                  <strong>Asientos derivados:</strong>
                  @foreach($selectedItem->children as $child)
                    <div>#{{ $child->numero }} — {{ $child->concepto->nombre ?? '—' }} / {{ $child->detalle->nombre ?? '—' }}</div>
                  @endforeach
                </div>
              </div>
            @endif
          @endif
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  {{-- Personal Policial Report Modal --}}
  <div wire:ignore.self class="modal fade" id="personalPolicialModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-user-shield mr-2"></i>Personal Policial</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" wire:click="resetPersonalPolicialModal">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-row mb-3">
            <div class="col-md-4">
              <label for="pp_fecha" class="small mb-0">Fecha</label>
              <input type="date" class="form-control" wire:model="pp_fecha" id="pp_fecha">
            </div>
            <div class="col-md-8 d-flex align-items-end justify-content-end">
              <button type="button" class="btn btn-primary btn-sm" onclick="imprimirPersonalPolicial()">
                <i class="fas fa-print mr-1"></i>Imprimir
              </button>
            </div>
          </div>
          <div class="text-center mb-3">
            <h5 class="font-weight-bold mb-1">JEFATURA DE POLICÍA DE MONTEVIDEO</h5>
            <h6 class="font-weight-bold mb-1">DIRECCIÓN DE TESORERÍA</h6>
            <h6 class="mb-0">PERSONAL POLICIAL PAGADO EL DÍA {{ \Carbon\Carbon::parse($pp_fecha)->format('d/m/Y') }}</h6>
          </div>
          @if(count($pp_datos) > 0)
            <table class="table table-bordered table-sm mb-0" style="width:100%">
              <thead class="thead-light">
                <tr>
                  <th>Detalle</th>
                  <th class="text-center" style="width:80px">Cantidad</th>
                  <th class="text-right" style="width:150px">Total</th>
                  <th class="d-none d-print-table-cell" style="min-width:3cm"></th>
                  <th class="d-none d-print-table-cell" style="min-width:3cm"></th>
                </tr>
              </thead>
              <tbody>
                @foreach($pp_datos as $grupo)
                  <tr>
                    <td>
                      <strong>{{ $grupo['detalle_nombre'] }}</strong>
                    </td>
                    <td class="text-center">{{ $grupo['cantidad'] }}</td>
                    <td class="text-right">$ {{ number_format($grupo['total'], 2, ',', '.') }}</td>
                    <td class="d-none d-print-table-cell"></td>
                    <td class="d-none d-print-table-cell"></td>
                  </tr>
                @endforeach
              </tbody>
              <tfoot>
                <tr class="font-weight-bold bg-light">
                  <td>TOTAL</td>
                  <td class="text-center">{{ collect($pp_datos)->sum('cantidad') }}</td>
                  <td class="text-right">$ {{ number_format(collect($pp_datos)->sum('total'), 2, ',', '.') }}</td>
                  <td class="d-none d-print-table-cell"></td>
                  <td class="d-none d-print-table-cell"></td>
                </tr>
              </tfoot>
            </table>
            <hr>
            @foreach($pp_datos as $grupo)
              <h6 class="mt-5 mb-1"><strong>{{ $grupo['detalle_nombre'] }}</strong> <span class="text-muted">({{ $grupo['cantidad'] }} boletos)</span></h6>
              <table class="table table-bordered table-sm mb-0" style="width:100%">
                <thead class="thead-light">
                  <tr>
                    <th>Identidad</th>
                    <th>Denominación</th>
                    <th>Medio</th>
                    <th class="text-right" style="width:120px">Monto</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($grupo['items'] as $item)
                    <tr>
                      <td>{{ $item['identidad'] ?? '—' }}</td>
                      <td>{{ $item['denominacion'] ?? '—' }}</td>
                      <td>{{ $item['medio']['nombre_corto'] ?? $item['medio']['nombre'] ?? '—' }}</td>
                      <td class="text-right">$ {{ number_format($item['monto'], 2, ',', '.') }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            @endforeach
          @else
            <div class="alert alert-info mb-0">No hay boletos en ventanilla registrados en esta fecha.</div>
          @endif
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal" wire:click="resetPersonalPolicialModal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  {{-- Libro Diario Report Modal --}}
  <div wire:ignore.self class="modal fade" id="libroDiarioReportModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-book mr-2"></i>Libro Diario</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" wire:click="resetLibroDiarioReportModal">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-row mb-3">
            <div class="col-md-4">
              <label for="ld_fecha" class="small mb-0">Fecha</label>
              <input type="date" class="form-control" wire:model="ld_fecha" id="ld_fecha">
            </div>
            <div class="col-md-8 d-flex align-items-end justify-content-end">
              <button type="button" class="btn btn-primary btn-sm" onclick="imprimirLibroDiario()">
                <i class="fas fa-print mr-1"></i>Imprimir
              </button>
            </div>
          </div>
          <div class="text-center mb-3">
            <h5 class="font-weight-bold mb-1">JEFATURA DE POLICÍA DE MONTEVIDEO</h5>
            <h6 class="font-weight-bold mb-1">DIRECCIÓN DE TESORERÍA</h6>
            <h6 class="mb-0">LIBRO DIARIO DEL DÍA {{ \Carbon\Carbon::parse($ld_fecha)->format('d/m/Y') }}</h6>
          </div>
          @if(count($ld_datos) > 0)
            <div style="overflow-x:auto">
              <table class="table table-bordered table-sm mb-0" style="width:100%">
                <thead class="thead-light">
                  <tr>
                    <th class="text-center" style="width:75px">Fecha</th>
                    <th class="text-center" style="width:40px">N°</th>
                    <th class="text-center" style="width:65px">Tipo</th>
                    <th class="text-center">Concepto / Detalle</th>
                    <th class="text-center">Identidad</th>
                    <th class="text-center" style="width:105px">Monto</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($ld_datos as $item)
                    <tr>
                      <td class="text-center align-middle">{{ $item->fecha->format('d/m/Y') }}</td>
                      <td class="text-center align-middle">{{ $item->numero }}</td>
                      <td class="text-center align-middle {{ $item->signo_efectivo === -1 ? 'table-danger' : ($item->signo_efectivo === 1 ? 'table-success' : '') }}">
                        {{ $item->tipo->nombre ?? '—' }}
                      </td>
                      <td class="text-left align-middle">
                        <div>{{ $item->concepto->nombre ?? '—' }}</div>
                        @if($item->detalle)
                          <div class="text-muted" style="font-size:65%">{{ $item->detalle->nombre }}</div>
                        @endif
                      </td>
                      <td class="text-left align-middle">
                        @if($item->identidad || $item->denominacion)
                          {{ $item->identidad }}@if($item->identidad && $item->denominacion) - @endif{{ $item->denominacion }}
                        @else
                          <span class="text-muted">—</span>
                        @endif
                      </td>
                      <td class="text-right align-middle font-weight-bold" style="white-space:nowrap">
                        <span class="{{ $item->signo_efectivo === -1 ? 'text-danger' : '' }}">
                          {{ $item->signo_efectivo === -1 ? '-' : '+' }} $ {{ number_format($item->monto, 2, ',', '.') }}
                        </span>
                        <span class="text-muted d-block" style="font-size:65%;line-height:1">{{ $item->medio->nombre_corto ?? $item->medio->nombre ?? '—' }}</span>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
                <tfoot>
                  <tr class="font-weight-bold bg-light">
                    <td colspan="5" class="text-right align-middle">MONTO TOTAL</td>
                    <td class="text-right align-middle" style="white-space:nowrap">
                      $ {{ number_format($ld_datos->sum(fn($i) => $i->signo_efectivo === 1 ? $i->monto : 0) - $ld_datos->sum(fn($i) => $i->signo_efectivo === -1 ? $i->monto : 0), 2, ',', '.') }}
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>
            <hr>
            <div class="row">
              <div class="col-12">
                <div class="table-responsive">
                  <table class="table table-bordered table-sm mb-0">
                    <thead class="thead-light">
                      <tr>
                        <th class="text-left align-middle">TOTALES POR MEDIO DE PAGO</th>
                        @foreach ($ld_mediosEnTabla as $medio)
                          <th class="text-center align-middle">{{ $medio->nombre_corto ?: $medio->nombre }}</th>
                        @endforeach
                      </tr>
                    </thead>
                    <tbody>
                      @if($ld_fechaAnterior)
                        <tr>
                          <td class="text-left align-middle font-weight-bold">SALDO ANTERIOR ({{ $ld_fechaAnterior }})</td>
                          @foreach ($ld_mediosEnTabla as $medio)
                            @php
                              $saldo = collect($ld_saldosAnterioresPorMedio)->firstWhere('medio_id', $medio->id)->saldo_actual ?? 0;
                            @endphp
                            <td class="text-center align-middle font-weight-bold {{ $saldo < 0 ? 'text-danger' : '' }}">
                              $ {{ number_format($saldo, 2, ',', '.') }}
                            </td>
                          @endforeach
                        </tr>
                        <tr>
                          <td class="text-left align-middle font-weight-bold">MOVIMIENTOS DEL DÍA</td>
                          @foreach ($ld_mediosEnTabla as $medio)
                            @php
                              $item = collect($ld_saldosPeriodoPorMedio)->firstWhere('medio_id', $medio->id);
                              $saldoPeriodo = $item->saldo_actual ?? 0;
                              $entradas = $item->total_entradas ?? 0;
                              $salidas = $item->total_salidas ?? 0;
                            @endphp
                            <td class="text-center align-middle">
                              <div class="font-weight-bold {{ $saldoPeriodo < 0 ? 'text-danger' : '' }}">$ {{ number_format($saldoPeriodo, 2, ',', '.') }}</div>
                              <small class="font-weight-normal" style="font-size:65%;line-height:1">ENT. = $ {{ number_format($entradas, 2, ',', '.') }} - SAL. = $ {{ number_format($salidas, 2, ',', '.') }}</small>
                            </td>
                          @endforeach
                        </tr>
                      @endif
                      <tr>
                        <td class="text-left align-middle font-weight-bold">SALDO ACTUAL</td>
                        @foreach ($ld_mediosEnTabla as $medio)
                          @php
                            $saldo = collect($ld_saldosActualesPorMedio)->firstWhere('medio_id', $medio->id)->saldo_actual ?? 0;
                          @endphp
                          <td class="text-center align-middle font-weight-bold {{ $saldo < 0 ? 'text-danger' : '' }}">
                            $ {{ number_format($saldo, 2, ',', '.') }}
                          </td>
                        @endforeach
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          @else
            <div class="alert alert-info mb-0">No hay asientos registrados en esta fecha.</div>
          @endif
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal" wire:click="resetLibroDiarioReportModal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    function imprimirPersonalPolicial() {
      var contenido = document.querySelector('#personalPolicialModal .modal-body').cloneNode(true);

      var ventana = window.open('', '_blank', 'width=1200,height=800');
      ventana.document.write('<html><head><title>Personal Policial</title>');
      ventana.document.write('<style>');
      ventana.document.write('@page { size: landscape; margin: 1.5cm; }');
      ventana.document.write('body { font-family: Arial, sans-serif; font-size: 12pt; padding: 0; margin: 0; }');
      ventana.document.write('table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }');
      ventana.document.write('th, td { border: 1px solid #000; padding: 4px 6px; font-size: 11pt; }');
      ventana.document.write('th { background: #e0e0e0; text-align: center; font-weight: bold; }');
      ventana.document.write('td:last-child, th:last-child { min-width: 3cm; }');
      ventana.document.write('td:nth-last-child(2), th:nth-last-child(2) { min-width: 3cm; }');
      ventana.document.write('.text-center { text-align: center; }');
      ventana.document.write('.text-right { text-align: right; }');
      ventana.document.write('h5 { font-size: 14pt; margin: 0 0 2px 0; }');
      ventana.document.write('h6 { font-size: 12pt; margin: 0 0 2px 0; font-weight: bold; }');
      ventana.document.write('.mt-5 { margin-top: 3rem !important; }');
      ventana.document.write('.mb-1 { margin-bottom: 0.25rem !important; }');
      ventana.document.write('.mb-0 { margin-bottom: 0 !important; }');
      ventana.document.write('.mb-3 { margin-bottom: 1rem !important; }');
      ventana.document.write('hr { margin: 10px 0; }');
      ventana.document.write('.d-print-table-cell { display: table-cell !important; }');
      ventana.document.write('.d-none.d-print-table-cell { display: table-cell !important; }');
      ventana.document.write('.form-row, .alert, .d-none:not(.d-print-table-cell) { display: none !important; }');
      ventana.document.write('</style>');
      ventana.document.write('</head><body>');
      ventana.document.write(contenido.innerHTML);
      ventana.document.write('</body></html>');
      ventana.document.close();
      ventana.focus();
      setTimeout(function() { ventana.print(); ventana.close(); }, 500);
    }

    function imprimirLibroDiario() {
      var contenido = document.querySelector('#libroDiarioReportModal .modal-body').cloneNode(true);

      var ventana = window.open('', '_blank', 'width=1200,height=800');
      ventana.document.write('<html><head><title>Libro Diario</title>');
      ventana.document.write('<style>');
      ventana.document.write('@page { size: portrait; margin: 1.5cm; }');
      ventana.document.write('body { font-family: Arial, sans-serif; font-size: 10pt; padding: 0; margin: 0; }');
      ventana.document.write('table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }');
      ventana.document.write('th, td { border: 1px solid #000; padding: 3px 5px; font-size: 9pt; }');
      ventana.document.write('th { background: #e0e0e0; text-align: center; font-weight: bold; }');
      ventana.document.write('.text-center { text-align: center; }');
      ventana.document.write('.text-right { text-align: right; }');
      ventana.document.write('.text-left { text-align: left; }');
      ventana.document.write('.text-muted { color: #555 !important; }');
      ventana.document.write('.text-danger { color: #c00 !important; }');
      ventana.document.write('.table-success { background-color: #d4edda; }');
      ventana.document.write('.table-danger { background-color: #f8d7da; }');
      ventana.document.write('h5 { font-size: 13pt; margin: 0 0 2px 0; }');
      ventana.document.write('h6 { font-size: 11pt; margin: 0 0 2px 0; font-weight: bold; }');
      ventana.document.write('.mb-0 { margin-bottom: 0 !important; }');
      ventana.document.write('.mb-1 { margin-bottom: 0.25rem !important; }');
      ventana.document.write('.mb-3 { margin-bottom: 1rem !important; }');
      ventana.document.write('.align-middle { vertical-align: middle !important; }');
      ventana.document.write('.form-row, .alert, .d-none:not(.d-print-table-cell) { display: none !important; }');
      ventana.document.write('</style>');
      ventana.document.write('</head><body>');
      ventana.document.write(contenido.innerHTML);
      ventana.document.write('</body></html>');
      ventana.document.close();
      ventana.focus();
      setTimeout(function() { ventana.print(); ventana.close(); }, 500);
    }
  </script>

  @push('scripts')
    <script>
      window.addEventListener('show-modal', event => {
        $('#' + event.detail.id).modal('show');
      });

      window.addEventListener('close-modal', event => {
        $('#' + event.detail.id).modal('hide');
      });

      window.addEventListener('swal:confirm', event => {
        Swal.fire({
          title: event.detail.title,
          text: event.detail.text,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: event.detail.confirmButtonText,
          cancelButtonText: 'Cancelar',
          focusConfirm: true
        }).then((result) => {
          if (result.isConfirmed) {
            @this.call(event.detail.method, event.detail.id);
          }
        });
      });

      window.addEventListener('alert', event => {
        Swal.fire({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
          icon: event.detail.type,
          title: event.detail.message,
        });
      });

      window.addEventListener('swal:confirmar-eliminar-asiento-con-cfe', event => {
        const data = event.detail;
        Swal.fire({
          title: '¿Está seguro?',
          html: `Este asiento está asociado al CFE <strong>${data.cfeTipo} ${data.cfeSerie}${data.cfeNumero}</strong> que también será eliminado. ¿Desea continuar?`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Sí, eliminar todo',
          cancelButtonText: 'Cancelar'
        }).then((result) => {
          if (result.isConfirmed) {
            @this.call('confirmarEliminarAsientoConCfe', data.asientoId);
          }
        });
      });

      $(document).ready(function() {
        $('[data-toggle="tooltip"]').tooltip();
      });

      if (typeof Livewire !== 'undefined') {
        var tooltipTimeout;
        Livewire.hook('message.received', function() {
          $('[data-toggle="tooltip"]').tooltip('dispose');
        });
        Livewire.hook('element.updated', function() {
          clearTimeout(tooltipTimeout);
          tooltipTimeout = setTimeout(function() {
            $('[data-toggle="tooltip"]').tooltip();
          }, 10);
        });
      }
    </script>
  @endpush
</div>
