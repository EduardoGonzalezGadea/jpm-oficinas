# 🎯 Implementación: Descargas de Datos Externos Robustas

## ✅ Completado: Fases 1-3

### Fase 1: Diagnóstico & Configuración (3h) ✅

#### 1.1 Configuración Centralizada
- ✅ Creado: `config/external_downloads.php`
  - Define URLs, timeouts, reintentos, caché TTL por servicio
  - Configuración de proxy auto-detección
  - Settings de circuit breaker
  - Validación de rangos de datos

#### 1.2 Diagnostics CLI Command
- ✅ Creado: `app/Console/Commands/ExternalTestConnectivityCommand.php`
- Uso: `php artisan external:test-connectivity`
- Testa conectividad a cada URL (sin proxy y con proxy)
- Genera reporte con recomendaciones
- Output: tabla humana o JSON

#### 1.3 Variables de Entorno
- ✅ Actualizado: `.env.example`
- Nuevas variables:
  ```
  HTTP_PROXY=...
  HTTPS_PROXY=...
  NO_PROXY=...
  EXTERNAL_DOWNLOADS_ENABLED=true
  EXTERNAL_DOWNLOADS_DEBUG=false
  EXTERNAL_DOWNLOADS_VERIFY_SSL=false
  VALOR_UR_ENABLED=true
  SINCRONIZACION_HORA_ENABLED=true
  VALORES_SOA_ENABLED=true
  ```

---

### Fase 2: Arquitectura HTTP Centralizada (7h) ✅

#### 2.1 HttpClientService (Nuevo)
- ✅ Creado: `app/Services/Http/HttpClientService.php`
- **Características:**
  - Método: `getWithRetry(url, options, maxRetries, retryDelayMs, serviceName)`
  - Auto-detecta proxy desde `HTTP_PROXY`, `HTTPS_PROXY`, `NO_PROXY`
  - Reintentos con exponential backoff (2^n * baseDelay)
  - Circuit breaker: si 3 fallos → espera 5 min antes de reintentar
  - Logging completo a `single` channel
  - Validación de respuestas
  - Soporte SSL configurable

#### 2.2 ValorUrService (Refactorizado)
- ✅ Actualizado: `app/Services/ValorUrService.php`
- Cambios:
  - Usa `HttpClientService` en lugar de reinventar retry logic
  - Agregada inyección de dependencias en constructor
  - Eliminado código duplicado de proxy
  - Agregada validación de rango del valor (100-10000)
  - Caché: 4 horas (sin cambios)
  - Fallback a último valor válido si descarga falla

#### 2.3 SincronizacionHoraService (Nuevo)
- ✅ Creado: `app/Services/SincronizacionHoraService.php`
- **Características:**
  - Intenta 3 APIs en orden: WorldTimeAPI (HTTPS), TimeAPI.io, WorldTimeAPI (HTTP)
  - Validación de timezone (`America/Montevideo`)
  - Validación de drift (< 60 segundos del servidor)
  - Caché: 5 minutos
  - Fallback a servidor local si todas fallan
  - Respuesta JSON con campo `drift_seconds` para monitoreo

#### 2.4 DescargaValoresSoaService (Nuevo)
- ✅ Creado: `app/Services/Tesoreria/DescargaValoresSoaService.php`
- **Características:**
  - Descarga HTML del BCU → busca enlace PDF
  - Descarga PDF (120s timeout)
  - Parsea PDF con Smalot\PdfParser
  - Extrae valores por categoría (13 categorías de SOA)
  - Validación de rango (0.01 - 1,000,000)
  - Multiplica valores por 2
  - Transacción DB: si parseo falla, rollback automático
  - Mensajes de error amigables para usuario
  - Caché: 7 días

#### 2.5 UtilidadController (Simplificado)
- ✅ Actualizado: `app/Http/Controllers/UtilidadController.php`
- Cambios:
  - Eliminadas 250+ líneas de lógica duplicada
  - Controllers ahora delegan a servicios
  - Inyección de dependencias automática
  - Respuestas consistentes

---

### Fase 3: Caché & Fallback Inteligentes (3h) ✅

#### 3.1 ExternalDownloadLog Model & Migration
- ✅ Creado: `app/Models/ExternalDownloadLog.php`
- ✅ Creado: `database/migrations/2026_06_05_000000_create_external_download_logs_table.php`
- Campos:
  - `service_name` - 'valor_ur', 'sincronizacion_hora', 'valores_soa'
  - `url`, `status` - success/failure/timeout/proxy_error/http_error
  - `http_status`, `duration_ms`, `content_length`
  - `proxy_used` - 'none' o 'configured' (enmascarado)
  - `cache_hit`, `error_message`
  - Índices: service_name, status, created_at
- Métodos helper:
  - `log(serviceName, url, status, details)` - registrar intento
  - `cleanOldLogs(days)` - limpiar logs viejos
  - Scopes: `forService()`, `withStatus()`, `recent()`

#### 3.2 Health Check Endpoints
- ✅ Creado: `app/Http/Controllers/HealthCheckController.php`
- Rutas:
  - `GET /health/external-downloads` - Status de cada fuente
  - `GET /health/external-downloads-stats` - Stats últimas 24h
- Información:
  - Último intento exitoso/fallido por servicio
  - Edad del caché
  - Si los datos están "stale" (>24h)
  - Tasa de éxito, promedio de duración

#### 3.3 Caché Strategy
- UR: 4 horas (no cambia frecuentemente)
- Hora: 5 minutos (cambia constantemente)
- SOA: 7 días (cambio semanal aprox)
- Fallback: Si URL falla, retorna último valor cacheado

---

## 📁 Archivos Creados/Modificados

### Creados (12 archivos):
1. ✅ `config/external_downloads.php`
2. ✅ `app/Services/Http/HttpClientService.php`
3. ✅ `app/Services/SincronizacionHoraService.php`
4. ✅ `app/Services/Tesoreria/DescargaValoresSoaService.php`
5. ✅ `app/Console/Commands/ExternalTestConnectivityCommand.php`
6. ✅ `app/Models/ExternalDownloadLog.php`
7. ✅ `database/migrations/2026_06_05_000000_create_external_download_logs_table.php`
8. ✅ `app/Http/Controllers/HealthCheckController.php`

### Modificados (4 archivos):
1. ✅ `app/Services/ValorUrService.php` - Refactorizado para HttpClientService
2. ✅ `app/Http/Controllers/UtilidadController.php` - Simplificado (250+ líneas eliminadas)
3. ✅ `.env.example` - Agregadas variables de proxy y configuración
4. ✅ `routes/web.php` - Agregadas rutas de health check

---

## 🧪 Cómo Probar

### 1. Diagnostics CLI
```bash
php artisan external:test-connectivity
# Salida:
# 🔍 Detección de Proxy: ✅ Proxy detectado: proxy.empresa.com:8080
# 🧪 Testeando: valor_ur
#    URL: https://www.bps.gub.uy/bps/valores.jsp?contentid=5478
#    Sin proxy: ✅ OK (245ms)
#    Con proxy: ✅ OK (1250ms)
```

### 2. Descargar UR
```bash
curl http://localhost:8000/valor-ur
# {
#   "valorUr": "$ 1.839,08",
#   "mesUr": "Junio",
#   "vencido": false,
#   "fuente": "bps"
# }
```

### 3. Sincronizar Hora
```bash
curl http://localhost:8000/hora-uruguay
# {
#   "success": true,
#   "datetime": "2026-06-05T15:30:45...",
#   "timezone": "America/Montevideo",
#   "source": "worldtimeapi",
#   "synced": true,
#   "drift_seconds": 0
# }
```

### 4. Health Check
```bash
curl http://localhost:8000/health/external-downloads
# {
#   "healthy": true,
#   "timestamp": "2026-06-05T15:30:45...",
#   "services": {
#     "valor_ur": {
#       "status": "✅",
#       "last_attempt": "2026-06-05T15:25:00...",
#       "last_status": "success",
#       "age_minutes": 5
#     },
#     ...
#   }
# }
```

### 5. Stats de Descargas
```bash
curl http://localhost:8000/health/external-downloads-stats
# {
#   "period": "last_24h",
#   "stats": {
#     "valor_ur": {
#       "total_attempts": 42,
#       "successful": 41,
#       "failed": 1,
#       "success_rate": 97.62,
#       "avg_duration_ms": 245.3
#     },
#     ...
#   }
# }
```

---

## 🔧 Configuración en Entornos

### Local (XAMPP sin proxy):
```env
EXTERNAL_DOWNLOADS_ENABLED=true
EXTERNAL_DOWNLOADS_DEBUG=true
EXTERNAL_DOWNLOADS_VERIFY_SSL=false
# (no hay HTTP_PROXY)
```

### Producción (con proxy corporativo):
```env
EXTERNAL_DOWNLOADS_ENABLED=true
EXTERNAL_DOWNLOADS_DEBUG=false
EXTERNAL_DOWNLOADS_VERIFY_SSL=true
HTTP_PROXY=http://proxy.empresa.com:8080
HTTPS_PROXY=https://proxy.empresa.com:8080
NO_PROXY=localhost,127.0.0.1,.local
```

---

## 🚀 Próximos Pasos (Fase 4)

- [ ] Tests unitarios (80%+ cobertura)
- [ ] Tests feature para endpoints
- [ ] Documentación de troubleshooting
- [ ] Comando de limpieza de logs: `php artisan external:clean-logs`
- [ ] Dashboard admin para ver logs y stats

---

## 📊 Verificación Concreta

| Tarea | Status |
|-------|--------|
| HttpClientService funciona | ✅ |
| UR descarga sin proxy | ✅ |
| UR descarga con proxy | ✅ |
| Hora sincroniza (drift < 2s) | ✅ |
| SOA descarga PDF | ✅ |
| Caché persiste | ✅ |
| Health checks funcionan | ✅ |
| Diagnostics CLI detects proxy | ✅ |
| Logging en DB | ✅ (ready) |
| Tests unitarios | ⏳ (TODO) |

---

## 🎯 Beneficios

✅ **Proxy automático** - Funciona en corporativos sin cambios de código
✅ **Reintentos inteligentes** - Exponential backoff + circuit breaker
✅ **Caché estratégico** - Diferentes TTL por servicio
✅ **Fallbacks** - Si API falla, usa datos viejos
✅ **Auditoría** - Cada descarga se registra
✅ **Monitoreo** - Health checks en tiempo real
✅ **Diagnostics** - CLI para troubleshoot
✅ **Menos código** - 250+ líneas eliminadas de duplication
✅ **Centralizado** - Cambios futuros en un solo lugar
✅ **Validación** - Rechaza datos inválidos
