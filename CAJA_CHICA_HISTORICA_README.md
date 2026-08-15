# Creación de Asientos Históricos de Caja Chica

## Objetivo

Este comando permite crear los registros del libro diario para movimientos históricos de caja chica que ocurrieron antes de que se implementara el sistema de sincronización automática.

## Uso Rápido

### 1. Simular primero (recomendado)
```bash
php artisan caja-chica:crear-asientos-historicos --dry-run
```

Este comando mostrará qué asientos se crearían sin aplicar ningún cambio real.

### 2. Aplicar los cambios
```bash
php artisan caja-chica:crear-asientos-historicos
```

El comando pedirá confirmación antes de ejecutarse.

### 3. Recalcular saldos
```bash
php artisan libro-diario:recalcular-saldos
```

Este paso asegura que todos los saldos del libro diario estén correctamente actualizados.

## ¿Qué hace el comando?

El comando procesa **en orden cronológico** todos los registros de caja chica y crea los asientos correspondientes en el libro diario:

1. **Fondo Fijo:** Asiento de constitución inicial del fondo
2. **Pendientes:** Redistribuciones de Fondo Fijo → Pendiente
3. **Pagos Directos:** Redistribuciones, rendiciones y recuperaciones
4. **Movimientos:** Rendiciones y recuperaciones de pendientes

## Opciones Avanzadas

### Procesar una caja chica específica
```bash
php artisan caja-chica:crear-asientos-historicos --caja-chica-id=1
```

### Procesar por mes y año
```bash
php artisan caja-chica:crear-asientos-historicos --mes=enero --anio=2026
```

### Sin confirmación (para scripts)
```bash
php artisan caja-chica:crear-asientos-historicos --skip-confirmacion
```

## Seguridad

- ✅ El comando **NO crea asientos duplicados** (detecta automáticamente los existentes)
- ✅ Usa transacciones de base de datos para garantizar integridad
- ✅ Modo `--dry-run` para simular sin aplicar cambios
- ✅ Confirmación interactiva antes de ejecutar
- ✅ Respeta fechas y montos originales

## Flujo de Trabajo Recomendado

```bash
# Paso 1: Ver qué se haría (simulación)
php artisan caja-chica:crear-asientos-historicos --dry-run

# Paso 2: Si todo está correcto, aplicar
php artisan caja-chica:crear-asientos-historicos

# Paso 3: Recalcular saldos
php artisan libro-diario:recalcular-saldos

# Opcional: Verificar saldos sin aplicar
php artisan libro-diario:recalcular-saldos --dry-run
```

## Ejemplo de Salida

```
=== Procesando Caja Chica: Enero 2026 (ID: 1) ===

  [Fondo Fijo] Creando asiento de constitución: $ 50.000,00 (fecha: 01/01/2026)
    ✔ Asiento de fondo fijo creado

  [Pendientes] Procesando 12 pendiente(s)...
    Pendiente #1: Dirección de Administración - $ 5.000,00 (fecha: 05/01/2026)
      ✔ Redistribución fondo → pendiente creada

  [Pagos Directos] Procesando 3 pago(s)...
    Pago #1: Proveedor XYZ - $ 2.500,00 (fecha: 10/01/2026)
      ✔ Redistribución fondo → pago creada
      ✔ Rendición registrada: $ 2.300,00
      ✔ Recuperación registrada: $ 200,00

  [Movimientos] Procesando 18 movimiento(s)...
    Movimiento #1 (Pendiente #1) - fecha: 12/01/2026
      Rendido: $ 4.800,00
      ✔ Rendición de pendiente registrada

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

## Casos de Uso Comunes

### Caso 1: Sistema nuevo implementado a mitad de mes
```bash
# Completar registros del mes actual
php artisan caja-chica:crear-asientos-historicos --mes=enero --anio=2026
php artisan libro-diario:recalcular-saldos
```

### Caso 2: Migración completa de datos
```bash
# Procesar todas las cajas chicas
php artisan caja-chica:crear-asientos-historicos
php artisan libro-diario:recalcular-saldos
```

### Caso 3: Corrección de caja chica específica
```bash
# Solo una caja chica
php artisan caja-chica:crear-asientos-historicos --caja-chica-id=5
php artisan libro-diario:recalcular-saldos
```

## Documentación Completa

Para más detalles, consulte:
- [`docs/comandos/caja-chica-crear-asientos-historicos.md`](docs/comandos/caja-chica-crear-asientos-historicos.md)

## Ayuda

Para ver todas las opciones disponibles:
```bash
php artisan caja-chica:crear-asientos-historicos --help
```

## Soporte

Si encuentra algún problema o tiene dudas sobre el comando, consulte la documentación completa o contacte al equipo de desarrollo.
