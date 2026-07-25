# Plan — Módulo Libro Diario de Tesorería

## Resumen

Creación del módulo **Libro Diario** para registrar cronológicamente todos los movimientos financieros de la Tesorería: entradas, salidas y redistribuciones, con numeración correlativa por año y saldo acumulado.

---

## 1. Tablas

### 1.1 Catálogos (valores relativamente fijos, soft-delete)

| Tabla | Modelo | Columnas |
|-------|--------|----------|
| `tes_lb_tipos` | `LbTipo` | `id`, `nombre` (varchar, unique), `signo` (tinyint: +1 Entrada, -1 Salida, 0 Redistribución) |
| `tes_lb_conceptos` | `LbConcepto` | `id`, `nombre` (varchar) |
| `tes_lb_detalle` | `LbDetalle` | `id`, `concepto_id` (FK → `tes_lb_conceptos`), `nombre` (varchar) |
| `tes_lb_medios` | `LbMedio` | `id`, `nombre` (varchar) |

Todas incluyen: `created_by` (FK → `users`), `updated_by` (FK → `users`), `deleted_by` (FK → `users`), `timestamps`, `softDeletes`.

### 1.2 Tabla transaccional

**`tes_libro_diario`** — Modelo `LibroDiario`

| Campo | Tipo | Restricciones |
|-------|------|---------------|
| `id` | bigint | PK, autoincrement |
| `fecha` | date | NOT NULL |
| `tipo_id` | bigint unsigned | FK → `tes_lb_tipos`, NOT NULL |
| `numero` | int | NOT NULL (auto-seq por año) |
| `signo_efectivo` | tinyint | NOT NULL (+1 Entrada/acredita, -1 Salida/descuenta) |
| `identidad` | varchar(255) | nullable |
| `denominacion` | varchar(255) | nullable |
| `concepto_id` | bigint unsigned | FK → `tes_lb_conceptos`, NOT NULL |
| `detalle_id` | bigint unsigned | FK → `tes_lb_detalle`, NOT NULL |
| `medio_id` | bigint unsigned | FK → `tes_lb_medios`, NOT NULL |
| `monto` | decimal(12,2) | NOT NULL |
| `saldo` | decimal(12,2) | NOT NULL (almacenado, se calcula al insertar) |
| `asociar` | bigint unsigned | nullable, FK self-ref → `tes_lb_diario.id` |

Columnas de auditoría: `created_by`, `updated_by`, `deleted_by` (FK → `users`, on delete set null), `timestamps`, `softDeletes`.

**Índices:**
- `fecha` — filtro por rango de fechas
- `numero` — búsqueda por número de asiento
- `tipo_id`, `concepto_id`, `detalle_id`, `medio_id` — joins con catálogos
- `asociar` — self-join
- Compuesto `(tipo_id, deleted_at)` — listados filtrados

**Restricción única:** compuesto `(YEAR(fecha), numero)` — un número por año.

---

## 2. Modelos

### 2.1 Ubicación

```
app/Models/Tesoreria/LibroDiario.php
app/Models/Tesoreria/LbTipo.php
app/Models/Tesoreria/LbConcepto.php
app/Models/Tesoreria/LbDetalle.php
app/Models/Tesoreria/LbMedio.php
```

### 2.2 Traits comunes en todos

- `HasFactory`
- `SoftDeletes`
- `Auditable` (asigna automáticamente `created_by`, `updated_by`, `deleted_by`)
- `LogsActivityTrait` (traza con Spatie Activitylog)

### 2.3 Relaciones del modelo `LibroDiario`

```php
// Belongs to
public function tipo()        → $this->belongsTo(LbTipo::class)
public function concepto()    → $this->belongsTo(LbConcepto::class)
public function detalle()     → $this->belongsTo(LbDetalle::class)
public function medio()       → $this->belongsTo(LbMedio::class)
public function parent()      → $this->belongsTo(self::class, 'asociar')   // asiento origen
public function children()    → $this->hasMany(self::class, 'asociar')     // asientos derivados

// Audit
public function createdBy()   → $this->belongsTo(User::class, 'created_by')
public function updatedBy()   → $this->belongsTo(User::class, 'updated_by')
public function deletedBy()   → $this->belongsTo(User::class, 'deleted_by')
```

### 2.4 Scopes de `LibroDiario`

- `scopeDelAnio($query, $anio)` — filtra por año de `fecha`
- `scopeEntreFechas($query, $desde, $hasta)` — rango de fechas
- `scopePorTipo($query, $tipoId)` — filtra por tipo
- `scopeOrdenado($query)` — ordena por `fecha ASC, numero ASC`

### 2.5 Hook `creating` en `LibroDiario` — Cálculo del saldo

**Concepto del saldo:** No es un saldo global del libro. Cada fila registra el **saldo acumulado de su subcuenta específica**, definida por la combinación `(medio_id, concepto_id, detalle_id)`. Es decir, cada `(medio, concepto, detalle)` es una subcuenta independiente con su propia evolución.

**Regla de signo:** El `signo` del `tipo` determina cómo afecta el monto al saldo de esa subcuenta:

| Tipo | signo | Efecto |
|------|-------|--------|
| Entrada | +1 | El saldo de la subcuenta **aumenta** |
| Salida | -1 | El saldo de la subcuenta **disminuye** |
| Redistribución | 0 | **No tiene signo fijo** — la operación crea dos registros, cada uno con su propio signo efectivo (ver abajo) |

**Algoritmo de cálculo del saldo (casos generales):**

```
1. Generar número: SELECT COALESCE(MAX(numero), 0) + 1
   FROM tes_libro_diario WHERE YEAR(fecha) = :anio

2. Calcular saldo de la subcuenta:
   - Obtener último saldo de la misma subcuenta:
     SELECT saldo FROM tes_libro_diario
     WHERE medio_id = :medio_id
       AND concepto_id = :concepto_id
       AND detalle_id = :detalle_id
     ORDER BY fecha DESC, numero DESC LIMIT 1
   - Si no hay registro previo → saldo_base = 0
   - Obtener signo_efectivo (ver casos abajo)
   - nuevo_saldo = saldo_base + (monto * signo_efectivo)
```

**Redistribución (caso especial):** Cuando `tipo_id` = Redistribución, se crean **dos registros en una misma transacción**, porque el dinero se mueve de una subcuenta a otra:

1. **Registro Origen** (disminuye el saldo de la subcuenta fuente):
   - `(concepto_id, detalle_id)` = subcuenta origen
   - `signo_efectivo` = -1 (se descuenta del origen)
   - `saldo` = saldo_base_origen - monto
   - `asociar` = null (o se asigna tras conocer el ID del destino)
   - `identidad`, `denominacion` = null (o datos del origen si aplica)

2. **Registro Destino** (aumenta el saldo de la subcuenta destino):
   - `(concepto_id, detalle_id)` = subcuenta destino
   - `signo_efectivo` = +1 (se acredita al destino)
   - `saldo` = saldo_base_destino + monto
   - `asociar` = ID del registro origen
   - `identidad`, `denominacion` = entidad/persona que recibe la asignación

Ambos comparten: `fecha`, `medio_id`, `monto`. Cada uno obtiene su propio `numero` correlativo (consecutivos).

**Implementación del signo_efectivo:** Dado que Redistribución tiene `signo = 0` en la tabla catálogo pero en la práctica necesita -1 y +1, el hook `creating` del modelo NO debe usar el signo del tipo directamente. En su lugar, se incorpora un campo `signo_efectivo` en la tabla `tes_libro_diario` (tinyint, NOT NULL) que almacena el signo real aplicado a esa fila:

- Para Entrada → signo_efectivo = +1
- Para Salida → signo_efectivo = -1
- Para Redistribución (origen) → signo_efectivo = -1
- Para Redistribución (destino) → signo_efectivo = +1

De esta forma el saldo se calcula siempre con `saldo_base + (monto * signo_efectivo)` sin excepciones, y el `tipo_id` conserva su valor semántico para reportes y agrupación.

Todo dentro de una transacción `DB::transaction()` con `LOCK FOR UPDATE` sobre los últimos registros de ambas subcuentas para evitar race conditions.

---

## 3. Datos semilla

### `tes_lb_tipos`

| nombre | signo |
|--------|-------|
| Entrada | +1 |
| Salida | -1 |
| Redistribución | 0 |

### `tes_lb_medios`

| nombre |
|--------|
| Efectivo |
| Cheque |
| Transferencia bancaria |
| Tarjeta de Débito (POS) |

### `tes_lb_conceptos`

| nombre |
|--------|
| Partida Presupuestal |
| Recaudación Artículo 222 |
| Recaudación Diaria |
| Caja Chica |
| Haberes |
| Devoluciones |
| Pagos varios |
| Custodia |
| Arrendamientos |

### `tes_lb_detalle` (cada uno asociado a un concepto)

| concepto | detalle |
|----------|---------|
| Haberes | Boleto en ventanilla |
| Haberes | Giro |
| Haberes | Varios |
| Recaudación Artículo 222 | Hora hombre normal |
| Recaudación Artículo 222 | Nocturnidad |
| Haberes | Sueldo Presupuestado |
| Haberes | Retención Judicial de Sueldo Presupuestado |
| Haberes | Sueldo Presupuestado (Rechazo BROU) |
| Haberes | Sueldo Presupuestado (con Quitas) |
| Haberes | Retención Judicial de Sueldo Presupuestado (Rechazo BROU) |
| Haberes | Retención Judicial de Sueldo Presupuestado (con Quitas) |
| Haberes | Devolución de mes y años anteriores |
| Devoluciones | Devolución de multas de tránsito |
| Devoluciones | Devolución de multas SOA |
| Devoluciones | Devolución por cobro en demasía |
| Devoluciones | Devolución de cobro indebido |
| Pagos varios | Pago de servicio |
| Pagos varios | Pago de multa |
| Pagos varios | Pago a proveedores |
| Arrendamientos | Arrendamiento JPM |

---

## 4. Plan de implementación por fases

| Fase | Descripción | Archivos |
|------|-------------|----------|
| **1** | Migración + Seeders | `database/migrations/*_create_libro_diario_tables.php` + `database/seeders/LibroDiarioSeeder.php` |
| **2** | Modelos (5) | `app/Models/Tesoreria/LibroDiario.php`, `LbTipo.php`, `LbConcepto.php`, `LbDetalle.php`, `LbMedio.php` |
| **3** | Servicio de lógica de negocio | `app/Services/Tesoreria/LibroDiarioService.php` |
| **4** | Componente Livewire CRUD | `app/Http/Livewire/Tesoreria/LibroDiario/` (Index, Form, etc.) |
| **5** | Rutas + Vistas | `routes/web.php`, `resources/views/livewire/tesoreria/libro_diario/` |
| **6** | Tests | `tests/Feature/LibroDiarioTest.php` |
| **7** | Seeders definitivos | Ajuste de datos semilla con usuario real |

---

## 5. Notas técnicas

- **Numeración**: se usa `MAX(numero) + 1` dentro del año fiscal. No se requiere tabla de contadores separada.
- **Saldo multidimensional**: No es un saldo global. Cada fila almacena el **saldo de su subcuenta** `(medio, concepto, detalle)`.
- **signo_efectivo**: Campo almacenado en la tabla (no depende solo del tipo). Permite que Redistribución genere dos registros con signo -1 (origen) y +1 (destino) sin ambigüedades. Para Entrada es +1, para Salida es -1.
- **Redistribución**: Operación que genera **dos registros vinculados** vía `asociar`: uno descuenta del origen (signo_efectivo = -1) y otro acredita al destino (signo_efectivo = +1). El `tipo_id` de ambos es Redistribución. El destino lleva `identidad`/`denominacion` de la entidad que recibe.
- **Eliminación**: soft delete. Si se elimina un asiento, se debe recalcular el saldo de todos los registros posteriores de la **misma subcuenta** `(medio, concepto, detalle)`.
- **Transaccionalidad**: la creación de asiento va envuelta en `DB::transaction()` con `LOCK FOR UPDATE` sobre los últimos registros de las subcuentas involucradas para evitar race conditions.
- **Seguridad**: todos los endpoints protegidos por middleware `web`, `jwt.verify`, `two-factor`.
- **Editabilidad**: solo se permiten ediciones en campos no financieros (`identidad`, `denominacion`). El resto (fecha, tipo, concepto, detalle, medio, monto, signo, número) es inmutable tras la creación. Para corregir errores: soft delete + nuevo asiento.

### 5.1 Ejemplo de cálculo de saldo

| # | fecha | tipo | signo_efectivo | concepto | detalle | medio | monto | identidad | saldo subcuenta |
|---|-------|------|----------------|----------|---------|-------|-------|-----------|-----------------|
| 1 | 01/07 | Entrada | +1 | Recaudación Diaria | Boleto en ventanilla | Efectivo | 1000 | — | 1000.00 |
| 2 | 01/07 | Entrada | +1 | Recaudación Diaria | Boleto en ventanilla | Efectivo | 500 | — | 1500.00 |
| 3 | 01/07 | Salida | -1 | Pagos varios | Pago de servicio | Efectivo | 200 | — | −200.00 |
| 4 | 01/07 | Salida | -1 | Pagos varios | Pago de servicio | Cheque | 300 | — | −300.00 |
| 5 | 02/07 | Redistribución | -1 | Custodia | Efectivo | Efectivo | 500 | — | **1000.00** |
| 6 | 02/07 | Redistribución | +1 | Recaudación Diaria | Giro | Efectivo | 500 | Fulano | **500.00** |

**Explicación:**

- `#1` y `#2`: misma subcuenta `(Efectivo, Recaudación Diaria, Boleto en ventanilla)` → saldo acumulado 1500.
- `#3`: subcuenta distinta `(Efectivo, Pagos varios, Pago de servicio)` → saldo propio −200.
- `#4`: mismo concepto/detalle que #3 pero medio Cheque → subcuenta independiente → saldo −300.
- `#5` y `#6`: Redistribución. `#5` descuenta 500 de `(Efectivo, Custodia, Efectivo)` — el saldo de esa subcuenta era 1500, pasa a 1000. `#6` acredita 500 a `(Efectivo, Recaudación Diaria, Giro)` — saldo de 0 a 500. Fulano recibe la asignación → `identidad/denominacion` en `#6`, `asociar` de `#6` → `#5`.
