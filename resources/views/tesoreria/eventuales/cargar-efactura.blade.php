@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb py-1 mb-3">
                    <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tesoreria.index') }}">Tesorería</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tesoreria.eventuales.index') }}">Eventuales</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Cargar eFactura</li>
                </ol>
            </nav>

            <livewire:tesoreria.eventuales.cargar-efactura />
        </div>
    </div>
</div>
@endsection