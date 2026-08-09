# 🔧 Troubleshooting: Sincronización de Hora

## 📋 Diagnóstico Rápido

La hora en el panel muestra la hora del servidor y no parece estar sincronizándose con APIs externas. 

### Paso 1: Verificar Conectividad a APIs de Tiempo

Ejecuta este comando para testear cada API directamente:

```bash
php artisan external:test-connectivity --service=sincronizacion_hora
```

**Salida esperada:**
- ✅ Las 3 URLs deben estar accesibles (al menos una)
- Puede funcionar sin proxy y luego con proxy

### Paso 2: Debuggear en Detalle

Ejecuta el comando de debug específico para sincronización de hora:

```bash
php artisan external:debug-hora
```

**¿Qué hace?**
- Testea conectividad a cada URL
- Intenta sincronizar hora
- Muestra qué API se usó o por qué falló
- Sugiere soluciones

### Paso 3: Debuggear con Verbose

Si necesitas más detalles, usa:

```bash
php artisan external:debug-hora --verbose
```

Luego revisa los logs:
```bash
tail -f storage/logs/laravel.log | grep "SincronizacionHoraService"
```

---

## 🔍 Soluciones por Causa Probable

### Caso 1: "Connection refused" o "Network unreachable"

**Causa:** Firewall/Proxy corporativo bloqueando las APIs

**Solución:**
```bash
# 1. Configurar proxy en .env
echo 'HTTP_PROXY=http://proxy.empresa.com:8080' >> .env
echo 'HTTPS_PROXY=https://proxy.empresa.com:8080' >> .env

# 2. Testear conectividad
php artisan external:test-connectivity

# 3. Limpiar caché y reintentar
php artisan external:clean-cache --hora
```

### Caso 2: "Timeout" o "Connection timed out"

**Causa:** APIs lentes o conexión inestable

**Solución:**
```bash
# 1. Aumentar timeout en config/external_downloads.php:
# 'sincronizacion_hora' => [
#     'timeout' => 30, // Aumentar a 30s
# ]

# 2. Limpiar caché para forzar reintento
php artisan external:clean-cache --hora

# 3. Verificar latencia de red
ping worldtimeapi.org
```

### Caso 3: Muestra hora del servidor pero sin mensajes de error

**Causa:** APIs retornan fallback silenciosamente (caché expirado o primera vez)

**Solución:**
```bash
# 1. Forzar resincronización limpiando caché
php artisan external:clean-cache --hora

# 2. Recargar página del panel para que llame /hora-uruguay nuevamente

# 3. Revisar console.log en F12 del navegador
# Debe mostrar: "Hora sincronizada con..."
```

### Caso 4: Reintentos lentos o no visibles

**Causa:** Caché muy corto, reintentos frecuentes pero lentos

**Solución:**
```bash
# Ya arreglado en la última actualización:
# - Timeout aumentado de 15s a 20s
# - Reintentos aumentados de 1 a 2
# - Caché aumentado de 5min a 10min
# - Retry delay aumentado de 500ms a 1000ms

# Solo ejecuta esto si aún tienes problemas:
php artisan external:clean-cache --all
```

---

## 🧪 Testear Manualmente

### Testear API directamente:

```bash
# WorldTimeAPI (HTTPS)
curl -s https://worldtimeapi.org/api/timezone/America/Montevideo | jq .

# TimeAPI.io
curl -s https://timeapi.io/api/Time/current/zone?timeZone=America/Montevideo | jq .

# WorldTimeAPI (HTTP fallback)
curl -s http://worldtimeapi.org/api/timezone/America/Montevideo | jq .
```

### Testear endpoint Laravel:

```bash
curl -s http://localhost:8000/hora-uruguay | jq .
```

**Salida esperada:**
```json
{
  "success": true,
  "datetime": "2026-06-05T15:30:45...",
  "timezone": "America/Montevideo",
  "source": "worldtimeapi",
  "synced": true,
  "drift_seconds": 0
}
```

---

## 📊 Monitoreo Continuo

### Ver estado actual:

```bash
curl http://localhost:8000/health/external-downloads | jq .services.sincronizacion_hora
```

### Ver estadísticas últimas 24h:

```bash
curl http://localhost:8000/health/external-downloads-stats | jq .stats.sincronizacion_hora
```

### Revisar logs de auditoría:

```bash
# Última sincronización
php artisan tinker
>>> \App\Models\ExternalDownloadLog::forService('sincronizacion_hora')->latest()->first()
```

---

## 🛠️ Comandos Útiles

```bash
# Test general de conectividad
php artisan external:test-connectivity

# Debug específico de hora
php artisan external:debug-hora

# Debug con verbose (más logs)
php artisan external:debug-hora --verbose

# Limpiar caché de hora (fuerza reintento)
php artisan external:clean-cache --hora

# Limpiar TODO
php artisan external:clean-cache --all

# Resetear circuit breakers
php artisan external:clean-cache --circuit-breaker
```

---

## 📝 Qué Revisar en Logs

**Archivo:** `storage/logs/laravel.log`

**Buscar:**
```
grep "SincronizacionHoraService" storage/logs/laravel.log | tail -20
```

**Logs esperados en éxito:**
```
[2026-06-05 15:30:45] local.INFO: SincronizacionHoraService: Iniciando sincronización de hora desde APIs externas
[2026-06-05 15:30:45] local.DEBUG: SincronizacionHoraService: URL intento 1/3: https://worldtimeapi.org/api/timezone/America/Montevideo
[2026-06-05 15:30:47] local.INFO: SincronizacionHoraService: ✅ Hora sincronizada exitosamente desde worldtimeapi
```

**Logs esperados en fallback:**
```
[2026-06-05 15:30:45] local.INFO: SincronizacionHoraService: Iniciando sincronización de hora desde APIs externas
[2026-06-05 15:30:45] local.DEBUG: SincronizacionHoraService: URL intento 1/3: https://worldtimeapi.org/...
[2026-06-05 15:30:47] local.WARNING: SincronizacionHoraService: Exception al obtener hora desde https://...: Connection timed out
[2026-06-05 15:32:00] local.WARNING: SincronizacionHoraService: ⚠️  Todas las APIs fallaron, usando hora del servidor como fallback
```

---

## ✅ Checklist de Solución

- [ ] Ejecuté `php artisan external:debug-hora`
- [ ] Revisé logs: `tail -f storage/logs/laravel.log | grep SincronizacionHoraService`
- [ ] Configuré proxy si es necesario en `.env`
- [ ] Ejecuté `php artisan external:clean-cache --hora`
- [ ] Recargué el panel
- [ ] Verifiqué en F12 console que dice "Hora sincronizada con..."
- [ ] Las APIs responden a manual curl test

---

## 📞 Si Nada Funciona

Si después de todo esto sigue fallando:

1. **Testea la UR** - Si la UR funciona pero hora no, es específico del servicio de hora
2. **Testea SOA** - Si también fallan, es problema de red/proxy general
3. **Revisa logs** de `storage/logs/laravel.log` y comparte los errores específicos
4. **Verifica proxy** - Que esté configurado correctamente en `.env`
5. **Considera fallback manual** - El sistema ya cachea y usa hora del servidor como fallback automático

El sistema está diseñado para **fallar gracefully** - si las APIs externas no funcionan, automáticamente usa la hora del servidor y cachea el resultado.
