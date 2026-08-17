<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="login-url" content="{{ route('login') }}">
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
    <style>.card-header svg[data-icon="calendar-alt"]{width:1em!important;height:1em!important;vertical-align:middle!important}</style>
    {{-- SweetAlert2 --}}
    <link href="{{ asset('libs/sweetalert2/dist/sweetalert2.min.css') }}" rel="stylesheet">

    <!-- Flatpickr (Fechas dd/mm/yyyy) -->
    <link href="{{ asset('libs/flatpickr/flatpickr.min.css') }}" rel="stylesheet">
    <link href="{{ asset('libs/flatpickr/material_blue.css') }}" rel="stylesheet">

    {{-- @routes --}}
</head>

<body>
    @auth
    @include('layouts.nav')
    @endauth

    <main class="@auth container-fluid mt-0 p-1 @else container-fluid @endauth">
        @yield('content')
        {{ $slot ?? '' }}
    </main>

    <!-- Botones Flotantes de Navegación (Comentados por solicitud: los usuarios no se han acostumbrado) -->
    {{-- 
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
    --}}

    <!-- Bootstrap 4 JS -->
    <!-- data-navigate-once: estos scripts globales no deben re-ejecutarse al
         navegar con wire:navigate (evita que la delegación de eventos de
         dropdowns/modales queden sin inicializar). -->
    <script data-navigate-once src="{{ asset('libs/jquery/js/jquery-3.6.0.min.js') }}"></script>
    <script data-navigate-once src="{{ asset('libs/bootstrap-4.6.2-dist/js/bootstrap.bundle.min.js') }}"></script>



    <script data-navigate-once src="{{ asset('libs/fontawesome-free-5.15.4-web/js/all.min.js') }}"></script>
    <script data-navigate-once src="{{ asset('libs/sweetalert2/dist/sweetalert2.all.min.js') }}"></script>

    @yield('scripts')

    <!-- Stack para scripts adicionales -->
    @stack('scripts')

    <!-- Lógica para el tema dinámico -->
    <script data-navigate-once src="{{ asset('js/session-expired.js') }}?v={{ filemtime(public_path('js/session-expired.js')) }}"></script>
    <script data-navigate-once src="{{ asset('js/theme-change.js') }}?v={{ filemtime(public_path('js/theme-change.js')) }}"></script>

    {{-- Loader global: controlado por JS (ver script abajo) para omitir los syncInput de escritura --}}
    <div
        id="livewire-loader"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.45); z-index:9999; flex-direction:column; justify-content:center; align-items:center;"
    >
        <div class="spinner-border text-light" role="status" style="width:3rem; height:3rem;">
            <span class="sr-only">Cargando...</span>
        </div>
        <p class="text-light mt-2 mb-0" style="font-size:0.95rem;">Procesando...</p>
    </div>

    <script>
        // Loader por eventos Livewire v3: cubre tanto componentes inline como los que
        // usan .extends('layouts.app'), donde wire:loading.flex no alcanza el DOM del loader.
        (function () {
            var loader = document.getElementById('livewire-loader');
            var loaderTimer = null;
            var hideTimer = null;
            var pendingRequests = 0;
            var DELAY_MS = 0;        // mostrar de inmediato
            var MIN_VISIBLE_MS = 100; // permanencia mínima para que se perciba

            function showLoader() {
                pendingRequests++;
                if (loaderTimer) return;
                clearTimeout(hideTimer);
                hideTimer = null;
                loaderTimer = setTimeout(function () {
                    loaderTimer = null;
                    if (loader && pendingRequests > 0) loader.style.display = 'flex';
                }, DELAY_MS);
            }

            function hideLoader() {
                if (pendingRequests > 0) pendingRequests--;
                if (pendingRequests > 0) return;
                clearTimeout(loaderTimer);
                loaderTimer = null;
                if (!loader) return;
                if (loader.style.display === 'flex') {
                    // Garantizar un tiempo mínimo visible, sin esconder si llega otra petición
                    hideTimer = setTimeout(function () {
                        if (pendingRequests === 0) loader.style.display = 'none';
                    }, MIN_VISIBLE_MS);
                } else {
                    loader.style.display = 'none';
                }
            }

            // Livewire v3: el hook 'commit' intercepta el ciclo de vida de red completo.
            // 'succeed' se dispara al terminar; dentro de succeed/fail se oculta.
            // En v3 el commit expone 'calls' (métodos invocados) y las actualizaciones de
            // propiedades van aparte (payload.updates). Solo mostramos el loader cuando el
            // commit invoca métodos (consultas/acciones), no en escritura/typing puro.
            document.addEventListener('livewire:init', function () {
                Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
                    var hasCalls = commit.calls && commit.calls.length > 0;

                    if (hasCalls) {
                        showLoader();
                    }

                    succeed(() => { hideLoader(); });
                    fail(() => { hideLoader(); });
                });
            });
        })();
    </script>

    <script data-navigate-once src="{{ asset('js/app-layout.js') }}"></script>

    <script data-navigate-once src="{{ asset('js/enter-navigation.js') }}"></script>

    <script data-navigate-once src="{{ asset('js/print.js') }}"></script>

    <!-- Flatpickr JS -->
    <script data-navigate-once src="{{ asset('libs/flatpickr/flatpickr.min.js') }}"></script>
    <script data-navigate-once src="{{ asset('libs/flatpickr/es.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initFlatpickr();
        });

        document.addEventListener('livewire:init', function() {
            initFlatpickr();
            // Livewire v3: reinicializar flatpickr después de cada commit exitoso
            Livewire.hook('commit', ({ succeed }) => {
                succeed(() => {
                    queueMicrotask(initFlatpickr);
                });
            });
        });

        function initFlatpickr() {
            flatpickr(".datepicker-uy", {
                locale: "es",
                dateFormat: "Y-m-d", // Formato interno (compatible con Livewire/ISO)
                altInput: true,
                altFormat: "d/m/Y", // Formato visual para Uruguay
                allowInput: true,
            });
        }
    </script>

    @auth
    <script>
        // Sistema de Cierre Automático de Sesión por Inactividad
        (function() {
            let inactivityTime = function () {
                // Obtiene la duración de la sesión del servidor en milisegundos (alineado con config/session.php)
                const sessionLifetime = {{ config('session.lifetime', 1440) }} * 60 * 1000;
                const warningTime = sessionLifetime - (5 * 60 * 1000);
                const keepAliveInterval = Math.max(5 * 60 * 1000, sessionLifetime - (10 * 60 * 1000));

                let warningTimeout;
                let logoutTimeout;
                let keepAliveTimeout;
                let lastKeepAliveAt = 0;

                // Eventos que indican actividad del usuario
                window.onload = resetTimer;
                document.onmousemove = resetTimer;
                document.onkeypress = resetTimer;
                document.onclick = resetTimer;
                document.onscroll = resetTimer;

                function showWarning() {
                    if (window.Swal) {
                        Swal.fire({
                            title: 'Sesión por expirar',
                            text: 'Tu sesión se cerrará automáticamente en 5 minutos debido a inactividad. ¿Deseas mantenerla activa?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Sí, mantener activa',
                            cancelButtonText: 'Cerrar sesión ahora',
                            timer: 5 * 60 * 1000,
                            timerProgressBar: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                renewSession();
                                resetTimer();
                            } else if (result.dismiss === Swal.DismissReason.cancel) {
                                forceLogout();
                            } else if (result.dismiss === Swal.DismissReason.timer) {
                                // Si el modal se cierra por el timer, ya se ejecutó forceLogout por el logoutTimeout
                            }
                        });
                    }
                }

                function forceLogout() {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route("logout") }}';
                    
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = csrfToken;
                    
                    form.appendChild(csrfInput);
                    document.body.appendChild(form);
                    form.submit();
                }

                function renewSession() {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    fetch('{{ route("session.keep-alive") }}', {
                        method: 'POST',
                        credentials: 'include',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({}),
                    }).catch(function () {
                        // Ignorar errores de red puntuales
                    });
                }

                function scheduleKeepAlive() {
                    clearTimeout(keepAliveTimeout);
                    keepAliveTimeout = setTimeout(function () {
                        const now = Date.now();
                        if (now - lastKeepAliveAt >= keepAliveInterval) {
                            lastKeepAliveAt = now;
                            renewSession();
                        }
                        scheduleKeepAlive();
                    }, keepAliveInterval);
                }

                function resetTimer() {
                    clearTimeout(warningTimeout);
                    clearTimeout(logoutTimeout);
                    
                    warningTimeout = setTimeout(showWarning, warningTime);
                    logoutTimeout = setTimeout(forceLogout, sessionLifetime);
                    
                    // Sincronizar actividad en múltiples pestañas
                    localStorage.setItem('lastActivity', Date.now().toString());

                    const now = Date.now();
                    if (now - lastKeepAliveAt >= keepAliveInterval) {
                        lastKeepAliveAt = now;
                        renewSession();
                    }
                }

                // Revisar actividad periódicamente para detectar si otra pestaña expiró la sesión
                setInterval(function() {
                    let lastActivity = localStorage.getItem('lastActivity');
                    if (lastActivity) {
                        let diff = Date.now() - parseInt(lastActivity);
                        if (diff >= sessionLifetime) {
                            forceLogout();
                        }
                    }
                }, 30000); // Revisar cada 30 segundos

                scheduleKeepAlive();
            };

            inactivityTime();
        })();
    </script>
    @endauth
</body>

</html>