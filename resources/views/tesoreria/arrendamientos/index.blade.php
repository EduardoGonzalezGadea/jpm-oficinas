@extends('layouts.app')

@section('title', 'Tesorería | Oficinas - Arrendamiento')

@section('content')
    <div class="container-fluid py-1 px-0">
        @livewire('tesoreria.arrendamientos.arrendamiento')
    </div>
@endsection
