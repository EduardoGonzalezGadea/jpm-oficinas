@extends('layouts.app')

@section('title', 'Detalle del Usuario')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0 ml-2">
                            <strong>Detalle del Usuario: {{ $usuario->nombre_completo }}</strong>
                        </h4>
                        <div>
                            <a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn-warning mr-2">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="30%">Nombre Completo:</th>
                                        <td>{{ $usuario->nombre_completo }}</td>
                                    </tr>
                                    <tr>
                                        <th>Email:</th>
                                        <td>{{ $usuario->email }}</td>
                                    </tr>
                                    <tr>
                                        <th>Teléfono:</th>
                                        <td>{{ $usuario->telefono ?? 'No especificado' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Cédula:</th>
                                        <td>{{ $usuario->cedula ?? 'No especificada' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Módulo:</th>
                                        <td>
                                            @if ($usuario->modulo)
                                                <span class="badge badge-info">{{ $usuario->modulo->nombre }}</span>
                                            @else
                                                <span class="text-muted">Sin módulo asignado</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Estado:</th>
                                        <td>
                                            @if ($usuario->activo)
                                                <span class="badge badge-success">Activo</span>
                                            @else
                                                <span class="badge badge-danger">Inactivo</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Fecha de Registro:</th>
                                        <td>{{ $usuario->created_at->format('d/m/Y H:i:s') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Última Actualización:</th>
                                        <td>{{ $usuario->updated_at->format('d/m/Y H:i:s') }}</td>
                                    </tr>
                                </table>
                            </div>

                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Dirección</h5>
                                    </div>
                                    <div class="card-body">
                                        {{ $usuario->direccion ?? 'No especificada' }}
                                    </div>
                                </div>

                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5 class="mb-0">Roles Asignados</h5>
                                    </div>
                                    <div class="card-body">
                                        @if ($usuario->roles->count() > 0)
                                            @foreach ($usuario->roles as $role)
                                                <span class="badge badge-secondary mr-2 mb-2">{{ $role->name }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted">Sin roles asignados</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5 class="mb-0">Permisos</h5>
                                    </div>
                                    <div class="card-body">
                                        @php
                                            $permissions = $usuario->getAllPermissions();
                                        @endphp

                                        @if ($permissions->count() > 0)
                                            @foreach ($permissions as $permission)
                                                <span class="badge badge-primary mr-2 mb-2">{{ $permission->name }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted">Sin permisos asignados</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Acciones Rápidas</h5>
                                    </div>
                                    <div class="card-body">
                                        <form action="{{ route('usuarios.reset-password', $usuario) }}" method="POST"
                                            style="display: inline-block;"
                                            onsubmit="return confirm('¿Está seguro de restablecer la contraseña a 123456?')">
                                            @csrf
                                            <button type="submit" class="btn btn-warning mr-2">
                                                <i class="fas fa-key"></i> Restablecer Contraseña
                                            </button>
                                        </form>

                                        <form action="{{ route('usuarios.toggle-status', $usuario) }}" method="POST"
                                            style="display: inline-block;"
                                            onsubmit="return confirm('¿Está seguro de cambiar el estado del usuario?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="btn {{ $usuario->activo ? 'btn-secondary' : 'btn-success' }} mr-2">
                                                <i
                                                    class="fas {{ $usuario->activo ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                                                {{ $usuario->activo ? 'Desactivar' : 'Activar' }} Usuario
                                            </button>
                                        </form>

                                        @if (auth()->id() !== $usuario->id)
                                            <form action="{{ route('usuarios.destroy', $usuario) }}" method="POST"
                                                style="display: inline-block;"
                                                onsubmit="return confirm('¿Está seguro de eliminar este usuario? Esta acción no se puede deshacer.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fas fa-trash"></i> Eliminar Usuario
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
