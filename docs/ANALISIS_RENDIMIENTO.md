# Análisis de Rendimiento — Procesos que Causan Demoras o Lentitud Sistémica

> **Auditoría:** Julio 2026  
> **Método:** Revisión estática de código, configuración y patrones de acceso.  
> **Nota:** No se ejecutaron pruebas de carga ni profiling real; los hallazgos se basan en inspección del código fuente.

---

## Resumen Ejecutivo

Los principales focos de degradación son cuatro:

1. **Cola `sync`** — jobs y listeners diseñados para ser asíncronos se ejecutan dentro del request web.
2. **Servicios HTTP externos** — timeouts largos, reintentos duplicados y circuit breaker mal implementado generan latencias acumulativas de minutos.
3. **Autenticación/autorización** — el middleware JWT y el AuthServiceProvider recalientan permisos y ejecutan consultas globales en cada request.
4. **Operaciones pesadas inline** — backups, parsing de PDF, reportes masivos y listados de archivos se resuelven completos dentro de la respuesta HTTP.

Estos cuatro problemas estructurales se amplifican con configuraciones de archivo (caché, sesión) y un frontend sobredimensionado para todas las pantallas.

---

## Hallazgos por Severidad

### Críticos

| # | Archivo | Problema | Impacto | Mejora Propuesta |
|---|---------|----------|---------|------------------|
| 1 | `config/queue.php:16`, `app/Services/CfeProcessorService.php:171`, `app/Listeners/` | La cola por defecto es `sync`. Los jobs (`ConfirmarCfeJob`) y listeners con `ShouldQueue` se ejecutan en línea. | Subir/procesar/confirmar un CFE carga PDF, lo parsea, escribe BD, dispara eventos y auditoría todo en la misma request. | Usar `QUEUE_CONNECTION=database` o `redis` con workers reales. Mover parseo y confirmación fuera del request. |
| 2 | `app/Services/Http/HttpClientService.php:56`, `app/Http/HttpClientService.php:81`, `config/external_downloads.php:73,113,149` | Reintentos con exponential backoff + prueba sin proxy y con proxy por intento. Timeouts largos (hasta 45s). Circuit breaker no cuenta fallos reales (se resetea por URL). | Un request a `/valor-ur`, `/hora-uruguay` o SOA puede quedar colgado >240s si la fuente externa falla. | Reducir `connect/read timeout`, imponer presupuesto total por operación, no duplicar intentos proxy en cada reintento, ejecutar descargas en jobs programados. |
| 3 | `app/Http/Middleware/JWTVerify.php:35` | Cada request JWT hace `login($user)`, `forgetCachedPermissions()` y `load(['roles.permissions', 'permissions'])`. | En Livewire/AJAX esto invalida la caché de Spatie por request y recarga roles/permisos completos. | No invalidar caché de permisos por request. Limitar el eager load a lo estrictamente necesario. |
| 4 | `app/Providers/AuthServiceProvider.php:49` | `Permission::all()` en el `boot()` + definición de gates dinámicos para cada permiso + `Gate::before()` duplicado. | Consulta global en cada bootstrap HTTP/CLI. Trabajo redundante con lo que ya resuelve Spatie. | Eliminar el registro dinámico de gates por fila y usar `hasPermissionTo()`/middleware de Spatie directamente. |
| 5 | `app/Http/Controllers/BackupController.php:41,96,168` | Backup/restore se ejecutan síncronamente por HTTP. Restore descomprime ZIP, carga SQL completo con `file_get_contents()` y lo inyecta a `mysql` vía `Process::setInput()`. | Respuestas muy lentas, alto consumo de RAM, riesgo de bloquear procesos PHP web durante operaciones administrativas. | Mover backup/restore a comandos/jobs asíncronos. Restaurar vía streaming en vez de cargar dump completo en memoria. |

### Altos

| # | Archivo | Problema | Impacto | Mejora Propuesta |
|---|---------|----------|---------|------------------|
| 6 | `app/Services/CfeProcessorService.php:85`, `app/Services/Tesoreria/DescargaValoresSoaService.php:207` | Parseo de PDF full-memory con `Smalot\PdfParser`. Sin límites de tamaño/páginas. | PDFs grandes o maliciosos pueden agotar CPU/RAM y degradar toda la instancia PHP. | Impner límites estrictos de tamaño/páginas, usar colas dedicadas para parsing. |
| 7 | `routes/web.php:54,56`, `app/Http/Controllers/UtilidadController.php:15,32` | Endpoints públicos que ejecutan llamadas externas (UR, hora, SOA) en el request. | Latencia variable visible al usuario. Posibilidad de saturar workers web con operaciones remotas bloqueantes. | Servir siempre desde caché local. Refrescar en background con scheduler/jobs. |
| 8 | `app/Services/Tesoreria/CajaChicaService.php:47,89` | `selectRaw` con subconsultas correlacionadas por fila + `->get()` masivo + filtrado/mapeo posterior en PHP. | A medida que crecen pendientes/movimientos/pagos, la consulta escala mal y ocupa mucha memoria. | Reemplazar subconsultas por joins/agregados agrupados. Paginar y mover filtros al SQL. |
| 9 | `app/Services/Tesoreria/ReporteRecibosService.php:176` | Reporte que carga todo el rango en memoria, fusiona secciones y deduplica por recibo sobre colecciones PHP. | En rangos amplios: mucha memoria, mucho CPU, consultas voluminosas. | Limitar rangos máximos, usar `chunk`/`cursor` para exportes, trasladar agregación al SQL. |
| 10 | `app/Http/Controllers/PendriveController.php:17,207` | `index()` lista todos los archivos con `Storage::files()`. `getThumbnail()` genera miniatura on-demand si no existe. | Con muchos archivos o disco lento, respuesta muy lenta y alto I/O. | Paginar/indexar listados, precalcular thumbnails, cachear metadatos. |
| 11 | `resources/views/layouts/app.blade.php` | Layout global con Google Fonts, Bootstrap, FontAwesome, SweetAlert2, Flatpickr, Alpine, Livewire y bloque inline de ~500 líneas. | Primera carga pesada. JS/CSS innecesario en pantallas simples. | Separar bundles por pantalla/stack. Cargar librerías solo donde se usan. Reducir JS inline global. |
| 12 | `resources/views/layouts/app.blade.php` (Flatpickr y scripts) | Flatpickr se reinicializa sobre todos los datepickers tras cada mensaje Livewire. | En pantallas Livewire muy interactivas, degradación progresiva del lado cliente. | Inicialización idempotente. Destruir/recrear explícitamente. |
| 13 | `routes/web.php` (closures), falta de `route:cache` y `config:cache` | Closures en rutas (`fn() => view(...)`). Sin evidencia de configuración/rutas cacheadas en `bootstrap/cache`. | Bootstrap más costoso. Sin `route:cache` el árbol de rutas se recompone en cada arranque. | Mover closures a controladores. Usar `config:cache`, `route:cache`, `view:cache` en despliegue. |

### Medios

| # | Archivo | Problema | Impacto | Mejora Propuesta |
|---|---------|----------|---------|------------------|
| 14 | `app/Providers/AppServiceProvider.php:36` | Recalcula `livewire.asset_url` y `app.url` en cada request usando `request()`. | Trabajo adicional por request. Impide `config:cache`. | Fijar `APP_URL`/`ASSET_URL` por entorno. Resolver subcarpeta fuera del provider. |
| 15 | `config/session.php:21`, `config/cache.php:18` | `SESSION_DRIVER=file` y `CACHE_DRIVER=file`. | I/O de disco por request. Peor throughput concurrente. | Migrar a Redis si el entorno lo permite. |
| 16 | `app/Services/Tesoreria/CajaChicaService.php`, `ReporteRecibosService.php`, varios Livewire | Filtrado y postprocesamiento intensivo en colecciones PHP en vez de SQL. | Consultas traen más filas de las necesarias. CPU de PHP hace trabajo del motor BD. | Mover búsquedas, filtros y agrupaciones al query builder siempre que sea posible. |
| 17 | `app/Services/Tesoreria/CfeCreatorService.php:276,299` | `autoAsignarDistribuciones()` hace consultas N+1 por ítem (historial + exists). | En CFEs con muchos ítems o cargas masivas, escala mal dentro de la transacción. | Precargar distribuciones válidas una vez por concepto/dependencia. Resolver historiales en una query por lote. |
| 18 | `app/Http/Livewire/Tesoreria/EstadosRecaudacion/Index.php:406` | `withTrashed()` + `with('items' => fn($q) => $q->withTrashed())` en el listado principal. | Cada página del listado incluye items soft-deleted; si hay muchas planillas anuladas con muchos items, la carga aumenta. | Evaluar si el total de items anulados impacta realmente; mantener si el volumen es bajo. |
| 19 | `app/Services/Tesoreria/CfeConfirmationService.php:60` | Búsqueda de duplicados con `LIKE '%...%'` + filtrado en PHP. | Con tabla de CFEs grande, scan completo + traer filas a PHP para filtrar. | Persistir campos normalizados de referencia e indexarlos. Resolver duplicidad en SQL exacto. |
| 20 | `app/Console/Commands/` (varios) | Comandos que usan `->get()` sin `chunkById()`/`cursor()`. | Cargan conjuntos completos en memoria. Con `QUEUE_CONNECTION=sync`, el reprocesamiento es serial y lento. | Usar `chunkById()`/`cursor()`. Hacer explícito si la cola es `sync`. |
| 21 | `resources/views/layouts/nav.blade.php` | Múltiples `@can()`, `esAdministrador()`, `moduloClave()`, `nivelActual()` en el menú global. | Si `modulo` no está cargado, dispara consulta lazy. Se repite en toda pantalla autenticada. | Precalcular capacidades en view model/composer. Cargar `modulo` junto al usuario autenticado. |
| 22 | `resources/views/layouts/publico.blade.php` | Layout público que también carga Bootstrap, FontAwesome JS, jQuery, Livewire y Alpine. | Páginas públicas simples tienen mucho peso innecesario. | Crear layout público realmente mínimo. |

---

## Patrones Estructurales

Los hallazgos se agrupan en cinco patrones que, combinados, explican la degradación bajo carga:

1. **Trabajo pesado dentro del request web** — PDF parsing, backups, descargas externas, jobs sync, reportes masivos.
2. **Reintentos externos demasiado generosos** — timeouts largos, proxy duplicado, circuit breaker ineficaz.
3. **Invalidación innecesaria de caché de permisos** — JWT + AuthServiceProvider recalientan autorización por request.
4. **Bootstrap global demasiado dinámico** — closures en rutas, config mutada, layout cargado, sesión archivo.
5. **Reportes/listados masivos resueltos en memoria** — CajaChicaService, ReporteRecibosService, PendriveController.

---

## Orden Recomendado de Ataque

| Fase | Prioridad | Acción | Esfuerzo Est. |
|------|-----------|--------|---------------|
| 1 | Quick win | Mover `QUEUE_CONNECTION` a `database`/`redis` y verificar que jobs/listeners se desacoplen del request | 1 día |
| 1 | Quick win | Eliminar `forgetCachedPermissions()` de `JWTVerify` | Horas |
| 1 | Quick win | Limitar rango máximo de reporte de recibos y paginar listados | 1 día |
| 2 | Medio | Corregir `AuthServiceProvider` (quitar Permission::all() + gates dinámicos) | 1 día |
| 2 | Medio | Migrar sesión/caché a Redis si el entorno lo soporta | 1 día |
| 2 | Medio | Endurecer timeouts de `HttpClientService` y eliminar proxy duplicado por intento | 2 días |
| 3 | Alto | Mover descargas externas (UR, hora, SOA) a jobs programados | 2 días |
| 3 | Alto | Refactorizar `CajaChicaService` con consultas agrupadas en vez de subconsultas por fila | 2-3 días |
| 3 | Alto | Adelgazar layout global (`app.blade.php`): lazy loading, stacks, bundle propio | 2 días |
| 3 | Alto | Mover backup/restore a comandos asíncronos con streaming | 2 días |
| 4 | Estructural | Refactorizar closures de rutas a controladores + `route:cache`/`config:cache`/`view:cache` | 1-2 días |
| 4 | Estructural | Agregar telemetría real de duración por servicio externo y parser | 2 días |
| 4 | Estructural | Implementar contador real de fallos en circuit breaker | 1 día |
| 4 | Estructural | Reemplazar `LIKE '%...%'` en búsqueda de duplicados CFE por campos normalizados indexados | 2 días |

---

## Suposiciones y Dudas a Validar

- Se asume que producción sigue usando `QUEUE_CONNECTION=sync`; si ya usan `database` o `redis`, parte del impacto de los jobs baja considerablemente.
- Se asume que los endpoints `/valor-ur`, `/hora-uruguay` y `/utilidad/actualizar-soa-art-184` se usan desde UI interactiva y no solo como endpoints internos.
- No hay métricas reales de tiempos de respuesta; la prioridad entre `CajaChicaService` y reportes depende del uso real de cada módulo.
- No se verificó la configuración real de entorno (`QUEUE_CONNECTION`, `SESSION_DRIVER`, `CACHE_DRIVER`, `LOG_LEVEL`); los hallazgos se basan en los valores por defecto en `config/`.
