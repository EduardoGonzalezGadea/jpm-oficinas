<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/x-icon" href="{{ asset('images/icons/jpm.png') }}">

    <title>@yield('title', 'Tesorería | Oficinas')</title>

    <!-- Bootstrap 4 CSS -->
    <link href="{{ asset('libs/bootstrap-4.6.2-dist/css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Script para cargar el tema dinámico y evitar parpadeos -->
    <script>
        (function() {
            @auth
            // Obtener el tema guardado en el perfil del usuario
            const userThemePath = "{{ auth()->user()->theme_path }}";
            const userThemeName = "{{ auth()->user()->theme }}";

            // Sincronizar con LocalStorage para coherencia con el resto de la App
            localStorage.setItem("bootswatch-theme", userThemePath);
            localStorage.setItem("bootswatch-theme-name", userThemeName);

            // Crear y agregar el elemento link
            const themeLink = document.createElement('link');
            themeLink.id = 'bootswatch-theme';
            themeLink.rel = 'stylesheet';
            themeLink.href = userThemePath;
            document.head.appendChild(themeLink);
            @else
            // Para invitados, no cargamos ningún tema de Bootswatch, dejando el Bootstrap base.
            localStorage.removeItem("bootswatch-theme");
            localStorage.removeItem("bootswatch-theme-name");
            @endauth
        })();
    </script>

    <!-- Estilos personalizados -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="{{ asset('libs/fontawesome-free-5.15.4-web/css/all.min.css') }}" rel="stylesheet">

    {{-- Alpine.js CDN --}}
    <script defer src="{{ asset('libs/alpinejs@3.14.9/dist/cdn.min.js') }}"></script>

    @livewireStyles
    @yield('styles')

    @routes
</head>

<body>
    <!-- Header simple para vista pública -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">
                <i class="fas fa-building mr-2"></i>
                Tesorería | Oficinas
            </a>
            <div class="navbar-nav ml-auto">
                <a class="nav-item nav-link" href="{{ url('/') }}">
                    <i class="fas fa-home mr-1"></i>
                    Inicio
                </a>
                <a class="nav-item nav-link" href="{{ route('login') }}">
                    <i class="fas fa-sign-in-alt mr-1"></i>
                    Iniciar Sesión
                </a>
            </div>
        </div>
    </nav>

    <main class="container-fluid mt-3">
        @yield('content')
    </main>

    @livewireScripts

    <!-- Bootstrap 4 JS -->
    <script src="{{ asset('libs/jquery/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('libs/bootstrap-4.6.2-dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('libs/fontawesome-free-5.15.4-web/js/all.min.js') }}"></script>

    @yield('scripts')

    <!-- Stack para scripts adicionales -->
    @stack('scripts')

    <!-- Lógica para el tema dinámico -->
    <script src="{{ asset('js/theme-change.js') }}"></script>

    <!-- Limpiar cualquier token de sesión existente para vistas públicas -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Eliminar cualquier token JWT almacenado para evitar acceso no autorizado
            localStorage.removeItem('jwt_token');
            sessionStorage.removeItem('jwt_token');

            // Eliminar cookies de autenticación si existen
            document.cookie.split(";").forEach(function(c) {
                document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
            });
        });
    </script>
</body>

</html>