<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'JPM Oficinas')</title>

    <!-- Bootstrap 4 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootswatch@4.6.2/dist/cosmo/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    @livewireStyles
    @yield('estilos')
</head>

<body>
    @auth
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <a class="navbar-brand" href="{{ route('panel') }}">
                <i class="fas fa-building"></i> JPM Oficinas
            </a>

            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('panel') }}">
                            <i class="fas fa-home"></i> Inicio
                        </a>
                    </li>

                    @can('gestionar_usuarios')
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('usuarios.index') }}">
                                <i class="fas fa-users"></i> Usuarios
                            </a>
                        </li>
                    @endcan

                    @can('gestionar_tesoreria')
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('tesoreria.index') }}">
                                <i class="fas fa-coins"></i> Tesorería
                            </a>
                        </li>
                    @endcan

                    @can('gestionar_contabilidad')
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
                            <a href="{{ route('usuarios.miPerfil') }}" class="btn btn-outline-secondary btn-block btn-lg">
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
    @endauth

    <main class="@auth container-fluid mt-4 @else container-fluid @endauth">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        @endif

        @yield('contenido')
    </main>

    <!-- Bootstrap 4 JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    @livewireScripts
    @yield('scripts')
</body>

</html>
