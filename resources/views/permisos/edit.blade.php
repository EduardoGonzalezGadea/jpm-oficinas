@extends('layouts.app')

@section('titulo', 'Editar Permiso - JPM Oficinas')

@section('contenido')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Editar Permiso: {{ $permission->name }}</h4>
                </div>
                
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('permissions.update', $permission) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre del Permiso <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $permission->name) }}" 
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Use formato punto para separar módulo y acción. 
                                Ejemplo: usuarios.create, productos.edit, reportes.index
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="guard_name" class="form-label">Guard Name</label>
                            <select class="form-select @error('guard_name') is-invalid @enderror" 
                                    id="guard_name" 
                                    name="guard_name">
                                <option value="web" {{ old('guard_name', $permission->guard_name) == 'web' ? 'selected' : '' }}>Web</option>
                                <option value="api" {{ old('guard_name', $permission->guard_name) == 'api' ? 'selected' : '' }}>API</option>
                            </select>
                            @error('guard_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Seleccione 'web' para aplicaciones web o 'api' para APIs
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Roles Asignados</label>
                            <div class="row">
                                @forelse($roles as $role)
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   value="{{ $role->id }}" 
                                                   id="role_{{ $role->id }}"
                                                   name="roles[]"
                                                   {{ in_array($role->id, old('roles', $permission->roles->pluck('id')->toArray())) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="role_{{ $role->id }}">
                                                {{ $role->name }}
                                                @if($permission->roles->contains($role->id))
                                                    <small class="text-success">(asignado)</small>
                                                @endif
                                            </label>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <p class="text-muted">No hay roles disponibles</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción (opcional)</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3"
                                      placeholder="Breve descripción del permiso">{{ old('description', '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Información adicional -->
                        <div class="mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Información del Permiso</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted">
                                                <strong>ID:</strong> {{ $permission->id }}<br>
                                                <strong>Creado:</strong> {{ $permission->created_at->format('d/m/Y H:i') }}<br>
                                                <strong>Actualizado:</strong> {{ $permission->updated_at->format('d/m/Y H:i') }}
                                            </small>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">
                                                <strong>Roles actuales:</strong> {{ $permission->roles->count() }}<br>
                                                <strong>Usuarios directos:</strong> {{ $permission->users->count() }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Actualizar Permiso
                                </button>
                            </div>
                            <div class="col-md-6 text-end">
                                <a href="{{ route('permissions.show', $permission) }}" class="btn btn-info me-2">
                                    <i class="fas fa-eye"></i> Ver Detalle
                                </a>
                                <a href="{{ route('permissions.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Volver
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validación en tiempo real del nombre del permiso
document.getElementById('name').addEventListener('blur', function() {
    const name = this.value;
    const permissionId = {{ $permission->id }};
    
    if (name) {
        fetch(`{{ route('api.validate.permission') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ 
                name: name,
                permission_id: permissionId
            })
        })
        .then(response => response.json())
        .then(data => {
            const input = document.getElementById('name');
            const feedback = input.parentNode.querySelector('.invalid-feedback');
            
            if (data.valid) {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
                if (feedback) feedback.style.display = 'none';
            } else {
                input.classList.remove('is-valid');
                input.classList.add('is-invalid');
                if (feedback) {
                    feedback.textContent = data.message;
                    feedback.style.display = 'block';
                }
            }
        })
        .catch(error => console.error('Error:', error));
    }
});
</script>
@endsection