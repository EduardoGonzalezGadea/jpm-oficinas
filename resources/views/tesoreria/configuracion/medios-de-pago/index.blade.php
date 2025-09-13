@extends('layouts.app')

@section('title', 'Medios de Pago - JPM Oficinas')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Medios de Pago</h3>
                    <a href="{{ route('tesoreria.configuracion.medios-de-pago.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Medio de Pago
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th class="text-center align-middle">Nombre</th>
                                    <th class="text-center align-middle">Descripción</th>
                                    <th class="text-center align-middle">Estado</th>
                                    <th class="text-center align-middle">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($mediosDePago as $medio)
                                    <tr>
                                        <td class="text-left align-middle">{{ $medio->nombre }}</td>
                                        <td class="text-left align-middle">{{ $medio->descripcion }}</td>
                                        <td class="text-center align-middle">
                                            @if($medio->activo)
                                                <span class="badge badge-success">Activo</span>
                                            @else
                                                <span class="badge badge-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            <a href="{{ route('tesoreria.configuracion.medios-de-pago.show', $medio) }}" class="btn btn-sm btn-info" title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('tesoreria.configuracion.medios-de-pago.edit', $medio) }}" class="btn btn-sm btn-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('tesoreria.configuracion.medios-de-pago.destroy', $medio) }}" method="POST" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Eliminar"
                                                        onclick="return confirm('¿Estás seguro de que deseas eliminar este medio de pago?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No hay medios de pago registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $mediosDePago->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
