@extends('layouts.app')

@section('title', 'Sesión Expirada')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-clock"></i> Sesión Expirada
                    </h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-history fa-4x text-info"></i>
                    </div>
                    <h5 class="card-title">La página ha expirado</h5>
                    <p class="card-text">
                        Debido a la inactividad, su sesión ha expirado por razones de seguridad.
                        Por favor, intente recargar la página o volver al panel principal.
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('panel') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-home mr-2"></i>Ir al Panel Principal
                        </a>
                        <button onclick="location.reload()" class="btn btn-info btn-lg ml-2 text-white">
                            <i class="fas fa-sync-alt mr-2"></i>Recargar Página
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    body {
        background-color: #f8f9fa;
    }

    .card {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
</style>
@endpush