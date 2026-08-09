# Plan de Unificación de Medios de Pago

## 1. Resumen Ejecutivo

Actualmente existen **5 representaciones distintas** de "medio de pago" en el sistema, con
3 tablas catálogo y 2 campos en texto libre. Esto genera duplicación, inconsistencias y
dificultad de mantenimiento. El plan propone unificar en un único catálogo (`tes_medio_de_pagos`)
y migrar todos los consumidores a usar su FK.

---

## 2. Inventario de Representaciones Actuales

### 2.1 Catálogos Existentes

| # | Modelo | Tabla | Propósito | Registros |
|---|--------|-------|-----------|-----------|
| A | `MedioDePago` | `tes_medio_de_pagos` | Catálogo general: nombre, descripcion, activo, contado, codigo_soniar | ~4 (Efectivo, Transferencia, POS, Cheque) |
| B | `LbMedio` | `tes_lb_medios` | Catálogo para Libro Diario: nombre, nombre_corto | ~4 |
| C | *(sin modelo)* | `tes_medios_pago_caja` | Catálogo para Caja Diario: nombre, codigo, requiere_conciliacion, orden, activo | *(sin uso en app)* |

### 2.2 Tablas con Medio de Pago como Texto Libre

| # | Tabla | Columna | Formato | Ejemplo |
|---|-------|---------|---------|---------|
| D | `tes_cfe_medios_pago` | `medio_pago_tipo` (VARCHAR) | Texto libre | `"Transferencia"`, `"Efectivo"` |
| E | `tes_arrendamientos` | `medio_de_pago` (VARCHAR) | Texto libre | `"TRANSFERENCIA"` |
| F | `tes_eventuales` | `medio_de_pago` (VARCHAR) | Texto libre | `"TRANSFERENCIA"` |
| G | `tes_multas_cobradas` | `forma_pago` (VARCHAR) | Texto libre con formato combinado | `"EFECTIVO:1000/CHEQUE:2000"` |

### 2.3 Tablas con FK a Catálogo (Correcto)

| # | Tabla | FK | Destino |
|---|-------|----|---------|
| H | `tes_prendas` | `medio_pago_id` (BIGINT) | `tes_medio_de_pagos.id` |
| I | `tes_deposito_vehiculos` | `medio_pago_id` (BIGINT) | `tes_medio_de_pagos.id` |
| J | `tes_libro_diario` | `medio_id` (BIGINT) | `tes_lb_medios.id` |

### 2.4 Relación Cruzada entre Catálogos

`RegistrarAsientosCfeService` (línea 186-193) **resuelve** el texto de
`TesCfeMedioPago.medio_pago_tipo` contra `LbMedio.nombre` usando coincidencia
por substring. Esto acopla los dos catálogos de forma frágil.

---

## 3. Mapa de Consumidores por Modelo

### 3.1 `MedioDePago` (tes_medio_de_pagos)

| Componente/Archivo | Uso |
|---------------------|-----|
| `app/Models/Tesoreria/MedioDePago.php` | Definición del modelo |
| `app/Http/Livewire/Tesoreria/Configuracion/MediosDePago.php` | CRUD (índice, crear, editar, eliminar) |
| `app/Http/Controllers/Tesoreria/MedioDePagoController.php` | CRUD legacy (vistas Blade) |
| `app/Http/Livewire/Tesoreria/Prendas/Create.php` | Select FK `medio_pago_id` |
| `app/Http/Livewire/Tesoreria/Prendas/Edit.php` | Select FK `medio_pago_id` |
| `app/Http/Livewire/Tesoreria/Prendas/CargarCfe.php` | Resuelve texto extraído a FK |
| `app/Http/Livewire/Tesoreria/Prendas/PrendasReporte.php` | Filtro por FK `medio_pago_id` |
| `app/Http/Livewire/Tesoreria/DepositoVehiculos/Create.php` | Select FK `medio_pago_id` |
| `app/Http/Livewire/Tesoreria/DepositoVehiculos/Edit.php` | Select FK `medio_pago_id` |
| `app/Http/Livewire/Tesoreria/DepositoVehiculos/DepositoVehiculosReporte.php` | Filtro por FK `medio_pago_id` |
| `app/Http/Livewire/Tesoreria/Arrendamientos/CargarCfe.php` | Resuelve texto a `nombre` (string) |
| `app/Http/Livewire/Tesoreria/Arrendamientos/Arrendamiento.php` | Select texto libre `medio_de_pago` |
| `app/Http/Livewire/Tesoreria/Arrendamientos/PrintArrendamientos.php` | GROUP BY `medio_de_pago` |
| `app/Http/Livewire/Tesoreria/Eventuales/CargarEfactura.php` | Resuelve texto a `nombre` (string via `normalizarMedioPago`) |
| `app/Http/Livewire/Tesoreria/Eventuales/Eventuales.php` | Select texto libre `medio_de_pago` |
| `app/Http/Livewire/Tesoreria/Eventuales/PrintEventuales.php` | GROUP BY `medio_de_pago` |
| `app/Http/Livewire/Tesoreria/GestionCfe/Index.php` | Provee lista al componente (para referencia) |
| `app/Services/Tesoreria/MedioPagoService.php` | `obtenerMediosDisponibles()`, `obtenerNombreReal()` |
| `database/seeders/Tesoreria/MedioDePagoSeeder.php` | Seed de datos iniciales |

### 3.2 `LbMedio` (tes_lb_medios)

| Componente/Archivo | Uso |
|---------------------|-----|
| `app/Models/Tesoreria/LbMedio.php` | Definición del modelo |
| `app/Http/Livewire/Tesoreria/LibroDiario/LbMedios.php` | CRUD completo |
| `app/Http/Livewire/Tesoreria/LibroDiario/Index.php` | Select FK `medio_id` para asientos |
| `app/Http/Livewire/Tesoreria/LibroDiario/Asientos.php` | Select FK `medio_id` |
| `app/Http/Livewire/Tesoreria/CargaMasivaHaberes/Index.php` | Busca `LbMedio` por nombre "Efectivo" |
| `app/Services/Tesoreria/RegistrarAsientosCfeService.php` | Resuelve texto → `LbMedio.id` |
| `app/Models/Tesoreria/LibroDiario.php` | FK `medio_id` → `LbMedio` |
| `database/seeders/LibroDiarioSeeder.php` | Seed de datos |

### 3.3 `TesCfeMedioPago` (tes_cfe_medios_pago)

| Componente/Archivo | Uso |
|---------------------|-----|
| `app/Models/Tesoreria/TesCfeMedioPago.php` | Definición del modelo |
| `app/Models/Tesoreria/TesCfe.php` | Relación `hasMany('mediosPago')` |
| `app/Services/Tesoreria/CfeCreatorService.php` | `createMediosPago()` escribe `medio_pago_tipo` como string |
| `app/Http/Livewire/Tesoreria/GestionCfe/WithNuevoCfe.php` | Array `nuevoMediosPago` → `['tipo' => string, 'valor' => float]` |
| `app/Http/Livewire/Tesoreria/EstadosRecaudacion/Confirmar.php` | `groupBy('medio_pago_tipo')` + suma `medio_pago_valor` |
| `app/Http/Livewire/Tesoreria/Recaudaciones/Index.php` | `groupBy('medio_pago_tipo')` + suma `medio_pago_valor` |
| `app/Http/Livewire/AsesoriaContable/ResumenRecaudaciones/Index.php` | `groupBy('medio_pago_tipo')` + suma `medio_pago_valor` |
| `app/Console/Commands/ImportarCfesDePruebaCommand.php` | Escribe texto libre |

### 3.4 `TesMultasCobradas.forma_pago` (texto combinado)

| Componente/Archivo | Uso |
|---------------------|-----|
| `app/Models/Tesoreria/TesMultasCobradas.php` | `$fillable` incluye `forma_pago` |
| `app/Http/Livewire/Tesoreria/MultasCobradas/MultasCobradas.php` | Campo `$forma_pago` + guarda como texto |
| `app/Http/Livewire/Tesoreria/MultasCobradas/CargarCfe.php` | Extrae del PDF y guarda como texto |
| `app/Http/Livewire/Tesoreria/MultasCobradas/PrintMultasCobradas*.php` | GROUP BY + parsea con `MedioPagoService` |
| `app/Services/Tesoreria/MultasCobradasService.php` | Procesa `forma_pago` con `MedioPagoService` |
| `app/Services/Tesoreria/MedioPagoService.php` | Parseo de formato `"EFECTIVO/CHEQUE"` |

### 3.5 Inventario de Strings Hardcodeados en Código

Existe un problema adicional no cubierto por las migraciones de schema:
**strings de medios de pago hardcodeados en lógica de negocio PHP/Blade**.
Estos NO se migran automáticamente al agregar FKs — son puntos ciegos que
requieren refactor manual.

#### 3.5.1 Strings por Tipo de Lógica

| Tipo | Archivo(s) | Líneas | Severidad |
|------|-----------|--------|-----------|
| `stripos`/`str_contains` para dispatch | `Prendas\CargarCfe.php`, `EstadosRecaudacion\Confirmar.php`, `Recaudaciones\Index.php` | 288, 338, 340, 343, 345, 362 / 575 / 122 | ALTA |
| `where('nombre', 'like', '%Transferencia%')` | `Arrendamientos\CargarCfe.php:75`, `Arrendamientos\Arrendamiento.php:38`, `Eventuales\{CargarEfactura.php:156-157,187-188, Eventuales.php:48}`, `Prendas\CargarCfe.php:340` | 6 LIKE queries | ALTA |
| Fallback `'SIN DATOS'` (legacy) | 18 apariciones en extractores y componentes | ver §3.5.2 | MEDIA |
| Fallback `'TRANSFERENCIA'` (default en normalización) | `Eventuales\CargarEfactura.php:188`, `eventuales\cargar-efactura.blade.php:213` | 2 | MEDIA |
| Fallback `'Transferencia'` | `Arrendamientos\Arrendamiento.php:118`, `Eventuales\Eventuales.php:122` | 2 | MEDIA |
| Consulta exacta `'Efectivo'` sobre `LbMedio` | `CargaMasivaHaberes\Index.php:268, 338` | 2 | MEDIA |
| `tarjeta`/`debito`/`débito`/`siif` en dispatch | `EstadosRecaudacion\Confirmar.php:575`, `Recaudaciones\Index.php:122` | 2 | MEDIA |

#### 3.5.2 Apariciones de `'SIN DATOS'` (texto centinela legacy)

Más de 18 apariciones del centinela `'SIN DATOS'` representan un medio de
pago "desconocido". Se usa como fallback cuando el parser del CFE no logra
extraer el medio. Post-migración, debe reemplazarse por `NULL` en la FK
(`medio_pago_id = NULL`) y reemplazar las comparaciones `=== 'SIN DATOS'`
por un método `tieneMedioPago(): bool` en el modelo.

Archivos afectados (parcial):
- `app/Services/CfeExtractor/BaseExtractor.php`
- `app/Services/CfeExtractor/{PrendasExtractor,ArrendamientosExtractor,CertificadoResidenciaExtractor,MultasExtractor}.php`
- `app/Services/CfeProcessorService.php`
- `app/Services/Tesoreria/CfeConfirmationService.php`
- `app/Http/Livewire/Tesoreria/MultasCobradas/MultasCobradas.php`
- `app/Http/Livewire/Tesoreria/MultasCobradas/CargarCfe.php` (3 apariciones)
- `app/Http/Livewire/Tesoreria/CertificadosResidencia/CargarCfe.php`
- `app/Http/Livewire/Tesoreria/MultasCobradas/PrintMultasCobradas{Full,Resumen}.php`

#### 3.5.3 Top 5 Puntos Críticos de Riesgo

| # | Archivo | Apariciones | Motivo |
|---|---------|-------------|--------|
| 1 | `app/Http/Livewire/Tesoreria/Prendas/CargarCfe.php` | 6 | Mezcla `stripos` + `where like` + `'SIN DATOS'` |
| 2 | `app/Http/Livewire/Tesoreria/EstadosRecaudacion/Confirmar.php` | 7 | Dispatch por `str_contains('tarjeta'/'debito'/'siif')` |
| 3 | `app/Http/Livewire/Tesoreria/Recaudaciones/Index.php` | 7 | Idem Confirmar.php (lógica duplicada) |
| 4 | `app/Http/Livewire/Tesoreria/Eventuales/CargarEfactura.php` | 5 | `normalizarMedioPago` + fallbacks `'TRANSFERENCIA'` |
| 5 | `app/Services/Tesoreria/MultasCobradasService.php` + `MedioPagoService.php` | 3 + 2 | Núcleo de normalización, 11 callers dependientes de `obtenerNombreReal` |

> **Implicancia para el plan**: §5.3.Cambios en Código debe incluir el
> refactor de estos strings a través de un método centralizado (ej.
> `MedioPagoService::resolverPorTexto(string $texto): ?MedioDePago`) y
> eliminar progresivamente el patrón `'SIN DATOS'`.

---

## 4. Diagnóstico de Problemas

### 4.1 Duplicación de Catálogos

Los catálogos A (`tes_medio_de_pagos`) y B (`tes_lb_medios`) contienen
información redundante:
- Ambos tienen registros como "Efectivo", "Transferencia"
- No hay sincronización entre ellos
- `RegistrarAsientosCfeService` hace match textual frágil

### 4.2 Texto Libre vs FK

Las tablas E (`tes_arrendamientos`), F (`tes_eventuales`) y G
(`tes_multas_cobradas`) almacenan el medio de pago como texto libre.
Esto impide:
- Reportes agregados confiables (variantess Tipográficas: "Transferencia" vs "TRANSFERENCIA")
- Migración/renombrado de medios de pago
- Integridad referencial

### 4.3 Tabla `tes_medios_pago_caja` Huérfana

La tabla C existe en el schema pero **no tiene modelo, ni controlador,
ni componente Livewire**. No es referenciada por ninguna FK. Es
candidata a eliminación si no hay planes de reactivar el módulo Caja
Diaria.

### 4.4 `TesCfeMedioPago` sin FK

La tabla D guarda `medio_pago_tipo` como VARCHAR en lugar de FK a
`tes_medio_de_pagos`. Esto impide trackear cambios en el catálogo.

### 4.5 Formato Combinado en Multas

El campo `forma_pago` en `tes_multas_cobradas` usa un formato
`"EFECTIVO:1000/CHEQUE:2000"` que requiere parseo con
`MedioPagoService`. Es un intento de relación 1:N embebida en un string.

---

## 5. Propuesta de Unificación

### 5.1 Catálogo Único

**Estrategia**: Fusionar `tes_lb_medios` y `tes_medio_de_pagos` en una
sola tabla `tes_medio_de_pagos`, normalizando todas las variantes
históricas a un catálogo configurable. **El catálogo inicial post-migración
tendrá 4 medios** (definidos en §5.1.1), pero la existencia de registros
no debe asumirse como fija: el schema y el código deben operar sobre
`MedioDePago::all()` para soportar agregados/removidos futuros sin
refactor.

#### 5.1.1 Catálogo Final (valores iniciales post-migración)

Esta es una **decisión de negocio**: el catálogo unificado se inicializa
con **4 registros**. Cualquier variante textual histórica que no coincida
con uno de estos 4 debe mapearse al más cercano según la tabla de §5.3.

> **A futuro**: el catálogo puede crecer o reducirse via CRUD en
> `Configuracion/MediosDePago.php`; el diseño del sistema **no debe
> asumir** la cantidad de medios. Los 4 actuales son el set de arranque.

| orden | nombre                       | nombre_corto   | descripcion                                  | contado | codigo_soniar | es_libro_diario | es_recaudacion |
|-------|------------------------------|----------------|----------------------------------------------|---------|---------------|-----------------|----------------|
| 1     | `Efectivo`                   | `Efectivo`     | Dinero físico en billetes/monedas            | 1       | *(verificar)* | 1               | 1              |
| 2     | `Cheque`                     | `Cheque`       | Cheque bancario (propios o de terceros)      | 0       | *(verificar)* | 1               | 1              |
| 3     | `Transferencia Bancaria`     | `Transferencia`| Transferencia entre cuentas (BROU, otra)    | 0       | *(verificar)* | 1               | 1              |
| 4     | `Tarjeta de Débito (POS)`    | `Débito (POS)` | Tarjeta de débito terminal POS              | 0       | *(verificar)* | 1               | 1              |

> **Notas clave**:
> - El campo `codigo_soniar` no se documenta en este plan; debe verificar
>   valor existente en `tes_medio_de_pagos` antes de la migración y
>   preservarlo.
> - `es_libro_diario = 1` y `es_recaudacion = 1` para todos los registros:
>   los 4 medios aplican a ambos contextos.
> - `contado = 1` solo para `Efectivo` (los demás son diferidos/compensados).
> - IDs serán auto-asignados; los valores arriba son referenciales.

#### 5.1.2 Catálogo Histórico (mapeo a target)

Registros existentes en `tes_medio_de_pagos` o `tes_lb_medios` que NO
coinciden con los 4 del catálogo final deben eliminarse o renombrarse:

| Variante histórica                   | Mapeo target                | Acción |
|--------------------------------------|-----------------------------|--------|
| `Efectivo`                           | `Efectivo`                  | Renombrar `nombre_corto` = `Efectivo` si difiere |
| `Cheque`                             | `Cheque`                    | Renombrar `nombre_corto` = `Cheque` si difiere |
| `Transferencia`                      | `Transferencia Bancaria`    | **Renombrar `nombre`**, `nombre_corto = Transferencia` |
| `POS`                                | `Tarjeta de Débito (POS)`   | **Renombrar `nombre`**, `nombre_corto = Débito (POS)` |
| `Débito`, `Débito`, `Tarjeta`, `TARJETA` | `Tarjeta de Débito (POS)` | **Eliminar/E_PENDING (registro con `id` alias)** |
| `Crédito`, `CREDITO`                 | *(variable)*                | Mapear según reglas de negocio — cheque específico |
| `BROU`, `Brou`                       | `Transferencia Bancaria`    | Mapeo textual |
| `Depósito`, `DEPOSITO`               | `Transferencia Bancaria`    | Mapeo textual (depósito bancario = transferencia) |
| `Contado`, `CONTADO`                 | `Efectivo`                  | Mapeo textual (venta de contado = efectivo) |

> **Paso 0 crítico**: Antes de las migrations, ejecutar las queries de
> diagnóstico de §Fase 0.5 para identificar cualquier variante no
> listada en esta tabla. Si aparecen variantes adicionales, debe
> decidirse su mapeo caso a caso y documentarse en esta tabla.

#### 5.1.3 Diseño N-medios (no asumir cantidad fija)

**Importante**: El catálogo inicial se limita a **4 medios** (§5.1.1)
por decisión de negocio actual, pero el sistema **debe soportar N
medios** sin refactor. Reglas de diseño:

1. **Nunca asumir IDs o cantidad en código**. Todo consumo del catálogo
   debe iterar sobre `MedioDePago::ordenado()->get()`.
2. **Selects en Blade** se construyen desde la query, nunca se
   hardcodean opciones.
3. **Tests** no asumen IDs fijos; usan `MedioDePago::first()` o
   `factory`.
4. **`resolverPorTexto()`** debe seguir funcionando al agregar medios:
   solo añadir la variante al mapping de §5.3 — no requiere tocar código
   de consumidores.
5. **Reportes** agrupan por `medio_pago_id`; un nuevo medio aparece en
   el reporte automáticamente (sin new case/switch).

##### Escenarios de Evolución Soportados

| Escenario | Acción requerida |
|-----------|-------------------|
| Agregar medio (ej. `'Cripto'`, `'Transferencia TDE'`) | Alta via CRUD `Configuracion/MediosDePago` + agregar variante a mapeo de `resolverPorTexto()` |
| Eliminar medio | `activo = 0` (solo-lectura historica); FKs existentes preservadas (no se borra fila) |
| Renombrar medio | Update `nombre` y `nombre_corto`; FKs no cambian, reportes se renombran automaticamente |
| Reordenar | Update `orden`; selects y reportes usan `ordenado()` scope automaticamente |

## 5.2 DryRun del Catálogo Final

#### Nuevo Schema de `tes_medio_de_pagos`:

```sql
CREATE TABLE tes_medio_de_pagos (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100) NOT NULL,
    nombre_corto    VARCHAR(100) DEFAULT '',       -- desde tes_lb_medios
    descripcion     VARCHAR(255) DEFAULT NULL,     -- desde tes_medio_de_pagos
    activo          TINYINT(1) DEFAULT 1,          -- desde tes_medio_de_pagos
    contado         TINYINT(1) DEFAULT 0,          -- desde tes_medio_de_pagos
    codigo_soniar   VARCHAR(50) DEFAULT NULL,      -- desde tes_medio_de_pagos
    es_libro_diario TINYINT(1) DEFAULT 1,          -- si aplica a asientos de libro diario
    es_recaudacion  TINYINT(1) DEFAULT 1,          -- si aplica a módulos de recaudación
    orden           INT DEFAULT 0,                 -- orden de visualización
    created_by      INT UNSIGNED DEFAULT NULL,
    updated_by      INT UNSIGNED DEFAULT NULL,
    deleted_by      INT UNSIGNED DEFAULT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    deleted_at      TIMESTAMP NULL,
    UNIQUE KEY (nombre)
);
```

### 5.2 Migración de Datos

#### Fase 1: Unificar catálogos A y B

1. Crear migration que agregue a `tes_medio_de_pagos`:
   - `nombre_corto` (desde `tes_lb_medios`)
   - `es_libro_diario` (BOOLEAN)
   - `es_recaudacion` (BOOLEAN)
   - `orden` (INT)

2. Insertar en `tes_medio_de_pagos` los registros de `tes_lb_medios`
   que no existan (por nombre coincidente).

3. Migrar FK de `tes_libro_diario.medio_id` a apuntar a
   `tes_medio_de_pagos.id`:
   - Crear columna `tes_libro_diario.nuevo_medio_id` como FK a
     `tes_medio_de_pagos.id`
   - Poblar según correspondencia de nombre
   - Eliminar FK vieja, renombrar columna

4. Deprecar `tes_lb_medios` (no eliminar hasta validación).

#### Fase 2: Migrar texto libre a FK

| Tabla | Acción |
|-------|--------|
| `tes_arrendamientos.medio_de_pago` | Agregar `medio_pago_id`, poblar desde `nombre`, deprecar columna texto |
| `tes_eventuales.medio_de_pago` | Agregar `medio_pago_id`, poblar desde `nombre`, deprecar columna texto |
| `tes_multas_cobradas.forma_pago` | Agregar `medio_pago_id` con el medio principal, migrar formato combinado a tabla puente `tes_multa_medios_pago` si es necesario |
| `tes_cfe_medios_pago.medio_pago_tipo` | Agregar `medio_pago_id`, poblar desde coincidencia de nombre |

#### Fase 3: Normalizar `TesCfeMedioPago`

Convertir `tes_cfe_medios_pago` para que use `medio_pago_id` (FK)
en lugar de `medio_pago_tipo` (VARCHAR). Mantener `medio_pago_valor`.

#### Fase 4: Limpieza

- Eliminar tabla `tes_lb_medios`
- Eliminar tabla `tes_medios_pago_caja` (si no se reactiva)
- Eliminar modelo `LbMedio`, renombrando referencias a `MedioDePago`

### 5.3 Mapeo de Normalización de Nombres

Antes de migrar, se debe establecer un mapeo que resuelva todas las
variantes textuales existentes a uno de los **nombres canónicos** del catálogo unificado
definidos en §5.1.1 (`Efectivo`, `Cheque`, `Transferencia Bancaria`,
`Tarjeta de Débito (POS)`). Este mapeo se usa en las migrations de
poblamiento de FKs.

| Variante(s) en origen (LOWER/TRIM)                                  | Canonico destino             | Notas |
|---------------------------------------------------------------------|------------------------------|-------|
| `"efectivo"`, `"contado"`, `"venta contado"`, `"cash"`              | `Efectivo`                   | Contado = venta en efectivo |
| `"cheque"`                                                          | `Cheque`                     | Único |
| `"transferencia"`, `"transferencia bancaria"`, `"brou"`, `"brou transferencia"`, `"deposito"`, `"depósito"` | `Transferencia Bancaria` | Cualquier transferencia entre cuentas |
| `"pos"`, `"debito"`, `"débito"`, `"tarjeta"`, `"tarjeta de débito"`, `"tarjeta debito"`, `"tde"`, `"tdc"` | `Tarjeta de Débito (POS)` | Tarjeta/crédito POS |
| `"credito"`, `"crédito"`                                            | *(REVISAR)* — probablemente `Tarjeta de Débito (POS)` si era tarjeta, `Cheque`/`Transferencia Bancaria` si era otro medio | Decisión de negocio; ejecutar query §Fase 0.5 para validar ocurrencias |
| `"siif"`                                                            | *(contexto)* ver §3.5.3 — suele combinarse con `Transferencia Bancaria` | Mantener como texto en comentario de auditoría |

> **Notas importantes**:
> 1. Este mapeo debe validarse contra los datos reales en producción
>    ejecutando las queries de diagnóstico de la sección Fase 0.5 antes
>    de comenzar. Si aparecen variantes no listadas, documentarlas aquí.
> 2. **No se permite crear un 5° medio de pago** — toda variante
>    desconocida debe resolverse caso a caso contra los 4 existentes,
>    registrándose la decisión en este mapeo y en logs de la migration.
> 3. La validación con `--fix` del comando `php artisan mediospago:verificar`
>    (ver §9.5) debe usar exactamente este mapeo, no uno distinto.

### 5.4 Scripts de Migración Detallados

A continuación se especifican las migrations de Laravel necesarias,
ordenadas secuencialmente:

#### Migration 1: Expandir `tes_medio_de_pagos`

```php
Schema::table('tes_medio_de_pagos', function (Blueprint $table) {
    $table->string('nombre_corto', 100)->default('')->after('nombre');
    $table->boolean('es_libro_diario')->default(true)->after('codigo_soniar');
    $table->boolean('es_recaudacion')->default(true)->after('es_libro_diario');
    $table->integer('orden')->default(0)->after('es_recaudacion');
});
```

#### Migration 2: Inicializar catálogo final (4 medios iniciales) + mapeo

**Cambio clave**: En lugar de "insertar lo que exista en `tes_lb_medios`",
se **inicializa el catálogo con los 4 medios iniciales** según §5.1.1,
y se documenta cualquier registro histórico que se elimine. La
cantidad (4) es lo definido para el rollout inicial; futuros medios
se agregan via CRUD sin requerir migration.

```php
// PASO 1: Asegurar/actualizar los 4 medios iniciales del catálogo (§5.1.1)
$catalogoFinal = [
    ['nombre' => 'Efectivo',                'nombre_corto' => 'Efectivo',      'contado' => 1, 'orden' => 1],
    ['nombre' => 'Cheque',                  'nombre_corto' => 'Cheque',        'contado' => 0, 'orden' => 2],
    ['nombre' => 'Transferencia Bancaria',  'nombre_corto' => 'Transferencia', 'contado' => 0, 'orden' => 3],
    ['nombre' => 'Tarjeta de Débito (POS)', 'nombre_corto' => 'Débito (POS)',  'contado' => 0, 'orden' => 4],
];

foreach ($catalogoFinal as $cat) {
    DB::table('tes_medio_de_pagos')->updateOrInsert(
        ['nombre' => $cat['nombre']],
        array_merge($cat, [
            'es_libro_diario' => true,
            'es_recaudacion'  => true,
            'activo'          => true,
            'descripcion'     => null, // Ver §5.1.1 para descripciones
        ])
    );
}

// PASO 2: Snapshot de registros históricos a eliminar (para auditoría)
$mapeoVariantes = [
    // [variantes_a_normalizar] => nombre_canónico
    ['efectivo']                                    => 'Efectivo',
    ['cheque']                                       => 'Cheque',
    ['transferencia', 'brou', 'deposito', 'depósito'] => 'Transferencia Bancaria',
    ['pos', 'debito', 'débito', 'tarjeta']           => 'Tarjeta de Débito (POS)',
    ['contado']                                      => 'Efectivo', // venta contado = efectivo
];

// PASO 3: Para cada registro en tes_lb_medios, mapear al catálogo final
//         (NO preservar registros que no matcheen)
$lbMedios = DB::table('tes_lb_medios')->get();
foreach ($lbMedios as $lb) {
    $canonico = $this->normalizarACanonico($lb->nombre, $mapeoVariantes);

    if ($canonico === null) {
        // Registrar como outlier y NO insertar (recopilar para revisión manual)
        Log::warning("LbMedio sin mapeo: id={$lb->id} nombre={$lb->nombre}");
        continue;
    }

    // Solo actualizar `nombre_corto` del registro canonico si difiere
    $canonicoRow = DB::table('tes_medio_de_pagos')->where('nombre', $canonico)->first();
    if ($canonicoRow && empty($canonicoRow->nombre_corto)) {
        DB::table('tes_medio_de_pagos')
            ->where('id', $canonicoRow->id)
            ->update(['nombre_corto' => $lb->nombre_corto]);
    }
}

// PASO 4: Eliminar registros de tes_medio_de_pagos que NO estén en el catálogo final
DB::table('tes_medio_de_pagos')
    ->whereNotIn('nombre', array_column($catalogoFinal, 'nombre'))
    ->delete();
```

Helper de normalización (a ubicar en `MedioPagoService::normalizarACanonico`):

```php
private function normalizarACanonico(string $texto, array $mapeo): ?string
{
    $texto = strtolower(trim($texto));
    foreach ($mapeo as $variantes => $canonico) {
        foreach ($variantes as $v) {
            if ($texto === $v || str_contains($texto, $v)) {
                return $canonico;
            }
        }
    }
    return null;
}
```

> **Critical**: Al final de esta migration, `SELECT COUNT(*) FROM tes_medio_de_pagos`
> debe ser exactamente **4** (los iniciales de §5.1.1). Si hay más,
> abortar y revisar logs de outliers. A futuro, nuevos medios se agregan
> via CRUD (no via migration) y este conteo puede variar.

#### Migration 3: Migrar FK de `tes_libro_diario`

```php
Schema::table('tes_libro_diario', function (Blueprint $table) {
    $table->unsignedBigInteger('nuevo_medio_id')->nullable()->after('medio_id');
});

// Poblar nuevo_medio_id según correspondencia de nombre
DB::statement("
    UPDATE tes_libro_diario ld
    JOIN tes_lb_medios lb ON ld.medio_id = lb.id
    JOIN tes_medio_de_pagos mp ON mp.nombre = lb.nombre
    SET ld.nuevo_medio_id = mp.id
");

// Eliminar FK vieja
Schema::table('tes_libro_diario', function (Blueprint $table) {
    $table->dropForeign(['medio_id']);
    $table->dropColumn('medio_id');
    $table->renameColumn('nuevo_medio_id', 'medio_id');
    $table->foreign('medio_id')->references('id')->on('tes_medio_de_pagos');
});
```

#### Migration 4: Agregar FKs en tablas de texto libre

**Cambio clave**: En lugar de coincidencia exacta (`LOWER(texto) = LOWER(nombre)`),
se usa el mapeo de normalización de §5.3 para resolver variantes históricas
(`"BROU"`, `"DEBITO"`, etc.) a los 4 medios iniciales del catálogo (§5.1.1).

```php
// 1) Agregar columnas medio_pago_id (nullable)
foreach (['tes_arrendamientos', 'tes_eventuales'] as $table) {
    Schema::table($table, function (Blueprint $t) {
        $t->unsignedBigInteger('medio_pago_id')->nullable()->after('medio_de_pago');
    });
}
Schema::table('tes_cfe_medios_pago', function (Blueprint $t) {
    $t->unsignedBigInteger('medio_pago_id')->nullable()->after('medio_pago_tipo');
});
Schema::table('tes_multas_cobradas', function (Blueprint $t) {
    $t->unsignedBigInteger('medio_pago_id')->nullable()->after('forma_pago');
});

// 2) Poblar FKs usando el mapeo de normalización vía PHP
$medios = DB::table('tes_medio_de_pagos')->get()->keyBy(fn($m) => strtolower($m->nombre));
$normalizar = function (string $texto) use ($medios): ?int {
    $texto = strtolower(trim($texto));
    if (empty($texto)) return null;

    // Mapeo exacto primero
    foreach (['efectivo', 'cheque', 'transferencia bancaria', 'tarjeta de débito (pos)'] as $canon) {
        if (isset($medios[$canon]) && $texto === $canon) return $medios[$canon]->id;
    }

    // Mapeo por variantes (ver §5.3)
    if (in_array($texto, ['efectivo', 'contado', 'venta contado', 'cash'])) return $medios['efectivo']->id;
    if ($texto === 'cheque') return $medios['cheque']->id;
    if (in_array($texto, ['transferencia', 'brou', 'deposito', 'depósito', 'siif'])) return $medios['transferencia bancaria']->id;
    if (in_array($texto, ['pos', 'debito', 'débito', 'tarjeta', 'tarjeta de débito', 'credito', 'crédito'])) return $medios['tarjeta de débito (pos)']->id;

    // Fallback: buscar por substring (caso "Transferencia por SIIF", "Cheque NNNN")
    foreach ($medios as $canon => $m) {
        if (str_contains($texto, $canon)) return $m->id;
    }
    // BÚSQUEDA por nombre_corto como último recurso
    foreach ($medios as $m) {
        if (!empty($m->nombre_corto) && str_contains($texto, strtolower($m->nombre_corto))) return $m->id;
    }
    return null; // No matchean: FK queda NULL (reportar como outlier)
};

// 3) Aplicar mapeo a cada tabla
foreach (DB::table('tes_arrendamientos')->whereNotNull('medio_de_pago')->get() as $row) {
    $fk = $normalizar($row->medio_de_pago);
    if ($fk) DB::table('tes_arrendamientos')->where('id', $row->id)->update(['medio_pago_id' => $fk]);
    else Log::warning("tes_arrendamientos id={$row->id} medio_de_pago='{$row->medio_de_pago}' sin mapeo");
}
// Repetir para tes_eventuales, tes_cfe_medios_pago (campo medio_pago_tipo), tes_multas_cobradas (campo forma_pago cuando no sea combinado)
```

> **Resultado esperado**: Al final, el porcentaje de registros con `medio_pago_id IS NULL`
> debe ser 0% (o representar solo registros legacy vacíos/`'SIN DATOS'`).
> Cualquier null se reporta en log para revisión manual.

#### Migration 5: Tabla puente para multas

```sql
CREATE TABLE tes_multa_medios_pago (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    multa_id        BIGINT UNSIGNED NOT NULL,
    medio_pago_id   BIGINT UNSIGNED NOT NULL,
    monto           DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (multa_id) REFERENCES tes_multas_cobradas(id) ON DELETE CASCADE,
    FOREIGN KEY (medio_pago_id) REFERENCES tes_medio_de_pagos(id),
    UNIQUE KEY (multa_id, medio_pago_id)
);
```

#### Migration 6: Poblar `tes_multa_medios_pago` desde `forma_pago`

Esta migration ejecuta un script PHP que parsea el formato
`"EFECTIVO:1000/CHEQUE:2000"` usando la misma lógica de
`MedioPagoService::parsearFormaPago()` e inserta en la tabla puente:

```php
$multas = DB::table('tes_multas_cobradas')->whereNotNull('forma_pago')->get();
foreach ($multas as $multa) {
    $pares = explode('/', $multa->forma_pago);
    foreach ($pares as $par) {
        [$nombre, $monto] = explode(':', $par);
        $mp = DB::table('tes_medio_de_pagos')
            ->where(DB::raw('LOWER(TRIM(nombre))'), strtolower(trim($nombre)))
            ->first();
        if ($mp) {
            DB::table('tes_multa_medios_pago')->insert([
                'multa_id'      => $multa->id,
                'medio_pago_id' => $mp->id,
                'monto'         => $monto,
            ]);
        }
    }
}
```

Luego agregar `medio_pago_id` (medio principal = el de mayor monto) en `tes_multas_cobradas`:

```php
DB::statement("
    UPDATE tes_multas_cobradas mc
    JOIN (
        SELECT multa_id, medio_pago_id
        FROM tes_multa_medios_pagos
        WHERE (multa_id, monto) IN (
            SELECT multa_id, MAX(monto)
            FROM tes_multa_medios_pagos
            GROUP BY multa_id
        )
    ) AS principal ON mc.id = principal.multa_id
    SET mc.medio_pago_id = principal.medio_pago_id
");
```

#### Migration 7: Constraints y deprecación

```php
// Agregar FKs formales donde se pobLaron columnas
Schema::table('tes_arrendamientos', function (Blueprint $t) {
    $t->foreign('medio_pago_id')->references('id')->on('tes_medio_de_pagos');
});
// Repetir para tes_eventuales, tes_cfe_medios_pago, tes_multas_cobradas
```

### 5.3 Cambios en Código por Módulo

> **Premisa general**: Tras la migración, **TODO el código debe usar solo
> los 4 medios iniciales** (§5.1.1). Cualquier string hardcodeado debe
> pasar por `MedioPagoService::resolverPorTexto()` para obtener la FK,
> y los nombres canónicos mostrados en UI/reportes deben venir
> siempre del catálogo (`$medio->nombre`), nunca de literales.

#### Refactor Central: `MedioPagoService::resolverPorTexto()`

Método a agregar en `app/Services/Tesoreria/MedioPagoService.php` que
reemplaza los ~20 strings hardcodeados detectados en §3.5:

```php
/**
 * Resuelve un texto libre (de CFE, PDF, input usuario) al MedioDePago
 * canonico del catalogo. Retorna null si no hay match. Cachea en request.
 * Cachea resultado en memoria por request (evita queries repetidas).
 */
public function resolverPorTexto(?string $texto): ?MedioDePago
{
    if ($texto === null || trim($texto) === '' || strtoupper($texto) === 'SIN DATOS') {
        return null;
    }

    return Cache::remember("mp:resolver:{$texto}", 60, function () use ($texto) {
        $textoLower = mb_strtolower(trim($texto));

        $reglas = [
            'efectivo'                => 'Efectivo',
            'contado'                 => 'Efectivo',
            'cash'                    => 'Efectivo',
            'cheque'                  => 'Cheque',
            'transferencia'           => 'Transferencia Bancaria',
            'brou'                    => 'Transferencia Bancaria',
            'deposito'                => 'Transferencia Bancaria',
            'depósito'                => 'Transferencia Bancaria',
            'siif'                    => 'Transferencia Bancaria',
            'pos'                     => 'Tarjeta de Débito (POS)',
            'debito'                  => 'Tarjeta de Débito (POS)',
            'débito'                  => 'Tarjeta de Débito (POS)',
            'tarjeta'                 => 'Tarjeta de Débito (POS)',
            'credito'                 => 'Tarjeta de Débito (POS)',
            'crédito'                 => 'Tarjeta de Débito (POS)',
        ];

        foreach ($reglas as $variant => $canon) {
            if ($textoLower === $variant || str_contains($textoLower, $variant)) {
                return MedioDePago::where('nombre', $canon)->first();
            }
        }
        return null;
    });
}
```

Todos los strings hardcodeados actuales (`'Transferencia'`, `'TRANSFERENCIA'`,
`'POS'`, `'BROU'`, `'Efectivo'`, `'Cheque'`, etc.) deben reescribirse como:

```php
// ANTES
$medio = 'Transferencia';
// DESPUÉS
$medio = $this->medioPagoService->resolverPorTexto('Transferencia'); // => returns MedioDePago model
$medioId = $medio?->id; // FK o null

// ANTES
if (str_contains($texto, 'Transferencia')) { ... }
// DESPUÉS
$medio = $this->medioPagoService->resolverPorTexto($texto);
if ($medio?->nombre === 'Transferencia Bancaria') { ... }
```

#### Modelos

| Archivo | Cambio |
|---------|--------|
| `app/Models/Tesoreria/MedioDePago.php` | Agregar `nombre_corto`, `es_libro_diario`, `es_recaudacion`, `orden` a `$fillable`; agregar scopes `libroDiario()`, `recaudacion()`, `ordenado()`; casts boolean para flags |
| `app/Models/Tesoreria/LbMedio.php` | **Eliminar** (migrar consumidores a MedioDePago) |
| `app/Models/Tesoreria/LibroDiario.php` | Cambiar `belongsTo(LbMedio::class)` → `belongsTo(MedioDePago::class, 'medio_id')` |
| `app/Models/Tesoreria/TesCfeMedioPago.php` | Agregar `medio_pago_id` y relación `belongsTo(MedioDePago::class)` |
| `app/Models/Tesoreria/Eventual.php` | Cambiar `medio_de_pago` fillable → `medio_pago_id`, agregar relación `medioPago()` |
| `app/Models/Tesoreria/Arrendamiento.php` | Cambiar `medio_de_pago` fillable → `medio_pago_id`, agregar relación `medioPago()` |
| `app/Models/Tesoreria/TesMultasCobradas.php` | Agregar `medio_pago_id` (medio principal), relación `medioPago()` y `mediosPago()` (hasMany via puente); deprecar `forma_pago` |
| `database/seeders/Tesoreria/MedioDePagoSeeder.php` | **Reescribir** para crear los 4 medios iniciales de §5.1.1 (no hardcodear IDs; usar `firstOrCreate` para idempotencia) |
| `database/seeders/LibroDiarioSeeder.php` | Eliminar seed de `tes_lb_medios`; usar `MedioDePago` directamente |

#### Services

| Archivo | Cambio |
|---------|--------|
| `app/Services/Tesoreria/MedioPagoService.php` | **Agregar `resolverPorTexto()` (ver §5.3 Refactor Central)**. `obtenerMediosDisponibles()` retorna solo los 4 activos. `obtenerNombreReal()` puede simplificarse a `$medio->nombre` ya que no habrá variantes tipográficas. |
| `app/Services/Tesoreria/RegistrarAsientosCfeService.php` | `resolverMedioPago()` debe usar `MedioPagoService::resolverPorTexto()` en vez de `LbMedio::all()` + `str_contains` |
| `app/Services/Tesoreria/CfeCreatorService.php` | `createMediosPago()` debe resolver texto a FK via `resolverPorTexto()` y guardar `medio_pago_id` |
| `app/Services/Tesoreria/MultasCobradasService.php` | `parsearMedioPago()` debe retornar FK (`medio_pago_id`); operaciones de escritura deben insertar en `tes_multa_medios_pago` (puente) en lugar de construir string `"EFECTIVO:1000/CHEQUE:2000"` |
| `app/Services/CfeProcessorService.php` | Reemplazar `'SIN DATOS'` por `null` en el output del extractor; consumidores deben usar `tieneMedioPago()` en lugar de comparar string |
| `app/Services/CfeExtractor/BaseExtractor.php` y derivados | Reemplazar主流1 `'SIN DATOS'` por retornar `null` y dejar que consumidor llame a `resolverPorTexto()` |
| `app/Services/Tesoreria/CfeConfirmationService.php` | Reemplazar `=== 'SIN DATOS'` por `tieneMedioPago()` o `is_null($medio_pago_id)` |

#### Componentes Livewire

| Archivo | Cambio |
|---------|--------|
| `app/Http/Livewire/Tesoreria/LibroDiario/LbMedios.php` | **Eliminar** o migrar a `Configuracion/MediosDePago.php` |
| `app/Http/Livewire/Tesoreria/LibroDiario/Index.php` | Cambiar `LbMedio` → `MedioDePago` en queries |
| `app/Http/Livewire/Tesoreria/LibroDiario/Asientos.php` | Cambiar `LbMedio` → `MedioDePago` |
| `app/Http/Livewire/Tesoreria/CargaMasivaHaberes/Index.php` | Cambiar `LbMedio::where('nombre', 'Efectivo')` → `MedioDePago::where('nombre', 'Efectivo')` |
| `app/Http/Livewire/Tesoreria/Arrendamientos/Arrendamiento.php` | Cambiar `$medio_de_pago` (string) a `$medio_pago_id` (int), select con FK |
| `app/Http/Livewire/Tesoreria/Eventuales/Eventuales.php` | Cambiar `$medio_de_pago` (string) a `$medio_pago_id` (int), select con FK |
| `app/Http/Livewire/Tesoreria/MultasCobradas/MultasCobradas.php` | Cambiar `$forma_pago` a `$medio_pago_id`, o mantener ambos |
| `app/Http/Livewire/Tesoreria/Arrendamientos/CargarCfe.php` | Resolver texto a `medio_pago_id` |
| `app/Http/Livewire/Tesoreria/Eventuales/CargarEfactura.php` | `normalizarMedioPago()` debe retornar `medio_pago_id` |
| `app/Http/Livewire/Tesoreria/Prendas/CargarCfe.php` | Ya usa FK, solo validar que consulta coincide con catálogo unificado |
| `app/Http/Livewire/Tesoreria/Arrendamientos/PrintArrendamientos.php` | Cambiar GROUP BY `medio_de_pago` → JOIN con `medio_pago_id` |
| `app/Http/Livewire/Tesoreria/Eventuales/PrintEventuales.php` | Cambiar GROUP BY `medio_de_pago` → JOIN con `medio_pago_id` |

#### Vistas Blade

| Archivo | Cambio |
|---------|--------|
| `resources/views/livewire/tesoreria/arrendamientos/*.blade.php` | Reemplazar `<input>` texto libre por `<select>` con opciones de `MedioDePago` |
| `resources/views/livewire/tesoreria/eventuales/*.blade.php` | Reemplazar `<input>` texto libre por `<select>` con opciones de `MedioDePago` |
| `resources/views/livewire/tesoreria/multas-cobradas/*.blade.php` | Reemplazar `<input>` texto por `<select>` con opciones (mantener texto para combinados) |
| Vistas de libro diario | Cambiar referencias de `LbMedio` a `MedioDePago` |

---

## 6. Implicancias y Riesgos

### 6.1 Impacto en Datos Existentes

| Tabla | Filas aprox | Tipo de cambio |
|-------|-------------|----------------|
| `tes_lb_medios` | ~4 | Migrar a `tes_medio_de_pagos`, mapear FKs |
| `tes_libro_diario` | Probablemente miles | Actualizar FK a nuevo catálogo |
| `tes_cfe_medios_pago` | ~174 | Agregar FK, poblar desde texto |
| `tes_arrendamientos` | ~428 | Agregar FK, poblar desde texto |
| `tes_eventuales` | ~382 | Agregar FK, poblar desde texto |
| `tes_multas_cobradas` | Variable | Formato combinado requiere lógica especial |
| `tes_prendas` | Variable | FK ya existe, solo verificar consistencia |
| `tes_deposito_vehiculos` | Variable | FK ya existe, solo verificar consistencia |

### 6.2 Riesgos

1. **Formato combinado en Multas**: `tes_multas_cobradas.forma_pago`
   permite múltiples medios en un solo registro (`"EFECTIVO:1000/CHEQUE:2000"`).
   La unificación requerirá una tabla puente o conformarse con
   almacenar solo el medio "principal".

2. **Reportes de recaudación**: Los reportes en
   `ResumenRecaudaciones`, `Recaudaciones`, `EstadosRecaudacion`
   agrupan por `medio_pago_tipo` de `TesCfeMedioPago`. Al migrar a
   FK, habrá que agrupar por `medio_pago_id` y JOIN contra el catálogo.

3. **CargaMasivaHaberes**: Busca `LbMedio` con nombre exacto
   "Efectivo". Es frágil pero fácil de migrar.

4. **RegistrarAsientosCfeService**: Usa `LbMedio::all()` y hace
   coincidencia textual con `str_contains`. Al cambiar a
   `MedioDePago` debe mantener el mismo comportamiento de "mejor
   esfuerzo".

5. **CRUD duplicado**: Existen dos CRUDs de medios de pago:
   - `Configuracion/MediosDePago.php` (Livewire, más moderno)
   - `MedioDePagoController.php` (Blade tradicional)
   - `LibroDiario/LbMedios.php` (para libro diario)
   
   Post-unificación debe existir **un solo** CRUD que gestione el
   catálogo completo.

### 6.3 Estrategia de Rollback

Cada fase debe ser reversible de forma independiente. La estrategia general
consiste en mantener columnas viejas como `DEPRECATED` hasta validar la
migración, permitiendo volver atrás sin pérdida de datos.

| Fase | Acción de rollback | Condición para activar |
|------|-------------------|------------------------|
| Schema (Fase 1) | `php artisan migrate:rollback --step=3` y restaurar FKs viejas | Reportes con datos incorrectos o errores en producción |
| Código (Fase 2) | `git revert <commit-hash>` + redeploy | Bug crítico en módulo migrado (arrendamientos, eventuales, etc.) |
| Limpieza (Fase 3) | Restaurar `tes_lb_medios` desde backup y revertir eliminación de modelo | Dependencia no identificada que requiere `LbMedio` |
| Testing (Fase 4) | Ejecutar suite completa y comparar contra baseline pre-migración | Más de 3 tests fallando en módulos financieros |

#### Reglas de Rollback

1. **No eliminar columnas viejas** en Fase 1 — solo deprecarlas (prefijo `deprecated_`).
2. **Mantener modelo `LbMedio`** como wrapper de solo lectura durante Fase 2,
   apuntando internamente a `MedioDePago` vía `getAttribute()`.
3. Cada migration debe tener su correspondiente `down()` que restaure el estado
   anterior con sus datos.
4. Si se detecta un error en producción dentro de las primeras 48h, se ejecuta
   rollback completo (schema + código + datos).

#### Script de Rollback de Emergencia

```bash
# 1. Revertir cambios de código
git checkout <commit-pre-migracion> -- app/Http/Livewire/ app/Models/ app/Services/

# 2. Revertir migrations (ejecutar en orden inverso)
php artisan migrate:rollback --step=7

# 3. Restaurar datos desde backup (si rollback de migration no es suficiente)
php artisan db:restore --backup=backup_pre_unificacion_medios.sql

# 4. Verificar estado
php artisan test --filter=MedioPagoServiceTest
```

### 6.4 Beneficios

- **Integridad referencial**: No más huérfanos ni variantes
  tipográficas
- **Reportes consistentes**: JOIN contra catálogo único
- **Mantenibilidad**: Un solo lugar para agregar/editar/eliminar
  medios de pago
- **Renombrado seguro**: Cambiar "Transferencia" → "Transferencia
  Bancaria" actualiza en todos lados automáticamente
- **Menos código**: Eliminar `LbMedio` y sus acompañantes reduce
  ~300 líneas

### 6.5 Consideraciones de Caché y Rendimiento

La migración introduce JOINs adicionales pero también permite optimizar
con caché del catálogo (que cambia raramente).

| Optimización | Impacto | Implementación |
|--------------|---------|----------------|
| Cachear `MedioDePago::activos()->ordenado()->get()` por 1 hora | Reduce queries del catálogo en reportes | `Cache::remember('medios_pago_activos', 3600, fn() => ...)` |
| Invalidar caché on write en `MedioDePago` observer | Mantiene caché consistente | Observer `saved`/`deleted` → `Cache::forget('medios_pago_activos')` |
| Indexar FKs nuevas | Mejora JOIN en reportes grandes | Ya incluido en migrations de Fase 1 |
| `GROUP BY medio_pago_id` en lugar de `GROUP BY LOWER(texto)` | Reduce CPU en queries de recaudación (174+ filas) | Cambio en `EstadosRecaudacion\Confirmar.php`, `Recaudaciones\Index.php`, `ResumenRecaudaciones\Index.php` |
| Eager loading `with('medioPago')` en listados | Evita N+1 en índices | Aplicar en componentes Livewire de Arrendamientos, Eventuales, Multas |

#### Queries de Rendimiento a Medir (Pre vs Post)

```sql
-- Pre: agrupación por texto (CPU-bound por LOWER/TRIM)
SELECT LOWER(TRIM(medio_pago_tipo)), SUM(medio_pago_valor)
FROM tes_cfe_medios_pago
GROUP BY LOWER(TRIM(medio_pago_tipo));

-- Post: agrupación por FK indexada
SELECT mp.nombre, SUM(cfe.medio_pago_valor)
FROM tes_cfe_medios_pago cfe
JOIN tes_medio_de_pagos mp ON cfe.medio_pago_id = mp.id
GROUP BY cfe.medio_pago_id;
```

Comparar `EXPLAIN` de ambas: se espera reducción de tiempo de ~30-50%
en tablas con miles de registros (recaudaciones).

---

## 7. Plan de Implementación por Fases

### Fase 0 — Análisis y Preparación (Estimación: 2-3 días)
- [ ] Confirmar que `tes_medios_pago_caja` no se usará (o rescatarlo)
- [ ] Backup de BD
- [ ] Verificar volúmenes de datos en cada tabla

### Fase 1 — Migración de Schema (Estimación: 1-2 días)
- [ ] Migration: agregar campos a `tes_medio_de_pagos`
- [ ] Migration: poblar `tes_medio_de_pagos` con datos de `tes_lb_medios`
- [ ] Migration: agregar FK columnas en tablas de texto libre
- [ ] Migration: poblar FKs desde correspondencia de nombres
- [ ] Migration: migrar FK de `tes_libro_diario`
- [ ] Migration: agregar constraints y deprecar columnas viejas

### Fase 2 — Cambios en Código (Estimación: 3-4 días)
- [ ] Refactorizar `MedioDePago` con nuevos campos y scopes
- [ ] Refactorizar `LibroDiario` model → `belongsTo(MedioDePago)`
- [ ] Refactorizar `TesCfeMedioPago` → agregar FK y relación
- [ ] Refactorizar `Eventual` y `Arrendamiento` → cambiar a FK
- [ ] Refactorizar `TesMultasCobradas` → agregar FK (medio principal)
- [ ] Refactorizar componentes Livewire de Arrendamientos, Eventuales,
      MultasCobradas para usar select en lugar de input texto
- [ ] Refactorizar `RegistrarAsientosCfeService` para usar `MedioDePago`
- [ ] Refactorizar `MedioPagoService` si es necesario
- [ ] Refactorizar reportes (`PrintArrendamientos`, `PrintEventuales`,
      `PrintMultasCobradas*`, `ResumenRecaudaciones`)
- [ ] Refactorizar `CargaMasivaHaberes`

### Fase 3 — Limpieza (Estimación: 1 día)
- [ ] Migration: eliminar tabla `tes_lb_medios`
- [ ] Migration: eliminar tabla `tes_medios_pago_caja`
- [ ] Eliminar modelo `LbMedio`
- [ ] Eliminar componente `LbMedios` (CRUD libro diario)
- [ ] Eliminar controlador legacy `MedioDePagoController` si corresponde
- [ ] Consolidar en un solo CRUD (`Configuracion/MediosDePago`)

### Fase 4 — Testing (Estimación: 1-2 días)
- [ ] Ejecutar tests existentes (`php artisan test --filter=MedioPagoServiceTest`)
- [ ] Verificar que todos los selects de medios de pago funcionan
- [ ] Verificar reportes de recaudación
- [ ] Verificar libro diario
- [ ] Verificar módulos: Prendas, Depósito Vehículos, Arrendamientos,
      Eventuales, Multas Cobradas

### Fase 0.5 — Validación Pre-Migración (Estimación: 1 día)

Antes de ejecutar cualquier cambio, se deben ejecutar estas queries de
diagnóstico para conocer el estado real de los datos y detectar
inconsistencias:

#### Diagnóstico de Variantes en Texto Libre

```sql
-- Variantes en arrendamientos
SELECT DISTINCT LOWER(TRIM(medio_de_pago)) AS variante,
       COUNT(*) AS ocurrencias
FROM tes_arrendamientos
WHERE medio_de_pago IS NOT NULL AND medio_de_pago != ''
GROUP BY LOWER(TRIM(medio_de_pago))
ORDER BY ocurrencias DESC;

-- Variantes en eventuales
SELECT DISTINCT LOWER(TRIM(medio_de_pago)) AS variante,
       COUNT(*) AS ocurrencias
FROM tes_eventuales
WHERE medio_de_pago IS NOT NULL AND medio_de_pago != ''
GROUP BY LOWER(TRIM(medio_de_pago))
ORDER BY ocurrencias DESC;

-- Variantes en cfe_medios_pago
SELECT DISTINCT LOWER(TRIM(medio_pago_tipo)) AS variante,
       COUNT(*) AS ocurrencias
FROM tes_cfe_medios_pago
GROUP BY LOWER(TRIM(medio_pago_tipo))
ORDER BY ocurrencias DESC;
```

#### Diagnóstico de Formato Combinado en Multas

```sql
-- Detectar patrones en forma_pago
SELECT forma_pago, COUNT(*) AS ocurrencias
FROM tes_multas_cobradas
WHERE forma_pago IS NOT NULL AND forma_pago != ''
GROUP BY forma_pago
ORDER BY ocurrencias DESC;

-- Detectar registros con múltiples medios (contienen '/')
SELECT COUNT(*) AS combinados,
       SUM(CASE WHEN forma_pago LIKE '%/%' THEN 1 ELSE 0 END) AS multi_medio,
       SUM(CASE WHEN forma_pago NOT LIKE '%/%' OR forma_pago IS NULL THEN 1 ELSE 0 END) AS medio_unico
FROM tes_multas_cobradas;
```

#### Diagnóstico de Huérfanos (FKs inválidas existentes)

```sql
-- Prendas con FK inválida
SELECT COUNT(*) AS prendas_huerfanas
FROM tes_prendas p
LEFT JOIN tes_medio_de_pagos mp ON p.medio_pago_id = mp.id
WHERE mp.id IS NULL AND p.medio_pago_id IS NOT NULL;

-- Depósito vehículos con FK inválida
SELECT COUNT(*) AS depositos_huerfanos
FROM tes_deposito_vehiculos dv
LEFT JOIN tes_medio_de_pagos mp ON dv.medio_pago_id = mp.id
WHERE mp.id IS NULL AND dv.medio_pago_id IS NOT NULL;

-- Libro diario con FK inválida
SELECT COUNT(*) AS ld_huerfanos
FROM tes_libro_diario ld
LEFT JOIN tes_lb_medios lb ON ld.medio_id = lb.id
WHERE lb.id IS NULL AND ld.medio_id IS NOT NULL;
```

#### Diagnóstico de Coincidencia entre Catálogos

```sql
-- Medios que existen en un catálogo pero no en el otro
SELECT lb.nombre AS lb_nombre, mp.nombre AS mp_nombre
FROM tes_lb_medios lb
FULL OUTER JOIN tes_medio_de_pagos mp ON LOWER(TRIM(lb.nombre)) = LOWER(TRIM(mp.nombre))
WHERE lb.id IS NULL OR mp.id IS NULL;
```

> **Output esperado**: Estas queries producen un reporte que determina si el
> mapeo de normalización (sección 5.3) está completo, o si se necesita
> agregar más variantes al mapeo antes de ejecutar las migrations.

### Fase 1 — Migración de Schema (Estimación: 1-2 días)
- [ ] Migration: agregar campos a `tes_medio_de_pagos`
- [ ] Migration: poblar `tes_medio_de_pagos` con datos de `tes_lb_medios`
- [ ] Migration: agregar FK columnas en tablas de texto libre
- [ ] Migration: poblar FKs desde correspondencia de nombres
- [ ] Migration: migrar FK de `tes_libro_diario`
- [ ] Migration: agregar constraints y deprecar columnas viejas

### Fase 1.5 — Validación Post-Migración (Schema)

Después de cada migration de datos, ejecutar estas verificaciones antes de
continuar:

```sql
-- Verificar que no hay registros sin FK (excepto nulls válidos)
SELECT 'arrendamientos' AS tabla, COUNT(*) AS sin_fk
FROM tes_arrendamientos WHERE medio_de_pago IS NOT NULL AND medio_pago_id IS NULL
UNION ALL
SELECT 'eventuales', COUNT(*)
FROM tes_eventuales WHERE medio_de_pago IS NOT NULL AND medio_pago_id IS NULL
UNION ALL
SELECT 'cfe_medios_pago', COUNT(*)
FROM tes_cfe_medios_pago WHERE medio_pago_tipo IS NOT NULL AND medio_pago_id IS NULL
UNION ALL
SELECT 'multas_cobradas', COUNT(*)
FROM tes_multas_cobradas WHERE forma_pago IS NOT NULL AND medio_pago_id IS NULL;
```

```sql
-- Verificar integridad de FKs migradas
SELECT COUNT(*) AS fks_invalidas
FROM tes_libro_diario ld
LEFT JOIN tes_medio_de_pagos mp ON ld.medio_id = mp.id
WHERE mp.id IS NULL;
```

```sql
-- Verificar que la tabla puente de multas tiene los mismos montos totales
SELECT
    (SELECT SUM(monto) FROM tes_multa_medios_pago) AS suma_puente,
    (SELECT SUM(CAST(SUBSTRING_INDEX(forma_pago, ':', -1) AS DECIMAL(12,2)))
     FROM tes_multas_cobradas WHERE forma_pago IS NOT NULL) AS suma_original;
```

### Fase 2 — Cambios en Código (Estimación: 3-4 días)
- [ ] Refactorizar `MedioDePago` con nuevos campos y scopes
- [ ] Refactorizar `LibroDiario` model → `belongsTo(MedioDePago)`
- [ ] Refactorizar `TesCfeMedioPago` → agregar FK y relación
- [ ] Refactorizar `Eventual` y `Arrendamiento` → cambiar a FK
- [ ] Refactorizar `TesMultasCobradas` → agregar FK (medio principal)
- [ ] Refactorizar componentes Livewire de Arrendamientos, Eventuales,
      MultasCobradas para usar select en lugar de input texto
- [ ] Refactorizar `RegistrarAsientosCfeService` para usar `MedioDePago`
- [ ] Refactorizar `MedioPagoService` si es necesario
- [ ] Refactorizar reportes (`PrintArrendamientos`, `PrintEventuales`,
      `PrintMultasCobradas*`, `ResumenRecaudaciones`)
- [ ] Refactorizar `CargaMasivaHaberes`

### Fase 2.5 — Secuencia de Deploy

Para evitar downtime o datos inconsistentes durante el deploy de los cambios
de código, seguir esta secuencia:

| Paso | Acción | Riesgo | Tiempo |
|------|--------|--------|--------|
| 1 | Poner app en modo mantenimiento (`php artisan down`) | Ninguno | 1 min |
| 2 | Ejecutar todas las migrations de Fase 1 + Fase 1.5 | Bajo (solo agregan columnas) | 5 min |
| 3 | Ejecutar validación post-migración (queries sección 1.5) | Medio (detecta errores) | 2 min |
| 4 | Deployar nuevo código (Fase 2) | Medio (cambios en modelos y components) | 2 min |
| 5 | Limpiar cache (`php artisan optimize:clear`) | Bajo | 1 min |
| 6 | Verificar funcionalidad básica (1 request por módulo) | Medio | 5 min |
| 7 | Sacar app de modo mantenimiento (`php artisan up`) | Ninguno | 1 min |
| 8 | Monitorear logs por 30 min (errores 500, queries lentas) | Bajo | 30 min |

**Rollback durante deploy**: Si algún paso ≥3 falla, revertir migrations
(`php artisan migrate:rollback`), revertir código (`git revert`), y repetir
desde paso 1.

### Fase 3 — Limpieza (Estimación: 1 día)
- [ ] Migration: eliminar tabla `tes_lb_medios`
- [ ] Migration: eliminar tabla `tes_medios_pago_caja`
- [ ] Eliminar modelo `LbMedio`
- [ ] Eliminar componente `LbMedios` (CRUD libro diario)
- [ ] Eliminar controlador legacy `MedioDePagoController` si corresponde
- [ ] Consolidar en un solo CRUD (`Configuracion/MediosDePago`)

### Fase 4 — Testing (Estimación: 1-2 días)
- [ ] Ejecutar tests existentes (`php artisan test --filter=MedioPagoServiceTest`)
- [ ] Verificar que todos los selects de medios de pago funcionan
- [ ] Verificar reportes de recaudación
- [ ] Verificar libro diario
- [ ] Verificar módulos: Prendas, Depósito Vehículos, Arrendamientos,
      Eventuales, Multas Cobradas

### Fase 4.5 — Monitoreo Post-Migración (Estimación: 3 días hábiles)

Durante los primeros 3 días hábiles posterior al deploy, ejecutar este
checklist diariamente:

#### Día 1 Post-Deploy
- [ ] Revisar logs de errores (`storage/logs/laravel.log`) en busca de:
  - `Column not found` (referencias a columnas viejas)
  - `Class "LbMedio" not found`
  - `Undefined property: medio_de_pago`
- [ ] Verificar que todos los reportes de recaudación cargan correctamente
- [ ] Confirmar que el libro diario muestra asientos con nombres de medios
- [ ] Ejecutar query de verificación de FKs nulas:
  ```sql
  SELECT COUNT(*) AS con_texto_sin_fk FROM tes_arrendamientos
  WHERE medio_de_pago IS NOT NULL AND medio_pago_id IS NULL;
  ```

#### Día 2 Post-Deploy
- [ ] Generar reporte de recaudación y comparar contra el mismo período
      pre-migración (los totales deben coincidir)
- [ ] Verificar CRUD unificado de medios de pago (crear, editar, eliminar)
- [ ] Probar creación de nuevo CFE con medios de pago

#### Día 3 Post-Deploy
- [ ] Ejecutar limpieza de columnas deprecadas si no hay errores
- [ ] Backup final del schema migrado
- [ ] Cerrar issue/ticket de unificación

**Estimación total: 8-12 días hábiles.**

---

## 8. Alternativa Reducida (Mínimo Impacto)

Si el riesgo/tiempo es prohibitivo, se puede implementar una
**unificación parcial**:

1. **Solo** unificar `LbMedio` + `MedioDePago` (Fase 1 + Fase 2
   parcial limitada a Libro Diario)
2. Dejar texto libre en Arrendamientos, Eventuales y Multas
3. Agregar FK a `tes_cfe_medios_pago` (migración de datos sencilla)

Esto resuelve el 80% de los problemas de integridad con ~40% del
esfuerzo.

---

## 9. Estrategia de Testing Automatizado

La cobertura actual de tests para operaciones de medios de pago es baja
(según §Deuda Técnica de CLAUDE.md). La migración es una oportunidad para
añadir tests de regresión que blindan el refactor.

### 9.1 Tests Unitarios a Crear

| Test | Archivo sugerido | Casos a cubrir |
|------|------------------|----------------|
| `MedioDePagoModelTest` | `tests/Unit/Models/MedioDePagoTest.php` | scopes `activos()`, `ordenado()`, `libroDiario()`, `recaudacion()`; soft delete; mutators |
| `MedioPagoServiceTest` (extender) | `tests/Unit/Services/MedioPagoServiceTest.php` | `resolverPorTexto('EFECTIVO')`, `resolverPorTexto('Brou')`, `resolverPorTexto('SIN DATOS')`, `resolverPorTexto('')`, retorna `null` para desconocido |
| `MedioPagoMigrationTest` | `tests/Feature/MedioPagoMigrationTest.php` | Backfill de FKs coincide 100% con texto original; total de registros preservado |
| `MultasMediosPagoPuenteTest` | `tests/Feature/MultasMediosPagoPuenteTest.php` | `SUM(monto)` puente == `SUM(parseado(forma_pago))`; todos los registros con `forma_pago` tienen filas en la puente |
| `RegistrarAsientosCfeServiceTest` | `tests/Feature/RegistrarAsientosCfeServiceTest.php` | Resolver texto "Transferencia" → FK correcta; fallback null mantenido |

### 9.2 Tests de Feature (Browser/HTTP)

| Test | Cubre |
|------|-------|
| `CrearArrendamientoWithFKTest` | POST a store, assert `medio_pago_id` persistido, texto NO usado |
| `CrearEventualWithFKTest` | Ídem para eventuales |
| `CrearMultaWithMultipleMediosTest` | Forma combinada: 2 inserts en puente + 1 medio principal |
| `ReporteRecaudacionesFkTest` | Reporte agrupa por `medio_pago_id`, totales idénticos al reporte legacy |
| `CargarCfeExtractionTest` | Cargar XML/PDF CFE con texto → FK poblada automáticamente |
| `LibroDiarioAsientoTest` | Crear asiento con `medio_id` que apunta a `tes_medio_de_pagos.id` |

### 9.3 Tests de Regresión de Reportes (Snapshot)

Para evitar que la migración altere reportes históricos, antes y después
de la migración ejecutar:

```bash
# Generar baseline de reportes (pre-migración)
php artisan test --filter=ReportesRecaudacionRegressionTest

# Script de comparación de snapshots
php artisan reportes:comparar-snapshots \
    --desde=2024-01-01 --hasta=2025-12-31 \
    --output=tests/snapshots/post-migracion.diff
```

Tests sugeridos con `UploadedFile::fake()` + snapshot JSON:
- `PrintArrendamientosTest`
- `PrintEventualesTest`
- `PrintMultasCobradasFullTest`
- `PrintMultasCobradasResumenTest`
- `ResumenRecaudacionesTest`

### 9.4 Test de Rollback (Smoke Test)

```php
public function test_rollback_completo_no_pierde_datos()
{
    $this->artisan('migrate', ['--path' => 'database/migrations/unificacion_medios']);
    $this->artisan('migrate:rollback', ['--step' => 7]);

    // Verificar que el schema volvió al estado original
    $this->assertTrue(Schema::hasTable('tes_lb_medios'));
    $this->assertFalse(Schema::hasColumn('tes_arrendamientos', 'medio_pago_id'));

    // Verificar que los FKs viejos siguen funcionando
    $asiento = LibroDiario::factory()->create(['medio_id' => 1]);
    $this->assertNotNull($asiento->medio);
}
```

### 9.5 Comando de Validación Post-Deploy

Se sugiere un comando Artisan consolidado que ejecute todas las
verificaciones:

```bash
php artisan mediospago:verificar {--fix}
```

Acciones:
1. Contar registros con `medio_pago_id IS NULL` en cada tabla migrada
2. Reportar discrepancias entre texto y FK
3. Con `--fix`: poblar FKs faltantes usando el mapeo de §5.3
4. Reportar montos totales pre/post para reportes de recaudación
5. Generar log en `storage/logs/verificacion_medios_pago_YYYYMMDD.log`

Crear en `app/Console/Commands/VerificarMediosPagoCommand.php`.

---

## 10. Mejoras de Diseño Sugeridas (OutOfScope pero recomendadas)

Estas mejoras no son parte de la unificación pero deberían documentarse
como seguimiento posterior:

1. **Histórico de renombrados**: Tabla `tes_medio_de_pagos_historial`
   que registre cambios de `nombre` para auditar reportes históricos.

2. **Códigos externos estables**: Agregar columna `codigo_externo`
   (VARCHAR 50 UNIQUE) inmutable para integraciones con sistemas
   externos (BPS, BCU, SIIF) — los IDs pueden cambiar con migración
   pero el código permanece.

3. **Herencia de medios**: `es_libro_diario` y `es_recaudacion` como
   flags es una solución pobre; considerar tabla pivote
   `tes_medio_pago_contextos` para soportar N contextos futuros.

4. **Normalización bidireccional**: `MedioPagoService::resolverPorTexto()`
   debería cachearse en memoria por request para evitar queries
   repetidas durante carga masiva de CFEs.

5. **Auditoría de migración**: Cada migration de datos debería insertar
   log en `auditoria` con el snapshot pre-migración por tabla
   afectada, de forma que el cambio sea trazable en el sistema de
   auditoría existente.

---

## 11. Checklist de Aceptación Final

Antes de cerrar la migración como "completa", **TODOS** los ítems deben
estar verificados:

### 11.1 Integridad de Datos

- [ ] **`SELECT COUNT(*) FROM tes_medio_de_pagos` == 4** (estado inicial post-migración — los 4 de §5.1.1). A futuro este número puede variar via CRUD.
- [ ] `SELECT nombre FROM tes_medio_de_pagos WHERE activo=1 ORDER BY orden` retorna inicialmente: `Efectivo`, `Cheque`, `Transferencia Bancaria`, `Tarjeta de Débito (POS)`
- [ ] `SELECT nombre_corto FROM tes_medio_de_pagos ORDER BY orden` retorna inicialmente: `Efectivo`, `Cheque`, `Transferencia`, `Débito (POS)`
- [ ] `tes_medio_de_pagos.descripcion` poblada para los 4 registros
- [ ] `tes_medio_de_pagos.contado == 1` solo para `Efectivo`, `== 0` para los otros 3
- [ ] `tes_medio_de_pagos.es_libro_diario == 1` y `es_recaudacion == 1` para los 4
- [ ] No existen registros con `medio_pago_id IS NULL` Y texto NO nulo en `tes_arrendamientos`, `tes_eventuales`, `tes_cfe_medios_pago`, `tes_multas_cobradas`
- [ ] FKs y Constraints presentes en todas las tablas migradas
- [ ] `(SELECT COUNT(*) FROM tes_multa_medios_pago) >= (SELECT COUNT(*) FROM tes_multas_cobradas WHERE forma_pago LIKE '%/%')`
- [ ] `(SELECT SUM(monto) FROM tes_multa_medios_pago) == (SELECT SUM(parsear(forma_pago)) FROM tes_multas_cobradas)`
- [ ] Backup final del schema migrado generado y etiquetado
- [ ] Logs de outliers (variantes sin mapeo) en `storage/logs/laravel.log` revisados y resueltos

### 11.2 Código

- [ ] No existen referencias a la clase `LbMedio` en `app/` (verificar con: `grep -r "LbMedio" app/` retorna 0)
- [ ] No existen strings `'tes_lb_medios'` en `app/` salvo en migrations históricas
- [ ] No existen strings `'SIN DATOS'` en lógica de negocio (solo permitido como centinela legacy en lecturas)
- [ ] `MedioPagoService::resolverPorTexto()` implementado y reemplaza 6+ usos de `where like` y `stripos`
- [ ] Todos los componentes Livewire de ABM usan `<select>` con FK en lugar de `<input>` texto
- [ ] Reportes usan `JOIN` con FK y `GROUP BY medio_pago_id`
- [ ] **No existen strings hardcodeados** `'Transferencia'`, `'TRANSFERENCIA'`, `'POS'`, `'Efectivo'`, `'Cheque'`, `'Débito'` en lógica comparativa de `app/` (verificar con `grep -riE "'(transferencia|pos|efectivo|cheque|debito|credito|brou|siif)'" app/` retorna 0)
- [ ] Strings como `'Transferencia Bancaria'` y `'Tarjeta de Débito (POS)'` solo aparecen en el seeder y en `resolverPorTexto()` (mapping), nunca en comparaciones directas

### 11.3 Performance

- [ ] Queries de recaudación (174+ filas) tardan igual o menos que pre-migración (ver §6.5)
- [ ] No se detectan queries N+1 en listados de Arrendamientos/Eventuales/Multas (usar Laravel Telescope o Debugbar)
- [ ] Caché de `medios_pago_activos` implementada y se invalida al editar catálogo

### 11.4 Testing

- [ ] `php artisan test --filter=MedioPago` pasa al 100%
- [ ] `php artisan test` pasa al 100% (regresión total)
- [ ] Tests de snapshot de reportes no muestran diferencias
- [ ] `php artisan mediospago:verificar` reporta 0 discrepancias

### 11.5 Operacionales

- [ ] After 3 días hábiles post-deploy: cero errores `Column not found` en `storage/logs/laravel.log`
- [ ] After 3 días: usuarios de negocio firman que reportes de recaudación coinciden con períodos previos
- [ ] After 3 días: CRUD unificado de `Configuracion/MediosDePago` es funcional para crear/editar/eliminar
- [ ] Issue/ticket de unificación cerrado con enlace a este documento y al commit de cierre

### 11.6 Limpieza Final (post 1 semana estable en prod)

- [ ] Eliminar columnas deprecadas (`medio_de_pago` texto en arrendamientos, eventuales; `forma_pago` texto en multas)
- [ ] Eliminar tabla `tes_lb_medios`
- [ ] Eliminar tabla `tes_medios_pago_caja`
- [ ] Limpiar seeders `LbMedio` y `LibroDiarioSeeder` referencias a `tes_lb_medios`
- [ ] Actualizar `docs/INDICE_APLICACION.md` y este plan con fecha de cierre
