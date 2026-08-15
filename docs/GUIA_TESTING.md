# Guía de Testing - Sistema Tesorería

## Índice

1. [Introducción](#introducción)
2. [Configuración Inicial](#configuración-inicial)
3. [Estructura de Tests](#estructura-de-tests)
4. [Ejecutar Tests](#ejecutar-tests)
5. [Crear Nuevos Tests](#crear-nuevos-tests)
6. [Factories y Datos de Prueba](#factories-y-datos-de-prueba)
7. [Helpers y Assertions](#helpers-y-assertions)
8. [Mejores Prácticas](#mejores-prácticas)
9. [Solución de Problemas](#solución-de-problemas)

---

## Introducción

Esta guía documenta la infraestructura de testing del sistema de Tesorería, diseñada específicamente para:

- **Proteger la base de datos de producción** (triple verificación)
- **Facilitar la migración a Laravel 12 + Livewire 4**
- **Garantizar la calidad del código crítico financiero**

### Cobertura Actual

| Módulo | Cobertura | Tests |
|--------|-----------|-------|
| Caja Chica | 🟢 Alta (85%+) | 66 tests |
| Libro Diario | 🟢 Alta (85%+) | 57 tests |
| Multas | 🟢 Alta (85%+) | 83 tests |
| CFE | 🟢 Alta (85%+) | 85 tests |
| Integración E2E | 🟢 Alta | 22 tests |
| **TOTAL** | **~80%** | **313+ tests** |

---

## Configuración Inicial

### 1. Verificar Seguridad

**IMPORTANTE**: Antes de ejecutar tests, verifica que la configuración es segura:

```bash
php artisan testing:safety-check
```

Este comando verifica:
- ✅ Base de datos de test configurada correctamente
- ✅ No se usará la BD de producción
- ✅ Variables de entorno correctas

### 2. Preparar Base de Datos de Test

```bash
php artisan testing:db-setup
```

Este comando:
- Crea la base de datos `tesoreria_oficinas_test`
- Ejecuta migraciones
- Carga datos básicos (seeders)

### 3. Archivo de Configuración

Verificar que `.env.testing` tiene:

```ini
DB_DATABASE=tesoreria_oficinas_test
DB_USERNAME=root
DB_PASSWORD=

APP_ENV=testing
```

Y que `phpunit.xml` incluye:

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_DATABASE" value="tesoreria_oficinas_test"/>
</php>
```

---

## Estructura de Tests

```
tests/
├── Feature/
│   └── Tesoreria/
│       ├── CajaChica/          # Tests de Caja Chica
│       │   ├── CajaChicaCreacionTest.php
│       │   ├── PagosTest.php
│       │   ├── PendientesTest.php
│       │   └── CalculosTotalesTest.php
│       │
│       ├── LibroDiario/        # Tests de Libro Diario
│       │   ├── AsientosBasicosTest.php
│       │   ├── RedistribucionTest.php
│       │   ├── ConfirmacionTest.php
│       │   └── RecalculoSaldosTest.php
│       │
│       ├── Multas/             # Tests de Multas
│       │   ├── MultasBasicasTest.php
│       │   ├── MultasCobradasTest.php
│       │   └── MultasSearchTest.php
│       │
│       ├── CFE/                # Tests de CFE
│       │   ├── CfeBasicTest.php
│       │   ├── CfeExtractionTest.php
│       │   └── CfeWorkflowTest.php
│       │
│       └── Integration/        # Tests E2E
│           ├── CajaChicaFlowTest.php
│           ├── CfeToLibroDiarioFlowTest.php
│           └── MultasFlowTest.php
│
├── Unit/                       # Tests unitarios (mocks, sin BD)
├── TestCase.php               # Clase base con protección BD
├── TesoreriaTestCase.php      # Clase específica Tesorería
│
├── Traits/                    # Traits reutilizables
│   ├── DatabaseProtection.php
│   ├── InteractsWithTesoreria.php
│   ├── WithAuthentication.php
│   └── WithFakeHttpResponses.php
│
└── Helpers/                   # Utilidades
    └── DatabaseSafetyChecker.php
```

---

## Ejecutar Tests

### Todos los Tests

```bash
php artisan test
```

### Por Módulo

```bash
# Caja Chica
php artisan test --filter=CajaChica

# Libro Diario
php artisan test --filter=LibroDiario

# Multas
php artisan test --filter=Multas

# CFE
php artisan test --filter=Cfe

# Integración E2E
php artisan test --filter=Integration
```

### Por Archivo

```bash
php artisan test tests/Feature/Tesoreria/CajaChica/PagosTest.php
```

### Test Específico

```bash
php artisan test --filter=test_puede_rendir_pago_completo
```

### Con Cobertura de Código

```bash
php artisan test --coverage
```

### Modo Verboso (Debugging)

```bash
php artisan test --verbose
```

---

## Crear Nuevos Tests

### 1. Estructura Básica

```php
<?php

namespace Tests\Feature\Tesoreria\MiModulo;

use Tests\TesoreriaTestCase;

class MiModuloTest extends TesoreriaTestCase
{
    public function test_descripcion_clara_del_caso(): void
    {
        // 1. Arrange (Preparar)
        $datos = /* ... */;

        // 2. Act (Actuar)
        $resultado = /* ejecutar acción */;

        // 3. Assert (Verificar)
        $this->assertEquals($esperado, $resultado);
    }
}
```

### 2. Usar Factories

```php
use App\Models\Tesoreria\CajaChica;
use App\Models\Tesoreria\Pago;

public function test_con_factories(): void
{
    // Crear caja chica del mes actual
    $caja = CajaChica::factory()->mesActual()->create();

    // Crear pago con monto específico
    $pago = Pago::factory()
        ->paraCajaChica($caja)
        ->rendido()
        ->create(['montoPagos' => 1500.00]);

    $this->assertFloatEquals(1500.00, $pago->montoPagos);
}
```

### 3. Usar Helpers de TesoreriaTestCase

```php
public function test_con_helpers(): void
{
    // Obtener datos básicos
    $tipo = $this->getTipo('Entrada');
    $concepto = $this->getConcepto('Caja Chica');
    $detalle = $this->getDetalle('Fondo Fijo');
    $medio = $this->getMedioDePago('EF');

    // Crear asiento
    $asiento = /* ... */;

    // Assertions personalizadas
    $this->assertAsientoValido($asiento);
    $this->assertFloatEquals(1000.00, $asiento->monto);
}
```

---

## Factories y Datos de Prueba

### Factories Disponibles

Ver documentación completa en: `database/factories/Tesoreria/README.md`

#### Ejemplos Rápidos

```php
// Caja Chica
$caja = CajaChica::factory()->mesActual()->create();
$caja = CajaChica::factory()->enMes('julio', 2026)->create();

// Pagos
$pago = Pago::factory()->paraCajaChica($caja)->create();
$pago = Pago::factory()->rendido()->recuperado()->create();

// Libro Diario
$asiento = LibroDiario::factory()->entrada()->create();
$asiento = LibroDiario::factory()->confirmado()->cajaChica()->create();

// Multas
$multa = Multa::factory()->enPesos()->create();
$multa = Multa::factory()->enUR()->articulo184()->create();

// CFE
$cfe = TesCfe::factory()->eFactura()->confirmado()->create();
$cfe = TesCfe::factory()->conPdf()->conMonto(5000)->create();
```

### Crear Múltiples Registros

```php
// 5 cajas chicas
CajaChica::factory()->count(5)->create();

// 10 pagos para una caja
Pago::factory()->count(10)->paraCajaChica($caja)->create();
```

---

## Helpers y Assertions

### Helpers de Datos Básicos

```php
// Obtener tipo de asiento
$tipo = $this->getTipo('Entrada');
$tipo = $this->getTipo('Salida');

// Obtener concepto
$concepto = $this->getConcepto('Caja Chica');
$concepto = $this->getConcepto('Recaudación 222');

// Obtener detalle
$detalle = $this->getDetalle('Fondo Fijo');
$detalle = $this->getDetalle('Pendiente');
$detalle = $this->getDetalle('Pagos');

// Obtener medio de pago
$medio = $this->getMedioDePago('EF'); // Efectivo
$medio = $this->getMedioDePago('CH'); // Cheque
$medio = $this->getMedioDePago('TD'); // Tarjeta Débito
```

### Assertions Personalizadas

```php
// Verificar asiento válido
$this->assertAsientoValido($asiento);

// Verificar caja chica válida
$this->assertCajaChicaValida($caja);

// Verificar saldo
$this->assertSaldoCorrecto($asiento, $saldoEsperado);

// Comparar floats con precisión
$this->assertFloatEquals(1234.56, $monto, 0.01);
```

### Obtener Saldos

```php
// Saldo de una subcuenta
$saldo = $this->getSaldoSubcuenta($detalleId);

// Saldo de flujo completo
$saldos = $this->getSaldosActualesPorFlujo();
```

---

## Mejores Prácticas

### ✅ DO: Hacer

1. **Extender de TesoreriaTestCase** para tests de Tesorería
2. **Usar factories** en lugar de crear datos manualmente
3. **Nombres descriptivos** de tests: `test_puede_rendir_pago_con_reintegro()`
4. **Verificar múltiples aspectos** en un mismo test si están relacionados
5. **Usar assertions personalizadas** cuando estén disponibles
6. **Comentar flujos complejos** en tests E2E
7. **Verificar estados antes y después** de operaciones críticas

```php
public function test_rendicion_actualiza_saldos_correctamente(): void
{
    // Saldo inicial
    $saldoInicial = $this->getSaldoSubcuenta($detalleId);

    // Realizar operación
    $pago->rendir(1500.00);

    // Verificar cambio
    $saldoFinal = $this->getSaldoSubcuenta($detalleId);
    $this->assertFloatEquals($saldoInicial + 1500, $saldoFinal);
}
```

### ❌ DON'T: Evitar

1. **NO usar datos reales de producción** en tests
2. **NO hardcodear IDs** de base de datos
3. **NO hacer tests interdependientes** (cada test debe ser independiente)
4. **NO ignorar factories** disponibles
5. **NO crear TestCase genérico** para Tesorería (usar TesoreriaTestCase)
6. **NO mezclar múltiples funcionalidades** no relacionadas en un test
7. **NO olvidar limpiar datos** (RefreshDatabase lo hace automáticamente)

---

## Solución de Problemas

### Error: "Database 'tesoreria_oficinas' is not safe"

**Causa**: El test está intentando usar la BD de producción.

**Solución**:
```bash
# Verificar configuración
php artisan testing:safety-check

# Configurar BD de test
php artisan testing:db-setup
```

### Error: "Class 'Database\\Factories\\...' not found"

**Causa**: Factory no configurada en el modelo.

**Solución**: Agregar al modelo:
```php
use HasFactory;

protected static function newFactory()
{
    return \Database\Factories\Tesoreria\MiModeloFactory::new();
}
```

### Tests Lentos

**Optimización**:
1. Usar `RefreshDatabase` en lugar de `DatabaseMigrations`
2. Minimizar llamadas a BD en cada test
3. Agrupar tests relacionados
4. Usar `--parallel` si es posible

```bash
php artisan test --parallel
```

### Errores de Precisión Decimal

**Usar**:
```php
$this->assertFloatEquals(1234.56, $monto, 0.01);
```

**En lugar de**:
```php
$this->assertEquals(1234.56, $monto); // ❌ Puede fallar
```

### Datos Básicos No Existen

**Verificar** que se ejecutó:
```bash
php artisan testing:db-setup
```

**O ejecutar manualmente** en el test:
```php
protected function setUp(): void
{
    parent::setUp();
    $this->setupDatosBasicosTesoreria();
}
```

---

## Comandos Útiles

```bash
# Verificar seguridad
php artisan testing:safety-check

# Setup BD test
php artisan testing:db-setup

# Ejecutar todos los tests
php artisan test

# Tests con cobertura
php artisan test --coverage --min=80

# Tests específicos
php artisan test --filter=Multas

# Tests en paralelo (más rápido)
php artisan test --parallel

# Ver output detallado
php artisan test --verbose

# Rerun failed tests
php artisan test --retry
```

---

## Recursos Adicionales

- **Factories**: Ver `database/factories/Tesoreria/README.md`
- **Progreso**: Ver `docs/PROGRESO_MEJORA_TESTS.md`
- **Migración Laravel**: Ver `docs/MIGRACION_LARAVEL_12_LIVEWIRE_4.md`
- **Ejemplos**: Ver tests existentes en `tests/Feature/Tesoreria/`

---

## Contacto y Soporte

Si encuentras problemas o tienes dudas sobre testing:

1. Revisa esta documentación
2. Revisa tests similares existentes
3. Ejecuta `php artisan testing:safety-check`
4. Consulta con el equipo de desarrollo

---

**Última actualización**: 14/08/2026  
**Versión**: 1.0  
**Autor**: Sistema de Testing Tesorería
