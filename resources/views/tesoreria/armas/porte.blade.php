@extends('tesoreria.armas.index')

@section('content_armas')
@livewire('tesoreria.armas.tes-porte-armas', ['anio' => request('anio', date('Y'))])
@endsection