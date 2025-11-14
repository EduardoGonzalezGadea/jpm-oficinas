@extends('layouts.app')

@section('title', 'Acceso No Autorizado')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-lock"></i> Acceso No Autorizado
                    </h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-user-times fa-4x text-danger"></i>
                    </div>
                    <h5 class="card-title">No tiene permisos para acceder a esta página</h5>
                    <p class="card-text">
                        Debe iniciar sesión para continuar con esta acción.
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt mr-2"></i>Iniciar Sesión
                        </a>
                        <a href="{{ route('panel.index') }}" class="btn btn-secondary btn-lg ml-2">
                            <i class="fas fa-home mr-2"></i>Ir al Inicio
                        </a>
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
