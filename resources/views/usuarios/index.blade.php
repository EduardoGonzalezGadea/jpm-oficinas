@extends('layouts.app')

@section('titulo', 'Gestión de Usuarios')

@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0 ml-2">
                        <strong>Gestión de Usuarios</strong>
                    </h4>
                    <div>
                        <!-- Botones de gestión -->
                        <div class="btn-group me-2" role="group">
                            <a href="{{ route('roles.index') }}" class="btn btn-info">
                                <i class="fas fa-user-tag"></i> Gestionar Roles
                            </a>
                            <a href="{{ route('permissions.index') }}" class="btn btn-warning">
                                <i class="fas fa-key"></i> Gestionar Permisos
                            </a>
                        </div>
                        <a href="{{ route('usuarios.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Usuario
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    {{-- Livewire component for the users table --}}
                    @livewire('users-table')

                    <!-- Panel de estadísticas rápidas -->
                    <hr class="my-4">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-bar mr-2"></i>
                                Estadísticas Rápidas
                            </h5>
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-user-tag fa-2x mb-2"></i>
                                    <h5>Roles Activos</h5>
                                    <h3>{{ $totalRoles ?? 0 }}</h3>
                                    <a href="{{ route('roles.index') }}" class="btn btn-sm btn-outline-light mt-2">
                                        Gestionar <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-key fa-2x mb-2"></i>
                                    <h5>Permisos</h5>
                                    <h3>{{ $totalPermissions ?? 0 }}</h3>
                                    <a href="{{ route('permissions.index') }}" class="btn btn-sm btn-outline-light mt-2">
                                        Gestionar <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-users fa-2x mb-2"></i>
                                    <h5>Total Usuarios</h5>
                                    <h3 id="totalUsuarios" class="mt-3 mb-3">{{ $totalUsers ?? 0 }}</h3>
                                    <div class="mt-2 text-sm">Gestión completa</div>
                                </div>
                            </div>
                        </div>
                        <small>
                            <em>
                                <i class="fas fa-info-circle"></i>
                                Las estadísticas se actualizan en tiempo real y son totales generales de la información almacenada en la base de datos.
                            </em>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para gestionar roles de usuario -->
<div class="modal fade" id="manageRolesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gestionar Roles de Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="manageRolesForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <p>Usuario: <strong id="userNameRoles"></strong></p>
                    <div id="rolesCheckboxes">
                        <!-- Se llenará dinámicamente -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para gestionar permisos de usuario -->
<div class="modal fade" id="managePermissionsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gestionar Permisos Directos de Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="managePermissionsForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <p>Usuario: <strong id="userNamePermissions"></strong></p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Los permisos aquí mostrados son adicionales a los que ya tiene por sus roles.
                    </div>
                    <div id="permissionsCheckboxes">
                        <!-- Se llenará dinámicamente -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
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
                ¿Está seguro de que desea eliminar este usuario?
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

<script>
// Datos para los modales (deberían venir del controlador)
const roles = @json($allRoles ?? []);
const permissions = @json($allPermissions ?? []);

function manageUserRoles(userId, userName) {
    document.getElementById('userNameRoles').textContent = userName;
    document.getElementById('manageRolesForm').action = `/usuarios/${userId}/roles`;
    
    // Obtener roles actuales del usuario (necesitarías hacer una petición AJAX o tener los datos)
    fetch(`/usuarios/${userId}/roles-data`)
        .then(response => response.json())
        .then(data => {
            let html = '';
            roles.forEach(role => {
                const checked = data.userRoles.includes(role.id) ? 'checked' : '';
                html += `
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" value="${role.id}" 
                               id="role_${role.id}" name="roles[]" ${checked}>
                        <label class="form-check-label" for="role_${role.id}">
                            <strong>${role.name}</strong>
                            <br><small class="text-muted">${role.permissions ? role.permissions.length : 0} permisos</small>
                        </label>
                    </div>
                `;
            });
            document.getElementById('rolesCheckboxes').innerHTML = html;
        });
    
    new bootstrap.Modal(document.getElementById('manageRolesModal')).show();
}

function manageUserPermissions(userId, userName) {
    document.getElementById('userNamePermissions').textContent = userName;
    document.getElementById('managePermissionsForm').action = `/usuarios/${userId}/permissions`;
    
    // Obtener permisos directos actuales del usuario
    fetch(`/usuarios/${userId}/permissions-data`)
        .then(response => response.json())
        .then(data => {
            let html = '';
            permissions.forEach(permission => {
                const checked = data.userDirectPermissions.includes(permission.id) ? 'checked' : '';
                html += `
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" value="${permission.id}" 
                               id="permission_${permission.id}" name="permissions[]" ${checked}>
                        <label class="form-check-label" for="permission_${permission.id}">
                            <code>${permission.name}</code>
                        </label>
                    </div>
                `;
            });
            document.getElementById('permissionsCheckboxes').innerHTML = html;
        });
    
    new bootstrap.Modal(document.getElementById('managePermissionsModal')).show();
}

function deleteUser(id) {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    document.getElementById('deleteForm').action = `/usuarios/${id}`;
    modal.show();
}
</script>
@endsection