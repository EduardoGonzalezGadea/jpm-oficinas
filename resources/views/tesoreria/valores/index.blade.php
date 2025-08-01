@extends('layouts.app')

@section('titulo', 'Valores - JPM Oficinas')

@section('contenido')
<div class="container-fluid">
    <h2 class="mb-4">
        <strong>Control del Stock de Valores</strong>
    </h2>
    <livewire:tesoreria.valores.index />
</div>
@endsection