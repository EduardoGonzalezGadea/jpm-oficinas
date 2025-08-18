@extends('layouts.app')

@section('title', 'Tránsito - JPM Oficinas')

@section('content')
    <div>
        <h2 class="mb-4">
            <strong>Gestión de Infracciones de Tránsito</strong>
        </h2>
        <livewire:infracciones-transito />
    </div>
@endsection
