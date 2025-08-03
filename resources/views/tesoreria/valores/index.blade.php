{{-- resources/views/tesoreria/valores/index.blade.php --}}
@extends('layouts.tesoreria')

@section('breadcrumb')
    <li class="breadcrumb-item">Valores</li>
    <li class="breadcrumb-item active">Listado</li>
@endsection

@section('page-content')
    @livewire('tesoreria.valores.index')
@endsection
