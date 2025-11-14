@extends('layouts.app')

@section('title', 'Ver Planilla de Cheques')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col">
                                <h4 class="card-title">
                                    <i class="fas fa-file-alt mr-2"></i>Ver Planilla de Cheques
                                </h4>
                            </div>
                            <div class="col-auto d-print-none">
                                <a href="{{ route('tesoreria.cheques.index') }}" class="btn btn-success btn-sm">
                                    <i class="fas fa-arrow-left mr-1"></i>Volver
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @livewire('tesoreria.cheque.planilla-ver', ['id' => $id])
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
