# Checklist para Deploy a Producción - Laravel 12

## ⚠️ ANTES DE DESPLEGAR

### 1. Verificar Archivos `.env` en Producción

**Agregar obligatoriamente**:

```env
MAIL_FROM_ADDRESS=noreply@oficinas.gub.uy
MAIL_FROM_NAME="Sistema Tesorería Oficinas"
```

> **Crítico**: Sin `MAIL_FROM_ADDRESS`, los backups fallarán con:  
> `Spatie\Backup\Exceptions\InvalidConfig: No sender email address specified`

### 2. Probar Backup Manualmente

```bash
php artisan backup:run --only-db
```

Verificar que se crea en `storage/app/Oficinas/`

### 3. Verificar Permisos de Escritura

```bash
# En Linux/Ubuntu servidor
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
chown -R www-data:www-data storage/
chown -R www-data:www-data bootstrap/cache/
```

### 4. Limpiar Cachés

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize
```

### 5. Verificar Tareas Programadas (Cron)

En crontab del servidor:

```cron
* * * * * cd /ruta/a/oficinas && php artisan schedule:run >> /dev/null 2>&1
```

Esto ejecutará automáticamente:
- `backup:clean` a la 01:00
- `backup:run` a las 02:00
- `cfe:detect-duplicates` a las 04:00
- `cfe:expirar-pendientes` a las 05:00

### 6. Verificar Sincronización de Hora

```bash
php artisan external:debug-hora
```

Debe mostrar la hora correcta del servidor externo.

## 📋 CONFIGURACIÓN DE ENTORNO

### Variables Críticas en Producción

```env
# Aplicación
APP_ENV=production
APP_DEBUG=false
APP_URL=https://oficinas.gub.uy

# Base de Datos (NO MODIFICAR tesoreria_oficinas)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tesoreria_oficinas
DB_USERNAME=usuario_produccion
DB_PASSWORD=contraseña_segura

# Sesiones
SESSION_DRIVER=database
SESSION_LIFETIME=180
SESSION_SECURE_COOKIE=true  # Si usa HTTPS

# Email (obligatorio para backups)
MAIL_FROM_ADDRESS=noreply@oficinas.gub.uy
MAIL_FROM_NAME="Sistema Tesorería"

# Opcional: Email SMTP real para notificaciones
MAIL_MAILER=smtp
MAIL_HOST=smtp.servidor.gub.uy
MAIL_PORT=587
MAIL_USERNAME=usuario_smtp
MAIL_PASSWORD=contraseña_smtp
MAIL_ENCRYPTION=tls
```

## 🚀 PASOS DE DEPLOY

### 1. Hacer Pull del Branch

```bash
cd /ruta/a/oficinas
git checkout feature/laravel-12-upgrade
git pull origin feature/laravel-12-upgrade
```

### 2. Instalar Dependencias

```bash
composer install --no-dev --optimize-autoloader
npm ci --production
```

### 3. Compilar Assets

```bash
npm run build
```

### 4. Actualizar Base de Datos

```bash
php artisan migrate --force
```

> **Nota**: Las migraciones ya fueron ejecutadas en desarrollo, este paso debería mostrar "Nothing to migrate".

### 5. Limpiar y Optimizar

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 6. Reiniciar Servicios

```bash
# Reiniciar PHP-FPM (ajustar según versión)
sudo systemctl restart php8.2-fpm

# Reiniciar Nginx/Apache
sudo systemctl restart nginx
# o
sudo systemctl restart apache2
```

## ✅ POST-DEPLOY: VERIFICACIONES

### 1. Acceso a la Aplicación

- [ ] Login funciona correctamente
- [ ] Dashboard carga sin errores
- [ ] Auto-refresh funciona (esperar 1-10 min según vista)

### 2. Funcionalidades Críticas

- [ ] Gestión de CFEs
- [ ] Caja Chica abre correctamente
- [ ] Caja Diaria muestra arqueos
- [ ] Libro Diario despliega asientos
- [ ] Multas (artículo y 303) cargan

### 3. Backups

```bash
# Ejecutar manualmente
php artisan backup:run --only-db

# Verificar que se creó
ls -lh storage/app/Oficinas/
```

### 4. Logs

```bash
# Verificar que no haya errores críticos
tail -f storage/logs/laravel.log
```

## 🔧 TROUBLESHOOTING

### Error: "Class 'Livewire\Livewire' not found"

```bash
composer dump-autoload
php artisan optimize:clear
```

### Error: Assets no cargan en subdirectorio

Verificar en `app/Providers/AppServiceProvider.php`:

```php
// Debe existir la detección de subdirectorios
if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/oficinas/public')) {
    // ...
}
```

### Error: Backup falla

1. Verificar `MAIL_FROM_ADDRESS` en `.env`
2. Verificar permisos en `storage/app/`
3. Revisar logs: `storage/logs/laravel.log`

### Error: Hora incorrecta en sincronización

```bash
php artisan external:debug-hora
```

Verificar configuración de proxy en `.env` si es necesario.

## 📊 MONITOREO POST-DEPLOY

### Primeras 24 Horas

- [ ] Revisar logs cada 2 horas
- [ ] Verificar que cron ejecutó backups (02:00)
- [ ] Confirmar que CFEs se procesan correctamente
- [ ] Verificar que auto-refresh no causa problemas de rendimiento

### Primera Semana

- [ ] Revisar logs diariamente
- [ ] Confirmar que backups se crean automáticamente
- [ ] Verificar espacio en disco (`df -h`)
- [ ] Consultar con usuarios sobre problemas

## 🔄 ROLLBACK (Si algo falla)

```bash
# 1. Volver a versión anterior
git checkout main
git pull origin main

# 2. Restaurar dependencias
composer install --no-dev --optimize-autoloader

# 3. Limpiar cachés
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

# 4. Reiniciar servicios
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

## 📝 NOTAS IMPORTANTES

### Base de Datos

**⚠️ NUNCA MODIFICAR LA BD `tesoreria_oficinas` DIRECTAMENTE**

Esta BD contiene datos de producción críticos. Cualquier cambio de esquema debe hacerse mediante migraciones de Laravel.

### Auto-Refresh

Los componentes ahora se actualizan automáticamente:
- Dashboard: cada 1 minuto
- Operaciones (Cajas, CFEs): cada 5 minutos  
- Históricos (Libro Diario, Multas): cada 10 minutos

Esto **no afecta rendimiento** porque solo actualiza la vista activa (pestaña visible).

### Backups Automáticos

Los backups se ejecutan automáticamente vía cron a las 02:00 AM diario.  
Retención: 30 días.

---

**Última actualización**: 15/08/2026  
**Versiones**: Laravel 12.66.0 + Livewire 4.4.0 + Spatie Backup 9.3.6
