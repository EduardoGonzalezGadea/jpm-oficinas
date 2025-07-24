<div>
    {{-- SweetAlert2 CSS y JS ya están cargados en el layout principal --}}

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
                @if ($modulos->count() > 1)
                    <option value="all">Todos los módulos</option>
                @endif
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

    <!-- Mensajes de sesión (SweetAlert2) -->
    @if (session()->has('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: '{{ session('success') }}',
                timer: 3000,
                showConfirmButton: false
            });
        </script>
    @endif

    @if (session()->has('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '{{ session('error') }}',
                timer: 3000,
                showConfirmButton: false
            });
        </script>
    @endif

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
                                {{-- Botón para ver --}}
                                <a href="{{ route('usuarios.show', $user) }}" class="btn btn-sm btn-info"
                                    title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>

                                <!-- Botón para editar -->
                                <a href="{{ route('usuarios.edit', $user) }}" class="btn btn-sm btn-warning"
                                    title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <!-- Botón para restablecer contraseña -->
                                @if ($user->id !== 1)
                                    <button type="button" class="btn btn-sm btn-secondary"
                                        title="Restablecer contraseña" onclick="confirmResetPassword({{ $user->id }})">
                                        <i class="fas fa-key"></i>
                                    </button>
                                @endif

                                <!-- Botón para cambiar estado -->
                                @if ($user->id !== 1)
                                    <button type="button"
                                        class="btn btn-sm {{ $user->activo ? 'btn-warning' : 'btn-success' }}"
                                        title="{{ $user->activo ? 'Desactivar' : 'Activar' }}"
                                        onclick="confirmToggleStatus({{ $user->id }})">
                                        <i class="fas {{ $user->activo ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                                    </button>
                                @endif

                                <!-- Botón para eliminar -->
                                {{-- La condición ahora usa $currentAuthId del componente --}}
                                @if ($currentAuthId !== $user->id && $user->id !== 1)
                                    <button type="button" class="btn btn-sm btn-danger" title="Eliminar"
                                        onclick="confirmDelete({{ $user->id }})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No se encontraron usuarios.</td>
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

    {{-- Scripts para SweetAlert2 y Livewire --}}
    <script>
        // Función para confirmar y restablecer la contraseña
        function confirmResetPassword(userId) {
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
                    // Emitir evento Livewire para llamar al método del componente
                    Livewire.emit('resetUserPassword', userId);
                }
            });
        }

        // Función para confirmar y cambiar el estado del usuario
        function confirmToggleStatus(userId) {
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
                    // Emitir evento Livewire para llamar al método del componente
                    Livewire.emit('toggleUserStatus', userId);
                }
            });
        }

        // Función para confirmar la eliminación
        function confirmDelete(userId) {
            Swal.fire({
                title: '¿Está seguro?',
                text: '¡No podrás revertir esto!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminarlo!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Emitir evento Livewire para llamar al método del componente
                    Livewire.emit('deleteUser', userId);
                }
            });
        }
    </script>

</div>