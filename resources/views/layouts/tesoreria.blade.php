{{-- resources/views/layouts/tesoreria.blade.php --}}
@extends('layouts.app')

@section('titulo', 'Tesorería')

@section('contenido')
<div class="container-fluid">
    <div class="row">
        {{-- Sidebar de navegación --}}
        <div class="col-md-2 sidebar py-3">
            <div class="sidebar-sticky">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-2 mb-3 text-muted">
                    <span>MÓDULO TESORERÍA</span>
                </h6>

                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('tesoreria.valores.index') }}">
                            <i class="fas fa-receipt me-2"></i>Control de Valores
                        </a>
                    </li>
                </ul>

                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>VALORES</span>
                </h6>

                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('tesoreria.valores.index') ? 'active' : '' }}"
                           href="{{ route('tesoreria.valores.index') }}">
                            <i class="fas fa-list me-2"></i>Listado de Valores
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('tesoreria.valores.conceptos') ? 'active' : '' }}"
                           href="{{ route('tesoreria.valores.conceptos') }}">
                            <i class="fas fa-tags me-2"></i>Conceptos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('tesoreria.valores.entradas') ? 'active' : '' }}"
                           href="{{ route('tesoreria.valores.entradas') }}">
                            <i class="fas fa-arrow-down me-2 text-success"></i>Entradas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('tesoreria.valores.salidas') ? 'active' : '' }}"
                           href="{{ route('tesoreria.valores.salidas') }}">
                            <i class="fas fa-arrow-up me-2 text-danger"></i>Salidas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('tesoreria.valores.stock') ? 'active' : '' }}"
                           href="{{ route('tesoreria.valores.stock') }}">
                            <i class="fas fa-chart-bar me-2 text-info"></i>Resumen de Stock
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Contenido principal --}}
        <main role="main" class="col-md-10 ms-sm-auto px-4">
            {{-- Breadcrumb --}}
            <nav aria-label="breadcrumb" class="mt-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('panel') }}">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="#">Tesorería</a>
                    </li>
                    @yield('breadcrumb')
                </ol>
            </nav>

            {{-- Alertas --}}
            <div id="alert-container"></div>

            {{-- Contenido de la página --}}
            @yield('page-content')
        </main>
    </div>
</div>

{{-- Scripts específicos de Tesorería --}}
@push('scripts')
<script>
// Escuchar eventos de alerta de Livewire
document.addEventListener('livewire:load', function () {
    Livewire.on('alert', function (data) {
        showAlert(data.type, data.message);
    });
});

// Función para mostrar alertas
function showAlert(type, message) {
    const alertContainer = document.getElementById('alert-container');
    const alertId = 'alert-' + Date.now();

    const alertHtml = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="fas fa-${getAlertIcon(type)} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    alertContainer.innerHTML = alertHtml;

    // Auto-ocultar después de 5 segundos
    setTimeout(function() {
        const alert = document.getElementById(alertId);
        if (alert) {
            alert.remove();
        }
    }, 5000);
}

function getAlertIcon(type) {
    switch(type) {
        case 'success': return 'check-circle';
        case 'error':
        case 'danger': return 'exclamation-triangle';
        case 'warning': return 'exclamation-triangle';
        case 'info': return 'info-circle';
        default: return 'info-circle';
    }
}

// Función para formatear números
function formatNumber(number) {
    return new Intl.NumberFormat('es-UY').format(number);
}

// Función para formatear moneda
function formatCurrency(amount) {
    return new Intl.NumberFormat('es-UY', {
        style: 'currency',
        currency: 'UYU'
    }).format(amount);
}
</script>
@endpush

@push('styles')
<style>
.sidebar {
    position: fixed;
    top: 56px; /* Altura del navbar principal */
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 48px 0 0;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
    overflow-y: auto;
}

.sidebar .nav-link {
    color: #333;
    border-radius: 0.25rem;
    margin: 0 0.5rem;
}

.sidebar .nav-link:hover {
    color: #007bff;
    background-color: rgba(0, 123, 255, 0.1);
}

.sidebar .nav-link.active {
    color: #007bff;
    background-color: rgba(0, 123, 255, 0.1);
    font-weight: 500;
}

.sidebar-heading {
    font-size: .75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

main {
    margin-left: 16.66667%; /* Ancho del sidebar */
}

@media (max-width: 767.98px) {
    .sidebar {
        position: static;
        height: auto;
    }

    main {
        margin-left: 0;
    }
}

/* Estilos para tablas responsivas */
.table-responsive {
    border-radius: 0.375rem;
}

/* Estilos para badges de estado */
.badge.bg-success { background-color: #198754 !important; }
.badge.bg-danger { background-color: #dc3545 !important; }
.badge.bg-warning { background-color: #ffc107 !important; color: #000; }
.badge.bg-info { background-color: #0dcaf0 !important; color: #000; }
.badge.bg-secondary { background-color: #6c757d !important; }

/* Estilos para modales grandes */
.modal-xl {
    max-width: 1200px;
}

/* Estilos para progress bars */
.progress {
    border-radius: 0.375rem;
}

/* Animaciones sutiles */
.card {
    transition: box-shadow 0.15s ease-in-out;
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.btn {
    transition: all 0.15s ease-in-out;
}

/* Estilos para alertas */
.alert {
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

/* Estilos para el loading */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(255, 255, 255, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loading-spinner {
    width: 3rem;
    height: 3rem;
}
</style>
@endpush
@endsection
