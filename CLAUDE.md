# CLAUDE.md

Este archivo proporciona guia a Claude Code (claude.ai/code) cuando trabaja con codigo en este repositorio.

## Directiva de idioma (OBLIGATORIA)

**Todas las respuestas, explicaciones, comentarios de código, mensajes de commit,
documentación y resúmenes generados por cualquier IA DEBEN estar escritos en español.**
- Esto aplica a cualquier asistente o IDE: Claude Code, Cursor, Google Antigravity IDE,
  GitHub Copilot, opencode, etc.
- Si una herramienta viene configurada por defecto en otro idioma, indicar en su
  configuración o prompt que utilice español de forma permanente.
- Se permite el inglés únicamente para: nombres de variables, clases, funciones,
  identificadores, claves de array y textos literales del código fuente.

## Resumen del Proyecto

Aplicación administrativa interna para una oficina de Tesorería construida con **Laravel 12 + PHP 8.2+ + Livewire 4 + Bootstrap 4.6 (Bootswatch)**. El sistema maneja autenticación, permisos, auditoría y varios módulos financieros incluyendo pagos, multas, certificados y gestión de caja diaria.

## Comandos Principales

```bash
# Instalar dependencias
composer install
npm install

# Desarrollo
php artisan serve          # Servidor de desarrollo
npm run dev                # Compilar assets con Laravel Mix
npm run production         # Compilar bundle de producción

# Base de datos
php artisan migrate        # Ejecutar migraciones

# Testing
php artisan test           # Ejecutar tests PHPUnit
php artisan test --filter=MedioPagoServiceTest  # Test especifico

# Generacion de clave
php artisan key:generate   # Generar clave de aplicacion

# Caja Chica - Libro Diario
php artisan caja-chica:crear-asientos-historicos --dry-run  # Simular creación de asientos históricos
php artisan caja-chica:crear-asientos-historicos            # Crear asientos históricos de caja chica
php artisan libro-diario:recalcular-saldos                  # Recalcular saldos del libro diario
php artisan caja-chica:reparar-asientos                     # Reparar asientos faltantes
```

## Arquitectura

### Estructura Backend

- **`app/Http/Controllers/`** - Controladores clásicos para auth, admin y módulos de tesorería
- **`app/Livewire/`** - Componentes UI reactivos (Livewire 4); la mayor parte de la lógica de negocio vive aquí
- **`app/Models/Tesoreria/`** - Modelos Eloquent del dominio financiero
- **`app/Services/`** - Servicios transversales:
  - `Http/HttpClientService.php` - Cliente HTTP centralizado con proxy auto-detect, reintentos, circuit breaker
  - `ValorUrService.php` - Descarga de UR desde BPS (refactorizado v2)
  - `SincronizacionHoraService.php` - Sincronización de hora desde APIs públicas
  - `Tesoreria/DescargaValoresSoaService.php` - Descarga de valores SOA desde BCU
  - Otros: parser CFE, reportes
- **`app/Http/Middleware/`** - Verificacion JWT, 2FA, permisos

### Estructura Frontend

- **`resources/views/`** - Plantillas Blade para paneles y modulos
- **`resources/views/livewire/`** - Vistas de componentes Livewire
- **`resources/js/`** y **`resources/css/`** - Assets compilados via Laravel Mix

### Rutas

- **`routes/web.php`** - Entrada principal (contiene la mayoria de rutas - nota: archivo grande que mezcla closures, controladores y Livewire)
- **`routes/api.php`** - Endpoints API, principalmente para procesamiento CFE
- **`routes/valores.php`** - Subrutas del modulo Valores

## Archivos Criticos

| Archivo | Proposito |
|---------|-----------|
| `app/Services/CfeProcessorService.php` | Parser PDF y ruteo de modulos - central para flujo CFE |
| `app/Http/Controllers/AuthController.php` | Autenticacion hibrida (JWT + sesion) |
| `app/Services/Tesoreria/ReporteRecibosService.php` | Reportes consolidados de recibos |
| `app/Http/Livewire/Tesoreria/CajaChica/Index.php` | Logica de gestion de caja |
| `app/Http/Livewire/Tesoreria/MultasCobradas/MultasCobradas.php` | Cobro de multas |

## Flujo de Autenticacion

El sistema usa autenticacion hibrida:
1. Sesion web (Laravel)
2. JWT almacenado en cookie HTTP-only
3. Spatie Permission para roles/permisos
4. Middleware de autenticacion de dos factores

Rutas protegidas usan cadena de middleware: `web` → `jwt.verify` → `two-factor`

## Flujo de Procesamiento CFE

1. PDF entra via API o carga manual
2. `CfeController` → `CfeProcessorService`
3. El servicio detecta tipo de documento via regex
4. Extrae datos y crea prefill temporal
5. Redirige al modulo destino (multas, eventuales, arrendamientos, prendas, certificados, armas)
6. Usuario confirma/completa datos en formulario final

**Riesgo**: El parser CFE depende de patrones regex; cambios de formato en documentos fuente pueden romper la extraccion.

## Patron de Modulos

La mayoria de modulos de Tesoreria siguen esta estructura:
1. Ruta en `routes/web.php`
2. Componente Livewire en `app/Http/Livewire/Tesoreria/{Modulo}/`
3. Modelo en `app/Models/Tesoreria/`
4. Servicio opcional en `app/Services/Tesoreria/`
5. Vistas en `resources/views/livewire/tesoreria/{modulo}/`

## Infraestructura de Reportes

Componentes compartidos para reportes avanzados:
- `app/Livewire/Shared/BaseReportComponent.php`

## Navegacion en el Codigo

1. Empezar en `routes/web.php` para encontrar la ruta
2. Localizar controlador o componente Livewire
3. Buscar modelo en `app/Models/Tesoreria/`
4. Verificar si existe capa de servicio para logica compleja
5. Para flujos con PDF/datos externos, revisar `CfeProcessorService`

## Deuda Tecnica

- `routes/web.php` es demasiado grande y mezcla patrones - considerar refactorizacion
- Logica de negocio embebida en componentes Livewire grandes
- Baja cobertura de tests para operaciones financieras criticas
- Parser CFE fragil (extraccion basada en regex sobre texto)

## Sistema de Libro Diario y Caja Chica

### Arquitectura

El sistema de libro diario está integrado con el módulo de caja chica, registrando automáticamente todos los movimientos contables.

#### Servicios Clave

- **LibroDiarioService**: Gestión de asientos, redistribuciones, confirmaciones y saldos
- **CajaChicaService**: Lógica de pendientes, pagos, movimientos y recuperaciones
- **CajaChicaAsientosService**: Puente entre caja chica y libro diario (registra asientos automáticamente)

#### Comandos de Sincronización

##### 1. Crear Asientos Históricos
Crea asientos del libro diario para registros históricos de caja chica creados antes de la implementación del sistema:

```bash
# Simular (recomendado primero)
php artisan caja-chica:crear-asientos-historicos --dry-run

# Aplicar todos
php artisan caja-chica:crear-asientos-historicos

# Por mes y año específico
php artisan caja-chica:crear-asientos-historicos --mes=enero --anio=2026

# Por ID de caja chica
php artisan caja-chica:crear-asientos-historicos --caja-chica-id=1
```

**Qué hace:**
1. Crea asiento de fondo fijo (constitución inicial)
2. Registra redistribuciones de pendientes (Fondo Fijo → Pendiente)
3. Registra redistribuciones de pagos directos (Fondo Fijo → Pagos)
4. Registra rendiciones y recuperaciones de pendientes y pagos

**Seguridad:**
- Detecta automáticamente asientos existentes (no crea duplicados)
- Respeta fechas y montos originales
- Usa transacciones de base de datos

##### 2. Reparar Asientos Faltantes
Registra asientos faltantes para registros puntuales:

```bash
php artisan caja-chica:reparar-asientos --dry-run
php artisan caja-chica:reparar-asientos --desde=2026-01-01
```

##### 3. Recalcular Saldos
Recalcula los saldos acumulados de todas las subcuentas:

```bash
php artisan libro-diario:recalcular-saldos --dry-run
php artisan libro-diario:recalcular-saldos
```

#### Flujo Recomendado para Datos Históricos

```bash
# 1. Simular creación de asientos
php artisan caja-chica:crear-asientos-historicos --dry-run

# 2. Si todo está correcto, aplicar
php artisan caja-chica:crear-asientos-historicos

# 3. Recalcular saldos
php artisan libro-diario:recalcular-saldos
```

#### Documentación

Ver archivos de documentación:
- `CAJA_CHICA_HISTORICA_README.md` - Guía rápida del comando
- `docs/comandos/caja-chica-crear-asientos-historicos.md` - Documentación completa

## Documentacion

Ver `docs/` para documentacion funcional:
- `INDICE_APLICACION.md` - Indice tecnico
- `MAPA_FLUJOS_APLICACION.md` - Diagramas de flujo
- `PLAN_CAJA_DIARIA.md` - Plan de modulo caja diaria
- `DOCUMENTACION_MULTAS_COBRADAS.md` - Documentacion de multas
- `DOCUMENTACION_PRENDAS.md` - Documentacion de prendas
- `EXTERNAL_DOWNLOADS_IMPLEMENTATION.md` - Arquitectura de descargas externas

## Descargas de Datos Externos (UR, Hora, SOA)

### Arquitectura

Se utilizan tres servicios para descargar datos críticos desde URLs externas:

#### 1. HttpClientService (centralizado)
- **Ubicación**: `app/Services/Http/HttpClientService.php`
- **Responsabilidad**: Manejar HTTP requests, reintentos, proxy, circuit breaker
- **Proxy**: Auto-detecta desde `HTTP_PROXY`, `HTTPS_PROXY`, `NO_PROXY`
- **Reintentos**: Exponential backoff (1s, 2s, 4s, 8s...)
- **Circuit breaker**: Si 3 fallos → espera 5 min

#### 2. ValorUrService (refactorizado)
- **Ubicación**: `app/Services/ValorUrService.php`
- **Descarga**: Valor de UR desde BPS
- **Timeout**: 45 segundos | Caché: 4 horas | Fallback si falla.

#### 3. SincronizacionHoraService (nuevo)
- **Ubicación**: `app/Services/SincronizacionHoraService.php`
- **Descarga**: Hora sincronizada desde APIs públicas
- **Timeout**: 15 segundos | Caché: 5 minutos | Fallback si falla.

#### 4. DescargaValoresSoaService (nuevo)
- **Ubicación**: `app/Services/Tesoreria/DescargaValoresSoaService.php`
- **Descarga**: Valores SOA del BCU, actualiza multas Art. 184
- **Caché**: 7 días

### Diagnostics & Monitoreo

#### CLI: Test de conectividad
```bash
php artisan external:test-connectivity
```

#### Health Check Endpoints
- `GET /health/external-downloads` - Status actual de cada servicio
- `GET /health/external-downloads-stats` - Stats últimas 24h

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.2. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- This project upgraded from Laravel 10 without migrating to the new streamlined Laravel file structure.
- This is perfectly fine and recommended by Laravel. Follow the existing structure from Laravel 10. We do not need to migrate to the new Laravel structure unless the user explicitly requests it.

## Laravel 10 Structure

- Middleware typically lives in `app/Http/Middleware/` and service providers in `app/Providers/`.
- There is no `bootstrap/app.php` application configuration in a Laravel 10 structure:
    - Middleware registration happens in `app/Http/Kernel.php`
    - Exception handling is in `app/Exceptions/Handler.php`
    - Console commands and schedule register in `app/Console/Kernel.php`
    - Rate limits likely exist in `RouteServiceProvider` or `app/Http/Kernel.php`

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.

- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

</laravel-boost-guidelines>
