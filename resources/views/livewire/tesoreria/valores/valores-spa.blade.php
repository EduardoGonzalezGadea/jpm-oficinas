<div>
    <nav class="nav navbar-expand-md nav-pills nav-justified">
        <li class="nav-item">
            <a class="nav-link {{ $componenteActivo === 'stock' ? 'active' : '' }}" href="#" wire:click.prevent="cambiarComponente('stock')">STOCK</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $componenteActivo === 'libretas' ? 'active' : '' }}" href="#" wire:click.prevent="cambiarComponente('libretas')">LIBRETAS</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $componenteActivo === 'recibos' ? 'active' : '' }}" href="#" wire:click.prevent="cambiarComponente('recibos')">RECIBOS</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $componenteActivo === 'entradas' ? 'active' : '' }}" href="#" wire:click.prevent="cambiarComponente('entradas')">ENTRADAS</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $componenteActivo === 'salidas' ? 'active' : '' }}" href="#" wire:click.prevent="cambiarComponente('salidas')">SALIDAS</a>
        </li>
    </nav>
    <hr class="mt-0 mb-1">
    <div class="spa-content">
        @if ($componenteActivo === 'stock')
            @livewire('tesoreria.valores.stock')
        @elseif ($componenteActivo === 'libretas')
            @livewire('tesoreria.valores.index')
        @elseif ($componenteActivo === 'recibos')
            @livewire('tesoreria.valores.conceptos')
        @elseif ($componenteActivo === 'entradas')
            @livewire('tesoreria.valores.entradas')
        @elseif ($componenteActivo === 'salidas')
            @livewire('tesoreria.valores.salidas')
        @endif
    </div>
</div>
