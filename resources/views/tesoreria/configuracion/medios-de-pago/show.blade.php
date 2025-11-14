@extends('layouts.app')

@section('title', 'Tesorería | Oficinas - Ver Medio de Pago')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Detalles del Medio de Pago</h3>
                    <div>
                        <a href="{{ route('tesoreria.configuracion.medios-de-pago.edit', $medioDePago) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <a href="{{ route('tesoreria.configuracion.medios-de-pago.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <table class="table table-borderless">
                                <tr>
                                    <th class="text-right" style="width: 150px;">Nombre:</th>
                                    <td>{{ $medioDePago->nombre }}</td>
                                </tr>
                                <tr>
                                    <th class="text-right">Descripción:</th>
                                    <td>{{ $medioDePago->descripcion ?: 'Sin descripción' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-right">Estado:</th>
                                    <td>
                                        @if($medioDePago->activo)
                                            <span class="badge badge-success">Activo</span>
                                        @else
                                            <span class="badge badge-secondary">Inactivo</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-right">Fecha de Creación:</th>
                                    <td>{{ $medioDePago->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th class="text-right">Última Actualización:</th>
                                    <td>{{ $medioDePago->updated_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
