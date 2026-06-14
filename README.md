# Oficinas | Tesorería

Aplicación interna para gestión administrativa y operativa, con foco principal en los procesos de **Tesorería**. El sistema centraliza autenticación, permisos, auditoría y varios módulos financieros y de recaudación, incluyendo carga manual, reportes, planillas y procesamiento asistido de CFEs.

## Propósito

La aplicación está pensada para cubrir la operativa diaria de una oficina administrativa con fuerte peso en Tesorería. Hoy el sistema permite, entre otras cosas:

- autenticar usuarios con sesión web, JWT y doble factor
- administrar usuarios, roles, permisos y módulos
- operar módulos de Tesorería desde interfaces Blade y Livewire
- registrar y reportar arrendamientos, eventuales, multas, armas, prendas, certificados de residencia y depósito de vehículos
- gestionar caja chica, caja diaria, valores, cheques, bancos y cuentas bancarias
- generar reportes e impresiones por módulo
- exportar datos a Excel (PhpSpreadsheet)
- analizar PDFs de CFE y redirigirlos al módulo correspondiente
- mantener auditoría, respaldos del sistema y sincronización horaria
- descarga SOA de valores
- gestionar pendrive virtual para descarga de archivos

## Stack del Proyecto

| Componente | Versión |
|---|---|
| Laravel | 9.52.21 |
| PHP | ^8.0 |
| Livewire | 2.12.7 |
| Laravel Sanctum | ^2.14 |
| JWT Auth | 1.4.2 (`php-open-source-saver/jwt-auth`) |
| Spatie Permission | 5.11 |
| Spatie Activitylog | ^4.7 |
| Spatie Backup | ^8.2 |
| PhpSpreadsheet | 4.1 |
| Smalot PDF Parser | ^2.0 |
| Google2FA | ^2.3 (`pragmarx/google2fa-laravel`) |
| Intervention Image | ^3.11 |
| Bacon QR Code | ^3.0 |
| Doctrine DBAL | ^3.0 |
| Ziggy | ^2.5 (`tightenco/ziggy`) |
| Laravel Mix | 6 |

La referencia exacta de dependencias está en:
- `composer.json`
- `package.json`

## Módulos Principales

### Administración y Seguridad

- Login y logout web
- Doble factor (Google2FA)
- Usuarios
- Roles y permisos (Spatie Permission)
- Módulos
- Auditoría (Spatie Activitylog)
- Backups (Spatie Backup)
- Cambio de tema
- Pendrive virtual
- Health check
- Sincronización horaria

### Tesorería

- **Arrendamientos** — gestión, reportes, planillas, carga CFE, impresiones avanzadas
- **Eventuales** — gestión, reportes, planillas, carga e-factura y CFE, instituciones asociadas
- **Multas de tránsito** — gestión general y multas Ley 303
- **Multas cobradas** — gestión, reportes, carga CFE, impresiones resumen y avanzadas
- **Armas** — porte y tenencia, planillas, reportes, carga CFE
- **Certificados de residencia** — CRUD completo, reportes, carga CFE, impresiones avanzadas
- **Prendas** — CRUD completo, reportes, planillas, carga CFE, impresiones avanzadas
- **Depósito de vehículos** — CRUD completo, reportes, planillas
- **Bancos** — alta, edición, listado
- **Cuentas bancarias** — alta, edición, listado
- **Cheques** — emisión, libreta, reportes, planillas
- **Caja chica** — fondos, pagos, pendientes, acreedores, dependencias, rendiciones, recuperación
- **Valores** — gestión, entrega, servicio, tipo libreta, reportes
- **Reporte general de recibos** — reporte consolidado con exportación Excel
- **Configuración auxiliar** — categorías 222, conceptos, definiciones ER, instituciones 222, medios de pago, denominaciones y tipos de moneda

## Rutas

La aplicación organiza sus rutas en varios archivos:

| Archivo | Propósito |
|---|---|
| `routes/web.php` | Entrada principal de la aplicación |
| `routes/api.php` | API usada especialmente para flujo de CFE |
| `routes/administracion.php` | Rutas del módulo de administración |
| `routes/tesoreria.php` | Rutas principales de Tesorería |
| `routes/valores.php` | Rutas del submódulo de Valores |
| `routes/backup.php` | Rutas de respaldos |

## Arquitectura a Grandes Rasgos

### Backend

```
app/
├── Builders/            — Query Builders personalizados
├── Console/             — Comandos Artisan
├── Exceptions/          — Manejadores de excepciones
├── Exports/             — Exportaciones a Excel (PhpSpreadsheet)
├── Helpers/             — Funciones auxiliares globales
├── Http/
│   ├── Controllers/
│   │   ├── Api/         — Controladores para API
│   │   └── Tesoreria/   — Controladores de módulos de Tesorería
│   ├── Livewire/
│   │   └── Tesoreria/   — Componentes Livewire por módulo
│   ├── Middleware/       — JWT, permisos, doble factor
│   └── Requests/        — Form Requests
├── Listeners/           — Event listeners
├── Models/
│   └── Tesoreria/       — Modelos Eloquent del dominio financiero
├── Providers/           — Service Providers
├── Repositories/        — Capa de repositorios
├── Services/
│   ├── CfeExtractor/    — Extractores específicos por tipo de CFE
│   ├── Http/            — Cliente HTTP
│   └── Tesoreria/       — Servicios de negocio de Tesorería
└── Traits/              — Traits reutilizables
```

### Frontend

```
resources/
├── views/               — Vistas Blade del panel, autenticación y módulos
│   └── livewire/        — Vistas de componentes Livewire
├── js/                  — JavaScript / Alpine.js
└── css/                 — Estilos
```

### Componentes Destacados

- **`app/Services/CfeProcessorService.php`** — orquestador del procesamiento de CFE
- **`app/Services/CfeExtractor/`** — 7 extractores específicos (armas, arrendamientos, certificados, eventuales, multas, prendas + base)
- **`app/Services/Tesoreria/ReporteRecibosService.php`** — reporte consolidado de recibos
- **`app/Services/Tesoreria/DescargaValoresSoaService.php`** — descarga SOA de valores
- **`app/Http/Controllers/AuthController.php`** — autenticación híbrida
- **`app/Http/Controllers/CfeController.php`** — entrada de CFE por API
- **`app/Http/Livewire/Tesoreria/CajaChica/Index.php`** — caja chica (operativa compleja)
- **`app/Http/Livewire/Tesoreria/MultasCobradas/MultasCobradas.php`** — multas cobradas
- **`app/Http/Livewire/Tesoreria/Valores/Reportes/Index.php`** — reportes de valores

## Flujo Importante: CFE

El procesamiento de CFEs es una capacidad transversal del sistema.

Flujo resumido:

1. entra un PDF por API (`CfeController`) o análisis manual
2. `CfeProcessorService` delega en el extractor específico según el tipo detectado
3. el servicio detecta el tipo de comprobante y extrae datos con reglas específicas
4. prepara un prefill temporal y redirige al módulo final de Tesorería

Participan los módulos:
- multas cobradas
- eventuales
- arrendamientos
- prendas
- certificados de residencia
- armas

También existen dos extensiones en el repo:
- `extension-cfe-detect/`
- `extension-text-replacer/`

## Autenticación y Acceso

La aplicación usa autenticación híbrida:

- sesión web de Laravel
- JWT en cookie para parte del acceso protegido
- doble factor con Google2FA
- permisos y roles con Spatie Permission

La mayor parte de la aplicación protegida cuelga del grupo de rutas con:
- `jwt.verify`
- `two-factor`

## Testing

Pruebas existentes organizadas por tipo:

```
tests/
├── Feature/
│   └── Tesoreria/       — Multa303Test, MultasCobradasTest
├── Unit/
│   ├── Models/Tesoreria — PagoTest
│   └── Services/
│       ├── CfeExtractor/  — Tests para cada extractor + detección de tipo
│       └── Tesoreria/     — MedioPagoServiceTest, MedioPagoServiceSimpleTest
```

Ejecución:
```bash
php artisan test
```

Nota: la cobertura automatizada aún es baja pero está en crecimiento.

## Documentación del Repo

- `docs/TROUBLESHOOTING_HORA.md` — solución a problemas de sincronización horaria
- `docs/EXTERNAL_DOWNLOADS_IMPLEMENTATION.md` — implementación de descargas externas
- `docs/eventuales/` — PDFs de ejemplo para pruebas con eventuales
- `extension-cfe-detect/` — extensión Firefox para detección de CFE
- `extension-text-replacer/` — extensión Firefox para reemplazo de texto

## Puesta en Marcha

### Requisitos

- PHP 8
- Composer
- Node.js y npm
- Base de datos compatible con Laravel 9 (MySQL / MariaDB)

### Instalación

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

Según el entorno local, también puede levantarse con Apache o XAMPP apuntando a este proyecto.