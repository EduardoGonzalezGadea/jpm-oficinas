@extends('layouts.app')

@section('title', 'Tesorería | Oficinas - Editar Pendiente')

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12">
                <h3 class="mb-0 ml-2">
                    <strong>Caja Chica | Editar Pendiente</strong>
                </h3>
            </div>
        </div>
        <livewire:tesoreria.caja-chica.pendiente.editar-pendiente :id="$pendiente->idPendientes" />
    </div>
@endsection
