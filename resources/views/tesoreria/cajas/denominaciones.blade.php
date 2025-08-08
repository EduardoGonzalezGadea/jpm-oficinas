@extends('layouts.app')

@section('title', 'Gestión de Denominaciones')

@section('content')
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col">
                <h3>Gestión de Denominaciones</h3>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('tesoreria.index') }}">Tesorería</a></li>
                        <li class="breadcrumb-item active">Gestión de Denominaciones</li>
                    </ol>
                </nav>
            </div>
        </div>

        @livewire('tesoreria.cajas.denominaciones')
    </div>
@endsection
