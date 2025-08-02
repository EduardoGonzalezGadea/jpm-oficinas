{{-- resources/views/tesoreria/valores/salidas.blade.php --}}
@extends('layouts.tesoreria')

@section('breadcrumb')
    <li class="breadcrumb-item">Valores</li>
    <li class="breadcrumb-item active">Salidas</li>
@endsection

@section('page-content')
    @livewire('tesoreria.valores.salidas')
@endsection