<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Modules\ModuleRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:roles.gestionar'])->except(['index', 'show']);
        $this->middleware(['auth', 'permission:roles.gestionar'])->only(['create', 'store', 'edit', 'update', 'destroy']);
        $this->middleware(['auth', 'permission:usuarios.asignar_roles'])->only(['assignToUser', 'removeFromUser']);
    }

    public function index()
    {
        $user = auth()->user();
        $query = Role::with(['permissions', 'users']);

        if (!$user->esAdministrador()) {
            $clave = $user->moduloClave();
            $rolesModulo = ModuleRegistry::rolesDelModulo($clave);
            $query->whereIn('name', $rolesModulo);
        }

        $roles = $query->orderBy('name')->paginate(10);

        $totalPermissions = Permission::count();
        $totalRoles = Role::count();
        $totalUsers = User::count();

        return view('roles.index', compact(
            'roles', 'totalPermissions', 'totalRoles', 'totalUsers',
        ));
    }

    public function create()
    {
        $permissions = Permission::orderBy('name')->get();
        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required', 'string', 'max:255', 'unique:roles,name',
                'regex:/^[a-zA-Z0-9_\-\s]+$/'
            ],
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id'
        ], [
            'name.required' => 'El nombre del rol es obligatorio.',
            'name.unique' => 'Ya existe un rol con ese nombre.',
            'name.regex' => 'El nombre solo puede contener letras, números, guiones y espacios.',
            'permissions.*.exists' => 'Uno o más permisos seleccionados no son válidos.'
        ]);

        try {
            DB::beginTransaction();

            $role = Role::create([
                'name' => strtolower(trim($request->name)),
                'guard_name' => 'web',
            ]);

            if ($request->has('permissions') && is_array($request->permissions)) {
                $permissions = Permission::whereIn('id', $request->permissions)->get();
                $role->syncPermissions($permissions);
            }

            DB::commit();

            return redirect()->route('roles.index')
                ->with('swal-success', 'Rol creado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()
                ->with('toast-error', 'Error al crear el rol: ' . $e->getMessage());
        }
    }

    public function show(Role $role)
    {
        $role->load(['permissions', 'users']);
        return view('roles.show', compact('role'));
    }

    public function edit(Role $role)
    {
        $permissions = Permission::orderBy('name')->get();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        if ($role->name === 'administrador') {
            return back()->with('error', 'No se puede modificar el rol de administrador.');
        }

        $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('roles')->ignore($role->id),
                'regex:/^[a-zA-Z0-9_\-\s]+$/'
            ],
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id'
        ], [
            'name.required' => 'El nombre del rol es obligatorio.',
            'name.unique' => 'Ya existe un rol con ese nombre.',
            'name.regex' => 'El nombre solo puede contener letras, números, guiones y espacios.',
            'permissions.*.exists' => 'Uno o más permisos seleccionados no son válidos.'
        ]);

        try {
            DB::beginTransaction();

            $role->update(['name' => strtolower(trim($request->name))]);

            if ($request->has('permissions') && is_array($request->permissions)) {
                $role->syncPermissions(Permission::whereIn('id', $request->permissions)->get());
            } else {
                $role->syncPermissions([]);
            }

            DB::commit();

            return redirect()->route('roles.index')
                ->with('swal-success', 'Rol actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()
                ->with('error', 'Error al actualizar el rol: ' . $e->getMessage());
        }
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'administrador') {
            return back()->with('error', 'No se puede eliminar el rol de administrador.');
        }

        if ($role->users()->count() > 0) {
            return back()->with(
                'toast-error',
                'No se puede eliminar. El rol tiene ' . $role->users()->count() . ' usuarios asignados.'
            );
        }

        try {
            DB::beginTransaction();
            $role->syncPermissions([]);
            $role->delete();
            DB::commit();

            return redirect()->route('roles.index')
                ->with('swal-success', 'Rol eliminado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error al eliminar el rol: ' . $e->getMessage());
        }
    }

    public function assignToUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id'
        ]);

        try {
            $user = User::findOrFail($request->user_id);
            $role = Role::findOrFail($request->role_id);

            $user->assignRole($role);

            return response()->json([
                'success' => true,
                'message' => 'Rol asignado exitosamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar el rol: ' . $e->getMessage()
            ], 500);
        }
    }

    public function removeFromUser($user_id, $role_id)
    {
        try {
            $user = User::findOrFail($user_id);
            $role = Role::findOrFail($role_id);

            if ($user->id === auth()->id() && $role->name === 'administrador') {
                return back()->with('error', 'No puedes remover tu propio rol de administrador.');
            }

            $user->removeRole($role);

            return back()->with('success', "Rol '{$role->name}' removido exitosamente.");
        } catch (\Exception $e) {
            return back()->with('error', 'Error al remover el rol: ' . $e->getMessage());
        }
    }

    public function getRoleData(Role $role = null)
    {
        $data = [
            'availableRoles' => Role::with('permissions')->orderBy('name')->get(),
        ];

        if ($role) {
            $role->load('permissions');
            $data['role'] = $role;
            $data['rolePermissionIds'] = $role->permissions->pluck('id')->toArray();
        }

        return response()->json($data);
    }

    public function export()
    {
        $roles = Role::with('permissions')->orderBy('name')->get()->map(function ($role) {
            return [
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
            ];
        });

        $filename = 'roles_export_' . date('Y-m-d') . '.json';

        return response()->json($roles)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json',
        ]);

        try {
            DB::beginTransaction();

            $roles = json_decode(file_get_contents($request->file('file')), true);
            $imported = [];
            $existing = [];

            foreach ($roles as $roleData) {
                $role = Role::firstOrCreate([
                    'name' => $roleData['name'],
                    'guard_name' => $roleData['guard_name'] ?? 'web',
                ]);

                if (isset($roleData['permissions']) && is_array($roleData['permissions'])) {
                    $permissions = Permission::whereIn('name', $roleData['permissions'])->get();
                    $role->syncPermissions($permissions);
                }

                if ($role->wasRecentlyCreated) {
                    $imported[] = $roleData['name'];
                } else {
                    $existing[] = $roleData['name'];
                }
            }

            DB::commit();

            $message = 'Proceso de importación completado. ';
            if (count($imported) > 0) {
                $message .= 'Importados: ' . count($imported) . ' roles. ';
            }
            if (count($existing) > 0) {
                $message .= 'Ya existían: ' . count($existing) . ' roles.';
            }

            return redirect()->route('roles.index')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Error al importar los roles: ' . $e->getMessage());
        }
    }

    public function bulkAssignToUsers(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        try {
            $role = Role::findOrFail($request->role_id);

            foreach ($request->user_ids as $userId) {
                $user = User::find($userId);
                $user->assignRole($role);
            }

            return response()->json([
                'success' => true,
                'message' => 'Rol asignado a ' . count($request->user_ids) . ' usuario(s) correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar el rol: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updatePermissions(Request $request, Role $role)
    {
        if ($role->name === 'administrador') {
            return response()->json([
                'success' => false,
                'message' => 'No se pueden modificar los permisos del rol de administrador.',
            ], 403);
        }

        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        try {
            $permissions = Permission::whereIn('id', $request->permissions ?? [])->get();
            $role->syncPermissions($permissions);

            return response()->json([
                'success' => true,
                'message' => 'Permisos del rol actualizados correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar los permisos: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function searchRoles(Request $request)
    {
        $search = $request->get('q', '');

        $roles = Role::where('name', 'like', '%' . $search . '%')
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name']);

        return response()->json($roles);
    }

    public function validateName(Request $request)
    {
        $name = $request->get('name');
        $roleId = $request->get('role_id');

        $query = Role::where('name', $name);

        if ($roleId) {
            $query->where('id', '!=', $roleId);
        }

        $exists = $query->exists();

        return response()->json([
            'valid' => !$exists,
            'message' => $exists ? 'Este nombre de rol ya existe.' : 'Nombre disponible.',
        ]);
    }
}
