<div>
    <!-- Filtros -->
    <div class="row mb-3">
        <div class="col-md-4">
            <input wire:model.debounce.300ms="search" type="text" class="form-control"
                placeholder="Buscar por nombre, email o cédula...">
        </div>
        <div class="col-md-2">
            <select wire:model="statusFilter" class="form-control">
                <option value="all">Todos los estados</option>
                <option value="active">Activos</option>
                <option value="inactive">Inactivos</option>
            </select>
        </div>
        <div class="col-md-2">
            <select wire:model="moduleFilter" class="form-control">
                <option value="all">Todos los módulos</option>
                @foreach ($modulos as $modulo)
                    <option value="{{ $modulo->id }}">{{ $modulo->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select wire:model="perPage" class="form-control">
                <option value="10">10 por página</option>
                <option value="25">25 por página</option>
                <option value="50">50 por página</option>
            </select>
        </div>
        <div class="col-md-2">
            <button wire:click="resetFilters" class="btn btn-secondary btn-block">
                <i class="fas fa-eraser"></i> Limpiar
            </button>
        </div>
    </div>

    <!-- Tabla -->
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Cédula</th>
                    <th>Módulo</th>
                    <th>Roles</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div>
                                    <strong>{{ $user->nombre_completo }}</strong>
                                </div>
                            </div>
                        </td>
                        <td>{{ $user->telefono ?? '-' }}</td>
                        <td>{{ $user->cedula ?? '-' }}</td>
                        <td>
                            @if ($user->modulo)
                                <span class="badge badge-info">{{ $user->modulo->nombre }}</span>
                            @else
                                <span class="text-muted">Sin módulo</span>
                            @endif
                        </td>
                        <td>
                            @if ($user->roles->count() > 0)
                                @foreach ($user->roles as $role)
                                    <span class="badge badge-secondary mr-1">{{ $role->name }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">Sin roles</span>
                            @endif
                        </td>
                        <td>
                            @if ($user->activo)
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-danger">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{{ route('usuarios.show', $user) }}" class="btn btn-sm btn-info"
                                    title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('usuarios.edit', $user) }}" class="btn btn-sm btn-warning"
                                    title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <!-- Botón para restablecer contraseña -->
                                <form action="{{ route('usuarios.reset-password', $user) }}" method="POST"
                                    style="display: inline-block;"
                                    onsubmit="resetPassword({{ $user }}); return false;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-secondary"
                                        title="Restablecer contraseña">
                                        <i class="fas fa-key"></i>
                                    </button>
                                </form>

                                <!-- Botón para cambiar estado -->
                                <form action="{{ route('usuarios.toggle-status', $user) }}" method="POST"
                                    style="display: inline-block;"
                                    onsubmit="toggleStatus({{ $user }}); return false;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="btn btn-sm {{ $user->activo ? 'btn-warning' : 'btn-success' }}"
                                        title="{{ $user->activo ? 'Desactivar' : 'Activar' }}">
                                        <i class="fas {{ $user->activo ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                                    </button>
                                </form>

                                <!-- Botón para eliminar -->
                                @if (auth()->id() !== $user->id && $user->id !== 1)
                                    <form action="{{ route('usuarios.destroy', $user) }}" method="POST"
                                        style="display: inline-block;"
                                        onsubmit="return confirm('¿Está seguro de eliminar este usuario? Esta acción no se puede deshacer.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center">No se encontraron usuarios.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div>
            <small class="text-muted">
                Mostrando {{ $users->firstItem() ?? 0 }} a {{ $users->lastItem() ?? 0 }}
                de {{ $users->total() }} resultados
            </small>
        </div>
        <div>
            {{ $users->links() }}
        </div>
    </div>
</div>

<script>
    // Función para restablecer la contraseña
    function resetPassword(user) {
        Swal.fire({
            title: 'Restablecer contraseña',
            text: '¿Está seguro de restablecer la contraseña a 123456?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Restablecer',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Realizar la solicitud para restablecer la contraseña
                $.ajax({
                    url: route('usuarios.reset-password', {
                        usuario: user
                    }),
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Contraseña restablecida',
                            text: 'La contraseña ha sido restablecida a 123456',
                            timer: 2000,
                            confirmButtonText: 'Aceptar'
                        });
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo restablecer la contraseña',
                            timer: 2000,
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            }
        });
    }

    // Función para cambiar el estado del usuario
    function toggleStatus(user) {
        console.log(user);
        Swal.fire({
            title: 'Cambiar estado',
            text: `¿Está seguro de cambiar el estado del usuario?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cambiar estado',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Realizar la solicitud para restablecer la contraseña
                $.ajax({
                    url: route('usuarios.toggle-status', {
                        usuario: user
                    }),
                    type: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    },
                    success: function(response) {
                        Swal.fire({
                            title: 'Estado cambiado',
                            text: 'El estado del usuario ha sido cambiado con éxito.',
                            icon: 'success',
                            timer: 3000,
                            confirmButtonText: 'Aceptar'
                        });
                        Livewire.emit('userStatusUpdated');
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            title: 'Error',
                            text: 'Ha ocurrido un error al cambiar el estado del usuario.',
                            icon: 'error',
                            timer: 3000,
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            }
        });
    }
</script>
