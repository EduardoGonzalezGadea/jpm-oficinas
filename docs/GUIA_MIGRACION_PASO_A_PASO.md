# Guía Paso a Paso: Migración Laravel 12 + Livewire 4

## 📋 Pre-requisitos

Antes de empezar, asegúrate de tener:

- [x] Tests completos funcionando (335+ tests)
- [ ] Backup completo de base de datos
- [ ] Backup completo del código
- [ ] Acceso a ambiente de staging
- [ ] Equipo notificado de la migración

---

## 🚀 Fase 1: Preparación (Día 1)

### Paso 1.1: Verificar Estado Actual

```bash
# Ejecutar tests baseline
php artisan test

# Debe mostrar: Tests: 335 passed
```

✅ **Verificar**: Todos los tests deben pasar antes de continuar.

### Paso 1.2: Crear Backups

```bash
# 1. Backup de base de datos
mysqldump -u root -p tesoreria_oficinas > backup_$(date +%Y%m%d).sql

# 2. Crear tag git del estado actual
git tag -a v1.0-pre-migracion -m "Estado antes de migración Laravel 12"
git push origin v1.0-pre-migracion
```

### Paso 1.3: Crear Rama de Migración

```bash
# Crear y cambiar a rama de migración
git checkout -b feature/laravel-12-migration

# Verificar rama actual
git branch
```

✅ **Verificar**: Debes estar en la rama `feature/laravel-12-migration`

---

## 🔧 Fase 2: Actualización de Laravel (Día 2-3)

### Paso 2.1: Actualizar composer.json

```bash
# Hacer backup del composer.json actual
cp composer.json composer.json.backup

# Reemplazar con la versión Laravel 12
cp composer.laravel12.json composer.json
```

**Archivo `composer.json` actualizado**:
```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0",
        "livewire/livewire": "^4.0",
        ...
    }
}
```

### Paso 2.2: Actualizar Dependencias

```bash
# Limpiar caché de composer
composer clear-cache

# Actualizar dependencias (esto puede tardar varios minutos)
composer update

# Si hay conflictos, resolver uno por uno
# Documentar cada conflicto y su solución
```

**Conflictos Comunes Esperados**:

| Paquete | Solución |
|---------|----------|
| `doctrine/dbal` | Actualizar a `^4.0` |
| `spatie/laravel-permission` | Actualizar a `^6.0` |
| `spatie/laravel-backup` | Actualizar a `^9.0` |
| `phpoffice/phpspreadsheet` | Actualizar a `^2.0` |

### Paso 2.3: Verificar Instalación

```bash
# Ver versión de Laravel
php artisan --version
# Debe mostrar: Laravel Framework 12.x.x

# Ver versión de Livewire
php artisan about | grep Livewire
```

### Paso 2.4: Ejecutar Tests

```bash
# Ejecutar suite completa
php artisan test

# Documentar cualquier test que falle
```

⚠️ **Si fallan tests**: Antes de continuar, revisar y corregir los errores.

---

## 🎯 Fase 3: Detección de Patrones Livewire (Día 3)

### Paso 3.1: Ejecutar Script de Detección

```powershell
# Ejecutar desde la raíz del proyecto
.\scripts\detectar-patrones-livewire.ps1
```

**Output Esperado**:
```
Total de patrones encontrados: XXX
Total de componentes afectados: YYY

Reportes guardados en: ./migracion-reports/
```

### Paso 3.2: Revisar Reportes

```bash
cd migracion-reports
ls -la
```

Archivos generados:
- `00-componentes-afectados.txt` - Lista de componentes
- `01-emits.txt` - Usos de emit()
- `02-emitTo.txt` - Usos de emitTo()
- `03-emitSelf.txt` - Usos de emitSelf()
- `04-emitUp.txt` - Usos de emitUp()
- `05-listeners.txt` - Usos de $listeners
- `06-rules.txt` - Usos de $rules
- `07-public-properties.txt` - Properties sin tipo
- `08-computed-properties.txt` - Computed properties
- `RESUMEN.md` - Resumen ejecutivo

### Paso 3.3: Priorizar Componentes

Abrir `RESUMEN.md` y crear lista priorizada:

**Prioridad 1 - Componentes con Tests** (migrar primero):
- [ ] Caja Chica (66 tests)
- [ ] Libro Diario (57 tests)
- [ ] Multas (83 tests)
- [ ] CFE (85 tests)

**Prioridad 2 - Componentes Críticos Sin Tests**:
- [ ] Certificados
- [ ] Armas
- [ ] Etc.

---

## 🔄 Fase 4: Migración de Componentes (Día 4-10)

### Estrategia de Migración

Para cada componente:
1. Migrar automáticamente (script)
2. Revisar cambios manuales
3. Ejecutar tests
4. Commit cambios

### Paso 4.1: Migrar Componente Individual

```powershell
# Ejemplo: Migrar componente Multa.php
.\scripts\migrar-componente-livewire.ps1 -FilePath "app\Livewire\Tesoreria\Multa.php"
```

**Output**:
```
[✓] Backup creado
[✓] Migrados emit() → dispatch()
[✓] Migrados emitTo() → dispatch()->to()
⚠  $listeners comentado - REQUIERE REVISIÓN MANUAL
...
```

### Paso 4.2: Revisar Cambios Manuales

Abrir el archivo migrado y buscar comentarios `TODO LIVEWIRE 4`:

```php
// TODO LIVEWIRE 4: Migrar a #[On('evento')] - Ver docs
// protected $listeners = ['refrescar' => 'cargarDatos'];
```

**Completar manualmente**:

```php
use Livewire\Attributes\On;

#[On('refrescar')]
public function cargarDatos(): void
{
    // ...
}
```

### Paso 4.3: Patrones Comunes de Migración Manual

#### A. Listeners → Attributes

```php
// ❌ Antes (Livewire 3)
protected $listeners = [
    'multaActualizada' => 'refrescar',
    'cerrarModal' => 'resetForm',
];

// ✅ Después (Livewire 4)
#[On('multaActualizada')]
public function refrescar(): void { }

#[On('cerrarModal')]
public function resetForm(): void { }
```

#### B. Rules → Validate Attributes

```php
// ❌ Antes
protected $rules = [
    'nombre' => 'required|min:3',
    'monto' => 'required|numeric|min:0',
];

// ✅ Después
#[Validate('required|min:3')]
public string $nombre = '';

#[Validate('required|numeric|min:0')]
public float $monto = 0;
```

#### C. Computed Properties

```php
// ❌ Antes
public function getTotalProperty()
{
    return $this->items->sum('monto');
}

// En blade: {{ $this->total }}

// ✅ Después
#[Computed]
public function total(): float
{
    return $this->items->sum('monto');
}

// En blade: {{ $this->total }} (igual)
```

#### D. Properties con Tipado

```php
// ❌ Antes
public $multaId;
public $monto;
public $search;

// ✅ Después
#[Locked]
public int $multaId;

#[Locked]
public float $monto = 0;

#[Modelable]
public string $search = '';
```

### Paso 4.4: Ejecutar Tests del Componente

```bash
# Ejemplo para módulo Multas
php artisan test --filter=Multas

# Debe pasar todos los tests
```

### Paso 4.5: Commit Cambios

```bash
git add app/Livewire/Tesoreria/Multa.php
git commit -m "Migrar componente Multa a Livewire 4

- Migrar emit() a dispatch()
- Migrar listeners a #[On] attributes
- Agregar tipado a properties
- Tests: 83 passed"
```

### Paso 4.6: Repetir para Cada Componente

Ir componente por componente, siguiendo el orden de prioridad.

**Checklist de Migración por Componente**:
- [ ] Ejecutar script de migración automática
- [ ] Revisar y completar TODOs manuales
- [ ] Agregar tipado a properties
- [ ] Ejecutar tests del módulo
- [ ] Commit cambios
- [ ] Marcar como completado en lista

---

## ✅ Fase 5: Verificación (Día 11-12)

### Paso 5.1: Ejecutar Suite Completa de Tests

```bash
# Todos los tests
php artisan test

# Con cobertura
php artisan test --coverage --min=80

# Verbose (para ver detalles)
php artisan test --verbose
```

**Objetivo**: 335+ tests deben pasar.

### Paso 5.2: Revisar Logs

```bash
# Limpiar logs
php artisan log:clear

# Ejecutar aplicación
php artisan serve

# Navegar por módulos principales
# Revisar logs por errores
tail -f storage/logs/laravel.log
```

### Paso 5.3: Pruebas Manuales

**Checklist de Pruebas Manuales**:
- [ ] Login y autenticación
- [ ] Caja Chica: crear, pagar, rendir, recuperar
- [ ] Libro Diario: asientos, redistribuciones
- [ ] Multas: búsqueda, cobro
- [ ] CFE: carga, confirmación
- [ ] Navegación general

### Paso 5.4: Performance Testing

```bash
# Baseline (antes de migración)
# Anotar tiempos de respuesta

# Después de migración
# Comparar tiempos

# Laravel Telescope puede ayudar
composer require laravel/telescope --dev
php artisan telescope:install
```

---

## 🚀 Fase 6: Deploy a Staging (Día 13)

### Paso 6.1: Merge a Staging Branch

```bash
git checkout staging
git merge feature/laravel-12-migration
git push origin staging
```

### Paso 6.2: Deploy a Servidor Staging

```bash
# SSH al servidor staging
ssh user@staging-server

# Pull cambios
cd /var/www/oficinas
git pull origin staging

# Actualizar dependencias
composer install --no-dev --optimize-autoloader

# Ejecutar migraciones si hay
php artisan migrate --force

# Limpiar cachés
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Optimizar
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Paso 6.3: Pruebas en Staging

**Usuarios Beta**:
- Invitar a 3-5 usuarios clave
- Pedirles que prueben funcionalidad crítica
- Documentar cualquier problema

**Checklist**:
- [ ] Funcionalidad básica OK
- [ ] Performance aceptable
- [ ] Sin errores críticos en logs
- [ ] Feedback de usuarios positivo

---

## 📦 Fase 7: Deploy a Producción (Día 14)

### Paso 7.1: Pre-Deploy Checklist

- [ ] Todos los tests pasan
- [ ] Staging funcionando correctamente
- [ ] Backup de producción realizado
- [ ] Equipo notificado del deploy
- [ ] Horario de bajo tráfico confirmado
- [ ] Plan de rollback listo

### Paso 7.2: Deploy Gradual

**Opción A: Blue-Green Deploy** (Recomendado)
- Tener dos ambientes: actual (blue) y nuevo (green)
- Deploy a green
- Probar green
- Cambiar tráfico a green
- Mantener blue como rollback

**Opción B: Deploy Directo con Mantenimiento**
```bash
# Activar modo mantenimiento
php artisan down --message="Actualización del sistema" --retry=60

# Deploy...

# Desactivar mantenimiento
php artisan up
```

### Paso 7.3: Monitoreo Post-Deploy

**Primeras 2 horas** (críticas):
- Monitorear logs en tiempo real
- Verificar métricas de performance
- Revisar reportes de errores
- Estar listo para rollback

**Primer día**:
- Revisar logs cada hora
- Contactar usuarios clave por feedback
- Documentar cualquier issue

**Primera semana**:
- Monitoreo diario
- Recolectar feedback
- Ajustes menores si es necesario

---

## 🔙 Plan de Rollback

Si algo sale mal en producción:

```bash
# 1. Activar mantenimiento
php artisan down

# 2. Revertir a commit anterior
git checkout v1.0-pre-migracion

# 3. Restaurar dependencias
composer install

# 4. Restaurar BD si es necesario
mysql -u root -p tesoreria_oficinas < backup_YYYYMMDD.sql

# 5. Limpiar cachés
php artisan config:clear
php artisan cache:clear

# 6. Desactivar mantenimiento
php artisan up

# 7. Notificar al equipo
```

---

## 📊 Métricas de Éxito

### Durante Migración
- [ ] 100% de tests pasando
- [ ] 0 errores críticos en logs
- [ ] Componentes migrados: X/Y

### Post-Deploy
- [ ] Disponibilidad: >99.9%
- [ ] Performance: igual o mejor
- [ ] Errores reportados: <5 en primera semana
- [ ] Feedback usuarios: positivo

---

## 📝 Documentar Todo

Mantener actualizado el archivo `LOG_MIGRACION.md`:

```markdown
## 2026-08-15
- ✅ Tests baseline: 335 passed
- ✅ Backup BD realizado
- ✅ Rama creada: feature/laravel-12-migration
- ✅ composer.json actualizado
- ⚠️ Conflicto con doctrine/dbal → solucionado

## 2026-08-16
- 🔄 Migrando componente Multa.php
- ✅ Tests Multas: 83 passed
- ✅ Commit realizado

...
```

---

## 🎓 Recursos

### Documentación
- Laravel 12: https://laravel.com/docs/12.x/upgrade
- Livewire 4: https://livewire.laravel.com/docs/upgrade-guide
- Tests: `docs/GUIA_TESTING.md`

### Scripts
- Detección: `scripts/detectar-patrones-livewire.ps1`
- Migración: `scripts/migrar-componente-livewire.ps1`

### Comandos Útiles
```bash
# Tests
php artisan test
php artisan test --filter=NombreModulo
php artisan test --coverage

# Seguridad
php artisan testing:safety-check

# Logs
tail -f storage/logs/laravel.log

# Estado de git
git status
git log --oneline
```

---

## ✅ Checklist Final

Antes de dar por completada la migración:

- [ ] 335+ tests pasando
- [ ] 0 deprecations en logs
- [ ] Performance igual o mejor
- [ ] Staging funcionando 48h sin issues
- [ ] Producción funcionando 1 semana sin issues
- [ ] Documentación actualizada
- [ ] Equipo capacitado
- [ ] Celebrar éxito! 🎉

---

**Última actualización**: 14/08/2026  
**Autor**: Equipo de Desarrollo  
**Estado**: ✅ LISTA PARA USAR
