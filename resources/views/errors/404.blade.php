@extends('layouts.app')

@section('title', 'Página No Encontrada')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-search"></i> Página No Encontrada
                    </h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-question-circle fa-4x text-info"></i>
                    </div>
                    <h5 class="card-title">404 - Página no encontrada</h5>
                    <p class="card-text">
                        Lo sentimos, la página que está buscando no existe o ha sido movida.
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('panel') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-home mr-2"></i>Ir al Panel Principal
                        </a>
                        <button onclick="history.back()" class="btn btn-secondary btn-lg ml-2">
                            <i class="fas fa-arrow-left mr-2"></i>Volver Atrás
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
