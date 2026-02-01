@extends('layouts.app')

@section('title', 'Tesorería - Módulo en Construcción')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <!-- Card principal de bienvenida -->
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center py-4">
                    <h1 class="h2 mb-0">
                        <i class="fas fa-coins mr-3"></i>
                        Módulo de Tesorería
                    </h1>
                </div>

                <div class="card-body text-center py-5">
                    <!-- Icono de construcción -->
                    <div class="mb-4">
                        <i class="fas fa-tools text-warning" style="font-size: 4rem;"></i>
                    </div>

                    <!-- Mensaje principal -->
                    <h2 class="text-primary mb-3">¡Módulo en Construcción!</h2>

                    <p class="lead text-muted mb-4">
                        Estamos trabajando arduamente para traerle las mejores funcionalidades
                        para la gestión financiera de la institución.
                    </p>

                    <div class="row mt-5">
                        <div class="col-md-3 mb-4">
                            <div class="card h-100 border-success shadow-sm hover-elevate">
                                <div class="card-body">
                                    <i class="fas fa-coins text-success mb-3" style="font-size: 2rem;"></i>
                                    <h5 class="card-title">Caja Chica</h5>
                                    <p class="card-text text-muted small">
                                        Administración eficiente de gastos menores y rendiciones.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-4">
                            <div class="card h-100 border-info shadow-sm hover-elevate">
                                <div class="card-body">
                                    <i class="fas fa-car text-info mb-3" style="font-size: 2rem;"></i>
                                    <h5 class="card-title">Infracciones</h5>
                                    <p class="card-text text-muted small">
                                        Artículos y montos de multas de tránsito.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-4">
                            <div class="card h-100 border-warning shadow-sm hover-elevate">
                                <div class="card-body">
                                    <i class="fas fa-file-signature text-warning mb-3" style="font-size: 2rem;"></i>
                                    <h5 class="card-title">Contratos</h5>
                                    <p class="card-text text-muted small">
                                        Control de arrendamientos y servicios eventuales.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-4">
                            <div class="card h-100 border-danger shadow-sm hover-elevate">
                                <div class="card-body">
                                    <i class="fas fa-receipt text-danger mb-3" style="font-size: 2rem;"></i>
                                    <h5 class="card-title">Multas Cobradas</h5>
                                    <p class="card-text text-muted small">
                                        Registro y gestión de multas de tránsito cobradas.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Barra de progreso -->
                    <div class="mt-5">
                        <h5 class="mb-3">Progreso del Desarrollo</h5>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                                role="progressbar"
                                style="width: 65%"
                                aria-valuenow="65"
                                aria-valuemin="0"
                                aria-valuemax="100">
                                65% Completado
                            </div>
                        </div>
                        <small class="text-muted mt-2 d-block">
                            Estimación de finalización: Próximas semanas
                        </small>
                    </div>

                    <!-- Botones de acción -->
                    <div class="mt-5">
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <a href="{{ route('panel') }}" class="btn btn-outline-primary btn-lg btn-block mb-3">
                                    <i class="fas fa-arrow-left mr-2"></i>
                                    Volver al Panel Principal
                                </a>
                            </div>
                        </div>

                        <!-- Contacto para soporte -->
                        <div class="mt-4 pt-4 border-top">
                            <p class="text-muted mb-2">
                                <i class="fas fa-envelope mr-2"></i>
                                ¿Necesitas ayuda o tienes sugerencias?
                            </p>
                            <small class="text-muted">
                                Contacta al equipo de desarrollo para más información sobre el progreso del módulo.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card {
        border-radius: 15px;
        overflow: hidden;
    }

    .card-header {
        background: linear-gradient(135deg, #007bff, #0056b3) !important;
        border: none;
    }

    .progress-bar {
        font-weight: 600;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
    }

    .btn-outline-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
        transition: all 0.3s ease;
    }

    .border-primary {
        border-color: #007bff !important;
    }

    .border-success {
        border-color: #28a745 !important;
    }

    .border-info {
        border-color: #17a2b8 !important;
    }
</style>
@endpush