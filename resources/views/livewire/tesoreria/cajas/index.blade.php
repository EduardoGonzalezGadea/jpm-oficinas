<div wire:poll.visible.300s>
  {{-- Auto-refresh cada 5 minutos solo cuando la vista está activa --}}

  <style>
    .nav-tabs {
      border-bottom: 2px solid #dee2e6;
    }
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
    html.dark-theme .nav-tabs {
      border-bottom-color: rgba(255,255,255,.15);
    }
    html.dark-theme .nav-tabs .nav-link.active {
      border-left-color: rgba(255,255,255,.2);
      border-right-color: rgba(255,255,255,.2);
      border-top-color: #5bc0de;
      border-bottom-color: transparent;
    }
  </style>

  {{-- Barra de título --}}
  <div class="card shadow-sm mb-2">
    <div class="card-header card-header-section card-header-gradient py-1 px-3">
      <h5 class="mb-0 text-premium-header">
        <i class="fas fa-cash-register mr-2"></i>Caja Diaria
      </h5>
      
      {{-- Indicador de actualización --}}
      <span wire:loading wire:target="$refresh" class="badge badge-light ml-2">
        <i class="fas fa-sync-alt fa-spin"></i> Actualizando...
      </span>
      
      <div>
        <a href="{{ route('tesoreria.caja-diaria.apertura-cierre') }}" class="btn btn-light btn-sm">
          <i class="fas fa-arrows-alt-h mr-1"></i>Apertura / Cierre
        </a>
        <a href="{{ route('tesoreria.caja-diaria.arqueo') }}" class="btn btn-light btn-sm">
          <i class="fas fa-list-ol mr-1"></i>Arqueo
        </a>
        <a href="{{ route('tesoreria.caja-diaria.movimientos') }}" class="btn btn-light btn-sm">
          <i class="fas fa-exchange-alt mr-1"></i>Movimientos
        </a>
      </div>
    </div>
  </div>

  {{-- Selector de fecha --}}
  <div class="card shadow-sm mb-2">
    <div class="card-body py-1 px-3">
      <div class="row align-items-center">
        <div class="col-md-6 col-lg-4">
          <div class="input-group input-group-sm">
            <div class="input-group-prepend">
              <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
            </div>
            <input type="date" wire:model.live="fechaSeleccionada" id="fechaSeleccionada"
              class="form-control form-control-sm" title="Seleccione la fecha a visualizar">
            <div class="input-group-append">
              <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="irAHoy">
                Hoy
              </button>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-8 text-md-right mt-1 mt-md-0">
          <span class="text-muted small">
            <i class="fas fa-tasks mr-1"></i>Cajas de la jornada: <strong>{{ $cajasDelDia->count() }}</strong>
            @if ($cajaTrabajo && $cajaTrabajo->estado === 'abierta')
              <span class="badge badge-success ml-2"><i class="fas fa-door-open mr-1"></i>Tu caja está abierta (del {{ optional($cajaTrabajo->fecha_apertura)->format('d/m/Y') }})</span>
            @endif
          </span>
        </div>
      </div>
    </div>
  </div>

  {{-- CTA cuando no hay caja propia hoy --}}
  @if ($esHoy && !$cajaTrabajo)
    <div class="alert alert-warning shadow-sm py-1 px-3 d-flex align-items-center justify-content-between mb-2">
      <div class="small">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        No tenés una caja abierta para el día de hoy. Para comenzar la jornada, abrí una caja.
      </div>
      <a href="{{ route('tesoreria.caja-diaria.apertura-cierre') }}" class="btn btn-info btn-sm py-0">
        <i class="fas fa-arrows-alt-h mr-1"></i>Abrir Caja
      </a>
    </div>
  @endif

  {{-- Pestañas --}}
  <ul class="nav nav-tabs mb-0" role="tablist" wire:key="cajas-dia-tabs">
    <li class="nav-item" wire:key="tab-mi-caja">
      <a href="#" wire:click.prevent="cambiarTab('mi-caja')"
        class="nav-link {{ $tab === 'mi-caja' ? 'active' : '' }}">
        <i class="fas fa-user mr-1"></i><span>Mi Caja</span>
      </a>
    </li>
    <li class="nav-item" wire:key="tab-cajas">
      <a href="#" wire:click.prevent="cambiarTab('cajas')"
        class="nav-link {{ $tab === 'cajas' ? 'active' : '' }}">
        <i class="fas fa-tasks mr-1"></i><span>Cajas del Día</span>
        <span class="badge badge-secondary ml-1">{{ $cajasDelDia->count() }}</span>
      </a>
    </li>
  </ul>
  <hr class="mt-0 mb-2 border-secondary">

  @if ($tab === 'mi-caja')
    {{-- ================= MI CAJA ================= --}}
    @if ($cajaTrabajo)
      <div class="row mb-1">
        <div class="col-6 col-lg-3 px-1">
          <div class="card text-white bg-primary shadow-sm h-100">
            <div class="card-body stat-card-body d-flex align-items-center">
              <i class="fas fa-wallet fa-lg mr-2 d-none d-sm-block"></i>
              <div>
                <div class="text-white-50 small" style="font-size:75%">Saldo Inicial</div>
                <div class="font-weight-bold h6 mb-0">@money($cajaTrabajo->saldo_inicial)</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3 px-1">
          <div class="card text-white bg-success shadow-sm h-100">
            <div class="card-body stat-card-body d-flex align-items-center">
              <i class="fas fa-long-arrow-alt-down fa-lg mr-2 d-none d-sm-block"></i>
              <div>
                <div class="text-white-50 small" style="font-size:75%">Entradas (Efectivo)</div>
                <div class="font-weight-bold h6 mb-0">@money($totalIngresos ?? 0)</div>
                <small class="text-white-50" style="font-size:70%">Otras Ent.: @money($totalIngresosOtros ?? 0)</small>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3 px-1">
          <div class="card text-white bg-danger shadow-sm h-100">
            <div class="card-body stat-card-body d-flex align-items-center">
              <i class="fas fa-long-arrow-alt-up fa-lg mr-2 d-none d-sm-block"></i>
              <div>
                <div class="text-white-50 small" style="font-size:75%">Salidas (Efectivo)</div>
                <div class="font-weight-bold h6 mb-0">@money($totalEgresos ?? 0)</div>
                <small class="text-white-50" style="font-size:70%">Otras Sal.: @money($totalEgresosOtros ?? 0)</small>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3 px-1">
          <div class="card text-white bg-dark shadow-sm h-100">
            <div class="card-body stat-card-body d-flex align-items-center">
              <i class="fas fa-coins fa-lg mr-2 d-none d-sm-block"></i>
              <div>
                <div class="text-white-50 small" style="font-size:75%">
                  @if ($cajaTrabajo->estado === 'abierta')
                    Saldo Actual
                  @else
                    Saldo Final
                  @endif
                </div>
                <div class="font-weight-bold h6 mb-0">@money($saldoFinal ?? 0)</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row mb-1">
        <div class="col-md-4 px-1">
          <div class="card border-info shadow-sm h-100">
            <div class="card-body stat-card-body d-flex align-items-center">
              <i class="fas fa-receipt fa-lg text-info mr-2"></i>
              <div>
                <div class="text-muted small" style="font-size:75%">Recaudaciones del Día</div>
                <div class="font-weight-bold h6 mb-0">{{ $recaudacionesDia }}</div>
                <small class="text-muted" style="font-size:70%">por @money($totalRecaudadoDia)</small>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4 px-1">
          <div class="card border-warning shadow-sm h-100">
            <div class="card-body stat-card-body d-flex align-items-center">
              <i class="fas fa-clock fa-lg text-warning mr-2"></i>
              <div>
                <div class="text-muted small" style="font-size:75%">Estado de la Caja</div>
                <div class="font-weight-bold h6 mb-0">
                  @if ($cajaTrabajo->estado === 'abierta')
                    <span class="badge badge-success"><i class="fas fa-door-open mr-1"></i>Abierta</span>
                  @else
                    <span class="badge badge-secondary"><i class="fas fa-lock mr-1"></i>Cerrada</span>
                  @endif
                </div>
                <small class="text-muted" style="font-size:70%">
                  {{ optional($cajaTrabajo->fecha_apertura)->format('d/m/Y') }}
                  {{ $cajaTrabajo->hora_apertura_formateada }}
                  @if ($cajaTrabajo->estado === 'cerrada' && $cajaTrabajo->fecha_cierre)
                    → {{ $cajaTrabajo->fecha_cierre->format('d/m/Y H:i') }}
                  @endif
                </small>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4 px-1">
          <div class="card border-secondary shadow-sm h-100">
            <div class="card-body stat-card-body d-flex align-items-center">
              <i class="fas fa-user fa-lg text-secondary mr-2"></i>
              <div>
                <div class="text-muted small" style="font-size:75%">Cajero</div>
                <div class="font-weight-bold h6 mb-0">{{ $cajaTrabajo->cajero->nombre_completo ?? '—' }}</div>
                <small class="text-muted" style="font-size:70%">{{ $cajaTrabajo->observaciones ?: 'Sin observaciones' }}</small>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Acciones rápidas --}}
      @if ($cajaTrabajo->estado === 'abierta')
        <div class="card shadow-sm mb-2">
          <div class="card-body py-2 px-2">
            <div class="d-flex flex-wrap" style="gap:.4rem;">
              <a href="{{ route('tesoreria.caja-diaria.movimientos') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus-circle mr-1"></i>Registrar movimiento
              </a>
              <a href="{{ route('tesoreria.caja-diaria.cobrar') }}" class="btn btn-success btn-sm">
                <i class="fas fa-hand-holding-usd mr-1"></i>Cobrar
              </a>
              <a href="{{ route('tesoreria.caja-diaria.pagar') }}" class="btn btn-danger btn-sm">
                <i class="fas fa-money-bill-wave mr-1"></i>Pagar
              </a>
              <a href="{{ route('tesoreria.caja-diaria.arqueo') }}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-list-ol mr-1"></i>Realizar arqueo
              </a>
              <a href="{{ route('tesoreria.caja-diaria.apertura-cierre') }}" class="btn btn-outline-danger btn-sm ml-auto">
                <i class="fas fa-lock mr-1"></i>Cerrar caja
              </a>
            </div>
          </div>
        </div>
      @endif

      {{-- Totales del día por medio de pago (desde Libro Diario) --}}
      @if ($totalesDiaPorMedio->isNotEmpty())
        <div class="card shadow-sm mb-2">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 font-weight-bold"><i class="fas fa-chart-bar mr-1"></i>Movimientos del Día por Medio de Pago</h6>
            <small class="text-muted">Sincronizado con el Libro Diario</small>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead class="thead-light">
                  <tr>
                    <th>Medio</th>
                    <th class="text-right text-success">Entradas</th>
                    <th class="text-right text-danger">Salidas</th>
                    <th class="text-right">Neto</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($totalesDiaPorMedio as $fila)
                    @php $neto = $fila->entradas - $fila->salidas; @endphp
                    <tr>
                      <td>
                        @if ($fila->medio_nombre === 'Efectivo')
                          <strong><i class="fas fa-money-bill-wave mr-1 text-success"></i>{{ $fila->medio_nombre }}</strong>
                        @else
                          {{ $fila->medio_nombre }}
                        @endif
                      </td>
                      <td class="text-right text-success">@money($fila->entradas)</td>
                      <td class="text-right text-danger">@money($fila->salidas)</td>
                      <td class="text-right font-weight-bold {{ $neto >= 0 ? 'text-success' : 'text-danger' }}">
                        @money($neto)
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      @endif

      {{-- Referencia Caja Chica (informativo) --}}
      @if ($cajaChicaDia && ($cajaChicaDia->entradas > 0 || $cajaChicaDia->salidas > 0))
        <div class="card border-warning shadow-sm mb-2">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 font-weight-bold"><i class="fas fa-coins mr-1 text-warning"></i>Caja Chica — Referencia del Día</h6>
            <small class="text-muted">No forma parte de la caja diaria</small>
          </div>
          <div class="card-body py-1 px-2">
            <div class="row text-center">
              <div class="col-4 border-right">
                <small class="d-block text-muted">Entradas</small>
                <strong class="text-success small">@money($cajaChicaDia->entradas)</strong>
              </div>
              <div class="col-4 border-right">
                <small class="d-block text-muted">Salidas</small>
                <strong class="text-danger small">@money($cajaChicaDia->salidas)</strong>
              </div>
              <div class="col-4">
                <small class="d-block text-muted">Neto</small>
                @php $netoCch = $cajaChicaDia->entradas - $cajaChicaDia->salidas; @endphp
                <strong class="small {{ $netoCch >= 0 ? 'text-success' : 'text-danger' }}">@money($netoCch)</strong>
              </div>
            </div>
          </div>
        </div>
      @endif

      <div class="card shadow-sm mb-0">
        <div class="card-header card-header-section card-header-gradient py-1 px-2 d-flex justify-content-between align-items-center">
          <h6 class="mb-0 text-premium-header">
            <i class="fas fa-exchange-alt mr-2"></i>Movimientos de la Caja
          </h6>
          <a href="{{ route('tesoreria.caja-diaria.movimientos') }}" class="btn btn-light btn-sm py-0 px-2">
            <i class="fas fa-plus mr-1"></i>Registrar
          </a>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-hover table-striped mb-0">
            <thead class="thead-dark">
              <tr>
                <th>Fecha/Hora</th>
                <th>Tipo</th>
                <th>Concepto</th>
                <th>Identidad</th>
                <th>Medio</th>
                <th class="text-right">Monto</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($movimientos as $movimiento)
                <tr>
                  <td class="text-nowrap">{{ $movimiento->created_at ? $movimiento->created_at->format('d/m/Y H:i') : '—' }}</td>
                  <td>
                    <span class="badge badge-{{ $movimiento->tipo_movimiento === 'INGRESO' ? 'success' : 'danger' }}">
                      {{ $movimiento->tipo_movimiento }}
                    </span>
                  </td>
                  <td>
                    {{ $movimiento->concepto }}
                    @if ($movimiento->descripcion)
                      <small class="d-block text-muted">{{ $movimiento->descripcion }}</small>
                    @endif
                  </td>
                  <td>
                    @if ($movimiento->libroDiario && ($movimiento->libroDiario->denominacion || $movimiento->libroDiario->identidad))
                      @if ($movimiento->libroDiario->denominacion)
                        <span>{{ $movimiento->libroDiario->denominacion }}</span>
                      @endif
                      @if ($movimiento->libroDiario->identidad)
                        <small class="d-block text-muted" style="font-size:75%">{{ $movimiento->libroDiario->identidad }}</small>
                      @endif
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td>
                    @if ($movimiento->medioPago && $movimiento->medioPago->nombre === 'Efectivo')
                      <span class="font-weight-bold">Efectivo</span>
                    @else
                      {{ $movimiento->medioPago->nombre ?? '—' }}
                    @endif
                  </td>
                  <td class="text-right font-weight-bold
                    {{ $movimiento->tipo_movimiento === 'INGRESO' ? 'text-success' : 'text-danger' }}">
                    @money($movimiento->monto)
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-muted py-3">
                    No hay movimientos registrados en esta caja.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    @else
      <div class="alert alert-secondary shadow-sm mb-0 py-2 px-3">
        <i class="fas fa-info-circle mr-2"></i>
        No existe una caja abierta para el usuario actual.
        Las cajas de la fecha se muestran en la pestaña <strong>Cajas del Día</strong>.
      </div>
    @endif

  @elseif ($tab === 'cajas')
    {{-- ================= CAJAS DEL DÍA ================= --}}
    @if ($cajasDelDia->isNotEmpty())
      <div class="row">
        @foreach ($cajasDelDia as $caja)
          @php
            $esPropia = $caja->cajero_id === auth()->id();
            $ing = $caja->totalIngresos();
            $egr = $caja->totalEgresos();
            $ingOtros = $caja->totalIngresosOtros();
            $egrOtros = $caja->totalEgresosOtros();
            $saldo = $caja->estado === 'cerrada' && $caja->saldo_cierre !== null
              ? (float) $caja->saldo_cierre
              : $caja->obtenerSaldoActual();
          @endphp
          <div class="col-12 mb-2">
            <div class="card shadow-sm h-100 {{ $esPropia ? 'border-primary' : '' }}">
              <div class="card-header d-flex justify-content-between align-items-center
                {{ $esPropia ? 'card-header-section card-header-gradient' : '' }}">
                <div class="font-weight-bold">
                  <i class="fas fa-user-circle mr-1"></i>{{ $caja->cajero->nombre_completo ?? '—' }}
                  @if ($esPropia)
                    <span class="badge badge-primary ml-1">Tu caja</span>
                  @endif
                </div>
                @if ($caja->estado === 'abierta')
                  <span class="badge badge-success"><i class="fas fa-door-open mr-1"></i>Abierta</span>
                @else
                  <span class="badge badge-secondary"><i class="fas fa-lock mr-1"></i>Cerrada</span>
                @endif
              </div>
              <div class="card-body py-2 px-2">
                <div class="row text-center">
                  <div class="col-4 border-right">
                    <small class="d-block text-muted">Saldo Inicial</small>
                    <strong class="small">@money($caja->saldo_inicial)</strong>
                  </div>
                  <div class="col-4 border-right">
                    <small class="d-block text-muted">Entradas</small>
                    <strong class="small text-success">@money($ing)</strong>
                  </div>
                  <div class="col-4">
                    <small class="d-block text-muted">Salidas</small>
                    <strong class="small text-danger">@money($egr)</strong>
                  </div>
                </div>
                <hr class="my-1">
                <div class="row text-center">
                  <div class="col-4 border-right">
                    <small class="d-block text-muted">Otras Ent.</small>
                    <strong class="small">@money($ingOtros)</strong>
                  </div>
                  <div class="col-4 border-right">
                    <small class="d-block text-muted">Otras Sal.</small>
                    <strong class="small">@money($egrOtros)</strong>
                  </div>
                  <div class="col-4">
                    <small class="d-block text-muted">
                      {{ $caja->estado === 'cerrada' ? 'Saldo Final' : 'Saldo Actual' }}
                    </small>
                    <strong class="small">@money($saldo)</strong>
                  </div>
                </div>
              </div>
              <div class="card-footer py-1 px-2 d-flex justify-content-between align-items-center">
                <small class="text-muted">
                  <i class="far fa-clock mr-1"></i>{{ optional($caja->fecha_apertura)->format('d/m/Y') }} {{ $caja->hora_apertura_formateada }}
                </small>
                <div>
                  <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2"
                    wire:click="verMovimientos({{ $caja->id }})" title="Ver movimientos">
                    <i class="fas fa-exchange-alt"></i> Ver movimientos
                  </button>
                  @if ($esPropia && $caja->estado === 'abierta')
                    <a href="{{ route('tesoreria.caja-diaria.apertura-cierre') }}"
                      class="btn btn-outline-secondary btn-sm py-0 px-2" title="Apertura / Cierre">
                      <i class="fas fa-arrows-alt-h"></i>
                    </a>
                    <a href="{{ route('tesoreria.caja-diaria.arqueo') }}"
                      class="btn btn-outline-secondary btn-sm py-0 px-2" title="Arqueo">
                      <i class="fas fa-list-ol"></i>
                    </a>
                  @endif
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @else
      <div class="alert alert-secondary shadow-sm mb-0 py-2 px-3">
        <i class="fas fa-info-circle mr-2"></i>
        No hay cajas registradas para la fecha <strong>@urudate($fechaSeleccionada)</strong>.
      </div>
    @endif

  @endif

  {{-- Modal: Movimientos de una caja --}}
  <div class="modal fade" id="modalMovimientosCaja" tabindex="-1" role="dialog"
    aria-labelledby="modalMovimientosCajaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 95vw;" role="document">
      <div class="modal-content">
        <div class="modal-header py-2 px-3">
          <h5 class="modal-title" id="modalMovimientosCajaLabel">
            <i class="fas fa-exchange-alt mr-1"></i>Movimientos de la Caja
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body py-2 px-3">
          @if ($cajaSeleccionada)
            <div class="mb-2">
              <span class="font-weight-bold">{{ $cajaSeleccionada->cajero->nombre_completo ?? '—' }}</span>
              <small class="text-muted ml-2">
                <i class="far fa-clock mr-1"></i>{{ optional($cajaSeleccionada->fecha_apertura)->format('d/m/Y') }} {{ $cajaSeleccionada->hora_apertura_formateada }}
              </small>
              @if ($cajaSeleccionada->estado === 'abierta')
                <span class="badge badge-success ml-1"><i class="fas fa-door-open mr-1"></i>Abierta</span>
              @else
                <span class="badge badge-secondary ml-1"><i class="fas fa-lock mr-1"></i>Cerrada</span>
              @endif
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-hover table-striped mb-0">
                <thead class="thead-dark">
                  <tr>
                    <th>Fecha/Hora</th>
                    <th>Tipo</th>
                    <th>Concepto</th>
                    <th>Identidad</th>
                    <th>Medio</th>
                    <th class="text-right">Monto</th>
                    <th class="text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($movimientosCaja as $movimiento)
                    <tr>
                      <td class="text-nowrap">{{ $movimiento->created_at ? $movimiento->created_at->format('d/m/Y H:i') : '—' }}</td>
                      <td>
                        <span class="badge badge-{{ $movimiento->tipo_movimiento === 'INGRESO' ? 'success' : 'danger' }}">
                          {{ $movimiento->tipo_movimiento }}
                        </span>
                      </td>
                      <td>
                        {{ $movimiento->concepto }}
                        @if ($movimiento->descripcion)
                          <small class="d-block text-muted">{{ $movimiento->descripcion }}</small>
                        @endif
                      </td>
                      <td>
                        @if ($movimiento->libroDiario && ($movimiento->libroDiario->denominacion || $movimiento->libroDiario->identidad))
                          @if ($movimiento->libroDiario->denominacion)
                            <span>{{ $movimiento->libroDiario->denominacion }}</span>
                          @endif
                          @if ($movimiento->libroDiario->identidad)
                            <small class="d-block text-muted" style="font-size:75%">{{ $movimiento->libroDiario->identidad }}</small>
                          @endif
                        @else
                          <span class="text-muted">—</span>
                        @endif
                      </td>
                      <td>
                        @if ($movimiento->medioPago && $movimiento->medioPago->nombre === 'Efectivo')
                          <span class="font-weight-bold">Efectivo</span>
                        @else
                          {{ $movimiento->medioPago->nombre ?? '—' }}
                        @endif
                      </td>
                      <td class="text-right font-weight-bold
                        {{ $movimiento->tipo_movimiento === 'INGRESO' ? 'text-success' : 'text-danger' }}">
                        @money($movimiento->monto)
                      </td>
                      <td class="text-center py-1">
                        @if ($cajaSeleccionada && $cajaSeleccionada->estado === 'abierta')
                          <button type="button"
                                  class="btn btn-sm btn-outline-danger py-0 px-2"
                                  title="Eliminar movimiento y su asiento del Libro Diario"
                                  wire:click="confirmarEliminarMovimiento({{ $movimiento->id }})">
                            <i class="fas fa-trash-alt"></i>
                          </button>
                        @else
                          <span class="text-muted" title="Caja cerrada"><i class="fas fa-lock"></i></span>
                        @endif
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="7" class="text-center text-muted py-3">
                        No hay movimientos registrados en esta caja.
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          @else
            <div class="text-center text-muted py-3">
              Seleccioná una caja para ver sus movimientos.
            </div>
          @endif
        </div>
        <div class="modal-footer py-1 px-3">
          <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  function unwrapDetail(e) {
    const d = e.detail;
    return (Array.isArray(d) && d.length > 0) ? d[0] : (d || {});
  }

  window.addEventListener('alert', event => {
    const data = unwrapDetail(event);
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

  document.addEventListener('DOMContentLoaded', function () {
    window.addEventListener('confirm-eliminar-movimiento', event => {
      const data = unwrapDetail(event);
      Swal.fire({
        title: '¿Está seguro?',
        html: 'Se eliminará este movimiento y su asiento asociado en el Libro Diario. Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          @this.call('eliminarMovimiento', data.id);
        }
      });
    });

    window.addEventListener('swal:confirmar-eliminar-recaudacion', event => {
      const data = unwrapDetail(event);
      const texto = data.cantidad === 1
        ? 'Este CFE de recaudación tiene 1 asiento asociado en el Libro Diario que también será eliminado y los saldos serán recalculados. ¿Desea continuar?'
        : `Este CFE de recaudación tiene ${data.cantidad} asientos asociados en el Libro Diario que también serán eliminados y los saldos serán recalculados. ¿Desea continuar?`;
      Swal.fire({
        title: '¿Eliminar la recaudación?',
        html: `Se eliminará la recaudación <strong>${data.cfeTipo || ''} ${data.cfeSerie || ''}${data.cfeNumero}</strong>, sus asientos en el Libro Diario y este movimiento de caja.<br><br>${texto}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar todo',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          @this.call('eliminarMovimiento', data.movimientoId);
        }
      });
    });
  });
</script>
@endpush
