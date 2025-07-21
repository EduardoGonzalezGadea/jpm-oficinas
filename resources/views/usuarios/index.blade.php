@extends('layouts.app')

@section('titulo', 'Gestión de Usuarios')

@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <strong>Gestión de Usuarios</strong>
                    </h4>
                    <a href="{{ route('usuarios.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Usuario
                    </a>
                </div>
                <div class="card-body">
                    {{-- Livewire component for the users table --}}
                    @livewire('users-table')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection