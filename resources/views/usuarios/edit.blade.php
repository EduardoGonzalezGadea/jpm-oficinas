@extends('layouts.app')

@section('title', 'Editar Usuario')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0 ml-2">
                            <strong>Editar Usuario: {{ $usuario->nombre_completo }}</strong>
                        </h4>
                        <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('usuarios.update', $usuario) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nombre">Nombre *</label>
                                        <input type="text" name="nombre" id="nombre"
                                            class="form-control @error('nombre') is-invalid @enderror"
                                            value="{{ old('nombre', $usuario->nombre) }}" required>
                                        @error('nombre')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="apellido">Apellido *</label>
                                        <input type="text" name="apellido" id="apellido"
                                            class="form-control @error('apellido') is-invalid @enderror"
                                            value="{{ old('apellido', $usuario->apellido) }}" required>
                                        @error('apellido')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email *</label>
                                        <input type="email" name="email" id="email"
                                            class="form-control @error('email') is-invalid @enderror"
                                            value="{{ old('email', $usuario->email) }}" required>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="telefono">Teléfono</label>
                                        <input type="text" name="telefono" id="telefono"
                                            class="form-control @error('telefono') is-invalid @enderror"
                                            value="{{ old('telefono', $usuario->telefono) }}">
                                        @error('telefono')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="cedula">Cédula</label>
                                        <input type="text" name="cedula" id="cedula"
                                            class="form-control @error('cedula') is-invalid @enderror"
                                            value="{{ old('cedula', $usuario->cedula) }}">
                                        @error('cedula')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="modulo_id">Módulo</label>
                                        <select name="modulo_id" id="modulo_id"
                                            class="form-control @error('modulo_id') is-invalid @enderror">
                                            <option value="">Seleccionar módulo</option>
                                            @foreach ($modulos as $modulo)
                                                <option value="{{ $modulo->id }}"
                                                    {{ old('modulo_id', $usuario->modulo_id) == $modulo->id ? 'selected' : '' }}>
                                                    {{ $modulo->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('modulo_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="direccion">Dirección</label>
                                        <textarea name="direccion" id="direccion" rows="3" class="form-control @error('direccion') is-invalid @enderror">{{ old('direccion', $usuario->direccion) }}</textarea>
                                        @error('direccion')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Roles</label>
                                        <div class="row">
                                            @foreach ($roles as $role)
                                                <div class="col-md-3">
                                                    <div class="form-check">
                                                        <input type="radio" name="roles[]" value="{{ $role->name }}"
                                                            class="form-check-input" id="role_{{ $role->id }}"
                                                            {{ in_array($role->name, old('roles', $usuarioRoles)) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="role_{{ $role->id }}">
                                                            {{ $role->name }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        @error('roles')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input type="checkbox" name="activo" value="1" class="form-check-input"
                                                id="activo" {{ old('activo', $usuario->activo) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="activo">
                                                Usuario activo
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Actualizar Usuario
                                </button>
                                <a href="{{ route('usuarios.index') }}" class="btn btn-secondary ml-2">
                                    Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
