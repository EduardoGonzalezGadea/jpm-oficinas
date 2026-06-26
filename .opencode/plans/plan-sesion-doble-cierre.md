# Plan: Unificar JWT_TTL con SESSION_LIFETIME

**Objetivo**: Eliminar la desalineación JWT (60 min) vs Sesión Laravel (180 min) que causa el doble cierre de sesión.

**Fecha**: 2026-06-25

---

## Cambios Requeridos

### 1. `.env` - Unificar valores
**Línea 55-56:**
```diff
- JWT_TTL=60
- JWT_REFRESH_TTL=20160
+ JWT_TTL=180
+ JWT_REFRESH_TTL=28800
+ JWT_BLACKLIST_GRACE_PERIOD=30
```

### 2. `.env.servidor` - Mismos cambios
**Línea 54-55:**
```diff
- JWT_TTL=60
- JWT_REFRESH_TTL=20160
+ JWT_TTL=180
+ JWT_REFRESH_TTL=28800
```

### 3. `.env.example` - Actualizar default
**Línea 26:**
```diff
- SESSION_LIFETIME=120
+ SESSION_LIFETIME=180
```

**Agregar después de la línea 26:**
```
JWT_TTL=180
```

### 4. `config/jwt.php` - Añadir grace period
**Línea 226:**
```diff
- 'blacklist_grace_period' => env('JWT_BLACKLIST_GRACE_PERIOD', 0),
+ 'blacklist_grace_period' => env('JWT_BLACKLIST_GRACE_PERIOD', 30),
```

### 5. `app/Http/Controllers/AuthController.php` - 4 cambios

#### 5a. Login (L105-113) - Limpiar cookie antigua
```php
// Para peticiones web, guardar token en cookie y redirigir
$minutes = (int) config('jwt.ttl', config('auth_session.lifetime_minutes', 1440));
$cookie = cookie('jwt_token', $token, $minutes, '/', null, false, true);
$expiredCookie = cookie()->forget('jwt_token'); // NUEVO: limpiar cookie anterior

// Establecer el token en JWTAuth para la sesión actual
JWTAuth::setToken($token);

return redirect()->intended('/panel')
    ->withCookie($cookie)
    ->withCookie($expiredCookie) // NUEVO
    ->with('success', 'Sesión iniciada exitosamente');
```

#### 5b. Login JSON (L97) - Fix fallback
```diff
- 'expires_in' => config('jwt.ttl', 60) * 60,
+ 'expires_in' => config('jwt.ttl', config('auth_session.lifetime_minutes', 1440)) * 60,
```

#### 5c. Register (L165-175) - Limpiar cookie antigua
```php
// Para peticiones web
$minutes = (int) config('jwt.ttl', config('auth_session.lifetime_minutes', 1440));
$cookie = cookie('jwt_token', $token, $minutes, '/', null, false, true);
$expiredCookie = cookie()->forget('jwt_token'); // NUEVO

// Iniciar sesión al registrarse
Auth::login($user, false);
session()->put('auth.password_confirmed_at', time());
session()->regenerate(true);

return redirect()->route('panel.index')
    ->withCookie($cookie)
    ->withCookie($expiredCookie) // NUEVO
    ->with('success', 'Cuenta creada exitosamente');
```

#### 5d. Register JSON (L159) - Fix fallback
```diff
- 'expires_in' => config('jwt.ttl', 60) * 60,
+ 'expires_in' => config('jwt.ttl', config('auth_session.lifetime_minutes', 1440)) * 60,
```

#### 5e. Refresh (L279, 284) - Fix fallbacks
```diff
- 'expires_in' => config('jwt.ttl', 60) * 60
+ 'expires_in' => config('jwt.ttl', config('auth_session.lifetime_minutes', 1440)) * 60
```

```diff
// Para peticiones web, actualizar cookie
- $minutes = config('jwt.ttl', 60);
+ $minutes = (int) config('jwt.ttl', config('auth_session.lifetime_minutes', 1440));
$cookie = cookie('jwt_token', $token, $minutes, '/', null, false, true);
```

---

## Archivos Afectados

| # | Archivo | Cambio |
|---|---------|--------|
| 1 | `.env` | JWT_TTL=180, JWT_REFRESH_TTL=28800, JWT_BLACKLIST_GRACE_PERIOD=30 |
| 2 | `.env.servidor` | JWT_TTL=180, JWT_REFRESH_TTL=28800 |
| 3 | `.env.example` | SESSION_LIFETIME=180, JWT_TTL=180 |
| 4 | `config/jwt.php` | blacklist_grace_period=30 |
| 5 | `app/Http/Controllers/AuthController.php` | Limpiar cookies + fix fallbacks |

## No Requiere Cambios

- `app.blade.php` - Los timers JavaScript ya usan `config('session.lifetime')` = 180 min, que ahora está alineado con JWT_TTL.
- `config/auth_session.php` - Ya lee `SESSION_LIFETIME` directamente.
- `JWTVerify.php` - Sin cambios necesarios.
- `SessionExpiredResponse.php` - Sin cambios necesarios.

## Después de Implementar

1. Ejecutar `php artisan config:clear`
2. Ejecutar tests: `php artisan test --filter=SessionExpirationTest`
3. Verificar que el login/logout funcionan correctamente
4. Monitorear que no hay más doble cierre de sesión
