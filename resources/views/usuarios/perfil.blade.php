@extends('layouts.app')

@section('titulo', 'Mi Perfil - JPM Oficinas')

@section('contenido')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0 ml-2">
                    <i class="fas fa-user-edit"></i> Mi Perfil
                </h4>
            </div>
            <div class="card-body">
                {{-- Pestañas --}}
                <ul class="nav nav-tabs" id="perfilTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="datos-tab" data-toggle="tab" href="#datos" role="tab">
                            <i class="fas fa-user"></i> Datos Personales
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="contrasena-tab" data-toggle="tab" href="#contrasena" role="tab">
                            <i class="fas fa-lock"></i> Cambiar Contraseña
                        </a>
                    </li>
                </ul>

                <div class="tab-content" id="perfilTabContent">
                    {{-- Pestaña Datos Personales --}}
                    <div class="tab-pane fade show active" id="datos" role="tabpanel">
                        <div class="mt-4">
                            <form action="{{ route('usuarios.actualizarPerfil') }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nombre" class="form-label">
                                                <i class="fas fa-user"></i> Nombre *
                                            </label>
                                            <input type="text" 
                                                   id="nombre" 
                                                   name="nombre" 
                                                   class="form-control @error('nombre') is-invalid @enderror" 
                                                   value="{{ old('nombre', $usuario->nombre) }}" 
                                                   required>
                                            @error('nombre')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="apellido" class="form-label">
                                                <i class="fas fa-user"></i> Apellido *
                                            </label>
                                            <input type="text" 
                                                   id="apellido" 
                                                   name="apellido" 
                                                   class="form-control @error('apellido') is-invalid @enderror" 
                                                   value="{{ old('apellido', $usuario->apellido) }}" 
                                                   required>
                                            @error('apellido')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope"></i> Email *
                                    </label>
                                    <input type="email" 
                                           id="email" 
                                           name="email" 
                                           class="form-control @error('email') is-invalid @enderror" 
                                           value="{{ old('email', $usuario->email) }}" 
                                           required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="telefono" class="form-label">
                                        <i class="fas fa-phone"></i> Teléfono
                                    </label>
                                    <input type="tel" 
                                           id="telefono" 
                                           name="telefono" 
                                           class="form-control @error('telefono') is-invalid @enderror" 
                                           value="{{ old('telefono', $usuario->telefono) }}" 
                                           placeholder="Ej: +598 99 123 456">
                                    @error('telefono')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="direccion" class="form-label">
                                        <i class="fas fa-map-marker-alt"></i> Dirección
                                    </label>
                                    <textarea id="direccion" 
                                              name="direccion" 
                                              class="form-control @error('direccion') is-invalid @enderror" 
                                              rows="3" 
                                              placeholder="Dirección completa">{{ old('direccion', $usuario->direccion ?? '') }}</textarea>
                                    @error('direccion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-shield-alt"></i> Rol
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           value="{{ ucfirst($usuario->getRoleNames()->first() ?? 'Sin rol') }}" 
                                           readonly>
                                    <small class="form-text text-muted">
                                        Tu rol no puede ser modificado desde aquí.
                                    </small>
                                </div>

                                <div class="form-group mb-0">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Actualizar Datos
                                    </button>
                                    <a href="{{ route('panel') }}" class="btn btn-secondary ml-2">
                                        <i class="fas fa-arrow-left"></i> Volver
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Pestaña Cambiar Contraseña --}}
                    <div class="tab-pane fade" id="contrasena" role="tabpanel">
                        <div class="mt-4">
                            <form action="{{ route('usuarios.cambiarContraseña') }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    La contraseña debe tener al menos 6 caracteres.
                                </div>

                                <div class="form-group">
                                    <label for="contraseña_actual" class="form-label">
                                        <i class="fas fa-key"></i> Contraseña Actual *
                                    </label>
                                    <input type="password" 
                                           id="contraseña_actual" 
                                           name="contraseña_actual" 
                                           class="form-control @error('contraseña_actual') is-invalid @enderror" 
                                           required>
                                    @error('contraseña_actual')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="nueva_contraseña" class="form-label">
                                        <i class="fas fa-lock"></i> Nueva Contraseña *
                                    </label>
                                    <input type="password" 
                                           id="nueva_contraseña" 
                                           name="nueva_contraseña" 
                                           class="form-control @error('nueva_contraseña') is-invalid @enderror" 
                                           required>
                                    @error('nueva_contraseña')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="nueva_contraseña_confirmation" class="form-label">
                                        <i class="fas fa-lock"></i> Confirmar Nueva Contraseña *
                                    </label>
                                    <input type="password" 
                                           id="nueva_contraseña_confirmation" 
                                           name="nueva_contraseña_confirmation" 
                                           class="form-control" 
                                           required>
                                </div>

                                <div class="form-group mb-0">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-key"></i> Cambiar Contraseña
                                    </button>
                                    <button type="button" class="btn btn-secondary ml-2" onclick="limpiarFormularioContrasena()">
                                        <i class="fas fa-eraser"></i> Limpiar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function limpiarFormularioContrasena() {
    document.getElementById('contraseña_actual').value = '';
    document.getElementById('nueva_contraseña').value = '';
    document.getElementById('nueva_contraseña_confirmation').value = '';
}

// Mantener la pestaña activa después de enviar formulario
$(document).ready(function() {
    // Si hay errores relacionados con contraseña, mostrar esa pestaña
    @if($errors->has('contraseña_actual') || $errors->has('nueva_contraseña'))
        $('#contrasena-tab').tab('show');
    @endif
});
</script>
@endsection