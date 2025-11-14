@extends('layouts.app')

@section('title', 'Tesorería | Oficinas')

@section('content')
    <div class="container-fluid pl-0 pr-0">

        <!-- Header del Panel -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-10">
                                <h2 class="mb-0">
                                    <i class="fas fa-home"></i>
                                    ¡Bienvenid@, {{ $usuario->nombre }} {{ $usuario->apellido }}!
                                </h2>
                                <p class="mb-0 mt-2">
                                    <i class="fas fa-user-tag mr-1"></i>
                                    Rol:
                                    <strong>{{ ucfirst(str_replace('_', ' ', $usuario->getRoleNames()->first())) }}</strong>
                                    @if ($usuario->modulo)
                                        | <i class="fas fa-building mr-1"></i>
                                        Módulo: <strong>{{ $usuario->modulo->nombre }}</strong>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-2 text-right">
                                <i class="fas fa-building display-3"></i>
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
                            @can('operador_tesoreria')

                                <div class="col-md-4 mb-3">
                                    <a href="{{ route('tesoreria.multas-transito') }}"
                                        class="btn btn-outline-info btn-block btn-lg d-flex flex-column justify-content-center align-items-center btn-quick-access">
                                        <i class="fas fa-list fa-2x d-block mb-2"></i>
                                        Tesorería | Multas Tránsito
                                    </a>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <a href="{{ route('tesoreria.caja-chica.index') }}"
                                        class="btn btn-outline-info btn-block btn-lg d-flex flex-column justify-content-center align-items-center btn-quick-access">
                                        <i class="fas fa-coins fa-2x d-block mb-2"></i>
                                        Tesorería | Caja Chica
                                    </a>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <a href="{{ route('tesoreria.cheques.index') }}"
                                        class="btn btn-outline-info btn-block btn-lg d-flex flex-column justify-content-center align-items-center btn-quick-access">
                                        <i class="fas fa-money-check fa-2x d-block mb-2"></i>
                                        Tesorería | Cheques
                                    </a>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <a href="{{ route('tesoreria.arrendamientos.index') }}"
                                        class="btn btn-outline-info btn-block btn-lg d-flex flex-column justify-content-center align-items-center btn-quick-access">
                                        <i class="fas fa-file-signature fa-2x d-block mb-2"></i>
                                        Tesorería | Arrendamientos
                                    </a>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <a href="{{ route('tesoreria.eventuales.index') }}"
                                        class="btn btn-outline-info btn-block btn-lg d-flex flex-column justify-content-center align-items-center btn-quick-access">
                                        <i class="fas fa-hand-holding-usd fa-2x d-block mb-2"></i>
                                        Tesorería | Eventuales
                                    </a>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <a href="{{ route('tesoreria.certificados-residencia.index') }}"
                                        class="btn btn-outline-info btn-block btn-lg d-flex flex-column justify-content-center align-items-center btn-quick-access">
                                        <i class="fas fa-file-alt fa-2x d-block mb-2"></i>
                                        Tesorería | Cert. Residencia
                                    </a>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <a href="{{ route('tesoreria.valores.index') }}"
                                        class="btn btn-outline-info btn-block btn-lg d-flex flex-column justify-content-center align-items-center btn-quick-access">
                                        <i class="fas fa-barcode fa-2x d-block mb-2"></i>
                                        Tesorería | Valores
                                    </a>
                                </div>

                                @hasrole('administrador')
                                {{-- Acceso directo a Tesorería | Valores eliminado --}}
                                @endhasrole

                                <div class="col-md-4 mb-3">
                                    <a href="{{ route('tesoreria.armas.porte') }}"
                                        class="btn btn-outline-info btn-block btn-lg d-flex flex-column justify-content-center align-items-center btn-quick-access">
                                        <i class="fas fa-shield-alt fa-2x d-block mb-2"></i>
                                        Tesorería | Porte
                                    </a>
                                </div>
                            @endcan



                            @can('ver_usuarios')
                                <div class="col-md-4 mb-3">
                                    <a href="{{ route('usuarios.index') }}"
                                        class="btn btn-outline-success btn-block btn-lg d-flex flex-column justify-content-center align-items-center btn-quick-access">
                                        <i class="fas fa-users fa-2x mb-2"></i> Gestionar Usuarios
                                    </a>
                                </div>
                            @endcan

                            <div class="col-md-4 mb-3">
                                <a href="{{ route('usuarios.miPerfil') }}"
                                    class="btn btn-outline-primary text-black btn-block btn-lg d-flex flex-column justify-content-center align-items-center btn-quick-access">
                                    <i class="fas fa-user-edit fa-2x d-block mb-2"></i>
                                    Mi Perfil
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjetas de Estadísticas -->
        @can('ver_usuarios')
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-bar mr-2"></i>
                                Estadísticas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @hasrole('administrador')
                                    <div class="col-lg-4 col-md-6 mb-3">
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

                                    <div class="col-lg-4 col-md-6 mb-3">
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
                                @endhasrole

                                @can('operador_tesoreria')
                                    <div class="col-lg-4 col-md-6 mb-3">
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
                                @endcan

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

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
                            <div class="col-12 text-center">
                                Creado por el Cabo (P.A.) Eduardo González Gadea (Dirección de Tesorería) en el año 2025
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12 text-center">
                                República Oriental del Uruguay | Ministerio del Interior | Jefatura de Policía de Montevideo
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

        .btn-quick-access {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .btn-quick-access:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
@endsection
