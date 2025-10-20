<div class="container-fluid">
    @if (session()->has('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="row align-items-center mb-3">
        <div class="col-md-6">
            <h3 class="mb-0">Caja Diaria</h3>
        </div>
        <div class="col-md-6 text-right">
            <input type="date" class="form-control d-inline-block w-auto" wire:model.lazy="fecha">
        </div>
    </div>
    <ul class="nav nav-pills mb-0" role="tablist">
        <li class="nav-item">
            <a class="nav-link {{ $activeTab == 'resumen' ? 'active' : '' }}" href="{{ route('tesoreria.caja_diaria', ['tab' => 'resumen']) }}" wire:navigate>Resumen</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab == 'cobros' ? 'active' : '' }}" href="{{ route('tesoreria.caja_diaria', ['tab' => 'cobros']) }}" wire:navigate>Cobros</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab == 'pagos' ? 'active' : '' }}" href="{{ route('tesoreria.caja_diaria', ['tab' => 'pagos']) }}" wire:navigate>Pagos</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab == 'art222' ? 'active' : '' }}" href="{{ route('tesoreria.caja_diaria', ['tab' => 'art222']) }}" wire:navigate>Art. 222</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab == 'opciones' ? 'active' : '' }}" href="{{ route('tesoreria.caja_diaria', ['tab' => 'opciones']) }}" wire:navigate>Opciones</a>
        </li>
    </ul>
    <hr class="mt-0 mb-1">
    <div class="tab-content" id="pills-tabContent">
        @if ($activeTab == 'resumen')
            <div class="mt-3">
                @livewire('tesoreria.caja-diaria.resumen', ['fecha' => $fecha, 'cajaDiariaExists' => $cajaDiariaExists, 'saldoInicial' => $saldoInicial, 'totalCobros' => $totalCobros, 'totalPagos' => $totalPagos, 'saldoActual' => $saldoActual], key('resumen-' . $fecha))
            </div>
        @elseif ($activeTab == 'cobros')
            <div class="mt-3">
                @livewire('tesoreria.caja-diaria.cobros', ['fecha' => $fecha], key('cobros-' . $fecha))
            </div>
        @elseif ($activeTab == 'pagos')
            <div class="mt-3">
                @livewire('tesoreria.caja-diaria.pagos', ['fecha' => $fecha], key('pagos-' . $fechaKey))
            </div>
        @elseif ($activeTab == 'art222')
            <div class="mt-3">
                @livewire('tesoreria.caja-diaria.art222', ['fecha' => $fecha], key('art222-' . $fecha))
            </div>
        @elseif ($activeTab == 'opciones')
            <div class="mt-3">
                @livewire('tesoreria.caja-diaria.opciones', ['fecha' => $fecha], key('opciones-' . $fechaKey))
            </div>
        @endif
    </div>
</div>
