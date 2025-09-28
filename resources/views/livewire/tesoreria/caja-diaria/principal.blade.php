<div class="container-fluid">
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
            <a class="nav-link {{ $activeTab == 'resumen' ? 'active' : '' }}" href="#" wire:click.prevent.stop="cambiarPestana('resumen')">Resumen</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab == 'cobros' ? 'active' : '' }}" href="#" wire:click.prevent.stop="cambiarPestana('cobros')">Cobros</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab == 'pagos' ? 'active' : '' }}" href="#" wire:click.prevent.stop="cambiarPestana('pagos')">Pagos</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab == 'art222' ? 'active' : '' }}" href="#" wire:click.prevent.stop="cambiarPestana('art222')">Art. 222</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab == 'opciones' ? 'active' : '' }}" href="#" wire:click.prevent.stop="cambiarPestana('opciones')">Opciones</a>
        </li>
    </ul>
    <hr class="mt-0 mb-1">
    <div class="tab-content" id="pills-tabContent">
        @if ($activeTab == 'resumen')
            @livewire('tesoreria.caja-diaria.resumen', ['fecha' => $fecha], key('resumen-' . $fecha))
        @elseif ($activeTab == 'cobros')
            @livewire('tesoreria.caja-diaria.cobros', ['fecha' => $fecha], key('cobros-' . $fecha))
        @elseif ($activeTab == 'pagos')
            @livewire('tesoreria.caja-diaria.pagos', ['fecha' => $fecha], key('pagos-' . $fecha))
        @elseif ($activeTab == 'art222')
            @livewire('tesoreria.caja-diaria.art222', ['fecha' => $fecha], key('art222-' . $fecha))
        @elseif ($activeTab == 'opciones')
            @livewire('tesoreria.caja-diaria.opciones', ['fecha' => $fecha], key('opciones-' . $fecha))
        @endif
    </div>
</div>
