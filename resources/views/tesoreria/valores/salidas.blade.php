{{-- resources/views/tesoreria/valores/salidas.blade.php --}}
@extends('layouts.valores')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('tesoreria.valores.index') }}">Valores</a>
    </li>
    <li class="breadcrumb-item active">
        <a href="{{ route('tesoreria.valores.salidas') }}">Salidas</a>
    </li>
@endsection

@section('page-content')
    @livewire('tesoreria.valores.salidas')
@endsection
