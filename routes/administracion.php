<?php

/**
 * routes/administracion.php
 *
 * Rutas del módulo de Administración del sistema:
 * usuarios, roles, permisos, módulos y API interna.
 * Se incluye dentro del grupo JWT de web.php.
 */

use App\Http\Controllers\ModuloController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

// ============================================================================
// GESTIÓN DE USUARIOS
// ============================================================================

Route::prefix('usuarios')->name('usuarios.')->group(function () {
    // CRUD principal
    Route::get('/',       [UsuarioController::class, 'index'])  ->name('index');
    Route::get('crear',   [UsuarioController::class, 'create']) ->name('create');
    Route::post('/',      [UsuarioController::class, 'store'])  ->name('store');

    // Perfil propio (ANTES de las rutas con parámetros)
    Route::get('mi-perfil',              [UsuarioController::class, 'miPerfil'])          ->name('miPerfil');
    Route::put('actualizar-perfil',      [UsuarioController::class, 'actualizarPerfil'])  ->name('actualizarPerfil');
    Route::put('cambiar-contrasena',     [UsuarioController::class, 'cambiarContrasena']) ->name('cambiarContraseña');

    // AJAX y datos
    Route::get('data/roles/{usuario?}',       [UsuarioController::class, 'getRolesData'])       ->name('roles.data');
    Route::get('data/permissions/{usuario?}', [UsuarioController::class, 'getPermissionsData']) ->name('permissions.data');

    // Acciones sobre usuario específico (ANTES de {usuario} genérico)
    Route::post('/{usuario}/resetear-contrasena', [UsuarioController::class, 'resetPassword'])      ->name('reset-password');
    Route::patch('/{usuario}/cambiar-estado',     [UsuarioController::class, 'toggleStatus'])       ->name('toggle-status');
    Route::put('/{usuario}/roles',                [UsuarioController::class, 'updateRoles'])        ->name('roles.update');
    Route::put('/{usuario}/permissions',          [UsuarioController::class, 'updatePermissions']) ->name('permissions.update');

    // CRUD con parámetros (AL FINAL)
    Route::get('/{usuario}',         [UsuarioController::class, 'show'])    ->name('show');
    Route::get('/{usuario}/editar',  [UsuarioController::class, 'edit'])    ->name('edit');
    Route::put('/{usuario}',         [UsuarioController::class, 'update'])  ->name('update');
    Route::delete('/{usuario}',      [UsuarioController::class, 'destroy']) ->name('destroy');
});

// ============================================================================
// GESTIÓN DE ROLES
// ============================================================================

Route::prefix('roles')->name('roles.')->group(function () {
    // CRUD principal
    Route::get('/',     [RoleController::class, 'index'])  ->name('index');
    Route::get('crear', [RoleController::class, 'create']) ->name('create');
    Route::post('/',    [RoleController::class, 'store'])  ->name('store');

    // AJAX, datos y exportación
    Route::get('data/{role?}',   [RoleController::class, 'getRoleData']) ->name('data');
    Route::get('export',         [RoleController::class, 'export'])      ->name('export');
    Route::post('import',        [RoleController::class, 'import'])      ->name('import');

    // Asignaciones
    Route::post('assign-user',                    [RoleController::class, 'assignToUser'])      ->name('assign.user');
    Route::delete('remove-user/{user_id}/{role_id}', [RoleController::class, 'removeFromUser']) ->name('remove.user');
    Route::post('bulk-assign',                    [RoleController::class, 'bulkAssignToUsers']) ->name('bulk.assign');

    // CRUD con parámetros (AL FINAL)
    Route::get('/{role}',              [RoleController::class, 'show'])               ->name('show');
    Route::get('/{role}/editar',       [RoleController::class, 'edit'])               ->name('edit');
    Route::put('/{role}',              [RoleController::class, 'update'])             ->name('update');
    Route::delete('/{role}',           [RoleController::class, 'destroy'])            ->name('destroy');
    Route::put('/{role}/permissions',  [RoleController::class, 'updatePermissions'])  ->name('permissions.update');
});

// ============================================================================
// GESTIÓN DE PERMISOS
// ============================================================================

Route::prefix('permisos')->name('permissions.')->group(function () {
    // CRUD principal
    Route::get('/',     [PermissionController::class, 'index'])  ->name('index');
    Route::get('crear', [PermissionController::class, 'create']) ->name('create');
    Route::post('/',    [PermissionController::class, 'store'])  ->name('store');

    // Utilitarias y exportación
    Route::post('bulk-create',       [PermissionController::class, 'bulkCreateForModule'])   ->name('bulk.create');
    Route::get('export',             [PermissionController::class, 'export'])                ->name('export');
    Route::post('import',            [PermissionController::class, 'import'])               ->name('import');
    Route::get('stats',              [PermissionController::class, 'getStats'])              ->name('stats');
    Route::delete('clean-unused',    [PermissionController::class, 'cleanUnusedPermissions'])->name('clean');

    // AJAX y datos
    Route::get('data/{usuario?}',    [PermissionController::class, 'getPermissionsData'])   ->name('data');

    // Asignación a usuarios
    Route::put('users/{usuario}/permissions', [PermissionController::class, 'updateUserPermissions'])->name('users.update');

    // CRUD con parámetros (AL FINAL)
    Route::get('/{permission}',         [PermissionController::class, 'show'])          ->name('show');
    Route::get('/{permission}/editar',  [PermissionController::class, 'edit'])          ->name('edit');
    Route::put('/{permission}',         [PermissionController::class, 'update'])        ->name('update');
    Route::delete('/{permission}',      [PermissionController::class, 'destroy'])       ->name('destroy');
    Route::get('/{permission}/roles',   [PermissionController::class, 'roles'])         ->name('roles');
    Route::post('/{permission}/roles',  [PermissionController::class, 'assignToRole'])  ->name('assign-role');
});

// ============================================================================
// GESTIÓN DE MÓDULOS
// ============================================================================

Route::prefix('modulos')->name('modulos.')->group(function () {
    Route::get('/',              [ModuloController::class, 'index'])   ->name('index');
    Route::get('crear',          [ModuloController::class, 'create'])  ->name('create');
    Route::post('/',             [ModuloController::class, 'store'])   ->name('store');
    Route::get('/{modulo}',      [ModuloController::class, 'show'])   ->name('show');
    Route::get('/{modulo}/editar', [ModuloController::class, 'edit']) ->name('edit');
    Route::put('/{modulo}',      [ModuloController::class, 'update']) ->name('update');
    Route::delete('/{modulo}',   [ModuloController::class, 'destroy'])->name('destroy');
});

// ============================================================================
// API INTERNA (llamadas AJAX del panel)
// ============================================================================

Route::prefix('api')->name('api.')->group(function () {
    // Dashboard
    Route::get('dashboard/stats', [PanelController::class, 'getDashboardStats'])->name('dashboard.stats');

    // Búsquedas rápidas
    Route::get('search/users',       [UsuarioController::class,  'searchUsers'])      ->name('search.users');
    Route::get('search/roles',       [RoleController::class,     'searchRoles'])      ->name('search.roles');
    Route::get('search/permissions', [PermissionController::class, 'searchPermissions'])->name('search.permissions');

    // Validaciones en tiempo real
    Route::post('validate/user-email',      [UsuarioController::class,   'validateEmail']) ->name('validate.email');
    Route::post('validate/role-name',       [RoleController::class,      'validateName'])  ->name('validate.role');
    Route::post('validate/permission-name', [PermissionController::class, 'validateName']) ->name('validate.permission');
});
