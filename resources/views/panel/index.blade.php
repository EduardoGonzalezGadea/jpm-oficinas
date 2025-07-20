@extends('layouts.app')

@section('titulo', 'Panel Principal - JPM Oficinas')

@section('contenido')
<div class="container-fluid">
    <!-- Header del Panel -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-0">
                                <i class="fas fa-home mr-2"></i>
                                ¡Bienvenido, {{ $usuario->nombre }} {{ $usuario->apellido }}!
                            </h2>
                            <p class="mb-0 mt-2">
                                <i class="fas fa-user-tag mr-1"></i>
                                Rol: <strong>{{ ucfirst(str_replace('_', ' ', $usuario->getRoleNames()->first())) }}</strong>
                                @if($usuario->modulo)
                                    | <i class="fas fa-building mr-1"></i>
                                    Módulo: <strong>{{ $usuario->modulo->nombre }}</strong>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-4 text-right">
                            <i class="fas fa-building display-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjetas de Estadísticas -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Usuarios
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $estadisticas['total_usuarios'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-left-success shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Módulos Activos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $estadisticas['total_modulos'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-puzzle-piece fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-left-info shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Tesorería
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $estadisticas['usuarios_tesoreria'] }} usuarios
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-coins fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Contabilidad
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $estadisticas['usuarios_contabilidad'] }} usuarios
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calculator fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-tachometer-alt mr-2"></i>
                        Accesos Rápidos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @can('gestionar_usuarios')
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('usuarios.index') }}" class="btn btn-outline-primary btn-block btn-lg">
                                <i class="fas fa-users fa-2x d-block mb-2"></i>
                                Gestionar Usuarios
                            </a>
                        </div>
                        @endcan

                        @can('gestionar_tesoreria')
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('tesoreria.index') }}" class="btn btn-outline-info btn-block btn-lg">
                                <i class="fas fa-coins fa-2x d-block mb-2"></i>
                                Módulo Tesorería
                            </a>
                        </div>
                        @endcan

                        @can('gestionar_contabilidad')
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('contabilidad.index') }}" class="btn btn-outline-warning btn-block btn-lg">
                                <i class="fas fa-calculator fa-2x d-block mb-2"></i>
                                Módulo Contabilidad
                            </a>
                        </div>
                        @endcan

                        <div class="col-md-4 mb-3">
                            <a href="{{ route('usuarios.miPerfil') }}" class="btn btn-outline-secondary btn-block btn-lg">
                                <i class="fas fa-user-edit fa-2x d-block mb-2"></i>
                                Mi Perfil
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Información del Sistema -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle mr-2"></i>
                        Información del Sistema
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Sistema:</strong> JPM Oficinas v1.0</p>
                            <p><strong>Framework:</strong> Laravel 9</p>
                            <p><strong>Autenticación:</strong> JWT (JSON Web Token)</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Roles y Permisos:</strong> Spatie Permission</p>
                            <p><strong>Base de Datos:</strong> MySQL</p>
                            <p><strong>Interfaz:</strong> Bootstrap 4</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 text-center">
                            Creado y diseñado por el Cabo (P.A.) Eduardo González Gadea (Dirección de Tesorería) en el año 2025
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary {
    border-left: 4px solid #4e73df !important;
}
.border-left-success {
    border-left: 4px solid #1cc88a !important;
}
.border-left-info {
    border-left: 4px solid #36b9cc !important;
}
.border-left-warning {
    border-left: 4px solid #f6c23e !important;
}
.text-gray-800 {
    color: #5a5c69 !important;
}
.text-gray-300 {
    color: #dddfeb !important;
}
.shadow {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
}
</style>
@endsection