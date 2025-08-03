{{-- resources/views/layouts/tesoreria.blade.php --}}
@extends('layouts.app')

@section('titulo', 'Tesorería')

@section('contenido')
<div class="container-fluid p-0">
    <div class="d-flex tesoreria-layout">
        {{-- Sidebar de navegación --}}
        <div class="sidebar-container bg-body-tertiary border-end" id="sidebarContainer">
            <div class="sidebar py-3" id="sidebarMenu">
                <div class="position-sticky pt-3">
                    <div class="d-flex justify-content-between align-items-center px-3 mt-2 mb-3">
                        <h6 class="sidebar-heading text-body-secondary mb-0 text-uppercase fw-semibold">
                            <span class="sidebar-text">MÓDULO TESORERÍA</span>
                        </h6>
                        <button id="sidebarToggle" class="btn btn-outline-secondary btn-sm border-0" type="button" aria-label="Toggle sidebar">
                            <i class="fas fa-bars"></i>
                        </button>
                    </div>

                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-body-emphasis text-decoration-none" href="{{ route('tesoreria.valores.index') }}">
                                <i class="fas fa-receipt me-2"></i>
                                <span class="sidebar-text">Control de Valores</span>
                            </a>
                        </li>
                    </ul>

                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-body-secondary text-uppercase fw-semibold">
                        <span class="sidebar-text">VALORES</span>
                    </h6>

                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link text-body-emphasis text-decoration-none {{ request()->routeIs('tesoreria.valores.index') ? 'active' : '' }}"
                               href="{{ route('tesoreria.valores.index') }}">
                                <i class="fas fa-list me-2"></i>
                                <span class="sidebar-text">Listado de Valores</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-body-emphasis text-decoration-none {{ request()->routeIs('tesoreria.valores.conceptos') ? 'active' : '' }}"
                               href="{{ route('tesoreria.valores.conceptos') }}">
                                <i class="fas fa-tags me-2"></i>
                                <span class="sidebar-text">Conceptos</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-body-emphasis text-decoration-none {{ request()->routeIs('tesoreria.valores.entradas') ? 'active' : '' }}"
                               href="{{ route('tesoreria.valores.entradas') }}">
                                <i class="fas fa-arrow-down me-2 text-success"></i>
                                <span class="sidebar-text">Entradas</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-body-emphasis text-decoration-none {{ request()->routeIs('tesoreria.valores.salidas') ? 'active' : '' }}"
                               href="{{ route('tesoreria.valores.salidas') }}">
                                <i class="fas fa-arrow-up me-2 text-danger"></i>
                                <span class="sidebar-text">Salidas</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-body-emphasis text-decoration-none {{ request()->routeIs('tesoreria.valores.stock') ? 'active' : '' }}"
                               href="{{ route('tesoreria.valores.stock') }}">
                                <i class="fas fa-chart-bar me-2 text-info"></i>
                                <span class="sidebar-text">Resumen de Stock</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Contenido principal --}}
        <main class="main-content flex-grow-1 bg-body" id="mainContent">
            <div class="main-content-inner px-4">
                {{-- Breadcrumb --}}
                <nav aria-label="breadcrumb" class="mt-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('panel') }}" class="text-decoration-none">Inicio</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="#" class="text-decoration-none">Tesorería</a>
                        </li>
                        @yield('breadcrumb')
                    </ol>
                </nav>

                {{-- Alertas --}}
                <div id="alert-container"></div>

                {{-- Contenido de la página --}}
                @yield('page-content')
            </div>
        </main>
    </div>
</div>

{{-- Scripts específicos de Tesorería --}}
<script>
console.log('=== SCRIPT DE TESORERÍA CARGADO ===');

// Variables globales ultra-simples
window.TESORERIA_SIDEBAR = {
    initialized: false
};

// Función toggle ULTRA SIMPLE con debugging completo
function toggleSidebar() {
    console.log('🔄 toggleSidebar() EJECUTADO');

    const container = document.getElementById('sidebarContainer');
    console.log('📦 Container encontrado:', !!container);

    if (!container) {
        console.error('❌ NO SE ENCONTRÓ sidebarContainer');
        return;
    }

    const currentClasses = container.className;
    console.log('📋 Clases actuales:', currentClasses);

    const wasCollapsed = container.classList.contains('collapsed');
    console.log('📊 Estado inicial - collapsed:', wasCollapsed);

    // TOGGLE DIRECTO
    if (wasCollapsed) {
        container.classList.remove('collapsed');
        console.log('✅ REMOVIENDO clase collapsed');
    } else {
        container.classList.add('collapsed');
        console.log('✅ AGREGANDO clase collapsed');
    }

    const newClasses = container.className;
    console.log('📋 Clases después:', newClasses);

    const isNowCollapsed = container.classList.contains('collapsed');
    console.log('📊 Estado final - collapsed:', isNowCollapsed);

    // Guardar en localStorage
    localStorage.setItem('sidebarCollapsed', isNowCollapsed.toString());
    console.log('💾 Guardado en localStorage:', isNowCollapsed);

    // Cambiar ícono
    updateSidebarIcon(isNowCollapsed);
    console.log('🎯 Ícono actualizado');

    // Actualizar tooltips
    setupTooltips();
    console.log('🏷️ Tooltips actualizados');

    console.log('✅ toggleSidebar() COMPLETADO');
}

// Función para actualizar el ícono
function updateSidebarIcon(isCollapsed) {
    const toggleBtn = document.getElementById('sidebarToggle');
    if (!toggleBtn) return;

    const icon = toggleBtn.querySelector('i');
    if (!icon) return;

    icon.className = isCollapsed ? 'fas fa-chevron-right' : 'fas fa-bars';
    console.log('Ícono actualizado a:', isCollapsed ? 'chevron-right' : 'bars');
}

// Función para aplicar estado inicial SIN animaciones
function applySidebarState() {
    const savedState = localStorage.getItem('sidebarCollapsed');
    const shouldCollapse = savedState === 'true';

    console.log('Aplicando estado inicial:', shouldCollapse ? 'COLAPSADO' : 'EXPANDIDO');

    const container = document.getElementById('sidebarContainer');
    if (!container) {
        console.error('No se encontró el contenedor del sidebar');
        return;
    }

    // Deshabilitar transiciones temporalmente
    container.style.transition = 'none';

    // Aplicar estado
    if (shouldCollapse) {
        container.classList.add('collapsed');
        updateSidebarIcon(true);
    } else {
        container.classList.remove('collapsed');
        updateSidebarIcon(false);
        if (savedState === null) {
            localStorage.setItem('sidebarCollapsed', 'false');
        }
    }

    // Configurar tooltips
    setupTooltips();

    // Reactivar transiciones
    setTimeout(() => {
        container.style.transition = '';
    }, 100);
}

// Función para configurar tooltips simples con title nativo
function setupTooltips() {
    console.log('🏷️ setupTooltips() - usando title nativo');

    const container = document.getElementById('sidebarContainer');
    const isCollapsed = container && container.classList.contains('collapsed');

    console.log('📊 Sidebar collapsed:', isCollapsed);

    // Obtener todos los enlaces del sidebar
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    console.log('🔗 Enlaces encontrados:', navLinks.length);

    navLinks.forEach((link, index) => {
        const textElement = link.querySelector('.sidebar-text');
        if (!textElement) {
            console.log(`⚠️ Link ${index}: No tiene .sidebar-text`);
            return;
        }

        const text = textElement.textContent.trim();
        console.log(`📝 Link ${index}: "${text}"`);

        if (isCollapsed) {
            // Solo agregar title nativo cuando está colapsado
            link.setAttribute('title', text);
            console.log(`✅ Title nativo agregado a "${text}"`);
        } else {
            // Remover title cuando está expandido
            link.removeAttribute('title');
            console.log(`🗑️ Title removido de "${text}"`);
        }
    });

    console.log('🏷️ setupTooltips() completado - solo title nativo');
}

// Configuración cuando el DOM esté listo
function initSidebar() {
    console.log('=== INIT SIDEBAR ===');
    console.log('URL actual:', window.location.href);

    // Verificar si ya se inicializó
    if (window.TESORERIA_SIDEBAR.initialized) {
        console.log('Sidebar ya inicializado, saltando...');
        return;
    }

    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarContainer = document.getElementById('sidebarContainer');

    console.log('sidebarToggle element:', sidebarToggle);
    console.log('sidebarContainer element:', sidebarContainer);

    if (!sidebarToggle) {
        console.error('CRÍTICO: No se encontró el botón sidebarToggle');
        return;
    }

    if (!sidebarContainer) {
        console.error('CRÍTICO: No se encontró el contenedor del sidebar');
        return;
    }

    // Marcar como inicializado ANTES de aplicar el estado
    window.TESORERIA_SIDEBAR.initialized = true;

    // Aplicar estado guardado SIN animaciones
    applySidebarState();

    // Configurar tooltips inicial
    setupTooltips();

    // Limpiar cualquier event listener previo
    const oldHandler = window.TESORERIA_SIDEBAR.toggleHandler;
    if (oldHandler) {
        sidebarToggle.removeEventListener('click', oldHandler);
    }

    // Crear nuevo handler y guardarlo
    const newHandler = function(e) {
        console.log('=== CLICK DETECTADO ===');
        e.preventDefault();
        e.stopPropagation();
        toggleSidebar();
    };

    window.TESORERIA_SIDEBAR.toggleHandler = newHandler;

    // Agregar event listener
    sidebarToggle.addEventListener('click', newHandler);

    console.log('✅ Sidebar configurado correctamente');
}

// Ejecutar inicialización con debugging
console.log('📋 Script cargado, estado del DOM:', document.readyState);

if (document.readyState === 'loading') {
    console.log('⏳ DOM cargando, esperando DOMContentLoaded...');
    document.addEventListener('DOMContentLoaded', function() {
        console.log('✅ DOMContentLoaded disparado');
        initSidebar();
    });
} else {
    console.log('✅ DOM ya listo, ejecutando inmediatamente');
    initSidebar();
}

// Backup con timeout para debugging
setTimeout(function() {
    console.log('⏰ Timeout backup ejecutado');
    console.log('📊 Estado de inicialización:', window.TESORERIA_SIDEBAR.initialized);

    if (!window.TESORERIA_SIDEBAR.initialized) {
        console.log('⚠️ No estaba inicializado, forzando inicialización...');
        initSidebar();
    } else {
        console.log('✅ Ya estaba inicializado correctamente');

        // Verificar que el botón tiene onclick
        const btn = document.getElementById('sidebarToggle');
        console.log('🔍 Botón onclick:', typeof btn?.onclick);
    }
}, 1000);

// Funciones adicionales para alertas
function showAlert(type, message) {
    const alertContainer = document.getElementById('alert-container');
    if (!alertContainer) return;

    const alertId = 'alert-' + Date.now();
    const alertHtml = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="fas fa-${getAlertIcon(type)} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    alertContainer.innerHTML = alertHtml;

    setTimeout(() => {
        const alert = document.getElementById(alertId);
        if (alert) alert.remove();
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

// Configurar Livewire si existe
if (typeof Livewire !== 'undefined') {
    document.addEventListener('livewire:load', () => {
        Livewire.on('alert', (data) => {
            showAlert(data.type, data.message);
        });
    });
}

// Funciones de formateo
window.formatNumber = function(number) {
    return new Intl.NumberFormat('es-UY').format(number);
}

window.formatCurrency = function(amount) {
    return new Intl.NumberFormat('es-UY', {
        style: 'currency',
        currency: 'UYU'
    }).format(amount);
}

// Función de test global SIMPLE
window.testSidebarToggle = function() {
    console.log('🧪 TEST MANUAL EJECUTADO');
    console.log('📊 Estado TESORERIA_SIDEBAR:', window.TESORERIA_SIDEBAR);

    const btn = document.getElementById('sidebarToggle');
    console.log('🔍 Botón encontrado:', !!btn);
    console.log('🔍 Onclick asignado:', typeof btn?.onclick);

    if (btn && typeof btn.onclick === 'function') {
        console.log('✅ Simulando click...');
        btn.onclick();
    } else {
        console.log('❌ No se puede simular click - onclick no es función');
        console.log('🔄 Intentando llamar toggleSidebar directamente...');
        toggleSidebar();
    }
};
</script>

{{-- Estilos específicos de Tesorería --}}
<style>
/* USANDO VARIABLES CSS DE BOOTSTRAP PARA COHERENCIA TOTAL */

/* PREVENCIÓN DE PARPADEO EN NAVEGACIÓN */
.tesoreria-layout {
    min-height: calc(100vh - 56px);
    position: relative;
}

.tesoreria-layout.loading,
.tesoreria-layout.loading * {
    transition: none !important;
    animation: none !important;
}

/* CONTENEDOR DEL SIDEBAR - USANDO VARIABLES DE BOOTSTRAP */
.sidebar-container {
    width: 250px;
    flex-shrink: 0;
    transition: width 0.3s ease;
    position: relative;
    /* Colores usando variables de Bootstrap */
    background-color: var(--bs-tertiary-bg);
    border-right: var(--bs-border-width) solid var(--bs-border-color);
}

.sidebar-container.collapsed {
    width: 60px;
}

/* SIDEBAR INTERNO */
.sidebar {
    height: 100%;
    overflow-y: auto;
    padding: 1rem 0;
    position: relative;
}

/* CONTENIDO PRINCIPAL - USANDO VARIABLES DE BOOTSTRAP */
.main-content {
    background-color: var(--bs-body-bg);
    color: var(--bs-body-color);
    min-height: calc(100vh - 56px);
    overflow-x: auto;
}

.main-content-inner {
    min-width: 320px;
}

/* OCULTAR TEXTO EN ESTADO COLAPSADO */
.sidebar-container.collapsed .sidebar-text {
    display: none;
}

.sidebar-text {
    margin-left: 0.5em;
}

.sidebar-container.collapsed .sidebar .nav-link {
    justify-content: center;
    padding: 0.75rem 0.5rem;
}

.sidebar-container.collapsed .sidebar-heading {
    text-align: center;
    padding: 0 0.5rem;
}

.sidebar-container.collapsed .sidebar-heading span {
    display: none;
}

/* ESTILOS DE LOS ENLACES - USANDO VARIABLES DE BOOTSTRAP */
.sidebar .nav-link {
    color: var(--bs-body-emphasis);
    padding: 0.75rem 1rem;
    margin: 0.25rem 0.5rem;
    border-radius: var(--bs-border-radius);
    display: flex;
    align-items: center;
    text-decoration: none;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.sidebar .nav-link:hover {
    background-color: var(--bs-secondary-bg);
    color: var(--bs-primary);
    text-decoration: none;
}

.sidebar .nav-link.active {
    background-color: var(--bs-primary);
    color: var(--bs-white);
    border-color: var(--bs-primary);
}

.sidebar .nav-link i {
    flex-shrink: 0;
    width: 20px;
    text-align: center;
    margin-right: 0.75rem;
}

.sidebar-container.collapsed .sidebar .nav-link i {
    margin-right: 0;
}

/* HEADING DEL SIDEBAR - USANDO VARIABLES DE BOOTSTRAP */
.sidebar-heading {
    font-size: 0.75rem;
    font-weight: var(--bs-font-weight-semibold);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--bs-secondary);
    margin-bottom: 0.5rem;
}

/* BOTÓN DE TOGGLE - USANDO VARIABLES DE BOOTSTRAP */
#sidebarToggle {
    border: var(--bs-border-width) solid transparent;
    background: transparent;
    padding: 0.375rem 0.75rem;
    color: var(--bs-secondary);
    transition: all 0.2s ease;
    flex-shrink: 0;
    border-radius: var(--bs-border-radius-sm);
}

#sidebarToggle:hover {
    background-color: var(--bs-secondary-bg);
    border-color: var(--bs-border-color);
    color: var(--bs-secondary);
}

#sidebarToggle:focus {
    box-shadow: 0 0 0 var(--bs-focus-ring-width) var(--bs-focus-ring-color);
}

.sidebar-container.collapsed #sidebarToggle {
    margin: 0 auto;
}

/* RESPONSIVE PARA TABLETS */
@media (max-width: 992px) {
    .sidebar-container {
        width: 200px;
    }

    .sidebar-container.collapsed {
        width: 50px;
    }
}

/* RESPONSIVE PARA MÓVILES */
@media (max-width: 768px) {
    .tesoreria-layout {
        flex-direction: column;
    }

    .sidebar-container {
        width: 100% !important;
        height: auto;
        border-right: none;
        border-bottom: var(--bs-border-width) solid var(--bs-border-color);
        position: static;
    }

    .sidebar-container.collapsed {
        height: 60px;
        overflow: hidden;
    }

    .sidebar-container.collapsed .sidebar {
        height: 60px;
    }

    .main-content {
        min-height: auto;
    }

    .sidebar-container.collapsed .nav-item:not(:first-child) {
        display: none;
    }

    .sidebar-container.collapsed .sidebar-heading:not(:first-child) {
        display: none;
    }
}

/* COMPONENTES ADICIONALES - USANDO VARIABLES DE BOOTSTRAP */
.table-responsive {
    border-radius: var(--bs-border-radius);
    box-shadow: var(--bs-box-shadow-sm);
}

/* Badges usando variables de Bootstrap */
.badge.bg-success {
    background-color: var(--bs-success) !important;
    color: var(--bs-white) !important;
}
.badge.bg-danger {
    background-color: var(--bs-danger) !important;
    color: var(--bs-white) !important;
}
.badge.bg-warning {
    background-color: var(--bs-warning) !important;
    color: var(--bs-dark) !important;
}
.badge.bg-info {
    background-color: var(--bs-info) !important;
    color: var(--bs-white) !important;
}
.badge.bg-secondary {
    background-color: var(--bs-secondary) !important;
    color: var(--bs-white) !important;
}

.modal-xl {
    max-width: 1200px;
}

.progress {
    border-radius: var(--bs-border-radius);
}

/* Cards usando variables de Bootstrap */
.card {
    transition: box-shadow 0.15s ease-in-out;
    border: var(--bs-border-width) solid var(--bs-card-border-color);
    background-color: var(--bs-card-bg);
    color: var(--bs-card-color);
}

.card:hover {
    box-shadow: var(--bs-box-shadow);
}

.btn {
    transition: all 0.15s ease-in-out;
}

/* Alertas usando variables de Bootstrap */
.alert {
    border: none;
    border-radius: var(--bs-border-radius-lg);
    box-shadow: var(--bs-box-shadow-sm);
}

/* Loading overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: var(--bs-body-bg);
    background-color: rgba(var(--bs-body-bg-rgb), 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loading-spinner {
    width: 3rem;
    height: 3rem;
    border: 4px solid var(--bs-border-color);
    border-top: 4px solid var(--bs-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* BREADCRUMB - USANDO VARIABLES DE BOOTSTRAP */
.breadcrumb {
    background-color: transparent;
    margin-bottom: 1.5rem;
}

.breadcrumb-item + .breadcrumb-item::before {
    color: var(--bs-secondary);
}

.breadcrumb-item a {
    color: var(--bs-primary);
}

.breadcrumb-item.active {
    color: var(--bs-secondary);
}

/* SCROLLBAR PERSONALIZADO USANDO VARIABLES DE BOOTSTRAP */
.sidebar::-webkit-scrollbar {
    width: 4px;
}

.sidebar::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar::-webkit-scrollbar-thumb {
    background: var(--bs-border-color);
    border-radius: 2px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: var(--bs-secondary);
}

/* TOOLTIPS PERSONALIZADOS USANDO VARIABLES DE BOOTSTRAP */
.sidebar-container.collapsed .nav-link[title] {
    position: relative;
}

.sidebar-container.collapsed .nav-link[title]:hover::after {
    content: attr(title);
    position: absolute;
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    margin-left: 10px;
    padding: 0.5rem 0.75rem;
    background-color: var(--bs-dark);
    color: var(--bs-white);
    border-radius: var(--bs-border-radius);
    font-size: 0.875rem;
    white-space: nowrap;
    z-index: 1070;
    box-shadow: var(--bs-box-shadow);
    pointer-events: none;
    opacity: 0;
    animation: fadeInTooltip 0.2s ease-in-out 0.3s forwards;
}

.sidebar-container.collapsed .nav-link[title]:hover::before {
    content: '';
    position: absolute;
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    margin-left: 4px;
    width: 0;
    height: 0;
    border-top: 6px solid transparent;
    border-bottom: 6px solid transparent;
    border-right: 6px solid var(--bs-dark);
    z-index: 1069;
    pointer-events: none;
    opacity: 0;
    animation: fadeInTooltip 0.2s ease-in-out 0.3s forwards;
}

@keyframes fadeInTooltip {
    from {
        opacity: 0;
        transform: translateY(-50%) translateX(-5px);
    }
    to {
        opacity: 1;
        transform: translateY(-50%) translateX(0);
    }
}

/* ASEGURAR COHERENCIA CON TEMAS OSCUROS */
[data-bs-theme="dark"] .sidebar-container {
    background-color: var(--bs-tertiary-bg);
    border-color: var(--bs-border-color);
}

[data-bs-theme="dark"] .sidebar .nav-link {
    color: var(--bs-body-emphasis);
}

[data-bs-theme="dark"] .sidebar .nav-link:hover {
    background-color: var(--bs-secondary-bg);
    color: var(--bs-primary);
}

[data-bs-theme="dark"] .sidebar .nav-link.active {
    background-color: var(--bs-primary);
    color: var(--bs-white);
}

[data-bs-theme="dark"] #sidebarToggle {
    color: var(--bs-secondary);
}

[data-bs-theme="dark"] #sidebarToggle:hover {
    background-color: var(--bs-secondary-bg);
    color: var(--bs-secondary);
}

[data-bs-theme="dark"] .sidebar-heading {
    color: var(--bs-secondary);
}
</style>
@endsection
