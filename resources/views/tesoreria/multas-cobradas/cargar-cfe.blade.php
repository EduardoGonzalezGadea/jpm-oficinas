@extends('layouts.app')

@section('title', 'Cargar CFE de Multas | Tesorería')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb py-1 px-3">
                    <li class="breadcrumb-item"><a href="{{ route('tesoreria.multas-cobradas.index') }}">Multas Cobradas</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Cargar CFE</li>
                </ol>
            </nav>
            @livewire('tesoreria.multas-cobradas.cargar-cfe')
        </div>
    </div>
</div>
@endsection