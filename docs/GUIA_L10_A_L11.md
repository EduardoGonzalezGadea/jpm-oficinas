# Guía Migración Laravel 10 → 11 (Fase 1)

## 🎯 Objetivo

Actualizar de Laravel 10 a Laravel 11 **manteniendo Livewire 3**.

**Duración estimada**: 1-2 semanas  
**Riesgo**: 🟢 BAJO  
**Prioridad**: PRIMERA FASE

---

## ✅ Pre-requisitos

Antes de empezar:
- [x] Tests funcionando (335 passing)
- [ ] Backup de BD
- [ ] Backup de código
- [ ] Rama: `feature/laravel-11-upgrade`

---

## 📋 Cambios Principales en Laravel 11

### 1. Requisitos de PHP
```
Laravel 10: PHP ^8.1
Laravel 11: PHP ^8.1 | ^8.2  (se recomienda 8.2)
```

### 2. Nueva Estructura de Aplicación (Opcional)
Laravel 11 introduce estructura simplificada, pero es **opcional** adoptarla.

**Puedes mantener** la estructura actual de L10 sin problemas.

### 3. Cambios en Service Providers
- `RouteServiceProvider` eliminado (lógica movida a `bootstrap/app.php`)
- Muchos service providers son opcionales ahora

### 4. Middleware Simplificado
- Alias de middleware en `bootstrap/app.php`
- Algunos middleware renombrados

### 5. Cambios en Config
- Algunos archivos de config simplificados
- Nuevas opciones agregadas

---

## 🚀 Pasos de Migración

### Paso 1: Preparación

```bash
# 1. Backup BD
mysqldump -u root -p tesoreria_oficinas > backup_pre_l11_$(date +%Y%m%d).sql

# 2. Crear rama
git checkout -b feature/laravel-11-upgrade

# 3. Tests baseline
php artisan test
# Anotar: 335 passing
```

---

### Paso 2: Actualizar composer.json

```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        "livewire/livewire": "^3.5",
        "bacon/bacon-qr-code": "^3.0",
        "doctrine/dbal": "^3.8",
        "guzzlehttp/guzzle": "^7.8",
        "intervention/image": "^3.11",
        "laravel/sanctum": "^3.3",
        "laravel/tinker": "^2.9",
        "php-open-source-saver/jwt-auth": "^2.7",
        "phpoffice/phpspreadsheet": "^1.29",
        "pragmarx/google2fa-laravel": "^2.3",
        "smalot/pdfparser": "^2.0",
        "spatie/laravel-activitylog": "^4.8",
        "spatie/laravel-backup": "^8.8",
        "spatie/laravel-permission": "^6.3",
        "tightenco/ziggy": "^2.5"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/sail": "^1.29",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.0",
        "phpunit/phpunit": "^10.5",
        "spatie/laravel-ignition": "^2.4"
    }
}
```

**Cambios clave**:
- `laravel/framework`: `^10.0` → `^11.0`
- `php`: `^8.1` → `^8.2`
- `spatie/laravel-permission`: `^5.11` → `^6.3`

---

### Paso 3: Actualizar Dependencias

```bash
# Limpiar caché
composer clear-cache

# Actualizar
composer update

# Si hay conflictos, resolver uno por uno
```

**Conflictos Esperados**:

| Paquete | Solución |
|---------|----------|
| `doctrine/dbal` | Actualizar a `^3.8` |
| `spatie/laravel-permission` | Actualizar a `^6.3` |
| `phpunit/phpunit` | Actualizar a `^10.5` |

---

### Paso 4: Publicar Assets Actualizados

```bash
# Publicar nuevos assets de Laravel 11
php artisan vendor:publish --tag=laravel-assets --ansi --force
```

---

### Paso 5: Actualizar Archivos de Configuración

#### A. config/database.php

Agregar nuevas opciones de MySQL:

```php
'mysql' => [
    'driver' => 'mysql',
    // ... existente ...
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        PDO::ATTR_EMULATE_PREPARES => true,  // ← Nueva opción
    ]) : [],
],
```

#### B. config/logging.php

Laravel 11 tiene nuevos canales de logging. Puedes mantener el actual o actualizar.

---

### Paso 6: Actualizar Middleware (IMPORTANTE)

Laravel 11 cambió cómo se registran los middleware.

#### Opción A: Mantener Estructura L10 (Más Fácil)

Puedes mantener tu `app/Http/Kernel.php` actual sin cambios.

#### Opción B: Migrar a Estructura L11 (Recomendado)

Crear `bootstrap/app.php` actualizado:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Middleware aliases
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            // ... agregar tus middleware personalizados
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

---

### Paso 7: Actualizar Testing

Laravel 11 usa PHPUnit 10, que tiene algunos cambios:

#### phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory suffix=".php">./app</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_DATABASE" value="tesoreria_oficinas_test"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
    </php>
</phpunit>
```

Cambios clave:
- `cacheDirectory` en lugar de `cacheResult`
- `<source>` en lugar de `<filter>`

---

### Paso 8: Actualizar String Helpers (Si los Usas)

Laravel 11 deprecó algunos helpers globales:

```php
// ❌ Deprecado en L11
str_contains($text, 'search');
str_starts_with($text, 'prefix');
str_ends_with($text, 'suffix');

// ✅ Usar en L11
use Illuminate\Support\Str;

Str::contains($text, 'search');
Str::startsWith($text, 'prefix');
Str::endsWith($text, 'suffix');
```

**Buscar y reemplazar**:
```bash
# Buscar usos
grep -r "str_contains" app/
grep -r "str_starts_with" app/
grep -r "str_ends_with" app/

# Reemplazar manualmente usando Str facade
```

---

### Paso 9: Ejecutar Tests

```bash
# Ejecutar todos los tests
php artisan test

# Objetivo: 335 passing
```

**Si fallan tests**:
1. Revisar error específico
2. Consultar [Laravel 11 Upgrade Guide](https://laravel.com/docs/11.x/upgrade)
3. Corregir
4. Re-ejecutar tests

---

### Paso 10: Pruebas Manuales

**Checklist**:
- [ ] Login funciona
- [ ] Navegación general OK
- [ ] Módulos principales accesibles:
  - [ ] Caja Chica
  - [ ] Libro Diario
  - [ ] Multas
  - [ ] CFE
- [ ] Crear un registro de prueba en cada módulo
- [ ] Sin errores en logs

```bash
# Limpiar logs
php artisan log:clear

# Navegar por la aplicación

# Revisar logs
tail -f storage/logs/laravel.log
```

---

### Paso 11: Limpiar y Optimizar

```bash
# Limpiar cachés
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Optimizar
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

### Paso 12: Commit Cambios

```bash
git add .
git commit -m "Actualizar Laravel 10 a 11

- Actualizar dependencias principales
- Mantener Livewire 3
- Tests: 335 passing
- Sin breaking changes observados"

git push origin feature/laravel-11-upgrade
```

---

### Paso 13: Deploy a Staging

```bash
# Merge a staging
git checkout staging
git merge feature/laravel-11-upgrade
git push origin staging

# Deploy en servidor staging
# ... proceso de deploy ...

# Probar exhaustivamente
```

**Monitoreo Staging** (3-5 días):
- [ ] Sin errores en logs
- [ ] Performance aceptable
- [ ] Funcionalidad completa OK
- [ ] Feedback de usuarios beta positivo

---

### Paso 14: Deploy a Producción

Solo después de staging estable por 3-5 días:

```bash
# Merge a main
git checkout main
git merge feature/laravel-11-upgrade
git tag -a v2.0-laravel-11 -m "Laravel 11 en producción"
git push origin main --tags

# Deploy a producción (horario de bajo tráfico)
# ... proceso de deploy ...
```

**Monitoreo Post-Deploy**:
- Primeras 2 horas: monitoreo continuo
- Primer día: revisar cada hora
- Primera semana: revisar diariamente

---

## 🔍 Breaking Changes Comunes L10→L11

### 1. Service Providers Removidos

```php
// L10: app/Providers/RouteServiceProvider.php existía
// L11: Movido a bootstrap/app.php

// Si necesitas personalización de rutas, hacerlo en bootstrap/app.php
```

### 2. Middleware Aliases

```php
// L10: En app/Http/Kernel.php
protected $middlewareAliases = [ ... ];

// L11: En bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([ ... ]);
})
```

### 3. Rate Limiting

```php
// L10: En RouteServiceProvider
RateLimiter::for('api', function (Request $request) { ... });

// L11: En bootstrap/app.php o service provider dedicado
```

---

## ✅ Checklist Final L10→L11

### Pre-Migración
- [x] Tests: 335 passing
- [ ] Backup BD realizado
- [ ] Backup código (tag git)
- [ ] Rama creada

### Durante Migración
- [ ] composer.json actualizado
- [ ] Dependencias actualizadas sin conflictos
- [ ] Configuraciones actualizadas
- [ ] Middleware actualizado (si aplica)
- [ ] Tests: 335 passing post-actualización

### Post-Migración
- [ ] Pruebas manuales OK
- [ ] Deploy staging exitoso
- [ ] Monitoreo staging 3-5 días OK
- [ ] Deploy producción exitoso
- [ ] Monitoreo producción 1 semana OK

---

## 📊 Métricas de Éxito

| Métrica | Objetivo |
|---------|----------|
| Tests passing | 335/335 (100%) |
| Errores en logs | 0 críticos |
| Performance | Igual o mejor |
| Downtime | <5 minutos |
| Rollback necesario | No |

---

## 🚨 Si Algo Sale Mal

### Rollback en Desarrollo

```bash
git checkout main
git branch -D feature/laravel-11-upgrade
# Empezar de nuevo
```

### Rollback en Staging/Producción

```bash
# Volver a versión anterior
git checkout v1.0-laravel-10
composer install
php artisan config:clear
php artisan cache:clear
php artisan optimize
```

---

## 📚 Recursos

- [Laravel 11 Release Notes](https://laravel.com/docs/11.x/releases)
- [Laravel 11 Upgrade Guide](https://laravel.com/docs/11.x/upgrade)
- [What's New in Laravel 11](https://laravel-news.com/laravel-11)

---

## ➡️ Próximo Paso

Una vez L11 está **estable en producción por 1 semana**:

👉 Continuar con: **GUIA_L11_A_L12.md**

---

**Última actualización**: 14/08/2026  
**Estado**: ✅ LISTA PARA USAR  
**Duración estimada**: 1-2 semanas
