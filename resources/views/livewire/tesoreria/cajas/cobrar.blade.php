<div>
  

  <div class="card shadow-sm mb-2">
    <div class="card-header card-header-section card-header-gradient py-1 px-3">
      <h5 class="mb-0 text-premium-header">
        <i class="fas fa-hand-holding-usd mr-2 text-success"></i>Cobrar
      </h5>
      <a href="{{ route('tesoreria.caja-diaria.index') }}" class="btn btn-light btn-sm py-0 px-2">
        <i class="fas fa-arrow-left mr-1"></i> Volver a la Caja Diaria
      </a>
    </div>
  </div>

  @if ($caja_actual)
    {{-- Filtros --}}
    <div class="card shadow-sm mb-2">
      <div class="card-body py-2 px-3">
        <div class="row">
          <div class="col-md-4 mb-2 mb-md-0">
            <label class="mb-0 small font-weight-bold">Buscar</label>
            <input type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm py-1"
              placeholder="Cédula, nombre, descripción o número...">
          </div>
          <div class="col-md-3 mb-2 mb-md-0">
            <label class="mb-0 small font-weight-bold">Concepto</label>
            <select wire:model="concepto_id" class="form-control form-control-sm py-1">
              <option value="">Todos los conceptos</option>
              @foreach ($conceptos as $concepto)
                <option value="{{ $concepto->id }}">{{ $concepto->nombre }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3 mb-2 mb-md-0">
            <label class="mb-0 small font-weight-bold">Detalle</label>
            <select wire:model="detalle_id" class="form-control form-control-sm py-1" {{ !$concepto_id ? 'disabled' : '' }}>
              <option value="">@if ($concepto_id) Todos los detalles @else Seleccione concepto primero @endif</option>
              @foreach ($detalles as $detalle)
                <option value="{{ $detalle->id }}">{{ $detalle->nombre }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2 mb-2 mb-md-0">
            <label class="mb-0 small font-weight-bold">Medio</label>
            <select wire:model="medio_id" class="form-control form-control-sm py-1">
              <option value="">Todos los medios</option>
              @foreach ($medios as $medio)
                <option value="{{ $medio->id }}">{{ $medio->nombre }}</option>
              @endforeach
            </select>
          </div>
        </div>
      </div>
    </div>

    {{-- Ítems pendientes de cobro --}}
    <div class="card shadow-sm mb-2">
      <div class="card-header py-1 px-2 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 font-weight-bold"><i class="fas fa-list mr-1"></i>Ítems pendientes de cobro</h6>
        <small class="text-muted">{{ $items->count() }} resultado(s)</small>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover table-striped mb-0">
          <thead class="thead-dark">
            <tr>
              <th class="text-center align-middle" style="width: 40px;">Sel.</th>
              <th class="align-middle">Nro. / Fecha</th>
              <th class="align-middle">Concepto / Detalle</th>
              <th class="align-middle">Identidad</th>
              <th class="align-middle">Medio</th>
              <th class="text-right align-middle">Saldo pendiente</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($items as $item)
              <tr wire:key="item-{{ $item->id }}" class="{{ $seleccion_id === (int) $item->id ? 'table-primary' : '' }}">
                <td class="text-center align-middle">
                  <input type="radio" name="seleccion" wire:model.live="seleccion_id" value="{{ $item->id }}">
                </td>
                <td class="align-middle">
                  <span class="font-weight-bold">#{{ $item->numero }}</span>
                  <small class="d-block text-muted">@urudate($item->fecha)</small>
                </td>
                <td class="align-middle">
                  <span class="font-weight-bold">{{ $item->concepto->nombre ?? '—' }}</span>
                  <small class="d-block text-muted">{{ $item->detalle->nombre ?? '' }}</small>
                  @if ($item->descripcion)
                    <small class="d-block text-muted"><i class="fas fa-align-left mr-1"></i>{{ $item->descripcion }}</small>
                  @endif
                </td>
                <td class="align-middle">
                  @if ($item->denominacion || $item->identidad)
                    @if ($item->denominacion)
                      <span>{{ $item->denominacion }}</span>
                    @endif
                    @if ($item->identidad)
                      <small class="d-block text-muted" style="font-size:75%">{{ $item->identidad }}</small>
                    @endif
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="align-middle">
                  @if ($item->medio && $item->medio->nombre === 'Efectivo')
                    <span class="font-weight-bold">Efectivo</span>
                  @else
                    {{ $item->medio->nombre ?? '—' }}
                  @endif
                </td>
                <td class="text-right font-weight-bold text-danger align-middle"><span class="text-nowrap">@money(abs($item->saldo_actual)</span></td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4 align-middle">
                  No hay ítems pendientes de cobro para las condiciones seleccionadas.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Panel de cobro --}}
    @if ($seleccion_id)
      @php $itemSeleccionado = $items->firstWhere('id', (int) $seleccion_id); @endphp
      @if ($itemSeleccionado)
        <div class="card shadow-sm mb-0 border-success">
          <div class="card-header card-header-section card-header-gradient py-1 px-3">
            <h6 class="mb-0 text-premium-header">
              <i class="fas fa-hand-holding-usd mr-2"></i>Registrar cobro
            </h6>
          </div>
          <div class="card-body py-2 px-3">
            <div class="row">
              <div class="col-md-3 mb-2 mb-md-0">
                <label class="mb-0 small font-weight-bold">Monto <span class="text-danger">*</span></label>
                <div class="input-group input-group-sm">
                  <div class="input-group-prepend"><span class="input-group-text py-1">$</span></div>
                  <input type="number" wire:model.live="monto" class="form-control form-control-sm py-1" step="0.01" min="0">
                </div>
                <small class="text-muted">Pendiente: <span class="text-nowrap">@money(abs($itemSeleccionado->saldo_actual)</span></small>
                @error('monto') <span class="text-danger small">{{ $message }}</span> @enderror
              </div>
              <div class="col-md-3 mb-2 mb-md-0">
                <label class="mb-0 small font-weight-bold">Identidad <span class="text-muted">(quién paga)</span></label>
                <input type="text" wire:model.blur="identidad" class="form-control form-control-sm py-1" placeholder="Cédula / RUT">
                @error('identidad') <span class="text-danger small">{{ $message }}</span> @enderror
              </div>
              <div class="col-md-3 mb-2 mb-md-0">
                <label class="mb-0 small font-weight-bold">Denominación</label>
                <input type="text" wire:model.blur="denominacion" class="form-control form-control-sm py-1" placeholder="Nombre">
                @error('denominacion') <span class="text-danger small">{{ $message }}</span> @enderror
              </div>
              <div class="col-md-3 d-flex align-items-end flex-column justify-content-end">
                @if ($itemSeleccionado->medio && $itemSeleccionado->medio->nombre === 'Efectivo')
                  <div class="custom-control custom-checkbox mb-1">
                    <input type="checkbox" class="custom-control-input"
                           wire:model.live="entrada_confirmada" id="confirmarCobro">
                    <label class="custom-control-label small" for="confirmarCobro">
                      Confirmar ingreso a caja
                    </label>
                  </div>
                @endif
                <button type="button" class="btn btn-success btn-sm btn-block" wire:click="cobrar">
                  <i class="fas fa-hand-holding-usd mr-1"></i>Cobrar <span class="text-nowrap">@money($monto)</span>
                </button>
              </div>
            </div>
            <div class="row">
              <div class="col-md-4 mb-2 mb-md-0">
                <label class="mb-0 small font-weight-bold">Descripción</label>
                <input type="text" wire:model.live="descripcion" class="form-control form-control-sm py-1" placeholder="Opcional">
              </div>
              <div class="col-md-4 mb-2 mb-md-0">
                <label class="mb-0 small font-weight-bold">Ref. Documento</label>
                <input type="text" wire:model.live="documento_referencia" class="form-control form-control-sm py-1" placeholder="Nro. documento">
              </div>
              <div class="col-md-4 d-flex align-items-center">
                <small class="text-muted">
                  <i class="fas fa-info-circle mr-1"></i>
                  Se registrará una entrada sobre {{ $itemSeleccionado->concepto->nombre ?? 'el ítem' }} / {{ $itemSeleccionado->detalle->nombre ?? '' }}
                  y se asentará en el Libro Diario.
                </small>
              </div>
            </div>
          </div>
        </div>
      @endif
    @endif
  @else
    <div class="alert alert-warning shadow-sm mb-0">
      <i class="fas fa-exclamation-triangle mr-2"></i>
      No hay una caja abierta. Por favor, abra una caja para registrar cobros.
    </div>
  @endif
</div>

@push('scripts')
<script>
  window.addEventListener('alert', event => {
    const data = window.LiveEvent(event);
    Swal.fire({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
      icon: data.type,
      title: data.message,
    });
  });
</script>
@endpush
