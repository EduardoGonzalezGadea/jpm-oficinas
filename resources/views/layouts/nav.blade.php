<nav class="navbar navbar-expand-md navbar-dark bg-dark">
    <a class="navbar-brand" href="{{ route('panel') }}">
        <i class="fas fa-building"></i> JPM Oficinas
    </a>

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownThemes" role="button"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Estilo
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdownThemes">
                    {{-- Tema por Defecto --}}
                    <form action="{{ route('theme.switch') }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="theme" value="default">
                        <button type="submit" class="dropdown-item">
                            Por defecto
                            {{-- Leemos la cookie. El valor por defecto es 'cerulean' para que algo esté marcado la primera vez --}}
                            @if (request()->cookie('theme_name', 'cerulean') == 'default')
                                <span class="text-success">✔</span>
                            @endif
                        </button>
                    </form>

                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header">Temas Claros</h6>

                    {{-- Tema Cosmo --}}
                    <form action="{{ route('theme.switch') }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="theme" value="cosmo">
                        <button type="submit" class="dropdown-item">
                            Cosmo
                            @if (request()->cookie('theme_name', 'cosmo') == 'cosmo')
                                <span class="text-success">✔</span>
                            @endif
                        </button>
                    </form>

                    {{-- Tema Cerulean --}}
                    <form action="{{ route('theme.switch') }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="theme" value="cerulean">
                        <button type="submit" class="dropdown-item">
                            Cerulean
                            @if (request()->cookie('theme_name', 'cerulean') == 'cerulean')
                                <span class="text-success">✔</span>
                            @endif
                        </button>
                    </form>

                    {{-- Tema Litera --}}
                    <form action="{{ route('theme.switch') }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="theme" value="litera">
                        <button type="submit" class="dropdown-item">
                            Litera
                            @if (request()->cookie('theme_name') == 'litera')
                                <span class="text-success">✔</span>
                            @endif
                        </button>
                    </form>

                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header">Temas Oscuros</h6>

                    {{-- Tema Cyborg --}}
                    <form action="{{ route('theme.switch') }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="theme" value="cyborg">
                        <button type="submit" class="dropdown-item">
                            Cyborg
                            @if (request()->cookie('theme_name') == 'cyborg')
                                <span class="text-success">✔</span>
                            @endif
                        </button>
                    </form>

                    {{-- Tema Darkly --}}
                    <form action="{{ route('theme.switch') }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="theme" value="darkly">
                        <button type="submit" class="dropdown-item">
                            Darkly
                            @if (request()->cookie('theme_name') == 'darkly')
                                <span class="text-success">✔</span>
                            @endif
                        </button>
                    </form>
                </div>
            </li>

            @can('ver_usuarios')
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('usuarios.index') }}">
                        <i class="fas fa-users"></i> Usuarios
                    </a>
                </li>
            @endcan

            @can('operador_tesoreria')
                {{-- <li class="nav-item">
                    <a class="nav-link" href="{{ route('tesoreria.index') }}">
                        <i class="fas fa-coins"></i> Tesorería
                    </a>
                </li> --}}
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownThemes" role="button"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Tesorería
                    </a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdownThemes">
                        {{-- Link Caja Chica --}}
                        <a class="dropdown-item" href="{{ route('tesoreria.caja-chica.index') }}">
                            <i class="fas fa-coins"></i> Caja Chica
                        </a>
                    </div>
                </li>
            @endcan

            @can('operador_contabilidad')
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('contabilidad.index') }}">
                        <i class="fas fa-calculator"></i> Contabilidad
                    </a>
                </li>
            @endcan
        </ul>

        <ul class="navbar-nav">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                    <i class="fas fa-user"></i> {{ auth()->user()->nombre }} {{ auth()->user()->apellido }}
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="{{ route('usuarios.miPerfil') }}" class="dropdown-item">
                        <i class="fas fa-user-edit"></i> Mi Perfil
                    </a>
                    <div class="dropdown-divider"></div>
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </div>
</nav>
