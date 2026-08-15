@extends('layouts.app')

@section('title', 'Tesorería | Oficinas - Apertura / Cierre de Caja')

@section('content')
    <div class="container-fluid py-0 px-0 caja-diaria-view">
        <livewire:tesoreria.caja-diaria.apertura-cierre />
    </div>
@endsection