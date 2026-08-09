# Verificación de Configuración del Módulo de Tesorería

## Descripción

El comando `tesoreria:verificar` valida que existan todos los conceptos, detalles y medios de pago requeridos para el correcto funcionamiento del módulo de Tesorería, específicamente:

- **Caja Chica**: Fondo Fijo, Pendientes y Pagos Directos
- **Recaudaciones**: Artículo 222 y Recaudación Diaria
- **Estados de Recaudación**: Transferencias de planillas

## Uso

### Verificar sin modificar

```bash
php artisan tesoreria:verificar
```

Muestra un reporte de todos los elementos faltantes sin crear nada.

### Verificar con reparación automática

```bash
php artisan tesoreria:verificar --fix
```

Crea automáticamente los elementos faltantes que sean necesarios.

### Verificar con información detallada

```bash
php artisan tesoreria:verificar --detallado
```

Muestra información adicional de cada elemento verificado, incluyendo sus IDs en la base de datos.

### Combinar opciones

```bash
php artisan tesoreria:verificar --fix --detallado
```

## Elementos Verificados

### 1. Tipos de Asiento
- **Entrada** (signo: 1)
- **Salida** (signo: -1)

### 2. Conceptos
- **Caja Chica**
- **Recaudación Artículo 222**
- **Recaudación Diaria**

### 3. Detalles de Caja Chica
Bajo el concepto "Caja Chica":
- **Fondo Fijo**
- **Pendiente**
- **Pagos**

### 4. Detalles de Recaudaciones
- Bajo "Recaudación Artículo 222": **Recaudaciones varias de Artículo 222**
- Bajo "Recaudación Diaria": **Otras recaudaciones varias**

### 5. Medios de Pago
- **Efectivo** (activo)
- **Transferencia Bancaria** (activo, búsqueda flexible)

## Salida del Comando

### Ejemplo: Configuración Correcta

```
═══════════════════════════════════════════════════════════════
  Verificación de Configuración del Módulo de Tesorería
═══════════════════════════════════════════════════════════════

► Verificando tipos de asiento...
► Verificando conceptos...
► Verificando detalles de Caja Chica...
► Verificando detalles de Recaudaciones...
► Verificando medios de pago...

═══════════════════════════════════════════════════════════════
✓ Verificación completada: Configuración correcta
═══════════════════════════════════════════════════════════════
```

### Ejemplo: Elementos Faltantes

```
═══════════════════════════════════════════════════════════════
  Verificación de Configuración del Módulo de Tesorería
═══════════════════════════════════════════════════════════════

► Verificando tipos de asiento...
► Verificando conceptos...
  ✗ Falta concepto: Recaudación Diaria
► Verificando detalles de Caja Chica...
  ✗ Falta detalle: Pendiente
► Verificando detalles de Recaudaciones...
  ✗ Falta detalle: Otras recaudaciones varias
► Verificando medios de pago...

═══════════════════════════════════════════════════════════════
✗ Se encontraron 3 problema(s)

  Ejecute con --fix para intentar repararlos automáticamente:
  php artisan tesoreria:verificar --fix
═══════════════════════════════════════════════════════════════
```

### Ejemplo: Reparación Automática

```
═══════════════════════════════════════════════════════════════
  Verificación de Configuración del Módulo de Tesorería
═══════════════════════════════════════════════════════════════

⚠  Modo reparación activado: se crearán elementos faltantes

► Verificando tipos de asiento...
► Verificando conceptos...
  ✗ Falta concepto: Recaudación Diaria
    → Creado concepto: Recaudación Diaria (ID: 5)
► Verificando detalles de Caja Chica...
  ✗ Falta detalle: Pendiente
    → Creado detalle: Pendiente (ID: 12)
► Verificando detalles de Recaudaciones...
  ✗ No se puede verificar detalles: concepto Recaudación Diaria no existe
► Verificando medios de pago...

═══════════════════════════════════════════════════════════════
✓ Verificación completada: Configuración correcta
  Se crearon 2 elemento(s)
═══════════════════════════════════════════════════════════════
```

## Cuándo Ejecutar

### Obligatorio
- **Después de instalar el sistema** en un nuevo servidor
- **Después de restaurar una base de datos** desde backup
- **Antes de usar por primera vez** los módulos de Tesorería

### Recomendado
- **Después de migraciones** que afecten tablas de Tesorería
- **Periódicamente** (puede programarse como tarea diaria)
- **Antes de deployar** a producción (en CI/CD)

## Integración con CI/CD

Agregar al pipeline de deployment:

```yaml
# Ejemplo para GitHub Actions
- name: Verificar configuración de Tesorería
  run: php artisan tesoreria:verificar --fix
```

```yaml
# Ejemplo para GitLab CI
tesoreria_check:
  script:
    - php artisan tesoreria:verificar --fix
```

## Programación Automática

Agregar en `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Verificar diariamente a las 8:00 AM
    $schedule->command('tesoreria:verificar')
        ->daily()
        ->at('08:00')
        ->emailOutputOnFailure('admin@example.com');
}
```

## Códigos de Salida

- **0**: Verificación exitosa, no hay problemas
- **1**: Se encontraron problemas de configuración

Útil para scripts:

```bash
#!/bin/bash
php artisan tesoreria:verificar
if [ $? -ne 0 ]; then
    echo "ERROR: Configuración de Tesorería incompleta"
    exit 1
fi
```

## Solución de Problemas

### El comando reporta elementos faltantes pero existen

Verifique que los nombres sean **exactamente** iguales (mayúsculas/minúsculas importan):
- "Caja Chica" ≠ "caja chica"
- "Fondo Fijo" ≠ "Fondo fijo"

### El comando con --fix no puede crear elementos

Posibles causas:
1. **Permisos de base de datos**: Verifique que el usuario tenga permisos INSERT
2. **Restricciones de integridad**: Si hay FK constraints que fallan
3. **Campos requeridos**: Algunos modelos pueden requerir campos adicionales

En estos casos, cree los elementos manualmente desde la interfaz web:
- Tesorería → Libro Diario → Conceptos
- Tesorería → Libro Diario → Detalles
- Tesorería → Libro Diario → Medios de Pago

### Error: "Class not found"

Ejecute:
```bash
composer dump-autoload
```

## Métodos Estáticos en Modelos

Los modelos ahora incluyen métodos estáticos para obtener configuraciones requeridas:

```php
// Conceptos
$cajaChica = LbConcepto::cajaChica();
$recaudacion222 = LbConcepto::recaudacion222();
$recaudacionDiaria = LbConcepto::recaudacionDiaria();

// Detalles
$fondoFijo = LbDetalle::fondoFijo();
$pendiente = LbDetalle::pendiente();
$pagos = LbDetalle::pagos();
$recaudacionesVarias222 = LbDetalle::recaudacionesVarias222();
$otrasRecaudaciones = LbDetalle::otrasRecaudacionesVarias();

// Medios de Pago
$efectivo = MedioDePago::efectivo();
$transferencia = MedioDePago::transferencia();
```

Estos métodos lanzan excepciones claras si el elemento no existe, indicando al usuario cómo crearlo.

## Soporte

Si el comando reporta problemas persistentes, contacte al equipo de desarrollo con:
1. Salida completa del comando con `--detallado`
2. Versión de PHP y Laravel
3. Logs de `storage/logs/laravel.log`
