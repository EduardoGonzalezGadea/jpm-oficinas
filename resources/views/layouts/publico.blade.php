<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-authenticated" content="{{ auth()->check() ? 'true' : 'false' }}">

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <title>@yield('title', 'Tesorería | Oficinas')</title>

    <!-- Pre-cargar color de fondo para evitar FOUC (flash blanco) -->
    @auth
    @php
        $darkThemes = ['darkly', 'slate', 'cyborg', 'superhero', 'vapor', 'material'];
        $bgColor = in_array(auth()->user()->theme, $darkThemes) ? '#222222' : '#ffffff';
    @endphp
    <style>html { background-color: {{ $bgColor }}; }</style>
    @else
    <script>
        (function() {
            try {
                var t = localStorage.getItem("bootswatch-theme-name") || "cosmo";
                var d = ["darkly","slate","cyborg","superhero","vapor","material"];
                document.documentElement.style.backgroundColor = d.indexOf(t) >= 0 ? "#222222" : "#ffffff";
            } catch(e) {}
        })();
    </script>
    @endauth

    <!-- Bootstrap 4 CSS: carga directa del tema del usuario (sin flash) -->
    @auth
    <link id="bootswatch-theme" href="{{ auth()->user()->theme_path }}" data-theme-name="{{ auth()->user()->theme ?? 'cosmo' }}" rel="stylesheet">
    <!-- Sincronizar localStorage con el tema del servidor (evita rasgos del usuario anterior) -->
    <script>
        (function() {
            try {
                localStorage.setItem("bootswatch-theme", "{{ auth()->user()->theme_path }}");
                localStorage.setItem("bootswatch-theme-name", "{{ auth()->user()->theme ?? 'cosmo' }}");
            } catch(e) {}
        })();
    </script>
    @else
    <link id="bootswatch-theme" href="{{ asset('libs/bootstrap-4.6.2-dist/css/bootstrap.min.css') }}" data-theme-name="default" rel="stylesheet">
    @endauth

    <!-- Estilos personalizados -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="{{ asset('libs/fontawesome-free-5.15.4-web/css/all.min.css') }}" rel="stylesheet">




    @yield('styles')

    @routes

    <style>
        body {
            background: linear-gradient(180deg, #001a4d 0%, #0d47a1 30%, #42a5f5 65%, #90caf9 100%);
            min-height: 100vh;
        }
    </style>
</head>

<body>
    <!-- Header simple para vista pública -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">
                <i class="fas fa-building mr-2"></i>
                Tesorería | Oficinas
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarPublico" aria-controls="navbarPublico" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarPublico">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('acceso-publico') }}">
                            <i class="fas fa-globe mr-1"></i>Acceso Público
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownConsultas" role="button"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-search mr-1"></i>Consultas
                        </a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdownConsultas">
                            <a class="dropdown-item" href="{{ route('multas-transito-publico') }}">
                                <i class="fas fa-list mr-2"></i>Artículos de Multas CPT
                            </a>
                            <a class="dropdown-item" href="{{ route('multas-303-publico') }}">
                                <i class="fas fa-list-alt mr-2"></i>Cód. Multas CPT (Dec. 303/2023)
                            </a>
                        </div>
                    </li>
                </ul>
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/') }}">
                            <i class="fas fa-home mr-1"></i>Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">
                            <i class="fas fa-sign-in-alt mr-1"></i>Iniciar Sesión
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container-fluid mt-3">
        @yield('content')
    </main>



    <!-- Bootstrap 4 JS -->
    <script src="{{ asset('libs/jquery/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('libs/bootstrap-4.6.2-dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('libs/fontawesome-free-5.15.4-web/js/all.min.js') }}"></script>

    @yield('scripts')

    <!-- Stack para scripts adicionales -->
    @stack('scripts')

    <!-- Lógica para el tema dinámico -->
    <script src="{{ asset('js/theme-change.js') }}?v={{ filemtime(public_path('js/theme-change.js')) }}"></script>

    <!-- Limpiar tokens de sesión para vistas públicas (preserva cookies de tema) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Eliminar cualquier token JWT almacenado para evitar acceso no autorizado
            localStorage.removeItem('jwt_token');
            sessionStorage.removeItem('jwt_token');

            // Eliminar cookies de autenticación si existen, pero preservar las de tema
            document.cookie.split(";").forEach(function(c) {
                var cookieName = c.replace(/^ +/, "").replace(/=.*/, "");
                // No borrar las cookies del tema (bootswatch-theme, bootswatch-theme-name)
                if (cookieName !== 'bootswatch-theme' && cookieName !== 'bootswatch-theme-name') {
                    document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
                }
            });
        });
    </script>
</body>

</html>