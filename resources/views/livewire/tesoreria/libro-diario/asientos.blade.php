<div>
  <div class="row mb-3">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header bg-info text-white card-header-gradient p-2">
          <div class="d-flex justify-content-between align-items-center">
            <h4 class="card-title px-1 m-0">
              <strong><i class="fas fa-book mr-2"></i>Asientos del Libro Diario</strong>
            </h4>
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
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="{{ route('tesoreria.libro-diario.index') }}">
                  <i class="fas fa-cog mr-2"></i>Opciones
                </a>
              </div>
            </div>
          </div>
        </div>
        <div class="card-body px-2 pt-2">
          <form class="form-row align-items-end mb-2">
            <div class="col-md-2">
              <label class="small mb-0">Desde</label>
              <input type="date" class="form-control form-control-sm" wire:model="fecha_desde">
            </div>
            <div class="col-md-2">
              <label class="small mb-0">Hasta</label>
              <input type="date" class="form-control form-control-sm" wire:model="fecha_hasta">
            </div>
            <div class="col-md-2">
              <label class="small mb-0">Tipo</label>
              <select class="form-control form-control-sm" wire:model="filtro_tipo_id">
                <option value="">Todos</option>
                @foreach ($tipos as $tipo)
                  <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <label class="small mb-0">Concepto</label>
              <select class="form-control form-control-sm" wire:model="filtro_concepto_id">
                <option value="">Todos</option>
                @foreach ($conceptos as $concepto)
                  <option value="{{ $concepto->id }}">{{ $concepto->nombre }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <label class="small mb-0">Buscar</label>
              <input type="text" class="form-control form-control-sm" wire:model="search" placeholder="Identidad / Denominación">
            </div>
            <div class="col-md-1">
              <button class="btn btn-sm btn-outline-secondary w-100" wire:click="limpiarFiltros" title="Limpiar filtros">
                <i class="fas fa-undo"></i>
              </button>
            </div>
          </form>

          <div class="table-responsive">
            <table class="table table-bordered table-sm table-hover">
              <thead>
                <tr>
                  <th class="text-center align-middle" style="width:70px">N°</th>
                  <th class="text-center align-middle" style="width:90px">Fecha</th>
                  <th class="text-center align-middle" style="width:90px">Tipo</th>
                  <th class="text-center align-middle">Concepto / Detalle</th>
                  <th class="text-center align-middle">Identidad</th>
                  <th class="text-center align-middle" style="width:140px;white-space:nowrap">Monto</th>
                  <th class="text-center align-middle" style="width:110px;white-space:nowrap">Saldo</th>
                  <th class="text-center align-middle" style="width:90px">Acciones</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($items as $item)
                  <tr class="{{ $item->signo_efectivo === -1 ? 'table-danger' : ($item->signo_efectivo === 1 ? 'table-success' : '') }}">
                    <td class="text-center align-middle">{{ $item->numero }}</td>
                    <td class="text-center align-middle">{{ $item->fecha->format('d/m/Y') }}</td>
                    <td class="text-center align-middle">
                      {{ $item->tipo->nombre ?? '—' }}
                      @if($item->grupo_redistribucion_id)
                        <i class="fas fa-exchange-alt text-muted ml-1" title="Parte de una redistribución"></i>
                      @endif
                    </td>
                    <td class="text-left align-middle small">
                      <strong>{{ $item->concepto->nombre ?? '—' }}</strong>
                      @if($item->detalle)
                        <br><span class="text-muted">{{ $item->detalle->nombre }}</span>
                      @endif
                    </td>
                    <td class="text-left align-middle small">
                      @if($item->identidad || $item->denominacion)
                        {{ $item->identidad }}@if($item->identidad && $item->denominacion) - @endif{{ $item->denominacion }}
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                    <td class="text-right align-middle" style="white-space:nowrap">
                      <span class="{{ $item->signo_efectivo === -1 ? 'text-danger' : 'text-success' }}">
                        {{ $item->signo_efectivo === -1 ? '-' : '+' }} $ {{ number_format($item->monto, 2, ',', '.') }}
                      </span>
                      <br><small class="text-muted">{{ $item->medio->nombre ?? '—' }}</small>
                    </td>
                    <td class="text-right align-middle" style="white-space:nowrap">$ {{ number_format($item->saldo, 2, ',', '.') }}</td>
                    <td class="text-center align-middle">
                      <button wire:click="showDetails({{ $item->id }})"
                        class="btn btn-sm btn-info" title="Ver"><i class="fas fa-eye"></i></button>
                      <button wire:click="openEditModal({{ $item->id }})"
                        class="btn btn-sm btn-primary" title="Editar"><i class="fas fa-edit"></i></button>
                      <button
                        onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('swal:confirm', { detail: { title: '¿Estás seguro?', text: '¡No podrás revertir esto! Se recalcularán los saldos.', method: 'destroy', id: {{ $item->id }}, confirmButtonText: 'Sí, elimínalo' } }))"
                        class="btn btn-sm btn-danger" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="8" class="text-center py-3">No hay asientos registrados en el período seleccionado.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-content-between align-items-center">
            <div class="small">
              {{ $items->firstItem() }}–{{ $items->lastItem() }} de {{ $items->total() }} asientos
            </div>
            <div>{{ $items->links() }}</div>
          </div>
        </div>
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
                  wire:model.defer="tipo_id" id="tipo_id">
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
                  wire:model.defer="medio_id" id="medio_id">
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
                  wire:model.defer="detalle_id" id="detalle_id" {{ !$concepto_id ? 'disabled' : '' }}>
                  <option value="">Seleccione un concepto primero...</option>
                  @foreach ($detalles as $detalle)
                    <option value="{{ $detalle->id }}">{{ $detalle->nombre }}</option>
                  @endforeach
                </select>
                @error('detalle_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-4">
                <label for="monto">Monto *</label>
                <input type="number" step="0.01" min="0.01" class="form-control @error('monto') is-invalid @enderror"
                  wire:model.defer="monto" id="monto" placeholder="0.00">
                @error('monto') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="form-group col-md-4">
                <label for="identidad">Identidad</label>
                <input type="text" class="form-control @error('identidad') is-invalid @enderror"
                  wire:model.defer="identidad" id="identidad" placeholder="Cédula / RUT">
                @error('identidad') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="form-group col-md-4">
                <label for="denominacion">Denominación</label>
                <input type="text" class="form-control @error('denominacion') is-invalid @enderror"
                  wire:model.defer="denominacion" id="denominacion" placeholder="Nombre / Razón social">
                @error('denominacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                  <div class="d-flex justify-content-between align-items-start mb-3"><div><span class="badge badge-danger px-2 py-1">SALE</span><h6 class="mb-0 mt-2 font-weight-bold">Cuenta de origen</h6></div><i class="fas fa-arrow-up text-danger fa-lg"></i></div>
                  <p class="small text-muted">Seleccione la subcuenta desde la cual se descontará el importe.</p>
                  <div class="form-group">
                <label for="rd_origen_concepto_id">Concepto origen *</label>
                <select class="form-control @error('rd_origen_concepto_id') is-invalid @enderror"
                  wire:model="rd_origen_concepto_id" id="rd_origen_concepto_id">
                  <option value="">Seleccione...</option>
                  @foreach ($conceptos as $concepto)
                    <option value="{{ $concepto->id }}">{{ $concepto->nombre }}</option>
                  @endforeach
                </select>
                @error('rd_origen_concepto_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
                  <div class="form-group mb-0">
                <label for="rd_origen_detalle_id">Detalle origen *</label>
                <select class="form-control @error('rd_origen_detalle_id') is-invalid @enderror"
                  wire:model.defer="rd_origen_detalle_id" id="rd_origen_detalle_id" {{ !$rd_origen_concepto_id ? 'disabled' : '' }}>
                  <option value="">Seleccione un concepto primero...</option>
                  @foreach ($rd_origen_detalles as $detalle)
                    <option value="{{ $detalle->id }}">{{ $detalle->nombre }}</option>
                  @endforeach
                </select>
                @error('rd_origen_detalle_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                  </div>
                </div>
              </div>
              <div class="col-lg-2 d-flex align-items-center justify-content-center py-2"><div class="text-center"><div class="rounded-circle bg-white border shadow-sm d-inline-flex align-items-center justify-content-center" style="width:58px;height:58px"><i class="fas fa-long-arrow-alt-right fa-2x text-primary"></i></div><div class="small text-muted mt-2 font-weight-bold">REDISTRIBUYE</div></div></div>
              <div class="col-lg-5 pl-lg-3">
                <div class="h-100 bg-white rounded border-top border-success shadow-sm p-3" style="border-top-width:4px!important">
                  <div class="d-flex justify-content-between align-items-start mb-3"><div><span class="badge badge-success px-2 py-1">ENTRA</span><h6 class="mb-0 mt-2 font-weight-bold">Cuenta de destino</h6></div><i class="fas fa-arrow-down text-success fa-lg"></i></div>
                  <p class="small text-muted">Defina la subcuenta que recibirá el importe.</p>
                  <div class="form-group">
                <label for="rd_destino_concepto_id">Concepto destino *</label>
                <select class="form-control @error('rd_destino_concepto_id') is-invalid @enderror"
                  wire:model="rd_destino_concepto_id" id="rd_destino_concepto_id">
                  <option value="">Seleccione...</option>
                  @foreach ($conceptos as $concepto)
                    <option value="{{ $concepto->id }}">{{ $concepto->nombre }}</option>
                  @endforeach
                </select>
                @error('rd_destino_concepto_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
                  <div class="form-group mb-0">
                <label for="rd_destino_detalle_id">Detalle destino *</label>
                <select class="form-control @error('rd_destino_detalle_id') is-invalid @enderror"
                  wire:model.defer="rd_destino_detalle_id" id="rd_destino_detalle_id" {{ !$rd_destino_concepto_id ? 'disabled' : '' }}>
                  <option value="">Seleccione un concepto primero...</option>
                  @foreach ($rd_destino_detalles as $detalle)
                    <option value="{{ $detalle->id }}">{{ $detalle->nombre }}</option>
                  @endforeach
                </select>
                @error('rd_destino_detalle_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                  </div>
                </div>
              </div>
            </div>
            <div class="row mt-3">
              <div class="col-lg-5">
                <div class="bg-white border rounded shadow-sm p-3 h-100">
                  <div class="form-group mb-3"><label for="rd_monto" class="small text-uppercase font-weight-bold text-muted">Importe a redistribuir *</label><input type="number" step="0.01" min="0.01" class="form-control @error('rd_monto') is-invalid @enderror" wire:model.defer="rd_monto" id="rd_monto" placeholder="0.00">@error('rd_monto') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                  <div class="form-group mb-0"><label for="rd_medio_id">Medio de pago *</label><select class="form-control @error('rd_medio_id') is-invalid @enderror" wire:model.defer="rd_medio_id" id="rd_medio_id"><option value="">Seleccione...</option>@foreach ($medios as $medio)<option value="{{ $medio->id }}">{{ $medio->nombre }}</option>@endforeach</select>@error('rd_medio_id') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                </div>
              </div>
              <div class="col-lg-7">
                <div class="bg-white border rounded shadow-sm p-3 h-100">
                  <div class="small text-uppercase font-weight-bold text-muted mb-2"><i class="fas fa-receipt mr-1"></i>Resumen de operación</div>
                  <div class="d-flex justify-content-between align-items-center"><span class="text-danger"><i class="fas fa-minus-circle mr-1"></i>Origen</span><span class="small">{{ $rd_origen_concepto_id ? ($conceptos->firstWhere('id', $rd_origen_concepto_id)->nombre ?? 'Pendiente') : 'Pendiente' }}</span></div>
                  <div class="d-flex justify-content-between align-items-center mt-1"><span class="text-success"><i class="fas fa-plus-circle mr-1"></i>Destino</span><span class="small">{{ $rd_destino_concepto_id ? ($conceptos->firstWhere('id', $rd_destino_concepto_id)->nombre ?? 'Pendiente') : 'Pendiente' }}</span></div>
                  <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top"><strong>Total trasladado</strong><strong class="text-primary">$ {{ number_format((float) ($rd_monto ?: 0), 2, ',', '.') }}</strong></div>
                </div>
              </div>
            </div>
            <div class="bg-white border rounded shadow-sm p-3 mt-3">
              <div class="small text-uppercase font-weight-bold text-muted mb-2"><i class="fas fa-user-tag mr-1"></i>Identificación del destino <span class="font-weight-normal">(opcional)</span></div>
              <div class="form-row mb-0">
              <div class="form-group col-md-6 mb-md-0">
                <label for="rd_destino_identidad">Identidad (destino)</label>
                <input type="text" class="form-control @error('rd_destino_identidad') is-invalid @enderror"
                  wire:model.defer="rd_destino_identidad" id="rd_destino_identidad" placeholder="Cédula / RUT">
                @error('rd_destino_identidad') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="form-group col-md-6 mb-0">
                <label for="rd_destino_denominacion">Denominación (destino)</label>
                <input type="text" class="form-control @error('rd_destino_denominacion') is-invalid @enderror"
                  wire:model.defer="rd_destino_denominacion" id="rd_destino_denominacion" placeholder="Nombre / Razón social">
                @error('rd_destino_denominacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
    </script>
  @endpush
</div>
