{{-- resources/views/tesoreria/valores/conceptos.blade.php --}}
@extends('layouts.tesoreria')

@section('breadcrumb')
    <li class="breadcrumb-item">Valores</li>
    <li class="breadcrumb-item active">Conceptos</li>
@endsection

@section('page-content')
    @livewire('tesoreria.valores.conceptos')
@endsection