# Plan Detallado de Remediación de Seguridad

## Objetivo General

Elevar el nivel de seguridad del proyecto "Oficinas" mediante la implementación de mejores prácticas, fortalecimiento de defensas y mitigación de vulnerabilidades clave, priorizando los riesgos medios, altos y críticos.

---

## Fase 1: Inmediato (Esta Semana)

### 1. Implementación de Headers de Seguridad HTTP

**Descripción:** Crear un nuevo middleware para añadir headers de seguridad HTTP que protejan contra ataques comunes como XSS, clickjacking, MIME-sniffing y cross-site requests.

**Acciones:**

a. Crear `app/Http/Middleware/SecurityHeadersMiddleware.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY', false);
        $response->headers->set('X-XSS-Protection', '1; mode=block', false);
        $response->headers->set('X-Content-Type-Options', 'nosniff', false);
        $response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade', false);
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', false);
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:", false); // Ajustar según necesidades reales

        return $response;
    }
}
```

b. Registrar Middleware en `app/Http/Kernel.php`:
   - Añadir `\App\Http\Middleware\SecurityHeadersMiddleware::class` a la sección `$middleware` (global) o al grupo `web`.
   - **Decisión:** Lo agregaremos al grupo `web` para que aplique a todas las rutas protegidas por sesión.

c. Configurar CSP: La `Content-Security-Policy` inicia con directivas permisivas (`unsafe-inline`, `unsafe-eval`) y se endurecerá en el futuro.

**Verificación:** Inspeccionar headers HTTP de respuestas con herramientas de navegador.

---

### 2. Revisión y Fortalecimiento de `$fillable` / `$guarded` en Modelos

**Descripción:** Asegurar que todos los modelos Eloquent utilizan `$fillable` o `$guarded` de manera estricta para prevenir ataques de "Mass Assignment".

**Acciones:**

a. Auditar todos los modelos en `app/Models/Tesoreria/` y subdirectorios:
   - Para cada modelo, verificar que existe `$fillable` y que lista explícitamente solo los atributos que pueden ser asignados masivamente.
   - Alternativamente, si se usa `$guarded`, asegurar que lista explícitamente todos los atributos que NO deben ser asignados masivamente (o `protected $guarded = ['id'];`).

b. Ejemplo de ajuste para `CajaChica.php` y `TesMultasCobradas.php`: Ya tienen `$fillable` definidos, lo cual es una buena práctica. Se debe replicar este cuidado en todos los demás modelos que manejan datos sensibles.

**Verificación:** Revisión manual del código de cada modelo.

---

### 3. Configuración Segura para Producción

**Descripción:** Asegurar que el archivo `.env` en producción contenga configuraciones de seguridad adecuadas y que los secretos no estén hardcodeados o vacíos.

**Acciones:**

a. Actualizar documentación interna o proceso de despliegue: Incluir estos pasos críticos para configurar `.env` en producción.

b. En entorno de producción (o simularlo):
   - `EXTERNAL_DOWNLOADS_VERIFY_SSL=true` (si la verificación es posible y los certificados son válidos)
   - `SESSION_SECURE_COOKIE=true`
   - `JWT_COOKIE_SECURE=true`
   - `APP_KEY` debe ser generada (`php artisan key:generate`)
   - `JWT_SECRET` debe ser generada (`php artisan jwt:secret` si se usa tymon/jwt-auth o similar, o un valor random único)

**Verificación:** Revisar `.env` en entorno de producción y logs para asegurar la aplicación de la configuración.

---

## Fase 2: Corto Plazo (Este Mes)

### 1. Reestructuración y Auditoría de Rutas

**Descripción:** Descomponer `routes/web.php` y sus require asociados para mejorar la claridad, mantenibilidad y seguridad, facilitando la auditoría de middlewares.

**Acciones:**

a. Inventario de rutas: Ejecutar `php artisan route:list` para obtener una lista completa de rutas y sus middlewares.

b. Separación de rutas:
   - Crear nuevos archivos de ruta por dominios claros (ej. `routes/autenticacion.php`, `routes/sistema.php`).
   - Mover las rutas desde `routes/web.php` a estos nuevos archivos.
   - Asegurar que cada grupo de rutas tenga los middlewares de autenticación, autorización y 2FA correctos aplicados explícitamente.

c. Refactorizar `routes/web.php`: Dejarlo solo como el orquestador de los nuevos archivos de ruta y de las rutas públicas esenciales.

d. Auditoría de acceso: Verificar que cada ruta protegida tiene la cadena de middlewares (`web`, `jwt.verify`, `two-factor`, `modulo`, `permission`, `role`) aplicada correctamente y que ninguna ruta sensible es accesible sin autenticación/autorización.

**Verificación:** Ejecutar `php artisan route:list` y verificar manualmente los middlewares de cada ruta. Crear pruebas de integración para rutas críticas.

---

### 2. Implementación de Tests de Seguridad Básicos

**Descripción:** Introducir tests automatizados para validar políticas de autorización, verificar sanitización de entradas y confirmar el comportamiento de rate limiting.

**Acciones:**

a. **Tests de Políticas (Authorization):**
   - Para cada modelo/recurso crítico (ej. `TesMultasCobradas`, `CajaChica`), crear tests para las políticas (`App\Policies\*Policy`) que aseguren que solo los usuarios autorizados pueden realizar acciones (create, view, update, delete).
   - Ejemplo: `tests/Feature/Tesoreria/MultasCobradasPolicyTest.php`.

b. **Tests de Sanitización (XSS):**
   - Crear tests para inputs de formularios que se muestran en la UI para asegurar que los payloads XSS son correctamente escapados.
   - Ejemplo: `Livewire::test(MultasCobradas::class)->set('nombre', '<script>alert("xss")</script>')->assertSee('alert("xss")\');</script>');`

c. **Tests de Rate Limiting:**
   - Crear tests que confirmen que los endpoints sensibles (ej. `/api/cfe-process`, `/login`) activan el rate limiting después de un número específico de peticiones.

**Verificación:** Todos los nuevos tests de seguridad deben pasar (`php artisan test --filter=Security`). Los tests se deben de agregar en `tests/Feature/Security/`.

---

### 3. Fortalecimiento de la Validación/Sanitización del Parser CFE

**Descripción:** Mejorar la robustez del `CfeProcessorService` para manejar datos potencialmente maliciosos o malformados, reduciendo el riesgo de inyección o errores.

**Acciones:**

a. **Sanitización antes de extracción:** Antes de pasar el texto del PDF a los extractores regex, aplicar una sanitización general para eliminar caracteres no imprimibles o sospechosos.

b. **Validación estricta de DTOs:** Asegurar que los `CfeExtraccionDto` y otros DTOs tienen reglas de validación muy estrictas para cada campo.

c. **Manejo de errores:** Asegurar que cualquier excepción de extracción o validación sea capturada y que el proceso no continúe con datos corruptos.

d. **Alertas:** Implementar alertas automáticas (ej. vía Slack/email) cuando un CFE falla la extracción para una revisión manual.

**Verificación:** Crear tests de unidad e integración con payloads de inyección de ejemplo.

---

## Fase 3: Mediano Plazo (Próximos 2-3 Meses)

### 1. Refactorización de Componentes Livewire (Extracción a Servicios)

**Descripción:** Migrar la lógica de negocio compleja fuera de los componentes Livewire hacia clases de servicio dedicadas (`app/Services/Tesoreria/`) para mejorar la separación de responsabilidades y la seguridad.

**Acciones:**

a. **Identificar "God Components":** Empezar por los más grandes y complejos (ej. `MultasCobradas.php`, `CajaChica/Index.php`).

b. **Crear Servicios Dedicados:** Para cada módulo, crear o extender un servicio que maneje la persistencia, las reglas de negocio, y las interacciones con el repositorio/modelos.

c. **Inyección de Dependencias:** Inyectar estos servicios en los componentes Livewire y delegar la lógica.

d. **Tests Unitarios para Servicios:** Escribir tests de unidad exhaustivos para la lógica de negocio ahora encapsulada en los servicios.

e. **Políticas de Autorización:** Asegurar que la lógica de autorización se centraliza en Políticas y se invoca desde los servicios o componentes (ej. `$this->authorize('update', $multa);`).

**Verificación:** Reducción del tamaño de los componentes Livewire. Aumento de la cobertura de tests unitarios en los servicios.

---

### 2. Logging Seguro y Monitoreo de Alertas

**Descripción:** Mejorar la gestión de logs para evitar la exposición de información sensible y configurar un sistema de monitoreo de alertas de seguridad.

**Acciones:**

a. **Anonymización de Logs:** Implementar un proceso para anonimizar o eliminar información sensible (PII, tokens, credenciales) de los logs antes de escribirlos. Usar un paquete como `spatie/laravel-log-cleanup` o crear un custom formatter.

b. **Rotación de Logs Segura:** Asegurar que los logs se roten de forma segura (cifrados).

c. **Alertas de Seguridad:**
   - Configurar alertas para intentos fallidos de login (demasiados por IP/usuario).
   - Alertas para fallos de autorización (`AuthorizationException`).
   - Alertas para errores en el `CfeProcessorService`.

d. **Spatie Activitylog:** Revisar la configuración del `Activitylog` para excluir atributos sensibles (`protected $hidden` en modelos) o limitar qué se registra en las propiedades.

**Verificación:** Revisar logs para asegurar que no hay fuga de información. Confirmar la recepción de alertas para eventos de seguridad simulados.

---

### 3. Integración con Herramientas de Análisis de Seguridad

**Descripción:** Integrar herramientas de Static Application Security Testing (SAST) y Dynamic Application Security Testing (DAST) en el pipeline de CI/CD.

**Acciones:**

a. **SAST (PHPStan, Psalm):**
   - Integrar PHPStan y/o Psalm con reglas de seguridad específicas (taint analysis con Psalm).
   - Configurar en CI/CD para que fallen builds con vulnerabilidades detectadas.

b. **DAST (OWASP ZAP, Burp Suite):**
   - Explorar la integración de OWASP ZAP en un entorno de staging como parte del CI/CD.
   - Escaneo de vulnerabilidades comunes (XSS, SQLi, etc.).

**Verificación:** Las herramientas de SAST/DAST deben ejecutarse automáticamente y reportar un estado "limpio" o "con advertencias mínimas" antes del despliegue.

---

## Fase 4: Largo Plazo (Continuo / Iterativo)

### 1. Gestión de Secretos Centralizada

**Descripción:** Migrar los secretos (claves de API, credenciales de BD, `JWT_SECRET`) fuera del archivo `.env` a un sistema de gestión de secretos seguro.

**Acciones:**

a. Investigar y seleccionar una solución: AWS Secrets Manager, HashiCorp Vault, Azure Key Vault, etc.

b. Adaptar la aplicación para cargar secretos desde esta fuente.

**Verificación:** No hay secretos sensibles en el control de versiones (`.env` solo contiene referencias).

---

### 2. Hardening del Servidor y Base de Datos

**Descripción:** Aplicar mejores prácticas de seguridad a la infraestructura subyacente (servidor web, base de datos).

**Acciones:**

a. Auditar configuración de Nginx/Apache.

b. Limitar permisos de usuario de base de datos.

c. Configurar firewalls (WAF, IPS).

**Verificación:** Auditoría de infraestructura.

---

### 3. Actualización de Dependencias Continua

**Descripción:** Mantener todas las dependencias (Composer, NPM) actualizadas y parchear vulnerabilidades conocidas.

**Acciones:**

a. `composer update` y `npm update` regularmente.

b. Monitorear feeds de seguridad para Laravel y paquetes utilizados.

**Verificación:** Ejecutar `composer audit` y `npm audit` periódicamente.

---

Este plan es exhaustivo y aborda cada punto de riesgo identificado. Empieza por las acciones más críticas y de más fácil implementación, y escala hacia mejoras arquitectónicas y de infraestructura a largo plazo.
