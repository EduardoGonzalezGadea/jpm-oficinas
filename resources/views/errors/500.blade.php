@extends('layouts.app')

@section('title', 'Sesión Expirada')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-warning">
                <div class="card-header bg-warning text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-clock"></i> Sesión Expirada
                    </h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-hourglass-end fa-4x text-warning"></i>
                    </div>
                    <h5 class="card-title">Su sesión ha expirado</h5>
                    <p class="card-text">
                        Por seguridad, su sesión ha expirado debido a inactividad prolongada.
                        Debe iniciar sesión nuevamente para continuar utilizando el sistema.
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt mr-2"></i>Iniciar Sesión Nuevamente
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
    .alert {
        border-radius: 0.375rem;
    }
</style>
@endpush
