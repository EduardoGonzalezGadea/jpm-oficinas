@extends('layouts.app')

@section('title', 'Tesorería | Oficinas - Arqueo de Caja')

@section('content')
    <div class="container-fluid py-0 px-0 caja-diaria-view">
        <livewire:tesoreria.caja-diaria.arqueo />
    </div>
@endsection