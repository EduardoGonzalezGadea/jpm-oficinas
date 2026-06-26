<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Modulo;
use App\Models\User;
use App\Modules\ModuleRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('usuarios.ver');

        $user = auth()->user();
        $query = User::with(['modulo', 'roles'])->where('id', '!=', 1);

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('apellido', 'like', "%{$buscar}%")
                    ->orWhere('email', 'like', "%{$buscar}%")
                    ->orWhere('cedula', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('modulo')) {
            $query->where('modulo_id', $request->modulo);
        }

        if ($request->filled('rol')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $request->rol));
        }

        // Scoping: no-admin solo ve usuarios de su módulo
        if (!$user->esAdministrador()) {
            $query->delModulo($user->moduloClave());
        }

        $usuarios = $query->latest()->paginate(10);
        $modulos = Modulo::activos()->get();
        $roles = Role::all();

        if ($user->esAdministrador()) {
            $totalPermissions = Permission::count();
            $totalRoles = Role::count();
            $totalUsers = User::count();
        } else {
            $totalPermissions = 0;
            $totalRoles = 0;
            $totalUsers = 0;
        }

        return view('usuarios.index', compact(
            'usuarios', 'modulos', 'roles',
            'totalPermissions', 'totalRoles', 'totalUsers',
        ));
    }

    public function create()
    {
        $this->authorize('usuarios.crear');

        $user = auth()->user();

        if ($user->esAdministrador()) {
            $modulos = Modulo::activos()->get();
            $roles = Role::all();
        } else {
            $clave = $user->moduloClave();
            $modulos = Modulo::where('clave', $clave)->get();
            $roles = Role::whereIn('name', ModuleRegistry::rolesDelModulo($clave))->get();
        }

        return view('usuarios.create', compact('modulos', 'roles'));
    }

    public function store(StoreUserRequest $request)
    {
        $validatedData = $request->validated();

        DB::transaction(function () use ($validatedData) {
            $usuario = User::create([
                'nombre' => $validatedData['nombre'],
                'apellido' => $validatedData['apellido'],
                'email' => $validatedData['email'],
                'cedula' => $validatedData['cedula'] ?? null,
                'telefono' => $validatedData['telefono'] ?? null,
                'direccion' => $validatedData['direccion'] ?? null,
                'password' => Hash::make('123456'),
                'modulo_id' => $validatedData['modulo_id'] ?? null,
                'activo' => true,
            ]);

            if (isset($validatedData['roles'])) {
                $usuario->syncRoles($validatedData['roles']);
            }
        });

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario creado exitosamente');
    }

    public function show(User $usuario)
    {
        $this->authorize('usuarios.ver');
        $this->verificarAccesoModulo($usuario);

        return view('usuarios.show', compact('usuario'));
    }

    public function edit(User $usuario)
    {
        $this->authorize('usuarios.editar');
        $this->verificarAccesoModulo($usuario);

        $user = auth()->user();

        if ($user->esAdministrador()) {
            $modulos = Modulo::activos()->get();
            $roles = Role::all();
        } else {
            $clave = $user->moduloClave();
            $modulos = Modulo::where('clave', $clave)->get();
            $roles = Role::whereIn('name', ModuleRegistry::rolesDelModulo($clave))->get();
        }

        $usuario->load('roles');
        $usuarioRoles = $usuario->roles->pluck('name')->toArray();

        return view('usuarios.edit', compact('usuario', 'modulos', 'roles', 'usuarioRoles'));
    }

    public function update(UpdateUserRequest $request, User $usuario)
    {
        $this->verificarAccesoModulo($usuario);

        $validatedData = $request->validated();

        DB::transaction(function () use ($request, $usuario, $validatedData) {
            $usuario->update([
                'nombre' => $validatedData['nombre'],
                'apellido' => $validatedData['apellido'],
                'email' => $validatedData['email'],
                'cedula' => $validatedData['cedula'] ?? null,
                'telefono' => $validatedData['telefono'] ?? null,
                'direccion' => $validatedData['direccion'] ?? null,
                'modulo_id' => $validatedData['modulo_id'] ?? null,
                'activo' => $validatedData['activo'] ?? false,
            ]);

            if (isset($validatedData['roles'])) {
                $usuario->syncRoles($validatedData['roles']);
            } else {
                $usuario->syncRoles([]);
            }
        });

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario actualizado exitosamente');
    }

    public function destroy(User $usuario)
    {
        $this->authorize('usuarios.eliminar');

        if ($usuario->id === auth()->id()) {
            return back()->withErrors(['error' => 'No puedes eliminarte a ti mismo']);
        }

        if ($usuario->id === 1) {
            return back()->withErrors(['error' => 'No puedes eliminar el administrador principal']);
        }

        $this->verificarAccesoModulo($usuario);

        $usuario->delete();

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario eliminado exitosamente');
    }

    public function miPerfil()
    {
        return view('usuarios.perfil', ['usuario' => auth()->user()]);
    }

    public function actualizarPerfil(Request $request)
    {
        $usuario = auth()->user();

        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $usuario->id,
            'telefono' => 'nullable|string|max:30',
            'direccion' => 'nullable|string|max:500',
        ]);

        $usuario->update($request->only(['nombre', 'apellido', 'email', 'telefono', 'direccion']));

        return back()->with('success', 'Perfil actualizado exitosamente');
    }

    public function cambiarContrasena(Request $request)
    {
        $request->validate([
            'contraseña_actual' => 'required',
            'nueva_contraseña' => 'required|string|min:6|confirmed',
        ], [
            'contraseña_actual.required' => 'La contraseña actual es obligatoria',
            'nueva_contraseña.required' => 'La nueva contraseña es obligatoria',
            'nueva_contraseña.confirmed' => 'Las contraseñas no coinciden',
        ]);

        $usuario = auth()->user();

        if (!Hash::check($request->contraseña_actual, $usuario->password)) {
            return back()->withErrors(['contraseña_actual' => 'La contraseña actual es incorrecta']);
        }

        $usuario->update(['password' => Hash::make($request->nueva_contraseña)]);

        return back()->with('success', 'Contraseña cambiada exitosamente');
    }

    public function resetPassword(Request $request, User $usuario)
    {
        $this->authorize('usuarios.gestionar');
        $this->verificarAccesoModulo($usuario);

        $usuario->update(['password' => Hash::make('123456')]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Contraseña restablecida a: 123456',
            ], 200);
        }

        return redirect()->route('usuarios.index')
            ->with('success', 'Contraseña restablecida a: 123456');
    }

    public function toggleStatus(Request $request, User $usuario)
    {
        $this->authorize('usuarios.gestionar');
        $this->verificarAccesoModulo($usuario);

        $usuario->update(['activo' => !$usuario->activo]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Usuario ' . ($usuario->activo ? 'activado' : 'desactivado') . ' exitosamente.',
            ], 200);
        }

        return redirect()->route('usuarios.index')
            ->with('success', "Usuario " . ($usuario->activo ? 'activado' : 'desactivado') . " exitosamente.");
    }

    protected function verificarAccesoModulo(User $usuario): void
    {
        $user = auth()->user();
        if ($user->esAdministrador()) return;

        abort_if(
            $usuario->moduloClave() !== $user->moduloClave(),
            403,
            'No puedes gestionar usuarios de otro módulo'
        );
    }
}
