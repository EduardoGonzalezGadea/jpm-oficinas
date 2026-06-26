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
}
