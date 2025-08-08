@extends('layouts.app')

@section('title', 'Caja Chica - JPM Oficinas')

@section('content')
    <div>
        <h2 class="mb-4">
            <strong>Gestión de Caja Chica</strong>
        </h2>
        <livewire:tesoreria.caja-chica.index />
    </div>
@endsection
