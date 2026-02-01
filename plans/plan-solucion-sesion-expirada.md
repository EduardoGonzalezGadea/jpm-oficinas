# Plan de Solución - Error 500 en lugar de Sesión Expirada

## 🎯 Problema Identificado

Cuando la aplicación permanece mucho tiempo inactiva en el navegador, ahora aparece un **error 500** en lugar del mensaje de **sesión expirada** que se mostraba anteriormente.

## 🔍 Causa Raíz

El problema ocurre porque:

1. **Livewire usa peticiones AJAX** para actualizar componentes
2. Cuando la sesión expira, el middleware [`Authenticate`](app/Http/Middleware/Authenticate.php:15) intenta redirigir a la ruta 'login'
3. **Las peticiones AJAX no siguen redirecciones** automáticamente
4. Livewire recibe la respuesta de redirección como un error 500
5. El usuario ve un error genérico en lugar del mensaje de sesión expirada

## 📋 Solución Propuesta

### Opción 1: Manejar excepciones de autenticación en Handler.php (Recomendada)

Esta solución captura las excepciones de autenticación y devuelve una respuesta JSON apropiada para peticiones AJAX/Livewire.

#### Archivo a modificar: [`app/Exceptions/Handler.php`](app/Exceptions/Handler.php:1)

```php
<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('livewire/*')) {
                return response()->json([
                    'message' => 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.',
                    'redirect' => route('login')
                ], 401);
            }
        });
    }
}
```

### Opción 2: Modificar el middleware Authenticate

Esta solución modifica el middleware para detectar peticiones Livewire y devolver una respuesta JSON.

#### Archivo a modificar: [`app/Http/Middleware/Authenticate.php`](app/Http/Middleware/Authenticate.php:1)

```php
<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // Detectar si es una petición de Livewire o AJAX
        if ($request->expectsJson() || $request->is('livewire/*')) {
            // No redirigir, dejar que el Handler maneje la excepción
            return null;
        }

        if (! $request->expectsJson()) {
            return route('login');
        }
    }
}
```

### Opción 3: Agregar JavaScript para manejar redirecciones en Livewire

Esta solución agrega código JavaScript en el layout principal para detectar respuestas de sesión expirada y redirigir al login.

#### Archivo a modificar: [`resources/views/layouts/app.blade.php`](resources/views/layouts/app.blade.php)

```javascript
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.hook('request.failed', (status, content) => {
            if (status === 401 || (status === 500 && content.message && content.message.includes('sesión'))) {
                alert('Tu sesión ha expirado. Serás redirigido a la página de inicio de sesión.');
                window.location.href = '{{ route('login') }}';
            }
        });
    });
</script>
```

## 🏆 Solución Recomendada

**Opción 1: Manejar excepciones de autenticación en Handler.php**

### Razones:
1. ✅ Es la solución más limpia y sigue las mejores prácticas de Laravel
2. ✅ Centraliza el manejo de excepciones de autenticación
3. ✅ Funciona para todas las peticiones AJAX, no solo Livewire
4. ✅ Devuelve un código de estado HTTP apropiado (401 Unauthorized)
5. ✅ Permite al frontend manejar la redirección de forma elegante

## 📝 Pasos de Implementación

1. **Modificar [`app/Exceptions/Handler.php`](app/Exceptions/Handler.php:1)**
   - Agregar el método `renderable` para manejar `AuthenticationException`
   - Detectar peticiones AJAX/Livewire
   - Devolver respuesta JSON con mensaje de sesión expirada

2. **Probar la solución**
   - Iniciar sesión en la aplicación
   - Esperar a que la sesión expire (o eliminar la cookie de sesión manualmente)
   - Intentar realizar una acción en Livewire
   - Verificar que se muestra el mensaje de sesión expirada

3. **Opcional: Agregar JavaScript para redirección automática**
   - Si se desea redirigir automáticamente al login, agregar el código JavaScript en el layout

## 🔧 Configuración Adicional

Si deseas ajustar el tiempo de expiración de la sesión, puedes modificar [`config/session.php`](config/session.php:34):

```php
'lifetime' => env('SESSION_LIFETIME', 1440), // 1440 minutos = 24 horas
```

O establecer que la sesión expire al cerrar el navegador:

```php
'expire_on_close' => true,
```

## 📊 Resultado Esperado

| Situación | Antes | Después |
|-----------|-------|---------|
| Sesión expirada + petición normal | Redirección a login | Redirección a login ✅ |
| Sesión expirada + petición Livewire | Error 500 ❌ | Mensaje de sesión expirada + redirección ✅ |
| Sesión expirada + petición AJAX | Error 500 ❌ | JSON 401 con mensaje ✅ |

---

**Documento generado:** 2026-02-01  
**Versión:** 1.0  
**Estado:** Aprobado para implementación
