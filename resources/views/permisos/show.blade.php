@extends('layouts.app')

@section('title', 'Tesorería | Oficinas - Detalle del Permiso')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>Detalles del Permiso</h4>
                        <div>
                            <a href="{{ route('permissions.edit', $permission) }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <button type="button" class="btn btn-danger btn-sm"
                                onclick="deletePermission({{ $permission->id }})">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                            <a href="{{ route('permissions.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <!-- Información básica del permiso -->
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0">Información del Permiso</h6>
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
                                                <code class="text-primary">{{ $permission->name }}</code>
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
                                                <small
                                                    class="text-muted">({{ $permission->created_at->diffForHumans() }})</small>
                                            </div>
                                        </div>

                                        <div class="row mb-0">
                                            <div class="col-sm-3">
                                                <strong>Última Actualización:</strong>
                                            </div>
                                            <div class="col-sm-9">
                                                {{ $permission->updated_at->format('d/m/Y H:i:s') }}
                                                <small
                                                    class="text-muted">({{ $permission->updated_at->diffForHumans() }})</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Estadísticas</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="text-center mb-2">
                                            <h3 class="text-success">{{ $rolesWithPermission->count() }}</h3>
                                            <small class="text-muted">Roles asignados</small>
                                        </div>
                                        <div class="text-center mb-2">
                                            <h3 class="text-warning">{{ $usersWithDirectPermission->count() }}</h3>
                                            <small class="text-muted">Usuarios directos</small>
                                        </div>
                                        <div class="text-center">
                                            <h3 class="text-info">{{ $usersWithRolePermission->count() }}</h3>
                                            <small class="text-muted">Usuarios por roles</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Roles asignados -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-users-cog"></i> Roles que tienen este permiso
                                            <span
                                                class="badge bg-secondary ms-2">{{ $rolesWithPermission->count() }}</span>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        @if ($rolesWithPermission->count() > 0)
                                            <div class="row">
                                                @foreach ($rolesWithPermission as $role)
                                                    <div class="col-md-6 mb-3">
                                                        <div class="card border-secondary">
                                                            <div class="card-body">
                                                                <h6 class="card-title">
                                                                    <span
                                                                        class="badge bg-secondary">{{ $role->name }}</span>
                                                                </h6>
                                                                <p class="card-text">
                                                                    <small class="text-muted">
                                                                        {{ $role->users->count() }} usuario(s) con este rol
                                                                    </small>
                                                                </p>
                                                                @if ($role->users->count() > 0)
                                                                    <div class="mt-2">
                                                                        @foreach ($role->users->take(3) as $user)
                                                                            <span
                                                                                class="badge bg-light text-dark me-1">{{ $user->name }}</span>
                                                                        @endforeach
                                                                        @if ($role->users->count() > 3)
                                                                            <span
                                                                                class="badge bg-light text-dark">+{{ $role->users->count() - 3 }}
                                                                                más</span>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-center text-muted py-4">
                                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                                <p>Este permiso no está asignado a ningún rol</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Usuarios con permiso directo -->
                        @if ($usersWithDirectPermission->count() > 0)
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-user-check"></i> Usuarios con permiso directo
                                                <span
                                                    class="badge bg-warning text-dark ms-2">{{ $usersWithDirectPermission->count() }}</span>
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                @foreach ($usersWithDirectPermission as $user)
                                                    <div class="col-md-4 mb-2">
                                                        <span class="badge bg-warning text-dark">
                                                            <i class="fas fa-user"></i> {{ $user->name }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Todos los usuarios que tienen este permiso -->
                        @if ($usersWithRolePermission->count() > 0)
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-users"></i> Todos los usuarios con este permiso
                                                <span
                                                    class="badge bg-success ms-2">{{ $usersWithRolePermission->count() }}</span>
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            @if ($usersWithRolePermission->count() > 0)
                                                <div class="row">
                                                    @foreach ($usersWithRolePermission->take(12) as $user)
                                                        <div class="col-md-3 mb-2">
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-user"></i> {{ $user->name }}
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                    @if ($usersWithRolePermission->count() > 12)
                                                        <div class="col-12 mt-2">
                                                            <button class="btn btn-sm btn-outline-primary" type="button"
                                                                data-bs-toggle="collapse" data-bs-target="#allUsers">
                                                                Ver todos los {{ $usersWithRolePermission->count() }}
                                                                usuarios
                                                            </button>
                                                            <div class="collapse mt-3" id="allUsers">
                                                                <div class="row">
                                                                    @foreach ($usersWithRolePermission->skip(12) as $user)
                                                                        <div class="col-md-3 mb-2">
                                                                            <span class="badge bg-success">
                                                                                <i class="fas fa-user"></i>
                                                                                {{ $user->name }}
                                                                            </span>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="text-center text-muted py-3">
                                                    <i class="fas fa-users fa-2x mb-2"></i>
                                                    <p>Ningún usuario tiene este permiso actualmente</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        ¿Está seguro de que desea eliminar el permiso <strong>{{ $permission->name }}</strong>?
                    </div>
                    <p>Esta acción no se puede deshacer y puede afectar el funcionamiento del sistema.</p>
                    @if ($rolesWithPermission->count() > 0 || $usersWithDirectPermission->count() > 0)
                        <div class="alert alert-danger">
                            <strong>Advertencia:</strong> Este permiso está actualmente asignado a
                            {{ $rolesWithPermission->count() }} rol(es) y {{ $usersWithDirectPermission->count() }}
                            usuario(s) directamente.
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="deleteForm" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Eliminar Permiso
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function deletePermission(id) {
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            document.getElementById('deleteForm').action = `/permisos/${id}`;
            modal.show();
        }
    </script>
@endsection
