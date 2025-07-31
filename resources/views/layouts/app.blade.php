<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/x-icon" href="{{ asset('images/icons/jpm.png') }}">

    <title>@yield('titulo', 'JPM Oficinas')</title>

    <!-- Lógica para el tema dinámico -->
    <script src="{{ asset('js/theme-change.js') }}"></script>

    <!-- Bootstrap 4 CSS -->
    <link href="{{ asset('libs/bootstrap-4.6.2-dist/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Hoja de estilos del tema dinámico -->
    @php
        $themePath = request()->cookie('theme_path', 'libs/bootswatch@4.6.2/dist/cosmo/bootstrap.min.css');
    @endphp
    @if ($themePath)
        <link id="bootswatch-theme" rel="stylesheet" href="{{ asset($themePath) }}">
    @endif
    <!-- Estilos personalizados -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="{{ asset('libs/fontawesome-free-5.15.4-web/css/all.min.css') }}" rel="stylesheet">
    {{-- SweetAlert2 --}}
    <link href="{{ asset('libs/sweetalert2/dist/sweetalert2.min.css') }}" rel="stylesheet">

    @livewireStyles
    @yield('estilos')

    @routes
</head>

<body>
    @auth
        @include('layouts.nav')
    @endauth

    <main class="@auth container-fluid mt-4 @else container-fluid @endauth">
        @yield('contenido')
    </main>

    <!-- Bootstrap 4 JS -->
    <script src="{{ asset('libs/jquery/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('libs/bootstrap-4.6.2-dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('libs/fontawesome-free-5.15.4-web/js/all.min.js') }}"></script>
    <script src="{{ asset('libs/sweetalert2/dist/sweetalert2.all.min.js') }}"></script>
    
    @yield('scripts')

    @livewireScripts

    <script>
        // Listener para notificaciones de SweetAlert2
        window.addEventListener('swal:success', event => {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
            Toast.fire({
                icon: 'success',
                title: event.detail.text
            });
        });

        window.addEventListener('swal:error', event => {
            Swal.fire({
                icon: 'error',
                title: event.detail.title,
                text: event.detail.text,
                confirmButtonText: 'Cerrar'
            });
        });
    </script>
</body>

</html>