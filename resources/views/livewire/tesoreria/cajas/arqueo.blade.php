<div>
  

  {{-- Barra de título --}}
  <div class="card shadow-sm mb-2">
    <div class="card-header card-header-section card-header-gradient py-1 px-3">
      <h5 class="mb-0 text-premium-header">
        <i class="fas fa-list-ol mr-2"></i>Arqueo de Caja
      </h5>
      <a href="{{ route('tesoreria.caja-diaria.index') }}" class="btn btn-light btn-sm py-0 px-2">
        <i class="fas fa-arrow-left mr-1"></i> Volver a la Caja Diaria
      </a>
    </div>
  </div>

  @if ($caja_actual)
    <div class="row">
      {{-- Conteo de efectivo --}}
      <div class="col-lg-7 mb-3">
        <div class="card shadow-sm h-100">
          <div class="card-header card-header-section card-header-gradient py-2 px-3">
            <h5 class="mb-0"><i class="fas fa-coins mr-2"></i>Conteo de Efectivo</h5>
          </div>
          <div class="card-body" data-enter-next>
            {{-- Botones para cargar arqueo previo o saldo inicial --}}
            <div class="row mb-3">
              <div class="col-md-6 mb-2 mb-md-0">
                <button type="button" wire:click="cargarUltimoArqueo" class="btn btn-outline-info btn-sm btn-block"
                  @if(!$arqueos_previos || $arqueos_previos->isEmpty()) disabled @endif
                  title="Cargar las cantidades del último arqueo realizado">
                  <i class="fas fa-history mr-1"></i>Cargar Último Arqueo
                </button>
              </div>
              <div class="col-md-6">
                <button type="button" wire:click="cargarSaldoInicial" class="btn btn-outline-success btn-sm btn-block"
                  title="Cargar las cantidades del saldo inicial de apertura">
                  <i class="fas fa-folder-open mr-1"></i>Cargar Saldo Inicial
                </button>
              </div>
            </div>

            <div class="alert alert-light border py-2 px-3 mb-3">
              <small class="text-muted mb-0">
                <i class="fas fa-info-circle mr-1"></i>
                <strong>Ayuda:</strong> Puede cargar el desglose del último arqueo o del saldo inicial 
                como punto de partida para facilitar el conteo.
              </small>
            </div>

            {{-- Selector de modo de cálculo (igual que apertura/cierre) --}}
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
              <table class="table table-sm table-hover mb-0">
                <thead class="thead-light">
                  <tr>
                    <th>Denominación</th>
                    <th style="width: 90px;">Cantidad</th>
                    <th style="width: 150px;">Total $</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($denominaciones as $den)
                    @php $esInvalido = in_array((string) $den->id, $desglose_invalido ?? []); @endphp
                    <tr class="{{ $esInvalido ? 'table-warning' : '' }}">
                      <td class="text-nowrap">
                        @if($esInvalido)
                          <i class="fas fa-exclamation-triangle text-warning mr-1" title="Valor no exacto"></i>
                        @endif
                        @money($den->valor)
                        <small class="text-muted">({{ $den->tipo }})</small>
                      </td>
                      <td style="width: 90px;">
                        <input type="number"
                          wire:model.blur="desglose.{{ $den->id }}.cantidad"
                          class="form-control form-control-sm"
                          @if ($modo_calculo === 'total') readonly @endif
                          min="0">
                      </td>
                      <td style="width: 150px;">
                        <div class="input-group input-group-sm">
                          <div class="input-group-prepend">
                            <span class="input-group-text">$</span>
                          </div>
                          <input type="number" class="form-control"
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
                    <th colspan="2" class="text-right">Total Efectivo</th>
                    <th>@money($total_efectivo)</th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
      </div>

      {{-- Balance y otros medios --}}
      <div class="col-lg-5 mb-3">
        <div class="card shadow-sm h-100">
          <div class="card-header card-header-section card-header-gradient py-2 px-3">
            <h5 class="mb-0"><i class="fas fa-balance-scale mr-2"></i>Balance del Arqueo</h5>
          </div>
          <div class="card-body">
            {{-- Otros medios (información colapsable) --}}
            <button type="button" class="btn btn-outline-secondary btn-sm btn-block mb-3"
              data-toggle="collapse" data-target="#otrosMediosArqueo" aria-expanded="false"
              aria-controls="otrosMediosArqueo">
              <i class="fas fa-credit-card mr-1"></i>Otros medios (transferencias, cheques, tarjetas)
              <i class="fas fa-chevron-down ml-1"></i>
            </button>
            <div class="collapse" id="otrosMediosArqueo">
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

            <div
              class="alert {{ $diferencia == 0 ? 'alert-success' : ($diferencia > 0 ? 'alert-info' : 'alert-danger') }} mb-3">
              <h6 class="alert-heading mb-2">
                <i class="fas fa-balance-scale mr-1"></i>Resumen del Arqueo
              </h6>
              <table class="table table-sm table-borderless mb-0">
                <tr>
                  <td class="text-muted"><i class="fas fa-wallet mr-1"></i>Saldo Inicial</td>
                  <td class="text-right font-weight-bold">@money($caja_actual->saldo_inicial)</td>
                </tr>
                <tr>
                  <td class="text-muted"><i class="fas fa-long-arrow-alt-down mr-1"></i>Total Entradas (Efectivo)</td>
                  <td class="text-right font-weight-bold">@money($caja_actual->totalIngresos())</td>
                </tr>
                <tr>
                  <td class="text-muted"><i class="fas fa-long-arrow-alt-up mr-1"></i>Total Salidas (Efectivo)</td>
                  <td class="text-right font-weight-bold">@money($caja_actual->totalEgresos())</td>
                </tr>
                <tr>
                  <td class="text-muted"><i class="fas fa-coins mr-1"></i>Total Efectivo Contado</td>
                  <td class="text-right font-weight-bold">@money($total_efectivo)</td>
                </tr>
                <tr class="font-weight-bold">
                  <td>
                    <i class="fas fa-{{ $diferencia == 0 ? 'check-circle' : 'exclamation-circle' }} mr-1"></i>
                    Diferencia
                  </td>
                  <td class="text-right">@money($diferencia)</td>
                </tr>
              </table>
              <small class="text-muted">
                <i class="fas fa-info-circle mr-1"></i>Transferencias, cheques y otros medios se
                contabilizan aparte y no afectan la diferencia.
              </small>
            </div>

            <div class="form-group">
              <label for="observaciones_arqueo">Observaciones</label>
              <textarea wire:model.live="observaciones" id="observaciones_arqueo" class="form-control" rows="2"></textarea>
            </div>

            <button type="button" wire:click="guardarArqueo" class="btn btn-primary btn-block">
              <i class="fas fa-save mr-1"></i>Guardar Arqueo
            </button>
          </div>
        </div>
      </div>
    </div>

    {{-- Últimos arqueos --}}
    @if ($arqueos_previos->isNotEmpty())
      <div class="card shadow-sm mb-3">
        <button type="button" class="btn btn-outline-secondary btn-sm btn-block text-left py-2"
          data-toggle="collapse" data-target="#ultimosArqueos" aria-expanded="false"
          aria-controls="ultimosArqueos">
          <i class="fas fa-history mr-1"></i>Últimos Arqueos ({{ $arqueos_previos->count() }})
          <i class="fas fa-chevron-down ml-1 float-right mt-1"></i>
        </button>
        <div class="collapse" id="ultimosArqueos">
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm table-hover table-striped mb-0">
                <thead class="thead-dark">
                  <tr>
                    <th>Fecha/Hora</th>
                    <th class="text-right">Efectivo</th>
                    <th class="text-right">Otros Medios</th>
                    <th class="text-right">Diferencia</th>
                    <th>Usuario</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($arqueos_previos as $arqueo)
                    <tr>
                      <td class="text-nowrap">{{ $arqueo->created_at->format('d/m/Y H:i') }}</td>
                      <td class="text-right">@money($arqueo->total_efectivo)</td>
                      <td class="text-right">@money($arqueo->total_transferencias + $arqueo->total_cheques)</td>
                      <td class="text-right">
                        <span
                          class="font-weight-bold text-{{ $arqueo->diferencia == 0 ? 'success' : ($arqueo->diferencia > 0 ? 'info' : 'danger') }}">
                          @money($arqueo->diferencia)
                        </span>
                      </td>
                      <td>{{ $arqueo->usuarioRegistro->nombre_completo ?? '—' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    @endif
  @else
    <div class="alert alert-warning shadow-sm">
      <i class="fas fa-exclamation-triangle mr-2"></i>
      No hay una caja abierta para realizar el arqueo.
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
  window.addEventListener('swal:toast:warning', event => {
    const data = window.LiveEvent(event);
    Swal.fire({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 4000,
      timerProgressBar: true,
      icon: 'warning',
      title: data.title || 'Advertencia',
      text: data.text || '',
    });
  });
</script>
@endpush
