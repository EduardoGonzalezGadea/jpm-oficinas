<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function __construct()
    {
        // $this->middleware(['auth', 'permission:roles.index'])->only('index');
        // $this->middleware(['auth', 'permission:roles.create'])->only(['create', 'store']);
        // $this->middleware(['auth', 'permission:roles.edit'])->only(['edit', 'update']);
        // $this->middleware(['auth', 'permission:roles.destroy'])->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Filtros
        $query = Role::with(['permissions', 'users']);

        // Si es gerente o supervisor, solo puede ver roles utilizados en su módulo
        if (!auth()->user()->esAdministrador()) {
            $query->whereHas('users', function ($q) {
                $q->where('modulo_id', auth()->user()->modulo_id);
            });
        }

        // Obtener los roles
        $roles = $query->orderBy('name', 'asc')
            ->paginate(10);

        // Datos adicionales para estadísticas
        $totalPermissions = Permission::count();
        $totalRoles = Role::count();
        $totalUsers = User::count();

        return view('roles.index', compact(
            'roles',
            'totalPermissions',
            'totalRoles',
            'totalUsers',
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permissions = Permission::orderBy('name')->get();

        return view('roles.create', compact('permissions'));
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
                'unique:roles,name',
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

            // Crear el rol
            $role = Role::create([
                'name' => strtolower(trim($request->name)),
                'guard_name' => 'api',
            ]);

            // Asignar permisos si se seleccionaron
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

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        $role->load(['permissions', 'users']);

        return view('roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        $permissions = Permission::orderBy('name')->get();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
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

            // Actualizar el rol
            $role->update([
                'name' => strtolower(trim($request->name))
            ]);

            // Sincronizar permisos
            if ($request->has('permissions') && is_array($request->permissions)) {
                $permissions = Permission::whereIn('id', $request->permissions)->get();
                $role->syncPermissions($permissions);
            } else {
                // Si no se enviaron permisos, remover todos
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        try {
            // Verificar que no sea el rol de administrador
            if ($role->name === 'administrador') {
                return back()->with('error', 'No se puede eliminar el rol de administrador.');
            }

            // Verificar si hay usuarios asignados a este rol
            if ($role->users()->count() > 0) {
                return back()->with(
                    'toast-error',
                    'No se puede eliminar. El rol tiene ' . $role->users()->count() . ' usuarios asignados.'
                );
            }

            DB::beginTransaction();

            // Remover todos los permisos del rol
            $role->syncPermissions([]);

            // Eliminar el rol
            $role->delete();

            DB::commit();

            return redirect()->route('roles.index')
                ->with('swal-success', 'Rol eliminado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error al eliminar el rol: ' . $e->getMessage());
        }
    }

    /**
     * Asignar rol a usuario (API endpoint adicional)
     */
    public function assignToUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id'
        ]);

        try {
            $user = auth()->user()->find($request->user_id);
            $role = Role::find($request->role_id);

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

    /**
     * Remover rol de usuario (API endpoint adicional)
     */
    public function removeFromUser($user_id, $role_id)
    {
        try {
            $user = User::findOrFail($user_id);
            $role = Role::findOrFail($role_id);

            // Evitar que un usuario se quite a sí mismo el rol de administrador
            if ($user->id === auth()->id() && $role->name === 'administrador') {
                return back()->with('error', 'No puedes remover tu propio rol de administrador.');
            }

            $user->removeRole($role);

            return back()->with('success', "Rol '{$role->name}' removido del usuario '{$user->name}' exitosamente.");
        } catch (\Exception $e) {
            return back()->with('error', 'Error al remover el rol: ' . $e->getMessage());
        }
    }
}
