@extends('layouts.app')

@section('titulo', 'Gestión de Permisos - JPM Oficinas')

@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <strong>Gestión de Permisos</strong>
                    </h4>
                    <div>
                        <a href="{{ route('permissions.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Crear Permiso
                        </a>
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#bulkCreateModal">
                            <i class="fas fa-layer-group"></i> Crear en Lote
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Filtros -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <form method="GET" action="{{ route('permissions.index') }}" class="d-flex">
                                <input type="text" name="search" class="form-control me-2" 
                                       placeholder="Buscar permisos..." 
                                       value="{{ request('search') }}">
                                <button type="submit" class="btn btn-outline-secondary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form method="GET" action="{{ route('permissions.index') }}">
                                <div class="input-group">
                                    <select name="module" class="form-select">
                                        <option value="">Todos los módulos</option>
                                        @foreach($modules as $module)
                                            <option value="{{ $module }}" {{ request('module') == $module ? 'selected' : '' }}>
                                                {{ ucfirst($module) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-outline-secondary">Filtrar</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th class="align-middle">ID</th>
                                    <th class="align-middle">Nombre</th>
                                    <th class="align-middle">Guard</th>
                                    <th class="align-middle">Roles Asignados</th>
                                    <th class="align-middle">Fecha Creación</th>
                                    <th class="align-middle">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($permissions as $permission)
                                    <tr>
                                        <td>{{ $permission->id }}</td>
                                        <td>
                                            <strong>{{ $permission->name }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-white">{{ $permission->guard_name }}</span>
                                        </td>
                                        <td>
                                            @if($permission->roles->count() > 0)
                                                @foreach($permission->roles as $role)
                                                    <span class="badge bg-secondary text-warning me-1">{{ $role->name }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">Sin roles</span>
                                            @endif
                                        </td>
                                        <td>{{ $permission->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('permissions.show', $permission) }}" 
                                                   class="btn btn-info btn-sm" title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('permissions.edit', $permission) }}" 
                                                   class="btn btn-warning btn-sm" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger btn-sm" 
                                                        onclick="deletePermission({{ $permission->id }})" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No hay permisos registrados</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div class="d-flex justify-content-center">
                        {{ $permissions->links() }}
                    </div>
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
                ¿Está seguro de que desea eliminar este permiso?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para crear permisos en lote -->
<div class="modal fade" id="bulkCreateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crear Permisos en Lote</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('permissions.bulk.create') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="module_name" class="form-label">Nombre del Módulo</label>
                        <input type="text" class="form-control" id="module_name" name="module_name" 
                               placeholder="ej: usuarios, productos" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Acciones a crear</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="index" id="action_index" name="actions[]">
                            <label class="form-check-label" for="action_index">index (ver listado)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="create" id="action_create" name="actions[]">
                            <label class="form-check-label" for="action_create">create (crear)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="edit" id="action_edit" name="actions[]">
                            <label class="form-check-label" for="action_edit">edit (editar)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="show" id="action_show" name="actions[]">
                            <label class="form-check-label" for="action_show">show (ver detalle)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="destroy" id="action_destroy" name="actions[]">
                            <label class="form-check-label" for="action_destroy">destroy (eliminar)</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Permisos</button>
                </div>
            </form>
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