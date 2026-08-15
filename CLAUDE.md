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

Aplicacion administrativa interna para una oficina de Tesoreria construida con Laravel 9 + PHP 8 + Livewire 2.12. El sistema maneja autenticacion, permisos, auditoria y varios modulos financieros incluyendo pagos, multas, certificados y gestion de caja diaria.

## Comandos Principales

```bash
# Instalar dependencias
composer install
npm install

# Desarrollo
php artisan serve          # Servidor de desarrollo
npm run dev                # Compilar assets con Laravel Mix

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

- **`app/Http/Controllers/`** - Controladores clasicos para auth, admin y modulos de tesoreria
- **`app/Http/Livewire/`** - Componentes UI reactivos; la mayor parte de la logica de negocio vive aqui
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