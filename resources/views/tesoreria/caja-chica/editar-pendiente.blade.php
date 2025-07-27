@extends('layouts.app')

@section('titulo', 'Editar Pendiente - JPM Oficinas')

@section('contenido')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12 d-flex justify-content-between align-items-center">
                <h3 class="mb-0 ml-2">
                    <strong>Caja Chica | Editar Pendiente</strong>
                </h3>
                <a href="{{ route('tesoreria.caja-chica.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
        <livewire:tesoreria.caja-chica.pendiente.editar-pendiente :id="$pendiente->idPendientes" />
    </div>
@endsection
