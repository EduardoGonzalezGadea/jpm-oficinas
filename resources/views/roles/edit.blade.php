@extends('layouts.app')

@section('titulo', 'Editar Rol')

@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Editar Rol: <span class="text-primary">{{ ucfirst($role->name) }}</span></h4>
                    <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('roles.update', $role) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Nombre del Rol *</label>
                                    <input type="text" 
                                           name="name" 
                                           id="name"
                                           class="form-control @error('name') is-invalid @enderror" 
                                           value="{{ old('name', $role->name) }}" 
                                           required
                                           placeholder="Ej: moderador, editor, supervisor"
                                           {{ $role->name === 'administrador' ? 'readonly' : '' }}>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if($role->name === 'administrador')
                                        <small class="form-text text-warning">
                                            <i class="fas fa-lock"></i> El nombre del rol administrador no se puede cambiar
                                        </small>
                                    @else
                                        <small class="form-text text-muted">
                                            El nombre debe ser único y descriptivo
                                        </small>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Información del Rol</label>
                                    <div class="card bg-light">
                                        <div class="card-body py-2">
                                            <small>
                                                <strong>Creado:</strong> {{ $role->created_at->format('d/m/Y H:i') }}<br>
                                                <strong>Usuarios asignados:</strong> {{ $role->users->count() }}<br>
                                                <strong>Permisos actuales:</strong> {{ $role->permissions->count() }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label>Permisos del Rol</label>
                                    <div class="row">
                                        @if($permissions->count() > 0)
                                            @php
                                                $permissionsByModule = $permissions->groupBy(function($permission) {
                                                    return explode('.', $permission->name)[0];
                                                });
                                            @endphp
                                            
                                            @foreach($permissionsByModule as $module => $modulePermissions)
                                                <div class="col-md-4 mb-3">
                                                    <div class="card">
                                                        <div class="card-header py-2">
                                                            <h6 class="mb-0 text-capitalize">
                                                                <strong>{{ ucfirst($module) }}</strong>
                                                                <small class="float-right">
                                                                    @php
                                                                        $moduleSelected = $modulePermissions->whereIn('id', $rolePermissions)->count();
                                                                    @endphp
                                                                    ({{ $moduleSelected }}/{{ $modulePermissions->count() }})
                                                                </small>
                                                            </h6>
                                                        </div>
                                                        <div class="card-body py-2">
                                                            @foreach($modulePermissions as $permission)
                                                                <div class="form-check">
                                                                    <input type="checkbox" 
                                                                           name="permissions[]" 
                                                                           value="{{ $permission->id }}"
                                                                           id="permission_{{ $permission->id }}"
                                                                           class="form-check-input"
                                                                           {{ in_array($permission->id, old('permissions', $rolePermissions)) ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                                        {{ ucfirst(str_replace(['.', '_'], [' - ', ' '], $permission->name)) }}
                                                                    </label>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="col-12">
                                                <div class="alert alert-warning">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    No hay permisos disponibles. 
                                                    <a href="{{ route('permissions.index') }}" class="alert-link">Crear permisos primero</a>.
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    @error('permissions')
                                        <div class="text-danger mt-2">{{ $message }}</div>
                                    @enderror
                                    
                                    <small class="form-text text-muted">
                                        Modifica los permisos que tendrá este rol
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Usuarios asignados -->
                        @if($role->users->count() > 0)
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Usuarios con este Rol</label>
                                        <div class="card bg-info text-white">
                                            <div class="card-body py-2">
                                                <div class="row">
                                                    @foreach($role->users->take(10) as $user)
                                                        <div class="col-md-4">
                                                            <small>
                                                                <i class="fas fa-user"></i> {{ $user->name }}
                                                                <br><span class="text-light">{{ $user->email }}</span>
                                                            </small>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                @if($role->users->count() > 10)
                                                    <small>
                                                        <em>Y {{ $role->users->count() - 10 }} usuarios más...</em>
                                                    </small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Botones de acción -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <button type="button" id="selectAll" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-check-square"></i> Seleccionar Todos
                                        </button>
                                        <button type="button" id="deselectAll" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-square"></i> Deseleccionar Todos
                                        </button>
                                        <button type="button" id="toggleModule" class="btn btn-outline-info btn-sm">
                                            <i class="fas fa-exchange-alt"></i> Alternar por Módulo
                                        </button>
                                    </div>
                                    <div>
                                        <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Actualizar Rol
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllBtn = document.getElementById('selectAll');
    const deselectAllBtn = document.getElementById('deselectAll');
    const toggleModuleBtn = document.getElementById('toggleModule');
    const checkboxes = document.querySelectorAll('input[name="permissions[]"]');
    
    selectAllBtn.addEventListener('click', function() {
        checkboxes.forEach(checkbox => checkbox.checked = true);
        updateModuleCounts();
    });
    
    deselectAllBtn.addEventListener('click', function() {
        checkboxes.forEach(checkbox => checkbox.checked = false);
        updateModuleCounts();
    });

    toggleModuleBtn.addEventListener('click', function() {
        const modules = document.querySelectorAll('.card .card-header h6');
        let currentModule = 0;
        
        if (currentModule < modules.length) {
            const moduleCard = modules[currentModule].closest('.card');
            const moduleCheckboxes = moduleCard.querySelectorAll('input[type="checkbox"]');
            const allChecked = Array.from(moduleCheckboxes).every(cb => cb.checked);
            
            moduleCheckboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });
            
            updateModuleCounts();
        }
    });

    // Actualizar contadores cuando cambian los checkboxes
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateModuleCounts);
    });

    function updateModuleCounts() {
        const modules = document.querySelectorAll('.card .card-header h6 small');
        modules.forEach(counter => {
            const moduleCard = counter.closest('.card');
            const moduleCheckboxes = moduleCard.querySelectorAll('input[type="checkbox"]');
            const selectedCount = moduleCard.querySelectorAll('input[type="checkbox"]:checked').length;
            const totalCount = moduleCheckboxes.length;
            
            counter.textContent = `(${selectedCount}/${totalCount})`;
        });
    }

    // Inicializar contadores
    updateModuleCounts();
});
</script>
@endpush
@endsection