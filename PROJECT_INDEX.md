# Índice del Proyecto - Tesorería Oficinas

## Información General

- **Framework**: Laravel 10 + PHP 8.1+ + Livewire 3.x
- **Base de datos**: MySQL/MariaDB
- **Autenticación**: Híbrida (Sesión Laravel + JWT en cookie HTTP-only + Spatie Permission)
- **Frontend**: Blade + Livewire + Laravel Mix (webpack)
- **Fecha del análisis**: 09/08/2026

---

## Estructura de Directorios Principales

```
app/
├── Builders/              # Query builders personalizados
├── Console/               # Comandos Artisan
├── DataTransferObjects/   # DTOs para transferencia de datos
├── DTOs/                  # Data Transfer Objects
├── Events/                # Eventos de Laravel
├── Exceptions/            # Excepciones personalizadas
├── Exports/               # Exportaciones (Excel, PDF)
├── Helpers/               # Helpers globales
├── Http/
│   ├── Controllers/       # Controladores clásicos
│   ├── Livewire/          # Componentes Livewire (lógica de negocio principal)
│   ├── Middleware/        # Middlewares (JWT, 2FA, permisos)
│   ├── Requests/          # Form Requests
│   └── Responses/         # Respuestas personalizadas
├── Jobs/                  # Jobs para colas
├── Listeners/             # Event listeners
├── Livewire/              # Componentes Livewire compartidos
├── Models/
│   └── Tesoreria/         # Modelos Eloquent del dominio financiero
├── Modules/               # Registro de módulos y permisos
├── Providers/             # Service Providers
├── Repositories/          # Repositorios (patrón Repository)
├── Services/              # Servicios de negocio
│   ├── CfeExtractor/      # Extractores por tipo de CFE
│   ├── Http/              # Cliente HTTP centralizado
│   └── Tesoreria/         # Servicios específicos de Tesorería
└── Traits/                # Traits reutilizables

config/
├── external_downloads.php # Configuración descargas externas (UR, Hora, SOA)
├── cfe.php                # Configuración procesamiento CFE
├── permission.php         # Spatie Permission
├── jwt.php                # JWT Auth
└── ...                    # Otros configs estándar

database/
├── migrations/            # 70+ migraciones
├── seeders/               # Seeders
└── factories/             # Factories para testing

routes/
├── web.php                # Punto de entrada principal
├── administracion.php     # Usuarios, roles, permisos, módulos
├── tesoreria.php          # Todos los submódulos de Tesorería
├── valores.php            # Gestión de Valores
├── asesoria_contable.php  # Módulo Asesoría Contable
├── api.php                # Endpoints API (procesamiento CFE)
└── console.php            # Comandos Artisan

resources/
├── views/
│   ├── livewire/          # Vistas de componentes Livewire
│   └── tesoreria/         # Vistas específicas de Tesorería
├── js/                    # Assets JS
└── css/                   # Assets CSS

tests/
├── Feature/               # Tests de integración
│   └── Tesoreria/         # Tests por módulo
└── Unit/                  # Tests unitarios
    └── Services/          # Tests de servicios

docs/                      # Documentación funcional y técnica
```

---

## Módulos Principales de Tesorería

| Módulo | Ruta | Componente Livewire Principal | Modelo Principal |
|--------|------|------------------------------|------------------|
| **Dashboard** | `/tesoreria` | - | - |
| **Multas Cobradas** | `/tesoreria/multas-cobradas` | `MultasCobradas` | `TesMultasCobradas` |
| **Eventuales** | `/tesoreria/eventuales` | `Eventuales\Index` | `TesEventuales` |
| **Arrendamientos** | `/tesoreria/arrendamientos` | `Arrendamientos\Index` | `TesArrendamientos` |
| **Armas (Porte/Tenencia)** | `/tesoreria/armas` | `Armas\PorteArmas` / `TenenciaArmas` | `TesPorteArmas` / `TesTenenciaArmas` |
| **Certificados Residencia** | `/tesoreria/certificados-residencia` | `CertificadosResidencia\Index` | `TesCertificadosResidencia` |
| **Prendas** | `/tesoreria/prendas` | `Prendas\Index` | `TesPrendas` |
| **Caja Chica** | `/tesoreria/caja-chica` | `CajaChica\Index` | `CajaChica` |
| **Caja Diaria** | `/tesoreria/caja-diaria` | `CajaDiaria\Index` | `CajaApertura` / `CajaMovimiento` |
| **Gestión CFE** | `/tesoreria/gestion-cfe` | `GestionCfe\Index` | `TesCfe` / `TesCfePendiente` |
| **Libro Diario** | `/tesoreria/libro-diario` | `LibroDiario\Index` | `LibroDiario` |
| **Valores** | `/valores` | `Valores\Index` | `LibretaValor` / `Servicio` |
| **Tarjetas BROU** | `/tesoreria/tarjetas-cobro-brou` | `TarjetasCobroBrou\Index` | `TesTarjetasCobroBrou` |
| **Depósito Vehículos** | `/tesoreria/deposito-vehiculos` | `DepositoVehiculos\Index` | `DepositoVehiculo` |
| **Cheques** | `/tesoreria/cheques` | Vistas Blade + Controller | `TesCheques` |
| **Bancos/Cuentas** | `/tesoreria/bancos` | Controller | `Banco` / `CuentaBancaria` |

---

## Servicios Críticos

### 1. HttpClientService (`app/Services/Http/HttpClientService.php`)
- **Propósito**: Cliente HTTP centralizado para descargas externas
- **Características**:
  - Auto-detección de proxy desde variables de entorno
  - Reintentos con exponential backoff
  - Circuit breaker (3 fallos → 5 min espera)
  - Logging detallado
  - Validación de conectividad del proxy

### 2. ValorUrService (`app/Services/ValorUrService.php`)
- **Propósito**: Descarga valor UR desde BPS
- **Fuente**: `https://www.bps.gub.uy/bps/valores.jsp?contentid=5478`
- **Caché**: 4 horas
- **Timeout**: 45 segundos
- **Fallback**: Último valor válido descargado

### 3. SincronizacionHoraService (`app/Services/SincronizacionHoraService.php`)
- **Propósito**: Sincroniza hora desde APIs públicas
- **Fuentes**: worldtimeapi.org, timeapi.io
- **Caché**: 5 minutos (configurable)
- **Fallback**: Hora del servidor local

### 4. DescargaValoresSoaService (`app/Services/Tesoreria/DescargaValoresSoaService.php`)
- **Propósito**: Descarga valores SOA del BCU, actualiza multas Art. 184
- **Fuente**: BCU (página web + PDF)
- **Caché**: 7 días
- **Actualiza**: Modelo `Multa` con valores SOA

### 5. CfeProcessorService (`app/Services/CfeProcessorService.php`)
- **Propósito**: Parser PDF y ruteo de módulos - **central para flujo CFE**
- **Flujo**:
  1. PDF entra via API o carga manual
  2. Detecta tipo de documento via regex
  3. Extrae datos y crea prefill temporal (`TesCfePendiente`)
  4. Redirige al módulo destino
  5. Usuario confirma/completa datos en formulario final
- **Extractores**: 6 extractores especializados (Multas, Prendas, Arrendamientos, Eventuales, Certificados, Armas)

---

## Flujo de Autenticación

```
1. Usuario accede a /login
2. POST /login → AuthController::login()
3. JWTAuth::attempt() → genera token
4. Token guardado en cookie HTTP-only (jwt_token)
5. Sesión Laravel iniciada (Auth::login)
6. Roles/permisos cargados en sesión
7. Middleware chain: web → jwt.verify → two-factor
```

### Middlewares Clave
- **JwtVerify**: Verifica JWT en cookie, inicia sesión Laravel
- **TwoFactor**: Verifica 2FA si habilitado
- **ModuloMiddleware**: Verifica acceso a módulo específico (tesoreria, asesoria_contable)

---

## Sistema de Permisos (Spatie + ModuleRegistry)

### Módulos Registrados
```php
ModuleRegistry::MODULES = [
    'tesoreria' => [
        'niveles' => ['gerente', 'supervisor', 'operador'],
        'recursos' => ['pagos', 'conceptos', 'multas', 'caja-chica', 'valores', 
                       'arrendamientos', 'eventuales', 'certificados', 'prendas', 'armas']
    ],
    'asesoria_contable' => [
        'niveles' => ['gerente', 'supervisor', 'operador'],
        'recursos' => ['eventuales', 'arrendamientos', 'certificados']
    ]
];
```

### Formato de Permisos
```
{modulo}.{recurso}.{accion}
Ej: tesoreria.multas.crear, tesoreria.caja-chica.ver
```

### Roles Generados
```
tesoreria_gerente, tesoreria_supervisor, tesoreria_operador
asesoria_contable_gerente, asesoria_contable_supervisor, asesoria_contable_operador
administrador (acceso total)
```

---

## Modelos Principales (Tesoreria)

| Modelo | Tabla | Descripción |
|--------|-------|-------------|
| `TesMultasCobradas` | `tes_multas_cobradas` | Multas cobradas con items |
| `TesMultasItems` | `tes_multas_items` | Items de multas cobradas |
| `TesEventuales` | `tes_eventuales` | Eventuales |
| `TesEventualesPlanilla` | `tes_eventuales_planillas` | Planillas de eventuales |
| `TesArrendamientos` | `tes_arrendamientos` | Arrendamientos |
| `TesArrPlanillas` | `tes_arr_planillas` | Planillas de arrendamientos |
| `TesPorteArmas` / `TesTenenciaArmas` | `tes_porte_armas` / `tes_tenencia_armas` | Armas |
| `TesCertificadosResidencia` | `tes_certificados_residencia` | Certificados |
| `TesPrendas` | `tes_prendas` | Prendas |
| `CajaChica` | `tes_caja_chica` | Caja chica |
| `CajaApertura` / `CajaMovimiento` | `tes_cajas_aperturas` / `tes_cajas_movimientos` | Caja diaria |
| `TesCfe` / `TesCfePendiente` | `tes_cfes` / `tes_cfe_pendientes` | CFEs procesados y pendientes |
| `LibroDiario` | `tes_libro_diario` | Libro diario contable |
| `MedioDePago` | `tes_medio_de_pagos` | Medios de pago |
| `Multa` | `tes_multas` | Catálogo de multas (con valores SOA) |
| `Multa303` | `tes_multas_303_2023` | Códigos multas CPT Dec. 303/2023 |

---

## Comandos Principales

```bash
# Desarrollo
php artisan serve              # Servidor desarrollo
npm run dev                    # Compilar assets (Laravel Mix)

# Base de datos
php artisan migrate            # Ejecutar migraciones
php artisan migrate:fresh --seed  # Reset + seed

# Testing
php artisan test               # Todos los tests
php artisan test --filter=MultasCobradasTest  # Test específico

# Descargas externas
php artisan external:test-connectivity  # Test conectividad UR/Hora/SOA

# Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clave aplicación
php artisan key:generate
```

---

## Endpoints de Health Check

| Endpoint | Descripción |
|----------|-------------|
| `GET /health/external-downloads` | Status actual de cada servicio (UR, Hora, SOA) |
| `GET /health/external-downloads-stats` | Stats últimas 24h de descargas |
| `GET /health/cfe` | Health check del procesador CFE |

---

## Configuración de Descargas Externas (`config/external_downloads.php`)

### Servicios Configurados
1. **valor_ur** - BPS (Unidad Reajustable)
2. **sincronizacion_hora** - APIs públicas de hora
3. **valores_soa** - BCU (Seguro Obligatorio Automotor)

### Características Comunes
- Proxy auto-detectado (`HTTP_PROXY`, `HTTPS_PROXY`, `NO_PROXY`)
- Reintentos con exponential backoff
- Circuit breaker configurable
- Caché por servicio con TTL independiente
- Timeouts y validaciones por servicio
- Modo debug opcional

---

## Deuda Técnica Identificada

1. **routes/web.php** - Archivo grande que mezcla closures, controladores y Livewire
2. **Lógica de negocio en Livewire** - Componentes grandes con mucha lógica embebida
3. **Baja cobertura de tests** - Operaciones financieras críticas sin tests suficientes
4. **Parser CFE frágil** - Extracción basada en regex sobre texto de PDF
5. **Falta de separación de capas** - Servicios y Livewire mezclan responsabilidades

---

## Documentación en `/docs/`

| Archivo | Descripción |
|---------|-------------|
| `INDICE_APLICACION.md` | Índice técnico general |
| `MAPA_FLUJOS_APLICACION.md` | Diagramas de flujo |
| `PLAN_CAJA_DIARIA.md` | Plan módulo caja diaria |
| `DOCUMENTACION_MULTAS_COBRADAS.md` | Documentación multas |
| `DOCUMENTACION_PRENDAS.md` | Documentación prendas |
| `EXTERNAL_DOWNLOADS_IMPLEMENTATION.md` | Arquitectura descargas externas |
| `OPTIMIZACION_N+1_*.md` | Optimizaciones de consultas |
| `PLAN_MEJORAS_GESTION_RECAUDACIONES.md` | Plan mejoras recaudaciones |

---

## Variables de Entorno Críticas (.env)

```env
# App
APP_KEY=
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=oficinas
DB_USERNAME=
DB_PASSWORD=

# JWT
JWT_SECRET=
JWT_TTL=1440

# External Downloads
EXTERNAL_DOWNLOADS_ENABLED=true
EXTERNAL_DOWNLOADS_DEBUG=false
EXTERNAL_DOWNLOADS_VERIFY_SSL=false
HTTP_PROXY=
HTTPS_PROXY=
NO_PROXY=

# Valor UR
VALOR_UR_ENABLED=true
VALOR_UR_URL=https://www.bps.gub.uy/bps/valores.jsp?contentid=5478

# Sincronización Hora
SINCRONIZACION_HORA_ENABLED=true

# Valores SOA
VALORES_SOA_ENABLED=true
VALORES_SOA_URL_SOURCE=https://www.bcu.gub.uy/Servicios-Financieros-SSF/Paginas/ImpPromCostoDelSOA.aspx

# CFE
CFE_PROCESSING_MODE=sync
CFE_QUEUE_PROCESS=cfe-processing
CFE_QUEUE_CONFIRM=cfe-confirmation
```

---

## Testing

### Tests Unitarios (Services)
- `ValorUrServiceTest` - Descarga UR
- `CfeExtractor/*Test` - Extractores CFE
- `Tesoreria/*ServiceTest` - Servicios de Tesorería

### Tests de Integración (Feature)
- `CfeControllerTest` - API CFE
- `CfeProcessorServiceTest` - Procesamiento completo
- `GestionCfeTest` - Gestión CFE
- `MultasCobradasTest` - Multas
- `CajaChica/*Test` - Caja chica
- `CajaDiaria/*Test` - Caja diaria
- `LibroDiario/*Test` - Libro diario
- `Multa303Test` - Códigos multas 303

---

## Patrones de Código Utilizados

1. **Repository Pattern** - `CfePendienteRepository`
2. **Service Layer** - Servicios en `app/Services/`
3. **DTO Pattern** - `CfeExtraccionDto` y otros en `app/DTOs/`
4. **Livewire Components** - UI reactiva con lógica de negocio
5. **Form Requests** - Validación en `app/Http/Requests/`
6. **Traits** - `Auditable`, `LogsActivityTrait`, `ConvertirMayusculas`
7. **Events/Listeners** - `CfeProcesado` event
8. **Jobs/Queues** - `ConfirmarCfeJob` para procesamiento async

---

## Próximos Pasos Recomendados

1. **Refactorizar routes/web.php** - Separar en archivos por dominio (ya parcialmente hecho)
2. **Extraer lógica de Livewire a Services** - Reducir tamaño de componentes
3. **Aumentar cobertura de tests** - Especialmente operaciones financieras
4. **Mejorar parser CFE** - Considerar OCR o ML para mayor robustez
5. **Implementar observabilidad** - Logs estructurados, métricas, tracing
6. **Documentar API** - OpenAPI/Swagger para endpoints API