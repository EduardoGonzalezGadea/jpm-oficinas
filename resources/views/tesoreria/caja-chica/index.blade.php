@extends('layouts.app')

@section('titulo', 'Caja Chica - JPM Oficinas')

@section('contenido')
<div class="container-fluid">
    <h2 class="mb-4">
        <strong>Gestión de Caja Chica</strong>
    </h2>
    <livewire:tesoreria.caja-chica.index />
</div>
@endsection