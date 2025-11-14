@extends('layouts.app')

@section('title', 'Tesorería | Oficinas - Crear Permiso')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Crear Nuevo Permiso</h4>
                    </div>

                    <div class="card-body">
                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form action="{{ route('permissions.store') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre del Permiso <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    id="name" name="name" value="{{ old('name') }}"
                                    placeholder="ej: usuarios.create, productos.edit" required>
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
                                <select class="form-select @error('guard_name') is-invalid @enderror" id="guard_name"
                                    name="guard_name">
                                    <option value="web" {{ old('guard_name', 'web') == 'web' ? 'selected' : '' }}>Web
                                    </option>
                                    <option value="api" {{ old('guard_name') == 'api' ? 'selected' : '' }}>API</option>
                                </select>
                                @error('guard_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    Seleccione 'web' para aplicaciones web o 'api' para APIs
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Asignar a Roles (opcional)</label>
                                <div class="row">
                                    @forelse($roles as $role)
                                        <div class="col-md-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="{{ $role->id }}"
                                                    id="role_{{ $role->id }}" name="roles[]"
                                                    {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="role_{{ $role->id }}">
                                                    {{ $role->name }}
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
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                    rows="3" placeholder="Breve descripción del permiso">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Crear Permiso
                                    </button>
                                </div>
                                <div class="col-md-6 text-end">
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
            if (name) {
                fetch(`{{ route('api.validate.permission') }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        },
                        body: JSON.stringify({
                            name: name
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
