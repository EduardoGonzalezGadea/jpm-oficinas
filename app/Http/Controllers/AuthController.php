<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Mostrar formulario de login
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Procesar login y generar JWT token
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Credenciales inválidas'], 401);
                }
                return redirect()->back()->with('error', 'Credenciales inválidas')->withInput();
            }
        } catch (JWTException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No se pudo crear el token: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Error del servidor: ' . $e->getMessage())->withInput();
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Error inesperado: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Error inesperado')->withInput();
        }

        // Obtener usuario autenticado
        $user = JWTAuth::user();

        // Inicia una sesión de Laravel tradicional para este usuario.
        // Esto creará la cookie de sesión que Livewire necesita.
        Auth::login($user);

        if ($request->expectsJson()) {
            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl', 60) * 60,
                'user' => $user,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name')
            ]);
        }

        // Para peticiones web, guardar token en cookie y redirigir
        $minutes = config('jwt.ttl', 60);
        $cookie = cookie('jwt_token', $token, $minutes, '/', null, false, true); // httpOnly = true

        return redirect()->intended('/panel')
            ->with('success', 'Sesión iniciada exitosamente')
            ->withCookie($cookie);
    }

    /**
     * Mostrar formulario de registro
     */
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    /**
     * Procesar registro
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Asignar rol por defecto si existe
        if ($user) {
            $user->assignRole('user'); // Asume que existe un rol 'user'
        }

        $token = JWTAuth::fromUser($user);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Usuario creado exitosamente',
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl', 60) * 60,
                'user' => $user,
            ], 201);
        }

        // Para peticiones web
        $minutes = config('jwt.ttl', 60);
        $cookie = cookie('jwt_token', $token, $minutes, '/', null, false, true);

        return redirect()->route('panel')
            ->with('success', 'Cuenta creada exitosamente')
            ->withCookie($cookie);
    }

    /**
     * Obtener usuario autenticado
     */
    public function me()
    {
        try {
            $user = JWTAuth::user();

            if (!$user) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }

            return response()->json([
                'user' => $user,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name')
            ]);
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'La sesión ha expirado'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'La sesión es inválida'], 401);
        } catch (JWTException $e) {
            return response()->json(['error' => 'La sesión ha expirado o es inválida'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error del servidor'], 500);
        }
    }

    /**
     * Logout (invalidar token)
     */
    public function logout(Request $request)
    {
        try {
            $token = JWTAuth::getToken();
            if ($token) {
                JWTAuth::invalidate($token);
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesión cerrada exitosamente']);
            }

            // Para peticiones web, eliminar cookie y redirigir
            $cookie = cookie()->forget('jwt_token');

            return redirect()->route('login')
                ->with('success', 'Sesión cerrada exitosamente')
                ->withCookie($cookie);
        } catch (TokenExpiredException $e) {
            // Token ya expirado, continuar con logout
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesión cerrada exitosamente']);
            }

            $cookie = cookie()->forget('jwt_token');
            return redirect()->route('login')
                ->with('success', 'Sesión cerrada')
                ->withCookie($cookie);
        } catch (JWTException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Error al cerrar sesión: ' . $e->getMessage()], 500);
            }

            return redirect()->route('login')
                ->with('error', 'Error al cerrar sesión');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Error inesperado'], 500);
            }

            return redirect()->route('login')
                ->with('error', 'Error inesperado');
        }
    }

    /**
     * Refrescar token
     */
    public function refresh(Request $request)
    {
        try {
            $oldToken = JWTAuth::getToken();
            if (!$oldToken) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'No se pudo reactivar la sesión'], 401);
                }
                return redirect()->route('login')->with('error', 'No se pudo reactivar la sesión');
            }

            $token = JWTAuth::refresh($oldToken);

            if ($request->expectsJson()) {
                return response()->json([
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => config('jwt.ttl', 60) * 60
                ]);
            }

            // Para peticiones web, actualizar cookie
            $minutes = config('jwt.ttl', 60);
            $cookie = cookie('jwt_token', $token, $minutes, '/', null, false, true);
            return response()->json(['success' => true])->withCookie($cookie);
        } catch (TokenExpiredException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Token expirado y no se puede refrescar'], 401);
            }
            return redirect()->route('login')
                ->with('error', 'Sesión expirada, inicia sesión nuevamente');
        } catch (TokenInvalidException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Token inválido'], 401);
            }
            return redirect()->route('login')
                ->with('error', 'Token inválido, inicia sesión nuevamente');
        } catch (JWTException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No se pudo refrescar el token: ' . $e->getMessage()], 401);
            }
            return redirect()->route('login')
                ->with('error', 'Error al refrescar sesión');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Error del servidor'], 500);
            }
            return redirect()->route('login')
                ->with('error', 'Error del servidor');
        }
    }

    /**
     * Mostrar formulario de recuperación de contraseña
     */
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Procesar solicitud de recuperación
     */
    public function forgotPassword(Request $request)
    {
        // Implementar lógica de recuperación de contraseña
        // ...

        return redirect()->back()->with('success', 'Se ha enviado un enlace de recuperación a tu email');
    }

    /**
     * Mostrar formulario de reseteo de contraseña
     */
    public function showResetPasswordForm($token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    /**
     * Procesar reseteo de contraseña
     */
    public function resetPassword(Request $request)
    {
        // Implementar lógica de reseteo de contraseña
        // ...

        return redirect()->route('login')->with('success', 'Contraseña actualizada exitosamente');
    }
}
