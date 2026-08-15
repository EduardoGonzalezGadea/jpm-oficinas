# Comando: Crear Asientos Históricos de Caja Chica

## Descripción

Este comando artisan permite crear los asientos del libro diario para los registros históricos de caja chica que fueron creados antes de que se implementara el sistema de sincronización automática con el libro diario.

El comando respeta las fechas y montos originales de cada operación, permitiendo reconstruir el historial contable completo.

## Sintaxis

```bash
php artisan caja-chica:crear-asientos-historicos [opciones]
```

## Opciones

| Opción | Descripción |
|--------|-------------|
| `--caja-chica-id=ID` | Procesar únicamente la caja chica con el ID especificado |
| `--mes=MES` | Filtrar por mes (ej: enero, febrero, marzo) |
| `--anio=AÑO` | Filtrar por año (ej: 2026) |
| `--dry-run` | Muestra qué se crearía sin aplicar los cambios (recomendado para prueba) |
| `--skip-confirmacion` | Omite la confirmación interactiva antes de ejecutar |

## Funcionamiento

El comando procesa en orden cronológico los siguientes registros de cada caja chica:

### 1. Fondo Fijo (Constitución Inicial)
- Crea el asiento de entrada por el monto del fondo fijo de la caja chica
- Fecha: utiliza la fecha de creación del registro de caja chica
- Tipo: Entrada con detalle "Fondo Fijo"

### 2. Pendientes
- Crea redistribuciones de Fondo Fijo → Pendiente por cada pendiente registrado
- Fecha: utiliza la fecha del pendiente (`fechaPendientes`)
- Monto: utiliza el monto original del pendiente (`montoPendientes`)
- Identidad: nombre de la dependencia

### 3. Pagos Directos
Para cada pago directo, crea hasta 3 tipos de asientos:

a) **Redistribución:** Fondo Fijo → Pagos
   - Fecha: fecha de egreso del pago (`fechaEgresoPagos`)
   - Monto: monto del pago (`montoPagos`)

b) **Rendición** (si existe):
   - Fecha: fecha de rendición (`fechaRendicionPagos`)
   - Monto: monto rendido (`rendidoPagos`)

c) **Recuperación** (si existe):
   - Fecha: fecha de ingreso (`fechaIngresoPagos`)
   - Monto: monto recuperado (`recuperadoPagos`)

### 4. Movimientos de Pendientes
Para cada movimiento, crea los asientos correspondientes:

a) **Rendición/Reintegro** (si existe):
   - Fecha: fecha del movimiento (`fechaMovimientos`)
   - Monto: monto rendido o reintegrado
   - Incluye redistribución de Pendiente → Fondo Fijo

b) **Recuperación** (si existe):
   - Fecha: fecha del movimiento (`fechaMovimientos`)
   - Monto: monto recuperado

## Ejemplos de Uso

### 1. Simulación (recomendado primero)
```bash
php artisan caja-chica:crear-asientos-historicos --dry-run
```

### 2. Procesar todas las cajas chicas
```bash
php artisan caja-chica:crear-asientos-historicos
```

### 3. Procesar una caja chica específica
```bash
php artisan caja-chica:crear-asientos-historicos --caja-chica-id=1
```

### 4. Procesar por mes y año
```bash
php artisan caja-chica:crear-asientos-historicos --mes=enero --anio=2026
```

### 5. Ejecución sin confirmación (scripts automatizados)
```bash
php artisan caja-chica:crear-asientos-historicos --skip-confirmacion
```

### 6. Combinación de opciones
```bash
php artisan caja-chica:crear-asientos-historicos --mes=febrero --anio=2026 --dry-run
```

## Salida del Comando

El comando muestra información detallada durante la ejecución:

```
=== Procesando Caja Chica: Enero 2026 (ID: 1) ===

  [Fondo Fijo] Creando asiento de constitución: $ 50.000,00 (fecha: 01/01/2026)
    ✔ Asiento de fondo fijo creado

  [Pendientes] Procesando 12 pendiente(s)...
    Pendiente #1: Dirección de Administración - $ 5.000,00 (fecha: 05/01/2026)
      ✔ Redistribución fondo → pendiente creada
    ...

  [Pagos Directos] Procesando 3 pago(s)...
    Pago #1: Proveedor XYZ - $ 2.500,00 (fecha: 10/01/2026)
      ✔ Redistribución fondo → pago creada
      ✔ Rendición registrada: $ 2.300,00
      ✔ Recuperación registrada: $ 200,00
    ...

  [Movimientos] Procesando 18 movimiento(s)...
    Movimiento #1 (Pendiente #1) - fecha: 12/01/2026
      Rendido: $ 4.800,00
      ✔ Rendición de pendiente registrada
    ...

═══════════════════════════════════════════════════════════
                       RESUMEN                             
═══════════════════════════════════════════════════════════
Fondos fijos creados      : 1
Pendientes procesados     : 12
Pagos procesados          : 3
Movimientos procesados    : 18
Registros omitidos        : 5
═══════════════════════════════════════════════════════════

Se crearon 34 asiento(s) en el libro diario.
Recomendación: ejecute "php artisan libro-diario:recalcular-saldos" para actualizar los saldos.
```

## Validaciones y Seguridad

### El comando NO procesa:
- Asientos que ya existen (detecta por `cch_origen_type` y `cch_origen_id`)
- Movimientos sin operaciones (rendido, reintegrado y recuperado en 0)
- Fondos fijos con monto 0

### Protecciones:
- Confirmación interactiva antes de ejecutar (puede omitirse con `--skip-confirmacion`)
- Modo `--dry-run` para simular sin aplicar cambios
- Transacciones de base de datos para garantizar integridad
- Manejo de errores con mensajes descriptivos

## Después de Ejecutar

Se recomienda ejecutar el comando de recalcular saldos para asegurar que todos los saldos del libro diario estén correctamente actualizados:

```bash
php artisan libro-diario:recalcular-saldos
```

Opcionalmente, puede usar el modo `--dry-run` primero para ver qué cambiaría:

```bash
php artisan libro-diario:recalcular-saldos --dry-run
```

## Casos de Uso

### Escenario 1: Sistema Nuevo Implementado a Mitad de Mes
Si el libro diario se implementó el día 15 de enero, pero hay registros de caja chica desde el día 1:

```bash
# 1. Simular primero
php artisan caja-chica:crear-asientos-historicos --mes=enero --anio=2026 --dry-run

# 2. Si todo está correcto, aplicar
php artisan caja-chica:crear-asientos-historicos --mes=enero --anio=2026

# 3. Recalcular saldos
php artisan libro-diario:recalcular-saldos
```

### Escenario 2: Migración de Sistema Legacy
Si se está migrando desde un sistema anterior y hay varios meses de datos históricos:

```bash
# Procesar todas las cajas chicas existentes
php artisan caja-chica:crear-asientos-historicos --dry-run
php artisan caja-chica:crear-asientos-historicos
php artisan libro-diario:recalcular-saldos
```

### Escenario 3: Corrección de Datos Específicos
Si se detectó que falta sincronizar una caja chica específica:

```bash
php artisan caja-chica:crear-asientos-historicos --caja-chica-id=5 --skip-confirmacion
php artisan libro-diario:recalcular-saldos
```

## Notas Técnicas

- Los asientos se marcan con `cch_origen_type` y `cch_origen_id` para identificar su origen
- Las redistribuciones se agrupan con `grupo_redistribucion_id`
- Los asientos se confirman automáticamente (`confirmado = true`)
- Las fechas de confirmación se establecen al momento de la creación del asiento
- El comando preserva las auditorías (`created_by`, `updated_by`)

## Solución de Problemas

### Error: "El monto a redistribuir supera el saldo disponible del flujo de origen"
**Causa:** Orden incorrecto de procesamiento o falta de asiento de fondo fijo.

**Solución:** Asegúrese de procesar las cajas chicas en orden cronológico y que exista el asiento de fondo fijo antes de procesar redistribuciones.

### Error: "No se encontraron cajas chicas para procesar"
**Causa:** Los filtros especificados no coinciden con ninguna caja chica.

**Solución:** Verifique que el mes/año o ID sean correctos:
```bash
# Listar cajas chicas disponibles
php artisan tinker
>>> \App\Models\Tesoreria\CajaChica::select('idCajaChica', 'mes', 'anio')->get();
```

### Asientos duplicados
**Causa:** El comando se ejecutó más de una vez sin validación.

**Solución:** El comando detecta automáticamente asientos existentes y los omite. No debería haber duplicados.

## Ver También

- [CajaChicaRepararAsientosCommand](./caja-chica-reparar-asientos.md) - Para reparar asientos faltantes puntuales
- [RecalcularSaldosLibroDiarioCommand](./libro-diario-recalcular-saldos.md) - Para recalcular saldos después de modificaciones
- [Arquitectura del Libro Diario](../arquitectura/libro-diario.md)
- [Sistema de Caja Chica](../arquitectura/caja-chica.md)
