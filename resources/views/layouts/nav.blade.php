<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="{{ route('panel') }}">
        <i class="fas fa-building"></i> JPM Oficinas
    </a>

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav mr-auto">

            @can('ver_usuarios')
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('usuarios.index') }}">
                        <i class="fas fa-users"></i> Usuarios
                    </a>
                </li>
            @endcan

            @can('operador_tesoreria')
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('tesoreria.index') }}">
                        <i class="fas fa-coins"></i> Tesorería
                    </a>
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
