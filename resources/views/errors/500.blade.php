@extends('layouts.app')

@section('title', 'Error Interno del Servidor')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-exclamation-circle"></i> Error Interno del Servidor
                    </h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-server fa-4x text-danger"></i>
                    </div>
                    <h5 class="card-title">Algo salió mal en nuestro servidor</h5>
                    <p class="card-text">
                        Ha ocurrido un error inesperado. Por favor, diríjase al inicio.
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('home') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-home mr-2"></i>Ir al Inicio
                        </a>
                        <!-- <button onclick="location.reload()" class="btn btn-secondary btn-lg ml-2">
                            <i class="fas fa-sync-alt mr-2"></i>Reintentar
                        </button> -->
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