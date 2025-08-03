{{-- resources/views/tesoreria/valores/conceptos.blade.php --}}
@extends('layouts.tesoreria')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('tesoreria.valores.index') }}">Valores</a>
    </li>
    <li class="breadcrumb-item active">
        <a href="{{ route('tesoreria.valores.conceptos') }}">Conceptos</a>
    </li>
@endsection

@section('page-content')
    @livewire('tesoreria.valores.conceptos')
@endsection
