<nav class="navbar navbar-expand-md navbar-dark bg-dark barra-oscura" style="margin-bottom: 0px;">
    <a class="navbar-brand" href="{{ route('panel') }}">
        <i class="fas fa-building mr-2"></i> Tesorería | Oficinas
    </a>

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav mr-auto">
            {{-- TESORERÍA --}}
            @can('tesoreria.acceso')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownTesoreria" role="button"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-dollar-sign mr-2"></i>Tesorería
                </a>
                <div class="dropdown-menu dropdown-menu-columns" aria-labelledby="navbarDropdownTesoreria">

                    {{-- Artículos de Multas de Tránsito (siempre al inicio) --}}
                    <a class="dropdown-item" href="{{ route('tesoreria.multas-transito') }}">
                        <i class="fas fa-list"></i> Artículos de Multas de Tránsito
                    </a>
                    <a class="dropdown-item" href="{{ route('tesoreria.multas-303-2023') }}">
                        <i class="fas fa-list-alt mr-2"></i> Cod. Multas Dec.303/2023
                    </a>
                    {{-- Separador --}}
                    <div class="dropdown-divider"></div>

                    {{-- Resto de opciones en orden alfabético --}}
                    {{-- Link Armas --}}
                    {{-- COMENTADO TEMPORALMENTE
                    <div class="dropdown-submenu submenu-right">
                        <a class="dropdown-item dropdown-toggle" href="#" role="button">
                            <i class="fas fa-shield-alt mr-2"></i>Armas
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="{{ route('tesoreria.armas.porte') }}" wire:navigate>
                                <i class="fas fa-id-badge mr-2"></i>Porte de Armas
                            </a>
                            <a class="dropdown-item" href="{{ route('tesoreria.armas.tenencia') }}" wire:navigate>
                                <i class="fas fa-address-card mr-2"></i>Tenencia de Armas
                            </a>
                        </div>
                    </div>
                    --}}
                    {{-- Link Arrendamientos --}}
                    <a class="dropdown-item" href="{{ route('tesoreria.arrendamientos.index') }}">
                        <i class="fas fa-file-signature mr-2"></i> Arrendamientos
                    </a>

                    {{-- Link Caja Chica --}}
                    <a class="dropdown-item" href="{{ route('tesoreria.caja-chica.index') }}" wire:navigate>
                        <i class="fas fa-coins mr-2"></i>Caja Chica
                    </a>
                    {{-- Link Certificados de Residencia --}}
                    <a class="dropdown-item" href="{{ route('tesoreria.certificados-residencia.index') }}" wire:navigate>
                        <i class="fas fa-file-alt mr-2"></i> Certificados de Residencia
                    </a>
                    {{-- Link Tarjetas de Cobro BROU --}}
                    <a class="dropdown-item" href="{{ route('tesoreria.tarjetas-cobro-brou.index') }}" wire:navigate>
                        <i class="fas fa-credit-card mr-2"></i> Tarjetas de Cobro BROU
                    </a>
                    {{-- Link Cheques --}}
                    <a class="dropdown-item" href="{{ route('tesoreria.cheques.index') }}" wire:navigate>
                        <i class="fas fa-money-check mr-2"></i>Cheques
                    </a>
                    {{-- Link Depósito de Vehículos --}}
                    <a class="dropdown-item" href="{{ route('tesoreria.deposito-vehiculos.index') }}" wire:navigate>
                        <i class="fas fa-car mr-2"></i> Depósito de Vehículos
                    </a>
                    {{-- Link Eventuales --}}
                    <a class="dropdown-item" href="{{ route('tesoreria.eventuales.index') }}">
                        <i class="fas fa-hand-holding-usd mr-2"></i> Eventuales
                    </a>
                    {{-- Link Gestión de CFEs --}}
                    <a class="dropdown-item" href="{{ route('tesoreria.gestion-cfe.index') }}" wire:navigate>
                        <i class="fas fa-file-invoice mr-2"></i> Gestión de CFEs
                    </a>
                    {{-- Link Estados de Recaudación --}}
                    <a class="dropdown-item" href="{{ route('tesoreria.gestion-cfe.estados-recaudacion') }}" wire:navigate>
                        <i class="fas fa-chart-line mr-2"></i> Estados de Recaudación
                    </a>
                    {{-- Link Multas Cobradas --}}
                    <a class="dropdown-item" href="{{ route('tesoreria.multas-cobradas.index') }}">
                        <i class="fas fa-receipt mr-2"></i> Multas Cobradas
                    </a>
                    {{-- Link Prendas --}}
                    <a class="dropdown-item" href="{{ route('tesoreria.prendas.index') }}" wire:navigate>
                        <i class="fas fa-file-invoice-dollar mr-2"></i> Prendas
                    </a>
                    {{-- Link Valores --}}
                    <a class="dropdown-item" href="{{ route('tesoreria.valores.index') }}">
                        <i class="fas fa-barcode mr-2"></i> Valores
                    </a>
                    {{-- Reporte de Recibos (solo roles superiores) --}}
                    @if(auth()->user()->esAdministrador() || auth()->user()->esGerente() || auth()->user()->esSupervisor())
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('tesoreria.reporte-recibos.index') }}">
                        <i class="fas fa-clipboard-list mr-2 text-danger"></i> <strong>Reporte de Recibos</strong>
                    </a>
                    @endif
                </div>
            </li>
            @endcan

            {{-- ASESORÍA CONTABLE --}}
            @can('asesoria_contable.acceso')
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAsesoriaContable" role="button"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-calculator mr-2"></i>Asesoría Contable
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdownAsesoriaContable">
                    <a class="dropdown-item" href="{{ route('asesoria-contable.estados-recaudacion') }}">
                        <i class="fas fa-chart-bar mr-2"></i> Estados de Recaudación
                    </a>
                    <a class="dropdown-item" href="{{ route('asesoria-contable.resumen-recaudaciones') }}">
                        <i class="fas fa-chart-pie mr-2"></i> Resumen de Recaudaciones
                    </a>
                </div>
            </li>
            @endcan
        </ul>

        @php
            $sistemaItems = 0;
            if (auth()->user()->esAdministrador() || auth()->user()->moduloClave() === 'tesoreria') $sistemaItems++; // Respaldos
            if (auth()->user()->esAdministrador() || (auth()->user()->moduloClave() === 'tesoreria' && in_array(auth()->user()->nivelActual(), ['supervisor', 'gerente']))) $sistemaItems++; // Auditoría
            if (auth()->user()->moduloClave() === 'tesoreria' || auth()->user()->esAdministrador()) $sistemaItems += 2; // Pendrive + Opciones
            $sistemaItems++; // Estilos (siempre visible)
        @endphp
        <ul class="navbar-nav">
            @if($sistemaItems > 0)
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownSistema" role="button"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-cogs mr-2"></i>Sistema
                </a>
                <div class="dropdown-menu {{ $sistemaItems > 1 ? 'sistema-menu-columns' : '' }} dropdown-menu-right" aria-labelledby="navbarDropdownSistema" id="sistema-menu">
                    @if(auth()->user()->moduloClave() === 'tesoreria' || auth()->user()->esAdministrador())
                    @if(auth()->user()->esAdministrador() || auth()->user()->moduloClave() === 'tesoreria')
                    <div class="dropdown-submenu submenu-left">
                        <a class="dropdown-item dropdown-toggle" href="#">
                            <i class="fas fa-database mr-2"></i>Respaldos
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#" id="btn-crear-respaldo-menu">
                                <i class="fas fa-plus mr-2"></i>Realizar Respaldo
                            </a>
                            @if(auth()->user()->esAdministrador() || in_array(auth()->user()->nivelActual(), ['supervisor', 'gerente']))
                            <a class="dropdown-item" href="{{ route('system.backups.index') }}">
                                <i class="fas fa-undo mr-2"></i>Gestionar Respaldos
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif
                    @if(auth()->user()->esAdministrador() || (auth()->user()->moduloClave() === 'tesoreria' && in_array(auth()->user()->nivelActual(), ['supervisor', 'gerente'])))
                    <a class="dropdown-item" href="{{ route('sistema.auditoria.index') }}">
                        <i class="fas fa-history mr-2"></i>Historial de Auditoría
                    </a>
                    @endif
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('pendrive.index') }}">
                        <i class="fas fa-hdd mr-2"></i>Pendrive Virtual
                    </a>
                    <div class="dropdown-submenu submenu-left">
                        <a class="dropdown-item dropdown-toggle" href="#">
                            <i class="fas fa-cog mr-2"></i>Opciones
                        </a>
                        <div class="dropdown-menu dropdown-menu-grid-2">
                            <div class="dropdown-menu-col">
                                <a class="dropdown-item" href="{{ route('tesoreria.configuracion.medios-de-pago.index') }}">
                                    <i class="fas fa-credit-card mr-2"></i>Medios de Pago
                                </a>
                                <a class="dropdown-item" href="{{ route('tesoreria.configuracion.tes-tipos-monedas.index') }}">
                                    <i class="fas fa-money-bill-wave mr-2"></i>Tipos de Monedas
                                </a>
                                <a class="dropdown-item" href="{{ route('tesoreria.configuracion.tes-denominaciones-monedas.index') }}">
                                    <i class="fas fa-coins mr-2"></i>Denominaciones
                                </a>
                                <a class="dropdown-item" href="{{ route('tesoreria.bancos.index') }}">
                                    <i class="fas fa-university mr-2"></i>Bancos
                                </a>
                                <a class="dropdown-item" href="{{ route('tesoreria.cuentas-bancarias.index') }}">
                                    <i class="fas fa-money-check mr-2"></i>Cuentas Bancarias
                                </a>
                            </div>
                            <div class="dropdown-menu-col">
                                <a class="dropdown-item" href="{{ route('tesoreria.configuracion.caja-conceptos.index') }}">
                                    <i class="fas fa-tags mr-2"></i>Conceptos de Caja
                                </a>
                                <a class="dropdown-item" href="{{ route('tesoreria.configuracion.siif-distribucion-dependencias.index') }}">
                                    <i class="fas fa-sitemap mr-2"></i>Dist. SIIF Deps.
                                </a>
                                <a class="dropdown-item" href="{{ route('tesoreria.configuracion.siif-distribucion-tipos.index') }}">
                                    <i class="fas fa-list-ol mr-2"></i>Dist. SIIF Tipos
                                </a>
                                <a class="dropdown-item" href="{{ route('tesoreria.configuracion.siif-distribuciones.index') }}">
                                    <i class="fas fa-project-diagram mr-2"></i>Distribuciones SIIF
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    @endif
                    <div class="dropdown-submenu submenu-left">
                        <a class="dropdown-item dropdown-toggle" href="#">
                            <i class="fas fa-palette mr-2"></i>Estilos
                        </a>
                        <div class="dropdown-menu">
                            {{-- Tema por Defecto (Bootstrap Original) --}}
                            <button type="button" class="dropdown-item theme-select-button"
                                data-theme-name="default"
                                data-theme-path="{{ asset('libs/bootstrap-4.6.2-dist/css/bootstrap.min.css') }}">
                                Por defecto
                                <span class="text-success theme-active-indicator" style="display: none;">✔</span>
                            </button>

                            {{-- Tema Cosmo --}}
                            <button type="button" class="dropdown-item theme-select-button" data-theme-name="cosmo"
                                data-theme-path="{{ asset('libs/bootswatch@4.6.2/dist/cosmo/bootstrap.min.css') }}">
                                Cosmo
                                <span class="text-success theme-active-indicator" style="display: none;">✔</span>
                            </button>

                            {{-- Tema Cerulean --}}
                            <button type="button" class="dropdown-item theme-select-button"
                                data-theme-name="cerulean"
                                data-theme-path="{{ asset('libs/bootswatch@4.6.2/dist/cerulean/bootstrap.min.css') }}">
                                Cerulean
                                <span class="text-success theme-active-indicator" style="display: none;">✔</span>
                            </button>

                            {{-- Tema Litera --}}
                            <button type="button" class="dropdown-item theme-select-button" data-theme-name="litera"
                                data-theme-path="{{ asset('libs/bootswatch@4.6.2/dist/litera/bootstrap.min.css') }}">
                                Litera
                                <span class="text-success theme-active-indicator" style="display: none;">✔</span>
                            </button>

                            {{-- Tema Materia --}}
                            <button type="button" class="dropdown-item theme-select-button"
                                data-theme-name="material"
                                data-theme-path="{{ asset('libs/bootswatch@4.6.2/dist/materia/bootstrap.min.css') }}">
                                Materia
                                <span class="text-success theme-active-indicator" style="display: none;">✔</span>
                            </button>

                            {{-- Tema Cyborg --}}
                            <button type="button" class="dropdown-item theme-select-button" data-theme-name="cyborg"
                                data-theme-path="{{ asset('libs/bootswatch@4.6.2/dist/cyborg/bootstrap.min.css') }}">
                                Cyborg
                                <span class="text-success theme-active-indicator" style="display: none;">✔</span>
                            </button>

                            {{-- Tema Darkly --}}
                            <button type="button" class="dropdown-item theme-select-button" data-theme-name="darkly"
                                data-theme-path="{{ asset('libs/bootswatch@4.6.2/dist/darkly/bootstrap.min.css') }}">
                                Darkly
                                <span class="text-success theme-active-indicator" style="display: none;">✔</span>
                            </button>
                        </div>
                    </div>
                </div>
            </li>
            @endif
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                    <i class="fas fa-user mr-2"></i>{{ auth()->user()->nombre }} {{ auth()->user()->apellido }}
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="{{ route('usuarios.miPerfil') }}" class="dropdown-item">
                        <i class="fas fa-user-edit mr-2"></i>Mi Perfil
                    </a>
                    <a href="{{ route('two-factor.index') }}" class="dropdown-item">
                        <i class="fas fa-lock mr-2"></i>Seguridad (2FA)
                    </a>
                    @can('usuarios.ver')
                    <a class="dropdown-item" href="{{ route('usuarios.index') }}">
                        <i class="fas fa-users mr-2"></i>Gestión de usuarios
                    </a>
                    @endcan
                    <div class="dropdown-divider"></div>
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="fas fa-sign-out-alt mr-2"></i>Cerrar Sesión
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </div>
</nav>




@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        balanceSistemaMenu();
    });

    function balanceSistemaMenu() {
        const menu = document.getElementById('sistema-menu');
        if (!menu || !menu.classList.contains('sistema-menu-columns')) return;

        const allChildren = Array.from(menu.children);
        const dividers = allChildren.filter(function(el) {
            return el.classList.contains('dropdown-divider');
        });
        const items = allChildren.filter(function(el) {
            return !el.classList.contains('dropdown-divider');
        });

        if (items.length <= 2) return;

        const half = Math.ceil(items.length / 2);
        var col1Items = items.slice(0, half);
        var col2Items = items.slice(half);

        menu.innerHTML = '';

        var col1 = document.createElement('div');
        col1.className = 'dropdown-menu-col';
        col1Items.forEach(function(el) { col1.appendChild(el); });

        var col2 = document.createElement('div');
        col2.className = 'dropdown-menu-col';
        col2Items.forEach(function(el) { col2.appendChild(el); });

        menu.appendChild(col1);
        menu.appendChild(col2);

        dividers.forEach(function(d) { menu.appendChild(d); });
    }
</script>
@endpush