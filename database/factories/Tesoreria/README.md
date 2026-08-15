# Factories de Tesorería

Este directorio contiene las factories para los modelos de Tesorería, facilitando la creación de datos de prueba en tests.

## Factories Disponibles

### Modelos Básicos

#### LbTipo (Tipos de Libro Diario)
```php
use App\Models\Tesoreria\LbTipo;

// Crear tipo por defecto (Entrada)
$tipo = LbTipo::factory()->create();

// Tipos específicos
$entrada = LbTipo::factory()->entrada()->create();
$salida = LbTipo::factory()->salida()->create();
$redistribucion = LbTipo::factory()->redistribucion()->create();
```

#### LbConcepto (Conceptos)
```php
use App\Models\Tesoreria\LbConcepto;

// Conceptos predefinidos
$cajaChica = LbConcepto::factory()->cajaChica()->create();
$recaudacion222 = LbConcepto::factory()->recaudacion222()->create();
$recaudacionDiaria = LbConcepto::factory()->recaudacionDiaria()->create();

// Concepto personalizado
$concepto = LbConcepto::factory()->conNombre('Mi Concepto')->create();
```

#### LbDetalle (Detalles de Caja Chica)
```php
use App\Models\Tesoreria\LbDetalle;

// Detalles de caja chica
$fondoFijo = LbDetalle::factory()->fondoFijo()->create();
$pendiente = LbDetalle::factory()->pendiente()->create();
$pagos = LbDetalle::factory()->pagos()->create();

// Detalle personalizado
$detalle = LbDetalle::factory()
    ->paraConcepto($concepto)
    ->conNombre('Mi Detalle')
    ->create();
```

#### MedioDePago
```php
use App\Models\Tesoreria\MedioDePago;

// Medios de pago comunes
$efectivo = MedioDePago::factory()->efectivo()->create();
$cheque = MedioDePago::factory()->cheque()->create();
$tarjetaDebito = MedioDePago::factory()->tarjetaDebito()->create();
$tarjetaCredito = MedioDePago::factory()->tarjetaCredito()->create();
$transferencia = MedioDePago::factory()->transferencia()->create();

// Medio inactivo
$medio = MedioDePago::factory()->inactivo()->create();

// Sin libro diario
$medio = MedioDePago::factory()->sinLibroDiario()->create();
```

### Caja Chica

#### CajaChica
```php
use App\Models\Tesoreria\CajaChica;

// Caja chica por defecto
$caja = CajaChica::factory()->create();

// Mes actual
$caja = CajaChica::factory()->mesActual()->create();

// Mes/año específico
$caja = CajaChica::factory()->enMes('agosto', 2026)->create();

// Con monto específico
$caja = CajaChica::factory()->conMonto(5000)->create();
```

#### Pago
```php
use App\Models\Tesoreria\Pago;

// Pago básico
$pago = Pago::factory()->create();

// Pago vinculado a caja chica
$pago = Pago::factory()->paraCajaChica($cajaChica)->create();

// Pago con acreedor específico
$pago = Pago::factory()->paraAcreedor($acreedor)->create();

// Pago con monto específico
$pago = Pago::factory()->conMonto(1500.50)->create();

// Pago rendido
$pago = Pago::factory()
    ->rendido(1200.00, '2026-08-15')
    ->create();

// Pago recuperado (BSE)
$pago = Pago::factory()
    ->rendido()
    ->recuperado(400.00, true) // true = con datos BSE
    ->create();

// Pago completo (rendido y recuperado)
$pago = Pago::factory()->completo(true)->create();

// Pago en fecha específica
$pago = Pago::factory()->enFecha('2026-08-10')->create();
```

#### Pendiente
```php
use App\Models\Tesoreria\Pendiente;

// Pendiente básico
$pendiente = Pendiente::factory()->create();

// Pendiente vinculado
$pendiente = Pendiente::factory()
    ->paraCajaChica($cajaChica)
    ->paraDependencia($dependencia)
    ->create();

// Pendiente con monto y fecha
$pendiente = Pendiente::factory()
    ->conMonto(800.00)
    ->enFecha('2026-08-05')
    ->create();
```

#### Movimiento
```php
use App\Models\Tesoreria\Movimiento;

// Movimiento básico
$movimiento = Movimiento::factory()->create();

// Movimiento de pendiente específico
$movimiento = Movimiento::factory()
    ->paraPendiente($pendiente)
    ->create();

// Movimiento rendido
$movimiento = Movimiento::factory()->rendido(500.00)->create();

// Movimiento reintegrado
$movimiento = Movimiento::factory()->reintegrado(200.00)->create();

// Movimiento recuperado
$movimiento = Movimiento::factory()->recuperado(300.00)->create();

// Con fecha específica
$movimiento = Movimiento::factory()->enFecha('2026-08-12')->create();
```

#### Acreedor
```php
use App\Models\Tesoreria\Acreedor;

// Acreedor genérico
$acreedor = Acreedor::factory()->create();

// BSE
$bse = Acreedor::factory()->bse()->create();

// Con nombre específico
$acreedor = Acreedor::factory()->conNombre('Mi Proveedor SA')->create();
```

#### Dependencia
```php
use App\Models\Tesoreria\Dependencia;

// Dependencia genérica
$dependencia = Dependencia::factory()->create();

// Con nombre específico
$dependencia = Dependencia::factory()
    ->conNombre('Departamento de Sistemas')
    ->create();
```

### Libro Diario

#### LibroDiario
```php
use App\Models\Tesoreria\LibroDiario;

// Asiento básico
$asiento = LibroDiario::factory()->create();

// Asiento de entrada
$asiento = LibroDiario::factory()->entrada()->create();

// Asiento de salida
$asiento = LibroDiario::factory()->salida()->create();

// Asiento de redistribución
$grupoId = 1;
$asiento = LibroDiario::factory()->redistribucion($grupoId)->create();

// Asiento confirmado
$asiento = LibroDiario::factory()
    ->confirmado('2026-08-14')
    ->create();

// Asiento con monto específico
$asiento = LibroDiario::factory()->conMonto(1500.00)->create();

// Asiento con saldo específico
$asiento = LibroDiario::factory()->conSaldo(3000.00)->create();

// Asiento de caja chica
$asiento = LibroDiario::factory()->cajaChica()->create();

// Asiento con origen (pago/pendiente)
$asiento = LibroDiario::factory()
    ->conOrigen('pago', $pago->idPagos)
    ->create();

// Asiento en fecha específica
$asiento = LibroDiario::factory()->enFecha('2026-08-01')->create();

// Contra-asiento
$asiento = LibroDiario::factory()->contraAsiento()->create();

// Combinando estados
$asiento = LibroDiario::factory()
    ->entrada()
    ->cajaChica()
    ->confirmado()
    ->conMonto(5000.00)
    ->enFecha('2026-08-01')
    ->create();
```

### Multas

#### Multa
```php
use App\Models\Tesoreria\Multa;

// Multa básica
$multa = Multa::factory()->create();

// Multa en pesos
$multa = Multa::factory()->enPesos()->create();

// Multa en UR
$multa = Multa::factory()->enUR()->create();

// Multa en UI
$multa = Multa::factory()->enUI()->create();

// Multa con artículo específico
$multa = Multa::factory()->articulo(184, '2A')->create();

// Multa Art. 184 (SOA)
$multa = Multa::factory()->articulo184()->create();

// Multa visible/oculta
$multa = Multa::factory()->visible()->create();
$multa = Multa::factory()->oculta()->create();

// Multa con monto específico
$multa = Multa::factory()->conMonto(2500.00, 'UYU')->create();
```

#### TesMultasCobradas
```php
use App\Models\Tesoreria\TesMultasCobradas;

// Multa cobrada básica
$multaCobrada = TesMultasCobradas::factory()->create();

// Multa de contado
$multaCobrada = TesMultasCobradas::factory()->contado()->create();

// Multa a crédito
$multaCobrada = TesMultasCobradas::factory()->credito()->create();

// Multa en efectivo
$multaCobrada = TesMultasCobradas::factory()->enEfectivo()->create();

// Multa con datos específicos
$multaCobrada = TesMultasCobradas::factory()
    ->conRecibo('REC-12345678')
    ->conCedula('12345678-9')
    ->conMonto(3500.00)
    ->enFecha('2026-08-14')
    ->create();

// Con medio de pago específico
$multaCobrada = TesMultasCobradas::factory()
    ->conMedioDePago($medio)
    ->create();
```

#### TesMultasItems
```php
use App\Models\Tesoreria\TesMultasItems;

// Item básico
$item = TesMultasItems::factory()->create();

// Item vinculado
$item = TesMultasItems::factory()
    ->paraMultaCobrada($multaCobrada)
    ->paraMulta($multa)
    ->conMonto(1500.00)
    ->create();
```

## Uso en Tests

### Ejemplo Completo: Flujo de Caja Chica

```php
use Tests\TesoreriaTestCase;

class CajaChicaFlowTest extends TesoreriaTestCase
{
    public function test_flujo_completo_caja_chica(): void
    {
        // 1. Crear caja chica del mes actual
        $caja = CajaChica::factory()
            ->mesActual()
            ->conMonto(5000)
            ->create();

        // 2. Crear acreedor
        $acreedor = Acreedor::factory()
            ->conNombre('Proveedor Test')
            ->create();

        // 3. Crear pago
        $pago = Pago::factory()
            ->paraCajaChica($caja)
            ->paraAcreedor($acreedor)
            ->conMonto(1000)
            ->enFecha(now()->format('Y-m-d'))
            ->create();

        // 4. Rendir el pago
        $pago->update([
            'rendidoPagos' => 850,
            'reintegradoPagos' => 150,
            'fechaRendicionPagos' => now()->addDays(10),
        ]);

        // 5. Verificar estado
        $this->assertEquals(850, $pago->fresh()->rendidoPagos);
        $this->assertEquals(150, $pago->fresh()->reintegradoPagos);
    }
}
```

### Ejemplo: Crear Múltiples Registros

```php
// Crear 10 pagos para una caja chica
$pagos = Pago::factory()
    ->count(10)
    ->paraCajaChica($cajaChica)
    ->create();

// Crear 5 pagos rendidos
$pagosRendidos = Pago::factory()
    ->count(5)
    ->paraCajaChica($cajaChica)
    ->rendido()
    ->create();

// Crear multas de diferentes tipos
$multasPesos = Multa::factory()->count(5)->enPesos()->create();
$multasUR = Multa::factory()->count(3)->enUR()->create();
$multasUI = Multa::factory()->count(2)->enUI()->create();
```

## Consejos

1. **Usa estados encadenados** para crear datos específicos:
   ```php
   $pago = Pago::factory()
       ->paraCajaChica($caja)
       ->paraAcreedor($bse)
       ->conMonto(2000)
       ->rendido(1800)
       ->recuperado(400, true)
       ->create();
   ```

2. **Crea datos relacionados automáticamente**:
   ```php
   // Esto crea automáticamente CajaChica y Acreedor
   $pago = Pago::factory()->create();
   ```

3. **Usa `make()` en lugar de `create()` para no persistir**:
   ```php
   $pago = Pago::factory()->make(); // No se guarda en BD
   ```

4. **Combina con helpers de `InteractsWithTesoreria`**:
   ```php
   // En un test que usa TesoreriaTestCase
   $this->setupDatosBasicosTesoreria(); // Crea tipos, conceptos, detalles, medios
   $caja = CajaChica::factory()->create(); // Usa los datos básicos
   ```

## Seguridad

**IMPORTANTE**: Estas factories SOLO deben usarse con la base de datos de testing (`tesoreria_oficinas_test`).

Las protecciones implementadas en `TestCase` y `DatabaseProtection` aseguran que:
- Nunca se ejecuten en base de datos de producción
- Se verifique el ambiente antes de cada test
- Se lance una excepción si se detecta configuración peligrosa

## Contribuir

Al crear nuevas factories:

1. Extiende de `Factory`
2. Define el modelo con `protected $model`
3. Implementa `definition()` con valores por defecto sensatos
4. Crea estados útiles con métodos públicos
5. Documenta los estados en este README
6. Usa Faker para valores aleatorios pero realistas
