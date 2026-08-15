# Ejemplos Prácticos de Testing

## Índice

1. [Ejemplos Básicos](#ejemplos-básicos)
2. [Ejemplos con Factories](#ejemplos-con-factories)
3. [Ejemplos de Integración](#ejemplos-de-integración)
4. [Ejemplos End-to-End](#ejemplos-end-to-end)
5. [Ejemplos con Assertions](#ejemplos-con-assertions)
6. [Patrones Comunes](#patrones-comunes)

---

## Ejemplos Básicos

### Test Simple de Creación

```php
public function test_puede_crear_caja_chica(): void
{
    // Arrange: Preparar datos
    $datos = [
        'mes' => 'agosto',
        'anio' => 2026,
        'montoCajaChica' => 10000.00,
    ];

    // Act: Ejecutar acción
    $caja = CajaChica::create($datos);

    // Assert: Verificar resultado
    $this->assertNotNull($caja->id);
    $this->assertEquals('agosto', $caja->mes);
    $this->assertFloatEquals(10000.00, $caja->montoCajaChica);
}
```

### Test de Actualización

```php
public function test_puede_actualizar_monto_caja(): void
{
    // Crear caja
    $caja = CajaChica::factory()->create([
        'montoCajaChica' => 10000.00,
    ]);

    // Actualizar
    $caja->update(['montoCajaChica' => 15000.00]);

    // Verificar
    $this->assertFloatEquals(15000.00, $caja->fresh()->montoCajaChica);
}
```

### Test de Eliminación (Soft Delete)

```php
public function test_puede_eliminar_caja_soft_delete(): void
{
    $caja = CajaChica::factory()->create();
    $id = $caja->id;

    // Eliminar
    $caja->delete();

    // Verificar soft delete
    $this->assertSoftDeleted('tes_caja_chica', ['id' => $id]);
    $this->assertNotNull($caja->fresh()->deleted_at);
}
```

---

## Ejemplos con Factories

### Factory Básica

```php
public function test_factory_crea_datos_validos(): void
{
    $caja = CajaChica::factory()->create();

    $this->assertNotNull($caja->id);
    $this->assertNotNull($caja->mes);
    $this->assertNotNull($caja->anio);
    $this->assertGreaterThan(0, $caja->montoCajaChica);
}
```

### Factory con Estados

```php
public function test_factory_con_estados_encadenados(): void
{
    // Mes actual
    $caja = CajaChica::factory()->mesActual()->create();
    $this->assertEquals(date('n'), $caja->mes_numero);

    // Mes específico con monto
    $cajaJulio = CajaChica::factory()
        ->enMes('julio', 2026)
        ->conMonto(15000)
        ->create();

    $this->assertEquals('julio', $cajaJulio->mes);
    $this->assertEquals(2026, $cajaJulio->anio);
    $this->assertFloatEquals(15000.00, $cajaJulio->montoCajaChica);
}
```

### Factory con Relaciones

```php
public function test_factory_con_relaciones_automaticas(): void
{
    // Crear caja
    $caja = CajaChica::factory()->create();

    // Crear pagos para la caja
    $pagos = Pago::factory()
        ->count(5)
        ->paraCajaChica($caja)
        ->create();

    // Verificar relación
    $this->assertCount(5, $caja->pagos);
    $this->assertEquals($caja->id, $pagos->first()->caja_chica_id);
}
```

### Múltiples Registros

```php
public function test_crear_multiples_registros(): void
{
    // 10 cajas de diferentes meses
    $cajas = CajaChica::factory()->count(10)->create();

    $this->assertCount(10, $cajas);
    $this->assertCount(10, CajaChica::all());
}
```

---

## Ejemplos de Integración

### Caja Chica con Pagos y Rendiciones

```php
public function test_flujo_caja_chica_con_pagos(): void
{
    // 1. Crear caja
    $caja = CajaChica::factory()->create([
        'montoCajaChica' => 10000.00,
    ]);

    // 2. Crear pagos
    $pago1 = Pago::factory()->paraCajaChica($caja)->create([
        'montoPagos' => 2000.00,
        'rendidoPagos' => 0,
    ]);

    $pago2 = Pago::factory()->paraCajaChica($caja)->create([
        'montoPagos' => 1500.00,
        'rendidoPagos' => 0,
    ]);

    // 3. Rendir pagos
    $pago1->update(['rendidoPagos' => 1900.00]);
    $pago2->update(['rendidoPagos' => 1500.00]);

    // 4. Verificar totales
    $totalPagos = $caja->pagos->sum('montoPagos');
    $totalRendido = $caja->pagos->sum('rendidoPagos');

    $this->assertFloatEquals(3500.00, $totalPagos);
    $this->assertFloatEquals(3400.00, $totalRendido);

    // Reintegro pendiente
    $reintegro = $totalPagos - $totalRendido;
    $this->assertFloatEquals(100.00, $reintegro);
}
```

### Libro Diario con Saldos

```php
public function test_libro_diario_calcula_saldos_correctamente(): void
{
    // 1. Entrada inicial
    $asiento1 = LibroDiario::factory()->entrada()->create([
        'monto' => 1000.00,
    ]);

    $this->assertFloatEquals(1000.00, $asiento1->saldo);

    // 2. Más entradas
    $asiento2 = LibroDiario::factory()->entrada()->create([
        'monto' => 500.00,
    ]);

    $this->assertFloatEquals(1500.00, $asiento2->saldo);

    // 3. Salida
    $asiento3 = LibroDiario::factory()->salida()->create([
        'monto' => 300.00,
    ]);

    $this->assertFloatEquals(1200.00, $asiento3->saldo);
}
```

---

## Ejemplos End-to-End

### Flujo Completo: CFE → Multa → Libro Diario

```php
public function test_flujo_completo_cfe_multa_libro(): void
{
    // 1. Crear CFE de multa
    $cfe = TesCfe::factory()->pendiente()->create([
        'fecha' => '2026-08-14',
        'total_a_pagar' => 5000.00,
    ]);

    TesCfeItem::factory()->paraCfe($cfe)->create([
        'descripcion' => 'Multa Art. 103',
        'subtotal' => 5000.00,
    ]);

    // 2. Agregar medio de pago
    $medioEfectivo = $this->getMedioDePago('EF');
    TesCfeMedioPago::factory()
        ->paraCfe($cfe)
        ->conMedio($medioEfectivo)
        ->conMonto(5000.00)
        ->create();

    // 3. Confirmar CFE
    $cfe->update(['status' => 'confirmado']);

    // 4. Registrar en libro diario
    $asiento = $this->libroService->registrarAsiento([
        'fecha' => $cfe->fecha,
        'tipo_id' => $this->getTipo('Entrada')->id,
        'concepto_id' => $this->getConcepto('Recaudación 222')->id,
        'detalle_id' => $this->getDetalle('Multas')->id,
        'medio_id' => $medioEfectivo->id,
        'monto' => 5000.00,
    ]);

    // 5. Verificaciones finales
    $this->assertEquals('confirmado', $cfe->fresh()->status);
    $this->assertFloatEquals(5000.00, $cfe->total_a_pagar);
    $this->assertFloatEquals(5000.00, $asiento->monto);
    $this->assertEquals(1, $cfe->items()->count());
    $this->assertEquals(1, $cfe->mediosPago()->count());
}
```

### Flujo Mes Completo de Caja Chica

```php
public function test_flujo_mes_completo_caja_chica(): void
{
    // Semana 1: Constitución
    $caja = CajaChica::factory()->create([
        'mes' => 'agosto',
        'anio' => 2026,
        'montoCajaChica' => 15000.00,
    ]);

    $this->libroService->registrarAsiento([
        'fecha' => '2026-08-01',
        'tipo_id' => $this->getTipo('Entrada')->id,
        'concepto_id' => $this->getConcepto('Caja Chica')->id,
        'detalle_id' => $this->getDetalle('Fondo Fijo')->id,
        'medio_id' => $this->getMedioDePago('EF')->id,
        'monto' => 15000.00,
    ]);

    // Semana 2: Pagos
    $pago1 = Pago::factory()->paraCajaChica($caja)->create([
        'montoPagos' => 2500.00,
    ]);

    $pago2 = Pago::factory()->paraCajaChica($caja)->create([
        'montoPagos' => 1800.00,
    ]);

    // Semana 3: Rendiciones
    $pago1->update(['rendidoPagos' => 2400.00]);
    $pago2->update(['rendidoPagos' => 1800.00]);

    $this->libroService->registrarSalida([
        'fecha' => '2026-08-15',
        'tipo_id' => $this->getTipo('Salida')->id,
        'concepto_id' => $this->getConcepto('Caja Chica')->id,
        'detalle_id' => $this->getDetalle('Pagos')->id,
        'medio_id' => $this->getMedioDePago('EF')->id,
        'monto' => 4200.00,
    ]);

    // Semana 4: Recuperaciones
    $pago1->update(['recuperadoPagos' => 2400.00]);
    $pago2->update(['recuperadoPagos' => 1800.00]);

    $this->libroService->registrarAsiento([
        'fecha' => '2026-08-25',
        'tipo_id' => $this->getTipo('Entrada')->id,
        'concepto_id' => $this->getConcepto('Caja Chica')->id,
        'detalle_id' => $this->getDetalle('Pagos')->id,
        'medio_id' => $this->getMedioDePago('EF')->id,
        'monto' => 4200.00,
    ]);

    // Verificaciones finales
    $saldoFondo = $this->getSaldoSubcuenta($this->getDetalle('Fondo Fijo')->id);
    $saldoPagos = $this->getSaldoSubcuenta($this->getDetalle('Pagos')->id);

    $this->assertFloatEquals(15000.00, $saldoFondo);
    $this->assertFloatEquals(0.00, $saldoPagos);

    $totalPagos = $caja->pagos->sum('montoPagos');
    $totalRendido = $caja->pagos->sum('rendidoPagos');
    $totalRecuperado = $caja->pagos->sum('recuperadoPagos');

    $this->assertFloatEquals(4300.00, $totalPagos);
    $this->assertFloatEquals(4200.00, $totalRendido);
    $this->assertFloatEquals(4200.00, $totalRecuperado);
}
```

---

## Ejemplos con Assertions

### Assertions Básicas

```php
public function test_assertions_basicas(): void
{
    $caja = CajaChica::factory()->create();

    // Existe
    $this->assertNotNull($caja->id);

    // Valores
    $this->assertEquals('agosto', $caja->mes);
    $this->assertTrue($caja->exists);
    $this->assertFalse($caja->trashed());

    // Arrays
    $this->assertIsArray($caja->toArray());
    $this->assertArrayHasKey('montoCajaChica', $caja->toArray());

    // Counts
    $this->assertCount(0, $caja->pagos);
}
```

### Assertions de Base de Datos

```php
public function test_assertions_database(): void
{
    $caja = CajaChica::factory()->create([
        'mes' => 'agosto',
        'anio' => 2026,
    ]);

    // Has
    $this->assertDatabaseHas('tes_caja_chica', [
        'mes' => 'agosto',
        'anio' => 2026,
    ]);

    // Missing
    $this->assertDatabaseMissing('tes_caja_chica', [
        'mes' => 'diciembre',
    ]);

    // Soft Delete
    $caja->delete();
    $this->assertSoftDeleted('tes_caja_chica', ['id' => $caja->id]);
}
```

### Assertions Personalizadas de Tesorería

```php
public function test_assertions_personalizadas(): void
{
    $caja = CajaChica::factory()->create();
    $asiento = LibroDiario::factory()->create();

    // Assertions personalizadas
    $this->assertCajaChicaValida($caja);
    $this->assertAsientoValido($asiento);
    $this->assertSaldoCorrecto($asiento, 1000.00);

    // Float con precisión
    $this->assertFloatEquals(1234.56, $asiento->monto, 0.01);
}
```

---

## Patrones Comunes

### Patrón: Arrange-Act-Assert

```php
public function test_patron_arrange_act_assert(): void
{
    // Arrange: Preparar el escenario
    $caja = CajaChica::factory()->create(['montoCajaChica' => 10000.00]);
    $pago = Pago::factory()->paraCajaChica($caja)->create(['montoPagos' => 2000.00]);

    // Act: Ejecutar la acción
    $pago->update(['rendidoPagos' => 1900.00]);

    // Assert: Verificar el resultado
    $this->assertFloatEquals(1900.00, $pago->fresh()->rendidoPagos);
    $reintegro = $pago->montoPagos - $pago->rendidoPagos;
    $this->assertFloatEquals(100.00, $reintegro);
}
```

### Patrón: Given-When-Then

```php
public function test_patron_given_when_then(): void
{
    // Given: Dado que existe una caja chica con pagos
    $caja = CajaChica::factory()->create();
    Pago::factory()->count(3)->paraCajaChica($caja)->create();

    // When: Cuando elimino la caja
    $caja->delete();

    // Then: Entonces la caja está eliminada pero los pagos persisten
    $this->assertSoftDeleted('tes_caja_chica', ['id' => $caja->id]);
    $this->assertCount(3, Pago::all());
}
```

### Patrón: Setup Helper

```php
protected function crearCajaConPagos(int $cantidadPagos = 5): array
{
    $caja = CajaChica::factory()->create();
    $pagos = Pago::factory()
        ->count($cantidadPagos)
        ->paraCajaChica($caja)
        ->create();

    return compact('caja', 'pagos');
}

public function test_usando_helper(): void
{
    $data = $this->crearCajaConPagos(10);

    $this->assertCount(10, $data['caja']->pagos);
}
```

### Patrón: Data Provider

```php
/**
 * @dataProvider montosProvider
 */
public function test_con_data_provider(float $monto, float $esperado): void
{
    $caja = CajaChica::factory()->create(['montoCajaChica' => $monto]);

    $this->assertFloatEquals($esperado, $caja->montoCajaChica);
}

public function montosProvider(): array
{
    return [
        'monto normal' => [10000.00, 10000.00],
        'monto grande' => [50000.00, 50000.00],
        'monto pequeño' => [1000.00, 1000.00],
    ];
}
```

---

## Recursos

- **Guía Principal**: `docs/GUIA_TESTING.md`
- **Troubleshooting**: `docs/TESTING_TROUBLESHOOTING.md`
- **Factories**: `database/factories/Tesoreria/README.md`

---

**Última actualización**: 14/08/2026
