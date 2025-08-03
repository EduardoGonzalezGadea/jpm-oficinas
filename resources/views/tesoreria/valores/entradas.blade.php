{{-- resources/views/tesoreria/valores/entradas.blade.php --}}
@extends('layouts.tesoreria')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('tesoreria.valores.index') }}">Valores</a>
    </li>
    <li class="breadcrumb-item active">
        <a href="{{ route('tesoreria.valores.entradas') }}">Entradas</a>
    </li>
@endsection

@section('page-content')
    @livewire('tesoreria.valores.entradas')
@endsection
