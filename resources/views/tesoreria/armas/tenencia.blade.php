@extends('tesoreria.armas.index')

@section('content_armas')
@livewire('tesoreria.armas.tes-tenencia-armas', ['anio' => request('anio', date('Y'))])
@endsection