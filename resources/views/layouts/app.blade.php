<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/x-icon" href="{{ asset('images/icons/jpm.png') }}">

    <title>@yield('title', 'JPM Oficinas')</title>

    <!-- Bootstrap 4 CSS -->
    <link href="{{ asset('libs/bootstrap-4.6.2-dist/css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Script para cargar el tema dinámico y evitar parpadeos -->
    <script>
        (function() {
            // Define el tema por defecto. El asset() de Laravel generará la ruta correcta.
            const defaultThemePath = "{{ asset('libs/bootswatch@4.6.2/dist/cosmo/bootstrap.min.css') }}";

            // Obtener el tema guardado en LocalStorage
            let themePath = localStorage.getItem("bootswatch-theme") || defaultThemePath;

            // Crear y agregar el elemento link
            const themeLink = document.createElement('link');
            themeLink.id = 'bootswatch-theme';
            themeLink.rel = 'stylesheet';
            themeLink.href = themePath;
            document.head.appendChild(themeLink);
        })();
    </script>

    <!-- Estilos personalizados -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="{{ asset('libs/fontawesome-free-5.15.4-web/css/all.min.css') }}" rel="stylesheet">
    {{-- SweetAlert2 --}}
    <link href="{{ asset('libs/sweetalert2/dist/sweetalert2.min.css') }}" rel="stylesheet">

    {{-- Alpine.js CDN --}}
    {{-- <script defer src="{{ asset('libs/alpinejs@3.14.9/dist/cdn.min.js') }}"></script> --}}

    @livewireStyles
    @yield('styles')

    {{-- @routes --}}
</head>

<body>
    @auth
        @include('layouts.nav')
    @endauth

    <main class="@auth container-fluid mt-2 @else container-fluid @endauth">
        @yield('content')
    </main>

    <!-- Botón flotante para ir al panel principal -->
    @auth
        <a href="{{ route('panel') }}" class="btn-float-base btn-home-float" title="Ir al Panel Principal">
            <i class="fas fa-home"></i>
        </a>

        <!-- Botón flotante para scroll hacia arriba -->
        <button class="btn-float-base btn-scroll-top-float" title="Volver arriba" onclick="scrollToTop()">
            <i class="fas fa-arrow-up"></i>
        </button>

        <!-- Botón flotante para scroll hacia abajo -->
        <button class="btn-float-base btn-scroll-bottom-float" title="Ir al final" onclick="scrollToBottom()">
            <i class="fas fa-arrow-down"></i>
        </button>
    @endauth

    <!-- Bootstrap 4 JS -->
    <script src="{{ asset('libs/jquery/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('libs/bootstrap-4.6.2-dist/js/bootstrap.bundle.min.js') }}"></script>

    @livewireScripts

    <script src="{{ asset('libs/fontawesome-free-5.15.4-web/js/all.min.js') }}"></script>
    <script src="{{ asset('libs/sweetalert2/dist/sweetalert2.all.min.js') }}"></script>

    @yield('scripts')

    <!-- Stack para scripts adicionales -->
    @stack('scripts')

    <!-- Lógica para el tema dinámico -->
    <script src="{{ asset('js/theme-change.js') }}"></script>

    <script>
        document.addEventListener('livewire:load', function () {
            Livewire.onError(statusCode => {
                if (statusCode === 401 || statusCode === 419) {
                    // SweetAlert2 para notificar al usuario y redirigir al login
                    Swal.fire({
                        title: 'Sesión expirada',
                        text: 'Tu sesión ha expirado. Serás redirigido al login.',
                        icon: 'warning',
                        confirmButtonText: 'Aceptar',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            try {
                                // Limpiar tokens locales si existieran
                                localStorage.removeItem('jwt_token');
                                sessionStorage.removeItem('jwt_token');
                            } catch (e) {}
                            // Redirigir explícitamente al login para evitar vistas parciales rotas
                            window.location.href = '{{ route("login") }}';
                        }
                    });
                    return false; // Detiene el manejo de errores de Livewire
                }
            });
        });

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

        window.addEventListener('swal:alert', event => {
            Swal.fire({
                icon: event.detail.type,
                title: event.detail.title,
                text: event.detail.text,
                confirmButtonText: 'Cerrar'
            }).then(() => {
                if (event.detail.modalToClose) {
                    $('#' + event.detail.modalToClose).modal('hide');
                }
            });
        });

        window.addEventListener('swal:toast-error', event => {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000, // 3 segundos
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
            Toast.fire({
                icon: 'error',
                title: event.detail.text
            });
        });

        // Listener para modales de Bootstrap 4 (compatible con jQuery)
        window.addEventListener('show-modal', event => {
            const modalId = event.detail.id;
            if (modalId) {
                $('#' + modalId).modal('show');
            }
        });

        window.addEventListener('hide-modal', event => {
            const modalId = event.detail.id;
            if (modalId) {
                $('#' + modalId).modal('hide');

                // Limpiar datos del modal después de cerrarlo
                setTimeout(() => {
                    if (modalId === 'modalNuevoPendiente') {
                        window.livewire.emit('cerrarModalNuevoPendiente');
                    } else if (modalId === 'modalNuevoPago') {
                        window.livewire.emit('cerrarModalNuevoPago');
                    }
                }, 300);
            }
        });

        // Listener para confirmaciones de SweetAlert2
        window.addEventListener('swal:confirm', event => {
            Swal.fire({
                title: event.detail.title,
                text: event.detail.text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: event.detail.confirmButtonText ? event.detail.confirmButtonText :
                    'Sí, ejecútalo!',
                cancelButtonText: event.detail.cancelButtonText ? event.detail.cancelButtonText : 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.livewire.emit(event.detail.method, event.detail.id);
                }
            });
        });

        // Función para desplazamiento suave hacia arriba
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Función para desplazamiento suave hacia abajo
        function scrollToBottom() {
            window.scrollTo({
                top: document.body.scrollHeight,
                behavior: 'smooth'
            });
        }
    </script>
</body>

</html>
