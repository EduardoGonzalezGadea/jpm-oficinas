@extends('layouts.app')

@section('titulo', 'Detalle del Permiso - JPM Oficinas')

@section('contenido')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Detalles del Permiso</h4>
                    <div>
                        <a href="{{ route('$permissions.edit', ['permiso' => $permission]) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <a href="{{ route('permissions .index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>ID:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $permission->id }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Nombre:</strong>
                        </div>
                        <div class="col-sm-9">
                            <code>{{ $permission->name }}</code>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Guard Name:</strong>
                        </div>
                        <div class="col-sm-9">
                            <span class="badge bg-info">{{ $permission->guard_name }}</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Fecha de Creación:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $permission->created_at->format('d/m/Y H:i:s') }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Última Actualización:</strong>
                        </div>
                        <div class="col-sm-9">
                            {{ $permission->updated_at->format('d/m/Y H:i:s') }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Roles Asignados:</strong>
                        </div>
                        <div class="col-sm-9">
                            @if($permission->roles->count() > 0)
                                @foreach($permission->roles as $role)
                                    <span class="badge bg-secondary me-2 mb-1">{{ $role->name }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">Este permiso no está asignado a ningún rol</span>
                            @endif
                        </div>
                    </div>

                    @if($permission->roles->count() > 0)
                        <div class="row mb-3">
                            <div class="col-sm-3">
                                <strong>Usuarios con este permiso:</strong>
                            </div>
                            <div class="col-sm-9">
                                @php
                                    $users = collect();
                                    foreach($permission->roles as $role) {
                                        $users = $users->merge($role->users);
                                    }
                                    $users = $users->unique('id');
                                @endphp
                                
                                @if($users->count() > 0)
                                    <div class="d-flex flex-wrap">
                                        @foreach($users as $user)
                                            <span class="badge bg-success me-2 mb-1">{{ $user->name }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted">Ningún usuario tiene este permiso actualmente</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection