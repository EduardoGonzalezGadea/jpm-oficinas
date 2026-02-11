<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-authenticated" content="{{ auth()->check() ? 'true' : 'false' }}">

    <link rel="icon" type="image/x-icon" href="{{ asset('images/icons/jpm.png') }}">

    <title>@yield('title', 'Tesorería | Oficinas')</title>

    <!-- Bootstrap 4 CSS -->
    <link href="{{ asset('libs/bootstrap-4.6.2-dist/css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Script para cargar el tema dinámico y evitar parpadeos -->
    <script>
        (function() {
            @auth
            // Obtenemos los valores directamente del usuario autenticado
            const userThemePath = "{{ auth()->user()->theme_path }}";
            const userThemeName = "{{ auth()->user()->theme }}";

            if (userThemePath) {
                localStorage.setItem("bootswatch-theme", userThemePath);
                localStorage.setItem("bootswatch-theme-name", userThemeName);

                const themeLink = document.createElement('link');
                themeLink.id = 'bootswatch-theme';
                themeLink.rel = 'stylesheet';
                themeLink.href = userThemePath;
                document.head.appendChild(themeLink);
            }
            @else
            localStorage.removeItem("bootswatch-theme");
            localStorage.removeItem("bootswatch-theme-name");
            @endauth
        })();
    </script>

    <!-- Estilos personalizados -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="{{ asset('libs/fontawesome-free-5.15.4-web/css/all.min.css') }}" rel="stylesheet">
    {{-- SweetAlert2 --}}
    <link href="{{ asset('libs/sweetalert2/dist/sweetalert2.min.css') }}" rel="stylesheet">

    <!-- Flatpickr (Fechas dd/mm/yyyy) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/material_blue.css">

    <!-- Alpine.js Intersect Plugin -->
    <script defer src="https://unpkg.com/@alpinejs/intersect@3.x.x/dist/cdn.min.js"></script>
    <!-- Alpine.js CDN -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @livewireStyles
    @yield('styles')

    <style>
        .spinner-border {
            display: inline-block;
            width: 2rem;
            height: 2rem;
            vertical-align: text-bottom;
            border: .25em solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            -webkit-animation: spinner-border .75s linear infinite;
            animation: spinner-border .75s linear infinite;
        }

        @-webkit-keyframes spinner-border {
            to {
                -webkit-transform: rotate(360deg);
            }
        }

        @keyframes spinner-border {
            to {
                transform: rotate(360deg);
            }
        }

        [x-cloak] {
            display: none !important;
        }

        body.modal-open {
            overflow: hidden;
        }

        /* Estilos para modales Alpine */
        .fixed.inset-0 {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
        }

        .z-50 {
            z-index: 50;
        }

        .bg-black {
            background-color: #000;
        }

        .opacity-50 {
            opacity: 0.5;
        }

        .bg-white {
            background-color: #fff;
        }

        .rounded-lg {
            border-radius: 0.5rem;
        }

        .shadow-xl {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .overflow-y-auto {
            overflow-y: auto;
        }

        .modal-alpine {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }

        .modal-alpine-content {
            width: 100%;
            max-width: 32rem;
            margin: auto;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        /* Contenedor para apilar banners si faltan varias extensiones */
        #extension-banners-container {
            position: fixed;
            bottom: 15px;
            left: 15px;
            right: 15px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
            /* Permitir clics a través del contenedor, pero no de sus hijos */
        }

        .extension-banner {
            display: none;
            /* Se activa vía JS con flex */
            pointer-events: auto;
            background: white;
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            align-items: center;
            justify-content: space-between;
            animation: slideUp 0.5s ease-out;
            font-size: 14px;
        }

        #cfe-extension-banner {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
        }

        #text-replacer-banner {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
        }

        .extension-banner .btn-install {
            background: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: bold;
            text-decoration: none;
            margin-left: 10px;
            white-space: nowrap;
            display: inline-block;
        }

        #cfe-extension-banner .btn-install {
            color: #f57c00;
        }

        #text-replacer-banner .btn-install {
            color: #1e7e34;
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Ajustes para pantallas pequeñas */
        @media (max-width: 600px) {
            #extension-banners-container {
                bottom: 10px;
                left: 10px;
                right: 10px;
            }

            .extension-banner {
                flex-direction: column;
                text-align: center;
                padding: 15px;
            }

            .extension-banner>div:first-child {
                margin-bottom: 10px;
            }

            .extension-banner .btn-install {
                margin-left: 0;
                margin-bottom: 8px;
                width: 100%;
            }

            .extension-banner .close {
                position: absolute;
                top: 5px;
                right: 10px;
            }
        }
    </style>

    @auth
    @if(auth()->user()->hasRole('admin') || auth()->user()->esAdministrador())
    <script>
        (function() {
            let extensionVerified = false;

            function checkExtension() {
                if (extensionVerified) return;

                const isInstalled = document.documentElement.hasAttribute('data-cfe-extension-installed');
                const banner = document.getElementById('cfe-extension-banner');

                if (isInstalled) {
                    extensionVerified = true;
                    if (banner) banner.style.setProperty('display', 'none', 'important');
                } else {
                    if (banner) banner.style.setProperty('display', 'flex', 'important');
                }
            }

            let textReplacerVerified = false;

            function checkTextReplacerExtension() {
                if (textReplacerVerified) return;

                const isInstalled = document.documentElement.hasAttribute('data-text-replacer-installed');
                const banner = document.getElementById('text-replacer-banner');

                if (isInstalled) {
                    textReplacerVerified = true;
                    if (banner) banner.style.setProperty('display', 'none', 'important');
                } else {
                    if (banner) banner.style.setProperty('display', 'flex', 'important');
                }
            }

            // Escuchar el evento personalizado de la extensión CFE
            window.addEventListener('cfe-extension-detected', function() {
                extensionVerified = true;
                const banner = document.getElementById('cfe-extension-banner');
                if (banner) banner.style.setProperty('display', 'none', 'important');
                document.documentElement.setAttribute('data-cfe-extension-installed', 'true');
            });

            // Escuchar el evento personalizado de la extensión Text Replacer
            window.addEventListener('text-replacer-detected', function() {
                textReplacerVerified = true;
                const banner = document.getElementById('text-replacer-banner');
                if (banner) banner.style.setProperty('display', 'none', 'important');
                document.documentElement.setAttribute('data-text-replacer-installed', 'true');
            });

            // Dar 3 segundos para que las extensiones se reporten antes de mostrar los banners
            window.addEventListener('load', function() {
                setTimeout(() => {
                    checkExtension();
                    checkTextReplacerExtension();
                }, 3000);
            });
        })();
    </script>
    @endif
    @endauth

    {{-- @routes --}}
</head>

<body>
    @auth
    @if(auth()->user()->hasRole('admin') || auth()->user()->esAdministrador())
    <div id="extension-banners-container">
        <div id="cfe-extension-banner" class="extension-banner">
            <div>
                <i class="fas fa-puzzle-piece mr-2"></i>
                <strong>¡Atención!</strong> La extensión "Detector de CFEs" no está instalada.
            </div>
            <div>
                <a href="{{ route('extension.download') }}" class="btn-install mr-2" data-no-loader="true">
                    <i class="fas fa-download mr-1"></i> Descargar
                </a>
                <a href="#" onclick="Swal.fire({
                            title: 'Instalación CFE',
                            html: '<div class=\'text-left\'><ol><li>Descarga el ZIP.</li><li>Descomprime en una carpeta.</li><li>Ve a <b>Extensiones</b> en el navegador.</li><li>Activa <b>Modo Desarrollador</b>.</li><li>Clic en <b>Cargar descomprimida</b>.</li></ol></div>',
                            icon: 'info',
                            confirmButtonText: 'Entendido'
                        }); return false;" class="text-white small underline">
                    ¿Cómo instalar?
                </a>
                <button type="button" class="close text-white ml-3" onclick="document.getElementById('cfe-extension-banner').style.display='none'">
                    <span>&times;</span>
                </button>
            </div>
        </div>

        <div id="text-replacer-banner" class="extension-banner">
            <div>
                <i class="fas fa-keyboard mr-2"></i>
                <strong>¡Atención!</strong> La extensión "Text Replacer" no está instalada.
            </div>
            <div>
                <a href="{{ route('extension.text-replacer.download') }}" class="btn-install mr-2" data-no-loader="true">
                    <i class="fas fa-download mr-1"></i> Descargar
                </a>
                <a href="#" onclick="Swal.fire({
                            title: 'Instalación Text Replacer',
                            html: '<div class=\'text-left\'><ol><li>Descarga el ZIP.</li><li>Descomprime en una carpeta.</li><li>Ve a <b>Extensiones</b> en el navegador.</li><li>Activa <b>Modo Desarrollador</b>.</li><li>Clic en <b>Cargar descomprimida</b>.</li></ol></div>',
                            icon: 'info',
                            confirmButtonText: 'Entendido'
                        }); return false;" class="text-white small underline">
                    ¿Cómo instalar?
                </a>
                <button type="button" class="close text-white ml-3" onclick="document.getElementById('text-replacer-banner').style.display='none'">
                    <span>&times;</span>
                </button>
            </div>
        </div>
    </div>
    @endif
    @include('layouts.nav')
    @endauth

    <main class="@auth container-fluid mt-0 p-1 @else container-fluid @endauth">
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

    {{-- Loader --}}
    <div id="loader" wire:loading.attr="hidden" wire:target="openEmitirModal, openAnularModal, formarPlanilla, emitir, editar, anular, openEditarModal, clearSearch, sortBy, seleccionarBeneficiario, seleccionarConcepto, selectedCheques" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 9999; flex-direction: column; justify-content: center; align-items: center;">
        <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
            <span class="sr-only">Cargando...</span>
        </div>
        <p class="text-light mt-2">Procesando...</p>
    </div>

    <script>
        // Función para desplazamiento suave (ya que los botones flotantes las necesitan)
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        function scrollToBottom() {
            window.scrollTo({
                top: document.body.scrollHeight,
                behavior: 'smooth'
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const loader = document.getElementById('loader');

            // La visibilidad del loader ahora se controla directamente con wire:loading.attr="hidden" y wire:target
            // por lo que los hooks de Livewire para showLoader/hideLoader ya no son necesarios aquí.

            window.addEventListener('hide-loader', () => {
                // Este listener aún puede ser útil si hay lógica JS que necesite ocultar el loader manualmente
                if (loader) {
                    loader.style.display = 'none';
                }
            });

            // --- Integración con Livewire (manejo de errores y finalización de peticiones) ---
            if (typeof Livewire !== 'undefined') {
                // Ocultar el loader cuando se recibe una respuesta exitosa de Livewire
                Livewire.on('message.received', () => {
                    if (loader) {
                        loader.style.display = 'none';
                    }
                });

                Livewire.onError((statusCode, response) => {
                    if (loader) {
                        loader.style.display = 'none';
                    }

                    // 419 (Page Expired) o 401 (Unauthorized) indican que la sesión terminó
                    if (statusCode === 419 || statusCode === 401) {
                        // Verificar si la respuesta JSON contiene el mensaje de sesión expirada del Handler
                        if (response && response.message && response.message.includes('sesión')) {
                            Swal.fire({
                                title: 'Sesión expirada',
                                text: response.message,
                                icon: 'warning',
                                confirmButtonText: 'Ir al Login',
                                allowOutsideClick: false,
                                allowEscapeKey: false
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = response.redirect || '{{ route("login") }}';
                                }
                            });
                        } else {
                            Swal.fire({
                                title: 'Sesión expirada',
                                text: 'Tu sesión ha terminado por inactividad o tu token de seguridad ha caducado. Debes ingresar nuevamente.',
                                icon: 'warning',
                                confirmButtonText: 'Ir al Login',
                                allowOutsideClick: false,
                                allowEscapeKey: false
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = '{{ route("login") }}';
                                }
                            });
                        }

                        // Retornamos false para suprimir el mensaje de error por defecto de Livewire
                        return false;
                    }

                    // Manejo opcional para errores internos del servidor (500)
                    if (statusCode === 500) {
                        Swal.fire({
                            title: 'Error en el servidor',
                            text: 'El servidor encontró un error inesperado al procesar la solicitud.',
                            icon: 'error',
                            confirmButtonText: 'Cerrar'
                        });
                        return false;
                    }

                    // Para cualquier otro error no especificado, permitimos que Livewire use su logica por defecto o simplemente lo ignoramos si preferimos
                    // return false;
                });
            }

            // --- Integración con Formularios y Enlaces (Navegación Tradicional) ---
            // Estos loaders se mantienen para envíos de formularios y navegación tradicional,
            // ya que wire:loading.target solo afecta a las peticiones de Livewire.
            function showLoaderManual() {
                if (loader) {
                    loader.style.display = 'flex';
                }
            }

            document.addEventListener('submit', function(e) {
                // Solo mostrar si el formulario no tiene el atributo 'data-no-loader' y no es un formulario Livewire
                const hasDataNoLoader = e.target.hasAttribute('data-no-loader');

                // Check for any wire:submit* attributes (including wire:submit.prevent, etc.)
                const hasWireSubmit = Array.from(e.target.attributes).some(attr => attr.name.startsWith('wire:submit')) ||
                    e.target.querySelector('[wire\\:submit]') !== null ||
                    e.target.querySelector('[wire\\:submit\\:prevent]') !== null;

                if (!hasDataNoLoader && !hasWireSubmit) {
                    showLoaderManual();
                }
            });

            document.addEventListener('click', function(e) {
                const target = e.target.closest('a');

                // Si no es un enlace, no hacer nada
                if (!target) return;

                const dataToggle = target.getAttribute('data-toggle');
                // Ignorar si es un toggle de dropdown, tab o pill de Bootstrap
                if (dataToggle === 'dropdown' || dataToggle === 'tab' || dataToggle === 'pill') {
                    return;
                }

                // Condiciones para mostrar el loader en otros enlaces
                const isNavigable = target.href &&
                    !target.href.endsWith('#') &&
                    target.target !== '_blank' &&
                    !target.hasAttribute('data-no-loader');

                if (isNavigable) {
                    showLoaderManual();
                }
            });

            // Ocultar el loader si el usuario vuelve con el botón de atrás del navegador
            window.addEventListener('pageshow', function(event) {
                if (event.persisted) {
                    if (loader) {
                        loader.style.display = 'none';
                    }
                }
            });

            // --- Listeners de SweetAlert y Modales (conservados del script original) ---
            window.addEventListener('swal:success', event => {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });

                // Handle both object format (title + text) and simple string format
                if (typeof event.detail === 'object') {
                    Toast.fire({
                        icon: 'success',
                        title: event.detail.title || 'Éxito',
                        text: event.detail.text
                    });
                } else {
                    Toast.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: event.detail
                    });
                }
            });

            window.addEventListener('show-success-alert', event => {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });
                Toast.fire({
                    icon: 'success',
                    title: event.detail.message
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
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });
                Toast.fire({
                    icon: 'error',
                    title: event.detail.text
                });
            });

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
                }
            });

            window.addEventListener('swal:confirm', event => {
                Swal.fire({
                    title: event.detail.title,
                    text: event.detail.text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: event.detail.confirmButtonText || 'Sí, acepto!',
                    cancelButtonText: event.detail.cancelButtonText || 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (event.detail.componentId) {
                            Livewire.find(event.detail.componentId).call(event.detail.method, event.detail.id);
                        } else {
                            window.livewire.emit(event.detail.method, event.detail.id);
                        }
                    }
                });
            });

            window.addEventListener('swal:confirm-with-input', event => {
                Swal.fire({
                    title: event.detail.title,
                    text: event.detail.text,
                    icon: 'warning',
                    input: event.detail.input || 'text',
                    inputLabel: event.detail.inputLabel,
                    inputPlaceholder: event.detail.inputPlaceholder,
                    inputValidator: event.detail.inputValidator ? new Function('return ' + event.detail.inputValidator)() : null,
                    inputAttributes: event.detail.inputAttributes || {},
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: event.detail.confirmButtonText || 'Sí, aceptar',
                    cancelButtonText: event.detail.cancelButtonText || 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (event.detail.componentId) {
                            Livewire.find(event.detail.componentId).call(event.detail.method, result.value);
                        } else {
                            window.livewire.emit(event.detail.method, result.value);
                        }
                    }
                });
            });

            // Lógica de respaldo con AJAX (adaptada para no usar el spinner global antiguo)
            $('#btn-crear-respaldo-menu').on('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: '¿Crear nuevo respaldo?',
                    text: 'Esto puede tardar unos minutos. ¿Desea continuar?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, crear respaldo',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // El loader automático se mostrará por el evento 'submit' o 'click' si esto fuera un formulario/enlace.
                        // Como es AJAX, lo disparamos manualmente.
                        showLoaderManual(); // Usar la función manual para este caso específico
                        $.ajax({
                            url: "{{ route('system.backups.create') }}",
                            method: 'GET',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(data) {
                                if (loader) {
                                    loader.style.display = 'none';
                                } // Ocultar manualmente
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Respaldo creado',
                                    text: data.message || 'El respaldo se ha creado correctamente.',
                                    confirmButtonText: 'Aceptar'
                                }).then(() => {
                                    if (window.location.pathname.includes('/system/backups')) {
                                        window.location.reload(); // El loader se mostrará automáticamente aquí
                                    }
                                });
                            },
                            error: function(xhr) {
                                if (loader) {
                                    loader.style.display = 'none';
                                } // Ocultar manualmente
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: (xhr.responseJSON && xhr.responseJSON.message) || 'Ocurrió un error al crear el respaldo.',
                                    confirmButtonText: 'Aceptar'
                                });
                            }
                        });
                    }
                });
            });
        });

        // Listener global para data-swal-confirm
        document.addEventListener('click', function(e) {
            const trigger = e.target.closest('[data-swal-confirm]');
            if (!trigger) return;

            e.preventDefault();
            const data = trigger.dataset;

            Swal.fire({
                title: data.swalTitle || '¿Estás seguro?',
                text: data.swalText || '¡No podrás revertir esto!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: data.swalConfirmBtn || 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Soporte para emitir a Livewire global o a un componente específico si se pudiera identificar
                    // Por defecto: Livewire.emit global
                    window.livewire.emit(data.swalMethod, data.swalId);
                }
            });
        });

        window.addEventListener('openInNewTab', event => {
            window.open(event.detail, '_blank');
        });

        document.addEventListener('livewire:load', function() {
            window.livewire.on('openInNewTab', (url) => {
                window.open(url, '_blank');
            });
        });
    </script>

    <script src="{{ asset('js/print.js') }}"></script>

    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initFlatpickr();
        });

        document.addEventListener('livewire:load', function() {
            initFlatpickr();
            Livewire.hook('message.processed', (message, component) => {
                initFlatpickr();
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
</body>

</html>