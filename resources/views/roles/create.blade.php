@extends('layouts.app')

@section('titulo', 'Crear Rol - JPM Oficinas')

@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Crear Nuevo Rol</h4>
                    <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('roles.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Nombre del Rol *</label>
                                    <input type="text" 
                                           name="name" 
                                           id="name"
                                           class="form-control @error('name') is-invalid @enderror" 
                                           value="{{ old('name') }}" 
                                           required
                                           placeholder="Ej: moderador, editor, supervisor">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        El nombre debe ser único y descriptivo
                                    </small>
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
                                                                           {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}>
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
                                        Selecciona los permisos que tendrá este rol
                                    </small>
                                </div>
                            </div>
                        </div>

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
                                    </div>
                                    <div>
                                        <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Crear Rol
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
    const checkboxes = document.querySelectorAll('input[name="permissions[]"]');
    
    selectAllBtn.addEventListener('click', function() {
        checkboxes.forEach(checkbox => checkbox.checked = true);
    });
    
    deselectAllBtn.addEventListener('click', function() {
        checkboxes.forEach(checkbox => checkbox.checked = false);
    });
});
</script>
@endpush
@endsection