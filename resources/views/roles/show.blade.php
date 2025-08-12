@extends('layouts.app')

@section('title', 'Detalle del Rol - JPM Oficinas')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            Detalle del Rol: <span class="text-primary">{{ ucfirst($role->name) }}</span>
                            @if ($role->name === 'administrador')
                                <span class="badge badge-warning ml-2">
                                    <i class="fas fa-crown"></i> Sistema
                                </span>
                            @endif
                        </h4>
                        <div>
                            <a href="{{ route('roles.edit', $role) }}" class="btn btn-warning mr-2">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Información general del rol -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i class="fas fa-info-circle"></i> Información General</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-borderless table-sm">
                                            <tr>
                                                <td><strong>ID:</strong></td>
                                                <td>{{ $role->id }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Nombre:</strong></td>
                                                <td>{{ ucfirst($role->name) }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Guard:</strong></td>
                                                <td><span class="badge badge-secondary">{{ $role->guard_name }}</span></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Creado:</strong></td>
                                                <td>{{ $role->created_at->format('d/m/Y H:i') }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Actualizado:</strong></td>
                                                <td>{{ $role->updated_at->format('d/m/Y H:i') }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fas fa-chart-pie"></i> Estadísticas</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <h3 class="text-info">{{ $role->permissions->count() }}</h3>
                                                <small class="text-muted">Permisos</small>
                                            </div>
                                            <div class="col-4">
                                                <h3 class="text-primary">{{ $role->users->count() }}</h3>
                                                <small class="text-muted">Usuarios</small>
                                            </div>
                                            <div class="col-4">
                                                <h3 class="text-success">
                                                    {{ $role->permissions->groupBy(function ($p) {
                                                            return explode('.', $p->name)[0];
                                                        })->count() }}
                                                </h3>
                                                <small class="text-muted">Módulos</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Permisos del rol -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-shield-alt"></i> Permisos Asignados
                                            <span class="badge badge-info">{{ $role->permissions->count() }}</span>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        @if ($role->permissions->count() > 0)
                                            @php
                                                $permissionsByModule = $role->permissions->groupBy(function (
                                                    $permission,
                                                ) {
                                                    return explode('.', $permission->name)[0];
                                                });
                                            @endphp

                                            <div class="row">
                                                @foreach ($permissionsByModule as $module => $modulePermissions)
                                                    <div class="col-md-4 mb-3">
                                                        <div class="card border-info">
                                                            <div class="card-header bg-light">
                                                                <h6 class="mb-0 text-capitalize">
                                                                    <i class="fas fa-folder"></i> {{ ucfirst($module) }}
                                                                    <span
                                                                        class="badge badge-info float-right">{{ $modulePermissions->count() }}</span>
                                                                </h6>
                                                            </div>
                                                            <div class="card-body p-2">
                                                                @foreach ($modulePermissions as $permission)
                                                                    <span
                                                                        class="badge badge-secondary mb-1 d-block text-left">
                                                                        <i class="fas fa-check"></i>
                                                                        {{ ucfirst(str_replace(['.', '_'], [' - ', ' '], $permission->name)) }}
                                                                    </span>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                Este rol no tiene permisos asignados.
                                                <a href="{{ route('roles.edit', $role) }}" class="alert-link">Asignar
                                                    permisos</a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Usuarios con este rol -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-users"></i> Usuarios con este Rol
                                            <span class="badge badge-primary">{{ $role->users->count() }}</span>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        @if ($role->users->count() > 0)
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Avatar</th>
                                                            <th>Nombre</th>
                                                            <th>Email</th>
                                                            <th>Estado</th>
                                                            <th>Último Acceso</th>
                                                            <th>Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($role->users as $user)
                                                            <tr>
                                                                <td>
                                                                    <div
                                                                        class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center">
                                                                        <span class="text-white font-weight-bold">
                                                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                                                        </span>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <strong>{{ $user->name }}</strong>
                                                                    @if ($user->roles->count() > 1)
                                                                        <br><small class="text-muted">
                                                                            +{{ $user->roles->count() - 1 }} rol(es) más
                                                                        </small>
                                                                    @endif
                                                                </td>
                                                                <td>{{ $user->email }}</td>
                                                                <td>
                                                                    @if ($user->email_verified_at)
                                                                        <span class="badge badge-success">Verificado</span>
                                                                    @else
                                                                        <span class="badge badge-warning">Pendiente</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if ($user->last_login_at)
                                                                        {{ $user->last_login_at->diffForHumans() }}
                                                                    @else
                                                                        <span class="text-muted">Nunca</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    <div class="btn-group btn-group-sm">
                                                                        @can('users.edit')
                                                                            <a href="{{ route('users.edit', $user) }}"
                                                                                class="btn btn-outline-primary btn-sm"
                                                                                title="Editar usuario">
                                                                                <i class="fas fa-edit"></i>
                                                                            </a>
                                                                        @endcan

                                                                        @can('roles.assign')
                                                                            @if ($role->name !== 'administrador')
                                                                                <form
                                                                                    action="{{ route('roles.remove.user', ['user_id' => $user->id, 'role_id' => $role->id]) }}"
                                                                                    method="POST" style="display: inline-block;"
                                                                                    class="form-confirm"
                                                                                    data-message="¿Está seguro de que desea remover el rol {{ $role->name }} del usuario {{ $user->name }}?">
                                                                                    @csrf
                                                                                    @method('DELETE')
                                                                                    <button type="submit"
                                                                                        class="btn btn-outline-danger btn-sm"
                                                                                        title="Remover rol">
                                                                                        <i class="fas fa-times"></i>
                                                                                    </button>
                                                                                </form>
                                                                            @endif
                                                                        @endcan
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i>
                                                No hay usuarios asignados a este rol.
                                                @can('users.edit')
                                                    <a href="{{ route('users.index') }}" class="alert-link">Asignar
                                                        usuarios</a>
                                                @endcan
                                            </div>
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

    @push('styles')
        <style>
            .avatar-sm {
                width: 32px;
                height: 32px;
                font-size: 12px;
            }

            .card-header h6 {
                margin-bottom: 0;
            }

            .badge-secondary {
                font-size: 0.75em;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.form-confirm').forEach(form => {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const message = this.dataset.message;

                        Swal.fire({
                            title: '¿Estás seguro?',
                            text: message,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Sí, continuar',
                            cancelButtonText: 'Cancelar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                this.submit();
                            }
                        });
                    });
                });
            });
        </script>
    @endpush
@endsection
