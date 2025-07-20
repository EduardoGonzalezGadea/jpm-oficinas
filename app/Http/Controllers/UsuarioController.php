<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Modulo;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
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

        // Si es supervisor, solo puede ver usuarios de su módulo
        if (auth()->user()->esSupervisor() && !auth()->user()->esAdministrador()) {
            $query->where('modulo_id', auth()->user()->modulo_id);
        }

        $usuarios = $query->latest()->paginate(10);
        $modulos = Modulo::activos()->get();
        $roles = Role::all();

        return view('usuarios.index', compact('usuarios', 'modulos', 'roles'));
    }

    public function create()
    {
        $this->authorize('crear_usuarios');

        $modulos = Modulo::activos()->get();
        $roles = Role::all();

        return view('usuarios.crear', compact('modulos', 'roles'));
    }

    public function store(Request $request)
    {
        $this->authorize('crear_usuarios');

        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'cedula' => 'required|string|unique:users,cedula|max:8',
            'telefono' => 'nullable|string|max:20',
            'password' => 'required|string|min:6|confirmed',
            'modulo_id' => 'nullable|exists:modulos,id',
            'rol' => 'required|exists:roles,name',
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'apellido.required' => 'El apellido es obligatorio',
            'email.required' => 'El email es obligatorio',
            'email.unique' => 'Ya existe un usuario con este email',
            'cedula.required' => 'La cédula es obligatoria',
            'cedula.unique' => 'Ya existe un usuario con esta cédula',
            'password.required' => 'La contraseña es obligatoria',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'rol.required' => 'Debe seleccionar un rol',
        ]);

        $usuario = User::create([
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'email' => $request->email,
            'cedula' => $request->cedula,
            'telefono' => $request->telefono,
            'password' => Hash::make($request->password),
            'modulo_id' => $request->modulo_id,
            'activo' => true,
        ]);

        $usuario->assignRole($request->rol);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario creado exitosamente');
    }

    public function show(User $usuario)
    {
        $this->authorize('ver_usuarios');

        return view('usuarios.mostrar', compact('usuario'));
    }

    public function edit(User $usuario)
    {
        $this->authorize('editar_usuarios');

        $modulos = Modulo::activos()->get();
        $roles = Role::all();

        return view('usuarios.editar', compact('usuario', 'modulos', 'roles'));
    }

    public function update(Request $request, User $usuario)
    {
        $this->authorize('editar_usuarios');

        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $usuario->id,
            'cedula' => 'required|string|unique:users,cedula,' . $usuario->id . '|max:8',
            'telefono' => 'nullable|string|max:20',
            'modulo_id' => 'nullable|exists:modulos,id',
            'rol' => 'required|exists:roles,name',
            'activo' => 'boolean',
        ]);

        $usuario->update([
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'email' => $request->email,
            'cedula' => $request->cedula,
            'telefono' => $request->telefono,
            'modulo_id' => $request->modulo_id,
            'activo' => $request->has('activo'),
        ]);

        // Actualizar rol
        $usuario->syncRoles([$request->rol]);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario actualizado exitosamente');
    }

    public function destroy(User $usuario)
    {
        $this->authorize('eliminar_usuarios');

        if ($usuario->id === auth()->id()) {
            return back()->withErrors(['error' => 'No puedes eliminarte a ti mismo']);
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
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:500', // Agregar validación para dirección
        ]);

        $usuario->update([
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'email' => $request->email,
            'telefono' => $request->telefono,
            'direccion' => $request->direccion, // Agregar dirección si existe en la BD
        ]);

        return back()->with('success', 'Perfil actualizado exitosamente');
    }

    public function cambiarContraseña(Request $request)
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
}