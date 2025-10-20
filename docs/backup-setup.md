Instalación y configuración de spatie/laravel-backup

Resumen

- Objetivo: Crear respaldos diarios (código + estructura + datos) automáticamente.
- Horario: limpieza a las 01:00, creación de backup a las 03:00.
- Retención: mantener respaldos por 30 días.

Pasos de instalación

1) Instalar el paquete via Composer:

   composer require spatie/laravel-backup

2) Publicar los archivos de configuración y migraciones (si aplica):

   php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider" --tag="config"

3) (Opcional) Crear el enlace simbólico de storage si no existe:

   php artisan storage:link

4) Verificar `config/backup.php` (ya preconfigurado en el proyecto para disco `local` y retención de 30 días).

Scheduler en Windows (Task Scheduler)

Laravel scheduler necesita ejecutarse cada minuto. En Windows, crea una tarea programada:

- Programa: Ejecutar cada 1 minuto (o cada 5 minutos si prefieres).
- Acción: Ejecutar `php` con argumentos `artisan schedule:run`.
- Working directory: la carpeta del proyecto (ej. C:\xampp\htdocs\oficinas)

Ejemplo de comando (PowerShell):

   php C:\xampp\htdocs\oficinas\artisan schedule:run

Asegúrate de ejecutar la tarea con un usuario que tenga permisos para acceder a los archivos de proyecto.

Comandos útiles

- Forzar limpieza y backup manualmente:

   php artisan backup:clean
   php artisan backup:run

- Verificar discos configurados en `config/filesystems.php`.

Notas y recomendaciones

- Para almacenar respaldos fuera del servidor (S3, FTP), configura los discos en `config/filesystems.php` y agrega el nombre del disco a `config/backup.php`.
- Revisa el tamaño de almacenamiento y programa alertas si la carpeta de respaldos crece demasiado.
- Puedes habilitar notificaciones por email o Slack en `config/backup.php`.
