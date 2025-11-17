<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    public function __construct()
    {
        // $this->middleware('jwt.verify');
    }

    public function index(Request $request)
    {
        $this->authorize('ver_usuarios');

        $query = User::with(['modulo', 'roles']);

        // Filtros
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
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->rol);
            });
        }

        // Si es gerente o supervisor, solo puede ver usuarios de su módulo
        if (auth()->user()->esGerente() || auth()->user()->esSupervisor() && !auth()->user()->esAdministrador()) {
            $query->where('modulo_id', auth()->user()->modulo_id);
        }

        $usuarios = $query->latest()->paginate(10);

        $modulos = Cache::remember('modulos_activos', now()->addDay(), function () {
            return Modulo::activos()->get();
        });

        $roles = Cache::remember('roles_all', now()->addDay(), function () {
            return Role::all();
        });

        // Datos adicionales para estadísticas
        $totalPermissions = Permission::count();
        $totalRoles = Role::count();
        $totalUsers = User::count();

        return view('usuarios.index', compact(
            'usuarios',
            'modulos',
            'roles',
            'totalPermissions',
            'totalRoles',
            'totalUsers',
        ));
    }

    public function create()
    {
        $this->authorize('crear_usuarios');

        $modulos = Cache::remember('modulos_activos', now()->addDay(), function () {
            return Modulo::activos()->get();
        });

        $roles = Cache::remember('roles_all', now()->addDay(), function () {
            return Role::all();
        });

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
                'password' => Hash::make('123456'), // Default password
                'modulo_id' => $validatedData['modulo_id'] ?? null,
                'activo' => true,
            ]);

            // Asignar roles
            if (isset($validatedData['roles'])) {
                $usuario->syncRoles($validatedData['roles']);
            }
        });

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario creado exitosamente');
    }

    public function show(User $usuario)
    {
        $this->authorize('ver_usuarios');

        return view('usuarios.show', compact('usuario'));
    }

    public function edit(User $usuario)
    {
        $this->authorize('editar_usuarios');

        if (auth()->user()->esAdministrador()) {
            $modulos = Cache::remember('modulos_activos', now()->addDay(), function () {
                return Modulo::activos()->get();
            });
        } else {
            $modulos = Modulo::activos()->where('id', auth()->user()->modulo_id)->get();
        }

        if (auth()->user()->esAdministrador()) {
            $roles = Cache::remember('roles_all', now()->addDay(), function () {
                return Role::all();
            });
        } else {
            $userRoleName = $usuario->getRoleNames()->first();
            $parts = explode('_', $userRoleName, 2);
            $moduleIdentifier = $parts[1];

            $roles = Role::where('name', '!=', 'administrador')
                        ->where('name', 'like', '%' . $moduleIdentifier . '%')
                        ->get();
        }
        $usuario->load('roles');
        $usuarioRoles = $usuario->roles->pluck('name')->toArray();

        return view('usuarios.edit', compact('usuario', 'modulos', 'roles', 'usuarioRoles'));
    }

    public function update(UpdateUserRequest $request, User $usuario)
    {
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

            // Sincronizar roles
            if (isset($validatedData['roles'])) {
                $usuario->syncRoles([$validatedData['roles']]);
            } else {
                $usuario->syncRoles([]);
            }
        });

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario actualizado exitosamente');
    }

    public function destroy(User $usuario)
    {
        $this->authorize('eliminar_usuarios');

        if ($usuario->id === auth()->id()) {
            return back()->withErrors(['error' => 'No puedes eliminarte a ti mismo']);
        }

        if ($usuario->id === 1) {
            return back()->withErrors(['error' => 'No puedes eliminar este usuario, porque es el administrador']);
        }

        $usuario->delete();

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario eliminado exitosamente');
    }

    public function miPerfil()
    {
        $usuario = auth()->user();
        // Eliminar el dd() que causa problemas
        return view('usuarios.perfil', compact('usuario'));
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

        $usuario->update([
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'email' => $request->email,
            'telefono' => $request->telefono,
            'direccion' => $request->direccion,
        ]);

        return back()->with('success', 'Perfil actualizado exitosamente');
    }

    public function cambiarContrasena(Request $request)
    {
        $request->validate([
            'contraseña_actual' => 'required', // Corregir nombre del campo
            'nueva_contraseña' => 'required|string|min:6|confirmed', // Corregir nombre del campo
        ], [
            'contraseña_actual.required' => 'La contraseña actual es obligatoria',
            'nueva_contraseña.required' => 'La nueva contraseña es obligatoria',
            'nueva_contraseña.confirmed' => 'Las contraseñas no coinciden',
        ]);

        $usuario = auth()->user();

        if (!Hash::check($request->contraseña_actual, $usuario->password)) {
            return back()->withErrors(['contraseña_actual' => 'La contraseña actual es incorrecta']);
        }

        $usuario->update([
            'password' => Hash::make($request->nueva_contraseña)
        ]);

        return back()->with('success', 'Contraseña cambiada exitosamente');
    }

    public function resetPassword(Request $request, User $usuario)
    {
        // Autorización: Asegúrate de que el usuario autenticado tiene permiso para gestionar usuarios.
        $this->authorize('gestionar_usuarios');

        try {
            // Actualizar la contraseña del usuario a '123456'
            $usuario->update([
                'password' => Hash::make('123456')
            ]);

            // Comprobar si la solicitud es una petición AJAX
            if ($request->ajax()) {
                // Si es AJAX, devolver una respuesta JSON de éxito.
                return response()->json([
                    'success' => true,
                    'message' => 'Contraseña restablecida a: 123456',
                    'usuario' => $usuario
                ], 200);
            }

            // Si no es AJAX (es una solicitud web normal), redirigir con un mensaje de éxito.
            return redirect()->route('usuarios.index')
                ->with('success', 'Contraseña restablecida a: 123456');
        } catch (\Exception $e) {
            // Opcional: Registrar el error para depuración
            Log::error('Error al restablecer la contraseña del usuario ' . $usuario->id . ': ' . $e->getMessage());

            // Manejar errores para solicitudes AJAX
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ha ocurrido un error al restablecer la contraseña.',
                    'error' => $e->getMessage() // Opcional: para depuración, no mostrar en producción
                ], 500); // Código de estado HTTP 500 Internal Server Error
            }

            // Manejar errores para solicitudes web normales
            return redirect()->back() // Redirige a la página anterior
                ->with('error', 'Ha ocurrido un error al restablecer la contraseña. Inténtalo de nuevo.');
        }
    }

    public function toggleStatus(Request $request, User $usuario)
    {
        $this->authorize('gestionar_usuarios');

        $usuario->update(['activo' => !$usuario->activo]);

        // Comprobar si la solicitud es una petición AJAX
        if ($request->ajax()) {
            // Si es AJAX, devolver una respuesta JSON de éxito.
            return response()->json([
                'success' => true,
                'message' => 'Usuario ' . ($usuario->activo ? 'activado' : 'desactivado') . ' exitosamente.',
                'user' => $usuario
            ], 200);
        }

        $status = $usuario->activo ? 'activado' : 'desactivado';
        return redirect()->route('usuarios.index')
            ->with('success', "Usuario {$status} exitosamente.");
    }
}