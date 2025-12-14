@extends('layouts.app')

@section('title', 'Tesorería | Oficinas')

@section('content')
    <div class="container-fluid">

        <!-- 1. Encabezado Moderno y Compacto -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-0">Panel Principal</h1>
                <p class="mb-0 small">
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
                        <i class="fas fa-sync-alt fa-spin text-muted"></i>
                    </span>
                    <div>
                        <span class="small font-weight-bold" id="fecha-hora">
                            <i class="far fa-calendar-alt mr-1"></i>{{ now()->format('d/m/Y') }} - {{ now()->format('H:i:s') }}
                        </span>
                        <br>
                        <small id="sync-status" class="text-muted">Sincronizando...</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Alertas del Sistema (Acordeón Compacto y Contrastado) -->
        @if($alertasStock->isNotEmpty() || $alertasCheques->isNotEmpty() || $pendientesAnteriores->isNotEmpty() || $pagosAnteriores->isNotEmpty())
            <div class="accordion mb-4 shadow-sm" id="alertasAccordion">
                <div class="card border-0">
                    <div class="card-header p-0 bg-warning" id="headingAlertas">
                        <h2 class="mb-0">
                            <button class="btn btn-block text-left d-flex align-items-center justify-content-between text-decoration-none py-2 px-3 collapsed" type="button" data-toggle="collapse" data-target="#collapseAlertas" aria-expanded="false" aria-controls="collapseAlertas" style="color: #FFFFFF !important; text-shadow: 1px 1px 2px rgba(0,0,0,0.7), 0 0 3px rgba(0,0,0,0.5);">
                                <span class="font-weight-bold" style="font-size: 0.9rem;">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>ALERTAS DEL SISTEMA
                                    <span class="badge badge-dark badge-pill ml-2">{{ $alertasStock->count() + $alertasCheques->count() + $pendientesAnteriores->count() + $pagosAnteriores->count() }}</span>
                                </span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </h2>
                    </div>

                    <div id="collapseAlertas" class="collapse" aria-labelledby="headingAlertas" data-parent="#alertasAccordion">
                        <div class="card-body p-0 border-left border-right border-bottom border-warning">
                            <ul class="list-group list-group-flush">
                                @foreach($alertasStock as $alerta)
                                    <li class="list-group-item list-group-item-warning d-flex justify-content-between align-items-center py-1 px-3" style="font-size: 0.85rem;">
                                        <span class=""><i class="fas fa-circle text-danger mr-2" style="font-size: 0.5rem;"></i>{!! $alerta['mensaje'] !!}</span>
                                        <a href="{{ route('tesoreria.valores.index') }}" class="font-weight-bold text-decoration-none">Ver <i class="fas fa-arrow-right ml-1"></i></a>
                                    </li>
                                @endforeach
                                @foreach($alertasCheques as $alerta)
                                    <li class="list-group-item list-group-item-warning d-flex justify-content-between align-items-center py-1 px-3" style="font-size: 0.85rem;">
                                        <span class=""><i class="fas fa-circle text-danger mr-2" style="font-size: 0.5rem;"></i>{!! $alerta['mensaje'] !!}</span>
                                        <a href="{{ route('tesoreria.cheques.index') }}" class="font-weight-bold text-decoration-none">Ver <i class="fas fa-arrow-right ml-1"></i></a>
                                    </li>
                                @endforeach
                                @foreach($pendientesAnteriores as $pendiente)
                                    <li class="list-group-item list-group-item-warning d-flex justify-content-between align-items-center py-1 px-3" style="font-size: 0.85rem;">
                                        <span class=""><i class="fas fa-circle text-warning mr-2" style="font-size: 0.5rem;"></i><strong>Caja Chica:</strong> Pendiente "{{ $pendiente->pendiente }}" ({{ $pendiente->dependencia->dependencia ?? 'S/D' }}) mes anterior.</span>
                                        <a href="{{ route('tesoreria.caja-chica.index') }}" class="font-weight-bold text-decoration-none">Ver <i class="fas fa-arrow-right ml-1"></i></a>
                                    </li>
                                @endforeach
                                @foreach($pagosAnteriores as $pago)
                                    <li class="list-group-item list-group-item-warning d-flex justify-content-between align-items-center py-1 px-3" style="font-size: 0.85rem;">
                                        <span class=""><i class="fas fa-circle text-warning mr-2" style="font-size: 0.5rem;"></i><strong>Caja Chica:</strong> Pago del {{ $pago->fechaEgresoPagos->format('d/m/Y') }} ({{ $pago->acreedor->nombre ?? 'S/D' }}) mes anterior.</span>
                                        <a href="{{ route('tesoreria.caja-chica.index') }}" class="font-weight-bold text-decoration-none">Ver <i class="fas fa-arrow-right ml-1"></i></a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- 3. Accesos Rápidos (Widgets Compactos) -->
        <h6 class="mb-3 font-weight-bold ml-1">Accesos Directos</h6>
        <div class="row">
            @can('operador_tesoreria')
                <!-- Multas -->
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                    <a href="{{ route('tesoreria.multas-transito') }}" class="text-decoration-none">
                        <div class="card shadow-sm h-100 widget-card border-left-info">
                            <div class="card-body p-2 d-flex align-items-center">
                                <div class="mr-3 text-info">
                                    <i class="fas fa-list fa-2x"></i>
                                </div>
                                <div>
                                    <div class="font-weight-bold text-body small">Art. Multas Tránsito</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Arrendamientos -->
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                    <a href="{{ route('tesoreria.arrendamientos.index') }}" class="text-decoration-none">
                        <div class="card shadow-sm h-100 widget-card border-left-info">
                            <div class="card-body p-2 d-flex align-items-center">
                                <div class="mr-3 text-info">
                                    <i class="fas fa-file-signature fa-2x"></i>
                                </div>
                                <div>
                                    <div class="font-weight-bold text-body small">Arrendamientos</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Caja Chica -->
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                    <a href="{{ route('tesoreria.caja-chica.index') }}" class="text-decoration-none">
                        <div class="card shadow-sm h-100 widget-card border-left-info">
                            <div class="card-body p-2 d-flex align-items-center">
                                <div class="mr-3 text-info">
                                    <i class="fas fa-coins fa-2x"></i>
                                </div>
                                <div>
                                    <div class="font-weight-bold text-body small">Caja Chica</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Certificados -->
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                    <a href="{{ route('tesoreria.certificados-residencia.index') }}" class="text-decoration-none">
                        <div class="card shadow-sm h-100 widget-card border-left-info">
                            <div class="card-body p-2 d-flex align-items-center">
                                <div class="mr-3 text-info">
                                    <i class="fas fa-file-alt fa-2x"></i>
                                </div>
                                <div>
                                    <div class="font-weight-bold text-body small">Cert. Residencia</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Cheques -->
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                    <a href="{{ route('tesoreria.cheques.index') }}" class="text-decoration-none">
                        <div class="card shadow-sm h-100 widget-card border-left-info">
                            <div class="card-body p-2 d-flex align-items-center">
                                <div class="mr-3 text-info">
                                    <i class="fas fa-money-check fa-2x"></i>
                                </div>
                                <div>
                                    <div class="font-weight-bold text-body small">Cheques</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Depósito Vehículos -->
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                    <a href="{{ route('tesoreria.deposito-vehiculos.index') }}" class="text-decoration-none">
                        <div class="card shadow-sm h-100 widget-card border-left-info">
                            <div class="card-body p-2 d-flex align-items-center">
                                <div class="mr-3 text-info">
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
                        <div class="card shadow-sm h-100 widget-card border-left-info">
                            <div class="card-body p-2 d-flex align-items-center">
                                <div class="mr-3 text-info">
                                    <i class="fas fa-hand-holding-usd fa-2x"></i>
                                </div>
                                <div>
                                    <div class="font-weight-bold text-body small">Eventuales</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Porte de Armas -->
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                    <a href="{{ route('tesoreria.armas.porte') }}" class="text-decoration-none">
                        <div class="card shadow-sm h-100 widget-card border-left-info">
                            <div class="card-body p-2 d-flex align-items-center">
                                <div class="mr-3 text-info">
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
                        <div class="card shadow-sm h-100 widget-card border-left-info">
                            <div class="card-body p-2 d-flex align-items-center">
                                <div class="mr-3 text-info">
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
                        <div class="card shadow-sm h-100 widget-card border-left-info">
                            <div class="card-body p-2 d-flex align-items-center">
                                <div class="mr-3 text-info">
                                    <i class="fas fa-barcode fa-2x"></i>
                                </div>
                                <div>
                                    <div class="font-weight-bold text-body small">Valores</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endcan

            @can('ver_usuarios')
                <!-- Gestionar Usuarios -->
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                    <a href="{{ route('usuarios.index') }}" class="text-decoration-none">
                        <div class="card shadow-sm h-100 widget-card border-left-success">
                            <div class="card-body p-2 d-flex align-items-center">
                                <div class="mr-3 text-success">
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

            <!-- Mi Perfil -->
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                <a href="{{ route('usuarios.miPerfil') }}" class="text-decoration-none">
                    <div class="card shadow-sm h-100 widget-card border-left-primary">
                        <div class="card-body p-2 d-flex align-items-center">
                            <div class="mr-3 text-primary">
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
                    <span class="d-block mb-1">Creado por el Cabo (P.A.) Eduardo González Gadea (Dirección de Tesorería) &copy; 2025</span>
                    <small>República Oriental del Uruguay | Ministerio del Interior | Jefatura de Policía de Montevideo</small>
                </div>
            </div>
        </footer>

    </div>

    <!-- Estilos Personalizados -->
    <style>
        /* Bordes de colores */
        .border-left-primary { border-left: 4px solid #4e73df !important; }
        .border-left-success { border-left: 4px solid #1cc88a !important; }
        .border-left-info { border-left: 4px solid #36b9cc !important; }
        .border-left-warning { border-left: 4px solid #f6c23e !important; }
        .border-bottom-warning { border-bottom: 4px solid #f6c23e !important; }

        /* Efectos Hover */
        .widget-card { transition: all 0.2s ease; border: 1px solid rgba(0,0,0,.125); }
        .widget-card:hover { transform: translateY(-2px); box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1) !important; border-color: #4e73df; }

        /* Footer */
        .sticky-footer { padding: 2rem 0; flex-shrink: 0; background-color: rgba(255, 255, 255, 0.05); }
    </style>

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
                syncIndicatorEl.innerHTML = '<i class="fas fa-clock text-info" title="Hora local del servidor"></i>';
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

                    if (data.synced) {
                        updateSyncIndicator(true);
                        syncStatusEl.innerHTML = '<i class="fas fa-globe text-success mr-1"></i>Sincronizado (Uruguay)';
                        syncStatusEl.className = 'text-success';
                        console.log('Hora sincronizada con', data.source, '. Offset:', offsetMs, 'ms');
                    } else {
                        updateSyncIndicator(false);
                        syncStatusEl.innerHTML = '<i class="fas fa-server text-info mr-1"></i>Hora del servidor';
                        syncStatusEl.className = 'text-info';
                        console.log('Usando hora del servidor. Offset:', offsetMs, 'ms');
                    }
                } else {
                    throw new Error('Respuesta no válida del servidor');
                }

            } catch (error) {
                console.warn('Error al sincronizar hora:', error.message);

                // Mantener el offset anterior si existe, sino usar hora local
                if (lastSyncTime === null) {
                    isSynced = false;
                    offsetMs = 0;
                    if (error.name === 'AbortError') {
                        updateSyncIndicator(false, true);
                        syncStatusEl.innerHTML = '<i class="fas fa-clock text-warning mr-1"></i>Timeout - Hora local';
                        syncStatusEl.className = 'text-warning';
                    } else {
                        updateSyncIndicator(false, true);
                        syncStatusEl.innerHTML = '<i class="fas fa-server text-muted mr-1"></i>Hora local';
                        syncStatusEl.className = 'text-muted';
                    }
                }
            }
        }

        // Iniciar sincronización y reloj
        syncWithInternet();

        // Actualizar el reloj cada segundo
        setInterval(updateClock, 1000);

        // Resincronizar periódicamente
        setInterval(function() {
            syncWithInternet();
        }, SYNC_INTERVAL);

        // Actualizar inmediatamente
        updateClock();
    })();
    </script>
@endsection
