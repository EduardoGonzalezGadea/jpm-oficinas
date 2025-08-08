@extends('layouts.app')

@section('title', 'Arqueo de Caja')

@section('content')
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col">
                <h3>Arqueo de Caja</h3>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('tesoreria.index') }}">Tesorería</a></li>
                        <li class="breadcrumb-item active">Arqueo de Caja</li>
                    </ol>
                </nav>
            </div>
        </div>

        @livewire('tesoreria.cajas.arqueo')
    </div>
@endsection
