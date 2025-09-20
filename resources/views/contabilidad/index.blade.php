@extends('layouts.app')

@section('title', 'Contabilidad - Módulo en Construcción')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <!-- Card principal de bienvenida -->
            <div class="card shadow-lg border-0">
                <div class="card-header bg-success text-white text-center py-4">
                    <h1 class="h2 mb-0">
                        <i class="fas fa-link mr-3"></i>
                        Integración Contable
                    </h1>
                </div>

                <div class="card-body text-center py-5">
                    <!-- Icono de construcción -->
                    <div class="mb-4">
                        <i class="fas fa-tools text-warning" style="font-size: 4rem;"></i>
                    </div>

                    <!-- Mensaje principal -->
                    <h2 class="text-success mb-3">¡Funciones de Integración en Desarrollo!</h2>

                    <p class="lead text-muted mb-4">
                        Estamos desarrollando funciones contables específicas que servirán de enlace
                        y soporte para el módulo de Tesorería, facilitando la integración de datos financieros.
                    </p>

                    <!-- Función principal en desarrollo -->
                    <div class="row mt-5 justify-content-center">
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 border-danger shadow">
                                <div class="card-body text-center">
                                    <i class="fas fa-chart-bar text-danger mb-3" style="font-size: 3rem;"></i>
                                    <h4 class="card-title text-danger">Reportes Integrados</h4>
                                    <p class="card-text text-muted lead">
                                        Informes combinados que unen datos de tesorería y contabilidad
                                        para una visión completa del estado financiero.
                                    </p>
                                    <div class="mt-3">
                                        <span class="badge badge-danger badge-pill p-2">
                                            <i class="fas fa-clock mr-1"></i>
                                            Próximamente
                                        </span>
                                    </div>
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
                                 style="width: 0%"
                                 aria-valuenow="0"
                                 aria-valuemin="0"
                                 aria-valuemax="100">
                                0% Completado
                            </div>
                        </div>
                        <small class="text-muted mt-2 d-block">
                            Estimación de finalización: 1 año
                        </small>
                    </div>

                    <!-- Botones de acción -->
                    <div class="mt-5">
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <a href="{{ route('panel') }}" class="btn btn-outline-success btn-lg btn-block mb-3">
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
                                Contacta al equipo de desarrollo para más información sobre las funciones de integración contable.
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
        background: linear-gradient(135deg, #28a745, #20c997) !important;
        border: none;
    }

    .progress-bar {
        font-weight: 600;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    }

    .btn-outline-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        transition: all 0.3s ease;
    }

    .border-success {
        border-color: #28a745 !important;
    }

    .border-primary {
        border-color: #007bff !important;
    }

    .border-info {
        border-color: #17a2b8 !important;
    }

    .border-warning {
        border-color: #ffc107 !important;
    }

    .border-danger {
        border-color: #dc3545 !important;
    }
</style>
@endpush
