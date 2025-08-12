@extends('layouts.app')

@section('title', 'Gestión de Roles - JPM Oficinas')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <strong>Gestión de Roles</strong>
                        </h4>
                        <div>
                            <a href="{{ route('permissions.index') }}" class="btn btn-info mr-2">
                                <i class="fas fa-shield-alt"></i> Permisos
                            </a>
                            @can('acceso_administrador')
                                <a href="{{ route('roles.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Nuevo Rol
                                </a>
                            @endcan
                        </div>
                    </div>
                    <div class="card-body">
                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="close" data-dismiss="alert">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th class="align-middle">ID</th>
                                        <th class="align-middle">Nombre del Rol</th>
                                        <th class="align-middle">Permisos Asignados</th>
                                        <th class="align-middle">Usuarios con este Rol</th>
                                        <th class="align-middle">Fecha de Creación</th>
                                        <th class="align-middle">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($roles as $role)
                                        <tr>
                                            <td>{{ $role->id }}</td>
                                            <td>
                                                <strong>{{ $role->name }}</strong>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">{{ $role->permissions->count() }}
                                                    permisos</span>
                                                @if ($role->permissions->count() > 0)
                                                    <br>
                                                    <small class="text-muted">
                                                        @foreach ($role->permissions->take(3) as $permission)
                                                            <span
                                                                class="badge badge-secondary mr-1">{{ $permission->name }}</span>
                                                        @endforeach
                                                        @if ($role->permissions->count() > 3)
                                                            <span
                                                                class="badge badge-light">+{{ $role->permissions->count() - 3 }}
                                                                más</span>
                                                        @endif
                                                    </small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-primary">{{ $role->users->count() }}
                                                    usuarios</span>
                                            </td>
                                            <td>{{ $role->created_at->format('d/m/Y H:i') }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('roles.edit', $role) }}"
                                                        class="btn btn-sm btn-warning" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>

                                                    @if ($role->name !== 'administrador')
                                                        <form action="{{ route('roles.destroy', $role) }}" method="POST"
                                                            style="display: inline-block;"
                                                            class="form-confirm"
                                                            data-message="¿Está seguro de eliminar este rol? Los usuarios con este rol perderán estos permisos.">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger"
                                                                title="Eliminar">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @else
                                                        <button type="button" class="btn btn-sm btn-secondary"
                                                            title="No se puede eliminar el rol de administrador" disabled>
                                                            <i class="fas fa-lock"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No se encontraron roles.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <div class="d-flex justify-content-center mt-3">
                            {{ $roles->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                @if (session('swal-success'))
                    window.dispatchEvent(new CustomEvent('swal:success', {
                        detail: {
                            text: '{{ session('swal-success') }}'
                        }
                    }));
                @endif

                @if (session('toast-error'))
                    window.dispatchEvent(new CustomEvent('swal:toast-error', {
                        detail: {
                            text: '{{ session('toast-error') }}'
                        }
                    }));
                @endif
            });
        </script>
    @endpush
@endsection
