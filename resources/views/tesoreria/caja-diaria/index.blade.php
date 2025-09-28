@extends('layouts.app')

@section('content')
    {{-- Se pasa el parametro 'tab' desde la ruta al componente --}}
    <livewire:tesoreria.caja-diaria.caja-diaria-principal :tab="$tab ?? 'resumen'" />
@endsection
