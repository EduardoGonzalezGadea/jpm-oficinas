@extends('layouts.app')

@section('title', 'Tesorería | Oficinas')

@section('content')
<div class="container-fluid panel-gradient-bg" id="panel-container">

    <!-- 1. Encabezado Moderno y Compacto -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-premium-header">Panel Principal</h1>
            <p class="mb-0 text-premium-muted">
                Bienvenid@, <strong>{{ $usuario->nombre }} {{ $usuario->apellido }}</strong> |
                Rol: <strong>{{ ucfirst(str_replace('_', ' ', $usuario->getRoleNames()->first())) }}</strong>
                @if ($usuario->modulo)
                | Módulo: <strong>{{ $usuario->modulo->nombre }}</strong>
                @endif
            </p>
        </div>
        {{-- Reloj sincronizado con Internet --}}
        <div class="d-none d-sm-inline-block text-right">
            <div class="d-flex align-items-center">
                <span id="sync-indicator" class="mr-2" title="Cargando...">
                    <i class="fas fa-sync-alt fa-spin text-premium-muted"></i>
                </span>
                <div>
                    <span class="font-weight-bold text-premium-header" style="font-size: 0.95rem;" id="fecha-hora">
                        <i class="far fa-calendar-alt mr-1"></i>{{ now()->format('d/m/Y') }} - {{ now()->format('H:i:s') }}
                    </span>
                    <br>
                    <small id="sync-status" class="text-premium-muted">Sincronizando...</small>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Alertas del Sistema (Acordeón Compacto y Contrastado) -->
    @php
        $todasLasAlertas = collect();
        if (isset($alertas['criticas']['alertas'])) {
            $todasLasAlertas = $todasLasAlertas->merge($alertas['criticas']['alertas']);
        }
        if (isset($alertas['colapsables'])) {
            foreach ($alertas['colapsables'] as $colapsable) {
                if (isset($colapsable['alertas'])) {
                    $todasLasAlertas = $todasLasAlertas->merge($colapsable['alertas']);
                }
            }
        }
    @endphp

    @if($todasLasAlertas->isNotEmpty())
    <div class="accordion mb-4 shadow-sm" id="alertasAccordion">
        <div class="card border-0">
            <div class="card-header p-0 bg-warning" id="headingAlertas">
                <h2 class="mb-0">
                    <button class="btn btn-block text-left d-flex align-items-center justify-content-between text-decoration-none py-2 px-3 collapsed" type="button" data-toggle="collapse" data-target="#collapseAlertas" aria-expanded="false" aria-controls="collapseAlertas" style="color: #FFFFFF !important; text-shadow: 1px 1px 2px rgba(0,0,0,0.7), 0 0 3px rgba(0,0,0,0.5);">
                        <span class="font-weight-bold" style="font-size: 0.9rem;">
                            <i class="fas fa-exclamation-triangle mr-2"></i>ALERTAS DEL SISTEMA
                            <span class="badge badge-dark badge-pill ml-2">{{ $todasLasAlertas->count() }}</span>
                        </span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </h2>
            </div>

            <div id="collapseAlertas" class="collapse" aria-labelledby="headingAlertas" data-parent="#alertasAccordion">
                <div class="card-body p-0 border-left border-right border-bottom border-warning">
                    <ul class="list-group list-group-flush">
                        @foreach($todasLasAlertas as $alerta)
                        <li class="list-group-item list-group-item-warning d-flex justify-content-between align-items-center py-1 px-3" style="font-size: 0.85rem;">
                            <span class="">
                                <i class="fas fa-circle text-{{ $alerta['tipo'] ?? 'warning' }} mr-2" style="font-size: 0.5rem;"></i>
                                @if(isset($alerta['titulo'])) <strong>{{ $alerta['titulo'] }}:</strong> @endif 
                                {!! $alerta['mensaje'] !!}
                            </span>
                            @if(isset($alerta['accion']) && isset($alerta['accion']['route']))
                                <a href="{{ route($alerta['accion']['route']) }}" class="font-weight-bold text-decoration-none">{{ $alerta['accion']['label'] ?? 'Ver' }} <i class="fas fa-arrow-right ml-1"></i></a>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- 3. Accesos Rápidos (Widgets Compactos) -->
    <h5 class="mb-3 text-premium-header ml-1">
        <i class="fas fa-th-large mr-2"></i>Accesos Directos
    </h5>
    <div class="row">
        @can('operador_tesoreria')
        <!-- Arrendamientos -->
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
            <a href="{{ route('tesoreria.arrendamientos.index') }}" class="text-decoration-none">
                <div class="card shadow-sm h-100 widget-card border-left-financial">
                    <div class="card-body p-2 d-flex align-items-center">
                        <div class="mr-3 text-financial">
                            <i class="fas fa-file-signature fa-2x"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-body small">Arrendamientos</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Art. Multas CPT -->
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
            <a href="{{ route('tesoreria.multas-transito') }}" class="text-decoration-none">
                <div class="card shadow-sm h-100 widget-card border-left-operational">
                    <div class="card-body p-2 d-flex align-items-center">
                        <div class="mr-3 text-operational">
                            <i class="fas fa-list fa-2x"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-body small">Art. Multas CPT</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Multas CPT Dec. 303 -->
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
            <a href="{{ route('tesoreria.multas-303-2023') }}" class="text-decoration-none">
                <div class="card shadow-sm h-100 widget-card border-left-operational">
                    <div class="card-body p-2 d-flex align-items-center">
                        <div class="mr-3 text-operational">
                            <i class="fas fa-list-alt fa-2x"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-body small">Cod. Multas Dec.303</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Caja Chica -->
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
            <a href="{{ route('tesoreria.caja-chica.index') }}" class="text-decoration-none">
                <div class="card shadow-sm h-100 widget-card border-left-financial">
                    <div class="card-body p-2 d-flex align-items-center">
                        <div class="mr-3 text-financial">
                            <i class="fas fa-coins fa-2x"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-body small">Caja Chica</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

    
        <!-- Cert. Residencia -->
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
            <a href="{{ route('tesoreria.certificados-residencia.index') }}" class="text-decoration-none">
                <div class="card shadow-sm h-100 widget-card border-left-documentary">
                    <div class="card-body p-2 d-flex align-items-center">
                        <div class="mr-3 text-documentary">
                            <i class="fas fa-file-alt fa-2x"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-body small">Cert. Residencia</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Tarjetas BROU -->
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
            <a href="{{ route('tesoreria.tarjetas-cobro-brou.index') }}" class="text-decoration-none">
                <div class="card shadow-sm h-100 widget-card border-left-documentary">
                    <div class="card-body p-2 d-flex align-items-center">
                        <div class="mr-3 text-documentary">
                            <i class="fas fa-credit-card fa-2x"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-body small">Tarjetas BROU</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Gestión de CFEs -->
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
            <a href="{{ route('tesoreria.gestion-cfe.index') }}" class="text-decoration-none">
                <div class="card shadow-sm h-100 widget-card border-left-documentary">
                    <div class="card-body p-2 d-flex align-items-center">
                        <div class="mr-3 text-documentary">
                            <i class="fas fa-file-invoice fa-2x"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-body small">Gestión de CFEs</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Cheques -->
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
            <a href="{{ route('tesoreria.cheques.index') }}" class="text-decoration-none">
                <div class="card shadow-sm h-100 widget-card border-left-financial">
                    <div class="card-body p-2 d-flex align-items-center">
                        <div class="mr-3 text-financial">
                            <i class="fas fa-money-check fa-2x"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-body small">Cheques</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        @can('administrar_sistema')

        @endcan

        <!-- Depósito Vehículos -->
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
            <a href="{{ route('tesoreria.deposito-vehiculos.index') }}" class="text-decoration-none">
                <div class="card shadow-sm h-100 widget-card border-left-operational">
                    <div class="card-body p-2 d-flex align-items-center">
                        <div class="mr-3 text-operational">
                            <i class="fas fa-car fa-2x"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-body small">Depósito Vehículos</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Eventuales -->
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
            <a href="{{ route('tesoreria.eventuales.index') }}" class="text-decoration-none">
                <div class="card shadow-sm h-100 widget-card border-left-financial">
                    <div class="card-body p-2 d-flex align-items-center">
                        <div class="mr-3 text-financial">
                            <i class="fas fa-hand-holding-usd fa-2x"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-body small">Eventuales</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Multas Cobradas -->
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
            <a href="{{ route('tesoreria.multas-cobradas.index') }}" class="text-decoration-none">
                <div class="card shadow-sm h-100 widget-card border-left-operational">
                    <div class="card-body p-2 d-flex align-items-center">
                        <div class="mr-3 text-operational">
                            <i class="fas fa-receipt fa-2x"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-body small">Multas Cobradas</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Porte de Armas -->
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
            <a href="{{ route('tesoreria.armas.porte') }}" class="text-decoration-none">
                <div class="card shadow-sm h-100 widget-card border-left-operational">
                    <div class="card-body p-2 d-flex align-items-center">
                        <div class="mr-3 text-operational">
                            <i class="fas fa-shield-alt fa-2x"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-body small">Porte de Armas</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Prendas -->
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
            <a href="{{ route('tesoreria.prendas.index') }}" class="text-decoration-none">
                <div class="card shadow-sm h-100 widget-card border-left-documentary">
                    <div class="card-body p-2 d-flex align-items-center">
                        <div class="mr-3 text-documentary">
                            <i class="fas fa-file-invoice-dollar fa-2x"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-body small">Prendas</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Valores -->
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
            <a href="{{ route('tesoreria.valores.index') }}" class="text-decoration-none">
                <div class="card shadow-sm h-100 widget-card border-left-financial">
                    <div class="card-body p-2 d-flex align-items-center">
                        <div class="mr-3 text-financial">
                            <i class="fas fa-barcode fa-2x"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-body small">Valores</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        @if(auth()->user()->hasAnyRole(['administrador', 'gerente_tesoreria', 'supervisor_tesoreria']))
        <!-- Reporte de Recibos (Contabilidad) -->
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
            <a href="{{ route('tesoreria.reporte-recibos.index') }}" class="text-decoration-none">
                <div class="card shadow-sm h-100 widget-card border-left-danger">
                    <div class="card-body p-2 d-flex align-items-center">
                        <div class="mr-3 text-danger">
                            <i class="fas fa-clipboard-list fa-2x"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-body small">Reporte Recibos</div>
                            <div class="text-muted" style="font-size: 0.65rem;">Para Contabilidad</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endif
        @endcan

        @can('ver_usuarios')
        <!-- Usuarios -->
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
            <a href="{{ route('usuarios.index') }}" class="text-decoration-none">
                <div class="card shadow-sm h-100 widget-card border-left-system">
                    <div class="card-body p-2 d-flex align-items-center">
                        <div class="mr-3 text-system">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-body small">Usuarios</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endcan

        <!-- Mi Perfil (siempre al final) -->
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
            <a href="{{ route('usuarios.miPerfil') }}" class="text-decoration-none">
                <div class="card shadow-sm h-100 widget-card border-left-system">
                    <div class="card-body p-2 d-flex align-items-center">
                        <div class="mr-3 text-system">
                            <i class="fas fa-user-edit fa-2x"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-body small">Mi Perfil</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- 5. Footer Simplificado -->
    <footer class="sticky-footer mt-4 rounded shadow-sm">
        <div class="container my-auto">
            <div class="copyright text-center my-auto">
                <span class="d-block mb-1">Creado por el Cabo (P.A.) Eduardo González Gadea (Dirección de Tesorería)</span>
                <small>República Oriental del Uruguay | Ministerio del Interior | Jefatura de Policía de Montevideo | Años 2025/2026</small>
            </div>
        </div>
    </footer>

</div>

<!-- Los estilos de .sticky-footer, .panel-gradient-bg, .text-premium-header y
     .text-premium-muted ahora residen en resources/css/app.css y usan variables CSS
     que se adaptan automáticamente al tema (claro/oscuro). -->

<!-- Script para Reloj Sincronizado con Internet -->
<script>
    (function() {
        // Variables globales
        let offsetMs = 0; // Diferencia entre hora del servidor y hora local
        let isSynced = false;
        let lastSyncTime = null;
        let syncSource = 'server';
        const SYNC_INTERVAL = 300000; // Resincronizar cada 5 minutos

        // Elementos del DOM
        const fechaHoraEl = document.getElementById('fecha-hora');
        const syncIndicatorEl = document.getElementById('sync-indicator');
        const syncStatusEl = document.getElementById('sync-status');

        // Función para formatear fecha
        function formatDate(date) {
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const seconds = String(date.getSeconds()).padStart(2, '0');
            return `<i class="far fa-calendar-alt mr-1"></i>${day}/${month}/${year} - ${hours}:${minutes}:${seconds}`;
        }

        // Función para actualizar el indicador de estado
        function updateSyncIndicator(synced, error = false) {
            if (error) {
                syncIndicatorEl.innerHTML = '<i class="fas fa-exclamation-circle text-warning" title="Error de sincronización"></i>';
                syncIndicatorEl.title = 'Error de sincronización';
            } else if (synced) {
                syncIndicatorEl.innerHTML = '<i class="fas fa-check-circle text-success" title="Sincronizado con Internet"></i>';
                syncIndicatorEl.title = 'Sincronizado con Internet (Uruguay) - ' + syncSource;
            } else {
                syncIndicatorEl.innerHTML = '<i class="fas fa-clock text-light" title="Hora local del servidor"></i>';
                syncIndicatorEl.title = 'Hora local del servidor';
            }
        }

        // Función para obtener la hora actual ajustada
        function getCurrentTime() {
            const now = new Date();
            if (isSynced || offsetMs !== 0) {
                return new Date(now.getTime() + offsetMs);
            }
            return now;
        }

        let syncRetries = 0;
        const MAX_RETRIES = 50;
        const RETRY_DELAY = 1000; // 1 segundo entre reintentos

        // Función para actualizar el reloj
        function updateClock() {
            const currentTime = getCurrentTime();
            fechaHoraEl.innerHTML = formatDate(currentTime);
        }

        // Función para sincronizar usando el endpoint backend
        async function syncWithInternet() {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000); // Timeout de 10 segundos

                const localTimeBefore = Date.now();
                const response = await fetch('{{ route("utilidad.hora-uruguay") }}', {
                    method: 'GET',
                    cache: 'no-cache',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    signal: controller.signal
                });
                clearTimeout(timeoutId);
                const localTimeAfter = Date.now();

                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }

                const data = await response.json();

                if (data.success) {
                    const serverTime = new Date(data.datetime);

                    // Calcular el offset considerando la latencia de red
                    const latency = (localTimeAfter - localTimeBefore) / 2;
                    const localTimeEstimated = new Date(localTimeBefore + latency);
                    offsetMs = serverTime.getTime() - localTimeEstimated.getTime();

                    isSynced = data.synced;
                    syncSource = data.source;
                    lastSyncTime = new Date();
                    syncRetries = 0; // Reiniciar reintentos en éxito

                    if (data.synced) {
                        updateSyncIndicator(true);
                        syncStatusEl.innerHTML = '<i class="fas fa-globe text-success mr-1"></i>Sincronizado (Uruguay)';
                        syncStatusEl.className = 'text-success';
                        console.log('Hora sincronizada con', data.source, '. Offset:', offsetMs, 'ms');
                    } else {
                        updateSyncIndicator(false);
                        syncStatusEl.innerHTML = '<i class="fas fa-server text-light mr-1"></i>Hora del servidor';
                        syncStatusEl.className = 'text-light';
                        console.log('Usando hora del servidor. Offset:', offsetMs, 'ms');
                    }
                } else {
                    throw new Error('Respuesta no válida del servidor');
                }

            } catch (error) {
                console.warn('Error al sincronizar hora:', error.message);
                syncRetries++;

                if (syncRetries <= MAX_RETRIES) {
                    console.log(`Reintentando sincronización (${syncRetries}/${MAX_RETRIES}) en ${RETRY_DELAY}ms...`);
                    setTimeout(syncWithInternet, RETRY_DELAY);

                    // Mostrar estado de reintento en la UI
                    updateSyncIndicator(false, true);
                    syncStatusEl.innerHTML = `<i class="fas fa-sync fa-spin text-warning mr-1"></i>Reintentando sincronización (${syncRetries})...`;
                    syncStatusEl.className = 'text-warning';
                } else {
                    console.error('Se alcanzó el máximo de reintentos (50). Usando hora local.');
                    // Mantener el offset anterior si existe, sino usar hora local
                    if (lastSyncTime === null) {
                        isSynced = false;
                        offsetMs = 0;
                        updateSyncIndicator(false, true);
                        syncStatusEl.innerHTML = '<i class="fas fa-exclamation-triangle text-danger mr-1"></i>Fallo total de sincronización - Hora local';
                        syncStatusEl.className = 'text-danger';
                    }
                }
            }
        }

        // Iniciar sincronización y reloj
        syncWithInternet();

        // Actualizar el reloj cada segundo
        setInterval(updateClock, 1000);

        // Resincronizar periódicamente cada 5 minutos
        setInterval(function() {
            if (syncRetries === 0) { // Solo resincronizar si no estamos en medio de un ciclo de reintentos
                syncWithInternet();
            }
        }, SYNC_INTERVAL);

        // Actualizar inmediatamente
        updateClock();
    })();

    // Detectar tema claro/oscuro y aplicar clase al panel
    (function() {
        const darkThemes = ['darkly', 'slate', 'cyborg', 'materia'];
        const panelContainer = document.getElementById('panel-container');

        function applyThemeClass() {
            const themeName = localStorage.getItem('bootswatch-theme-name') || '';
            const isDark = darkThemes.includes(themeName);

            if (panelContainer) {
                if (isDark) {
                    panelContainer.classList.add('dark-theme');
                } else {
                    panelContainer.classList.remove('dark-theme');
                }
            }
        }

        // Aplicar al cargar
        applyThemeClass();

        // Observar cambios en el tema (cuando el usuario cambia de tema)
        const originalSetItem = localStorage.setItem;
        localStorage.setItem = function(key, value) {
            originalSetItem.apply(this, arguments);
            if (key === 'bootswatch-theme-name') {
                applyThemeClass();
            }
        };
    })();
</script>
@endsection