# Plan de Integración: CFE → Libro Diario

## 1. Objetivo

Crear asientos automáticos en el Libro Diario (`tes_libro_diario`) **al registrarse cada CFE** (`CfeCreado`), y generar **contra-asientos** (reversión con signo opuesto) cuando el CFE se elimina (`CfeEliminado`), preservando la pista de auditoría.

No se manejan actualizaciones porque solo se modifican campos no monetarios.

---

## 2. Estructura Nueva de LbDetalle

### 2.1. Nuevo detalle para Artículo 222

Agregar en `LibroDiarioSeeder::seedConceptosYDetalles()` bajo `'Recaudación Artículo 222'`:

```
'Recaudaciones varias de Artículo 222'
```

El concepto `Recaudación Artículo 222` queda así:

| Concepto | Detalle |
|---|---|
| Recaudación Artículo 222 | Hora hombre normal |
| Recaudación Artículo 222 | Hora hombre nornal (nocturno) |
| Recaudación Artículo 222 | Hora hombre financiero |
| Recaudación Artículo 222 | Hora hombre financiero (nocturno) |
| **Recaudación Artículo 222** | **Recaudaciones varias de Artículo 222** ← NUEVO |

### 2.2. Detalle de respaldo para Recaudación Diaria

Se reutiliza el detalle existente `Otras recaudaciones varias` ya presente en `Recaudación Diaria`. No requiere cambios en el seeder.

---

## 3. Algoritmo de Asignación Concepto/Detalle

### 3.1. Determinación del concepto SIIF

Cada CFE tiene un `tes_caja_concepto_id` → `CajaConcepto` → `siif_distribucion_tipo_id` (1 = Art. 222, 2 = Rec. Diaria).

### 3.2. Asignación para Artículo 222

| Campo | Valor |
|---|---|
| `LbConcepto` | `Recaudación Artículo 222` (buscar por nombre) |
| `LbDetalle` | `Recaudaciones varias de Artículo 222` (buscar por nombre) |

**No se distingue entre hora normal/nocturno/financiero** — todo va al mismo detalle.

### 3.3. Asignación para Recaudación Diaria

Se intenta matchear el nombre del `CajaConcepto` con los nombres de `LbDetalle` bajo `Recaudación Diaria`:

```
normalizar(CajaConcepto.caja_concepto) === normalizar(LbDetalle.nombre)
```

Donde `normalizar()` usa `TextoHelper::normalizarTexto()` (minúsculas + sin acentos).

**Mapeo esperado:**

| CajaConcepto | LbDetalle | ¿Match? |
|---|---|---|
| ARRENDAMIENTOS | Arrendamientos | ✅ Sí |
| CERTIFICADO DE RESIDENCIA | Certificado de Residencia | ✅ Sí |
| MULTAS DE TRÁNSITO | Multas de Tránsito | ✅ Sí |
| PORTE DE ARMAS | Porte de armas | ✅ Sí |
| DEPÓSITO DE VEHÍCULOS | Depósito de vehículos | ✅ Sí |
| TITULO HABILITACIÓN Y TENENCIA DE ARMA (TAHTA) | Título de Habilitación y Tenencia de Armas (THATA) | ❌ No (arma vs armas, tahta vs thata) |
| ARTÍCULO 222 | (no aplica — va a Art. 222) | N/A |

**Si no hay match → se usa `Otras recaudaciones varias`** (detalle de respaldo).

### 3.4. Asignación del Medio de Pago

El `medio_pago_tipo` del CFE (`TesCfeMedioPago.medio_pago_tipo`) se mapea contra `LbMedio.nombre` mediante búsqueda flexible insensible a mayúsculas/acentos:

| medio_pago_tipo | LbMedio |
|---|---|
| Efectivo | Efectivo |
| Cheque | Cheque |
| Transferencia bancaria | Transferencia bancaria |
| Tarjeta de Débito (POS) | Tarjeta de Débito (POS) |

---

## 4. Generación de Asientos (en creación del CFE)

### 4.1. Momento de creación

Apenas se registra el CFE, via listener del evento `CfeCreado`. No se espera a la confirmación de planilla ER.

Los asientos se crean **dentro de la misma transacción** que el CFE (el dispatch ocurre dentro de `DB::transaction()` en `CfeCreatorService`).

### 4.2. Estructura de cada asiento

Se crea **un asiento por combinación (Concepto, Detalle, MedioPago)** por cada CFE.

| Campo | Fuente |
|---|---|
| `fecha` | `cfe.fecha` |
| `tipo_id` | `LbTipo::where('nombre', 'Entrada')->first()->id` |
| `signo_efectivo` | `+1` |
| `numero` | Auto-generado por `LibroDiarioService::registrarAsiento()` |
| `identidad` | `cfe.receptor_nombre_denominacion` (o "CONSUMIDOR FINAL") |
| `denominacion` | `"{documento_tipo} {documento_serie}-{documento_numero}"` |
| `descripcion` | Texto detallado multi-línea (ver 4.3) |
| `concepto_id` | ID del LbConcepto correspondiente (Art. 222 o Rec. Diaria) |
| `detalle_id` | ID del LbDetalle correspondiente (matcheado o fallback) |
| `medio_id` | ID del LbMedio correspondiente |
| `monto` | `medio_pago_valor` prorrateado por la proporción de items de este detalle |
| `cfe_id` (nueva columna) | `cfe.id` — para rastrear qué asientos pertenecen a cada CFE |

### 4.3. Formato del campo `descripcion`

```
Documento: E-123-4567
Receptor: JUAN PÉREZ
Referencias: REF-001
Adenda: Nota adicional
Ítems:
  - Detalle: Concepto X | Cant: 2 | $ 1.000,00
  - Detalle: Concepto Y | Cant: 1 | $ 2.500,00
```

### 4.4. Agrupación y splits

1. Determinar concepto/detalle según algoritmo (sección 3)
2. Todos los items del CFE que mapean al mismo detalle se agrupan
3. Los medios de pago se prorratean según proporción del subtotal de items en ese detalle vs total del CFE (misma lógica que `calcularGruposRecaudacion()` en `Confirmar.php`)
4. Crear un asiento `Entrada` por cada medio de pago dentro del detalle

### 4.5. CFEs sin medios de pago

Si `cfe.mediosPago` está vacío, se crea **un solo asiento** con `medio_id = null` (o medio "Desconocido" si existe) y `monto = total_a_pagar`.

---

## 5. Archivos a Modificar/Crear

### 5.1. Seeders

| Archivo | Cambio |
|---|---|
| `database/seeders/LibroDiarioSeeder.php` | Agregar detalle "Recaudaciones varias de Artículo 222" |

### 5.2. Nuevo servicio

| Archivo | Propósito |
|---|---|
| `app/Services/Tesoreria/RegistrarAsientosCfeService.php` | Lógica orquestadora: recibe planilla confirmada, agrupa items, resuelve concepto/detalle/medio, llama a LibroDiarioService |

### 5.3. Nuevo listener

| Archivo | Propósito |
|---|---|
| `app/Listeners/Tesoreria/RegistrarAsientosCfeListener.php` | Escucha evento de planilla confirmada y ejecuta el servicio |

### 5.4. Nueva migración

| Archivo | Cambio |
|---|---|
| `database/migrations/xxxx_add_cfe_id_to_tes_libro_diario.php` | Agregar columna `cfe_id` (nullable, FK a `tes_cfes`) y columna `es_contra_asiento` (boolean, default false) en `tes_libro_diario` |

### 5.5. Eventos (existentes — sin cambios)

No se crean eventos nuevos. El listener escucha:
- `CfeCreado` (ya existe) → crear asientos
- `CfeEliminado` (ya existe) → crear contra-asientos

### 5.6. Componentes Livewire

| Archivo | Cambio |
|---|---|
| `app/Http/Livewire/Tesoreria/EstadosRecaudacion/Confirmar.php` | **Sin cambios** — ya no se gatilla desde acá |

### 5.7. Service Provider

| Archivo | Cambio |
|---|---|
| `app/Providers/EventServiceProvider.php` | Agregar listener `RegistrarAsientosCfeListener::class` a la escucha de `CfeCreado` y `CfeEliminado` vía método `$subscribe` (mismo patrón que `LogCfeActivity`) |

---

## 6. Contra-asientos por Eliminación de CFE

### 6.1. Cuándo se crean

Cuando se dispara `CfeEliminado` (CFE soft-deleteado). El listener busca todos los asientos asociados al CFE via `cfe_id` y crea un contra-asiento (`Salida` con signo -1) por cada uno.

### 6.2. Estructura del contra-asiento

| Campo | Valor |
|---|---|
| `fecha` | Fecha actual (cuando se elimina) |
| `tipo_id` | `LbTipo::where('nombre', 'Salida')->first()->id` |
| `signo_efectivo` | `-1` |
| `numero` | Auto-generado |
| `identidad` | Mismo que el asiento original |
| `denominacion` | `"CONTRA-ASIENTO: {doc_original}"` |
| `descripcion` | `"Contra-asiento por anulación de CFE {doc_tipo} {serie}-{num}. Motivo: {razon}"` |
| `concepto_id` | Mismo que el asiento original |
| `detalle_id` | Mismo que el asiento original |
| `medio_id` | Mismo que el asiento original |
| `monto` | Mismo monto del asiento original |
| `asociar` | ID del asiento original (linkea contra-asiento con su original) |

### 6.3. Por qué contra-asientos y no DELETE

- **Auditoría**: No se pierde el registro del ingreso original ni de su reversión
- **Saldo correcto**: El saldo de la subcuenta se recalcula con ambos asientos (original + reversión), dando saldo neto 0
- **Inmutabilidad**: Los campos financieros en `tes_libro_diario` no se modifican nunca, solo se agregan nuevas filas
- **Trazabilidad**: El campo `asociar` vincula cada contra-asiento con su original

### 6.4. Edición de CFE

No aplica: el método `CfeCreatorService::updateCfe()` solo actualiza `fecha`, `tes_caja_concepto_id`, `siif_distribucion_dependencia_id` y `siif_distribucion_id` de items — campos no monetarios que no afectan los asientos ya creados.

---

## 7. Protección contra duplicados

### 7.1. Idempotencia

Agregar columna `planilla_er_id` nullable a `tes_libro_diario` (o usar un campo `asociar` existente) para marcar qué asientos fueron generados por una confirmación de planilla. Antes de crear, verificar si ya existen asientos vinculados a esta planilla.

Alternativa más simple: usar `Cache::lock()` o una transacción con verificación en el listener.

### 7.2. Rollback

Toda la creación de asientos debe ejecutarse dentro de `DB::transaction()`. Si falla la creación de cualquiera de los asientos (por ejemplo, el 3ro de 5), se revierten todos.

---

## 8. Pruebas

### 8.1. Escenarios a cubrir

| # | Escenario | Expected |
|---|---|---|
| 1 | CFE de Arrendamientos, pago en efectivo | 1 asiento Entrada: Recaudación Diaria / Arrendamientos / Efectivo |
| 2 | CFE de Art. 222, pago en efectivo + cheque | 2 asientos Entrada: ambos con concepto Art. 222 / detalle Recaudaciones varias de Artículo 222, cada uno con su medio |
| 3 | CFE de TAHTA (sin match en Rec. Diaria) | Fallback a Recaudación Diaria / Otras recaudaciones varias |
| 4 | CFE sin medios de pago | 1 asiento Entrada con medio_id = null |
| 5 | Eliminar CFE que generó 2 asientos | 2 contra-asientos Salida, linkeados vía `asociar` |
| 6 | CFE de Arrendamientos + Prendas (mismo CajaConcepto, pero Prendas es otro concepto SIIF) | Según reglas de matching |
| 7 | Crear CFE duplicado (falla validación) | 0 asientos — el evento nunca se dispara |

### 8.2. Test de unidad

Crear test `RegistrarAsientosCfeServiceTest` que:
- Construya una planilla con items mock
- Ejecute el servicio
- Verifique la cantidad correcta de asientos creados en `tes_libro_diario`

---

## 9. Orden de Implementación Sugerido

```
 1. Agregar "Recaudaciones varias de Artículo 222" al LibroDiarioSeeder
 2. Crear migración: add cfe_id y es_contra_asiento a tes_libro_diario
 3. Crear RegistrarAsientosCfeService con el algoritmo completo:
    - Resolver concepto/detalle (CajaConcepto → LbDetalle)
    - Resolver medio de pago
    - Prorratear montos por item
    - Registrar asientos vía LibroDiarioService
    - Crear contra-asientos en CfeEliminado
 4. Crear RegistrarAsientosCfeListener:
    - handleCfeCreado(): llama al service
    - handleCfeEliminado(): llama al service para contra-asientos
    - subscribe(): se registra en CfeCreado y CfeEliminado
 5. Registrar listener en EventServiceProvider (vía $subscribe)
 6. Ejecutar php artisan migrate
 7. Ejecutar php artisan db:seed --class=LibroDiarioSeeder
 8. Probar flujo completo
```
