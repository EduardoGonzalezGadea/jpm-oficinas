{{-- resources/views/tesoreria/valores/entradas.blade.php --}}
@extends('layouts.tesoreria')

@section('breadcrumb')
    <li class="breadcrumb-item">Valores</li>
    <li class="breadcrumb-item active">Entradas</li>
@endsection

@section('page-content')
    @livewire('tesoreria.valores.entradas')
@endsection