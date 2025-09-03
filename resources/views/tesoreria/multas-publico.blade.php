@extends('layouts.publico')

@section('title', 'Multas de Tránsito - Consulta Pública - JPM Oficinas')

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="mb-0">
                                    <i class="fas fa-list mr-2"></i>
                                    Multas de Tránsito
                                </h2>
                                <p class="mb-0 mt-2">
                                    <i class="fas fa-eye mr-1"></i>
                                    Acceso como invitado - Solo lectura
                                </p>
                            </div>
                            <div class="col-md-4 text-right">
                                <i class="fas fa-traffic-light display-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <livewire:tesoreria.multa-publico />
        </div>
    </div>
@endsection
