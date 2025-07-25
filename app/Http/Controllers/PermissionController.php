<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    public function __construct()
    {
        // $this->middleware(['auth', 'permission:permissions.index'])->only('index');
        // $this->middleware(['auth', 'permission:permissions.create'])->only(['create', 'store']);
        // $this->middleware(['auth', 'permission:permissions.edit'])->only(['edit', 'update']);
        // $this->middleware(['auth', 'permission:permissions.destroy'])->only('destroy');
        // $this->middleware(['auth', 'permission:permissions.show'])->only('show');
    }

    /**
     * Display a listing of the resource with additional statistics.
     */
    public function index(Request $request)
    {
        $query = Permission::with('roles');

        // Filtro por búsqueda
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filtro por módulo
        if ($request->filled('module')) {
            $query->where('name', 'like', $request->module . '.%');
        }

        $permissions = $query->orderBy('name')
            ->paginate(15)
            ->appends($request->query());

        // Obtener módulos únicos para el filtro
        $modules = Permission::select(DB::raw('SUBSTRING_INDEX(name, ".", 1) as module'))
            ->groupBy('module')
            ->orderBy('module')
            ->pluck('module');

        // Datos adicionales para estadísticas
        $totalPermissions = Permission::count();
        $totalRoles = Role::count();
        $totalUsers = User::count();
        $allRoles = Role::with('permissions')->get();

        return view('permisos.index', compact(
            'permissions',
            'modules',
            'totalPermissions',
            'totalRoles',
            'totalUsers',
            'allRoles'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Obtener módulos existentes
        $existingModules = Permission::select(DB::raw('SUBSTRING_INDEX(name, ".", 1) as module'))
            ->groupBy('module')
            ->orderBy('module')
            ->pluck('module')
            ->toArray();

        // Obtener todos los roles para asignación opcional
        $roles = Role::orderBy('name')->get();

        return view('permisos.create', compact('existingModules', 'roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:permissions,name',
                'regex:/^[a-zA-Z0-9_\.]+$/'
            ],
            'guard_name' => 'required|string|in:web,api',
            'roles' => 'sometimes|array',
            'roles.*' => 'exists:roles,id',
            'description' => 'nullable|string|max:500'
        ], [
            'name.required' => 'El nombre del permiso es obligatorio.',
            'name.unique' => 'Ya existe un permiso con ese nombre.',
            'name.regex' => 'El nombre solo puede contener letras, números, puntos y guiones bajos.',
        ]);

        try {
            DB::beginTransaction();

            $permission = Permission::create([
                'name' => strtolower(trim($request->name)),
                'guard_name' => $request->guard_name ?? 'web'
            ]);

            // Asignar roles si se especificaron
            if ($request->filled('roles')) {
                $roles = Role::whereIn('id', $request->roles)->get();
                foreach ($roles as $role) {
                    $role->givePermissionTo($permission);
                }
            }

            DB::commit();

            return redirect()->route('permissions.index')
                ->with('success', 'Permiso creado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()
                ->with('error', 'Error al crear el permiso: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission)
    {
        // Cargar roles que tienen este permiso
        $rolesWithPermission = Role::whereHas('permissions', function ($query) use ($permission) {
            $query->where('id', $permission->id);
        })->with('users')->get();

        // Usuarios que tienen este permiso directamente
        $usersWithDirectPermission = User::whereHas('permissions', function ($query) use ($permission) {
            $query->where('id', $permission->id);
        })->get();

        // Usuarios que tienen este permiso a través de roles
        $usersWithRolePermission = User::whereHas('roles.permissions', function ($query) use ($permission) {
            $query->where('id', $permission->id);
        })->get();

        return view('permisos.show', compact(
            'permission',
            'rolesWithPermission',
            'usersWithDirectPermission',
            'usersWithRolePermission'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Permission $permission)
    {
        // Obtener módulos existentes
        $existingModules = Permission::select(DB::raw('SUBSTRING_INDEX(name, ".", 1) as module'))
            ->groupBy('module')
            ->orderBy('module')
            ->pluck('module')
            ->toArray();

        // Obtener todos los roles
        $roles = Role::orderBy('name')->get();

        return view('permisos.edit', compact('permission', 'existingModules', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions')->ignore($permission->id),
                'regex:/^[a-zA-Z0-9_\.]+$/'
            ],
            'guard_name' => 'required|string|in:web,api',
            'roles' => 'sometimes|array',
            'roles.*' => 'exists:roles,id',
            'description' => 'nullable|string|max:500'
        ], [
            'name.required' => 'El nombre del permiso es obligatorio.',
            'name.unique' => 'Ya existe un permiso con ese nombre.',
            'name.regex' => 'El nombre solo puede contener letras, números, puntos y guiones bajos.',
        ]);

        try {
            DB::beginTransaction();

            $permission->update([
                'name' => strtolower(trim($request->name)),
                'guard_name' => $request->guard_name ?? 'web'
            ]);

            // Sincronizar roles
            $currentRoles = $permission->roles->pluck('id')->toArray();
            $newRoles = $request->roles ?? [];

            // Remover roles que ya no están seleccionados
            $rolesToRemove = array_diff($currentRoles, $newRoles);
            foreach ($rolesToRemove as $roleId) {
                $role = Role::find($roleId);
                if ($role) {
                    $role->revokePermissionTo($permission);
                }
            }

            // Agregar nuevos roles
            $rolesToAdd = array_diff($newRoles, $currentRoles);
            foreach ($rolesToAdd as $roleId) {
                $role = Role::find($roleId);
                if ($role) {
                    $role->givePermissionTo($permission);
                }
            }

            DB::commit();

            return redirect()->route('permissions.index')
                ->with('success', 'Permiso actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()
                ->with('error', 'Error al actualizar el permiso: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        try {
            // Verificar si el permiso está siendo usado por algún rol
            $rolesCount = $permission->roles()->count();

            // Verificar si el permiso está asignado directamente a usuarios
            $usersCount = $permission->users()->count();

            if ($rolesCount > 0 || $usersCount > 0) {
                $message = 'No se puede eliminar el permiso porque está ';
                if ($rolesCount > 0) {
                    $message .= 'asignado a ' . $rolesCount . ' rol(es)';
                }
                if ($usersCount > 0) {
                    if ($rolesCount > 0) $message .= ' y ';
                    $message .= 'asignado directamente a ' . $usersCount . ' usuario(s)';
                }
                $message .= '.';

                return back()->with('error', $message);
            }

            DB::beginTransaction();

            $permission->delete();

            DB::commit();

            return redirect()->route('permissions.index')
                ->with('success', 'Permiso eliminado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error al eliminar el permiso: ' . $e->getMessage());
        }
    }

    /**
     * Crear múltiples permisos para un módulo
     */
    public function bulkCreateForModule(Request $request)
    {
        $request->validate([
            'module_name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9_]+$/'
            ],
            'actions' => 'required|array|min:1',
            'actions.*' => 'string|in:index,create,edit,destroy,show'
        ]);

        try {
            DB::beginTransaction();

            $created = [];
            $existing = [];

            foreach ($request->actions as $action) {
                $permissionName = strtolower($request->module_name) . '.' . $action;

                $permission = Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web'
                ]);

                if ($permission->wasRecentlyCreated) {
                    $created[] = $permissionName;
                } else {
                    $existing[] = $permissionName;
                }
            }

            DB::commit();

            $message = 'Proceso completado. ';
            if (count($created) > 0) {
                $message .= 'Creados: ' . implode(', ', $created) . '. ';
            }
            if (count($existing) > 0) {
                $message .= 'Ya existían: ' . implode(', ', $existing) . '.';
            }

            return redirect()->route('permissions.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()
                ->with('error', 'Error al crear los permisos: ' . $e->getMessage());
        }
    }

    /**
     * Exportar permisos (para backup o migración)
     */
    public function export()
    {
        $permissions = Permission::orderBy('name')->get(['name', 'guard_name']);

        $filename = 'permissions_export_' . date('Y-m-d') . '.json';

        return response()->json($permissions)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Importar permisos (para backup o migración)
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json'
        ]);

        try {
            DB::beginTransaction();

            $permissions = json_decode(file_get_contents($request->file('file')), true);
            $imported = [];
            $existing = [];

            foreach ($permissions as $permission) {
                $perm = Permission::firstOrCreate([
                    'name' => $permission['name'],
                    'guard_name' => $permission['guard_name']
                ]);

                if ($perm->wasRecentlyCreated) {
                    $imported[] = $permission['name'];
                } else {
                    $existing[] = $permission['name'];
                }
            }

            DB::commit();

            $message = 'Proceso de importación completado. ';
            if (count($imported) > 0) {
                $message .= 'Importados: ' . count($imported) . ' permisos. ';
            }
            if (count($existing) > 0) {
                $message .= 'Ya existían: ' . count($existing) . ' permisos.';
            }

            return redirect()->route('permissions.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()
                ->with('error', 'Error al importar los permisos: ' . $e->getMessage());
        }
    }

    /**
     * Obtener datos de permisos para AJAX (para asignar a usuarios)
     */
    public function getPermissionsData(User $usuario = null)
    {
        $data = [
            'availablePermissions' => Permission::orderBy('name')->get()
        ];

        if ($usuario) {
            $data['userDirectPermissions'] = $usuario->getDirectPermissions()->pluck('id')->toArray();
            $data['userAllPermissions'] = $usuario->getAllPermissions()->pluck('id')->toArray();
        }

        return response()->json($data);
    }

    /**
     * Actualizar permisos directos de un usuario
     */
    public function updateUserPermissions(Request $request, User $usuario)
    {
        $request->validate([
            'permissions' => 'sometimes|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        try {
            DB::beginTransaction();

            // Sincronizar permisos directos
            if ($request->has('permissions')) {
                $permissions = Permission::whereIn('id', $request->permissions)->pluck('name')->toArray();
                $usuario->syncPermissions($permissions);
            } else {
                $usuario->syncPermissions([]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permisos del usuario actualizados correctamente.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar los permisos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de permisos
     */
    public function getStats()
    {
        $stats = [
            'total_permissions' => Permission::count(),
            'permissions_by_module' => Permission::select(
                DB::raw('SUBSTRING_INDEX(name, ".", 1) as module'),
                DB::raw('COUNT(*) as count')
            )
                ->groupBy('module')
                ->orderBy('module')
                ->get(),
            'permissions_usage' => Permission::withCount(['roles', 'users'])
                ->orderBy('name')
                ->get(),
            'unused_permissions' => Permission::doesntHave('roles')
                ->doesntHave('users')
                ->count()
        ];

        return response()->json($stats);
    }

    /**
     * Limpiar permisos no utilizados
     */
    public function cleanUnusedPermissions(Request $request)
    {
        try {
            DB::beginTransaction();

            // Obtener permisos que no están asignados a ningún rol ni usuario
            $unusedPermissions = Permission::doesntHave('roles')
                ->doesntHave('users')
                ->get();

            $deletedCount = $unusedPermissions->count();
            $deletedNames = $unusedPermissions->pluck('name')->toArray();

            // Eliminar permisos no utilizados
            Permission::doesntHave('roles')
                ->doesntHave('users')
                ->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Se eliminaron {$deletedCount} permisos no utilizados.",
                'deleted_permissions' => $deletedNames
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar permisos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar permisos para el autocompletado
     */
    public function searchPermissions(Request $request)
    {
        $search = $request->get('q', '');

        $permissions = Permission::where('name', 'like', '%' . $search . '%')
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name']);

        return response()->json($permissions);
    }

    /**
     * Validar nombre de permiso en tiempo real
     */
    public function validateName(Request $request)
    {
        $name = $request->get('name');
        $permissionId = $request->get('permission_id');

        $query = Permission::where('name', $name);

        if ($permissionId) {
            $query->where('id', '!=', $permissionId);
        }

        $exists = $query->exists();

        return response()->json([
            'valid' => !$exists,
            'message' => $exists ? 'Este nombre de permiso ya existe.' : 'Nombre disponible.'
        ]);
    }
}
