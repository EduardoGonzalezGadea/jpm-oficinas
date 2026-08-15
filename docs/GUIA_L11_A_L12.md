# Guía Migración Laravel 11 → 12 (Fase 2)

## 🎯 Objetivo

Actualizar de Laravel 11 a Laravel 12 **manteniendo Livewire 3**.

**Duración estimada**: 1 semana  
**Riesgo**: 🟢 BAJO (porque ya estás en L11)  
**Prioridad**: SEGUNDA FASE

---

## ✅ Pre-requisitos

Antes de empezar:
- [x] Laravel 11 funcionando en producción
- [x] Laravel 11 estable por al menos 1 semana
- [x] Tests funcionando (335 passing)
- [ ] Backup de BD
- [ ] Backup de código
- [ ] Rama: `feature/laravel-12-upgrade`

---

## 📋 Cambios Principales en Laravel 12

### 1. Requisitos Mínimos

```
PHP: ^8.2 (requerido, no opcional)
MySQL: ^8.0.28 o MariaDB ^10.5.0
PostgreSQL: ^12.0
SQLite: ^3.35.0
```

### 2. Dependencias Actualizadas

- Symfony 7.x
- PHPUnit 11.x
- Doctrine DBAL 4.x (si se usa)
- Actualización de varios paquetes core

### 3. Breaking Changes Principales

1. **Removed Deprecations from L11**
   - Todo lo deprecado en L11 se eliminó en L12
   - String helpers globales removidos
   - Algunos métodos antiguos eliminados

2. **Database Changes**
   - Mejoras en query builder
   - Nuevos tipos de columna
   - Mejoras en migrations

3. **HTTP Client Updates**
   - Nuevos métodos
   - Mejoras en testing

4. **Collection Improvements**
   - Nuevos métodos utilitarios
   - Performance mejorada

---

## 🚀 Pasos de Migración

### Paso 1: Verificar Estado Actual

```bash
# Verificar versión actual
php artisan --version
# Debe mostrar: Laravel Framework 11.x.x

# Verificar que L11 está estable
php artisan test
# Debe mostrar: 335 passing

# Verificar logs (no debe haber errores críticos)
tail -100 storage/logs/laravel.log
```

---

### Paso 2: Crear Backup

```bash
# Backup BD
mysqldump -u root -p tesoreria_oficinas > backup_pre_l12_$(date +%Y%m%d).sql

# Tag git de L11
git tag -a v2.1-laravel-11-stable -m "Laravel 11 estable antes de L12"
git push origin v2.1-laravel-11-stable

# Crear rama para L12
git checkout -b feature/laravel-12-upgrade
```

---

### Paso 3: Actualizar composer.json

```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0",
        "livewire/livewire": "^3.5",
        "bacon/bacon-qr-code": "^3.0",
        "doctrine/dbal": "^4.0",
        "guzzlehttp/guzzle": "^7.8",
        "intervention/image": "^3.11",
        "laravel/sanctum": "^4.0",
        "laravel/tinker": "^2.9",
        "php-open-source-saver/jwt-auth": "^2.7",
        "phpoffice/phpspreadsheet": "^2.0",
        "pragmarx/google2fa-laravel": "^2.3",
        "smalot/pdfparser": "^2.0",
        "spatie/laravel-activitylog": "^4.8",
        "spatie/laravel-backup": "^9.0",
        "spatie/laravel-permission": "^6.3",
        "tightenco/ziggy": "^2.5"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/sail": "^1.29",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.0",
        "phpunit/phpunit": "^11.0",
        "spatie/laravel-ignition": "^2.5"
    }
}
```

**Cambios clave de L11 a L12**:
- `laravel/framework`: `^11.0` → `^12.0`
- `doctrine/dbal`: `^3.8` → `^4.0`
- `laravel/sanctum`: `^3.3` → `^4.0`
- `phpoffice/phpspreadsheet`: `^1.29` → `^2.0`
- `spatie/laravel-backup`: `^8.8` → `^9.0`
- `phpunit/phpunit`: `^10.5` → `^11.0`

---

### Paso 4: Actualizar Dependencias

```bash
# Limpiar caché de composer
composer clear-cache

# Actualizar todas las dependencias
composer update

# Esto puede tardar varios minutos
```

**Posibles Conflictos**:

#### A. Doctrine DBAL 4.0
```bash
# Si hay error con doctrine/dbal
composer require doctrine/dbal:^4.0 --with-all-dependencies
```

#### B. PHPOffice/PhpSpreadsheet 2.0
```bash
# Si hay problemas
composer require phpoffice/phpspreadsheet:^2.0 --with-all-dependencies
```

#### C. Laravel Sanctum 4.0
```bash
# Actualizar sanctum
composer require laravel/sanctum:^4.0
```

---

### Paso 5: Actualizar PHPUnit (11.0)

Laravel 12 usa PHPUnit 11, que tiene cambios de sintaxis.

#### A. Actualizar phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/11.0/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache"
         beStrictAboutOutputDuringTests="true"
         failOnRisky="true"
         failOnWarning="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
        </include>
        <exclude>
            <directory>app/Console/Commands</directory>
        </exclude>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_DATABASE" value="tesoreria_oficinas_test"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_STORE" value="array"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="PULSE_ENABLED" value="false"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
    </php>
</phpunit>
```

**Cambios principales**:
- Schema URL actualizada a 11.0
- `<source>` con `<exclude>` mejorado
- Nuevas opciones de strict testing

---

### Paso 6: Verificar String Helpers

En Laravel 12, los helpers globales están completamente removidos.

```bash
# Buscar cualquier uso que quedó
grep -r "str_contains(" app/ --exclude-dir=vendor
grep -r "str_starts_with(" app/ --exclude-dir=vendor
grep -r "str_ends_with(" app/ --exclude-dir=vendor
grep -r "array_get(" app/ --exclude-dir=vendor
```

**Si encuentras alguno, reemplazar**:

```php
// ❌ NO funciona en L12
str_contains($text, 'search');
array_get($array, 'key');

// ✅ Usar en L12
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

Str::contains($text, 'search');
Arr::get($array, 'key');
```

---

### Paso 7: Actualizar Doctrine DBAL (Si se Usa)

Si usas `doctrine/dbal` para columnas o cambios de esquema:

```php
// L11 (DBAL 3.x)
use Doctrine\DBAL\Types\Type;

// L12 (DBAL 4.x)
use Doctrine\DBAL\Types\Types;  // ← Cambió el nombre

// Actualizar tipos:
Type::STRING → Types::STRING
Type::INTEGER → Types::INTEGER
Type::TEXT → Types::TEXT
```

**Buscar en tu código**:
```bash
grep -r "use Doctrine\\DBAL\\Types\\Type;" app/
```

---

### Paso 8: Actualizar Config Files

#### A. config/sanctum.php

Laravel Sanctum 4.0 tiene nuevas opciones:

```php
return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort()
    ))),

    'guard' => ['web'],

    'expiration' => null,

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),  // ← Nuevo en v4

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
```

---

### Paso 9: Limpiar Código Deprecado

Laravel 12 removió deprecations de L11:

```php
// ❌ Removido en L12
$request->get('key');  // Usar $request->input('key')
Model::firstOrNew();   // OK, pero revisar comportamiento

// ❌ Removido
Route::controller()    // Usar Route::controller(Controller::class)
```

---

### Paso 10: Ejecutar Tests

```bash
# Limpiar todo primero
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Ejecutar tests
php artisan test

# Objetivo: 335 passing
```

**Si fallan tests**:

1. **Error de PHPUnit**:
```bash
# Regenerar autoload
composer dump-autoload

# Limpiar cache de PHPUnit
rm -rf .phpunit.cache
```

2. **Error de Deprecation**:
```bash
# Ver error específico
php artisan test --verbose

# Buscar el método deprecado y actualizarlo
```

3. **Error de Tipo**:
```php
// PHPUnit 11 es más estricto con tipos
// Asegurar que assertions tienen tipos correctos

$this->assertEquals('string', $valor);  // OK
$this->assertEquals(123, $valor);       // OK
$this->assertSame('string', $valor);    // Más estricto
```

---

### Paso 11: Pruebas Manuales

```bash
# Iniciar servidor
php artisan serve

# Probar módulos principales
```

**Checklist**:
- [ ] Login y autenticación
- [ ] Caja Chica: crear, pagar, rendir
- [ ] Libro Diario: asientos, redistribuciones
- [ ] Multas: búsqueda, cobro
- [ ] CFE: carga, confirmación
- [ ] Sin errores en consola del navegador
- [ ] Sin errores en logs de Laravel

```bash
# Monitorear logs
tail -f storage/logs/laravel.log
```

---

### Paso 12: Performance Testing

```bash
# Medir tiempo de respuesta en endpoints clave
# Comparar con L11

# Ejemplo simple con curl
time curl http://localhost:8000/tesoreria/caja-chica

# Debe ser similar o mejor que L11
```

---

### Paso 13: Optimizar

```bash
# Limpiar y optimizar todo
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan event:clear

# Cachear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Optimizar autoloader
composer install --optimize-autoloader --no-dev
```

---

### Paso 14: Commit y Push

```bash
git add .
git commit -m "Actualizar Laravel 11 a 12

- Actualizar dependencias principales
- Mantener Livewire 3.5
- Actualizar PHPUnit 10 → 11
- Actualizar Sanctum 3 → 4
- Actualizar DBAL 3 → 4
- Tests: 335 passing
- Performance: igual o mejor"

git push origin feature/laravel-12-upgrade
```

---

### Paso 15: Deploy a Staging

```bash
# Crear PR
git checkout staging
git merge feature/laravel-12-upgrade
git push origin staging

# Deploy a servidor staging
ssh user@staging-server
cd /var/www/oficinas
git pull origin staging
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Monitoreo Staging** (3-5 días):
- [ ] Tests automáticos pasando
- [ ] Sin errores en logs
- [ ] Performance aceptable
- [ ] Usuarios beta satisfechos

---

### Paso 16: Deploy a Producción

```bash
# Después de staging estable 3-5 días
git checkout main
git merge feature/laravel-12-upgrade

# Tag importante
git tag -a v3.0-laravel-12 -m "Laravel 12 en producción"
git push origin main --tags

# Deploy (horario bajo tráfico)
```

**Post-Deploy**:
- Monitorear primeras 2 horas continuamente
- Revisar logs cada hora primer día
- Revisar diariamente primera semana

---

## 🔍 Breaking Changes Específicos L11→L12

### 1. Doctrine DBAL 4.x

```php
// ❌ L11 (DBAL 3.x)
use Doctrine\DBAL\Types\Type;
Schema::table('users', function (Blueprint $table) {
    $table->string('name')->change();
});

// ✅ L12 (DBAL 4.x)  
use Doctrine\DBAL\Types\Types;
// Sintaxis igual, pero imports diferentes
```

### 2. PHPUnit 11

```php
// ❌ Deprecado en PHPUnit 11
public function setUp()  // Sin tipo de retorno

// ✅ PHPUnit 11
public function setUp(): void  // Con tipo
public function tearDown(): void
protected function tearDown(): void
```

### 3. String Helpers Removidos

```php
// ❌ Completamente removido en L12
str_contains();
str_starts_with();
str_ends_with();

// ✅ Usar Str facade SIEMPRE
Str::contains();
Str::startsWith();
Str::endsWith();
```

### 4. Array Helpers Removidos

```php
// ❌ Removido
array_get($array, 'key');
array_set($array, 'key', 'value');
array_has($array, 'key');

// ✅ Usar Arr facade
Arr::get($array, 'key');
Arr::set($array, 'key', 'value');
Arr::has($array, 'key');
```

### 5. Collection Changes

```php
// Algunos métodos tienen mejor typing
// Revisar si usas métodos avanzados de Collection
// Documentación: https://laravel.com/docs/12.x/collections
```

---

## ✅ Checklist Final L11→L12

### Pre-Migración
- [x] Laravel 11 estable en producción
- [x] Monitoreo L11 satisfactorio (1 semana)
- [ ] Backup BD realizado
- [ ] Tag git: v2.1-laravel-11-stable
- [ ] Rama: feature/laravel-12-upgrade

### Durante Migración
- [ ] composer.json actualizado a L12
- [ ] Dependencias actualizadas sin conflictos
- [ ] PHPUnit 11 configurado
- [ ] String/Array helpers verificados
- [ ] Doctrine DBAL actualizado (si aplica)
- [ ] Tests: 335 passing

### Post-Migración
- [ ] Pruebas manuales OK
- [ ] Performance igual o mejor
- [ ] Deploy staging exitoso
- [ ] Staging estable 3-5 días
- [ ] Deploy producción exitoso
- [ ] Producción estable 1 semana

---

## 📊 Métricas de Éxito

| Métrica | L11 Baseline | L12 Objetivo |
|---------|--------------|--------------|
| Tests passing | 335/335 | 335/335 |
| Tiempo respuesta | X ms | ≤ X ms |
| Errores logs | 0 | 0 |
| Uptime | 99.9% | 99.9% |
| Memoria uso | Y MB | ≤ Y MB |

---

## 🚨 Plan de Rollback

Si algo sale mal en producción:

```bash
# 1. Activar mantenimiento
php artisan down

# 2. Volver a L11
git checkout v2.1-laravel-11-stable
composer install --no-dev
php artisan migrate:rollback  # Si hubo migraciones

# 3. Limpiar
php artisan config:clear
php artisan cache:clear
php artisan optimize

# 4. Reactivar
php artisan up

# 5. Investigar problema en dev
```

---

## 📚 Recursos

### Documentación Oficial
- [Laravel 12 Release Notes](https://laravel.com/docs/12.x/releases)
- [Laravel 12 Upgrade Guide](https://laravel.com/docs/12.x/upgrade)
- [PHPUnit 11 Docs](https://docs.phpunit.de/en/11.0/)
- [Doctrine DBAL 4 Upgrade](https://github.com/doctrine/dbal/blob/4.0.x/UPGRADE.md)

### Internal Docs
- `PLAN_MIGRACION_INCREMENTAL.md` - Estrategia general
- `GUIA_L10_A_L11.md` - Fase anterior
- `GUIA_TESTING.md` - Testing

---

## ➡️ Próximo Paso

Una vez L12 está **estable en producción por 1 semana**:

👉 Continuar con: **Fase 3 - Livewire 3 → 4**

Usar: `GUIA_MIGRACION_PASO_A_PASO.md`

---

## 💡 Notas Importantes

1. **L11→L12 es más sencillo que L10→L11**
   - Menos breaking changes
   - Mejor documentación
   - Comunidad más activa

2. **Mantén Livewire 3 estable**
   - No actualices a Livewire 4 todavía
   - Primero estabiliza Laravel 12
   - Luego migra Livewire (Fase 3)

3. **Tests son tu red de seguridad**
   - Ejecuta después de cada cambio
   - Si algo falla, reviértelo inmediatamente
   - 335 passing es tu objetivo SIEMPRE

---

**Última actualización**: 14/08/2026  
**Estado**: ✅ LISTA PARA USAR  
**Duración estimada**: 1 semana  
**Prerequisito**: Laravel 11 estable
