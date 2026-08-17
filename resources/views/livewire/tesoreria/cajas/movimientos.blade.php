<div>
  

  <div class="card shadow-sm mb-2">
    <div class="card-header card-header-section card-header-gradient py-1 px-3">
      <h5 class="mb-0 text-premium-header">
        <i class="fas fa-exchange-alt mr-2"></i>Movimientos de Caja
      </h5>
      <a href="{{ route('tesoreria.caja-diaria.index') }}" class="btn btn-light btn-sm py-0 px-2">
        <i class="fas fa-arrow-left mr-1"></i> Volver a la Caja Diaria
      </a>
    </div>
  </div>

  @if ($caja_actual && $caja_actual->cajero_id === auth()->id())
    <div class="row">
      {{-- Formulario --}}
      <div class="col-lg-4 mb-3">
        <div class="card shadow-sm h-100">
          <div class="card-header card-header-section card-header-gradient py-2 px-3">
            <h5 class="mb-0"><i class="fas fa-plus-circle mr-2"></i>Registrar Movimiento</h5>
          </div>
          <div class="card-body py-2 px-3" data-enter-next>
            <form wire:submit="registrarMovimiento">

              <div class="form-group mb-2">
                <label class="mb-0 small font-weight-bold">Tipo <span class="text-danger">*</span></label>
                <select wire:model.live="tipo_id" class="form-control form-control-sm py-1">
                  <option value="">— Seleccione —</option>
                  @foreach ($tipos as $tipo)
                    <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                  @endforeach
                </select>
                @error('tipo_id') <span class="text-danger small">{{ $message }}</span> @enderror
              </div>

              <div class="form-group mb-2">
                <label class="mb-0 small font-weight-bold">Concepto <span class="text-danger">*</span></label>
                <select wire:model.live="concepto_id" class="form-control form-control-sm py-1">
                  <option value="">— Seleccione —</option>
                  @foreach ($conceptos as $concepto)
                    <option value="{{ $concepto->id }}">{{ $concepto->nombre }}</option>
                  @endforeach
                </select>
                @error('concepto_id') <span class="text-danger small">{{ $message }}</span> @enderror
              </div>

              <div class="form-group mb-2">
                <label class="mb-0 small font-weight-bold">Detalle <span class="text-danger">*</span></label>
                <select wire:model.live="detalle_id" class="form-control form-control-sm py-1" {{ !$concepto_id ? 'disabled' : '' }}>
                  <option value="">— Seleccione concepto primero —</option>
                  @foreach ($detalles as $detalle)
                    <option value="{{ $detalle->id }}">{{ $detalle->nombre }}</option>
                  @endforeach
                </select>
                @error('detalle_id') <span class="text-danger small">{{ $message }}</span> @enderror
              </div>

              <div class="form-group mb-2">
                <label class="mb-0 small font-weight-bold">Medio de Pago <span class="text-danger">*</span></label>
                <select wire:model.live="medio_id" class="form-control form-control-sm py-1" {{ $asiento_base_id ? 'disabled' : '' }}>
                  <option value="">— Seleccione —</option>
                  @foreach ($medios as $medio)
                    <option value="{{ $medio->id }}">{{ $medio->nombre }}</option>
                  @endforeach
                </select>
                @error('medio_id') <span class="text-danger small">{{ $message }}</span> @enderror
              </div>

              @if (count($asientos_base))
                <div class="border rounded bg-light px-2 py-1 mb-2">
                  <div class="d-flex align-items-center mb-1">
                    <i class="fas fa-landmark text-primary mr-1 small"></i>
                    <span class="small font-weight-bold">Usar saldo existente</span>
                  </div>
                  <select class="form-control form-control-sm py-1 @error('asiento_base_id') is-invalid @enderror" wire:model.live="asiento_base_id">
                    <option value="">Agregar importe e identificación manualmente...</option>
                    @foreach ($asientos_base as $asientoBase)
                      <option value="{{ data_get($asientoBase, 'id') }}">
                        #{{ data_get($asientoBase, 'numero') }} - {{ \Carbon\Carbon::parse(data_get($asientoBase, 'fecha'))->format('d/m/Y') }}
                        - {{ data_get($asientoBase, 'medio.nombre', 'Sin medio') }}
                        @if(data_get($asientoBase, 'identidad') || data_get($asientoBase, 'denominacion'))
                          - {{ data_get($asientoBase, 'identidad') }} {{ data_get($asientoBase, 'denominacion') }}
                        @endif
                        - $ {{ number_format(abs(data_get($asientoBase, 'saldo_actual')), 2, ',', '.') }}
                      </option>
                    @endforeach
                  </select>
                  @error('asiento_base_id') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
              @endif

              <div class="form-group mb-2">
                <label class="mb-0 small font-weight-bold">Monto <span class="text-danger">*</span></label>
                <div class="input-group input-group-sm">
                  <div class="input-group-prepend"><span class="input-group-text py-1">$</span></div>
                  <input type="number" wire:model.live="monto" class="form-control form-control-sm py-1" step="0.01" min="0">
                </div>
                @error('monto') <span class="text-danger small">{{ $message }}</span> @enderror
              </div>

              <div class="form-row">
                <div class="form-group mb-2 col">
                  <label class="mb-0 small font-weight-bold">Identidad</label>
                  <input type="text" wire:model.blur="identidad" class="form-control form-control-sm py-1" placeholder="Cédula / RUT">
                </div>
                <div class="form-group mb-2 col">
                  <label class="mb-0 small font-weight-bold">Denominación</label>
                  <input type="text" wire:model.blur="denominacion" class="form-control form-control-sm py-1" placeholder="Nombre">
                </div>
              </div>

              <div class="form-row">
                <div class="form-group mb-2 col">
                  <label class="mb-0 small font-weight-bold">Descripción</label>
                  <input type="text" wire:model.live="descripcion" class="form-control form-control-sm py-1" placeholder="Opcional">
                </div>
                <div class="form-group mb-2 col">
                  <label class="mb-0 small font-weight-bold">Ref. Documento</label>
                  <input type="text" wire:model.live="documento_referencia" class="form-control form-control-sm py-1" placeholder="Nro. documento">
                </div>
              </div>

              @if ($tipo_id && $medio_id)
                @php
                  $tipo = $tipos->firstWhere('id', $tipo_id);
                  $medio = $medios->firstWhere('id', $medio_id);
                @endphp
                @if ($tipo && $tipo->signo === 1 && $medio && $medio->nombre === 'Efectivo')
                  <div class="form-group mb-2">
                    <div class="custom-control custom-checkbox">
                      <input type="checkbox" class="custom-control-input"
                             wire:model.live="entrada_confirmada" id="confirmarEntrada">
                      <label class="custom-control-label small" for="confirmarEntrada">
                        Confirmar ingreso a caja
                      </label>
                    </div>
                  </div>
                @endif
              @endif

              <button type="submit" class="btn btn-primary btn-sm btn-block">
                <i class="fas fa-save mr-1"></i>Registrar
              </button>
            </form>
          </div>
        </div>
      </div>

      {{-- Listado --}}
      <div class="col-lg-8 mb-3">

        {{-- Totales por medio --}}
        @if ($totalesPorMedio->isNotEmpty())
          <div class="card shadow-sm mb-3">
            <div class="card-header py-2 px-3">
              <h6 class="mb-0"><i class="fas fa-chart-bar mr-1"></i>Totales por Medio de Pago</h6>
            </div>
            <div class="card-body py-2">
              <div class="table-responsive">
                <table class="table table-sm mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th class="align-middle">Medio</th>
                      <th class="text-right text-success align-middle">Entradas</th>
                      <th class="text-right text-danger align-middle">Salidas</th>
                      <th class="text-right align-middle">Neto</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($totalesPorMedio as $fila)
                      @php $neto = $fila->entradas - $fila->salidas; @endphp
                      <tr>
                        <td class="align-middle">
                          @if ($fila->medio_nombre === 'Efectivo')
                            <strong><i class="fas fa-money-bill-wave mr-1 text-success"></i>{{ $fila->medio_nombre }}</strong>
                          @else
                            {{ $fila->medio_nombre }}
                          @endif
                        </td>
                        <td class="text-right text-success align-middle">@money($fila->entradas)</td>
                        <td class="text-right text-danger align-middle">@money($fila->salidas)</td>
                        <td class="text-right font-weight-bold align-middle {{ $neto >= 0 ? 'text-success' : 'text-danger' }}">
                          <span class="text-nowrap">@money($neto)</span>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        @endif

        {{-- Tabla de movimientos --}}
        <div class="card shadow-sm">
          <div class="card-header card-header-section card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list mr-2"></i>Movimientos de la Caja</h5>
            <small class="text-white-50">Caja: {{ $caja_actual->cajero->nombre_completo ?? '—' }} - {{ $caja_actual->fecha_apertura->format('d/m/Y') }}</small>
          </div>
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-md-4 mb-2 mb-md-0">
                <select wire:model.live="filtroTipo" class="form-control form-control-sm">
                  <option value="">Todos los tipos</option>
                  <option value="INGRESO">Entradas</option>
                  <option value="EGRESO">Salidas</option>
                </select>
              </div>
              <div class="col-md-8">
                <input type="text" wire:model.live.debounce.400ms="search" class="form-control form-control-sm"
                  placeholder="Buscar por concepto...">
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-sm table-hover mb-0">
                <thead class="thead-dark">
                  <tr>
                    <th class="align-middle">Fecha/Hora</th>
                    <th class="align-middle">Tipo</th>
                    <th class="align-middle">Concepto / Detalle</th>
                    <th class="align-middle">Identidad</th>
                    <th class="align-middle">Medio</th>
                    <th class="align-middle text-right">Monto</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($movimientos as $mov)
                    <tr>
                      <td class="text-nowrap align-middle">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                      <td class="align-middle">
                        <span class="badge badge-{{ $mov->tipo_movimiento === 'INGRESO' ? 'success' : 'danger' }}">
                          {{ $mov->tipo_movimiento }}
                        </span>
                      </td>
                      <td class="align-middle">
                        @if ($mov->libroDiario)
                          <span class="font-weight-bold">{{ $mov->libroDiario->concepto->nombre ?? '—' }}</span>
                          <small class="d-block text-muted">{{ $mov->libroDiario->detalle->nombre ?? '' }}</small>
                          @if ($mov->libroDiario->documento_referencia)
                            <small class="d-block text-muted">
                              <i class="fas fa-file-alt mr-1"></i>{{ $mov->libroDiario->documento_referencia }}
                            </small>
                          @endif
                          @if (!$mov->libroDiario->confirmado)
                            <span class="badge badge-warning mt-1">
                              <i class="fas fa-clock"></i> Pendiente
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-success mt-1 ml-1"
                                    wire:click="confirmarIngreso({{ $mov->libroDiario->id }})"
                                    title="Confirmar ingreso a caja">
                              <i class="fas fa-check"></i> Confirmar
                            </button>
                          @endif
                        @else
                          {{ $mov->concepto }}
                          @if ($mov->descripcion)
                            <small class="d-block text-muted">{{ $mov->descripcion }}</small>
                          @endif
                        @endif
                      </td>
                      <td class="align-middle">
                        @if ($mov->libroDiario && ($mov->libroDiario->denominacion || $mov->libroDiario->identidad))
                          @if ($mov->libroDiario->denominacion)
                            <span>{{ $mov->libroDiario->denominacion }}</span>
                          @endif
                          @if ($mov->libroDiario->identidad)
                            <small class="d-block text-muted" style="font-size:75%">{{ $mov->libroDiario->identidad }}</small>
                          @endif
                        @else
                          <span class="text-muted">—</span>
                        @endif
                      </td>
                      <td class="align-middle">{{ $mov->medioPago->nombre ?? '—' }}</td>
                      <td class="text-right font-weight-bold align-middle {{ $mov->tipo_movimiento === 'INGRESO' ? 'text-success' : 'text-danger' }}">
                        <span class="text-nowrap">@money($mov->monto)</span>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="6" class="text-center text-muted py-4 align-middle">
                        No hay movimientos registrados en esta caja.
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
  @elseif ($caja_actual)
    <div class="alert alert-danger shadow-sm">
      <i class="fas fa-ban mr-2"></i>
      No tenés autorización para operar esta caja. Solo el usuario que creó la caja puede registrar movimientos en efectivo.
    </div>
  @else
    <div class="alert alert-warning shadow-sm">
      <i class="fas fa-exclamation-triangle mr-2"></i>
      No hay una caja abierta. Por favor, abra una caja para registrar movimientos.
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
