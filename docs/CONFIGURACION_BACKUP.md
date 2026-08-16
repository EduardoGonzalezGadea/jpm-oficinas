# Configuración de Backups - Spatie Backup 9.x

## Requisito Obligatorio

Para que funcione **Spatie Backup 9.3.6+**, se requiere agregar al archivo `.env`:

```env
MAIL_FROM_ADDRESS=noreply@oficinas.local
MAIL_FROM_NAME="${APP_NAME}"
```

> **Nota**: Incluso si las notificaciones por email están deshabilitadas, Spatie Backup valida la configuración de email en la inicialización.

## Comandos de Backup

### Crear Backup (Solo Base de Datos)

```bash
php artisan backup:run --only-db
```

### Crear Backup Completo (BD + Archivos)

```bash
php artisan backup:run
```

### Limpiar Backups Antiguos

```bash
php artisan backup:clean
```

## Ubicación de Backups

Los backups se almacenan en:

```
storage/app/Oficinas/
```

## Configuración Actual

### Retención de Backups

- **Mantener todos los backups**: 30 días
- **Backups diarios**: 30 días
- **Backups semanales**: Deshabilitado
- **Backups mensuales**: Deshabilitado

### Notificaciones

Actualmente **deshabilitadas** (arrays vacíos en `config/backup.php`).

Si deseas activar notificaciones por email en producción:

1. Configurar servidor SMTP en `.env`:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.ejemplo.com
   MAIL_PORT=587
   MAIL_USERNAME=usuario@ejemplo.com
   MAIL_PASSWORD=contraseña_segura
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=noreply@oficinas.gub.uy
   MAIL_FROM_NAME="Sistema Tesorería"
   ```

2. Editar `config/backup.php` y cambiar arrays vacíos por `['mail']`:
   ```php
   'notifications' => [
       'notifications' => [
           BackupHasFailedNotification::class => ['mail'],
           UnhealthyBackupWasFoundNotification::class => ['mail'],
           // ... etc
       ],
       'mail' => [
           'to' => 'administrador@oficinas.gub.uy',
       ],
   ],
   ```

## Tareas Programadas

En `app/Console/Kernel.php`:

```php
// Limpieza de backups antiguos - 01:00 diario
$schedule->command('backup:clean')
         ->dailyAt('01:00')
         ->withoutOverlapping()
         ->onOneServer();

// Crear backup completo - 02:00 diario
$schedule->command('backup:run')
         ->dailyAt('02:00')
         ->withoutOverlapping()
         ->onOneServer();
```

## Breaking Changes en Spatie Backup 9.x

### Método `createFromArray()` Removido

**Antes** (v8.x):
```php
use Spatie\Backup\Tasks\Backup\BackupJobFactory;

$backupJob = BackupJobFactory::createFromArray(config('backup'));
$backupJob->disableNotifications();
$backupJob->run();
```

**Ahora** (v9.x):
```php
use Illuminate\Support\Facades\Artisan;

Artisan::call('backup:run', [
    '--only-db' => true,
    '--disable-notifications' => true,
]);
```

### Estructura de Configuración Actualizada

El archivo `config/backup.php` fue completamente renovado. Si migras desde v8.x:

```bash
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider" --tag="backup-config" --force
```

## Troubleshooting

### Error: "No sender email address specified"

**Solución**: Agregar al `.env`:
```env
MAIL_FROM_ADDRESS=noreply@oficinas.local
```

### Error: "Call to undefined method BackupJobFactory::createFromArray()"

**Solución**: Republicar configuración actualizada:
```bash
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider" --tag="backup-config" --force
```

## Verificación

Después de configurar, probar manualmente:

```bash
# Backup solo BD (más rápido)
php artisan backup:run --only-db

# Ver últimos backups
ls -lh storage/app/Oficinas/
```

## Restauración (Manual)

```bash
# 1. Extraer ZIP del backup
unzip storage/app/Oficinas/2026-08-15-22-01-13.zip -d restore/

# 2. Restaurar BD
mysql -u usuario -p tesoreria_oficinas < restore/db-dumps/mysql-tesoreria_oficinas.sql
```

---

**Última actualización**: 15/08/2026  
**Versiones**: Laravel 12.66.0 + Spatie Backup 9.3.6
