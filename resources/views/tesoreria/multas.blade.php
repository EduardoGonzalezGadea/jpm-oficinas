@extends('layouts.app')

@section('title', 'Multas de Tránsito - JPM Oficinas')

@section('content')
    <div>
        <h2 class="mb-4">
            <strong>Listado de Multas de Tránsito</strong>
        </h2>
        <livewire:tesoreria.multa />
    </div>
@endsection
