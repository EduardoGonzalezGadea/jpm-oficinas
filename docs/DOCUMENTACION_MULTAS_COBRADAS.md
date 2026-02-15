# Documentación de la Sección de Multas Cobradas

## Descripción General

El módulo de **Multas Cobradas** es una sección crítica del sistema de Tesorería que permite la gestión, registro y reporte de los cobros realizados por conceptos de multas (principalmente de tránsito). Este módulo integra una gestión dinámica de ítems (desglose) y múltiples medios de pago en un solo registro.

### Propósito del Módulo

El módulo de Multas Cobradas permite:

- **Registrar cobros de multas** con desglose detallado de conceptos.
- **Gestionar múltiples medios de pago** para un mismo recibo (ej: parte en efectivo, parte por transferencia).
- **Controlar la consistencia financiera** entre el total percibido y la suma de ítems/medios de pago.
- **Generar reportes y resúmenes** diarios o por rangos de fecha.
- **Carga masiva vía CFE** (Comprobante Fiscal Electrónico).

---

## Arquitectura del Módulo

### Estructura de Archivos

```
app/
├── Models/
│   └── Tesoreria/
│       ├── TesMultasCobradas.php   # Modelo principal (Cabecera)
│       └── TesMultasItems.php      # Modelo de ítems desglosados
├── Http/
│   └── Livewire/
│       └── Tesoreria/
│           └── MultasCobradas/
│               └── MultasCobradas.php # Componente principal
└── Services/
    └── Tesoreria/
        └── MedioPagoService.php    # Lógica de medios de pago

resources/views/
└── livewire/
    └── tesoreria/
        └── multas-cobradas/
            └── multas-cobradas.blade.php # Vista principal y modal
```

---

## Modelos

### TesMultasCobradas.php (Cabecera)

Representa el registro general del cobro.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int | Identificador único |
| `recibo` | string | Número de recibo (ej: A-1234) |
| `fecha` | date | Fecha de cobro |
| `monto` | decimal | Monto total percibido |
| `nombre` | string | Nombre del contribuyente |
| `cedula` | string | Cédula o RUT |
| `forma_pago`| string | String codificado (ej: EFECTIVO:1000/BROU:500) |
| `adicional` | string | Teléfono y Período concatenados |

### TesMultasItems.php (Detalle)

Representa cada concepto cobrado dentro de un recibo.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `parent_id`| int | Relación con TesMultasCobradas |
| `detalle`  | string | Concepto (ej: MULTA TRANSITO ART. X) |
| `importe`  | decimal| Monto del ítem individual |

---

## Lógica de UI y Reactividad (Alpine.js + Livewire)

Una de las características más avanzadas de este módulo es su **sincronización híbrida** para evitar retrasos en la interfaz de usuario.

### 1. Cálculo de Totales en Tiempo Real

El total se calcula de tres formas para asegurar exactitud:

- **Al escribir:** Alpine.js escucha el evento `@input` en los campos de importe y suma los valores del DOM instantáneamente.
- **Al agregar/eliminar filas:** El servidor (Livewire) calcula el total exacto y lo envía al frontend mediante un evento de navegador `update-total`.
- **Validación final:** Antes de guardar, se verifica que `Suma(Ítems) == Suma(Medios de Pago)`.

### 2. Prevención de Parpadeo (Flickering)

El modal utiliza `wire:ignore.self`. Esto evita que Livewire reconstruya todo el contenedor del modal cuando se actualiza la lista de ítems, manteniendo el foco del cursor y el estado de Alpine.js intactos.

### 3. Identificación Única de Filas

Cada fila de ítem utiliza `wire:key="item-row-{{ $item['_uid'] }}"`. Esto es fundamental para que, al eliminar una fila (especialmente la primera), Livewire no confunda los inputs restantes y mantenga sus valores correctamente.

---

## Gestión de Medios de Pago

El sistema permite múltiples medios de pago mediante un formato de cadena serializada manejado por `MedioPagoService.php`.

**Ejemplo de formato:** `EFECTIVO:1500.00/BROU:500.00`

- **Sugerencias:** Se cargan dinámicamente desde el servicio.
- **Validación:** El sistema avisa si la suma de los montos ingresados en los badges de medios de pago no coincide con el total de la multa.

---

## Reportes y Resúmenes

El componente incluye un sistema de caché inteligente para los totales del día:

- **Resumen por Medio de Pago:** Agrupa los cobros del rango seleccionado por tipo de pago, discriminando cobros puros de combinados.
- **Caché:** Utiliza un sistema de invalidación automática (`invalidateCache`) que limpia las claves de reporte cada vez que se crea, edita o elimina un registro.

---

## Mejoras Técnicas Recientes

1. **Sincronización por Eventos:** Se reemplazó el cálculo basado solo en DOM por un evento `dispatchBrowserEvent('update-total')` desde el backend. Esto solucionó el problema donde el total no se actualizaba correctamente al borrar ítems.
2. **Persistence de Estado:** Implementación de `_uid` únicos para cada ítem en el array `items_form`, asegurando que la reactividad de Livewire no "mezcle" datos de diferentes filas.
3. **Optimización de Carga:** Uso de `defer` en modelos de Livewire y cálculo reactivo en el cliente para una sensación de aplicación de escritorio.

---

## Notas para Mantenimiento

- **Formatos de Fecha:** El sistema utiliza `datepicker-uy` para asegurar el formato `DD/MM/YYYY` en el frontend, pero lo convierte a `YYYY-MM-DD` para la base de datos en el backend.
- **Mayúsculas Automáticas:** Utiliza el trait `ConvertirMayusculas` para normalizar nombres y conceptos.
- **Búsqueda:** La búsqueda en el índice principal escanea tanto la cabecera como los detalles de los ítems (mediante `orWhereHas('items', ...)`).
