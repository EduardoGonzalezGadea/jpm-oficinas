@extends('layouts.app')

@section('content')
    @livewire('tesoreria.deposito-vehiculos.planillas.show', ['id' => $planilla->id])
@endsection
