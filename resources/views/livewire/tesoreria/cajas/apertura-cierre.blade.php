<div>
  

  {{-- Barra de título --}}
  <div class="card shadow-sm mb-2">
    <div class="card-header card-header-section card-header-gradient py-1 px-3">
      <h5 class="mb-0 text-premium-header">
        <i class="fas fa-arrows-alt-h mr-2"></i>Apertura / Cierre de Caja
      </h5>
      <a href="{{ route('tesoreria.caja-diaria.index') }}" class="btn btn-light btn-sm py-0 px-2">
        <i class="fas fa-arrow-left mr-1"></i> Volver a la Caja Diaria
      </a>
    </div>
  </div>

  <div class="row">
    {{-- Sección de Apertura de Caja --}}
    <div class="col-lg-6 mb-3">
      <div class="card shadow-sm h-100">
        <div class="card-header card-header-section card-header-gradient py-2 px-3">
          <h5 class="mb-0">
            <i class="fas {{ $cajaAbierta ? 'fa-door-open' : 'fa-plus-circle' }} mr-2"></i>
            @if ($cajaAbierta)
              Caja Actual
            @else
              Apertura de Caja
            @endif
          </h5>
        </div>
        <div class="card-body">
          @if (!$cajaAbierta)
            <form wire:submit="abrirCaja" data-enter-next>
              <div class="form-group">
                <label for="fecha_apertura">Fecha de Apertura</label>
                <input type="date" wire:model.live="fecha_apertura" class="form-control"
                  id="fecha_apertura">
                @error('fecha_apertura')
                  <span class="text-danger">{{ $message }}</span>
                @enderror
              </div>

              {{-- Saldo sugerido desde caja anterior --}}
              @if ($saldo_inicial_sugerido > 0)
                <div class="alert alert-info mb-3 d-flex justify-content-between align-items-center flex-wrap">
                  <div>
                    <i class="fas fa-lightbulb mr-1"></i>
                    <strong>Saldo sugerido (cierre anterior): </strong><span class="text-nowrap">@money($saldo_inicial_sugerido)</span>
                    <small class="d-block text-muted">Puede modificarlo si el fondo de caja es diferente.</small>
                  </div>
                  <button type="button" class="btn btn-info btn-sm mt-2 mt-sm-0" wire:click="cargarDesgloseCajaAnterior" title="Cargar desgloses de la caja anterior">
                    <i class="fas fa-history mr-1"></i>Cargar caja anterior
                  </button>
                </div>
              @endif

              <div class="form-group mb-4">
                <label>Modo de Cálculo</label>
                <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                  <label
                    class="btn btn-outline-primary @if ($modo_calculo === 'cantidad') active @endif">
                    <input type="radio" wire:model.live="modo_calculo" value="cantidad"> Por Cantidad
                  </label>
                  <label
                    class="btn btn-outline-primary @if ($modo_calculo === 'total') active @endif">
                    <input type="radio" wire:model.live="modo_calculo" value="total"> Por Total
                  </label>
                </div>
              </div>

              <div class="form-group">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h6 class="text-muted mb-0"><i class="fas fa-coins mr-1"></i>Desglose Monetario Inicial</h6>
                  <button type="button" class="btn btn-outline-info btn-sm py-0.5 px-2" wire:click="cargarDesgloseCajaAnterior" title="Cargar valores de discriminación de la caja anterior">
                    <i class="fas fa-history mr-1"></i>Cargar caja anterior
                  </button>
                </div>
                <div class="table-responsive">
                  <table class="table table-sm table-hover">
                    <thead class="thead-light">
                      <tr>
                        <th class="align-middle">Denominación</th>
                        <th class="align-middle">Cantidad</th>
                        <th class="align-middle">Total</th>
                      </tr>
                    </thead>
<tbody>
                      @php $tipoAnterior = null; @endphp
                      @foreach ($denominaciones as $denominacion)
                        @php
                          $esInvalido = in_array((string) $denominacion->id, $desglose_invalido ?? []);
                          $esPrimeraMoneda = ($denominacion->tipo ?? '') === 'Monedas' && ($tipoAnterior ?? '') !== 'Monedas';
                          $tipoAnterior = $denominacion->tipo ?? NULL;
                        @endphp
                        <tr class="{{ $esInvalido ? 'table-warning' : (($denominacion->tipo ?? '') === 'Monedas' ? 'table-secondary' : '') }} {{ $esPrimeraMoneda ? 'fila-separador-monedas' : '' }}">
                          <td class="text-nowrap align-middle">
                            @if($esInvalido)
                              <i class="fas fa-exclamation-triangle text-warning mr-1" title="Valor no exacto"></i>
                            @endif
                            <span class="text-nowrap">@money($denominacion->valor)</span>
                            <small class="text-muted">({{ $denominacion->tipo }})</small>
                          </td>
                          <td style="width: 90px;" class="align-middle">
                            <input type="number" class="form-control form-control-sm tabla-apertura"
                              data-focus-den="{{ $denominacion->id }}" data-focus-campo="cantidad"
                              wire:model.blur="desglose.{{ $denominacion->id }}.cantidad"
                              @if ($modo_calculo === 'total') readonly @endif min="0">
                          </td>
                          <td style="width: 150px;" class="align-middle">
                            <div class="input-group input-group-sm">
                              <div class="input-group-prepend">
                                <span class="input-group-text">$</span>
                              </div>
                              <input type="number" class="form-control tabla-apertura"
                                data-focus-den="{{ $denominacion->id }}" data-focus-campo="total"
                                wire:model.blur="desglose.{{ $denominacion->id }}.total"
                                @if ($modo_calculo === 'cantidad') readonly @endif
                                min="0" step="{{ $denominacion->valor }}">
                            </div>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                    <tfoot>
                      <tr class="table-primary font-weight-bold">
                        <td colspan="2" class="text-right align-middle">Total:</td>
                        <td class="align-middle">@money($saldo_inicial)</td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>

              <div class="form-group">
                <label for="observaciones">Observaciones</label>
                <textarea wire:model.live="observaciones" id="observaciones" class="form-control" rows="2"></textarea>
              </div>

              <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-door-open mr-1"></i>Abrir Caja
              </button>
            </form>
          @else
            <div class="d-flex align-items-center mb-3">
              <i class="fas fa-door-open text-success fa-2x mr-2"></i>
              <span class="badge badge-success badge-pill px-3 py-2">
                <i class="fas fa-circle mr-1"></i>Caja abierta
              </span>
            </div>
            <div class="row small">
              <div class="col-6 text-muted">Fecha de Apertura</div>
              <div class="col-6 text-right font-weight-bold">@urudate($cajaAbierta->fecha_apertura)</div>
              <div class="col-6 text-muted">Hora</div>
              <div class="col-6 text-right font-weight-bold">{{ $cajaAbierta->hora_apertura_formateada }}</div>
              <div class="col-6 text-muted">Saldo Inicial</div>
              <div class="col-6 text-right font-weight-bold">@money($cajaAbierta->saldo_inicial)</div>
              <div class="col-6 text-muted">Usuario</div>
              <div class="col-6 text-right font-weight-bold">{{ $cajaAbierta->cajero->nombre_completo ?? 'No asignado' }}</div>
            </div>
          @endif
        </div>
      </div>
    </div>

    {{-- Sección de Cierre de Caja --}}
    <div class="col-lg-6 mb-3">
      <div class="card shadow-sm h-100">
        <div class="card-header card-header-section card-header-gradient py-2 px-3">
          <h5 class="mb-0"><i class="fas fa-lock mr-2"></i>Cierre de Caja</h5>
        </div>
        <div class="card-body">
          @if ($cajaAbierta)
            <ul class="list-group list-group-flush border rounded mb-3">
              <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                <span><i class="fas fa-wallet text-primary mr-2"></i>Saldo Inicial</span>
                <strong>@money($cajaAbierta->saldo_inicial)</strong>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                <span><i class="fas fa-long-arrow-alt-down text-success mr-2"></i>Total Entradas (Efectivo)</span>
                <strong><span class="text-nowrap">@money($cajaAbierta->totalIngresos())</span></strong>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                <span><i class="fas fa-long-arrow-alt-up text-danger mr-2"></i>Total Salidas (Efectivo)</span>
                <strong><span class="text-nowrap">@money($cajaAbierta->totalEgresos())</span></strong>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                <span><i class="fas fa-credit-card text-info mr-2"></i>Otros medios (apartes)</span>
                <small class="text-muted">
                  E <span class="text-nowrap">@money($cajaAbierta->totalIngresosOtros())</span> /
                  S <span class="text-nowrap">@money($cajaAbierta->totalEgresosOtros())</span>
                </small>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                <span class="font-weight-bold"><i class="fas fa-coins mr-2"></i>Saldo Final Esperado (Libro Diario)</span>
                <strong class="h6 mb-0">@money($saldo_esperado_ld)</strong>
              </li>
            </ul>

            {{-- Otros medios (información colapsable) --}}
            <button type="button" class="btn btn-outline-secondary btn-sm btn-block mb-3"
              data-toggle="collapse" data-target="#otrosMediosCierre" aria-expanded="false"
              aria-controls="otrosMediosCierre">
              <i class="fas fa-credit-card mr-1"></i>Otros medios (transferencias, cheques, tarjetas)
              <i class="fas fa-chevron-down ml-1"></i>
            </button>
            <div class="collapse" id="otrosMediosCierre">
              <div class="form-group">
                <label>Total en Transferencias</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">$</span>
                  </div>
                  <input type="number" wire:model.live="total_transferencias" class="form-control" readonly>
                </div>
              </div>
              <div class="form-group">
                <label>Total en Cheques</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">$</span>
                  </div>
                  <input type="number" wire:model.live="total_cheques" class="form-control" readonly>
                </div>
              </div>
              <div class="form-group">
                <label>Otros medios (Tarjetas, etc.)</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">$</span>
                  </div>
                  <input type="number" wire:model.live="total_otros" class="form-control" readonly>
                </div>
              </div>
            </div>

            <div data-enter-next>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="text-muted mb-0"><i class="fas fa-coins mr-1"></i>Desglose Monetario de Cierre</h6>
                <button type="button" class="btn btn-outline-info btn-sm py-0.5 px-2" wire:click="cargarUltimoArqueo" title="Tomar como base el conteo del último arqueo realizado">
                  <i class="fas fa-history mr-1"></i>Cargar último arqueo
                </button>
              </div>
              {{-- Selector de modo de cálculo (igual que apertura) --}}
              <div class="btn-group btn-group-sm w-100 mb-2" role="group" aria-label="Modo de cálculo">
                <button type="button"
                  class="btn flex-fill {{ $modo_calculo === 'cantidad' ? 'btn-secondary' : 'btn-outline-secondary' }}"
                  wire:click="$set('modo_calculo', 'cantidad')">
                  <i class="fas fa-hashtag mr-1"></i>Por Cantidad
                </button>
                <button type="button"
                  class="btn flex-fill {{ $modo_calculo === 'total' ? 'btn-secondary' : 'btn-outline-secondary' }}"
                  wire:click="$set('modo_calculo', 'total')">
                  <i class="fas fa-dollar-sign mr-1"></i>Por Total $
                </button>
              </div>
              <div class="table-responsive">
                <table class="table table-sm table-hover">
                  <thead class="thead-light">
                    <tr>
                      <th class="align-middle">Denominación</th>
                      <th style="width: 90px;" class="align-middle">Cantidad</th>
                      <th style="width: 150px;" class="align-middle">Total $</th>
                    </tr>
                  </thead>
                  <tbody>
                    @php $tipoAnterior = null; @endphp
                    @foreach ($denominaciones as $den)
                      @php
                        $esInvalido = in_array((string) $den->id, $desglose_invalido ?? []);
                        $esPrimeraMoneda = ($den->tipo ?? '') === 'Monedas' && ($tipoAnterior ?? '') !== 'Monedas';
                        $tipoAnterior = $den->tipo ?? NULL;
                      @endphp
                      <tr class="{{ $esInvalido ? 'table-warning' : (($den->tipo ?? '') === 'Monedas' ? 'table-secondary' : '') }} {{ $esPrimeraMoneda ? 'fila-separador-monedas' : '' }}">
                        <td class="text-nowrap align-middle">
                          @if($esInvalido)
                            <i class="fas fa-exclamation-triangle text-warning mr-1" title="Valor no exacto"></i>
                          @endif
                          <span class="text-nowrap">@money($den->valor)</span>
                          <small class="text-muted">({{ $den->tipo }})</small>
                        </td>
                        <td style="width: 90px;" class="align-middle">
                          <input type="number"
                            data-focus-den="{{ $den->id }}" data-focus-campo="cantidad"
                            wire:model.blur="desglose.{{ $den->id }}.cantidad"
                            class="form-control form-control-sm"
                            @if ($modo_calculo === 'total') readonly @endif
                            min="0">
                        </td>
                        <td style="width: 150px;" class="align-middle">
                          <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                              <span class="input-group-text">$</span>
                            </div>
                            <input type="number" class="form-control"
                              data-focus-den="{{ $den->id }}" data-focus-campo="total"
                              wire:model.blur="desglose.{{ $den->id }}.total"
                              @if ($modo_calculo === 'cantidad') readonly @endif
                              min="0" step="{{ $den->valor }}">
                          </div>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                  <tfoot>
                    <tr class="table-primary font-weight-bold">
                      <th colspan="2" class="align-middle">Total Efectivo Contado</th>
                      <th class="align-middle">@money($total_efectivo)</th>
                    </tr>
                  </tfoot>
                </table>
              </div>

              {{-- Balance de cierre con validación visual --}}
              <div
                class="alert {{ $diferencia == 0 ? 'alert-success' : (abs($diferencia) <= 0.50 ? 'alert-info' : 'alert-danger') }} d-flex align-items-center justify-content-between">
                <div>
                  <h6 class="alert-heading mb-1">
                    <i class="fas fa-balance-scale mr-1"></i>Balance de Cierre
                  </h6>
                  <table class="table table-sm table-borderless mb-0">
                    <tr>
                      <td class="text-muted align-middle">Total Efectivo Contado</td>
                      <td class="text-right font-weight-bold align-middle">@money($total_efectivo)</td>
                    </tr>
                    <tr>
                      <td class="text-muted align-middle">Saldo Final Esperado (Libro Diario)</td>
                      <td class="text-right font-weight-bold align-middle">@money($saldo_esperado_ld)</td>
                    </tr>
                    <tr class="font-weight-bold">
                      <td class="align-middle">
                        <i class="fas fa-{{ $diferencia == 0 ? 'check-circle text-success' : (abs($diferencia) <= 0.50 ? 'info-circle text-info' : 'exclamation-circle text-danger') }} mr-1"></i>Diferencia
                      </td>
                      <td class="text-right align-middle {{ $diferencia == 0 ? 'text-success' : (abs($diferencia) <= 0.50 ? 'text-info' : 'text-danger') }}">@money($diferencia)</td>
                    </tr>
                  </table>
                  @if ($diferencia != 0 && abs($diferencia) <= 0.50)
                    <small class="text-info d-block">
                      <i class="fas fa-info-circle mr-1"></i>
                      Diferencia dentro de tolerancia (50 centésimos).
                    </small>
                  @elseif ($diferencia == 0)
                    <small class="text-success d-block">
                      <i class="fas fa-check-circle mr-1"></i>
                      Caja cuadra perfectamente.
                    </small>
                  @endif
                  <small class="text-muted d-block mt-1">
                    <i class="fas fa-info-circle mr-1"></i>Transferencias, cheques y otros medios se contabilizan aparte y no afectan la diferencia.
                  </small>
                </div>
              </div>

              <div class="form-group">
                <label for="observaciones_cierre">Observaciones</label>
                <textarea wire:model.live="observaciones" id="observaciones_cierre" class="form-control" rows="2"></textarea>
              </div>

              @error('total_efectivo')
                <div class="alert alert-danger mb-3 py-2">
                  <i class="fas fa-exclamation-triangle mr-1"></i>{{ $message }}
                </div>
              @enderror

              <button type="button" class="btn btn-danger btn-block" onclick="confirmarCerrarCaja()">
                <i class="fas fa-lock mr-1"></i>Cerrar Caja
              </button>
            </div>
          @else
            <div class="text-center text-muted py-4">
              <i class="fas fa-lock fa-2x mb-2 d-block"></i>
              No hay una caja abierta en este momento.
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  function confirmarCerrarCaja() {
    Swal.fire({
      title: '¿Está seguro de cerrar la caja?',
      text: 'Se registrará el arqueo de cierre de la jornada.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Sí, cerrar caja',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        @this.call('cerrarCaja');
      }
    });
  }

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
  window.addEventListener('swal:toast:warning', event => {
    const data = window.LiveEvent(event);
    Swal.fire({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 4000,
      timerProgressBar: true,
      icon: 'warning',
      title: data.title || 'Valor no exacto',
      text: data.text || 'El monto ingresado no es divisible exactamente por el valor de la denominación.',
    });

    // Devolver el foco al campo con valor incorrecto
    if (data.focoDenId != null && data.focoCampo) {
      const input = document.querySelector(`[data-focus-den="${data.focoDenId}"][data-focus-campo="${data.focoCampo}"]:not([readonly])`);
      if (input) {
        setTimeout(() => {
          input.focus();
          if (input.select) input.select();
        }, 100);
      }
    }
  });
</script>
@endpush