# Testing Troubleshooting - Solución de Problemas

## Índice

1. [Errores de Base de Datos](#errores-de-base-de-datos)
2. [Errores de Factories](#errores-de-factories)
3. [Errores de Assertions](#errores-de-assertions)
4. [Errores de Configuración](#errores-de-configuración)
5. [Problemas de Performance](#problemas-de-performance)
6. [Errores Comunes](#errores-comunes)

---

## Errores de Base de Datos

### Error: "Database 'tesoreria_oficinas' is not safe for testing"

```
RuntimeException: Database 'tesoreria_oficinas' is not safe for testing
```

**Causa**: El test detectó que se intenta usar la BD de producción.

**Solución**:

1. Verificar `.env.testing`:
```ini
DB_DATABASE=tesoreria_oficinas_test
```

2. Verificar `phpunit.xml`:
```xml
<env name="DB_DATABASE" value="tesoreria_oficinas_test"/>
```

3. Ejecutar:
```bash
php artisan testing:safety-check
php artisan testing:db-setup
```

### Error: "SQLSTATE[HY000] [1049] Unknown database"

```
SQLSTATE[HY000] [1049] Unknown database 'tesoreria_oficinas_test'
```

**Causa**: La base de datos de test no existe.

**Solución**:

```bash
# Crear BD manualmente
mysql -u root -p
CREATE DATABASE tesoreria_oficinas_test;
exit;

# O usar el comando
php artisan testing:db-setup
```

### Error: "SQLSTATE[42S02]: Base table or view not found"

```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'tes_caja_chica' doesn't exist
```

**Causa**: Migraciones no ejecutadas en BD de test.

**Solución**:

```bash
# Opción 1: Comando helper
php artisan testing:db-setup

# Opción 2: Manual
php artisan migrate:fresh --env=testing
php artisan db:seed --env=testing --class=TestingSeeder
```

### Error: "Integrity constraint violation"

```
SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row
```

**Causa**: Falta una relación requerida (foreign key).

**Solución**:

```php
// ❌ Incorrecto
$pago = Pago::factory()->create(); // Sin caja_chica_id

// ✅ Correcto
$caja = CajaChica::factory()->create();
$pago = Pago::factory()->paraCajaChica($caja)->create();
```

---

## Errores de Factories

### Error: "Class 'Database\Factories\...' not found"

```
Error: Class 'Database\Factories\Tesoreria\PagoFactory' not found
```

**Causa**: Factory no configurada en el modelo o no existe.

**Solución**:

1. Verificar que existe el archivo:
```
database/factories/Tesoreria/PagoFactory.php
```

2. Verificar que el modelo tiene:
```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pago extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\Tesoreria\PagoFactory::new();
    }
}
```

### Error: "Call to undefined method ... ::factory()"

```
Error: Call to undefined method App\Models\Tesoreria\Pago::factory()
```

**Causa**: El modelo no tiene el trait `HasFactory`.

**Solución**:

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MiModelo extends Model
{
    use HasFactory; // ← Agregar esto

    // ...
}
```

### Error: Estado de Factory No Existe

```
Error: Call to undefined method ... ::estadoInventado()
```

**Causa**: El estado no está definido en la factory.

**Solución**:

Verificar estados disponibles en `database/factories/Tesoreria/README.md` o agregar el estado:

```php
// En la Factory
public function estadoInventado(): static
{
    return $this->state(fn (array $attributes) => [
        'campo' => 'valor',
    ]);
}
```

---

## Errores de Assertions

### Error: "Failed asserting that 1234.56 matches expected 1234.5600000001"

```
Failed asserting that 1234.56 matches expected 1234.5600000001
```

**Causa**: Problemas de precisión con números decimales.

**Solución**:

```php
// ❌ Incorrecto
$this->assertEquals(1234.56, $monto);

// ✅ Correcto
$this->assertFloatEquals(1234.56, $monto, 0.01);
```

### Error: "Failed asserting that null is an instance of Carbon"

```
Failed asserting that null is an instance of Illuminate\Support\Carbon
```

**Causa**: Campo de fecha es null o no está casteado.

**Solución**:

1. Verificar casts en el modelo:
```php
protected $casts = [
    'fecha' => 'datetime',
    'vencimiento' => 'datetime',
];
```

2. Asegurar que se crea con fecha:
```php
$cfe = TesCfe::factory()->create([
    'fecha' => '2026-08-14', // ← Incluir fecha
]);
```

### Error: "Failed asserting that two arrays are equal"

**Causa**: Orden diferente o campos adicionales.

**Solución**:

```php
// ❌ Compara todo incluyendo orden
$this->assertEquals($esperado, $actual);

// ✅ Compara solo campos específicos
$this->assertEquals($esperado['nombre'], $actual['nombre']);
$this->assertCount(3, $actual);
```

---

## Errores de Configuración

### Error: "APP_KEY is missing"

```
RuntimeException: No application encryption key has been specified.
```

**Solución**:

```bash
# Generar key para testing
php artisan key:generate --env=testing
```

### Error: "Class 'Tests\TestCase' not found"

**Causa**: Namespace incorrecto o autoload no actualizado.

**Solución**:

```bash
composer dump-autoload
```

Verificar namespace del test:
```php
namespace Tests\Feature\Tesoreria\MiModulo; // ✅
// NO: namespace App\Tests\Feature\... // ❌
```

### Error: "Session store not set on request"

**Causa**: Middleware de sesión no configurado en test.

**Solución**:

```php
// Para tests con sesión
public function test_con_sesion(): void
{
    $this->withSession(['key' => 'value'])
         ->get('/ruta');
}
```

---

## Problemas de Performance

### Tests Muy Lentos

**Síntomas**: Suite completa tarda >5 minutos.

**Soluciones**:

1. **Usar RefreshDatabase en lugar de DatabaseMigrations**:
```php
use RefreshDatabase; // ✅ Más rápido
// use DatabaseMigrations; // ❌ Más lento
```

2. **Ejecutar en paralelo**:
```bash
php artisan test --parallel
```

3. **Limitar consultas innecesarias**:
```php
// ❌ Muchas queries
$caja = CajaChica::find($id);
$pagos = $caja->pagos; // +1 query
$pendientes = $caja->pendientes; // +1 query

// ✅ Una sola query
$caja = CajaChica::with(['pagos', 'pendientes'])->find($id);
```

4. **Usar transacciones para tests unitarios**:
```php
use DatabaseTransactions; // Para tests Unit
```

### Timeout en Tests

**Síntomas**: Test se queda colgado o da timeout.

**Solución**:

```xml
<!-- En phpunit.xml -->
<phpunit stopOnFailure="false"
         stopOnError="false"
         timeoutForSmallTests="10"
         timeoutForMediumTests="60"
         timeoutForLargeTests="300">
```

---

## Errores Comunes

### Error: "Call to a member function on null"

```
Error: Call to a member function getMedioDePago() on null
```

**Causa**: Helper no disponible o setUp no ejecutado.

**Solución**:

```php
// Verificar que el test extiende TesoreriaTestCase
class MiTest extends TesoreriaTestCase // ✅
{
    protected function setUp(): void
    {
        parent::setUp(); // ← Importante
        // ...
    }
}
```

### Error: "Undefined variable"

```
ErrorException: Undefined variable: caja
```

**Causa**: Variable no definida en el scope del test.

**Solución**:

```php
public function test_ejemplo(): void
{
    // ❌ $caja no definida
    $pago = Pago::factory()->paraCajaChica($caja)->create();

    // ✅ Definir primero
    $caja = CajaChica::factory()->create();
    $pago = Pago::factory()->paraCajaChica($caja)->create();
}
```

### Error: "Method ... does not exist"

```
Error: Method Tests\TesoreriaTestCase::metodoInventado does not exist
```

**Causa**: Método no existe en la clase base.

**Solución**:

Verificar métodos disponibles en `tests/TesoreriaTestCase.php` o agregar el método si es necesario.

### Error: "Too few arguments to function"

```
ArgumentCountError: Too few arguments to function create(), 0 passed
```

**Causa**: Factory requiere parámetros obligatorios.

**Solución**:

```php
// ❌ Falta parámetro obligatorio
$item = TesCfeItem::factory()->create();

// ✅ Proporcionar CFE
$cfe = TesCfe::factory()->create();
$item = TesCfeItem::factory()->paraCfe($cfe)->create();
```

---

## Debugging Tips

### Ver Queries Ejecutadas

```php
DB::enableQueryLog();

// ... ejecutar código ...

dd(DB::getQueryLog());
```

### Ver Contenido de Variable

```php
dump($variable); // Continúa ejecución
dd($variable);   // Dump and die
```

### Ver Estado de BD

```php
$this->assertDatabaseHas('tes_caja_chica', [
    'mes' => 'agosto',
]);

// Ver todos los registros
dump(CajaChica::all()->toArray());
```

### Ejecutar Test Individual con Verbose

```bash
php artisan test --filter=test_nombre_especifico --verbose
```

---

## Checklist de Debugging

Cuando un test falla, verificar en orden:

- [ ] ¿La BD de test está configurada? (`testing:safety-check`)
- [ ] ¿Las migraciones están ejecutadas? (`testing:db-setup`)
- [ ] ¿El test extiende de `TesoreriaTestCase`?
- [ ] ¿Se llama a `parent::setUp()`?
- [ ] ¿Las factories existen y están configuradas?
- [ ] ¿Los datos básicos existen? (`setupDatosBasicosTesoreria()`)
- [ ] ¿Las relaciones están correctamente establecidas?
- [ ] ¿Se usan assertions correctas para floats? (`assertFloatEquals`)
- [ ] ¿El test es independiente? (no depende de otros tests)
- [ ] ¿La limpieza de BD funciona? (`RefreshDatabase`)

---

## Recursos

- **Guía Principal**: `docs/GUIA_TESTING.md`
- **Factories**: `database/factories/Tesoreria/README.md`
- **Comandos Útiles**:
  - `php artisan testing:safety-check`
  - `php artisan testing:db-setup`
  - `php artisan test --filter=NombreTest`

---

**Última actualización**: 14/08/2026
