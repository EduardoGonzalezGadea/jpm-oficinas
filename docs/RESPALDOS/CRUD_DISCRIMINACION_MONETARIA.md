# CRUD de Discriminación Monetaria

**Fecha de implementación:** 27/07/2026  
**Desarrollador:** Kiro AI  
**Relacionado con:** Sistema / Opciones / Discriminación Monetaria

---

## 📋 Descripción General

Se creó un CRUD completo para gestionar la **Discriminación Monetaria** del sistema, permitiendo registrar y mantener un catálogo de billetes y monedas con su valor numérico y representación textual.

Este módulo es similar al CRUD de "Tipos de Monedas" y se encuentra ubicado en **Sistema > Opciones > Discriminación Monetaria**.

---

## 🎯 Objetivo

Proporcionar una forma estructurada de gestionar la discriminación de billetes y monedas para:
- Registros contables detallados
- Arqueos de caja
- Reportes de discriminación monetaria
- Control de efectivo por denominación

---

## 📊 Estructura de Datos

### Tabla: `tes_discriminaciones_monetarias`

| Campo | Tipo | Descripción | Ejemplo |
|-------|------|-------------|---------|
| `id` | BIGINT | Identificador único | 1, 2, 3... |
| `tipo` | VARCHAR(50) | Tipo de denominación | "Billetes" o "Monedas" |
| `valor` | DECIMAL(10,2) | Valor numérico | 2000.00, 1000.00, 50.00 |
| `texto` | VARCHAR(100) | Representación textual | "dos mil", "mil", "cincuenta" |
| `activo` | BOOLEAN | Estado activo/inactivo | true/false |
| `created_by` | INT | Usuario creador | ID del usuario |
| `updated_by` | INT | Usuario que actualizó | ID del usuario |
| `deleted_by` | INT | Usuario que eliminó | ID del usuario |
| `created_at` | TIMESTAMP | Fecha de creación | 2026-07-27 18:00:00 |
| `updated_at` | TIMESTAMP | Fecha de actualización | 2026-07-27 18:00:00 |
| `deleted_at` | TIMESTAMP | Soft delete | NULL |

### Índices Creados

```sql
INDEX idx_tipo (tipo)
INDEX idx_valor (valor)
INDEX idx_activo (activo)
INDEX idx_tipo_valor (tipo, valor)
```

---

## 💾 Datos Iniciales

La tabla se crea con **12 registros precargados** directamente desde la migración:

### Billetes (7 registros)

| Valor | Texto |
|-------|-------|
| 2000 | dos mil |
| 1000 | mil |
| 500 | quinientos |
| 200 | doscientos |
| 100 | cien |
| 50 | cincuenta |
| 20 | veinte |

### Monedas (5 registros)

| Valor | Texto |
|-------|-------|
| 50 | cincuenta |
| 10 | diez |
| 5 | cinco |
| 2 | dos |
| 1 | uno |

---

## 🏗️ Arquitectura Implementada

### 1. **Migración**
**Archivo:** `database/migrations/2026_07_27_180000_create_tes_discriminaciones_monetarias_table.php`

- Crea la tabla con todos los campos
- Define índices para optimizar consultas
- **Inserta los 12 registros iniciales** usando `DB::table()->insert()`
- Implementa soft deletes

### 2. **Modelo Eloquent**
**Archivo:** `app/Models/Tesoreria/TesDiscriminacionMonetaria.php`

**Características:**
- Fillable: `tipo`, `valor`, `texto`, `activo`, `created_by`, `updated_by`, `deleted_by`
- Casts: `activo` → boolean, `valor` → decimal:2
- Traits: `HasFactory`, `SoftDeletes`, `LogsActivityTrait`
- Relationships: `creator()`, `updater()`, `deleter()`

**Scopes:**
```php
scopeActivos()         // WHERE activo = true
scopeBilletes()        // WHERE tipo = 'Billetes'
scopeMonedas()         // WHERE tipo = 'Monedas'
scopeOrdenado()        // ORDER BY tipo ASC, valor DESC
scopeSearch($term)     // Búsqueda por tipo, valor o texto
```

**Accessors:**
```php
getValorFormateadoAttribute()      // "2.000,00"
getDescripcionCompletaAttribute()  // "Billetes de 2.000,00 (dos mil)"
```

**Boot Method:**
- Tracking automático de usuarios en `creating`, `updating`, `deleting`

### 3. **Componente Livewire**
**Archivo:** `app/Livewire/Tesoreria/Configuracion/TesDiscriminacionesMonetarias.php`

**Funcionalidades:**
- ✅ Paginación (15 registros por página)
- ✅ Búsqueda en tiempo real (por tipo, valor, texto)
- ✅ Caché inteligente con versionado
- ✅ CRUD completo: Create, Read, Update, Delete
- ✅ Validaciones robustas
- ✅ Prevención de duplicados (tipo + valor)
- ✅ Modal de detalles con información de auditoría

**Validaciones:**
```php
'tipo'   => 'required|string|in:Billetes,Monedas'
'valor'  => 'required|numeric|min:0|max:999999.99'
'texto'  => 'required|string|max:100'
'activo' => 'boolean'
```

**Eventos Dispatch:**
```javascript
'show-modal'           // Abre modal
'discriminacionStore'  // Después de crear
'discriminacionUpdate' // Después de actualizar
'alert'                // Notificaciones toast
```

### 4. **Vista Blade**
**Archivo:** `resources/views/livewire/tesoreria/configuracion/tes-discriminaciones-monetarias.blade.php`

**Componentes UI:**

#### **Tabla Principal**
- Columnas: Tipo (con icono), Valor (formateado), Texto, Estado, Acciones
- Iconos dinámicos:
  - 💵 `fas fa-money-bill` para Billetes (verde)
  - 🪙 `fas fa-coins` para Monedas (amarillo)
- Formato de valor: `$ 2.000,00`
- Badges de estado: Activo (verde) / Inactivo (gris)

#### **Modal Crear/Editar**
- Select para **Tipo** (Billetes/Monedas)
- Input numérico para **Valor** con prefijo `$`
- Input text para **Texto** descriptivo
- Switch para **Activo**
- Validación en tiempo real con mensajes de error

#### **Modal de Detalles**
- Información completa del registro
- Descripción completa generada
- Datos de auditoría:
  - Fecha de creación
  - Última actualización
  - Usuario creador
  - Usuario que actualizó

#### **Búsqueda**
- Input con icono de búsqueda
- Búsqueda en tiempo real con `wire:model.live`
- Placeholder descriptivo

#### **Confirmación de Eliminación**
- SweetAlert2 con mensaje personalizado
- Prevención de eliminación accidental

### 5. **Vista Wrapper**
**Archivo:** `resources/views/tesoreria/configuracion/tes-discriminaciones-monetarias/index-livewire.blade.php`

Simple wrapper que extiende el layout principal y carga el componente Livewire.

---

## 🌐 Rutas Implementadas

### Ruta Principal
```php
Route::view('discriminaciones-monetarias', 
    'tesoreria.configuracion.tes-discriminaciones-monetarias.index-livewire')
    ->name('tes-discriminaciones-monetarias.index')
    ->middleware('modulo:tesoreria');
```

**URL completa:** `/tesoreria/configuracion/discriminaciones-monetarias`

**Nombre de ruta:** `tesoreria.configuracion.tes-discriminaciones-monetarias.index`

**Protección:** Middleware `modulo:tesoreria` (solo usuarios de Tesorería)

---

## 🧭 Navegación

### Ubicación en el Menú

```
Sistema
 └─ Opciones
     ├─ Medios de Pago
     ├─ Tipos de Monedas
     ├─ 🪙 Discriminación Monetaria  ← NUEVO
     ├─ Bancos
     └─ Cuentas Bancarias
```

**Icono:** `fas fa-coins` (monedas)

### Código del Menú

**Archivo:** `resources/views/layouts/nav.blade.php`

```html
<a class="dropdown-item" href="{{ route('tesoreria.configuracion.tes-discriminaciones-monetarias.index') }}">
    <i class="fas fa-coins mr-2"></i>Discriminación Monetaria
</a>
```

---

## 📁 Archivos Creados/Modificados

### Archivos Creados (5)

1. `database/migrations/2026_07_27_180000_create_tes_discriminaciones_monetarias_table.php`
2. `app/Models/Tesoreria/TesDiscriminacionMonetaria.php`
3. `app/Http/Livewire/Tesoreria/Configuracion/TesDiscriminacionesMonetarias.php` ⚠️
4. `resources/views/livewire/tesoreria/configuracion/tes-discriminaciones-monetarias.blade.php`
5. `resources/views/tesoreria/configuracion/tes-discriminaciones-monetarias/index-livewire.blade.php`

⚠️ **Nota importante:** El componente Livewire debe estar en `app/Http/Livewire` (no en `app/Livewire`) porque la configuración de Livewire especifica `'class_namespace' => 'App\\Http\\Livewire'` en `config/livewire.php`.

### Archivos Modificados (2)

1. `routes/tesoreria.php` - Agregada ruta de configuración
2. `resources/views/layouts/nav.blade.php` - Agregada entrada de menú

---

## ✅ Funcionalidades Implementadas

### CRUD Completo

| Acción | Descripción | Validaciones |
|--------|-------------|--------------|
| **Create** | Crear nueva discriminación | ✅ Tipo requerido (in:Billetes,Monedas)<br>✅ Valor requerido (0-999999.99)<br>✅ Texto requerido (max:100)<br>✅ No duplicar tipo+valor |
| **Read** | Listar con paginación | ✅ 15 registros por página<br>✅ Búsqueda en tiempo real<br>✅ Ordenado por tipo y valor |
| **Update** | Editar discriminación | ✅ Mismas validaciones que Create<br>✅ No duplicar tipo+valor (excepto actual) |
| **Delete** | Eliminación lógica | ✅ Confirmación con SweetAlert<br>✅ Soft delete (recuperable) |
| **Details** | Ver detalles completos | ✅ Modal con toda la info<br>✅ Datos de auditoría |

### Características Avanzadas

#### **🔍 Búsqueda Inteligente**
- Por tipo: "Billetes", "Monedas"
- Por valor: "2000", "50"
- Por texto: "dos mil", "cincuenta"
- Búsqueda parcial con LIKE

#### **💾 Sistema de Caché**
- Caché por 24 horas
- Versionado para invalidación
- Keys únicos por búsqueda y página

#### **👥 Auditoría Completa**
- Registro de usuario creador
- Registro de usuario actualizador
- Registro de usuario eliminador
- Timestamps automáticos

#### **🔒 Validación de Duplicados**
- Previene crear tipo+valor duplicado
- Mensaje de error claro al usuario
- Validación tanto en Create como en Update

#### **📊 Ordenamiento Inteligente**
- Primero Billetes, luego Monedas
- Dentro de cada tipo, de mayor a menor valor

#### **🎨 Interfaz Rica**
- Iconos diferenciados por tipo
- Formato monetario correcto
- Badges de estado visual
- Modales responsivos

---

## 🧪 Testing Manual

### Verificaciones Sugeridas

#### 1. **Migración**
```bash
php artisan migrate --path=database/migrations/2026_07_27_180000_create_tes_discriminaciones_monetarias_table.php
```
✅ Verificar que se creen los 12 registros iniciales

#### 2. **Acceso al CRUD**
- Navegar a: Sistema > Opciones > Discriminación Monetaria
- ✅ Verificar que se muestra el listado
- ✅ Verificar que se muestran los 12 registros

#### 3. **Búsqueda**
- Buscar "Billetes" → debe mostrar 7
- Buscar "Monedas" → debe mostrar 5
- Buscar "2000" → debe mostrar 1
- Buscar "cincuenta" → debe mostrar 2 (billete y moneda)

#### 4. **Crear**
- Crear: Tipo=Billetes, Valor=10, Texto="diez"
- ✅ Debe guardarse correctamente
- ✅ Debe aparecer en el listado ordenado

#### 5. **Validación de Duplicados**
- Intentar crear: Tipo=Billetes, Valor=2000
- ✅ Debe mostrar error "Ya existe..."

#### 6. **Editar**
- Editar un registro existente
- Cambiar el texto
- ✅ Debe actualizarse
- ✅ Debe mostrar usuario actualizador

#### 7. **Eliminar**
- Eliminar un registro
- ✅ Debe pedir confirmación
- ✅ Debe eliminarse (soft delete)
- ✅ No debe aparecer en el listado

#### 8. **Detalles**
- Ver detalles de un registro
- ✅ Debe mostrar toda la información
- ✅ Debe mostrar descripción completa
- ✅ Debe mostrar datos de auditoría

---

## 🎯 Casos de Uso

### 1. **Arqueo de Caja**
Utilizar las discriminaciones para detallar el efectivo en caja:
- 5 billetes de $2000 = $10,000
- 10 billetes de $1000 = $10,000
- 20 monedas de $10 = $200

### 2. **Reportes Contables**
Generar reportes con discriminación detallada del efectivo recaudado.

### 3. **Control de Efectivo**
Seguimiento de las denominaciones disponibles en caja para dar vuelto.

### 4. **Cierre de Caja**
Registro detallado de billetes y monedas al final del día.

---

## 🔧 Mantenimiento

### Agregar Nueva Denominación

1. **Vía Interfaz:** Sistema > Opciones > Discriminación Monetaria > Nueva Discriminación
2. **Vía Código:** Insertar en la migración o crear seeder

### Desactivar Denominación

No eliminar, solo marcar como `activo = false` para mantener históricos.

### Consultas SQL Útiles

```sql
-- Ver todas las denominaciones activas ordenadas
SELECT tipo, valor, texto 
FROM tes_discriminaciones_monetarias 
WHERE activo = 1 AND deleted_at IS NULL
ORDER BY tipo ASC, valor DESC;

-- Ver solo billetes
SELECT * FROM tes_discriminaciones_monetarias 
WHERE tipo = 'Billetes' AND activo = 1;

-- Ver solo monedas
SELECT * FROM tes_discriminaciones_monetarias 
WHERE tipo = 'Monedas' AND activo = 1;
```

---

## 📝 Notas Técnicas

### Por Qué Sin Seeder

Los datos se insertaron directamente en la migración (método `up()`) en lugar de usar un seeder porque:
1. Son datos esenciales para el funcionamiento del sistema
2. Se ejecutan automáticamente con la migración
3. No requieren comando adicional
4. Garantiza que la tabla nunca esté vacía

### Formato de Valores

- Los valores se almacenan como `DECIMAL(10,2)`
- Permite hasta $999,999.99
- Suficiente para cualquier denominación actual o futura

### Soft Deletes

Los registros eliminados no se borran físicamente:
- Mantiene integridad referencial
- Permite auditoría histórica
- Recuperación si es necesario

---

## 🚀 Próximas Mejoras Sugeridas

1. **API Endpoints** para consumo externo
2. **Exportación a Excel/PDF** del catálogo
3. **Importación masiva** desde archivo
4. **Imágenes** de billetes y monedas
5. **Histórico de cambios** con tabla de auditoría
6. **Conversión automática** a otras monedas
7. **Calculadora** de discriminación monetaria

---

## ✅ Checklist de Verificación

- [x] Migración creada y ejecutada
- [x] 12 registros iniciales insertados
- [x] Modelo Eloquent con traits y scopes
- [x] Componente Livewire con CRUD completo
- [x] Vista Blade con UI completa
- [x] Vista wrapper creada
- [x] Ruta registrada en routes/tesoreria.php
- [x] Entrada agregada al menú nav.blade.php
- [x] Validaciones implementadas
- [x] Prevención de duplicados
- [x] Sistema de caché
- [x] Auditoría de usuarios
- [x] Soft deletes
- [x] Sin errores de diagnóstico

---

## 🐛 Troubleshooting

### Error: "Unable to find component"

**Síntoma:** Al acceder al módulo aparece error 500 con mensaje `Unable to find component: [tesoreria.configuracion.tes-discriminaciones-monetarias]`

**Causa:** El componente Livewire no está en la ubicación correcta o el caché no está actualizado.

**Solución:**

1. Verificar que el componente esté en `app/Http/Livewire/Tesoreria/Configuracion/TesDiscriminacionesMonetarias.php`
2. Verificar que el namespace sea `App\Http\Livewire\Tesoreria\Configuracion`
3. Ejecutar comandos de limpieza:
   ```bash
   php artisan livewire:discover
   php artisan cache:clear
   php artisan view:clear
   php artisan config:clear
   ```

### Error: "Route not defined"

**Síntoma:** Error 500 con mensaje `Route [tesoreria.configuracion.tes-discriminaciones-monetarias.index] not defined`

**Causa:** Caché de rutas desactualizado.

**Solución:**
```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

### Datos iniciales no aparecen

**Síntoma:** La tabla está vacía al acceder por primera vez.

**Causa:** La migración no se ejecutó o falló.

**Solución:**
```bash
# Verificar status de migraciones
php artisan migrate:status

# Ejecutar la migración específica
php artisan migrate --path=database/migrations/2026_07_27_180000_create_tes_discriminaciones_monetarias_table.php

# Verificar datos en base de datos
SELECT * FROM tes_discriminaciones_monetarias;
```

---

## 📞 Soporte

Para consultas o problemas con este módulo:
- Revisar logs de Laravel: `storage/logs/laravel.log`
- Verificar migración ejecutada: `php artisan migrate:status`
- Verificar permisos de usuario: `modulo:tesoreria`

---

**Documento generado:** 27/07/2026  
**Versión:** 1.0  
**Estado:** ✅ Implementación Completa
