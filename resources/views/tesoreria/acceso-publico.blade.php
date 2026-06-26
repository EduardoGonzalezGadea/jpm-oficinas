@extends('layouts.publico')

@section('title', 'Tesorería | Oficinas - Acceso Público')

@section('content')
<div class="container">
    <div class="row justify-content-center mt-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-globe mr-2"></i>Consultas Públicas</h4>
                </div>
                <div class="card-body">
                    <p class="lead mb-4">
                        Seleccione el tipo de consulta que desea realizar:
                    </p>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-info">
                                <div class="card-body text-center d-flex flex-column">
                                    <div class="mb-3">
                                        <i class="fas fa-list fa-3x text-info"></i>
                                    </div>
                                    <h5 class="card-title">Artículos de Multas CPT</h5>
                                    <p class="card-text text-muted flex-grow-1">
                                        Consulte los artículos de multas de tránsito, montos originales y unificados.
                                    </p>
                                    <a href="{{ route('multas-transito-publico') }}" class="btn btn-info btn-block mt-auto">
                                        <i class="fas fa-list mr-2"></i>Ingresar
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-secondary">
                                <div class="card-body text-center d-flex flex-column">
                                    <div class="mb-3">
                                        <i class="fas fa-list-alt fa-3x text-secondary"></i>
                                    </div>
                                    <h5 class="card-title">Cód. Multas CPT (Dec. 303/2023)</h5>
                                    <p class="card-text text-muted flex-grow-1">
                                        Consulte los códigos de multas actualizados según el Decreto 303/2023.
                                    </p>
                                    <a href="{{ route('multas-303-publico') }}" class="btn btn-secondary btn-block mt-auto">
                                        <i class="fas fa-list-alt mr-2"></i>Ingresar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <a href="{{ url('/') }}" class="btn btn-outline-primary">
                            <i class="fas fa-home mr-2"></i>Volver al Inicio
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
