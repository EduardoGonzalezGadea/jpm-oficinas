# Documentación de la Sección de Prendas

## Descripción General

El módulo de **Prendas** es una sección del sistema de Tesorería que permite gestionar el registro, seguimiento y control de prendas (objetos值ados o empeñados). Este módulo está diseñado para municipales o instituciones que necesitan llevar un control de objetos dejados en garantía por ciudadanos.

### Propósito del Módulo

El módulo de Prendas permite:
- **Registrar prendas** con información detallada del titular, medio de pago y concepto
- **Organizar prendas en planillas** para su gestión y control
- **Generar reportes** con filtros avanzados
- **Imprimir planillas** y recibos de prendas
- **Anular planillas** cuando sea necesario

---

## Arquitectura del Módulo

### Estructura de Archivos

```
app/
├── Models/
│   └── Tesoreria/
│       ├── Prenda.php           # Modelo principal de prendas
│       └── PrendaPlanilla.php    # Modelo de planillas de prendas
└── Http/
    └── Livewire/
        └── Tesoreria/
            └── Prendas/
                ├── Index.php                    # Lista principal de prendas
                ├── Create.php                   # Formulario de creación
                ├── Edit.php                     # Formulario de edición
                ├── Show.php                     # Visualización de detalle
                ├── PrendasReporte.php            # Reportes avanzados
                ├── PrintPrendasAdvanced.php      # Impresión de reportes
                └── Planillas/
                    ├── Index.php                # Lista de planillas
                    └── Show.php                 # Visualización de planilla

resources/views/
└── livewire/
    └── tesoreria/
        └── prendas/
            ├── index.blade.php
            ├── create.blade.php
            ├── edit.blade.php
            ├── show.blade.php
            ├── prendas-reporte.blade.php
            ├── print-prendas-advanced.blade.php
            └── planillas/
                ├── index.blade.php
                └── show.blade.php
```

---

## Modelos

### Prenda.php

Este modelo representa una **prenda individual** registrada en el sistema. Una prenda es un objeto dado en garantía por un ciudadano.

#### Tabla en BD
- **Nombre**: `tes_prendas`
- **Propósito**: Almacena los registros de prendas

#### Atributos del Modelo

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int | Identificador único de la prenda |
| `planilla_id` | int | FK a planilla (nullable - si está null, la prenda no está asignada a ninguna planilla) |
| `recibo_serie` | string | Serie del recibo (ej: "A", "B") |
| `recibo_numero` | string | Número del recibo |
| `recibo_fecha` | date | Fecha de emisión del recibo |
| `orden_cobro` | string | Orden de cobro asociada |
| `titular_nombre` | string | Nombre del titular de la prenda |
| `titular_cedula` | string | Cédula de identidad del titular |
| `titular_telefono` | string | Teléfono de contacto del titular |
| `medio_pago_id` | int | FK al medio de pago utilizado |
| `monto` | decimal | Monto de la prenda |
| `concepto` | string | Descripción del objeto empeñado |
| `transferencia` | string | Número de transferencia (si aplica) |
| `transferencia_fecha` | date | Fecha de la transferencia |
| `created_by` | int | Usuario que creó el registro |
| `updated_by` | int | Usuario que último modificó el registro |
| `deleted_by` | int | Usuario que eliminó el registro (soft delete) |

#### Relaciones

```php
// Relación con MedioDePago - Una prenda tiene un medio de pago
public function medioPago()
{
    return $this->belongsTo(MedioDePago::class, 'medio_pago_id');
}

// Relación con PrendaPlanilla - Una prenda pertenece a una planilla (nullable)
public function planilla()
{
    return $this->belongsTo(PrendaPlanilla::class, 'planilla_id');
}

// Relación con Usuario (creador)
public function createdBy()
{
    return $this->belongsTo(User::class, 'created_by');
}

// Relación con Usuario (último editor)
public function updatedBy()
{
    return $this->belongsTo(User::class, 'updated_by');
}

// Relación con Usuario (eliminador)
public function deletedBy()
{
    return $this->belongsTo(User::class, 'deleted_by');
}
```

#### Atributos Computados

```php
// Formatea el monto como moneda (ej: 1.500,00)
public function getMontoFormateadoAttribute()
{
    return number_format($this->monto, 2, ',', '.');
}
```

#### Características Especiales

1. **Conversión a Mayúsculas**: Los campos definidos en `$uppercaseFields` se convierten automáticamente a mayúsculas al guardar:
   - `recibo_serie`
   - `recibo_numero`
   - `orden_cobro`
   - `titular_nombre`
   - `titular_cedula`
   - `concepto`
   - `transferencia`

2. **Soft Deletes**: El modelo usa SoftDeletes para mantener trazabilidad de eliminaciones.

3. **Auditoría Automática**: Los métodos `boot()` configuran automáticamente:
   - `created_by` y `updated_by` al crear/actualizar
   - `deleted_by` al eliminar (soft delete)

---

### PrendaPlanilla.php

Este modelo representa una **planilla de prendas**. Una planilla es un documento que agrupa varias prendas para su control y gestión.

#### Tabla en BD
- **Nombre**: `tes_prendas_planillas`
- **Propósito**: Almacena las planillas que agrupan prendas

#### Atributos del Modelo

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int | Identificador único de la planilla |
| `fecha` | date | Fecha de creación de la planilla |
| `numero` | string | Número único de planilla (formato: YYYY-MM-DD-N) |
| `anulada_fecha` | datetime | Fecha de anulación (null si está activa) |
| `anulada_user_id` | int | Usuario que anuló la planilla |
| `created_by` | int | Usuario que creó la planilla |
| `updated_by` | int | Usuario que último modificó la planilla |

#### Generación Automática de Número

El método `generarNumero()` genera automáticamente el número de planilla con el formato: `YYYY-MM-DD-N`

Donde:
- `YYYY-MM-DD`: Fecha de la planilla
- `N`: Número secuencial del día (1, 2, 3...)

**Ejemplo**: `2024-01-15-3` = 3ra planilla del 15 de enero de 2024

```php
public static function generarNumero($fecha)
{
    // Obtiene la fecha formateada
    $fechaStr = \Carbon\Carbon::parse($fecha)->format('Y-m-d');
    
    // Busca la última planilla del día
    $ultimaPlanilla = static::where('numero', 'like', $fechaStr . '-%')
        ->orderBy('numero', 'desc')
        ->first();
    
    // Incrementa el secuencial o inicia en 1
    if ($ultimaPlanilla) {
        $parts = explode('-', $ultimaPlanilla->numero);
        $nextSequential = (int) end($parts) + 1;
    } else {
        $nextSequential = 1;
    }
    
    return $fechaStr . '-' . $nextSequential;
}
```

#### Relaciones

```php
// Relación con Prenda - Una planilla tiene muchas prendas
public function prendas()
{
    return $this->hasMany(Prenda::class, 'planilla_id');
}

// Relación con Usuario (anulador)
public function anuladaPor()
{
    return $this->belongsTo(User::class, 'anulada_user_id');
}

// Relación con Usuario (creador)
public function createdBy()
{
    return $this->belongsTo(User::class, 'created_by');
}

// Relación con Usuario (editor)
public function updatedBy()
{
    return $this->belongsTo(User::class, 'updated_by');
}
```

#### Métodos de Gestión

```php
// Verifica si la planilla está anulada
public function isAnulada()
{
    return !is_null($this->anulada_fecha);
}

// Anula la planilla actual
public function anular()
{
    $this->anulada_fecha = now();
    $this->anulada_user_id = Auth::id();
    $this->save();
    
    // Libera todas las prendas asociadas (les quita la planilla)
    $this->prendas()->update(['planilla_id' => null]);
}
```

#### Atributos Computados

```php
// Calcula el total de montos de todas las prendas en la planilla
public function getTotalAttribute()
{
    return $this->prendas->sum('monto');
}

// Formatea el total como moneda
public function getTotalFormateadoAttribute()
{
    return number_format($this->total, 2, ',', '.');
}
```

---

## Componentes Livewire

### Index.php - Lista de Prendas

Este componente muestra la **lista principal de prendas** con funcionalidades de búsqueda, filtrado por año y selección múltiple para crear planillas.

#### Propiedades Públicas

| Propiedad | Tipo | Descripción |
|-----------|------|-------------|
| `$search` | string | Texto de búsqueda |
| `$selectedYear` | int | Año seleccionado para filtrar |
| `$years` | array | Años disponibles (se extraen de los registros) |
| `$selectedPrendas` | array | IDs de prendas seleccionadas |
| `$selectAll` | bool | Indica si están todas seleccionadas |

#### Métodos Principales

```php
// mount(): Inicializa el componente
// - Obtiene los años disponibles de los recibos
// - Agrega el año actual si no existe
// - Selecciona el año actual por defecto

// confirmDelete($id): Solicita confirmación para eliminar
public function confirmDelete($id)

// delete($id): Elimina una prenda
public function delete($id)

// createPlanilla(): Crea una nueva planilla con las prendas seleccionadas
public function createPlanilla()

// updatedSelectAll($value): Maneja el seleccionar/deseleccionar todas
public function updatedSelectAll($value)

// render(): Retorna la vista con las prendas filtradas
```

#### Flujo de Búsqueda

El método `render()` aplica los siguientes filtros:
1. Filtra por año de `recibo_fecha`
2. Aplica búsqueda en campos: `titular_nombre`, `titular_cedula`, `recibo_numero`, `orden_cobro`, `transferencia`
3. Ordena por `recibo_fecha` descendente
4. Pagina los resultados (10 por página)

---

### Create.php - Nueva Prenda

Este componente maneja el **formulario de registro de nuevas prendas**.

#### Propiedades Públicas (Campos del Formulario)

| Propiedad | Tipo | Descripción |
|-----------|------|-------------|
| `$recibo_serie` | string | Serie del recibo |
| `$recibo_numero` | string | Número del recibo |
| `$recibo_fecha` | date | Fecha del recibo |
| `$orden_cobro` | string | Orden de cobro |
| `$titular_nombre` | string | Nombre del titular |
| `$titular_cedula` | string | Cédula del titular |
| `$titular_telefono` | string | Teléfono del titular |
| `$medio_pago_id` | int | Medio de pago seleccionado |
| `$monto` | decimal | Monto de la prenda |
| `$concepto` | string | Descripción del objeto |
| `$transferencia` | string | Número de transferencia |
| `$transferencia_fecha` | date | Fecha de transferencia |

#### Validaciones

```php
protected $rules = [
    'recibo_serie' => 'required|string|max:255',
    'recibo_numero' => 'required|string|max:255',
    'recibo_fecha' => 'required|date',
    'orden_cobro' => 'required|string|max:255',
    'titular_nombre' => 'required|string|max:255',
    'titular_cedula' => 'nullable|string|max:255',
    'titular_telefono' => 'nullable|string|max:255',
    'medio_pago_id' => 'required|exists:tes_medio_de_pagos,id',
    'monto' => 'required|numeric|min:0',
    'concepto' => 'required|string|max:255',
    'transferencia' => 'nullable|string|max:255',
    'transferencia_fecha' => 'nullable|date',
];
```

#### Flujo de Creación

1. **showCreateModal()**: Abre el modal de creación y limpia los campos
2. **updatedTransferencia()**: Detecta transferencias duplicadas en tiempo real
3. **store()**: Valida los datos y verifica unicidad
4. **confirmStore()**: Crea el registro en la base de datos

#### Validaciones de Unicidad

- **Recibo**: La combinación `recibo_serie` + `recibo_numero` debe ser única
- **Transferencia**: Si se ingresa una transferencia, verifica que no exista otra prenda con el mismo número

---

### Edit.php - Editar Prenda

Este componente maneja la **edición de prendas existentes**. Es muy similar a Create.php pero con validaciones adicionales para excluir el registro actual.

#### Propiedades Públicas

Iguales que Create.php, más:
| Propiedad | Tipo | Descripción |
|-----------|------|-------------|
| `$prenda_id` | int | ID de la prenda que se está editando |

#### Validación de Unicidad (Diferencia con Create)

```php
// Al editar, se excluye el registro actual de la búsqueda
$existsRecibo = Prenda::where('recibo_serie', $this->recibo_serie)
    ->where('recibo_numero', $this->recibo_numero)
    ->where('id', '!=', $this->prenda_id)  // ← Excluye el registro actual
    ->exists();
```

---

### Show.php - Ver Detalle

Este componente muestra los **detalles completos de una prenda**.

#### Propiedades

| Propiedad | Tipo | Descripción |
|-----------|------|-------------|
| `$prenda` | Prenda | Instancia del modelo con relaciones cargadas |

#### Carga de Relaciones

```php
$this->prenda = Prenda::with([
    'medioPago',      // Carga el medio de pago
    'createdBy',      // Carga el usuario creador
    'updatedBy',      // Carga el último editor
    'deletedBy'       // Carga el usuario que eliminó (si aplica)
])->find($id);
```

---

### PrendasReporte.php - Reportes Avanzados

Este componente permite **generar reportes de prendas con filtros avanzados**.

#### Propiedades

| Propiedad | Tipo | Descripción |
|-----------|------|-------------|
| `$filters` | array | Arreglo con los filtros aplicados |
| `$resultados` | Collection | Resultados de la búsqueda |

#### Filtros Disponibles

```php
$this->filters = [
    'mes' => '',              // Mes (1-12)
    'year' => date('Y'),      // Año
    'titular_nombre' => '',   // Nombre del titular (búsqueda flexible)
    'titular_cedula' => '',   // Cédula del titular
    'recibo_numero' => '',    // Número de recibo
    'orden_cobro' => '',      // Orden de cobro
    'fecha_desde' => '',      // Fecha desde (override de mes/year)
    'fecha_hasta' => '',      // Fecha hasta (override de mes/year)
    'concepto' => '',         // Concepto (búsqueda flexible)
    'medio_pago_id' => '',    // ID del medio de pago
];
```

#### Búsqueda Flexible (Normalización)

El método `applyFlexibleSearch()` normaliza el texto para búsquedas:
- Convierte a minúsculas
- Elimina acentos (á → a, é → e, etc.)

```php
protected function applyFlexibleSearch($query, $column, $value)
{
    $normalized = '%' . $this->normalizeForSearch($value) . '%';
    $query->whereRaw("LOWER(REPLACE(REPLACE(...))) LIKE ?", [$normalized]);
}
```

#### Lógica de Fechas

- Si se usan `fecha_desde` y `fecha_hasta`, se ignoran `mes` y `year`
- Si no hay rango de fechas, se usa `mes` y `year` para filtrar

---

### PrintPrendasAdvanced.php - Impresión de Reportes

Este componente hereda de `BaseReportComponent` y se encarga de **generar la vista impresa del reporte**.

#### Propiedades

| Propiedad | Tipo | Descripción |
|-----------|------|-------------|
| `$registros` | Collection | Registros a imprimir |
| `$total` | decimal | Suma total de montos |
| `$titulo` | string | Título del reporte |

#### Flujo de Impresión

1. `setupData()`: Aplica los filtros y obtiene los registros
2. `getViewName()`: Retorna la vista a renderizar
3. La vista Blade genera el PDF/HTML para impresión

---

### Planillas/Index.php - Lista de Planillas

Este componente muestra el **listado de todas las planillas creadas**.

#### Propiedades

| Propiedad | Tipo | Descripción |
|-----------|------|-------------|
| `$search` | string | Texto de búsqueda (busca en número de planilla) |

#### Métodos Principales

```php
// confirmAnular($id): Solicita confirmación para anular
public function confirmAnular($id)

// anularPlanilla($id): Anula la planilla
public function anularPlanilla($id)
```

#### Flujo de Anulación

1. Verifica que la planilla exista
2. Verifica que no esté ya anulada
3. Ejecuta `$planilla->anular()` (ver modelo PrendaPlanilla)
4. Muestra mensaje de éxito

---

### Planillas/Show.php - Ver Planilla

Este componente muestra los **detalles de una planilla específica** con todas sus prendas.

#### Propiedades

| Propiedad | Tipo | Descripción |
|-----------|------|-------------|
| `$planilla` | PrendaPlanilla | Planilla con relaciones cargadas |
| `$planillaId` | int | ID de la planilla |

#### Carga de Relaciones

```php
$this->planilla = PrendaPlanilla::with([
    'prendas.medioPago',  // Prendas con su medio de pago
    'createdBy',          // Usuario creador
    'anuladaPor'          // Usuario anulador (si aplica)
])->findOrFail($id);
```

#### Generación de PDF

```php
public function generarPDF()
{
    return redirect()->route('tesoreria.prendas.planillas.pdf', $this->planillaId);
}
```

---

## Flujo de Trabajo Típico

### 1. Registrar una Prenda

```
1. Usuario entra a: Tesorería → Prendas
2. Clic en "Nueva Prenda"
3. Completa el formulario:
   - Serie y número de recibo
   - Fecha del recibo
   - Orden de cobro
   - Datos del titular (nombre, cédula, teléfono)
   - Medio de pago
   - Monto
   - Concepto (descripción del objeto)
   - Datos de transferencia (si aplica)
4. Clic en "Guardar"
5. El sistema valida unicidad y guarda el registro
```

### 2. Crear una Planilla

```
1. Usuario entra a: Tesorería → Prendas
2. Filtra por año si es necesario
3. Busca las prendas a agrupar
4. Selecciona las prendas (checkboxes)
5. Clic en "Crear Planilla"
6. El sistema:
   - Crea una nueva planilla con número automático
   - Asigna las prendas seleccionadas a la planilla
   - Muestra el número de la planilla creada
```

### 3. Ver/Anular una Planilla

```
1. Usuario entra a: Tesorería → Prendas → Planillas
2. Busca la planilla por número
3. Clic en "Ver" para ver los detalles
4. Si necesita anular:
   - Clic en "Anular"
   - Confirma la acción
   - La planilla queda anulada y las prendas liberadas
```

### 4. Generar Reporte

```
1. Usuario entra a: Tesorería → Prendas → Reportes
2. Aplica filtros:
   - Por período (mes/year o rango de fechas)
   - Por titular (nombre o cédula)
   - Por número de recibo
   - Por concepto
   - Por medio de pago
3. Clic en "Buscar"
4. Visualiza los resultados
5. Clic en "Imprimir" para generar PDF
```

---

## Consideraciones Técnicas

### Rutas del Módulo

```php
// Rutas principales de prendas
'tesoreria.prendas.index'        → Lista de prendas
'tesoreria.prendas.store'        → Crear prenda
'tesoreria.prendas.update'       → Actualizar prenda
'tesoreria.prendas.destroy'      → Eliminar prenda
'tesoreria.prendas.reportes'     → Vista de reportes
'tesoreria.prendas.imprimir-avanzado' → Impresión avanzada

// Rutas de planillas
'tesoreria.prendas.planillas.index'       → Lista de planillas
'tesoreria.prendas.planillas.show'        → Ver planilla
'tesoreria.prendas.planillas.pdf'         → PDF de planilla
'tesoreria.prendas.planillas.print'       → Vista de impresión
```

### Permisos Requeridos

El módulo requiere los siguientes permisos (definidos en la tabla `permissions`):
- `prendas.index` - Ver lista de prendas
- `prendas.create` - Crear prendas
- `prendas.edit` - Editar prendas
- `prendas.delete` - Eliminar prendas
- `prendas.reportes` - Acceder a reportes
- `prendas.planillas.index` - Ver planillas
- `prendas.planillas.anular` - Anular planillas

### Auditoría

El módulo usa el trait `LogsActivityTrait` para registrar:
- Creación de registros
- Modificaciones
- Eliminaciones (soft delete)
- Campo afectado y valor anterior/nuevo

---

## Explicación de Términos

| Término | Definición |
|---------|------------|
| **Prenda** | Objeto dado en garantía por un ciudadano ante una municipalidad |
| **Planilla** | Documento que agrupa varias prendas para control y gestión |
| **Recibo** | Documento que certifica el registro de una prenda |
| **Soft Delete** | Eliminación lógica (no física) que permite recuperar datos |
| **Medio de Pago** | Forma de pago utilizada (efectivo, transferencia, etc.) |
| **Orden de Cobro** | Código identificador del cobro municipal |

---

## Mejores Prácticas para Mantenimiento

1. **No modificar `recibo_serie` ni `recibo_numero`** después de creado el registro, ya que son parte de la identificación única y podrían generar duplicados no detectados.

2. **Al anular una planilla**, las prendas quedan sin asignación (`planilla_id = null`). Estas prendas pueden luego asignarse a una nueva planilla.

3. **Los filtros de fecha** (`fecha_desde` y `fecha_hasta`) tienen prioridad sobre `mes` y `year`.

4. **La búsqueda flexible** normaliza acentos, por lo que buscar "ángel" encontrará "Angel" y viceversa.

5. **El campo `transferencia`** es opcional pero si se ingresa, debe ser único en el sistema.

---

## Documentación de Vistas Blade

### Principales Vistas

| Vista | Descripción |
|-------|-------------|
| `index.blade.php` | Tabla principal de prendas con paginación y filtros |
| `create.blade.php` | Modal con formulario de registro |
| `edit.blade.php` | Modal con formulario de edición |
| `show.blade.php` | Modal con detalles completos |
| `prendas-reporte.blade.php` | Formulario de filtros y resultados |
| `print-prendas-advanced.blade.php` | Vista para impresión de reportes |
| `planillas/index.blade.php` | Lista de planillas |
| `planillas/show.blade.php` | Detalle de planilla con prendas |
| `planillas-print.blade.php` | Vista imprimible de planilla |
