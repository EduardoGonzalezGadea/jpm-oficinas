@extends('layouts.app')

@section('title', 'Generar Planilla de Cheques')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-file-alt mr-2"></i>Generar Planilla de Cheques
                        </h4>
                        <a href="{{ route('tesoreria.cheques.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i>Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @livewire('tesoreria.cheque.planilla-generar')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
