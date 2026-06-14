# CLAUDE.md

Este archivo proporciona guia a Claude Code (claude.ai/code) cuando trabaja con codigo en este repositorio.

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
- `app/Http/Livewire/Shared/BaseReportComponent.php`
- `app/Http/Livewire/Traits/WithAdvancedReportLogic.php`

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