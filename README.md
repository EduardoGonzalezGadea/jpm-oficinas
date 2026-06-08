# Oficinas | Tesoreria

Aplicacion interna para gestion administrativa y operativa, con foco principal en los procesos de **Tesoreria**. El sistema centraliza autenticacion, permisos, auditoria y varios modulos financieros y de recaudacion, incluyendo carga manual, reportes, planillas y procesamiento asistido de CFEs.

## Proposito

La aplicacion esta pensada para cubrir la operativa diaria de una oficina administrativa con fuerte peso en Tesoreria. Hoy el sistema permite, entre otras cosas:

- autenticar usuarios con sesion web, JWT y doble factor
- administrar usuarios, roles, permisos y modulos
- operar modulos de Tesoreria desde interfaces Blade y Livewire
- registrar y reportar arrendamientos, eventuales, multas, armas, prendas y certificados
- gestionar caja chica, caja diaria, valores, cheques, bancos y cuentas bancarias
- generar reportes e impresiones por modulo
- analizar PDFs de CFE y redirigirlos al modulo correspondiente
- mantener auditoria y respaldos del sistema

## Modulos Principales

### Administracion y seguridad

- Login y logout web
- Doble factor
- Usuarios
- Roles
- Permisos
- Modulos
- Auditoria
- Backups
- Cambio de tema
- Pendrive virtual

### Tesoreria

- Arrendamientos
- Eventuales
- Multas de transito
- Multas cobradas
- Armas
- Certificados de residencia
- Prendas
- Deposito de vehiculos
- Bancos
- Cuentas bancarias
- Cheques
- Caja chica
- Caja diaria
- Valores
- Reporte general de recibos
- Configuraciones auxiliares

## Stack Real del Proyecto

El stack activo detectado en codigo es:

- Laravel 9.52.21
- PHP 8
- Livewire 2.12.7
- Laravel Sanctum
- JWT Auth (`php-open-source-saver/jwt-auth`)
- Spatie Permission
- Spatie Activitylog
- Spatie Backup
- PhpSpreadsheet
- Smalot PDF Parser
- Laravel Mix 6

La referencia correcta para dependencias es:

- `composer.json`
- `package.json`

## Arquitectura a Grandes Rasgos

### Backend

- `app/Http/Controllers`
  Controladores para autenticacion, administracion y algunos modulos de Tesoreria.
- `app/Http/Livewire`
  Capa reactiva principal. Gran parte de la operativa vive aqui.
- `app/Models/Tesoreria`
  Modelos Eloquent del dominio financiero.
- `app/Services`
  Servicios transversales. Destacan el parser de CFE y reportes consolidados.
- `app/Http/Middleware`
  JWT, permisos y doble factor.

### Frontend

- `resources/views`
  Vistas Blade del panel, autenticacion y modulos.
- `resources/views/livewire`
  Vistas de componentes Livewire.
- `resources/js`
- `resources/css`

### Rutas

- `routes/web.php`
  Entrada principal de la aplicacion.
- `routes/api.php`
  API usada especialmente para flujo de CFE.
- `routes/valores.php`
  Rutas del submodulo de Valores.

## Flujo Importante: CFE

El procesamiento de CFEs es una capacidad importante del sistema, pero ya no define por si solo a toda la aplicacion.

Flujo resumido:

1. entra un PDF por API o analisis manual
2. `CfeController` delega en `CfeProcessorService`
3. el servicio detecta el tipo de comprobante
4. extrae datos por reglas especificas
5. prepara un prefill temporal
6. redirige al modulo final de Tesoreria

Puede intervenir en modulos como:

- multas cobradas
- eventuales
- arrendamientos
- prendas
- certificados de residencia
- armas

Tambien existen dos extensiones en el repo:

- `extension-cfe-detect`
- `extension-text-replacer`

## Autenticacion y Acceso

La aplicacion usa una autenticacion hibrida:

- sesion web de Laravel
- JWT en cookie para parte del acceso protegido
- permisos y roles con Spatie
- doble factor

La mayor parte de la aplicacion protegida cuelga del grupo de rutas con:

- `jwt.verify`
- `two-factor`

## Estructura del Dominio

Si vas a tocar un modulo, la ruta habitual para ubicarte es:

1. buscar la entrada en `routes/web.php`
2. encontrar el controlador o componente Livewire
3. revisar el modelo principal en `app/Models/Tesoreria`
4. ubicar servicios asociados en `app/Services` o `app/Services/Tesoreria`

Piezas especialmente sensibles:

- `app/Services/CfeProcessorService.php`
- `app/Services/Tesoreria/ReporteRecibosService.php`
- `app/Http/Controllers/AuthController.php`
- `app/Http/Livewire/Tesoreria/CajaChica/Index.php`
- `app/Http/Livewire/Tesoreria/MultasCobradas/MultasCobradas.php`
- `app/Http/Livewire/Tesoreria/Valores/Reportes/Index.php`

## Puesta en Marcha

### Requisitos

- PHP 8
- Composer
- Node.js y npm
- Base de datos compatible con Laravel 9

### Instalacion

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

### Desarrollo

```bash
php artisan serve
npm run dev
```

Segun el entorno local, tambien puede levantarse con Apache o XAMPP apuntando a este proyecto.

## Testing

La cobertura automatizada actual es baja. Existen pruebas base y algunas pruebas de servicios:

- `tests/Feature`
- `tests/Unit`
- `tests/Unit/Services/Tesoreria`

Ejecucion:

```bash
php artisan test
```

## Documentacion del Repo

Para orientarte mas rapido dentro del sistema:

- `docs/INDICE_APLICACION.md`
- `docs/MAPA_FLUJOS_APLICACION.md`
- `docs/PLAN_CAJA_DIARIA.md`
- `docs/DOCUMENTACION_MULTAS_COBRADAS.md`
- `docs/DOCUMENTACION_PRENDAS.md`

## Estado del README

Este README describe la aplicacion actual como sistema integral de gestion y Tesoreria. Reemplaza la descripcion anterior, que estaba enfocada solo en el experimento inicial de procesamiento de CFEs y ya no reflejaba el contenido real del repositorio.
